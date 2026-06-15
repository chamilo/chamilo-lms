<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Exercise;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\Exercise\ExerciseRuntimeReportBulkAction;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\GradebookLink;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\TrackEExercise;
use Chamilo\CoreBundle\Service\Exercise\ExerciseAttemptScoringService;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Repository\CQuizRepository;
use DateTimeImmutable;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

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
    private const LINK_TYPE_EXERCISE = 1;

    public function __construct(
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager,
        private CQuizRepository $quizRepository,
        private Security $security,
        private SettingsManager $settingsManager,
        private CsrfTokenManagerInterface $csrfTokenManager,
        private ExerciseAttemptScoringService $scoringService,
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

        if (!$this->canManageExercises()) {
            throw new AccessDeniedHttpException('You are not allowed to run this exercise report action.');
        }

        $this->validateCsrfToken($data->submittedCsrfToken);
        $course = $this->getCourse($request);
        $session = $this->getSession($request);
        $exerciseId = isset($uriVariables['exerciseId']) ? (int) $uriVariables['exerciseId'] : (int) ($data->exerciseId ?? 0);
        if (0 >= $exerciseId) {
            throw new BadRequestHttpException('A valid exercise id is required.');
        }

        $data->exerciseId = $exerciseId;
        $quiz = $this->getExerciseFromCurrentContext($exerciseId, $course, $session);
        if ($this->isGradebookLocked((int) $quiz->getIid(), $course)) {
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

    private function canManageExercises(): bool
    {
        return $this->security->isGranted('ROLE_CURRENT_COURSE_TEACHER')
            || $this->security->isGranted('ROLE_CURRENT_COURSE_SESSION_TEACHER');
    }

    private function validateCsrfToken(string $submittedCsrfToken): void
    {
        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken(ExerciseRuntimeReportProvider::BULK_ACTION_CSRF_TOKEN_ID, $submittedCsrfToken))) {
            throw new AccessDeniedHttpException('Invalid CSRF token.');
        }
    }

    private function getCourse(Request $request): Course
    {
        $courseId = $request->query->getInt('cid');
        if (0 >= $courseId) {
            throw new BadRequestHttpException('A valid course id is required.');
        }

        $course = $this->entityManager->getRepository(Course::class)->find($courseId);
        if (!$course instanceof Course) {
            throw new BadRequestHttpException('The requested course was not found.');
        }

        return $course;
    }

    private function getSession(Request $request): ?Session
    {
        $sessionId = $request->query->getInt('sid');
        if (0 >= $sessionId) {
            return null;
        }

        $session = $this->entityManager->getRepository(Session::class)->find($sessionId);
        if (!$session instanceof Session) {
            throw new BadRequestHttpException('The requested session was not found.');
        }

        return $session;
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

        return $this->buildResponse($data, true, 'Attempts recalculated', $processedCount, $failedCount);
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
            static fn (int $attemptId): bool => 0 < $attemptId,
        )));
    }

    private function getValidBeforeDate(string $beforeDate): DateTimeImmutable
    {
        $beforeDate = trim($beforeDate);
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $beforeDate);
        $errors = DateTimeImmutable::getLastErrors();
        if (!$date instanceof DateTimeImmutable || (\is_array($errors) && (0 < $errors['warning_count'] || 0 < $errors['error_count']))) {
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

    private function isGradebookLocked(int $exerciseId, Course $course): bool
    {
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return false;
        }

        if (!$this->isSettingEnabled('gradebook.gradebook_locking_enabled')) {
            return false;
        }

        $lockedLink = $this->entityManager->createQueryBuilder()
            ->select('link.id')
            ->from(GradebookLink::class, 'link')
            ->andWhere('link.locked = :locked')
            ->andWhere('link.refId = :exerciseId')
            ->andWhere('link.type = :linkType')
            ->andWhere('IDENTITY(link.course) = :courseId')
            ->setParameter('locked', 1, Types::INTEGER)
            ->setParameter('exerciseId', $exerciseId, Types::INTEGER)
            ->setParameter('linkType', self::LINK_TYPE_EXERCISE, Types::INTEGER)
            ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return null !== $lockedLink;
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
