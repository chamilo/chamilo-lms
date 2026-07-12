<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\CourseProgress;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\CourseProgress\CourseProgressThematicAction;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Repository\ResourceLinkRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CAttendance;
use Chamilo\CourseBundle\Entity\CThematic;
use Chamilo\CourseBundle\Entity\CThematicAdvance;
use Chamilo\CourseBundle\Entity\CThematicPlan;
use Chamilo\CourseBundle\Repository\CAttendanceRepository;
use Chamilo\CourseBundle\Repository\CThematicRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Throwable;

/**
 * @implements ProcessorInterface<CourseProgressThematicAction, CourseProgressThematicAction>
 */
final readonly class CourseProgressThematicActionProcessor implements ProcessorInterface
{
    use CourseProgressAccessHelperTrait;

    public function __construct(
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager,
        private CThematicRepository $thematicRepository,
        private CAttendanceRepository $attendanceRepository,
        private ResourceLinkRepository $resourceLinkRepository,
        private Security $security,
        private SettingsManager $settingsManager,
        private CsrfTokenManagerInterface $csrfTokenManager,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = [],
    ): CourseProgressThematicAction {
        if (!$data instanceof CourseProgressThematicAction) {
            throw new BadRequestHttpException('The request payload is invalid.');
        }

        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        $course = $this->getCourseProgressCourse($request, $this->entityManager);
        $this->assertCourseProgressToolEnabled($this->entityManager, $course);
        $session = $this->getCourseProgressSession($request, $this->entityManager);
        $this->assertSessionBelongsToCourse($session, $course);
        $this->assertCanManage($request, $course, $session);
        $this->validateCsrfToken($data->csrfToken);

        switch ($operation->getName()) {
            case 'post_course_progress_thematic_copy':
                $this->copyThematic($data, $course, $session);

                break;

            case 'post_course_progress_thematic_move':
                $this->moveThematic($data, $course, $session);

                break;

            case 'post_course_progress_thematic_bulk_delete':
                $this->bulkDeleteThematics($data, $course, $session);

                break;

            default:
                throw new BadRequestHttpException('The requested thematic action is invalid.');
        }

        $data->totalAverage = $this->thematicRepository->calculateTotalAverageForCourse($course, $session);

        return $data;
    }

    private function assertCanManage(Request $request, Course $course, ?Session $session): void
    {
        if (!$this->isCourseProgressStudentView($request, (int) $course->getId())
            && $this->canManageCourseProgress(
                $this->entityManager,
                $this->security,
                $this->settingsManager,
                $course,
                $session,
            )
        ) {
            return;
        }

        throw new AccessDeniedHttpException('You are not allowed to manage course progress in this context.');
    }

    private function copyThematic(
        CourseProgressThematicAction $data,
        Course $course,
        ?Session $session,
    ): void {
        $source = $this->getActionableThematic($data->thematicId, $course, $session, 'EDIT');
        $allowedAttendanceIds = $this->getAllowedAttendanceIds($course, $session);

        $this->entityManager->beginTransaction();

        try {
            $copy = (new CThematic())
                ->setTitle($source->getTitle())
                ->setContent((string) $source->getContent())
                ->setParent($course)
                ->addCourseLink($course, $session)
                ->setActive(true)
            ;

            $this->thematicRepository->create($copy);

            foreach ($source->getPlans() as $sourcePlan) {
                if (!$sourcePlan instanceof CThematicPlan) {
                    continue;
                }

                $plan = (new CThematicPlan())
                    ->setThematic($copy)
                    ->setTitle((string) $sourcePlan->getTitle())
                    ->setDescription($sourcePlan->getDescription())
                    ->setDescriptionType($sourcePlan->getDescriptionType())
                ;

                $this->entityManager->persist($plan);
            }

            foreach ($source->getAdvances() as $sourceAdvance) {
                if (!$sourceAdvance instanceof CThematicAdvance) {
                    continue;
                }

                $advance = (new CThematicAdvance())
                    ->setThematic($copy)
                    ->setContent((string) $sourceAdvance->getContent())
                    ->setStartDate(clone $sourceAdvance->getStartDate())
                    ->setDuration((int) $sourceAdvance->getDuration())
                    ->setDoneAdvance(false)
                ;

                $attendance = $sourceAdvance->getAttendance();
                if ($attendance instanceof CAttendance
                    && null !== $attendance->getIid()
                    && isset($allowedAttendanceIds[(int) $attendance->getIid()])
                ) {
                    $advance->setAttendance($attendance);
                }

                $this->entityManager->persist($advance);
            }

            $this->entityManager->flush();
            $this->entityManager->commit();
        } catch (Throwable $exception) {
            $this->entityManager->rollback();

            throw $exception;
        }

        $data->copiedThematicId = $copy->getIid();
        $data->affectedThematicIds = null !== $copy->getIid() ? [(int) $copy->getIid()] : [];
    }

    private function moveThematic(
        CourseProgressThematicAction $data,
        Course $course,
        ?Session $session,
    ): void {
        if ($session instanceof Session) {
            throw new AccessDeniedHttpException('Thematic ordering is only available in the base course.');
        }

        $direction = trim($data->direction);
        if (!\in_array($direction, ['up', 'down'], true)) {
            throw new BadRequestHttpException('The thematic move direction is invalid.');
        }

        $thematic = $this->getActionableThematic($data->thematicId, $course, null, 'EDIT');
        $orderedThematics = $this->thematicRepository->getThematicListForCourse($course);
        $position = $this->findThematicPosition($orderedThematics, (int) $thematic->getIid());

        if ('up' === $direction && 0 === $position) {
            throw new BadRequestHttpException('The thematic is already in the first position.');
        }

        if ('down' === $direction && $position >= \count($orderedThematics) - 1) {
            throw new BadRequestHttpException('The thematic is already in the last position.');
        }

        $resourceNode = $thematic->getResourceNode();
        $resourceLink = $resourceNode?->getResourceLinkByContext($course, null);

        if (null === $resourceLink) {
            throw new NotFoundHttpException('The thematic resource link was not found.');
        }

        $lastDoneAdvance = $this->thematicRepository->findLastDoneAdvanceForCourse($course);
        $lastDoneAdvanceId = null !== $lastDoneAdvance?->getIid() ? (int) $lastDoneAdvance->getIid() : null;

        if ('up' === $direction) {
            $resourceLink->moveUpPosition();
        } else {
            $resourceLink->moveDownPosition();
        }

        $this->entityManager->flush();
        $this->normalizeCompletionSequence($course, $lastDoneAdvanceId);

        $data->affectedThematicIds = [(int) $thematic->getIid()];
    }

    private function bulkDeleteThematics(
        CourseProgressThematicAction $data,
        Course $course,
        ?Session $session,
    ): void {
        $thematicIds = $this->normalizeThematicIds($data->thematicIds);
        if ([] === $thematicIds) {
            throw new BadRequestHttpException('At least one thematic id is required.');
        }

        $thematics = [];
        foreach ($thematicIds as $thematicId) {
            $thematics[] = $this->getActionableThematic($thematicId, $course, $session, 'DELETE');
        }

        $this->entityManager->beginTransaction();

        try {
            foreach ($thematics as $thematic) {
                $this->resourceLinkRepository->removeByResourceInContext($thematic, $course, $session);
            }

            $this->entityManager->commit();
        } catch (Throwable $exception) {
            $this->entityManager->rollback();

            throw $exception;
        }

        $data->affectedThematicIds = $thematicIds;
    }

    private function getActionableThematic(
        int $thematicId,
        Course $course,
        ?Session $session,
        string $attribute,
    ): CThematic {
        if ($thematicId <= 0) {
            throw new BadRequestHttpException('A valid thematic id is required.');
        }

        $thematic = $this->thematicRepository->find($thematicId);
        if (!$thematic instanceof CThematic) {
            throw new NotFoundHttpException('The requested thematic was not found.');
        }

        if (!$this->thematicBelongsToExactContext($thematic, $course, $session)) {
            throw new AccessDeniedHttpException('The requested thematic does not belong to the current course context.');
        }

        $resourceNode = $thematic->getResourceNode();
        if (null === $resourceNode || !$this->security->isGranted($attribute, $resourceNode)) {
            throw new AccessDeniedHttpException('You are not allowed to manage this thematic.');
        }

        return $thematic;
    }

    /**
     * @param CThematic[] $thematics
     */
    private function findThematicPosition(array $thematics, int $thematicId): int
    {
        foreach ($thematics as $position => $thematic) {
            if ($thematic instanceof CThematic && $thematic->getIid() === $thematicId) {
                return $position;
            }
        }

        throw new NotFoundHttpException('The requested thematic was not found in the current order.');
    }

    /**
     * @return array<int, true>
     */
    private function getAllowedAttendanceIds(Course $course, ?Session $session): array
    {
        $attendanceIds = [];

        foreach ($this->attendanceRepository->getAttendanceListForCourse($course, $session) as $attendance) {
            if (!$attendance instanceof CAttendance || null === $attendance->getIid()) {
                continue;
            }

            $attendanceIds[(int) $attendance->getIid()] = true;
        }

        return $attendanceIds;
    }

    private function normalizeCompletionSequence(Course $course, ?int $lastDoneAdvanceId): void
    {
        $isDone = null !== $lastDoneAdvanceId;

        foreach ($this->thematicRepository->findOrderedAdvancesForCourse($course) as $advance) {
            $advance->setDoneAdvance($isDone);
            $this->entityManager->persist($advance);

            if ($isDone && $advance->getIid() === $lastDoneAdvanceId) {
                $isDone = false;
            }
        }

        $this->entityManager->flush();
    }

    /**
     * @param mixed[] $rawIds
     *
     * @return int[]
     */
    private function normalizeThematicIds(array $rawIds): array
    {
        $thematicIds = [];

        foreach ($rawIds as $rawId) {
            if (!\is_int($rawId) && !\is_string($rawId)) {
                continue;
            }

            $thematicId = (int) $rawId;
            if ($thematicId > 0) {
                $thematicIds[$thematicId] = $thematicId;
            }
        }

        return array_values($thematicIds);
    }

    private function validateCsrfToken(string $token): void
    {
        if (!$this->csrfTokenManager->isTokenValid(
            new CsrfToken(CourseProgressThematicProvider::CSRF_TOKEN_ID, $token),
        )) {
            throw new AccessDeniedHttpException('The security token is invalid.');
        }
    }
}
