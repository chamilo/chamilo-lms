<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Exercise;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\Exercise\ExerciseOverview;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\TrackEAttemptQualify;
use Chamilo\CoreBundle\Entity\TrackEExercise;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Entity\CQuizRelQuestion;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Legacy-compatible start page data for one exercise.
 *
 * @implements ProviderInterface<ExerciseOverview>
 */
final readonly class ExerciseOverviewProvider implements ProviderInterface
{
    private const VISIBILITY_PUBLISHED = 2;
    private const RESULT_DISABLE_SHOW_SCORE_AND_EXPECTED_ANSWERS = 0;
    private const RESULT_DISABLE_NO_SCORE_AND_EXPECTED_ANSWERS = 1;
    private const RESULT_DISABLE_SHOW_SCORE_ONLY = 2;
    private const RESULT_DISABLE_SHOW_FINAL_SCORE_ONLY_WITH_CATEGORIES = 3;
    private const RESULT_DISABLE_SHOW_SCORE_ATTEMPT_SHOW_ANSWERS_LAST_ATTEMPT = 4;
    private const RESULT_DISABLE_DONT_SHOW_SCORE_ONLY_IF_USER_FINISHES_ATTEMPTS_SHOW_ALWAYS_FEEDBACK = 5;
    private const RESULT_DISABLE_RANKING = 6;
    private const RESULT_DISABLE_SHOW_ONLY_IN_CORRECT_ANSWER = 7;
    private const RESULT_DISABLE_SHOW_SCORE_AND_EXPECTED_ANSWERS_AND_RANKING = 8;
    private const RESULT_DISABLE_SHOW_SCORE_ATTEMPT_SHOW_ANSWERS_LAST_ATTEMPT_NO_FEEDBACK = 10;
    private const EXERCISE_FEEDBACK_TYPE_END = 0;

    public function __construct(
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager,
        private Security $security,
        private SettingsManager $settingsManager,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ExerciseOverview
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        $course = $this->getCourse($request);
        $session = $this->getSession($request);
        $user = $this->getCurrentUser();
        $canManage = $this->canManageExercises();

        if (!$canManage && !$this->canViewExercises()) {
            throw new AccessDeniedHttpException('You are not allowed to view exercises in this context.');
        }

        $exerciseId = isset($uriVariables['exerciseId']) ? (int) $uriVariables['exerciseId'] : 0;
        if (0 >= $exerciseId) {
            throw new BadRequestHttpException('A valid exercise id is required.');
        }

        [$quiz, $linkVisibility] = $this->getExerciseFromCurrentContext($exerciseId, $course, $session);
        $visible = self::VISIBILITY_PUBLISHED === $linkVisibility;
        if (!$canManage && !$visible) {
            throw new AccessDeniedHttpException('The requested exercise is not visible.');
        }

        $learnpathId = max(0, $request->query->getInt('learnpath_id'));
        $learnpathItemId = max(0, $request->query->getInt('learnpath_item_id'));
        $currentUserAttempts = $this->getCurrentUserAttempts($quiz, $course, $session, $user, $learnpathId, $learnpathItemId);
        $currentUserAttemptCount = \count($currentUserAttempts);
        $maxAttempt = (int) $quiz->getMaxAttempt();
        $attemptLimitReached = 0 < $maxAttempt && $currentUserAttemptCount >= $maxAttempt;
        $hideAttemptsTable = $this->isSettingEnabled('exercise.quiz_hide_attempts_table_on_start_page');

        $availabilityStatus = $this->getAvailabilityStatus($quiz->getStartTime(), $quiz->getEndTime());
        $canOpen = $canManage || ($visible && 'open' === $availabilityStatus);

        $overview = new ExerciseOverview();
        $overview->exerciseId = (int) $quiz->getIid();
        $overview->title = $quiz->getTitle();
        $overview->description = (string) $quiz->getDescription();
        $overview->visible = $visible;
        $overview->categoryTitle = null !== $quiz->getQuizCategory() ? $quiz->getQuizCategory()->getTitle() : '';
        $overview->questionCount = $this->getQuestionCount($quiz);
        $overview->attemptCount = $currentUserAttemptCount;
        $overview->currentUserAttemptCount = $currentUserAttemptCount;
        $overview->averageScore = $this->getAverageScore($quiz, $course, $session);
        $overview->maxScore = $this->getExerciseMaxScore($quiz);
        $overview->passPercentage = (int) ($quiz->getPassPercentage() ?? 0);
        $overview->startTime = $this->formatDate($quiz->getStartTime());
        $overview->endTime = $this->formatDate($quiz->getEndTime());
        $overview->duration = $quiz->getDuration();
        $overview->maxAttempt = $maxAttempt;
        $overview->feedbackType = $quiz->getFeedbackType();
        $overview->resultsDisabled = $quiz->getResultsDisabled();
        $overview->oneQuestionPerPage = CQuiz::ONE_PER_PAGE === (int) $quiz->getType();
        $overview->randomAnswers = $quiz->getRandomAnswers();
        $overview->random = (int) $quiz->getRandom();
        $overview->randomByCategory = (int) $quiz->getRandomByCategory();
        $overview->canManage = $canManage;
        $overview->canOpen = $canOpen;
        $overview->canStart = $canOpen && !$attemptLimitReached;
        $overview->canReport = $canManage;
        $overview->availabilityStatus = $availabilityStatus;
        $overview->currentUserAttempts = $currentUserAttempts;
        $overview->showAttemptsTable = !$hideAttemptsTable && [] !== $currentUserAttempts;
        $overview->showScoreColumn = $this->shouldShowScoreColumn($quiz, $currentUserAttemptCount);
        $overview->showDetailsColumn = $this->shouldShowDetailsColumn($quiz, $currentUserAttemptCount);
        $overview->attemptLimitReached = $attemptLimitReached;
        $overview->startButtonLabel = 0 < $currentUserAttemptCount ? 'Proceed with the test' : 'Start test';
        $overview->notice = 0 < $currentUserAttemptCount ? 'You have tried to resolve this exercise earlier' : '';

        return $overview;
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

    private function getCurrentUser(): User
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedHttpException('A valid user is required.');
        }

        return $user;
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

    /**
     * @return array{0: CQuiz, 1: int}
     */
    private function getExerciseFromCurrentContext(int $exerciseId, Course $course, ?Session $session): array
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('quiz')
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
                ->andWhere('(IDENTITY(links.session) = :sessionId OR links.session IS NULL)')
                ->setParameter('sessionId', (int) $session->getId(), Types::INTEGER)
            ;
        } else {
            $queryBuilder->andWhere('links.session IS NULL');
        }

        $row = $queryBuilder->getQuery()->getOneOrNullResult();
        if (!\is_array($row)) {
            throw new NotFoundHttpException('The requested exercise was not found.');
        }

        $quiz = $row[0] ?? $row['quiz'] ?? null;
        if (!$quiz instanceof CQuiz) {
            throw new NotFoundHttpException('The requested exercise was not found.');
        }

        return [$quiz, (int) ($row['linkVisibility'] ?? self::VISIBILITY_PUBLISHED)];
    }

    private function getQuestionCount(CQuiz $quiz): int
    {
        return (int) $this->entityManager->createQueryBuilder()
            ->select('COUNT(relQuestion.iid)')
            ->from(CQuizRelQuestion::class, 'relQuestion')
            ->andWhere('IDENTITY(relQuestion.quiz) = :exerciseId')
            ->setParameter('exerciseId', (int) $quiz->getIid(), Types::INTEGER)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    private function getExerciseMaxScore(CQuiz $quiz): float
    {
        $score = $this->entityManager->createQueryBuilder()
            ->select('COALESCE(SUM(question.ponderation), 0)')
            ->from(CQuizRelQuestion::class, 'relQuestion')
            ->innerJoin('relQuestion.question', 'question')
            ->andWhere('IDENTITY(relQuestion.quiz) = :exerciseId')
            ->setParameter('exerciseId', (int) $quiz->getIid(), Types::INTEGER)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return round((float) $score, 2);
    }

    private function getAverageScore(CQuiz $quiz, Course $course, ?Session $session): float
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('AVG(attempt.score)')
            ->from(TrackEExercise::class, 'attempt')
            ->andWhere('IDENTITY(attempt.quiz) = :exerciseId')
            ->andWhere('IDENTITY(attempt.course) = :courseId')
            ->andWhere("(attempt.status = '' OR attempt.status = 'completed')")
            ->setParameter('exerciseId', (int) $quiz->getIid(), Types::INTEGER)
            ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
        ;

        if (null !== $session) {
            $queryBuilder
                ->andWhere('IDENTITY(attempt.session) = :sessionId')
                ->setParameter('sessionId', (int) $session->getId(), Types::INTEGER)
            ;
        } else {
            $queryBuilder->andWhere('attempt.session IS NULL');
        }

        return round((float) ($queryBuilder->getQuery()->getSingleScalarResult() ?? 0), 2);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getCurrentUserAttempts(
        CQuiz $quiz,
        Course $course,
        ?Session $session,
        User $user,
        int $learnpathId,
        int $learnpathItemId,
    ): array {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('attempt')
            ->from(TrackEExercise::class, 'attempt')
            ->andWhere("(attempt.status = '' OR attempt.status = 'completed')")
            ->andWhere('IDENTITY(attempt.user) = :userId')
            ->andWhere('IDENTITY(attempt.quiz) = :exerciseId')
            ->andWhere('IDENTITY(attempt.course) = :courseId')
            ->andWhere('attempt.origLpId = :learnpathId')
            ->andWhere('attempt.origLpItemId = :learnpathItemId')
            ->setParameter('userId', (int) $user->getId(), Types::INTEGER)
            ->setParameter('exerciseId', (int) $quiz->getIid(), Types::INTEGER)
            ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
            ->setParameter('learnpathId', $learnpathId, Types::INTEGER)
            ->setParameter('learnpathItemId', $learnpathItemId, Types::INTEGER)
            ->orderBy('attempt.exeId', 'DESC')
        ;

        if (null !== $session) {
            $queryBuilder
                ->andWhere('IDENTITY(attempt.session) = :sessionId')
                ->setParameter('sessionId', (int) $session->getId(), Types::INTEGER)
            ;
        } else {
            $queryBuilder->andWhere('attempt.session IS NULL');
        }

        $attempts = $queryBuilder->getQuery()->getResult();
        $total = \count($attempts);
        $items = [];

        foreach ($attempts as $index => $attempt) {
            if (!$attempt instanceof TrackEExercise) {
                continue;
            }

            $maxScore = $attempt->getMaxScore();
            $revised = $this->isAttemptRevised((int) $attempt->getExeId());
            $items[] = [
                'attemptId' => (int) $attempt->getExeId(),
                'number' => $total - $index,
                'startDate' => $this->formatDate($attempt->getStartDate()),
                'userIp' => $attempt->getUserIp(),
                'score' => round($attempt->getScore(), 2),
                'maxScore' => round($maxScore, 2),
                'percentage' => 0 < $maxScore ? round(($attempt->getScore() / $maxScore) * 100, 2) : 0.0,
                'revised' => $revised,
                'showValidationStatus' => $revised || $this->hasPendingManualCorrection($attempt),
            ];
        }

        return $items;
    }

    private function isAttemptRevised(int $attemptId): bool
    {
        $total = $this->entityManager->createQueryBuilder()
            ->select('COUNT(qualify.id)')
            ->from(TrackEAttemptQualify::class, 'qualify')
            ->andWhere('IDENTITY(qualify.trackExercise) = :attemptId')
            ->andWhere('qualify.author > 0')
            ->setParameter('attemptId', $attemptId, Types::INTEGER)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return 0 < (int) $total;
    }


    private function hasPendingManualCorrection(TrackEExercise $attempt): bool
    {
        return '' !== trim($attempt->getQuestionsToCheck(), " \t\n\r\0\x0B,");
    }

    private function shouldShowScoreColumn(CQuiz $quiz, int $attemptCount): bool
    {
        $resultMode = (int) $quiz->getResultsDisabled();
        if (self::RESULT_DISABLE_NO_SCORE_AND_EXPECTED_ANSWERS === $resultMode) {
            return false;
        }

        if (
            self::RESULT_DISABLE_DONT_SHOW_SCORE_ONLY_IF_USER_FINISHES_ATTEMPTS_SHOW_ALWAYS_FEEDBACK === $resultMode
            && !$this->canShowLastAttemptAnswers($quiz, $attemptCount)
        ) {
            return false;
        }

        return \in_array(
            $resultMode,
            [
                self::RESULT_DISABLE_SHOW_SCORE_AND_EXPECTED_ANSWERS,
                self::RESULT_DISABLE_SHOW_SCORE_AND_EXPECTED_ANSWERS_AND_RANKING,
                self::RESULT_DISABLE_SHOW_SCORE_ONLY,
                self::RESULT_DISABLE_SHOW_FINAL_SCORE_ONLY_WITH_CATEGORIES,
                self::RESULT_DISABLE_SHOW_SCORE_ATTEMPT_SHOW_ANSWERS_LAST_ATTEMPT,
                self::RESULT_DISABLE_SHOW_SCORE_ATTEMPT_SHOW_ANSWERS_LAST_ATTEMPT_NO_FEEDBACK,
                self::RESULT_DISABLE_DONT_SHOW_SCORE_ONLY_IF_USER_FINISHES_ATTEMPTS_SHOW_ALWAYS_FEEDBACK,
                self::RESULT_DISABLE_RANKING,
                self::RESULT_DISABLE_SHOW_ONLY_IN_CORRECT_ANSWER,
            ],
            true
        );
    }

    private function shouldShowDetailsColumn(CQuiz $quiz, int $attemptCount): bool
    {
        $resultMode = (int) $quiz->getResultsDisabled();
        if (self::RESULT_DISABLE_NO_SCORE_AND_EXPECTED_ANSWERS === $resultMode) {
            return false;
        }

        if (self::RESULT_DISABLE_SHOW_SCORE_ONLY === $resultMode) {
            return self::EXERCISE_FEEDBACK_TYPE_END === (int) $quiz->getFeedbackType();
        }

        if (\in_array($resultMode, [self::RESULT_DISABLE_SHOW_SCORE_ATTEMPT_SHOW_ANSWERS_LAST_ATTEMPT, self::RESULT_DISABLE_SHOW_SCORE_ATTEMPT_SHOW_ANSWERS_LAST_ATTEMPT_NO_FEEDBACK], true)) {
            return true;
        }

        if (self::RESULT_DISABLE_DONT_SHOW_SCORE_ONLY_IF_USER_FINISHES_ATTEMPTS_SHOW_ALWAYS_FEEDBACK === $resultMode) {
            return $this->canShowLastAttemptAnswers($quiz, $attemptCount);
        }

        return \in_array(
            $resultMode,
            [
                self::RESULT_DISABLE_SHOW_SCORE_AND_EXPECTED_ANSWERS,
                self::RESULT_DISABLE_SHOW_SCORE_AND_EXPECTED_ANSWERS_AND_RANKING,
                self::RESULT_DISABLE_SHOW_FINAL_SCORE_ONLY_WITH_CATEGORIES,
                self::RESULT_DISABLE_RANKING,
                self::RESULT_DISABLE_SHOW_ONLY_IN_CORRECT_ANSWER,
            ],
            true
        );
    }

    private function canShowLastAttemptAnswers(CQuiz $quiz, int $attemptCount): bool
    {
        $maxAttempt = (int) $quiz->getMaxAttempt();

        return 0 >= $maxAttempt || $attemptCount >= $maxAttempt;
    }

    private function getAvailabilityStatus(?DateTimeInterface $startTime, ?DateTimeInterface $endTime): string
    {
        $now = new DateTimeImmutable();

        if (null !== $startTime && $startTime > $now) {
            return 'not_started';
        }

        if (null !== $endTime && $endTime < $now) {
            return 'closed';
        }

        return 'open';
    }

    private function formatDate(?DateTimeInterface $date): ?string
    {
        if (null === $date) {
            return null;
        }

        return $date->format(DateTimeInterface::ATOM);
    }

    private function isSettingEnabled(string $name): bool
    {
        return 'true' === $this->settingsManager->getSetting($name, true);
    }
}
