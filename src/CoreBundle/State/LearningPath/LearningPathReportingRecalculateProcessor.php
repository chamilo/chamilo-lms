<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\LearningPath;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\LearningPath\LearningPathReportingRecalculateInput;
use Chamilo\CoreBundle\Entity\TrackEExercise;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Entity\CLpItem;
use Doctrine\ORM\EntityManagerInterface;
use ExerciseLib;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Throwable;

/** @implements ProcessorInterface<LearningPathReportingRecalculateInput, void> */
final readonly class LearningPathReportingRecalculateProcessor implements ProcessorInterface
{
    use LearningPathStateHelperTrait;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private RequestStack $requestStack,
        private Security $security,
        private CsrfTokenManagerInterface $csrfTokenManager,
        private LearningPathReportingProvider $reportingProvider,
        private LoggerInterface $logger,
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
    ): void {
        if (!$data instanceof LearningPathReportingRecalculateInput) {
            throw new BadRequestHttpException('Invalid learning path recalculation payload.');
        }

        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            throw new BadRequestHttpException('Request is missing.');
        }

        $this->assertLearningPathTeacher($this->security);
        $course = $this->getContextCourse($this->entityManager, $request);
        $session = $this->getContextSession($this->entityManager, $request, $course);
        $group = $this->getContextGroup($this->entityManager, $request, $course);
        $lp = $this->getLearningPath($uriVariables);
        $this->getEditableResourceLink($lp, $course, $session, $group, $this->security);
        $this->validateActionToken($this->csrfTokenManager, $data->csrfToken);

        if ($data->userId <= 0) {
            throw new BadRequestHttpException('User ID not provided.');
        }

        $allowedUsers = $this->reportingProvider->getUsersForContext($lp, $course, $session, $request);
        if (!isset($allowedUsers[$data->userId])) {
            throw new AccessDeniedHttpException('The selected learner is outside the current learning path report.');
        }

        $quizItems = $this->entityManager->getRepository(CLpItem::class)->findBy([
            'lp' => $lp,
            'itemType' => 'quiz',
        ]);

        $quizItemsById = [];
        foreach ($quizItems as $quizItem) {
            if (!$quizItem instanceof CLpItem || null === $quizItem->getIid()) {
                continue;
            }

            $quizId = (int) $quizItem->getPath();
            if ($quizId > 0) {
                $quizItemsById[(int) $quizItem->getIid()] = $quizId;
            }
        }

        if ([] === $quizItemsById) {
            throw new BadRequestHttpException('No item found.');
        }

        $learner = $allowedUsers[$data->userId]['user'];
        $trackedExercises = $this->entityManager->getRepository(TrackEExercise::class)->findBy([
            'origLpId' => (int) $lp->getIid(),
            'user' => $learner,
            'course' => $course,
            'session' => $session,
        ]);

        $attemptCount = 0;
        $updatedCount = 0;
        $failedCount = 0;

        foreach ($trackedExercises as $trackedExercise) {
            if (!$trackedExercise instanceof TrackEExercise) {
                continue;
            }

            $lpItemId = $trackedExercise->getOrigLpItemId();
            $quizId = $quizItemsById[$lpItemId] ?? 0;
            if ($quizId <= 0) {
                continue;
            }

            ++$attemptCount;

            try {
                $updatedExercise = ExerciseLib::recalculateResult(
                    $trackedExercise->getExeId(),
                    $data->userId,
                    $quizId,
                    (int) $course->getId(),
                );
            } catch (Throwable $exception) {
                ++$failedCount;
                $this->logger->error('Learning path exercise recalculation failed.', [
                    'exception' => $exception,
                    'learningPathId' => (int) $lp->getIid(),
                    'learningPathItemId' => $lpItemId,
                    'exerciseAttemptId' => $trackedExercise->getExeId(),
                    'userId' => $data->userId,
                ]);

                continue;
            }

            if ($updatedExercise instanceof TrackEExercise) {
                ++$updatedCount;

                continue;
            }

            ++$failedCount;
            $this->logger->warning('Learning path exercise recalculation returned no result.', [
                'learningPathId' => (int) $lp->getIid(),
                'learningPathItemId' => $lpItemId,
                'exerciseAttemptId' => $trackedExercise->getExeId(),
                'userId' => $data->userId,
            ]);
        }

        if (0 === $attemptCount) {
            throw new BadRequestHttpException('No test attempt found.');
        }

        if (0 === $updatedCount) {
            throw new BadRequestHttpException('Error recalculating results.');
        }

        if ($failedCount > 0) {
            $this->logger->warning('Learning path results were only partially recalculated.', [
                'learningPathId' => (int) $lp->getIid(),
                'userId' => $data->userId,
                'attemptCount' => $attemptCount,
                'updatedCount' => $updatedCount,
                'failedCount' => $failedCount,
            ]);
        }
    }

    /** @param array<string, mixed> $uriVariables */
    private function getLearningPath(array $uriVariables): CLp
    {
        $lpId = (int) ($uriVariables['lpId'] ?? 0);
        if ($lpId <= 0) {
            throw new BadRequestHttpException('Invalid learning path id.');
        }

        $lp = $this->entityManager->getRepository(CLp::class)->find($lpId);
        if (!$lp instanceof CLp) {
            throw new NotFoundHttpException('Learning path not found.');
        }

        return $lp;
    }
}
