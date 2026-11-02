<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CoreBundle\Helpers\IsAllowedToEditHelper;
use Chamilo\CoreBundle\Service\Exercise\ExerciseAttemptRecycleBin;
use Chamilo\CoreBundle\Service\Gradebook\GradebookLinkManager;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CoreBundle\State\Gradebook\GradebookLinkResourceResolver;
use Chamilo\CourseBundle\Repository\CQuizRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted(new Expression('is_granted("ROLE_ADMIN") or is_granted("ROLE_CURRENT_COURSE_TEACHER") or is_granted("ROLE_CURRENT_COURSE_SESSION_TEACHER")'))]
final class ExerciseAttemptRecycleBinController extends AbstractController
{
    public function __construct(
        private readonly CidReqHelper $cidReqHelper,
        private readonly IsAllowedToEditHelper $isAllowedToEditHelper,
        private readonly CQuizRepository $quizRepository,
        private readonly GradebookLinkManager $gradebookLinkManager,
        private readonly ExerciseAttemptRecycleBin $recycleBin,
        private readonly SettingsManager $settingsManager,
        private readonly Security $security,
    ) {}

    #[Route('/api/exercise/runtime/{exerciseId}/recycle-bin', name: 'exercise_attempt_recycle_list', requirements: ['exerciseId' => '\d+'], methods: ['GET'])]
    public function list(int $exerciseId): JsonResponse
    {
        [$courseId, $sessionId] = $this->requireContext($exerciseId);

        return $this->json(['items' => $this->recycleBin->listForExercise($courseId, $sessionId, $exerciseId)]);
    }

    #[Route('/api/exercise/runtime/{exerciseId}/recycle-bin/{archiveId}/restore', name: 'exercise_attempt_recycle_restore', requirements: ['exerciseId' => '\d+', 'archiveId' => '\d+'], methods: ['POST'])]
    public function restore(int $exerciseId, int $archiveId): JsonResponse
    {
        [$courseId, $sessionId] = $this->requireContext($exerciseId);
        $course = $this->cidReqHelper->requireDoctrineCourseEntity();
        $session = $this->cidReqHelper->getDoctrineSessionEntity();
        if ($this->gradebookLinkManager->isResourceLocked($course, $session, GradebookLinkResourceResolver::LINK_EXERCISE, $exerciseId)) {
            throw new BadRequestHttpException('This exercise is locked by gradebook.');
        }
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedHttpException('An authenticated teacher is required.');
        }
        $this->recycleBin->restore($archiveId, $courseId, $sessionId, $exerciseId, (int) $user->getId());

        return $this->json(['success' => true, 'message' => 'Attempt restored']);
    }

    /**
     * @return array{int, null|int}
     */
    private function requireContext(int $exerciseId): array
    {
        if (!$this->isAllowedToEditHelper->check(coach: true)) {
            throw new AccessDeniedHttpException('You are not allowed to manage this exercise recycle bin.');
        }
        if (!$this->security->isGranted('ROLE_ADMIN') && 'true' === $this->settingsManager->getSetting('exercise.limit_exercise_teacher_access', true)) {
            throw new AccessDeniedHttpException('Managing exercise results is not allowed.');
        }
        $course = $this->cidReqHelper->requireDoctrineCourseEntity();
        $session = $this->cidReqHelper->getDoctrineSessionEntity();
        if (null === $this->quizRepository->findInCourseContext($exerciseId, $course, $session)) {
            throw new NotFoundHttpException('The exercise does not belong to this course or session.');
        }

        return [(int) $course->getId(), $session?->getId()];
    }
}
