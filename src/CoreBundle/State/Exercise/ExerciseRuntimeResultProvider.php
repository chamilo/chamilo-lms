<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Exercise;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\Exercise\ExerciseRuntimeResult;
use Chamilo\CoreBundle\Entity\AttemptFile;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceFile;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\TrackEAttempt;
use Chamilo\CoreBundle\Entity\TrackEExercise;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Entity\CQuizAnswer;
use Chamilo\CourseBundle\Entity\CQuizQuestion;
use Chamilo\CourseBundle\Entity\CQuizQuestionCategory;
use Chamilo\CourseBundle\Entity\CQuizQuestionOption;
use Chamilo\CourseBundle\Entity\CQuizRelQuestion;
use Chamilo\CourseBundle\Entity\CLpItem;
use Chamilo\CourseBundle\Entity\CLpItemView;
use Chamilo\CoreBundle\Repository\ResourceNodeRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
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
 * Read-only provider for migrated exercise runtime result/review data.
 *
 * @implements ProviderInterface<ExerciseRuntimeResult>
 */
final readonly class ExerciseRuntimeResultProvider implements ProviderInterface
{
    private const STATUS_COMPLETED = 'completed';
    private const ANNOTATION = 20;
    private const ANSWER_IN_OFFICE_DOC = 30;
    private const HOTSPOT_DELINEATION = 8;
    private const DRAGGABLE = 18;
    private const VISIBILITY_PUBLISHED = 2;
    private const LP_ITEM_TYPE_QUIZ = 'quiz';
    private const FEEDBACK_TYPE_DIRECT = 1;
    private const FEEDBACK_TYPE_EXAM = 2;
    private const RESULT_SHOW_SCORE_AND_EXPECTED_ANSWERS = 0;
    private const RESULT_NO_SCORE_AND_EXPECTED_ANSWERS = 1;
    private const RESULT_SHOW_SCORE_ONLY = 2;
    private const RESULT_SHOW_FINAL_SCORE_ONLY_WITH_CATEGORIES = 3;
    private const RESULT_SHOW_SCORE_ATTEMPT_SHOW_ANSWERS_LAST_ATTEMPT = 4;
    private const RESULT_DONT_SHOW_SCORE_ONLY_IF_USER_FINISHES_ATTEMPTS_SHOW_ALWAYS_FEEDBACK = 5;
    private const RESULT_RANKING = 6;
    private const RESULT_SHOW_ONLY_IN_CORRECT_ANSWER = 7;
    private const RESULT_SHOW_SCORE_AND_EXPECTED_ANSWERS_AND_RANKING = 8;
    private const RESULT_RADAR = 9;
    private const RESULT_SHOW_SCORE_ATTEMPT_SHOW_ANSWERS_LAST_ATTEMPT_NO_FEEDBACK = 10;

    public function __construct(
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager,
        private CQuizRepository $quizRepository,
        private ResourceNodeRepository $resourceNodeRepository,
        private Security $security,
        private SettingsManager $settingsManager,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ExerciseRuntimeResult
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        $course = $this->getCourse($request);
        $session = $this->getSession($request);
        $exerciseId = isset($uriVariables['exerciseId']) ? (int) $uriVariables['exerciseId'] : 0;
        $attemptId = isset($uriVariables['attemptId']) ? (int) $uriVariables['attemptId'] : 0;
        if (0 >= $exerciseId || 0 >= $attemptId) {
            throw new BadRequestHttpException('A valid exercise and attempt are required.');
        }

        $canManage = $this->canManageExercises();
        if (!$canManage && !$this->canViewExercises()) {
            throw new AccessDeniedHttpException('You are not allowed to view this exercise result.');
        }

        $quiz = $this->getExerciseFromCurrentContext($exerciseId, $course, $session, $canManage);
        $attempt = $this->getAttempt($attemptId, $quiz, $course, $session, $canManage);
        if (self::STATUS_COMPLETED !== (string) $attempt->getStatus()) {
            throw new BadRequestHttpException('The requested attempt has not been completed yet.');
        }

        $questionIds = $this->parseQuestionIds((string) $attempt->getDataTracking());
        if ([] === $questionIds) {
            $questionIds = $this->getExerciseQuestionIds($quiz);
        }

        $rowsByQuestion = $this->getAttemptRowsByQuestion((int) $attempt->getExeId());
        $questionIds = $this->normalizeResultQuestionIds($quiz, $questionIds, $rowsByQuestion);
        $pendingQuestionIds = $this->parseQuestionIds((string) $attempt->getQuestionsToCheck());
        $isReviewMode = $this->isReviewMode($attempt, $canManage, $request);
        $isLastAllowedAttempt = $this->isLastAllowedAttempt($quiz, $attempt, $course, $session);
        $visibility = $this->getResultVisibility($quiz, $isLastAllowedAttempt);
        if ($canManage) {
            $visibility = $this->getManagerResultVisibility($visibility);
        }
        $visibility['isReviewMode'] = $isReviewMode;

        $categoryScores = $this->getCategoryScores($questionIds, $rowsByQuestion, $attempt, $visibility);

        $questions = [];
        if ($canManage || true === ($visibility['showQuestionDetails'] ?? false)) {
            $questions = $this->getQuestions($quiz, $questionIds, $rowsByQuestion, $visibility, $pendingQuestionIds, $canManage, $isReviewMode);
            if (
                true === ($visibility['hideCorrectAnsweredQuestions'] ?? false)
                && true === ($visibility['showQuestionScore'] ?? false)
            ) {
                $questions = array_values(array_filter(
                    $questions,
                    static fn (array $question): bool => true !== ($question['isCorrect'] ?? false)
                ));
            }
        }

        $response = new ExerciseRuntimeResult();
        $response->exerciseId = $exerciseId;
        $response->attemptId = $attemptId;
        $response->title = $quiz->getTitle();
        $response->description = (string) $quiz->getDescription();
        $response->attempt = $this->normalizeAttempt($attempt, $quiz, $visibility);
        $response->visibility = $visibility;
        $response->questions = $questions;
        $response->categoryScores = $categoryScores;
        $response->ranking = true === ($visibility['showRanking'] ?? false)
            ? $this->getRanking($quiz, $course, $session, $attempt)
            : [];
        $response->finalActions = $this->getFinalActions($quiz, $attempt, $course, $session, $isReviewMode);
        $response->aiCorrection = $this->getAiCorrectionConfiguration($canManage && $isReviewMode);
        $response->canManage = $canManage;

        return $response;
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

    private function isReviewMode(TrackEExercise $attempt, bool $canManage, Request $request): bool
    {
        if (!$canManage) {
            return false;
        }

        if ('1' === (string) $request->query->get('review', '') || 'review' === strtolower((string) $request->query->get('mode', ''))) {
            return true;
        }

        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return false;
        }

        return (int) $attempt->getUser()->getId() !== (int) $user->getId();
    }

    /**
     * @return array<string, mixed>
     */
    private function getAiCorrectionConfiguration(bool $canManage): array
    {
        $helpersEnabled = $this->isSettingEnabled('ai_helpers.enable_ai_helpers');
        $openAnswersGraderEnabled = $this->isSettingEnabled('ai_helpers.open_answers_grader');

        return [
            'enabled' => $canManage && $helpersEnabled && $openAnswersGraderEnabled,
            'helpersEnabled' => $helpersEnabled,
            'openAnswersGraderEnabled' => $openAnswersGraderEnabled,
        ];
    }

    private function isSettingEnabled(string $settingName): bool
    {
        $value = $this->settingsManager->getSetting($settingName, true);

        if (true === $value || 1 === $value) {
            return true;
        }

        $normalized = strtolower(trim((string) $value));

        return \in_array($normalized, ['1', 'true', 'yes', 'on'], true);
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
                ->andWhere('(IDENTITY(links.session) = :sessionId OR links.session IS NULL)')
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
            if (self::VISIBILITY_PUBLISHED !== $visibility && !$this->isVisibleThroughLearnpath($quiz, $course, $session)) {
                throw new AccessDeniedHttpException('The requested exercise is not visible.');
            }
        }

        return $quiz;
    }

    private function isVisibleThroughLearnpath(CQuiz $quiz, Course $course, ?Session $session): bool
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            return false;
        }

        $learnpathId = $this->getQueryPositiveInt($request, ['learnpath_id', 'lp_id']);
        $learnpathItemId = $this->getQueryPositiveInt($request, ['learnpath_item_id', 'lp_item_id']);
        $learnpathItemViewId = $this->getQueryPositiveInt($request, ['learnpath_item_view_id']);
        $origin = strtolower(trim((string) $request->query->get('origin', '')));
        $hasLearnpathContext = 'learnpath' === $origin
            || $request->query->has('lp_init')
            || 0 < $learnpathId
            || 0 < $learnpathItemId
            || 0 < $learnpathItemViewId;

        if (!$hasLearnpathContext || 0 >= $learnpathId || 0 >= $learnpathItemId) {
            return false;
        }

        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return false;
        }

        $exerciseId = (int) ($quiz->getIid() ?? 0);
        if (0 >= $exerciseId) {
            return false;
        }

        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('item.iid')
            ->from(CLpItem::class, 'item')
            ->innerJoin('item.lp', 'lp')
            ->innerJoin('lp.resourceNode', 'lpNode')
            ->innerJoin('lpNode.resourceLinks', 'lpLinks')
            ->andWhere('item.iid = :learnpathItemId')
            ->andWhere('IDENTITY(item.lp) = :learnpathId')
            ->andWhere('item.itemType = :itemType')
            ->andWhere('(item.path = :exerciseIdString OR item.ref = :exerciseIdString)')
            ->andWhere('IDENTITY(lpLinks.course) = :courseId')
            ->andWhere('lpLinks.visibility = :publishedVisibility')
            ->andWhere('lpLinks.deletedAt IS NULL')
            ->andWhere('lpLinks.endVisibilityAt IS NULL')
            ->setParameter('learnpathItemId', $learnpathItemId, Types::INTEGER)
            ->setParameter('learnpathId', $learnpathId, Types::INTEGER)
            ->setParameter('itemType', self::LP_ITEM_TYPE_QUIZ)
            ->setParameter('exerciseIdString', (string) $exerciseId)
            ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
            ->setParameter('publishedVisibility', self::VISIBILITY_PUBLISHED, Types::INTEGER)
            ->setMaxResults(1)
        ;

        if (null !== $session) {
            $queryBuilder
                ->andWhere('(IDENTITY(lpLinks.session) = :sessionId OR lpLinks.session IS NULL)')
                ->setParameter('sessionId', (int) $session->getId(), Types::INTEGER)
            ;
        } else {
            $queryBuilder->andWhere('lpLinks.session IS NULL');
        }

        if (null === $queryBuilder->getQuery()->getOneOrNullResult()) {
            return false;
        }

        if (0 >= $learnpathItemViewId) {
            return true;
        }

        return $this->hasValidLearnpathItemView($learnpathItemViewId, $learnpathItemId, $learnpathId, $course, $session, $user);
    }

    private function hasValidLearnpathItemView(
        int $learnpathItemViewId,
        int $learnpathItemId,
        int $learnpathId,
        Course $course,
        ?Session $session,
        User $user,
    ): bool {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('itemView.iid')
            ->from(CLpItemView::class, 'itemView')
            ->innerJoin('itemView.view', 'lpView')
            ->andWhere('itemView.iid = :learnpathItemViewId')
            ->andWhere('IDENTITY(itemView.item) = :learnpathItemId')
            ->andWhere('IDENTITY(lpView.lp) = :learnpathId')
            ->andWhere('IDENTITY(lpView.course) = :courseId')
            ->andWhere('IDENTITY(lpView.user) = :userId')
            ->setParameter('learnpathItemViewId', $learnpathItemViewId, Types::INTEGER)
            ->setParameter('learnpathItemId', $learnpathItemId, Types::INTEGER)
            ->setParameter('learnpathId', $learnpathId, Types::INTEGER)
            ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
            ->setParameter('userId', (int) $user->getId(), Types::INTEGER)
            ->setMaxResults(1)
        ;

        if (null !== $session) {
            $queryBuilder
                ->andWhere('IDENTITY(lpView.session) = :sessionId')
                ->setParameter('sessionId', (int) $session->getId(), Types::INTEGER)
            ;
        } else {
            $queryBuilder->andWhere('lpView.session IS NULL');
        }

        return null !== $queryBuilder->getQuery()->getOneOrNullResult();
    }

    /**
     * @param array<int, string> $names
     */
    private function getQueryPositiveInt(Request $request, array $names): int
    {
        foreach ($names as $name) {
            $value = $request->query->get($name);
            if (\is_array($value)) {
                $value = $value[0] ?? null;
            }

            if (null === $value || '' === (string) $value || !is_numeric((string) $value)) {
                continue;
            }

            return max(0, (int) $value);
        }

        return 0;
    }

    private function getAttempt(int $attemptId, CQuiz $quiz, Course $course, ?Session $session, bool $canManage): TrackEExercise
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('attempt')
            ->from(TrackEExercise::class, 'attempt')
            ->andWhere('attempt.exeId = :attemptId')
            ->andWhere('IDENTITY(attempt.quiz) = :exerciseId')
            ->andWhere('IDENTITY(attempt.course) = :courseId')
            ->setParameter('attemptId', $attemptId, Types::INTEGER)
            ->setParameter('exerciseId', (int) $quiz->getIid(), Types::INTEGER)
            ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
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

        if (!$canManage) {
            $user = $this->security->getUser();
            if (!$user instanceof User) {
                throw new AccessDeniedHttpException('A valid authenticated user is required.');
            }

            $queryBuilder
                ->andWhere('IDENTITY(attempt.user) = :userId')
                ->setParameter('userId', (int) $user->getId(), Types::INTEGER)
            ;
        }

        $attempt = $queryBuilder->getQuery()->getOneOrNullResult();
        if (!$attempt instanceof TrackEExercise) {
            throw new NotFoundHttpException('The requested attempt was not found.');
        }

        return $attempt;
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
     * @return array<int, int>
     */
    private function getExerciseQuestionIds(CQuiz $quiz): array
    {
        $rows = $this->entityManager->createQueryBuilder()
            ->select('IDENTITY(relQuestion.question) AS questionId')
            ->from(CQuizRelQuestion::class, 'relQuestion')
            ->andWhere('IDENTITY(relQuestion.quiz) = :exerciseId')
            ->setParameter('exerciseId', (int) $quiz->getIid(), Types::INTEGER)
            ->orderBy('relQuestion.questionOrder', 'ASC')
            ->getQuery()
            ->getArrayResult()
        ;

        $questionIds = [];
        foreach ($rows as $row) {
            if (!\is_array($row)) {
                continue;
            }

            $questionId = (int) ($row['questionId'] ?? 0);
            if (0 < $questionId) {
                $questionIds[] = $questionId;
            }
        }

        return $questionIds;
    }

    /**
     * Direct feedback/adaptive exercises can end before visiting every question.
     * Result details must only show questions that were actually reached by the learner.
     *
     * @param array<int, int>                       $questionIds
     * @param array<int, array<int, TrackEAttempt>> $rowsByQuestion
     *
     * @return array<int, int>
     */
    private function normalizeResultQuestionIds(CQuiz $quiz, array $questionIds, array $rowsByQuestion): array
    {
        if (self::FEEDBACK_TYPE_DIRECT !== (int) $quiz->getFeedbackType()) {
            return $questionIds;
        }

        $answeredQuestionIds = array_map('intval', array_keys($rowsByQuestion));
        if ([] === $answeredQuestionIds) {
            return $questionIds;
        }

        return array_values(array_filter(
            $questionIds,
            static fn (int $questionId): bool => \in_array($questionId, $answeredQuestionIds, true)
        ));
    }

    /**
     * @return array<int, array<int, TrackEAttempt>>
     */
    private function getAttemptRowsByQuestion(int $attemptId): array
    {
        $rows = $this->entityManager->createQueryBuilder()
            ->select('attemptRow')
            ->from(TrackEAttempt::class, 'attemptRow')
            ->andWhere('IDENTITY(attemptRow.trackExercise) = :attemptId')
            ->setParameter('attemptId', $attemptId, Types::INTEGER)
            ->orderBy('attemptRow.questionId', 'ASC')
            ->addOrderBy('attemptRow.position', 'ASC')
            ->addOrderBy('attemptRow.id', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        $groupedRows = [];
        foreach ($rows as $row) {
            if (!$row instanceof TrackEAttempt) {
                continue;
            }

            $questionId = (int) $row->getQuestionId();
            if (0 >= $questionId) {
                continue;
            }

            $groupedRows[$questionId][] = $row;
        }

        return $groupedRows;
    }

    /**
     * @return array<string, mixed>
     */
    private function getResultVisibility(CQuiz $quiz, bool $isLastAllowedAttempt): array
    {
        $resultsDisabled = (int) $quiz->getResultsDisabled();
        $feedbackType = (int) $quiz->getFeedbackType();
        $pageResultConfiguration = $this->normalizePageResultConfiguration($quiz->getPageResultConfiguration());

        $showScore = self::RESULT_NO_SCORE_AND_EXPECTED_ANSWERS !== $resultsDisabled;
        if (
            self::RESULT_DONT_SHOW_SCORE_ONLY_IF_USER_FINISHES_ATTEMPTS_SHOW_ALWAYS_FEEDBACK === $resultsDisabled
            && !$isLastAllowedAttempt
        ) {
            $showScore = false;
        }

        $showQuestionDetails = !\in_array($resultsDisabled, [
            self::RESULT_NO_SCORE_AND_EXPECTED_ANSWERS,
            self::RESULT_SHOW_SCORE_ONLY,
            self::RESULT_SHOW_FINAL_SCORE_ONLY_WITH_CATEGORIES,
            self::RESULT_RANKING,
            self::RESULT_RADAR,
        ], true);

        $showExpectedAnswers = match ($resultsDisabled) {
            self::RESULT_SHOW_SCORE_AND_EXPECTED_ANSWERS,
            self::RESULT_SHOW_SCORE_AND_EXPECTED_ANSWERS_AND_RANKING,
            self::RESULT_SHOW_ONLY_IN_CORRECT_ANSWER => true,
            self::RESULT_SHOW_SCORE_ATTEMPT_SHOW_ANSWERS_LAST_ATTEMPT,
            self::RESULT_DONT_SHOW_SCORE_ONLY_IF_USER_FINISHES_ATTEMPTS_SHOW_ALWAYS_FEEDBACK,
            self::RESULT_SHOW_SCORE_ATTEMPT_SHOW_ANSWERS_LAST_ATTEMPT_NO_FEEDBACK => $isLastAllowedAttempt,
            default => false,
        };

        if (true === $pageResultConfiguration['hideExpectedAnswers']) {
            $showExpectedAnswers = false;
        }

        $showOnlyCorrectAnswers = self::RESULT_SHOW_ONLY_IN_CORRECT_ANSWER === $resultsDisabled;
        $showFeedback = self::FEEDBACK_TYPE_EXAM !== $feedbackType && match ($resultsDisabled) {
            self::RESULT_SHOW_SCORE_AND_EXPECTED_ANSWERS,
            self::RESULT_SHOW_SCORE_AND_EXPECTED_ANSWERS_AND_RANKING,
            self::RESULT_SHOW_ONLY_IN_CORRECT_ANSWER => $showExpectedAnswers,
            self::RESULT_SHOW_SCORE_ATTEMPT_SHOW_ANSWERS_LAST_ATTEMPT => $showExpectedAnswers,
            self::RESULT_DONT_SHOW_SCORE_ONLY_IF_USER_FINISHES_ATTEMPTS_SHOW_ALWAYS_FEEDBACK,
            self::RESULT_SHOW_SCORE_ATTEMPT_SHOW_ANSWERS_LAST_ATTEMPT_NO_FEEDBACK => true,
            default => false,
        };

        $showQuestionScore = $showScore
            && $showQuestionDetails
            && false === $pageResultConfiguration['hideQuestionScore']
            && !$showOnlyCorrectAnswers;

        return [
            'resultsDisabled' => $resultsDisabled,
            'feedbackType' => $feedbackType,
            'reviewAnswers' => 1 === (int) $quiz->getReviewAnswers(),
            'saveCorrectAnswers' => (int) ($quiz->getSaveCorrectAnswers() ?? 0),
            'showScore' => $showScore,
            'showTotalScore' => $showScore && false === $pageResultConfiguration['hideTotalScore'],
            'showQuestionScore' => $showQuestionScore,
            'showQuestionStatus' => $showQuestionScore,
            'showQuestionDetails' => $showQuestionDetails,
            'showCorrections' => $showExpectedAnswers,
            'showExpectedAnswers' => $showExpectedAnswers,
            'showStudentAnswers' => !$showOnlyCorrectAnswers,
            'showOnlyCorrectAnswers' => $showOnlyCorrectAnswers,
            'showFeedback' => $showFeedback,
            'hideCorrectAnsweredQuestions' => $pageResultConfiguration['hideCorrectAnsweredQuestions'],
            'showCategoryTable' => false === $pageResultConfiguration['hideCategoryTable'],
            'showRanking' => \in_array($resultsDisabled, [
                self::RESULT_RANKING,
                self::RESULT_SHOW_SCORE_AND_EXPECTED_ANSWERS_AND_RANKING,
            ], true),
            'showRadar' => self::RESULT_RADAR === $resultsDisabled,
            'isLastAllowedAttempt' => $isLastAllowedAttempt,
            'pageResultConfiguration' => $pageResultConfiguration,
        ];
    }

    /**
     * @param array<string, mixed> $visibility
     *
     * @return array<string, mixed>
     */
    private function getManagerResultVisibility(array $visibility): array
    {
        $visibility['showScore'] = true;
        $visibility['showTotalScore'] = true;
        $visibility['showQuestionScore'] = true;
        $visibility['showQuestionStatus'] = true;
        $visibility['showQuestionDetails'] = true;
        $visibility['showCorrections'] = true;
        $visibility['showExpectedAnswers'] = true;
        $visibility['showStudentAnswers'] = true;
        $visibility['showOnlyCorrectAnswers'] = false;
        $visibility['showFeedback'] = true;

        return $visibility;
    }

    /**
     * @param array<string, mixed> $configuration
     *
     * @return array<string, bool>
     */
    private function normalizePageResultConfiguration(array $configuration): array
    {
        return [
            'hideExpectedAnswers' => $this->isEnabledPageResultFlag($configuration, 'hideExpectedAnswers', 'hide_expected_answer'),
            'hideTotalScore' => $this->isEnabledPageResultFlag($configuration, 'hideTotalScore', 'hide_total_score'),
            'hideQuestionScore' => $this->isEnabledPageResultFlag($configuration, 'hideQuestionScore', 'hide_question_score'),
            'hideCategoryTable' => $this->isEnabledPageResultFlag($configuration, 'hideCategoryTable', 'hide_category_table'),
            'hideCorrectAnsweredQuestions' => $this->isEnabledPageResultFlag($configuration, 'hideCorrectAnsweredQuestions', 'hide_correct_answered_questions'),
        ];
    }

    /**
     * @param array<string, mixed> $configuration
     */
    private function isEnabledPageResultFlag(array $configuration, string $camelKey, string $legacyKey): bool
    {
        $value = $configuration[$camelKey] ?? $configuration[$legacyKey] ?? false;

        return true === $value || 1 === $value || '1' === (string) $value || 'on' === strtolower((string) $value);
    }

    private function isLastAllowedAttempt(CQuiz $quiz, TrackEExercise $attempt, Course $course, ?Session $session): bool
    {
        $maxAttempt = (int) $quiz->getMaxAttempt();
        if (0 >= $maxAttempt) {
            return false;
        }

        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('COUNT(completedAttempt.exeId)')
            ->from(TrackEExercise::class, 'completedAttempt')
            ->andWhere('IDENTITY(completedAttempt.quiz) = :exerciseId')
            ->andWhere('IDENTITY(completedAttempt.course) = :courseId')
            ->andWhere('IDENTITY(completedAttempt.user) = :userId')
            ->andWhere('completedAttempt.status = :status')
            ->setParameter('exerciseId', (int) $quiz->getIid(), Types::INTEGER)
            ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
            ->setParameter('userId', (int) $attempt->getUser()->getId(), Types::INTEGER)
            ->setParameter('status', self::STATUS_COMPLETED)
        ;

        if (null !== $session) {
            $queryBuilder
                ->andWhere('IDENTITY(completedAttempt.session) = :sessionId')
                ->setParameter('sessionId', (int) $session->getId(), Types::INTEGER)
            ;
        } else {
            $queryBuilder->andWhere('completedAttempt.session IS NULL');
        }

        return (int) $queryBuilder->getQuery()->getSingleScalarResult() >= $maxAttempt;
    }

    /**
     * @param array<string, mixed> $visibility
     *
     * @return array<string, mixed>
     */
    private function normalizeAttempt(TrackEExercise $attempt, CQuiz $quiz, array $visibility): array
    {
        $score = (float) $attempt->getScore();
        $maxScore = (float) $attempt->getMaxScore();
        $percentage = 0.0 < $maxScore ? round(($score / $maxScore) * 100, 2) : 0.0;
        $passPercentage = (int) ($quiz->getPassPercentage() ?? 0);
        $hasPassPercentage = 0 < $passPercentage;
        $passed = $hasPassPercentage ? $percentage >= (float) $passPercentage : null;

        return [
            'attemptId' => (int) $attempt->getExeId(),
            'status' => (string) $attempt->getStatus(),
            'score' => true === ($visibility['showTotalScore'] ?? false) ? $score : null,
            'maxScore' => true === ($visibility['showTotalScore'] ?? false) ? $maxScore : null,
            'percentage' => true === ($visibility['showTotalScore'] ?? false) ? $percentage : null,
            'passPercentage' => $hasPassPercentage ? $passPercentage : null,
            'passed' => true === ($visibility['showTotalScore'] ?? false) ? $passed : null,
            'startedAt' => $attempt->getStartDate()->format(DateTimeInterface::ATOM),
            'completedAt' => $attempt->getExeDate()->format(DateTimeInterface::ATOM),
            'duration' => (int) $attempt->getExeDuration(),
            'questionsToCheck' => $this->parseQuestionIds((string) $attempt->getQuestionsToCheck()),
            'textWhenFinished' => $this->getFinishedText($quiz, $passed),
        ];
    }

    private function getFinishedText(CQuiz $quiz, ?bool $passed): string
    {
        if (false === $passed && '' !== (string) $quiz->getTextWhenFinishedFailure()) {
            return (string) $quiz->getTextWhenFinishedFailure();
        }

        return (string) $quiz->getTextWhenFinished();
    }

    /**
     * @param array<int, int>                       $questionIds
     * @param array<int, array<int, TrackEAttempt>> $rowsByQuestion
     * @param array<string, mixed>                  $visibility
     *
     * @return array<int, array<string, mixed>>
     */
    private function getCategoryScores(array $questionIds, array $rowsByQuestion, TrackEExercise $attempt, array $visibility): array
    {
        if ([] === $questionIds || true !== ($visibility['showScore'] ?? false) || true !== ($visibility['showCategoryTable'] ?? false)) {
            return [];
        }

        $questionsById = $this->getQuestionsById($questionIds);
        $categoryRows = [];
        $noneRow = null;
        $hasNamedCategory = false;

        foreach ($questionIds as $questionId) {
            $question = $questionsById[$questionId] ?? null;
            if (!$question instanceof CQuizQuestion || $this->isStructuralContentQuestion($question)) {
                continue;
            }

            $score = $this->getQuestionScore($rowsByQuestion[$questionId] ?? []);
            $maxScore = $this->getQuestionMaxScore($question);
            if (0.0 === $score && 0.0 === $maxScore) {
                continue;
            }

            $categories = $question->getCategories();
            if (0 === $categories->count()) {
                if (null === $noneRow) {
                    $noneRow = [
                        'categoryId' => null,
                        'title' => '',
                        'labelKey' => 'None',
                        'score' => 0.0,
                        'maxScore' => 0.0,
                    ];
                }

                $noneRow['score'] += $score;
                $noneRow['maxScore'] += $maxScore;

                continue;
            }

            foreach ($categories as $category) {
                if (!$category instanceof CQuizQuestionCategory || null === $category->getIid()) {
                    continue;
                }

                $categoryId = (int) $category->getIid();
                if (!isset($categoryRows[$categoryId])) {
                    $categoryRows[$categoryId] = [
                        'categoryId' => $categoryId,
                        'title' => $category->getTitle(),
                        'labelKey' => null,
                        'score' => 0.0,
                        'maxScore' => 0.0,
                    ];
                }

                $categoryRows[$categoryId]['score'] += $score;
                $categoryRows[$categoryId]['maxScore'] += $maxScore;
                $hasNamedCategory = true;
            }
        }

        if (!$hasNamedCategory) {
            return [];
        }

        uasort($categoryRows, static function (array $left, array $right): int {
            return strcasecmp((string) ($left['title'] ?? ''), (string) ($right['title'] ?? ''));
        });

        $rows = [];
        foreach ($categoryRows as $row) {
            $rows[] = $this->normalizeCategoryScoreRow($row);
        }

        if (null !== $noneRow && 0.0 < (float) ($noneRow['maxScore'] ?? 0.0)) {
            $rows[] = $this->normalizeCategoryScoreRow($noneRow);
        }

        $rows[] = $this->normalizeCategoryScoreRow([
            'categoryId' => null,
            'title' => '',
            'labelKey' => 'Total',
            'score' => (float) $attempt->getScore(),
            'maxScore' => (float) $attempt->getMaxScore(),
            'isTotal' => true,
        ]);

        return $rows;
    }

    /**
     * @param array<int, int> $questionIds
     *
     * @return array<int, CQuizQuestion>
     */
    private function getQuestionsById(array $questionIds): array
    {
        $rows = $this->entityManager->createQueryBuilder()
            ->select('question')
            ->addSelect('category')
            ->from(CQuizQuestion::class, 'question')
            ->leftJoin('question.categories', 'category')
            ->andWhere('question.iid IN (:questionIds)')
            ->setParameter('questionIds', $questionIds, ArrayParameterType::INTEGER)
            ->getQuery()
            ->getResult()
        ;

        $questions = [];
        foreach ($rows as $row) {
            if (!$row instanceof CQuizQuestion || null === $row->getIid()) {
                continue;
            }

            $questions[(int) $row->getIid()] = $row;
        }

        return $questions;
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return array<string, mixed>
     */
    private function normalizeCategoryScoreRow(array $row): array
    {
        $score = (float) ($row['score'] ?? 0.0);
        $maxScore = (float) ($row['maxScore'] ?? 0.0);

        return [
            'categoryId' => isset($row['categoryId']) ? (int) $row['categoryId'] : null,
            'title' => (string) ($row['title'] ?? ''),
            'labelKey' => isset($row['labelKey']) ? (string) $row['labelKey'] : null,
            'score' => $score,
            'maxScore' => $maxScore,
            'percentage' => 0.0 < $maxScore ? round(($score / $maxScore) * 100, 2) : 0.0,
            'isTotal' => true === ($row['isTotal'] ?? false),
        ];
    }

    /**
     * @param array<int, int>                      $questionIds
     * @param array<int, array<int, TrackEAttempt>> $rowsByQuestion
     * @param array<string, mixed>                 $visibility
     * @param array<int, int>                      $pendingQuestionIds
     *
     * @return array<int, array<string, mixed>>
     */
    private function getQuestions(CQuiz $quiz, array $questionIds, array $rowsByQuestion, array $visibility, array $pendingQuestionIds, bool $canManage, bool $isReviewMode): array
    {
        $relations = $this->entityManager->createQueryBuilder()
            ->select('relQuestion')
            ->addSelect('question')
            ->from(CQuizRelQuestion::class, 'relQuestion')
            ->innerJoin('relQuestion.question', 'question')
            ->andWhere('IDENTITY(relQuestion.quiz) = :exerciseId')
            ->andWhere('IDENTITY(relQuestion.question) IN (:questionIds)')
            ->setParameter('exerciseId', (int) $quiz->getIid(), Types::INTEGER)
            ->setParameter('questionIds', $questionIds, ArrayParameterType::INTEGER)
            ->getQuery()
            ->getResult()
        ;

        $questions = [];
        foreach ($relations as $relation) {
            if (!$relation instanceof CQuizRelQuestion) {
                continue;
            }

            $question = $relation->getQuestion();
            if (null === $question->getIid()) {
                continue;
            }

            $questionId = (int) $question->getIid();
            $questions[$questionId] = $this->normalizeQuestion(
                $question,
                $rowsByQuestion[$questionId] ?? [],
                $visibility,
                \in_array($questionId, $pendingQuestionIds, true),
                $canManage,
                $isReviewMode,
            );
        }

        $orderedQuestions = [];
        foreach ($questionIds as $position => $questionId) {
            if (!isset($questions[$questionId])) {
                continue;
            }

            $question = $questions[$questionId];
            $question['position'] = $position + 1;
            $orderedQuestions[] = $question;
        }

        return $orderedQuestions;
    }

    /**
     * @param array<int, TrackEAttempt> $rows
     * @param array<string, mixed>      $visibility
     *
     * @return array<string, mixed>
     */
    private function normalizeQuestion(CQuizQuestion $question, array $rows, array $visibility, bool $pendingCorrection, bool $canManage, bool $isReviewMode): array
    {
        $questionScore = $this->getQuestionScore($rows);
        $maxScore = $this->getQuestionMaxScore($question);
        $requiresManualCorrection = $this->requiresManualCorrection($question);
        $isStructuralContent = $this->isStructuralContentQuestion($question);
        $isCorrect = 0 < $maxScore ? $questionScore >= $maxScore : 0 < $questionScore;
        if ($isStructuralContent || ($requiresManualCorrection && $pendingCorrection)) {
            $isCorrect = null;
        }
        $showQuestionCorrection = $this->shouldShowQuestionCorrection($rows, $visibility, true === $isCorrect);
        $feedback = $this->getQuestionFeedback($question, $visibility, $showQuestionCorrection);
        if ($this->shouldHideManualCorrectionFeedback($requiresManualCorrection, $pendingCorrection, $questionScore)) {
            $feedback = null;
        }

        return [
            'id' => (int) $question->getIid(),
            'title' => $question->getQuestion(),
            'description' => (string) $question->getDescription(),
            'type' => (int) $question->getType(),
            'typeLabel' => $this->getQuestionTypeLabel((int) $question->getType()),
            'position' => 0,
            'parentId' => (int) ($question->getParentMediaId() ?? 0),
            'parent' => $this->normalizeParentMediaQuestion($question),
            'score' => !$isStructuralContent && true === ($visibility['showQuestionScore'] ?? false) ? $questionScore : null,
            'maxScore' => !$isStructuralContent && true === ($visibility['showQuestionScore'] ?? false) ? $maxScore : null,
            'isCorrect' => !$isStructuralContent && true === ($visibility['showQuestionStatus'] ?? false) ? $isCorrect : null,
            'requiresManualCorrection' => $requiresManualCorrection,
            'pendingCorrection' => $pendingCorrection,
            'canCorrect' => $isReviewMode && $canManage && $requiresManualCorrection,
            'feedback' => $feedback,
            'answer' => $this->normalizeQuestionAnswer($question, $rows, $visibility, $showQuestionCorrection),
        ];
    }


    /**
     * @return array<string, mixed>|null
     */
    private function normalizeParentMediaQuestion(CQuizQuestion $question): ?array
    {
        $parentId = (int) ($question->getParentMediaId() ?? 0);
        if (0 >= $parentId) {
            return null;
        }

        $parent = $this->entityManager->getRepository(CQuizQuestion::class)->find($parentId);
        if (!$parent instanceof CQuizQuestion || 15 !== (int) $parent->getType()) {
            return null;
        }

        return [
            'id' => (int) $parent->getIid(),
            'title' => $parent->getQuestion(),
            'description' => (string) $parent->getDescription(),
            'type' => 15,
            'typeLabel' => $this->getQuestionTypeLabel(15),
            'content' => [
                'title' => $parent->getQuestion(),
                'description' => (string) $parent->getDescription(),
            ],
        ];
    }


    private function getQuestionMaxScore(CQuizQuestion $question): float
    {
        if ($this->isStructuralContentQuestion($question)) {
            return 0.0;
        }

        if (\in_array((int) $question->getType(), [9, 12], true)) {
            $answer = $this->getFirstAnswer($question);
            if ($answer instanceof CQuizAnswer && 0.0 !== $answer->getPonderation()) {
                return (float) $answer->getPonderation();
            }
        }

        return (float) $question->getPonderation();
    }

    /**
     * @param array<int, TrackEAttempt> $rows
     */
    private function getQuestionScore(array $rows): float
    {
        $row = $rows[0] ?? null;

        return $row instanceof TrackEAttempt ? (float) $row->getMarks() : 0.0;
    }

    /**
     * @param array<int, TrackEAttempt> $rows
     * @param array<string, mixed>      $visibility
     */
    private function shouldShowQuestionCorrection(array $rows, array $visibility, bool $isCorrect): bool
    {
        if (true !== ($visibility['showCorrections'] ?? false)) {
            return false;
        }

        if (true === ($visibility['showOnlyIncorrect'] ?? false) && $isCorrect) {
            return false;
        }

        return [] !== $rows;
    }

    /**
     * @param array<string, mixed> $visibility
     */
    private function getQuestionFeedback(CQuizQuestion $question, array $visibility, bool $showQuestionCorrection): ?string
    {
        if (!$showQuestionCorrection || true !== ($visibility['showFeedback'] ?? false)) {
            return null;
        }

        return '' !== (string) $question->getFeedback() ? (string) $question->getFeedback() : null;
    }

    private function shouldHideManualCorrectionFeedback(bool $requiresManualCorrection, bool $pendingCorrection, float $questionScore): bool
    {
        if (!$requiresManualCorrection || $pendingCorrection) {
            return false;
        }

        return 0.0 < $questionScore;
    }

    /**
     * @param array<int, TrackEAttempt> $rows
     * @param array<string, mixed>      $visibility
     *
     * @return array<string, mixed>
     */
    private function normalizeQuestionAnswer(CQuizQuestion $question, array $rows, array $visibility, bool $showQuestionCorrection): array
    {
        $type = (int) $question->getType();
        if (\in_array($type, [1, 2, 9, 10, 11, 12, 14, 17, 21, 22], true)) {
            return $this->normalizeChoiceAnswer($question, $rows, $visibility, $showQuestionCorrection);
        }

        if (\in_array($type, [3, 27], true)) {
            return $this->normalizeFillBlankAnswer($question, $rows, $visibility, $showQuestionCorrection);
        }

        if (\in_array($type, [4, 19, 24, 25], true)) {
            return $this->normalizeMatchingAnswer($question, $rows, $visibility, $showQuestionCorrection);
        }

        if (self::DRAGGABLE === $type) {
            return $this->normalizeDraggableAnswer($question, $rows, $visibility, $showQuestionCorrection);
        }

        if (\in_array($type, [28, 29], true)) {
            return $this->normalizeDropdownAnswer($question, $rows, $visibility, $showQuestionCorrection);
        }

        if (16 === $type) {
            return $this->normalizeCalculatedAnswer($question, $rows, $visibility, $showQuestionCorrection);
        }

        if (\in_array($type, [6, self::HOTSPOT_DELINEATION, 26], true)) {
            return $this->normalizeHotspotAnswer($question, $rows, $visibility, $showQuestionCorrection);
        }

        if (5 === $type) {
            return $this->normalizeFreeAnswer($rows, $visibility);
        }

        if (13 === $type) {
            return $this->normalizeOralExpressionAnswer($rows, $visibility);
        }

        if (23 === $type) {
            return $this->normalizeUploadAnswer($rows, $visibility);
        }

        if (self::ANSWER_IN_OFFICE_DOC === $type) {
            return $this->normalizeOnlyofficeAnswer($rows, $visibility);
        }

        if (self::ANNOTATION === $type) {
            return $this->normalizeAnnotationAnswer($question, $rows, $visibility);
        }

        if (\in_array($type, [15, 31], true)) {
            return $this->normalizeContentAnswer($question);
        }

        return [
            'kind' => 'unsupported',
            'studentAnswer' => $this->normalizeRawRows($rows),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeContentAnswer(CQuizQuestion $question): array
    {
        return [
            'kind' => 'content',
            'title' => $question->getQuestion(),
            'description' => (string) $question->getDescription(),
        ];
    }

    /**
     * @param array<int, TrackEAttempt> $rows
     * @param array<string, mixed>      $visibility
     *
     * @return array<string, mixed>
     */
    private function normalizeChoiceAnswer(CQuizQuestion $question, array $rows, array $visibility, bool $showQuestionCorrection): array
    {
        $selectedIds = array_flip($this->getSavedAnswerIds($rows));
        $trueFalseChoices = $this->getSavedTrueFalseChoices($rows);
        $isTrueFalse = \in_array((int) $question->getType(), [11, 12, 22], true);
        $isDegreeCertainty = 22 === (int) $question->getType();
        $degreeCertaintyChoices = $isDegreeCertainty ? $this->getSavedTrueFalseDegreeCertaintyChoices($rows) : [];
        $options = $this->getQuestionOptions($question);
        $choices = [];
        $showStudentAnswers = true === ($visibility['showStudentAnswers'] ?? true);
        $showExpectedAnswers = $showQuestionCorrection && true === ($visibility['showExpectedAnswers'] ?? false);
        $showOnlyCorrectAnswers = true === ($visibility['showOnlyCorrectAnswers'] ?? false);
        $showFeedback = true === ($visibility['showFeedback'] ?? false);

        foreach ($this->getOrderedAnswers($question) as $answer) {
            $answerId = (int) $answer->getIid();
            $isCorrectAnswer = 1 === (int) $answer->getCorrect();
            if ($showOnlyCorrectAnswers && !$isTrueFalse && !$isCorrectAnswer) {
                continue;
            }

            $isSelected = isset($selectedIds[$answerId]);
            $choice = [
                'id' => $answerId,
                'answer' => $answer->getAnswer(),
                'selected' => $showStudentAnswers && $isSelected,
            ];

            if ($isTrueFalse) {
                if ($isDegreeCertainty) {
                    $selectedOption = (int) ($degreeCertaintyChoices[$answerId]['choice'] ?? 0);
                    $selectedDegree = (int) ($degreeCertaintyChoices[$answerId]['degree'] ?? 0);
                    $choice['selectedDegreeId'] = $showStudentAnswers && 0 < $selectedDegree ? $selectedDegree : null;
                    $choice['selectedDegreeLabel'] = $showStudentAnswers ? $this->getOptionTitle($options, $selectedDegree) : '';
                } else {
                    $selectedOption = $trueFalseChoices[$answerId] ?? 0;
                }

                $choice['selectedOptionId'] = $showStudentAnswers && 0 < $selectedOption ? $selectedOption : null;
                $choice['selectedOptionLabel'] = $showStudentAnswers ? $this->getOptionTitle($options, $selectedOption) : '';
            }

            if ($showExpectedAnswers) {
                if ($isTrueFalse) {
                    $choice['correctOptionId'] = (int) $answer->getCorrect();
                    $choice['correctOptionLabel'] = $this->getOptionTitle($options, (int) $answer->getCorrect());
                } else {
                    $choice['correct'] = $isCorrectAnswer;
                }
            }

            if ($showFeedback && '' !== (string) $answer->getComment()) {
                if ($showExpectedAnswers || $isSelected || ($showOnlyCorrectAnswers && $isCorrectAnswer)) {
                    $choice['comment'] = (string) $answer->getComment();
                }
            }

            $choices[] = $choice;
        }

        return [
            'kind' => $isTrueFalse ? 'true_false' : 'choice',
            'choices' => $choices,
            'options' => array_values($options),
            'hasDegreeCertainty' => $isDegreeCertainty,
        ];
    }

    /**
     * @param array<int, TrackEAttempt> $rows
     *
     * @return array<int, array{choice: int, degree: int}>
     */
    private function getSavedTrueFalseDegreeCertaintyChoices(array $rows): array
    {
        $choices = [];
        foreach ($rows as $row) {
            $parts = explode(':', (string) $row->getAnswer());
            $answerId = isset($parts[0]) ? (int) $parts[0] : 0;
            $optionId = isset($parts[1]) ? (int) $parts[1] : 0;
            $degreeId = isset($parts[2]) ? (int) $parts[2] : 0;
            if (0 < $answerId && 0 < $optionId) {
                $choices[$answerId] = [
                    'choice' => $optionId,
                    'degree' => $degreeId,
                ];
            }
        }

        return $choices;
    }

    /**
     * @param array<int, TrackEAttempt> $rows
     *
     * @return array<string, mixed>
     */
    private function normalizeFillBlankAnswer(CQuizQuestion $question, array $rows, array $visibility, bool $showQuestionCorrection): array
    {
        $row = $rows[0] ?? null;
        $answer = $this->getFirstAnswer($question);
        $teacherInfo = $answer instanceof CQuizAnswer ? $this->parseFillBlankAnswer($answer->getAnswer(), false) : null;
        $studentInfo = $row instanceof TrackEAttempt ? $this->parseFillBlankAnswer($row->getAnswer(), true) : null;
        $showStudentAnswers = true === ($visibility['showStudentAnswers'] ?? true);
        $showExpectedAnswers = $showQuestionCorrection && true === ($visibility['showExpectedAnswers'] ?? false);
        $blankCount = null !== $teacherInfo ? \count($teacherInfo['words']) : \count($studentInfo['student_answer'] ?? []);
        $blanks = [];

        for ($index = 0; $index < $blankCount; ++$index) {
            $blank = [
                'position' => $index + 1,
                'studentAnswer' => $showStudentAnswers ? (string) ($studentInfo['student_answer'][$index] ?? '') : '',
                'studentScore' => $showStudentAnswers ? (string) ($studentInfo['student_score'][$index] ?? '') : '',
            ];

            if ($showExpectedAnswers && null !== $teacherInfo) {
                $blank['correctAnswer'] = (string) ($teacherInfo['words'][$index] ?? '');
            }

            $blanks[] = $blank;
        }

        return [
            'kind' => 'fill_blanks',
            'text' => (string) ($teacherInfo['text'] ?? ''),
            'blanks' => $blanks,
            'showStudentAnswers' => $showStudentAnswers,
        ];
    }

    /**
     * @param array<int, TrackEAttempt> $rows
     * @param array<string, mixed>      $visibility
     *
     * @return array<string, mixed>
     */
    private function normalizeMatchingAnswer(CQuizQuestion $question, array $rows, array $visibility, bool $showQuestionCorrection): array
    {
        $selected = $this->getSavedMatchingChoices($rows);
        $options = [];
        $prompts = [];
        $answers = $this->getOrderedAnswers($question);
        $showStudentAnswers = true === ($visibility['showStudentAnswers'] ?? true);
        $showExpectedAnswers = $showQuestionCorrection && true === ($visibility['showExpectedAnswers'] ?? false);
        $showFeedback = true === ($visibility['showFeedback'] ?? false);

        foreach ($answers as $answer) {
            $answerId = (int) $answer->getIid();
            if (0 < (int) ($answer->getCorrect() ?? 0)) {
                continue;
            }

            $options[$answerId] = [
                'id' => $answerId,
                'answer' => $answer->getAnswer(),
                'position' => (int) $answer->getPosition(),
            ];
        }

        foreach ($answers as $answer) {
            $answerId = (int) $answer->getIid();
            $correct = (int) ($answer->getCorrect() ?? 0);
            if (0 >= $correct) {
                continue;
            }

            $hasStudentAnswer = isset($selected[$answerId]);
            $prompt = [
                'id' => $answerId,
                'answer' => $answer->getAnswer(),
                'selectedOptionId' => $showStudentAnswers ? ($selected[$answerId] ?? null) : null,
                'selectedOptionAnswer' => $showStudentAnswers && $hasStudentAnswer ? (string) ($options[$selected[$answerId]]['answer'] ?? '') : '',
            ];

            if ($showExpectedAnswers) {
                $prompt['correctOptionId'] = $correct;
                $prompt['correctOptionAnswer'] = (string) ($options[$correct]['answer'] ?? '');
            }

            if ($showFeedback && '' !== (string) $answer->getComment()) {
                if ($showExpectedAnswers || $hasStudentAnswer) {
                    $prompt['comment'] = (string) $answer->getComment();
                }
            }

            $prompts[] = $prompt;
        }

        return [
            'kind' => 'matching',
            'prompts' => $prompts,
            'options' => array_values($options),
        ];
    }

    /**
     * @param array<int, TrackEAttempt> $rows
     *
     * @return array<string, mixed>
     */
    private function normalizeDraggableAnswer(CQuizQuestion $question, array $rows, array $visibility, bool $showQuestionCorrection): array
    {
        $savedPositions = $this->getSavedMatchingChoices($rows);
        $studentItems = [];
        $expectedItems = [];
        $showStudentAnswers = true === ($visibility['showStudentAnswers'] ?? true);
        $showExpectedAnswers = $showQuestionCorrection && true === ($visibility['showExpectedAnswers'] ?? false);

        foreach ($this->getOrderedAnswers($question) as $answer) {
            $answerId = (int) $answer->getIid();
            $targetPosition = (int) ($answer->getCorrect() ?? 0);
            if (0 >= $targetPosition) {
                continue;
            }

            $selectedPosition = $savedPositions[$answerId] ?? null;
            $item = [
                'id' => $answerId,
                'answer' => $answer->getAnswer(),
                'selectedPosition' => $selectedPosition,
                'correctPosition' => $showQuestionCorrection ? $targetPosition : null,
                'isCorrect' => null !== $selectedPosition && $selectedPosition === $targetPosition,
            ];

            if ($showStudentAnswers && null !== $selectedPosition) {
                $studentItems[] = $item;
            }

            if ($showExpectedAnswers) {
                $expectedItems[] = $item;
            }
        }

        usort($studentItems, static fn (array $left, array $right): int => ((int) ($left['selectedPosition'] ?? 0)) <=> ((int) ($right['selectedPosition'] ?? 0)));
        usort($expectedItems, static fn (array $left, array $right): int => ((int) ($left['correctPosition'] ?? 0)) <=> ((int) ($right['correctPosition'] ?? 0)));

        return [
            'kind' => 'draggable',
            'orientation' => \in_array((string) $question->getExtra(), ['h', 'v'], true) ? (string) $question->getExtra() : 'h',
            'studentItems' => $studentItems,
            'expectedItems' => $showExpectedAnswers ? $expectedItems : [],
        ];
    }

    /**
     * @param array<int, TrackEAttempt> $rows
     * @param array<string, mixed>      $visibility
     *
     * @return array<string, mixed>
     */
    private function normalizeDropdownAnswer(CQuizQuestion $question, array $rows, array $visibility, bool $showQuestionCorrection): array
    {
        $selectedId = $this->getFirstSavedAnswerId($rows);
        $showStudentAnswers = true === ($visibility['showStudentAnswers'] ?? true);
        $showExpectedAnswers = $showQuestionCorrection && true === ($visibility['showExpectedAnswers'] ?? false);
        $showOnlyCorrectAnswers = true === ($visibility['showOnlyCorrectAnswers'] ?? false);
        $showFeedback = true === ($visibility['showFeedback'] ?? false);
        $options = [];
        foreach ($this->getOrderedAnswers($question) as $answer) {
            $answerId = (int) $answer->getIid();
            $isCorrectAnswer = 1 === (int) $answer->getCorrect();
            if ($showOnlyCorrectAnswers && !$isCorrectAnswer) {
                continue;
            }

            $isSelected = $answerId === $selectedId;
            $option = [
                'id' => $answerId,
                'answer' => $answer->getAnswer(),
                'selected' => $showStudentAnswers && $isSelected,
            ];

            if ($showExpectedAnswers) {
                $option['correct'] = $isCorrectAnswer;
            }

            if ($showFeedback && '' !== (string) $answer->getComment()) {
                if ($showExpectedAnswers || $isSelected || ($showOnlyCorrectAnswers && $isCorrectAnswer)) {
                    $option['comment'] = (string) $answer->getComment();
                }
            }

            $options[] = $option;
        }

        return [
            'kind' => 'dropdown',
            'selectedId' => $showStudentAnswers && 0 < $selectedId ? $selectedId : null,
            'options' => $options,
        ];
    }

    /**
     * @param array<int, TrackEAttempt> $rows
     *
     * @return array<string, mixed>
     */
    private function normalizeCalculatedAnswer(CQuizQuestion $question, array $rows, array $visibility, bool $showQuestionCorrection): array
    {
        $row = $rows[0] ?? null;
        $studentAnswer = '';
        $answerId = 0;
        if ($row instanceof TrackEAttempt) {
            [$answerId, $studentAnswer] = $this->parseCalculatedStudentAnswer((string) $row->getAnswer());
        }

        $teacherAnswer = 0 < $answerId ? $this->getAnswerById($question, $answerId) : $this->getFirstAnswer($question);
        $parsedTeacherAnswer = $teacherAnswer instanceof CQuizAnswer
            ? $this->parseCalculatedTeacherAnswer((string) $teacherAnswer->getAnswer())
            : ['text' => '', 'expectedAnswer' => ''];

        $showStudentAnswers = true === ($visibility['showStudentAnswers'] ?? true);
        $showExpectedAnswers = $showQuestionCorrection && true === ($visibility['showExpectedAnswers'] ?? false);

        return [
            'kind' => 'calculated',
            'text' => (string) ($parsedTeacherAnswer['text'] ?? ''),
            'studentAnswer' => $showStudentAnswers ? $studentAnswer : '',
            'expectedAnswer' => $showExpectedAnswers ? (string) ($parsedTeacherAnswer['expectedAnswer'] ?? '') : null,
        ];
    }

    /**
     * @return array{0: int, 1: string}
     */
    private function parseCalculatedStudentAnswer(string $value): array
    {
        $parts = explode(':', $value, 2);
        if (2 === \count($parts)) {
            return [(int) $parts[0], trim((string) $parts[1])];
        }

        return [0, trim($value)];
    }

    /**
     * @return array{text: string, expectedAnswer: string}
     */
    private function parseCalculatedTeacherAnswer(string $answer): array
    {
        $parts = explode('@@', $answer, 2);
        $textWithExpectedAnswer = (string) ($parts[0] ?? $answer);
        $expectedAnswer = '';
        $text = $textWithExpectedAnswer;

        if (1 === preg_match('/\[([^\[\]]*)\]\s*$/', $textWithExpectedAnswer, $matches)) {
            $expectedAnswer = trim((string) ($matches[1] ?? ''));
            $text = (string) preg_replace('/\s*\[[^\[\]]*\]\s*$/', '', $textWithExpectedAnswer);
        }

        return [
            'text' => $text,
            'expectedAnswer' => $expectedAnswer,
        ];
    }

    private function getAnswerById(CQuizQuestion $question, int $answerId): ?CQuizAnswer
    {
        foreach ($this->getOrderedAnswers($question) as $answer) {
            if ((int) $answer->getIid() === $answerId) {
                return $answer;
            }
        }

        return null;
    }

    /**
     * @param array<int, TrackEAttempt> $rows
     *
     * @return array<string, mixed>
     */
    private function normalizeHotspotAnswer(CQuizQuestion $question, array $rows, array $visibility, bool $showQuestionCorrection): array
    {
        $row = $rows[0] ?? null;
        $showStudentAnswers = true === ($visibility['showStudentAnswers'] ?? true);
        $showExpectedAnswers = $showQuestionCorrection && true === ($visibility['showExpectedAnswers'] ?? false);
        $studentPoints = $showStudentAnswers && $row instanceof TrackEAttempt ? $this->parseHotspotCoordinates((string) $row->getAnswer()) : [];
        $zones = [];
        $isDelineation = self::HOTSPOT_DELINEATION === (int) $question->getType();
        $allowedHotspotTypes = $isDelineation ? ['delineation', 'oar'] : ['square', 'circle', 'poly'];

        if ($showExpectedAnswers) {
            foreach ($this->getOrderedAnswers($question) as $answer) {
                $hotspotType = (string) ($answer->getHotspotType() ?: ($isDelineation ? 'delineation' : 'square'));
                if (!\in_array($hotspotType, $allowedHotspotTypes, true)) {
                    continue;
                }

                $coordinates = (string) ($answer->getHotspotCoordinates() ?: '');
                $zones[] = [
                    'id' => (int) $answer->getIid(),
                    'answer' => $answer->getAnswer(),
                    'comment' => (string) $answer->getComment(),
                    'score' => (float) $answer->getPonderation(),
                    'position' => (int) $answer->getPosition(),
                    'hotspotType' => $hotspotType,
                    'coordinates' => $coordinates,
                    'points' => $this->parseHotspotCoordinates($coordinates),
                ];
            }
        }

        return [
            'kind' => 'hotspot',
            'imageUrl' => $this->getHotspotImageUrl($question),
            'imageName' => $this->getHotspotImageName($question),
            'studentPoints' => $studentPoints,
            'zones' => $zones,
            'combination' => 26 === (int) $question->getType(),
            'delineation' => $isDelineation,
        ];
    }

    /**
     * @return array<int, array{x: float, y: float, label: int, answerId?: int}>
     */
    private function parseHotspotCoordinates(string $value): array
    {
        $points = [];
        foreach (explode('|', $value) as $index => $coordinate) {
            $answerId = 0;
            $coordinateValue = trim($coordinate);
            if (str_contains($coordinateValue, ':')) {
                [$answerIdValue, $coordinateValue] = explode(':', $coordinateValue, 2);
                $answerId = (int) $answerIdValue;
            }

            $parts = array_map('trim', explode(';', $coordinateValue));
            if (2 > \count($parts) || !is_numeric($parts[0]) || !is_numeric($parts[1])) {
                continue;
            }

            $point = [
                'x' => (float) $parts[0],
                'y' => (float) $parts[1],
                'label' => $index + 1,
            ];
            if (0 < $answerId) {
                $point['answerId'] = $answerId;
            }

            $points[] = $point;
        }

        return $points;
    }

    private function getHotspotImageUrl(CQuizQuestion $question): string
    {
        try {
            return $this->resourceNodeRepository->getResourceFileUrl($question->getResourceNode());
        } catch (\Throwable) {
            return '';
        }
    }

    private function getHotspotImageName(CQuizQuestion $question): string
    {
        $resourceNode = $question->getResourceNode();
        if (!$resourceNode instanceof ResourceNode) {
            return '';
        }

        $resourceFile = $resourceNode->getResourceFiles()->first();

        return $resourceFile instanceof ResourceFile ? (string) $resourceFile->getOriginalName() : (string) $resourceNode->getTitle();
    }

    /**
     * @param array<int, TrackEAttempt> $rows
     * @param array<string, mixed>      $visibility
     *
     * @return array<string, mixed>
     */
    private function normalizeFreeAnswer(array $rows, array $visibility): array
    {
        $row = $rows[0] ?? null;
        if (!$row instanceof TrackEAttempt) {
            return [
                'kind' => 'free_answer',
                'studentAnswer' => '',
                'teacherComment' => null,
                'marks' => 0.0,
            ];
        }

        $showStudentAnswers = true === ($visibility['showStudentAnswers'] ?? true);

        return [
            'kind' => 'free_answer',
            'studentAnswer' => $showStudentAnswers ? $row->getAnswer() : '',
            'teacherComment' => true === ($visibility['showFeedback'] ?? false) && '' !== $row->getTeacherComment() ? $row->getTeacherComment() : null,
            'marks' => (float) $row->getMarks(),
            'showStudentAnswers' => $showStudentAnswers,
        ];
    }

    /**
     * @param array<int, TrackEAttempt> $rows
     * @param array<string, mixed>      $visibility
     *
     * @return array<string, mixed>
     */
    private function normalizeAnnotationAnswer(CQuizQuestion $question, array $rows, array $visibility): array
    {
        $row = $rows[0] ?? null;

        $showStudentAnswers = true === ($visibility['showStudentAnswers'] ?? true);
        $annotationAnswer = $showStudentAnswers && $row instanceof TrackEAttempt
            ? $this->parseAnnotationAnswer((string) $row->getAnswer())
            : ['paths' => [], 'texts' => []];

        return [
            'kind' => 'annotation',
            'imageUrl' => $this->getHotspotImageUrl($question),
            'imageName' => $this->getHotspotImageName($question),
            'paths' => $annotationAnswer['paths'],
            'texts' => $annotationAnswer['texts'],
            'teacherComment' => $row instanceof TrackEAttempt && true === ($visibility['showFeedback'] ?? false) && '' !== $row->getTeacherComment() ? $row->getTeacherComment() : null,
            'marks' => $row instanceof TrackEAttempt ? (float) $row->getMarks() : 0.0,
            'showStudentAnswers' => $showStudentAnswers,
        ];
    }

    /**
     * @return array{paths: array<int, array{points: array<int, array{x: float, y: float}>}>, texts: array<int, array{text: string, x: float, y: float}>}
     */
    private function parseAnnotationAnswer(string $value): array
    {
        $result = [
            'paths' => [],
            'texts' => [],
        ];

        foreach (explode('|', $value) as $item) {
            $item = trim($item);
            if ('' === $item) {
                continue;
            }

            $parts = explode(')(', $item);
            $type = array_shift($parts);
            if ('P' === $type) {
                $points = [];
                foreach ($parts as $point) {
                    $decoded = $this->decodeAnnotationPoint($point);
                    if (null !== $decoded) {
                        $points[] = $decoded;
                    }
                }

                if (2 <= \count($points)) {
                    $result['paths'][] = ['points' => $points];
                }
                continue;
            }

            if ('T' === $type && 2 <= \count($parts)) {
                $text = trim((string) array_shift($parts));
                $decoded = $this->decodeAnnotationPoint((string) ($parts[0] ?? ''));
                if ('' !== $text && null !== $decoded) {
                    $result['texts'][] = [
                        'text' => $text,
                        'x' => $decoded['x'],
                        'y' => $decoded['y'],
                    ];
                }
            }
        }

        return $result;
    }

    /**
     * @return array{x: float, y: float}|null
     */
    private function decodeAnnotationPoint(string $value): ?array
    {
        $parts = array_map('trim', explode(';', $value));
        if (2 > \count($parts) || !is_numeric($parts[0]) || !is_numeric($parts[1])) {
            return null;
        }

        return [
            'x' => (float) $parts[0],
            'y' => (float) $parts[1],
        ];
    }

    /**
     * @param array<int, TrackEAttempt> $rows
     * @param array<string, mixed>      $visibility
     *
     * @return array<string, mixed>
     */
    private function normalizeUploadAnswer(array $rows, array $visibility): array
    {
        $row = $rows[0] ?? null;
        if (!$row instanceof TrackEAttempt) {
            return [
                'kind' => 'upload_answer',
                'files' => [],
                'teacherComment' => null,
                'marks' => 0.0,
            ];
        }

        $showStudentAnswers = true === ($visibility['showStudentAnswers'] ?? true);

        return [
            'kind' => 'upload_answer',
            'files' => $showStudentAnswers ? $this->normalizeAttemptFiles($row) : [],
            'teacherComment' => true === ($visibility['showFeedback'] ?? false) && '' !== $row->getTeacherComment() ? $row->getTeacherComment() : null,
            'marks' => (float) $row->getMarks(),
            'showStudentAnswers' => $showStudentAnswers,
        ];
    }

    /**
     * @param array<int, TrackEAttempt> $rows
     * @param array<string, mixed>      $visibility
     *
     * @return array<string, mixed>
     */
    private function normalizeOnlyofficeAnswer(array $rows, array $visibility): array
    {
        $row = $rows[0] ?? null;
        if (!$row instanceof TrackEAttempt) {
            return [
                'kind' => 'onlyoffice',
                'files' => [],
                'teacherComment' => null,
                'marks' => 0.0,
            ];
        }

        $showStudentAnswers = true === ($visibility['showStudentAnswers'] ?? true);

        return [
            'kind' => 'onlyoffice',
            'files' => $showStudentAnswers ? $this->normalizeAttemptFiles($row) : [],
            'teacherComment' => true === ($visibility['showFeedback'] ?? false) && '' !== $row->getTeacherComment() ? $row->getTeacherComment() : null,
            'marks' => (float) $row->getMarks(),
            'showStudentAnswers' => $showStudentAnswers,
        ];
    }

    /**
     * @param array<int, TrackEAttempt> $rows
     * @param array<string, mixed>      $visibility
     *
     * @return array<string, mixed>
     */
    private function normalizeOralExpressionAnswer(array $rows, array $visibility): array
    {
        $row = $rows[0] ?? null;
        if (!$row instanceof TrackEAttempt) {
            return [
                'kind' => 'oral_expression',
                'files' => [],
                'teacherComment' => null,
                'marks' => 0.0,
            ];
        }

        $showStudentAnswers = true === ($visibility['showStudentAnswers'] ?? true);

        return [
            'kind' => 'oral_expression',
            'files' => $showStudentAnswers ? $this->normalizeAttemptFiles($row, true) : [],
            'teacherComment' => true === ($visibility['showFeedback'] ?? false) && '' !== $row->getTeacherComment() ? $row->getTeacherComment() : null,
            'marks' => (float) $row->getMarks(),
            'showStudentAnswers' => $showStudentAnswers,
        ];
    }

    /**
     * @return array<int, array{id: int, name: string, size: int, mimeType: string, url: string}>
     */
    private function normalizeAttemptFiles(TrackEAttempt $row, bool $withInlineUrl = false): array
    {
        $files = [];
        foreach ($row->getAttemptFiles() as $attemptFile) {
            if (!$attemptFile instanceof AttemptFile) {
                continue;
            }

            $resourceNode = $attemptFile->getResourceNode();
            if (!$resourceNode instanceof ResourceNode) {
                continue;
            }

            $resourceFile = $resourceNode->getResourceFiles()->first();
            $url = $this->getAttemptFileDownloadUrl($row, $resourceNode);

            $files[] = [
                'id' => (int) $resourceNode->getId(),
                'name' => (string) ($resourceFile instanceof ResourceFile ? $resourceFile->getOriginalName() : $resourceNode->getTitle()),
                'size' => (int) ($resourceFile instanceof ResourceFile ? $resourceFile->getSize() : 0),
                'mimeType' => (string) ($resourceFile instanceof ResourceFile ? $resourceFile->getMimeType() : ''),
                'url' => $url,
                'inlineUrl' => $withInlineUrl ? $this->getAttemptFileDownloadUrl($row, $resourceNode, true) : $url,
            ];
        }

        return $files;
    }


    private function getAttemptFileDownloadUrl(TrackEAttempt $attemptRow, ResourceNode $resourceNode, bool $inline = false): string
    {
        $attempt = $attemptRow->getTrackEExercise();
        $quiz = $attempt->getQuiz();
        if (null === $quiz || null === $quiz->getIid() || null === $resourceNode->getId()) {
            return '';
        }

        $query = [
            'cid' => (int) $attempt->getCourse()->getId(),
        ];

        $session = $attempt->getSession();
        if (null !== $session) {
            $query['sid'] = (int) $session->getId();
        }

        $request = $this->requestStack->getCurrentRequest();
        if (null !== $request) {
            if ($inline) {
            $query['inline'] = 1;
        }

        foreach (['gid', 'origin', 'learnpath_id', 'learnpath_item_id', 'learnpath_item_view_id'] as $queryKey) {
                $value = $request->query->get($queryKey);
                if (null !== $value && '' !== (string) $value) {
                    $query[$queryKey] = (string) $value;
                }
            }
        }

        return sprintf(
            '/api/exercise/runtime/%d/attempt/%d/file/%d/download?%s',
            (int) $quiz->getIid(),
            (int) $attempt->getExeId(),
            (int) $resourceNode->getId(),
            http_build_query($query)
        );
    }

    /**
     * @return array<int, CQuizAnswer>
     */
    private function getOrderedAnswers(CQuizQuestion $question): array
    {
        $rows = $this->entityManager->createQueryBuilder()
            ->select('answer')
            ->from(CQuizAnswer::class, 'answer')
            ->andWhere('IDENTITY(answer.question) = :questionId')
            ->setParameter('questionId', (int) $question->getIid(), Types::INTEGER)
            ->orderBy('answer.position', 'ASC')
            ->addOrderBy('answer.iid', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        return array_values(array_filter($rows, static fn (mixed $answer): bool => $answer instanceof CQuizAnswer));
    }

    private function getFirstAnswer(CQuizQuestion $question): ?CQuizAnswer
    {
        $answers = $this->getOrderedAnswers($question);

        return $answers[0] ?? null;
    }

    /**
     * @return array<int, array{id: int, title: string, position: int}>
     */
    private function getQuestionOptions(CQuizQuestion $question): array
    {
        $options = [];
        foreach ($question->getOptions() as $option) {
            if (!$option instanceof CQuizQuestionOption || null === $option->getIid()) {
                continue;
            }

            $options[(int) $option->getIid()] = [
                'id' => (int) $option->getIid(),
                'title' => (string) $option->getTitle(),
                'position' => (int) $option->getPosition(),
            ];
        }

        uasort($options, static fn (array $a, array $b): int => ((int) $a['position']) <=> ((int) $b['position']));

        return $options;
    }

    /**
     * @param array<int, array{id: int, title: string, position: int}> $options
     */
    private function getOptionTitle(array $options, int $optionId): string
    {
        if (isset($options[$optionId])) {
            return (string) ($options[$optionId]['title'] ?? '');
        }

        foreach ($options as $option) {
            if ((int) ($option['position'] ?? 0) === $optionId) {
                return (string) ($option['title'] ?? '');
            }
        }

        return '';
    }

    /**
     * @param array<int, TrackEAttempt> $rows
     *
     * @return array<int, int>
     */
    private function getSavedAnswerIds(array $rows): array
    {
        $answerIds = [];
        foreach ($rows as $row) {
            $answerId = (int) $row->getAnswer();
            if (0 < $answerId && !\in_array($answerId, $answerIds, true)) {
                $answerIds[] = $answerId;
            }
        }

        return $answerIds;
    }

    /**
     * @param array<int, TrackEAttempt> $rows
     */
    private function getFirstSavedAnswerId(array $rows): int
    {
        $row = $rows[0] ?? null;

        return $row instanceof TrackEAttempt ? (int) $row->getAnswer() : 0;
    }

    /**
     * @param array<int, TrackEAttempt> $rows
     *
     * @return array<int, int>
     */
    private function getSavedTrueFalseChoices(array $rows): array
    {
        $choices = [];
        foreach ($rows as $row) {
            $parts = explode(':', (string) $row->getAnswer());
            $answerId = isset($parts[0]) ? (int) $parts[0] : 0;
            $optionId = isset($parts[1]) ? (int) $parts[1] : 0;
            if (0 < $answerId && 0 < $optionId) {
                $choices[$answerId] = $optionId;
            }
        }

        return $choices;
    }

    /**
     * @param array<int, TrackEAttempt> $rows
     *
     * @return array<int, int>
     */
    private function getSavedMatchingChoices(array $rows): array
    {
        $choices = [];
        foreach ($rows as $row) {
            $position = $row->getPosition();
            if (null === $position || 0 >= $position) {
                continue;
            }

            $choices[(int) $position] = (int) $row->getAnswer();
        }

        return $choices;
    }

    /**
     * @param array<int, TrackEAttempt> $rows
     *
     * @return array<int, array{answer: string, position: int|null, marks: float}>
     */
    private function normalizeRawRows(array $rows): array
    {
        $result = [];
        foreach ($rows as $row) {
            $result[] = [
                'answer' => $row->getAnswer(),
                'position' => $row->getPosition(),
                'marks' => (float) $row->getMarks(),
            ];
        }

        return $result;
    }

    /**
     * @return array{
     *     text: string,
     *     system_string: string,
     *     words: array<int, string>,
     *     words_with_bracket: array<int, string>,
     *     common_words: array<int, string>,
     *     student_answer: array<int, string>,
     *     student_score: array<int, string>,
     *     blank_separator_start: string,
     *     blank_separator_end: string
     * }
     */
    private function parseFillBlankAnswer(string $answer, bool $isStudentAnswer): array
    {
        $parts = [];
        if (1 === preg_match('/(.*)::(.*)$/s', $answer, $matches)) {
            $parts = [(string) ($matches[1] ?? ''), (string) ($matches[2] ?? '')];
        } else {
            $parts = [$answer, ''];
        }

        $systemString = $parts[1];
        $systemParts = explode('@', $systemString, 2);
        $details = explode(':', (string) ($systemParts[0] ?? ''));
        $separatorNumber = \count($details) >= 3 ? (int) ($details[2] ?? 0) : 0;
        [$start, $end] = $this->getFillBlankSeparators($separatorNumber);
        $startPattern = preg_quote($start, '/');
        $endPattern = preg_quote($end, '/');
        $wordMatches = [];
        preg_match_all('/'.$startPattern.'[^'.$endPattern.']*'.$endPattern.'/', $parts[0], $wordMatches);
        $wordsWithBracket = \is_array($wordMatches[0] ?? null) ? $wordMatches[0] : [];
        $words = [];
        foreach ($wordsWithBracket as $word) {
            $words[] = trim((string) $word, $start.$end);
        }

        $commonWordsString = preg_replace('/'.$startPattern.'[^'.$endPattern.']*'.$endPattern.'/', '::', $parts[0]);
        if (!\is_string($commonWordsString)) {
            $commonWordsString = '';
        }

        $studentAnswer = [];
        $studentScore = [];
        if ($isStudentAnswer) {
            $baseWords = [];
            $baseWordsWithBracket = [];
            $count = \count($words);
            for ($index = 0; $index < $count; ++$index) {
                $baseWordsWithBracket[] = $wordsWithBracket[$index] ?? '';
                $baseWords[] = $words[$index] ?? '';
                ++$index;
                $studentAnswer[] = $words[$index] ?? '';
                ++$index;
                $studentScore[] = $words[$index] ?? '0';
            }
            $words = $baseWords;
            $wordsWithBracket = $baseWordsWithBracket;
            $commonWordsString = preg_replace('/::::::/', '::', $commonWordsString) ?: '';
        }

        return [
            'text' => $parts[0],
            'system_string' => $systemString,
            'words' => $words,
            'words_with_bracket' => $wordsWithBracket,
            'common_words' => explode('::', $commonWordsString),
            'student_answer' => $studentAnswer,
            'student_score' => $studentScore,
            'blank_separator_start' => $start,
            'blank_separator_end' => $end,
        ];
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function getFillBlankSeparators(int $separator): array
    {
        return match ($separator) {
            1 => ['{', '}'],
            2 => ['(', ')'],
            3 => ['*', '*'],
            4 => ['#', '#'],
            5 => ['%', '%'],
            6 => ['$', '$'],
            default => ['[', ']'],
        };
    }

    private function isStructuralContentQuestion(CQuizQuestion $question): bool
    {
        return \in_array((int) $question->getType(), [15, 31], true);
    }

    private function requiresManualCorrection(CQuizQuestion $question): bool
    {
        return \in_array((int) $question->getType(), [5, 13, 20, 23], true);
    }

    private function getQuestionTypeLabel(int $type): string
    {
        return match ($type) {
            1 => 'Unique answer',
            2 => 'Multiple answer',
            3 => 'Fill in blanks',
            4 => 'Matching',
            5 => 'Open question',
            6 => 'Hotspot',
            9 => 'Exact Selection',
            10 => 'Unique answer with unknown',
            11 => 'Multiple answer true/false',
            12 => 'Multiple answer combination true/false',
            14 => 'Global multiple answer',
            17 => 'Unique answer with images',
            18 => 'Sequence ordering',
            19 => 'Matching draggable',
            20 => 'Annotation',
            21 => 'Reading comprehension',
            22 => 'Multiple answer true/false with degree of certainty',
            23 => 'Upload answer',
            24 => 'Matching combination',
            25 => 'Matching draggable combination',
            26 => 'Hotspot combination',
            27 => 'Fill in blanks combination',
            28 => 'Multiple answer dropdown combination',
            29 => 'Multiple answer dropdown',
            31 => 'Page break',
            default => 'Question',
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function getFinalActions(CQuiz $quiz, TrackEExercise $attempt, Course $course, ?Session $session, bool $isReviewMode): array
    {
        $currentUser = $this->security->getUser();
        $currentUserId = $currentUser instanceof User ? (int) $currentUser->getId() : 0;
        $attemptUserId = (int) $attempt->getUser()->getId();
        $isLearningPathAttempt = 0 < (int) $attempt->getOrigLpId()
            || 0 < (int) $attempt->getOrigLpItemId()
            || 0 < (int) $attempt->getOrigLpItemViewId();
        $isOwnAttempt = $currentUserId > 0 && $currentUserId === $attemptUserId;
        $showFinalActions = !$isReviewMode && !$isLearningPathAttempt && $isOwnAttempt;
        $attemptCount = $this->getAttemptCountForResultContext($quiz, $attempt, $course, $session);
        $maxAttempt = (int) $quiz->getMaxAttempt();
        $attemptLimitReached = 0 < $maxAttempt && $attemptCount >= $maxAttempt;
        $canTryAgain = $showFinalActions
            && $this->isExerciseOpenForRetry($quiz)
            && !$attemptLimitReached;

        return [
            'isLearningPathAttempt' => $isLearningPathAttempt,
            'isOwnAttempt' => $isOwnAttempt,
            'showFinalActions' => $showFinalActions,
            'canTryAgain' => $canTryAgain,
            'attemptCount' => $attemptCount,
            'maxAttempt' => $maxAttempt,
            'remainingAttempts' => 0 < $maxAttempt ? max(0, $maxAttempt - $attemptCount) : null,
        ];
    }

    private function getAttemptCountForResultContext(CQuiz $quiz, TrackEExercise $attempt, Course $course, ?Session $session): int
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('COUNT(resultAttempt.exeId)')
            ->from(TrackEExercise::class, 'resultAttempt')
            ->andWhere('IDENTITY(resultAttempt.quiz) = :exerciseId')
            ->andWhere('IDENTITY(resultAttempt.course) = :courseId')
            ->andWhere('IDENTITY(resultAttempt.user) = :userId')
            ->andWhere('(resultAttempt.status = :emptyStatus OR resultAttempt.status = :completedStatus)')
            ->andWhere('resultAttempt.origLpId = :learnpathId')
            ->andWhere('resultAttempt.origLpItemId = :learnpathItemId')
            ->andWhere('resultAttempt.origLpItemViewId = :learnpathItemViewId')
            ->setParameter('exerciseId', (int) $quiz->getIid(), Types::INTEGER)
            ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
            ->setParameter('userId', (int) $attempt->getUser()->getId(), Types::INTEGER)
            ->setParameter('emptyStatus', '')
            ->setParameter('completedStatus', self::STATUS_COMPLETED)
            ->setParameter('learnpathId', (int) $attempt->getOrigLpId(), Types::INTEGER)
            ->setParameter('learnpathItemId', (int) $attempt->getOrigLpItemId(), Types::INTEGER)
            ->setParameter('learnpathItemViewId', (int) $attempt->getOrigLpItemViewId(), Types::INTEGER)
        ;

        if (null !== $session) {
            $queryBuilder
                ->andWhere('IDENTITY(resultAttempt.session) = :sessionId')
                ->setParameter('sessionId', (int) $session->getId(), Types::INTEGER)
            ;
        } else {
            $queryBuilder->andWhere('resultAttempt.session IS NULL');
        }

        return (int) $queryBuilder->getQuery()->getSingleScalarResult();
    }

    private function isExerciseOpenForRetry(CQuiz $quiz): bool
    {
        $now = new DateTimeImmutable();
        $startTime = $quiz->getStartTime();
        if ($startTime instanceof DateTimeInterface && $startTime > $now) {
            return false;
        }

        $endTime = $quiz->getEndTime();
        if ($endTime instanceof DateTimeInterface && $endTime < $now) {
            return false;
        }

        return true;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getRanking(CQuiz $quiz, Course $course, ?Session $session, TrackEExercise $currentAttempt): array
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('attempt')
            ->from(TrackEExercise::class, 'attempt')
            ->andWhere('IDENTITY(attempt.quiz) = :exerciseId')
            ->andWhere('IDENTITY(attempt.course) = :courseId')
            ->andWhere('attempt.status = :status')
            ->setParameter('exerciseId', (int) $quiz->getIid(), Types::INTEGER)
            ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
            ->setParameter('status', self::STATUS_COMPLETED)
            ->orderBy('attempt.score', 'DESC')
            ->addOrderBy('attempt.exeDate', 'ASC')
        ;

        if (null !== $session) {
            $queryBuilder
                ->andWhere('IDENTITY(attempt.session) = :sessionId')
                ->setParameter('sessionId', (int) $session->getId(), Types::INTEGER)
            ;
        } else {
            $queryBuilder->andWhere('attempt.session IS NULL');
        }

        $rows = $queryBuilder->getQuery()->getResult();
        $bestAttemptsByUser = [];
        foreach ($rows as $row) {
            if (!$row instanceof TrackEExercise) {
                continue;
            }

            $userId = (int) $row->getUser()->getId();
            if (0 >= $userId || isset($bestAttemptsByUser[$userId])) {
                continue;
            }

            $bestAttemptsByUser[$userId] = $row;
        }

        $ranking = [];
        $position = 0;
        $lastScore = null;
        foreach (array_values($bestAttemptsByUser) as $index => $row) {
            $score = (float) $row->getScore();
            if (null === $lastScore || $score < $lastScore) {
                $position = $index + 1;
            }
            $lastScore = $score;

            $ranking[] = [
                'position' => $position,
                'userId' => (int) $row->getUser()->getId(),
                'user' => $this->getUserDisplayName($row->getUser()),
                'score' => $score,
                'maxScore' => (float) $row->getMaxScore(),
                'date' => $row->getExeDate()->format(DateTimeInterface::ATOM),
                'currentUser' => (int) $row->getUser()->getId() === (int) $currentAttempt->getUser()->getId(),
            ];
        }

        return $ranking;
    }

    private function getUserDisplayName(User $user): string
    {
        $username = method_exists($user, 'getUsername') ? trim((string) $user->getUsername()) : '';
        if ('' !== $username) {
            return $username;
        }

        return 'User #'.(int) $user->getId();
    }

}
