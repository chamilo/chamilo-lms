<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\LearningPath;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionRelCourse;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CoreBundle\Helpers\LpAdvancedAccessHelper;
use Chamilo\CoreBundle\Helpers\StudentViewHelper;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CGroup;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Repository\CLpRepository;
use Chamilo\CourseBundle\Repository\CShortcutRepository;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * @implements ProviderInterface<CLp>
 */
readonly class LearningPathCollectionProvider implements ProviderInterface
{
    use LearningPathStateHelperTrait;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private CLpRepository $learningPathRepository,
        private CShortcutRepository $shortcutRepository,
        private Security $security,
        private SettingsManager $settingsManager,
        private LpAdvancedAccessHelper $advancedAccessHelper,
        private CidReqHelper $cidReqHelper,
        private StudentViewHelper $studentViewHelper,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     *
     * @return array<int, CLp>
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $filters = $context['filters'] ?? [];
        $parentNodeId = (int) ($filters['resourceNode.parent'] ?? 0);
        if ($parentNodeId <= 0) {
            return [];
        }

        $course = $this->cidReqHelper->getDoctrineCourseEntity();
        if (!$course instanceof Course) {
            return [];
        }

        $courseNode = $course->getResourceNode();
        if (null === $courseNode || $parentNodeId !== (int) $courseNode->getId()) {
            throw new AccessDeniedHttpException('resourceNode.parent does not match the current course.');
        }

        $contextSessionId = (int) ($this->cidReqHelper->getSessionId() ?? 0);
        $filterSessionId = isset($filters['sid']) ? (int) $filters['sid'] : $contextSessionId;
        if ($filterSessionId !== $contextSessionId) {
            throw new AccessDeniedHttpException('The requested session does not match the current context.');
        }

        $session = $this->cidReqHelper->getDoctrineSessionEntity();
        if ($session instanceof Session) {
            $sessionCourse = $this->entityManager->getRepository(SessionRelCourse::class)->findOneBy([
                'course' => $course,
                'session' => $session,
            ]);

            if (!$sessionCourse instanceof SessionRelCourse) {
                throw new AccessDeniedHttpException('The requested session is not linked to this course.');
            }
        }

        $group = $this->getContextGroup($this->entityManager, $this->cidReqHelper, $course);
        $title = isset($filters['title']) ? trim((string) $filters['title']) : null;
        $canManage = $this->canManageLearningPaths($this->security)
            && !$this->studentViewHelper->isActive();

        /** @var array<int, CLp> $learningPaths */
        $learningPaths = $this->learningPathRepository
            ->findAllByCourse($course, $session, '' !== $title ? $title : null, null, !$canManage, null, $group)
            ->getQuery()
            ->getResult()
        ;

        if ([] === $learningPaths) {
            return [];
        }

        $user = $this->security->getUser();
        $prerequisiteState = $user instanceof User
            ? $this->resolvePrerequisiteState($learningPaths, $course, $session, $group, $user)
            : [];

        if (!$canManage) {
            $showBlockedPrerequisite = $this->isTruthySetting(
                $this->settingsManager->getSetting('lp.show_prerequisite_as_blocked', true),
            );

            $learningPaths = $this->filterByAvailability(
                $learningPaths,
                $course,
                $session,
                $user instanceof User ? $user : null,
                $group,
                $prerequisiteState,
                $showBlockedPrerequisite,
            );
        }

        $this->applyContextualManagementState($learningPaths, $course, $session, $group);

        if ($user instanceof User) {
            $progress = $this->learningPathRepository->lastProgressForUser($learningPaths, $user, $session);
            foreach ($learningPaths as $learningPath) {
                $learningPath->setProgress($progress[(int) $learningPath->getIid()] ?? 0);

                $prerequisiteId = $learningPath->getPrerequisite();
                if ($prerequisiteId > 0 && isset($prerequisiteState[$prerequisiteId])) {
                    $learningPath->setPrerequisiteName($prerequisiteState[$prerequisiteId]['name']);
                    $learningPath->setPrerequisiteCompleted($prerequisiteState[$prerequisiteId]['completed']);
                }
            }
        }

        return $learningPaths;
    }

    /**
     * Resolves, for every distinct prerequisite referenced by $learningPaths, the prerequisite
     * learning path's title and whether the current user has completed it.
     *
     * @param array<int, CLp> $learningPaths
     *
     * @return array<int, array{name: string, completed: bool}>
     */
    private function resolvePrerequisiteState(
        array $learningPaths,
        Course $course,
        ?Session $session,
        ?CGroup $group,
        User $user,
    ): array {
        $prerequisiteIds = [];
        foreach ($learningPaths as $learningPath) {
            $prerequisiteId = $learningPath->getPrerequisite();
            if ($prerequisiteId > 0) {
                $prerequisiteIds[$prerequisiteId] = $prerequisiteId;
            }
        }

        if ([] === $prerequisiteIds) {
            return [];
        }

        $lpById = [];
        foreach ($learningPaths as $learningPath) {
            $lpById[(int) $learningPath->getIid()] = $learningPath;
        }

        $prerequisiteLps = [];
        $missingIds = [];
        foreach ($prerequisiteIds as $prerequisiteId) {
            if (isset($lpById[$prerequisiteId])) {
                $prerequisiteLps[$prerequisiteId] = $lpById[$prerequisiteId];
            } else {
                $missingIds[] = $prerequisiteId;
            }
        }

        if ([] !== $missingIds) {
            /** @var array<int, CLp> $missingLps */
            $missingLps = $this->learningPathRepository->findBy(['iid' => $missingIds]);
            foreach ($missingLps as $missingLp) {
                // A prerequisite id can only ever point to another learning path of the same
                // course (that's the only option the settings form offers), but the field is
                // a plain integer column with no DB-level constraint tying it to this course.
                // Re-check the context link here so a prerequisite id tampered with (e.g. a
                // direct API write) can never leak another course's learning path title.
                if ($this->getContextResourceLink($missingLp, $course, $session, $group) instanceof ResourceLink) {
                    $prerequisiteLps[(int) $missingLp->getIid()] = $missingLp;
                }
            }
        }

        $progress = $this->learningPathRepository->lastProgressForUser($prerequisiteLps, $user, $session);

        $state = [];
        foreach ($prerequisiteIds as $prerequisiteId) {
            $prerequisite = $prerequisiteLps[$prerequisiteId] ?? null;
            if (!$prerequisite instanceof CLp) {
                continue;
            }

            $state[$prerequisiteId] = [
                'name' => $prerequisite->getTitle(),
                'completed' => ($progress[$prerequisiteId] ?? 0) >= 100,
            ];
        }

        return $state;
    }

    /**
     * @param array<int, CLp>                                  $learningPaths
     * @param array<int, array{name: string, completed: bool}> $prerequisiteState
     *
     * @return array<int, CLp>
     */
    private function filterByAvailability(
        array $learningPaths,
        Course $course,
        ?Session $session,
        ?User $user,
        ?CGroup $group,
        array $prerequisiteState,
        bool $showBlockedPrerequisite,
    ): array {
        $showUnavailableWithDates = $this->isTruthySetting(
            $this->settingsManager->getSetting('lp.lp_start_and_end_date_visible_in_student_view', true),
        );

        return array_values(array_filter(
            $learningPaths,
            function (CLp $learningPath) use (
                $course,
                $session,
                $user,
                $group,
                $showUnavailableWithDates,
                $prerequisiteState,
                $showBlockedPrerequisite,
            ): bool {
                if ($user instanceof User && !$this->advancedAccessHelper->isAllowed($course, $learningPath, $session, $user)) {
                    return false;
                }

                $category = $learningPath->getCategory();
                if (null !== $category) {
                    $categoryLink = $this->getContextResourceLink($category, $course, $session, $group);
                    if (!$categoryLink instanceof ResourceLink || ResourceLink::VISIBILITY_PUBLISHED !== $categoryLink->getVisibility()) {
                        return false;
                    }
                }

                $prerequisiteId = $learningPath->getPrerequisite();
                if ($prerequisiteId > 0 && !$showBlockedPrerequisite) {
                    $completed = $prerequisiteState[$prerequisiteId]['completed'] ?? true;
                    if (!$completed) {
                        return false;
                    }
                }

                if ($this->isCurrentlyAvailable($learningPath)) {
                    return true;
                }

                return $showUnavailableWithDates && $learningPath->getDisplayNotAllowedLp();
            },
        ));
    }

    /**
     * @param array<int, CLp> $learningPaths
     */
    private function applyContextualManagementState(
        array $learningPaths,
        Course $course,
        ?Session $session,
        ?CGroup $group,
    ): void {
        foreach ($learningPaths as $learningPath) {
            $link = $this->getContextResourceLink($learningPath, $course, $session, $group);
            $exactLink = $learningPath->getResourceNode()?->getResourceLinkByContext($course, $session, $group);

            $learningPath->setVisible(
                $link instanceof ResourceLink && ResourceLink::VISIBILITY_PUBLISHED === $link->getVisibility(),
            );
            $learningPath->setManageableInContext($exactLink instanceof ResourceLink);
            $learningPath->setPublishedOnCourseHome(
                null !== $this->shortcutRepository->findShortcutFromResourceInCourse($learningPath, $course),
            );
        }
    }

    private function isCurrentlyAvailable(CLp $learningPath): bool
    {
        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $startDate = $learningPath->getPublishedOn();
        $endDate = $learningPath->getExpiredOn();

        if ($startDate instanceof DateTimeInterface && $startDate > $now) {
            return false;
        }

        if ($endDate instanceof DateTimeInterface && $endDate < $now) {
            return false;
        }

        return true;
    }

    private function isTruthySetting(mixed $value): bool
    {
        return \in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on'], true);
    }
}
