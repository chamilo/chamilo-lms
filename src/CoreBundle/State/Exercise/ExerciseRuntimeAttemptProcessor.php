<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Exercise;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\Exercise\ExerciseRuntimeAttempt;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\TrackEAttempt;
use Chamilo\CoreBundle\Entity\TrackEExercise;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Entity\CQuizQuestion;
use Chamilo\CourseBundle\Entity\CQuizRelQuestion;
use Chamilo\CourseBundle\Repository\CQuizRepository;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Starts or resumes a Vue runtime attempt without submitting answers yet.
 *
 * The goal of this batch is to create the same legacy-compatible attempt shell
 * used by exercise_submit.php: one incomplete row in track_e_exercises with
 * stable question order stored in data_tracking. Scoring and answer persistence
 * remain for the next processors.
 *
 * @implements ProcessorInterface<ExerciseRuntimeAttempt, ExerciseRuntimeAttempt>
 */
final readonly class ExerciseRuntimeAttemptProcessor implements ProcessorInterface
{
    private const VISIBILITY_PUBLISHED = 2;
    private const STATUS_INCOMPLETE = 'incomplete';
    private const QUESTION_SELECTION_RANDOM = 2;
    private const PAGE_BREAK = 31;
    private const MEDIA_QUESTION = 15;

    public function __construct(
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager,
        private CQuizRepository $quizRepository,
        private Security $security,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ExerciseRuntimeAttempt
    {
        if (!$data instanceof ExerciseRuntimeAttempt) {
            throw new BadRequestHttpException('Invalid exercise runtime attempt payload.');
        }

        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        $course = $this->getCourse($request);
        $session = $this->getSession($request);
        $canManage = $this->canManageExercises();

        if (!$canManage && !$this->canViewExercises()) {
            throw new AccessDeniedHttpException('You are not allowed to start this exercise.');
        }

        $exerciseId = isset($uriVariables['exerciseId']) ? (int) $uriVariables['exerciseId'] : (int) ($data->exerciseId ?? 0);
        if (0 >= $exerciseId) {
            throw new BadRequestHttpException('A valid exercise id is required.');
        }

        $quiz = $this->getExerciseFromCurrentContext($exerciseId, $course, $session, $canManage);

        if ($canManage) {
            return $this->createTeacherPreviewResponse($quiz, $course, $session, $request);
        }

        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedHttpException('A valid authenticated user is required.');
        }

        if ($this->requiresLegacyQuestionSelection($quiz)) {
            return $this->createLegacyRequiredResponse(
                $quiz,
                $course,
                $session,
                $request,
                'This exercise uses category-based question selection. Continue in legacy exercise until the Vue selector reaches parity.'
            );
        }

        $incompleteAttempt = $this->findIncompleteAttempt($quiz, $course, $session, $user, $request);
        if ($incompleteAttempt instanceof TrackEExercise) {
            return $this->normalizeAttemptResponse($quiz, $course, $session, $request, $incompleteAttempt, 'Attempt resumed');
        }

        if (!$this->canCreateNewAttempt($quiz, $course, $session, $user, $request)) {
            return $this->createLegacyRequiredResponse(
                $quiz,
                $course,
                $session,
                $request,
                'The maximum number of attempts has been reached.'
            );
        }

        $questionIds = $this->buildQuestionList($quiz);
        $expiredAt = $this->buildExpiredAt($quiz);
        $attempt = (new TrackEExercise())
            ->setSession($session)
            ->setCourse($course)
            ->setMaxScore($this->getTotalWeight($questionIds))
            ->setDataTracking(implode(',', $questionIds))
            ->setUser($user)
            ->setUserIp((string) ($request->getClientIp() ?? ''))
            ->setOrigLpId($request->query->getInt('learnpath_id'))
            ->setOrigLpItemId($request->query->getInt('learnpath_item_id'))
            ->setOrigLpItemViewId($request->query->getInt('learnpath_item_view_id'))
            ->setExpiredTimeControl($expiredAt)
            ->setQuiz($quiz)
        ;

        $this->entityManager->persist($attempt);
        $this->entityManager->flush();

        return $this->normalizeAttemptResponse($quiz, $course, $session, $request, $attempt, 'Attempt started');
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

    private function canViewExercises(): bool
    {
        return $this->security->isGranted('ROLE_CURRENT_COURSE_STUDENT')
            || $this->security->isGranted('ROLE_CURRENT_COURSE_SESSION_STUDENT')
            || $this->canManageExercises();
    }

    private function canManageExercises(): bool
    {
        return $this->security->isGranted('ROLE_CURRENT_COURSE_TEACHER')
            || $this->security->isGranted('ROLE_CURRENT_COURSE_SESSION_TEACHER');
    }

    private function getExerciseFromCurrentContext(int $exerciseId, Course $course, ?Session $session, bool $canManage): CQuiz
    {
        $quiz = $this->quizRepository->find($exerciseId);
        if (!$quiz instanceof CQuiz) {
            throw new NotFoundHttpException('The requested exercise was not found.');
        }

        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('quiz.iid')
            ->addSelect('links.visibility AS linkVisibility')
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
                ->andWhere('IDENTITY(links.session) = :sessionId')
                ->setParameter('sessionId', (int) $session->getId(), Types::INTEGER)
            ;
        } else {
            $queryBuilder->andWhere('links.session IS NULL');
        }

        $row = $queryBuilder->getQuery()->getOneOrNullResult();
        if (null === $row) {
            throw new AccessDeniedHttpException('The requested exercise does not belong to the current course context.');
        }

        if (!$canManage) {
            $visibility = \is_array($row) ? (int) ($row['linkVisibility'] ?? 0) : 0;
            $now = new DateTimeImmutable();

            if (self::VISIBILITY_PUBLISHED !== $visibility) {
                throw new AccessDeniedHttpException('The requested exercise is not visible.');
            }

            if (null !== $quiz->getStartTime() && $quiz->getStartTime() > $now) {
                throw new AccessDeniedHttpException('The requested exercise is not available yet.');
            }

            if (null !== $quiz->getEndTime() && $quiz->getEndTime() < $now) {
                throw new AccessDeniedHttpException('The requested exercise is closed.');
            }
        }

        return $quiz;
    }

    private function requiresLegacyQuestionSelection(CQuiz $quiz): bool
    {
        return 0 < (int) $quiz->getRandomByCategory()
            || (int) ($quiz->getQuestionSelectionType() ?? 0) > self::QUESTION_SELECTION_RANDOM;
    }

    private function findIncompleteAttempt(CQuiz $quiz, Course $course, ?Session $session, User $user, Request $request): ?TrackEExercise
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('attempt')
            ->from(TrackEExercise::class, 'attempt')
            ->andWhere('IDENTITY(attempt.quiz) = :exerciseId')
            ->andWhere('IDENTITY(attempt.course) = :courseId')
            ->andWhere('IDENTITY(attempt.user) = :userId')
            ->andWhere('attempt.status = :status')
            ->andWhere('attempt.origLpId = :lpId')
            ->andWhere('attempt.origLpItemId = :lpItemId')
            ->andWhere('attempt.origLpItemViewId = :lpItemViewId')
            ->setParameter('exerciseId', (int) $quiz->getIid(), Types::INTEGER)
            ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
            ->setParameter('userId', (int) $user->getId(), Types::INTEGER)
            ->setParameter('status', self::STATUS_INCOMPLETE)
            ->setParameter('lpId', $request->query->getInt('learnpath_id'), Types::INTEGER)
            ->setParameter('lpItemId', $request->query->getInt('learnpath_item_id'), Types::INTEGER)
            ->setParameter('lpItemViewId', $request->query->getInt('learnpath_item_view_id'), Types::INTEGER)
            ->orderBy('attempt.exeId', 'DESC')
            ->setMaxResults(1)
        ;

        if (null !== $session) {
            $queryBuilder
                ->andWhere('IDENTITY(attempt.session) = :sessionId')
                ->setParameter('sessionId', (int) $session->getId(), Types::INTEGER)
            ;
        } else {
            $queryBuilder->andWhere('attempt.session IS NULL');
        }

        $attempt = $queryBuilder->getQuery()->getOneOrNullResult();

        return $attempt instanceof TrackEExercise ? $attempt : null;
    }

    private function canCreateNewAttempt(CQuiz $quiz, Course $course, ?Session $session, User $user, Request $request): bool
    {
        $maxAttempt = (int) $quiz->getMaxAttempt();
        if (0 >= $maxAttempt) {
            return true;
        }

        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('COUNT(attempt.exeId)')
            ->from(TrackEExercise::class, 'attempt')
            ->andWhere('IDENTITY(attempt.quiz) = :exerciseId')
            ->andWhere('IDENTITY(attempt.course) = :courseId')
            ->andWhere('IDENTITY(attempt.user) = :userId')
            ->andWhere('attempt.origLpId = :lpId')
            ->andWhere('attempt.origLpItemId = :lpItemId')
            ->andWhere('attempt.origLpItemViewId = :lpItemViewId')
            ->setParameter('exerciseId', (int) $quiz->getIid(), Types::INTEGER)
            ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
            ->setParameter('userId', (int) $user->getId(), Types::INTEGER)
            ->setParameter('lpId', $request->query->getInt('learnpath_id'), Types::INTEGER)
            ->setParameter('lpItemId', $request->query->getInt('learnpath_item_id'), Types::INTEGER)
            ->setParameter('lpItemViewId', $request->query->getInt('learnpath_item_view_id'), Types::INTEGER)
        ;

        if (null !== $session) {
            $queryBuilder
                ->andWhere('IDENTITY(attempt.session) = :sessionId')
                ->setParameter('sessionId', (int) $session->getId(), Types::INTEGER)
            ;
        } else {
            $queryBuilder->andWhere('attempt.session IS NULL');
        }

        return (int) $queryBuilder->getQuery()->getSingleScalarResult() < $maxAttempt;
    }

    /**
     * @return array<int, int>
     */
    private function buildQuestionList(CQuiz $quiz): array
    {
        $relations = $this->entityManager->createQueryBuilder()
            ->select('relQuestion', 'question')
            ->from(CQuizRelQuestion::class, 'relQuestion')
            ->innerJoin('relQuestion.question', 'question')
            ->andWhere('IDENTITY(relQuestion.quiz) = :exerciseId')
            ->setParameter('exerciseId', (int) $quiz->getIid(), Types::INTEGER)
            ->orderBy('relQuestion.questionOrder', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        $mustShuffle = self::QUESTION_SELECTION_RANDOM === (int) ($quiz->getQuestionSelectionType() ?? 0);
        $randomCount = (int) $quiz->getRandom();
        $questionIds = [];
        foreach ($relations as $relation) {
            if (!$relation instanceof CQuizRelQuestion) {
                continue;
            }

            $question = $relation->getQuestion();
            if (!$question instanceof CQuizQuestion || null === $question->getIid()) {
                continue;
            }

            $type = (int) $question->getType();
            if (self::MEDIA_QUESTION === $type) {
                continue;
            }

            if (self::PAGE_BREAK === $type && ($mustShuffle || 0 < $randomCount)) {
                continue;
            }

            $questionIds[] = (int) $question->getIid();
        }

        if ($mustShuffle || 0 < $randomCount) {
            shuffle($questionIds);
        }

        if (0 < $randomCount && $randomCount < \count($questionIds)) {
            $questionIds = \array_slice($questionIds, 0, $randomCount);
        }

        return $questionIds;
    }

    /**
     * @param array<int, int> $questionIds
     */
    private function getTotalWeight(array $questionIds): float
    {
        if ([] === $questionIds) {
            return 0.0;
        }

        $rows = $this->entityManager->createQueryBuilder()
            ->select('SUM(question.ponderation) AS totalScore')
            ->from(CQuizQuestion::class, 'question')
            ->andWhere('question.iid IN (:questionIds)')
            ->setParameter('questionIds', $questionIds, ArrayParameterType::INTEGER)
            ->getQuery()
            ->getArrayResult()
        ;

        return (float) ($rows[0]['totalScore'] ?? 0.0);
    }

    private function buildExpiredAt(CQuiz $quiz): ?DateTimeImmutable
    {
        $expiredMinutes = (int) $quiz->getExpiredTime();
        if (0 >= $expiredMinutes) {
            return null;
        }

        return (new DateTimeImmutable())->modify(sprintf('+%d minutes', $expiredMinutes));
    }

    private function createTeacherPreviewResponse(CQuiz $quiz, Course $course, ?Session $session, Request $request): ExerciseRuntimeAttempt
    {
        $questionIds = $this->buildQuestionList($quiz);
        $response = $this->createBaseResponse($quiz, $course, $session, $request, $questionIds);
        $response->success = true;
        $response->preview = true;
        $response->message = 'Teacher preview does not create a tracked attempt.';
        $response->status = 'preview';

        return $response;
    }

    private function createLegacyRequiredResponse(CQuiz $quiz, Course $course, ?Session $session, Request $request, string $message): ExerciseRuntimeAttempt
    {
        $response = $this->createBaseResponse($quiz, $course, $session, $request, $this->buildQuestionList($quiz));
        $response->success = false;
        $response->usesLegacyRuntime = true;
        $response->message = $message;
        $response->status = 'legacy_required';

        return $response;
    }

    private function normalizeAttemptResponse(CQuiz $quiz, Course $course, ?Session $session, Request $request, TrackEExercise $attempt, string $message): ExerciseRuntimeAttempt
    {
        $questionIds = $this->parseQuestionIds((string) $attempt->getDataTracking());
        if ([] === $questionIds) {
            $questionIds = $this->buildQuestionList($quiz);
        }

        $response = $this->createBaseResponse($quiz, $course, $session, $request, $questionIds);
        $response->success = true;
        $response->attemptId = (int) $attempt->getExeId();
        $response->status = method_exists($attempt, 'getStatus') ? (string) $attempt->getStatus() : self::STATUS_INCOMPLETE;
        $response->message = $message;
        $response->savedAnswers = $this->getSavedAnswers((int) $attempt->getExeId());

        if (method_exists($attempt, 'getStartDate')) {
            $startDate = $attempt->getStartDate();
            $response->startedAt = $startDate instanceof DateTimeInterface ? $startDate->format(DateTimeInterface::ATOM) : null;
        }

        if (method_exists($attempt, 'getExpiredTimeControl')) {
            $expiredAt = $attempt->getExpiredTimeControl();
            if ($expiredAt instanceof DateTimeInterface) {
                $response->expiredAt = $expiredAt->format(DateTimeInterface::ATOM);
                $response->remainingSeconds = max(0, $expiredAt->getTimestamp() - time());
            }
        }

        return $response;
    }

    /**
     * @param array<int, int> $questionIds
     */
    private function createBaseResponse(CQuiz $quiz, Course $course, ?Session $session, Request $request, array $questionIds): ExerciseRuntimeAttempt
    {
        $response = new ExerciseRuntimeAttempt();
        $response->exerciseId = (int) $quiz->getIid();
        $response->questionIds = array_values($questionIds);
        $response->totalQuestions = \count($questionIds);
        $response->currentQuestionIndex = 0;
        $response->currentQuestionId = $questionIds[0] ?? null;
        $response->canNavigatePrevious = false;
        $response->canNavigateNext = \count($questionIds) > 1;
        $response->canFinish = \count($questionIds) > 0;
        $response->legacyUrls = $this->getLegacyUrls($quiz, $course, $session, $request);

        return $response;
    }

    /**
     * @return array<int, int>
     */
    private function parseQuestionIds(string $value): array
    {
        if ('' === trim($value)) {
            return [];
        }

        return array_values(array_filter(array_map(static fn (string $id): int => (int) trim($id), explode(',', $value))));
    }

    /**
     * @return array<int|string, array<int, array{answer: string, position: int|null}>>
     */
    private function getSavedAnswers(int $attemptId): array
    {
        $rows = $this->entityManager->createQueryBuilder()
            ->select('saved.questionId AS questionId')
            ->addSelect('saved.answer AS answer')
            ->addSelect('saved.position AS position')
            ->from(TrackEAttempt::class, 'saved')
            ->andWhere('IDENTITY(saved.trackExercise) = :attemptId')
            ->setParameter('attemptId', $attemptId, Types::INTEGER)
            ->orderBy('saved.questionId', 'ASC')
            ->addOrderBy('saved.position', 'ASC')
            ->getQuery()
            ->getArrayResult()
        ;

        $savedAnswers = [];
        foreach ($rows as $row) {
            if (!\is_array($row)) {
                continue;
            }

            $questionId = (int) ($row['questionId'] ?? 0);
            if (0 >= $questionId) {
                continue;
            }

            $savedAnswers[$questionId][] = [
                'answer' => (string) ($row['answer'] ?? ''),
                'position' => null !== ($row['position'] ?? null) ? (int) $row['position'] : null,
            ];
        }

        return $savedAnswers;
    }

    /**
     * @return array<string, string>
     */
    private function getLegacyUrls(CQuiz $quiz, Course $course, ?Session $session, Request $request): array
    {
        $baseParams = [
            'exerciseId' => (int) $quiz->getIid(),
            'cid' => (int) $course->getId(),
            'sid' => (int) ($session?->getId() ?? 0),
            'gid' => $request->query->getInt('gid'),
        ];

        foreach (['origin', 'learnpath_id', 'learnpath_item_id', 'learnpath_item_view_id'] as $key) {
            $value = $request->query->get($key);
            if (null !== $value && '' !== (string) $value) {
                $baseParams[$key] = (string) $value;
            }
        }

        return [
            'overview' => '/main/exercise/overview.php?'.http_build_query($baseParams),
            'show' => '/main/exercise/exercise_show.php?'.http_build_query($baseParams),
            'submit' => '/main/exercise/exercise_submit.php?'.http_build_query($baseParams),
        ];
    }
}
