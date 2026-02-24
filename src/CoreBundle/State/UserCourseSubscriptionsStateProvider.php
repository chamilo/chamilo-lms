<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\State;

use ApiPlatform\Doctrine\Orm\Extension\FilterExtension;
use ApiPlatform\Doctrine\Orm\Extension\OrderExtension;
use ApiPlatform\Doctrine\Orm\Extension\PaginationExtension;
use ApiPlatform\Doctrine\Orm\Extension\QueryResultCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGenerator;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\CourseRelUser;
use Chamilo\CoreBundle\Entity\SequenceResource;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Helpers\CourseStudentInfoHelper;
use Chamilo\CoreBundle\Helpers\UserHelper;
use Chamilo\CoreBundle\Repository\CourseRelUserRepository;
use Chamilo\CoreBundle\Repository\SequenceResourceRepository;
use RuntimeException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * @template-implements ProviderInterface<CourseRelUser>
 */
final class UserCourseSubscriptionsStateProvider implements ProviderInterface
{
    private array $extensions;

    public function __construct(
        private readonly UserHelper $userHelper,
        private readonly AccessUrlHelper $accessUrlHelper,
        private readonly CourseRelUserRepository $courseRelUserRepository,
        private readonly CourseStudentInfoHelper $courseStudentInfoHelper,
        private readonly SequenceResourceRepository $sequenceResourceRepository,
        FilterExtension $filterExtension,
        PaginationExtension $paginationExtension,
        OrderExtension $orderExtension,
    ) {
        $this->extensions = [
            $filterExtension,
            $orderExtension,
            $paginationExtension,
        ];
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array|object|null
    {
        $currentUser = $this->userHelper->getCurrent();
        if (!$currentUser instanceof User) {
            throw new AccessDeniedException('User not authenticated');
        }

        $url = $this->accessUrlHelper->getCurrent();
        if (!$url) {
            throw new RuntimeException('Access URL not found');
        }

        $qb = $this->courseRelUserRepository->createQueryBuilder('cru');
        $qb
            ->innerJoin('cru.course', 'c')
            ->addSelect('c')
            ->innerJoin('c.urls', 'cur')
            ->innerJoin('cur.url', 'u')
            ->andWhere('cru.user = :user')
            ->andWhere('u = :url')
            ->andWhere(
                $qb->expr()->eq('c.sticky', $qb->expr()->literal(false))
            )
            ->setParameter('user', $currentUser->getId())
            ->setParameter('url', $url->getId())
        ;

        $queryNameGenerator = new QueryNameGenerator();

        /** @var array<int, CourseRelUser> $items */
        $items = [];

        foreach ($this->extensions as $extension) {
            $extension->applyToCollection($qb, $queryNameGenerator, CourseRelUser::class, $operation, $context);

            if ($extension instanceof QueryResultCollectionExtensionInterface
                && $extension->supportsResult(CourseRelUser::class, $operation, $context)
            ) {
                $items = $extension->getResult($qb, CourseRelUser::class, $operation, $context);
            }
        }

        if (empty($items)) {
            $items = $qb->getQuery()->getResult();
        }

        if (empty($items)) {
            return [];
        }

        $courseIds = [];
        foreach ($items as $cru) {
            $courseId = (int) $cru->getCourse()->getId();
            if ($courseId > 0) {
                $courseIds[] = $courseId;
            }
        }
        $courseIds = array_values(array_unique($courseIds));

        // Teachers per course (User[]).
        /** @var array<int, array<int, User>> $teacherUsersByCourseId */
        $teacherUsersByCourseId = [];
        if (!empty($courseIds)) {
            $teacherUsersByCourseId = $this->courseRelUserRepository->getTeacherUsersByCourseIds($courseIds);
        }

        $userId = (int) $currentUser->getId();

        // Build buckets by session id when available (defensive).
        $courseIdsBySid = [];

        foreach ($items as $cru) {
            $courseId = (int) $cru->getCourse()->getId();
            if ($courseId <= 0) {
                continue;
            }

            $sid = 0;
            if (method_exists($cru, 'getSession') && $cru->getSession()) {
                $sid = (int) $cru->getSession()->getId();
            } elseif (method_exists($cru, 'getSessionId')) {
                $sid = (int) $cru->getSessionId();
            }

            $courseIdsBySid[$sid] ??= [];
            $courseIdsBySid[$sid][] = $courseId;
        }

        foreach ($courseIdsBySid as $sid => $ids) {
            $courseIdsBySid[$sid] = array_values(array_unique(array_map('intval', $ids)));
        }

        // Batch student info by session id.
        $batchBySid = [];
        foreach ($courseIdsBySid as $sid => $ids) {
            if (empty($ids)) {
                $batchBySid[$sid] = [];

                continue;
            }

            $batchBySid[$sid] = $this->courseStudentInfoHelper->getStudentInfoBatchForCourses(
                $userId,
                $ids,
                (int) $sid
            );
        }

        $requirementsCache = [];

        foreach ($items as $cru) {
            $courseId = (int) $cru->getCourse()->getId();
            if ($courseId <= 0) {
                continue;
            }

            // Teachers per course (User[]).
            $teacherUsers = $teacherUsersByCourseId[$courseId] ?? [];

            // Guard against non-object results.
            /** @var array<int, User> $teacherUsers */
            $teacherUsers = array_values(array_filter($teacherUsers, static fn ($t): bool => $t instanceof User));

            $seenTeacherIds = [];
            $normalizedTeachers = [];

            foreach ($teacherUsers as $teacher) {
                $tid = (int) $teacher->getId();
                if ($tid <= 0 || isset($seenTeacherIds[$tid])) {
                    continue;
                }

                $seenTeacherIds[$tid] = true;
                $normalizedTeachers[] = $this->normalizeTeacher($teacher);
            }

            $cru->setTeachersLite($normalizedTeachers);
            $sid = 0;
            if (method_exists($cru, 'getSession') && $cru->getSession()) {
                $sid = (int) $cru->getSession()->getId();
            } elseif (method_exists($cru, 'getSessionId')) {
                $sid = (int) $cru->getSessionId();
            }

            // Student-info fields
            $stats = $batchBySid[$sid][(string) $courseId] ?? null;
            if (\is_array($stats)) {
                $cru->setTrackingProgress($stats['progress'] ?? null);
                $cru->setScore($stats['score'] ?? null);
                $cru->setBestScore($stats['bestScore'] ?? null);
                $cru->setTimeSpentSeconds($stats['timeSpentSeconds'] ?? null);
                $cru->setCertificateAvailable($stats['certificateAvailable'] ?? null);
                $cru->setCompleted($stats['completed'] ?? null);
                $cru->setHasNewContent($stats['hasNewContent'] ?? null);
            }

            // Requirements flags
            if (CourseRelUser::STUDENT !== (int) $cru->getStatus()) {
                $cru->setHasRequirements(false);
                $cru->setAllowSubscription(true);

                continue;
            }

            $cacheKey = $courseId.':'.$sid;

            if (!isset($requirementsCache[$cacheKey])) {
                $sequences = $this->sequenceResourceRepository->getRequirements($courseId, SequenceResource::COURSE_TYPE);

                // Detect if there is at least one valid Course requirement.
                $hasValidRequirement = false;
                foreach ($sequences as $sequence) {
                    foreach ($sequence['requirements'] ?? [] as $resource) {
                        if ($resource instanceof Course) {
                            $hasValidRequirement = true;

                            break 2;
                        }
                    }
                }

                if (!$hasValidRequirement) {
                    $requirementsCache[$cacheKey] = [
                        'hasRequirements' => false,
                        'allowSubscription' => true,
                    ];
                } else {
                    $checked = $this->sequenceResourceRepository->checkRequirementsForUser(
                        $sequences,
                        SequenceResource::COURSE_TYPE,
                        $userId,
                        $sid
                    );

                    $isUnlocked = $this->sequenceResourceRepository->checkSequenceAreCompleted($checked);

                    $requirementsCache[$cacheKey] = [
                        'hasRequirements' => true,
                        'allowSubscription' => (bool) $isUnlocked,
                    ];
                }
            }

            $cru->setHasRequirements($requirementsCache[$cacheKey]['hasRequirements']);
            $cru->setAllowSubscription($requirementsCache[$cacheKey]['allowSubscription']);
        }

        return $items;
    }

    private function normalizeTeacher(User $u): array
    {
        $id = (int) $u->getId();

        $fullName = '';
        if (method_exists($u, 'getCompleteName')) {
            $fullName = (string) $u->getCompleteName();
        } else {
            $fullName = trim((string) ($u->getFirstname() ?? '').' '.(string) ($u->getLastname() ?? ''));
        }

        $illustrationUrl = '';
        if (method_exists($u, 'getIllustrationUrl')) {
            $illustrationUrl = (string) $u->getIllustrationUrl();
        } elseif (method_exists($u, 'getIllustration')) {
            $illustrationUrl = (string) $u->getIllustration();
        }

        return [
            'id' => $id,
            '@id' => '/api/users/'.$id,
            'username' => (string) $u->getUsername(),
            'fullName' => '' !== $fullName ? $fullName : (string) $u->getUsername(),
            'illustrationUrl' => $illustrationUrl,
            'roleLabel' => 'Teacher',
        ];
    }
}
