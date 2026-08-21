<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Exercise;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\Exercise\ExerciseRuntimeReportBulkAction;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\TrackEExercise;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CoreBundle\Helpers\IsAllowedToEditHelper;
use Chamilo\CoreBundle\Service\Exercise\ExerciseAttemptScoringService;
use Chamilo\CoreBundle\Service\Gradebook\GradebookLinkManager;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CoreBundle\State\Gradebook\GradebookLinkResourceResolver;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Repository\CQuizRepository;
use DateTimeImmutable;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Runs non-legacy bulk cleanup actions from the exercise learner attempts report.
 *
 * @implements ProcessorInterface<ExerciseRuntimeReportBulkAction, ExerciseRuntimeReportBulkAction>
 */
final readonly class ExerciseRuntimeReportBulkActionProcessor implements ProcessorInterface
{
    private const ACTION_DELETE_SELECTED = 'delete_selected';
    private const ACTION_CLEAN_BEFORE_DATE = 'clean_before_date';
    private const ACTION_RECALCULATE_ALL = 'recalculate_all';

    public function __construct(
        private CidReqHelper $cidReqHelper,
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager,
        private CQuizRepository $quizRepository,
        private Security $security,
        private GradebookLinkManager $gradebookLinkManager,
        private SettingsManager $settingsManager,
        private ExerciseAttemptScoringService $scoringService,
        private IsAllowedToEditHelper $isAllowedToEditHelper,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ExerciseRuntimeReportBulkAction
    {
        if (!$data instanceof ExerciseRuntimeReportBulkAction) {
            throw new BadRequestHttpException('Invalid report bulk action payload.');
        }

        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        if (!$this->isAllowedToEditHelper->check(coach: true)) {
            throw new AccessDeniedHttpException('You are not allowed to run this exercise report action.');
        }

        $course = $this->cidReqHelper->requireDoctrineCourseEntity();
        $session = $this->cidReqHelper->getDoctrineSessionEntity();
        $exerciseId = isset($uriVariables['exerciseId']) ? (int) $uriVariables['exerciseId'] : (int) ($data->exerciseId ?? 0);
        if ($exerciseId <= 0) {
            throw new BadRequestHttpException('A valid exercise id is required.');
        }

        $data->exerciseId = $exerciseId;
        $quiz = $this->getExerciseFromCurrentContext($exerciseId, $course, $session);
        if ($this->isGradebookLocked((int) $quiz->getIid(), $course, $session)) {
            throw new BadRequestHttpException('This exercise is locked by gradebook.');
        }

        $action = strtolower(trim($data->action));

        return match ($action) {
            self::ACTION_DELETE_SELECTED => $this->deleteSelected($data, $quiz, $course, $session),
            self::ACTION_CLEAN_BEFORE_DATE => $this->cleanBeforeDate($data, $quiz, $course, $session),
            self::ACTION_RECALCULATE_ALL => $this->recalculateAll($data, $quiz, $course, $session),
            default => throw new BadRequestHttpException('Unsupported report bulk action.'),
        };
    }

    private function getExerciseFromCurrentContext(int $exerciseId, Course $course, ?Session $session): CQuiz
    {
        $quiz = $this->quizRepository->find($exerciseId);
        if (!$quiz instanceof CQuiz) {
            throw new NotFoundHttpException('The requested exercise was not found.');
        }

        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('quiz.iid')
            ->from(CQuiz::class, 'quiz')
            ->innerJoin('quiz.resourceNode', 'node')
            ->innerJoin('node.resourceLinks', 'links')
            ->andWhere('quiz.iid = :exerciseId')
            ->andWhere('IDENTITY(links.course) = :courseId')
            ->andWhere('links.deletedAt IS NULL')
            ->andWhere('links.endVisibilityAt IS NULL')
            ->setParameter('exerciseId', $exerciseId, Types::INTEGER)
            ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
            ->setMaxResults(1)
        ;

        if (null !== $session) {
            $queryBuilder
                ->andWhere('(IDENTITY(links.session) = :sessionId OR links.session IS NULL)')
                ->setParameter('sessionId', (int) $session->getId(), Types::INTEGER)
            ;
        } else {
            $queryBuilder->andWhere('links.session IS NULL');
        }

        if (null === $queryBuilder->getQuery()->getOneOrNullResult()) {
            throw new AccessDeniedHttpException('The requested exercise does not belong to the current course context.');
        }

        return $quiz;
    }

    private function deleteSelected(ExerciseRuntimeReportBulkAction $data, CQuiz $quiz, Course $course, ?Session $session): ExerciseRuntimeReportBulkAction
    {
        if (!$this->canDeleteResults()) {
            throw new AccessDeniedHttpException('Deleting exercise results is not allowed.');
        }

        $attemptIds = $this->getSubmittedAttemptIds($data);
        if ([] === $attemptIds) {
            throw new BadRequestHttpException('Select at least one attempt.');
        }

        $attempts = $this->getAttempts($quiz, $course, $session, $attemptIds);
        foreach ($attempts as $attempt) {
            $this->entityManager->remove($attempt);
        }
        $this->entityManager->flush();

        $processedCount = \count($attempts);
        $failedCount = \count($attemptIds) - $processedCount;

        return $this->buildResponse($data, true, 'Attempts deleted', $processedCount, max(0, $failedCount));
    }

    private function cleanBeforeDate(ExerciseRuntimeReportBulkAction $data, CQuiz $quiz, Course $course, ?Session $session): ExerciseRuntimeReportBulkAction
    {
        if (!$this->canCleanResults()) {
            throw new AccessDeniedHttpException('Cleaning exercise results is not allowed.');
        }

        $beforeDate = $this->getValidBeforeDate($data->beforeDate);
        $attempts = $this->getAttemptsBeforeDate($quiz, $course, $session, $beforeDate);
        foreach ($attempts as $attempt) {
            $this->entityManager->remove($attempt);
        }
        $this->entityManager->flush();

        return $this->buildResponse($data, true, 'Results cleaned', \count($attempts), 0);
    }

    private function recalculateAll(ExerciseRuntimeReportBulkAction $data, CQuiz $quiz, Course $course, ?Session $session): ExerciseRuntimeReportBulkAction
    {
        $attempts = $this->getAttempts($quiz, $course, $session);
        $processedCount = 0;
        $failedCount = 0;

        foreach ($attempts as $attempt) {
            try {
                $this->scoringService->recalculateAttempt($attempt, $quiz);
                ++$processedCount;
            } catch (BadRequestHttpException) {
                ++$failedCount;
            }
        }

        $this->entityManager->flush();

        return $this->buildResponse($data, true, 'Results recalculated', $processedCount, $failedCount);
    }

    /**
     * @param array<int, int> $attemptIds
     *
     * @return array<int, TrackEExercise>
     */
    private function getAttempts(CQuiz $quiz, Course $course, ?Session $session, array $attemptIds = []): array
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('attempt')
            ->from(TrackEExercise::class, 'attempt')
            ->andWhere('IDENTITY(attempt.quiz) = :exerciseId')
            ->andWhere('IDENTITY(attempt.course) = :courseId')
            ->setParameter('exerciseId', (int) $quiz->getIid(), Types::INTEGER)
            ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
            ->orderBy('attempt.exeDate', 'DESC')
            ->addOrderBy('attempt.exeId', 'DESC')
        ;

        if ([] !== $attemptIds) {
            $queryBuilder
                ->andWhere('attempt.exeId IN (:attemptIds)')
                ->setParameter('attemptIds', $attemptIds, ArrayParameterType::INTEGER)
            ;
        }

        $this->addSessionCondition($queryBuilder, $session);

        return array_values(array_filter(
            $queryBuilder->getQuery()->getResult(),
            static fn (mixed $attempt): bool => $attempt instanceof TrackEExercise,
        ));
    }

    /**
     * @return array<int, TrackEExercise>
     */
    private function getAttemptsBeforeDate(CQuiz $quiz, Course $course, ?Session $session, DateTimeImmutable $beforeDate): array
    {
        $endOfDay = $beforeDate->setTime(23, 59, 59);
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('attempt')
            ->from(TrackEExercise::class, 'attempt')
            ->andWhere('IDENTITY(attempt.quiz) = :exerciseId')
            ->andWhere('IDENTITY(attempt.course) = :courseId')
            ->andWhere('attempt.exeDate <= :beforeDate')
            ->setParameter('exerciseId', (int) $quiz->getIid(), Types::INTEGER)
            ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
            ->setParameter('beforeDate', $endOfDay, Types::DATETIME_IMMUTABLE)
            ->orderBy('attempt.exeDate', 'ASC')
        ;

        $this->addSessionCondition($queryBuilder, $session);

        return array_values(array_filter(
            $queryBuilder->getQuery()->getResult(),
            static fn (mixed $attempt): bool => $attempt instanceof TrackEExercise,
        ));
    }

    private function addSessionCondition(QueryBuilder $queryBuilder, ?Session $session): void
    {
        if (null !== $session) {
            $queryBuilder
                ->andWhere('IDENTITY(attempt.session) = :sessionId')
                ->setParameter('sessionId', (int) $session->getId(), Types::INTEGER)
            ;

            return;
        }

        $queryBuilder->andWhere('attempt.session IS NULL');
    }

    /**
     * @return array<int, int>
     */
    private function getSubmittedAttemptIds(ExerciseRuntimeReportBulkAction $data): array
    {
        return array_values(array_unique(array_filter(
            array_map(static fn (mixed $attemptId): int => (int) $attemptId, $data->attemptIds),
            static fn (int $attemptId): bool => $attemptId > 0,
        )));
    }

    private function getValidBeforeDate(string $beforeDate): DateTimeImmutable
    {
        $beforeDate = trim($beforeDate);
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $beforeDate);
        $errors = DateTimeImmutable::getLastErrors();
        if (!$date instanceof DateTimeImmutable || (\is_array($errors) && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
            throw new BadRequestHttpException('A valid date is required.');
        }

        return $date;
    }

    private function canDeleteResults(): bool
    {
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        return !$this->isSettingEnabled('exercise.limit_exercise_teacher_access');
    }

    private function canCleanResults(): bool
    {
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        return !$this->isSettingEnabled('exercise.limit_exercise_teacher_access')
            && !$this->isSettingEnabled('exercise.disable_clean_exercise_results_for_teachers');
    }

    private function isGradebookLocked(int $exerciseId, Course $course, ?Session $session): bool
    {
        return $this->gradebookLinkManager->isResourceLocked(
            $course,
            $session,
            GradebookLinkResourceResolver::LINK_EXERCISE,
            $exerciseId,
        );
    }

    private function isSettingEnabled(string $name): bool
    {
        return 'true' === $this->settingsManager->getSetting($name, true);
    }

    private function buildResponse(
        ExerciseRuntimeReportBulkAction $data,
        bool $success,
        string $message,
        int $processedCount,
        int $failedCount,
    ): ExerciseRuntimeReportBulkAction {
        $response = new ExerciseRuntimeReportBulkAction();
        $response->exerciseId = $data->exerciseId;
        $response->action = $data->action;
        $response->attemptIds = $data->attemptIds;
        $response->beforeDate = $data->beforeDate;
        $response->success = $success;
        $response->message = $message;
        $response->processedCount = $processedCount;
        $response->failedCount = $failedCount;

        return $response;
    }
}
