<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Exercise;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\Exercise\ExerciseRuntimeAnswer;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\ExtraFieldValues;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\TrackEAttempt;
use Chamilo\CoreBundle\Entity\TrackEExercise;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CLpItem;
use Chamilo\CourseBundle\Entity\CLpItemView;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Entity\CQuizAnswer;
use Chamilo\CourseBundle\Entity\CQuizQuestion;
use Chamilo\CourseBundle\Entity\CQuizQuestionCategory;
use Chamilo\CourseBundle\Entity\CQuizQuestionOption;
use Chamilo\CourseBundle\Entity\CQuizRelQuestion;
use Chamilo\CourseBundle\Repository\CQuizRepository;
use DateTime;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Saves draft answers for simple Vue runtime question types.
 *
 * This processor intentionally writes legacy-compatible track_e_attempt rows only.
 * Final scoring, status changes and results remain delegated to the legacy runtime.
 *
 * @implements ProcessorInterface<ExerciseRuntimeAnswer, ExerciseRuntimeAnswer>
 */
final readonly class ExerciseRuntimeAnswerProcessor implements ProcessorInterface
{
    private const VISIBILITY_PUBLISHED = 2;
    private const LP_ITEM_TYPE_QUIZ = 'quiz';
    private const STATUS_INCOMPLETE = 'incomplete';
    private const FEEDBACK_TYPE_DIRECT = 1;
    private const FEEDBACK_TYPE_POPUP = 3;
    private const UNIQUE_TYPES = [1, 10, 17, 21];
    private const MULTIPLE_TYPES = [2, 9, 14];
    private const TRUE_FALSE_TYPES = [11, 12];
    private const TRUE_FALSE_DEGREE_CERTAINTY_TYPES = [22];
    private const FILL_BLANK_TYPES = [3, 27];
    private const MATCHING_TYPES = [4, 19, 24, 25];
    private const DRAGGABLE_TYPES = [18];
    private const DROPDOWN_TYPES = [28, 29];
    private const CALCULATED_TYPES = [16];
    private const FREE_ANSWER_TYPES = [5];
    private const ANNOTATION_TYPES = [20];
    private const HOTSPOT_DELINEATION = 8;
    private const HOTSPOT_TYPES = [6, self::HOTSPOT_DELINEATION, 26];

    public function __construct(
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager,
        private CQuizRepository $quizRepository,
        private Security $security,
        private SettingsManager $settingsManager,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ExerciseRuntimeAnswer
    {
        if (!$data instanceof ExerciseRuntimeAnswer) {
            throw new BadRequestHttpException('Invalid exercise runtime answer payload.');
        }

        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        if (!$this->canSaveDraftAnswers()) {
            throw new AccessDeniedHttpException('You are not allowed to save answers for this exercise.');
        }

        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedHttpException('A valid authenticated user is required.');
        }

        $course = $this->getCourse($request);
        $session = $this->getSession($request);
        $exerciseId = isset($uriVariables['exerciseId']) ? (int) $uriVariables['exerciseId'] : (int) ($data->exerciseId ?? 0);
        $attemptId = isset($uriVariables['attemptId']) ? (int) $uriVariables['attemptId'] : (int) ($data->attemptId ?? 0);
        $questionId = (int) ($data->questionId ?? 0);

        if (0 >= $exerciseId || 0 >= $attemptId || 0 >= $questionId) {
            throw new BadRequestHttpException('A valid exercise, attempt and question are required.');
        }

        $quiz = $this->getExerciseFromCurrentContext($exerciseId, $course, $session, $this->canManageExercises());
        $attempt = $this->getIncompleteAttempt($attemptId, $quiz, $course, $session, $user);
        $question = $this->getQuestionFromExercise($questionId, $quiz);

        if (!$question instanceof CQuizQuestion) {
            throw new NotFoundHttpException('The requested question was not found in this exercise.');
        }

        if (!$this->questionBelongsToAttempt($questionId, $attempt)) {
            throw new AccessDeniedHttpException('The requested question does not belong to this attempt.');
        }

        $navigationAction = strtolower(trim((string) $data->navigationAction));
        $this->assertAttemptAcceptsAnswer($attempt, $quiz, $questionId, $navigationAction);

        if (true === $data->reviewLaterOnly) {
            if (null !== $data->reviewLater) {
                $this->syncReviewQuestion($attempt, $questionId, true === $data->reviewLater);
            }

            $this->entityManager->flush();

            $response = $this->createResponse($exerciseId, $attemptId, $questionId);
            $response->message = 'Review list updated';

            return $response;
        }

        $rows = $this->buildDraftRows($question, $data->answer, max(0, (int) $data->secondsSpent));
        $feedback = $this->buildFeedback($quiz, $question, $rows);
        $marks = [] !== $feedback ? (float) ($feedback['score'] ?? 0.0) : 0.0;
        $this->deletePreviousDraftRows($attempt, $questionId);

        foreach ($rows as $row) {
            $attemptRow = (new TrackEAttempt())
                ->setTrackEExercise($attempt)
                ->setUser($user)
                ->setQuestionId($questionId)
                ->setAnswer($row['answer'])
                ->setTeacherComment('')
                ->setMarks($marks)
                ->setPosition($row['position'])
                ->setTms(new DateTime())
                ->setSecondsSpent($row['secondsSpent'])
            ;
            $this->entityManager->persist($attemptRow);
        }

        if (null !== $data->reviewLater) {
            $this->syncReviewQuestion($attempt, $questionId, true === $data->reviewLater);
        }

        $this->blockCategoryIfNeeded($attempt, $quiz, $question, $navigationAction);
        $this->lockPreventBackwardsStepIfNeeded($attempt, $quiz, $questionId, $navigationAction);

        $this->entityManager->flush();

        $response = $this->createResponse($exerciseId, $attemptId, $questionId);
        $response->message = [] === $rows ? 'Draft answer cleared' : 'Draft answer saved';
        $response->feedback = $feedback;

        return $response;
    }


    private function createResponse(int $exerciseId, int $attemptId, int $questionId): ExerciseRuntimeAnswer
    {
        $response = new ExerciseRuntimeAnswer();
        $response->exerciseId = $exerciseId;
        $response->attemptId = $attemptId;
        $response->questionId = $questionId;
        $response->success = true;
        $response->savedAnswer = $this->getSavedAnswerRows($attemptId, $questionId);
        $response->answeredQuestionIds = $this->getAnsweredQuestionIds($attemptId);
        $response->reviewQuestionIds = $this->getReviewQuestionIds($attemptId);
        $response->answeredCount = \count($response->answeredQuestionIds);
        $response->canFinish = false;

        return $response;
    }

    private function syncReviewQuestion(TrackEExercise $attempt, int $questionId, bool $reviewLater): void
    {
        $questionIds = $this->parseQuestionIds((string) $attempt->getDataTracking());
        if (!\in_array($questionId, $questionIds, true)) {
            return;
        }

        $reviewQuestionIds = $this->parseQuestionIds((string) $attempt->getQuestionsToCheck());
        $reviewQuestionMap = array_fill_keys($reviewQuestionIds, true);

        if ($reviewLater) {
            $reviewQuestionMap[$questionId] = true;
        } else {
            unset($reviewQuestionMap[$questionId]);
        }

        $orderedReviewQuestionIds = [];
        foreach ($questionIds as $orderedQuestionId) {
            if (isset($reviewQuestionMap[$orderedQuestionId])) {
                $orderedReviewQuestionIds[] = $orderedQuestionId;
            }
        }

        $attempt->setQuestionsToCheck(implode(',', $orderedReviewQuestionIds));
    }

    private function assertAttemptAcceptsAnswer(TrackEExercise $attempt, CQuiz $quiz, int $questionId, string $navigationAction): void
    {
        if ($this->isAttemptExpired($attempt)) {
            throw new AccessDeniedHttpException('The time for this exercise has expired.');
        }

        if (!$this->isPreventBackwardsEnabled($quiz)) {
            return;
        }

        $questionIndex = $this->getAttemptQuestionIndex($attempt, $questionId);
        if (0 > $questionIndex) {
            return;
        }

        if ($questionIndex < $attempt->getStepsCounter()) {
            throw new AccessDeniedHttpException('You cannot update a previous question in this exercise.');
        }
    }

    private function isAttemptExpired(TrackEExercise $attempt): bool
    {
        $expiredAt = $attempt->getExpiredTimeControl();

        return $expiredAt instanceof DateTime && $expiredAt <= new DateTime();
    }

    private function isPreventBackwardsEnabled(CQuiz $quiz): bool
    {
        return 1 === (int) $quiz->getPreventBackwards();
    }

    private function lockPreventBackwardsStepIfNeeded(TrackEExercise $attempt, CQuiz $quiz, int $questionId, string $navigationAction): void
    {
        if (!$this->isPreventBackwardsEnabled($quiz) || !\in_array($navigationAction, ['next', 'finish'], true)) {
            return;
        }

        $questionIndex = $this->getAttemptQuestionIndex($attempt, $questionId);
        if (0 > $questionIndex) {
            return;
        }

        $attempt->setStepsCounter(max($attempt->getStepsCounter(), $questionIndex + 1));
    }

    private function getAttemptQuestionIndex(TrackEExercise $attempt, int $questionId): int
    {
        $questionIds = $this->parseQuestionIds((string) $attempt->getDataTracking());
        foreach ($questionIds as $index => $attemptQuestionId) {
            if ($questionId === $attemptQuestionId) {
                return (int) $index;
            }
        }

        return -1;
    }

    private function blockCategoryIfNeeded(TrackEExercise $attempt, CQuiz $quiz, CQuizQuestion $question, string $navigationAction): void
    {
        if (!\in_array($navigationAction, ['next', 'finish'], true) || !$this->isBlockCategoryRuntimeEnabled($quiz)) {
            return;
        }

        $categoryId = $this->getPrimaryCategoryId($question);
        if (!$this->isLastQuestionInCategory($attempt, $question, $categoryId)) {
            return;
        }

        $blockedCategories = $this->parseBlockedCategories((string) $attempt->getBlockedCategories());
        if (\in_array($categoryId, $blockedCategories, true)) {
            return;
        }

        $blockedCategories[] = $categoryId;
        $attempt->setBlockedCategories(implode(',', $blockedCategories));
    }

    private function isBlockCategoryRuntimeEnabled(CQuiz $quiz): bool
    {
        if ('true' !== $this->settingsManager->getSetting('exercise.block_category_questions', true)) {
            return false;
        }

        $exerciseId = (int) ($quiz->getIid() ?? 0);
        if (0 >= $exerciseId) {
            return false;
        }

        $row = $this->entityManager->createQueryBuilder()
            ->select('value')
            ->from(ExtraFieldValues::class, 'value')
            ->innerJoin('value.field', 'field')
            ->andWhere('value.itemId = :itemId')
            ->andWhere('field.itemType = :itemType')
            ->andWhere('field.variable = :variable')
            ->setParameter('itemId', $exerciseId, Types::INTEGER)
            ->setParameter('itemType', ExtraField::EXERCISE_FIELD_TYPE, Types::INTEGER)
            ->setParameter('variable', 'block_category', Types::STRING)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        if (!$row instanceof ExtraFieldValues) {
            return false;
        }

        return \in_array(strtolower((string) ($row->getFieldValue() ?? '')), ['1', 'true', 'yes', 'on'], true);
    }

    private function isLastQuestionInCategory(TrackEExercise $attempt, CQuizQuestion $question, int $categoryId): bool
    {
        $questionId = (int) ($question->getIid() ?? 0);
        if (0 >= $questionId) {
            return false;
        }

        $lastQuestionId = 0;
        foreach ($this->parseQuestionIds((string) $attempt->getDataTracking()) as $attemptQuestionId) {
            $attemptQuestion = $this->entityManager->getRepository(CQuizQuestion::class)->find($attemptQuestionId);
            if (!$attemptQuestion instanceof CQuizQuestion) {
                continue;
            }

            if ($this->getPrimaryCategoryId($attemptQuestion) === $categoryId) {
                $lastQuestionId = (int) ($attemptQuestion->getIid() ?? 0);
            }
        }

        return $questionId === $lastQuestionId;
    }

    private function getPrimaryCategoryId(CQuizQuestion $question): int
    {
        foreach ($question->getCategories() as $category) {
            if ($category instanceof CQuizQuestionCategory && null !== $category->getIid()) {
                return (int) $category->getIid();
            }
        }

        return 0;
    }

    /**
     * @return array<int, int>
     */
    private function parseBlockedCategories(string $value): array
    {
        if ('' === trim($value)) {
            return [];
        }

        return array_values(array_unique(array_filter(
            array_map(static fn (string $id): int => (int) trim($id), explode(',', $value)),
            static fn (int $id): bool => 0 <= $id
        )));
    }

    private function canSaveDraftAnswers(): bool
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
            $now = new DateTimeImmutable();
            if (self::VISIBILITY_PUBLISHED !== $visibility && !$this->isVisibleThroughLearnpath($quiz, $course, $session)) {
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

    private function getIncompleteAttempt(int $attemptId, CQuiz $quiz, Course $course, ?Session $session, User $user): TrackEExercise
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('attempt')
            ->from(TrackEExercise::class, 'attempt')
            ->andWhere('attempt.exeId = :attemptId')
            ->andWhere('IDENTITY(attempt.quiz) = :exerciseId')
            ->andWhere('IDENTITY(attempt.course) = :courseId')
            ->andWhere('IDENTITY(attempt.user) = :userId')
            ->andWhere('attempt.status = :status')
            ->setParameter('attemptId', $attemptId, Types::INTEGER)
            ->setParameter('exerciseId', (int) $quiz->getIid(), Types::INTEGER)
            ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
            ->setParameter('userId', (int) $user->getId(), Types::INTEGER)
            ->setParameter('status', self::STATUS_INCOMPLETE)
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
        if (!$attempt instanceof TrackEExercise) {
            throw new NotFoundHttpException('The requested incomplete attempt was not found.');
        }

        return $attempt;
    }

    private function getQuestionFromExercise(int $questionId, CQuiz $quiz): ?CQuizQuestion
    {
        $relQuestion = $this->entityManager->createQueryBuilder()
            ->select('relQuestion')
            ->addSelect('question')
            ->from(CQuizRelQuestion::class, 'relQuestion')
            ->innerJoin('relQuestion.question', 'question')
            ->andWhere('IDENTITY(relQuestion.quiz) = :exerciseId')
            ->andWhere('IDENTITY(relQuestion.question) = :questionId')
            ->setParameter('exerciseId', (int) $quiz->getIid(), Types::INTEGER)
            ->setParameter('questionId', $questionId, Types::INTEGER)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        if (!$relQuestion instanceof CQuizRelQuestion) {
            return null;
        }

        return $relQuestion->getQuestion();
    }

    private function questionBelongsToAttempt(int $questionId, TrackEExercise $attempt): bool
    {
        $attemptQuestionIds = $this->parseQuestionIds((string) $attempt->getDataTracking());
        if ([] === $attemptQuestionIds) {
            return true;
        }

        return \in_array($questionId, $attemptQuestionIds, true);
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
     * @return array<int, array{answer: string, position: int, secondsSpent: int}>
     */
    private function buildDraftRows(CQuizQuestion $question, mixed $answer, int $secondsSpent): array
    {
        $type = (int) $question->getType();
        $payload = $this->normalizePayload($answer);

        if (\in_array($type, self::UNIQUE_TYPES, true)) {
            return $this->buildUniqueRows($payload, $secondsSpent);
        }

        if (\in_array($type, self::MULTIPLE_TYPES, true)) {
            return $this->buildMultipleRows($payload, $secondsSpent);
        }

        if (\in_array($type, self::TRUE_FALSE_TYPES, true)) {
            return $this->buildTrueFalseRows($payload, $secondsSpent);
        }

        if (\in_array($type, self::TRUE_FALSE_DEGREE_CERTAINTY_TYPES, true)) {
            return $this->buildTrueFalseDegreeCertaintyRows($payload, $secondsSpent);
        }

        if (\in_array($type, self::FILL_BLANK_TYPES, true)) {
            return $this->buildFillBlankRows($question, $payload, $secondsSpent);
        }

        if (\in_array($type, self::MATCHING_TYPES, true)) {
            return $this->buildMatchingRows($payload, $secondsSpent);
        }

        if (\in_array($type, self::DRAGGABLE_TYPES, true)) {
            return $this->buildDraggableRows($question, $payload, $secondsSpent);
        }

        if (\in_array($type, self::DROPDOWN_TYPES, true)) {
            return $this->buildDropdownRows($payload, $secondsSpent);
        }

        if (\in_array($type, self::CALCULATED_TYPES, true)) {
            return $this->buildCalculatedRows($question, $payload, $secondsSpent);
        }

        if (\in_array($type, self::FREE_ANSWER_TYPES, true)) {
            return $this->buildFreeAnswerRows($payload, $secondsSpent);
        }

        if (\in_array($type, self::ANNOTATION_TYPES, true)) {
            return $this->buildAnnotationRows($payload, $secondsSpent);
        }

        if (\in_array($type, self::HOTSPOT_TYPES, true)) {
            return $this->buildHotspotRows($payload, $secondsSpent);
        }

        throw new BadRequestHttpException('This question type is not supported by the draft answer processor yet.');
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizePayload(mixed $answer): array
    {
        if (\is_array($answer)) {
            return $answer;
        }

        if (null === $answer) {
            return [];
        }

        return ['value' => $answer];
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<int, array{answer: string, position: int, secondsSpent: int}>
     */
    private function buildUniqueRows(array $payload, int $secondsSpent): array
    {
        $choiceId = $this->toPositiveInt($payload['choice'] ?? $payload['value'] ?? null);

        return 0 < $choiceId ? [['answer' => (string) $choiceId, 'position' => 0, 'secondsSpent' => $secondsSpent]] : [];
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<int, array{answer: string, position: int, secondsSpent: int}>
     */
    private function buildMultipleRows(array $payload, int $secondsSpent): array
    {
        $choices = $this->toPositiveIntList($payload['choices'] ?? $payload['value'] ?? []);
        $rows = [];
        foreach ($choices as $position => $choiceId) {
            $rows[] = ['answer' => (string) $choiceId, 'position' => $position, 'secondsSpent' => $secondsSpent];
        }

        return $rows;
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<int, array{answer: string, position: int, secondsSpent: int}>
     */
    private function buildTrueFalseRows(array $payload, int $secondsSpent): array
    {
        $values = $payload['trueFalse'] ?? $payload['value'] ?? [];
        if (!\is_array($values)) {
            return [];
        }

        $rows = [];
        $position = 0;
        foreach ($values as $answerId => $optionValue) {
            $safeAnswerId = $this->toPositiveInt($answerId);
            $safeOptionValue = $this->toPositiveInt($optionValue);
            if (0 >= $safeAnswerId || 0 >= $safeOptionValue) {
                continue;
            }

            $rows[] = [
                'answer' => $safeAnswerId.':'.$safeOptionValue,
                'position' => $position,
                'secondsSpent' => $secondsSpent,
            ];
            ++$position;
        }

        return $rows;
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<int, array{answer: string, position: int, secondsSpent: int}>
     */
    private function buildTrueFalseDegreeCertaintyRows(array $payload, int $secondsSpent): array
    {
        $values = $payload['trueFalse'] ?? [];
        $certaintyValues = $payload['degreeCertainty'] ?? [];
        if (!\is_array($values)) {
            return [];
        }
        if (!\is_array($certaintyValues)) {
            $certaintyValues = [];
        }

        $rows = [];
        $position = 0;
        foreach ($values as $answerId => $optionValue) {
            $safeAnswerId = $this->toPositiveInt($answerId);
            $safeOptionValue = $this->toPositiveInt($optionValue);
            if (0 >= $safeAnswerId || 0 >= $safeOptionValue) {
                continue;
            }

            $safeCertaintyValue = $this->toPositiveInt($certaintyValues[$answerId] ?? 0);
            $rows[] = [
                'answer' => $safeAnswerId.':'.$safeOptionValue.':'.$safeCertaintyValue,
                'position' => $position,
                'secondsSpent' => $secondsSpent,
            ];
            ++$position;
        }

        return $rows;
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<int, array{answer: string, position: int, secondsSpent: int}>
     */
    private function buildFillBlankRows(CQuizQuestion $question, array $payload, int $secondsSpent): array
    {
        $answer = $this->getFirstAnswer($question);
        if (!$answer instanceof CQuizAnswer) {
            return [];
        }

        $blankValues = $payload['blanks'] ?? $payload['value'] ?? [];
        if (!\is_array($blankValues)) {
            return [];
        }

        $encodedAnswer = $this->buildStudentFillBlankAnswer($answer->getAnswer(), $blankValues);
        if ('' === $encodedAnswer) {
            return [];
        }

        return [['answer' => $encodedAnswer, 'position' => 0, 'secondsSpent' => $secondsSpent]];
    }

    /**
     * @param array<string|int, mixed> $blankValues
     */
    private function buildStudentFillBlankAnswer(string $teacherAnswer, array $blankValues): string
    {
        $parts = explode('::', $teacherAnswer, 2);
        $text = (string) ($parts[0] ?? '');
        $systemString = (string) ($parts[1] ?? '');
        $separator = $this->getFillBlankSeparator($systemString);
        [$start, $end] = $this->getFillBlankSeparators($separator);
        $pattern = '/'.preg_quote($start, '/').'(.*?)'.preg_quote($end, '/').'/s';
        $blankIndex = 0;

        $studentText = preg_replace_callback(
            $pattern,
            function (array $matches) use (&$blankIndex, $blankValues, $start, $end): string {
                ++$blankIndex;
                $correctAnswer = (string) ($matches[1] ?? '');
                $studentAnswer = $this->escapeFillBlankValue($blankValues[$blankIndex] ?? '');

                return $start.$correctAnswer.$end.$start.$studentAnswer.$end.$start.'0'.$end;
            },
            $text,
        );

        if (!\is_string($studentText)) {
            return '';
        }

        return $studentText.'::'.$systemString;
    }

    private function getFillBlankSeparator(string $systemString): int
    {
        $systemParts = explode('@', $systemString, 2);
        $details = explode(':', (string) ($systemParts[0] ?? ''));

        return \count($details) >= 3 ? max(0, (int) ($details[2] ?? 0)) : 0;
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

    private function escapeFillBlankValue(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<int, array{answer: string, position: int, secondsSpent: int}>
     */
    private function buildMatchingRows(array $payload, int $secondsSpent): array
    {
        $values = $payload['matching'] ?? $payload['value'] ?? [];
        if (!\is_array($values)) {
            return [];
        }

        $rows = [];
        foreach ($values as $promptId => $optionId) {
            $safePromptId = $this->toPositiveInt($promptId);
            $safeOptionId = $this->toPositiveInt($optionId);
            if (0 >= $safePromptId || 0 >= $safeOptionId) {
                continue;
            }

            $rows[] = [
                'answer' => (string) $safeOptionId,
                'position' => $safePromptId,
                'secondsSpent' => $secondsSpent,
            ];
        }

        return $rows;
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<int, array{answer: string, position: int, secondsSpent: int}>
     */
    private function buildDraggableRows(CQuizQuestion $question, array $payload, int $secondsSpent): array
    {
        $order = $payload['order'] ?? $payload['value'] ?? [];
        if (!\is_array($order)) {
            return [];
        }

        $validAnswerIds = [];
        foreach ($this->getOrderedAnswers($question) as $answer) {
            if (0 < (int) ($answer->getCorrect() ?? 0)) {
                $validAnswerIds[(int) $answer->getIid()] = true;
            }
        }

        $rows = [];
        $position = 1;
        $usedAnswerIds = [];
        foreach ($order as $answerId) {
            $safeAnswerId = $this->toPositiveInt($answerId);
            if (0 >= $safeAnswerId || !isset($validAnswerIds[$safeAnswerId]) || isset($usedAnswerIds[$safeAnswerId])) {
                continue;
            }

            $rows[] = [
                'answer' => (string) $position,
                'position' => $safeAnswerId,
                'secondsSpent' => $secondsSpent,
            ];
            $usedAnswerIds[$safeAnswerId] = true;
            ++$position;
        }

        return $rows;
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<int, array{answer: string, position: int, secondsSpent: int}>
     */
    private function buildDropdownRows(array $payload, int $secondsSpent): array
    {
        if (isset($payload['choices'])) {
            return $this->buildMultipleRows(['choices' => $payload['choices']], $secondsSpent);
        }

        $choiceId = $this->toPositiveInt($payload['dropdown'] ?? $payload['value'] ?? null);

        return 0 < $choiceId ? [['answer' => (string) $choiceId, 'position' => 0, 'secondsSpent' => $secondsSpent]] : [];
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<int, array{answer: string, position: int, secondsSpent: int}>
     */
    private function buildCalculatedRows(CQuizQuestion $question, array $payload, int $secondsSpent): array
    {
        $studentAnswer = trim((string) ($payload['calculated'] ?? $payload['value'] ?? ''));
        if ('' === $studentAnswer) {
            return [];
        }

        $answerId = $this->toPositiveInt($payload['answerId'] ?? 0);
        if (0 >= $answerId) {
            $firstAnswer = $this->getFirstAnswer($question);
            $answerId = $firstAnswer instanceof CQuizAnswer ? (int) $firstAnswer->getIid() : 0;
        }

        if (0 >= $answerId) {
            return [];
        }

        return [[
            'answer' => $answerId.':'.$studentAnswer,
            'position' => 0,
            'secondsSpent' => $secondsSpent,
        ]];
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<int, array{answer: string, position: int, secondsSpent: int}>
     */
    private function buildFreeAnswerRows(array $payload, int $secondsSpent): array
    {
        $text = (string) ($payload['text'] ?? $payload['value'] ?? '');

        return '' !== $text ? [['answer' => $text, 'position' => 0, 'secondsSpent' => $secondsSpent]] : [];
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<int, array{answer: string, position: int, secondsSpent: int}>
     */
    private function buildAnnotationRows(array $payload, int $secondsSpent): array
    {
        $encodedItems = [];

        $paths = $payload['paths'] ?? $payload['annotation']['paths'] ?? [];
        if (\is_array($paths)) {
            foreach ($paths as $path) {
                if (!\is_array($path)) {
                    continue;
                }

                $rawPoints = $path['points'] ?? $path;
                if (!\is_array($rawPoints)) {
                    continue;
                }

                $points = [];
                foreach ($rawPoints as $point) {
                    if (!\is_array($point)) {
                        continue;
                    }

                    $x = $this->toCoordinate($point['x'] ?? null);
                    $y = $this->toCoordinate($point['y'] ?? null);
                    if (null === $x || null === $y) {
                        continue;
                    }

                    $points[] = $x.';'.$y;
                }

                if (2 <= \count($points)) {
                    $encodedItems[] = 'P)('.implode(')(', $points);
                }
            }
        }

        $texts = $payload['texts'] ?? $payload['annotation']['texts'] ?? [];
        if (\is_array($texts)) {
            foreach ($texts as $textAnnotation) {
                if (!\is_array($textAnnotation)) {
                    continue;
                }

                $text = $this->sanitizeAnnotationText($textAnnotation['text'] ?? '');
                $x = $this->toCoordinate($textAnnotation['x'] ?? null);
                $y = $this->toCoordinate($textAnnotation['y'] ?? null);
                if ('' === $text || null === $x || null === $y) {
                    continue;
                }

                $encodedItems[] = 'T)('.$text.')('.$x.';'.$y;
            }
        }

        if ([] === $encodedItems) {
            return [];
        }

        return [[
            'answer' => implode('|', $encodedItems),
            'position' => 0,
            'secondsSpent' => $secondsSpent,
        ]];
    }

    private function sanitizeAnnotationText(mixed $value): string
    {
        $text = trim(strip_tags((string) $value));
        if ('' === $text) {
            return '';
        }

        return str_replace(['|', ')(', "\r", "\n"], [' ', ' ', ' ', ' '], $text);
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<int, array{answer: string, position: int, secondsSpent: int}>
     */
    private function buildHotspotRows(array $payload, int $secondsSpent): array
    {
        $points = $payload['points'] ?? $payload['hotspot']['points'] ?? $payload['answers'] ?? $payload['value'] ?? [];
        if (!\is_array($points)) {
            return [];
        }

        $coordinates = [];
        foreach ($points as $point) {
            if (!\is_array($point)) {
                continue;
            }

            $x = $this->toCoordinate($point['x'] ?? null);
            $y = $this->toCoordinate($point['y'] ?? null);
            if (null === $x || null === $y) {
                continue;
            }

            $answerId = $this->toPositiveInt($point['answerId'] ?? $point['answer_id'] ?? $point['id'] ?? 0);
            $coordinate = $x.';'.$y;
            $coordinates[] = 0 < $answerId ? $answerId.':'.$coordinate : $coordinate;
        }

        if ([] === $coordinates) {
            return [];
        }

        return [[
            'answer' => implode('|', $coordinates),
            'position' => 0,
            'secondsSpent' => $secondsSpent,
        ]];
    }

    private function toCoordinate(mixed $value): ?int
    {
        if (null === $value || '' === $value || !is_numeric($value)) {
            return null;
        }

        return max(0, (int) round((float) $value));
    }

    /**
     * @return array<int, int>
     */
    private function toPositiveIntList(mixed $value): array
    {
        if (!\is_array($value)) {
            $value = [$value];
        }

        $result = [];
        foreach ($value as $item) {
            $integer = $this->toPositiveInt($item);
            if (0 < $integer && !\in_array($integer, $result, true)) {
                $result[] = $integer;
            }
        }

        return $result;
    }

    private function toPositiveInt(mixed $value): int
    {
        if (null === $value || '' === $value) {
            return 0;
        }

        return max(0, (int) $value);
    }

    private function getFirstAnswer(CQuizQuestion $question): ?CQuizAnswer
    {
        $answer = $this->entityManager->createQueryBuilder()
            ->select('answer')
            ->from(CQuizAnswer::class, 'answer')
            ->andWhere('IDENTITY(answer.question) = :questionId')
            ->setParameter('questionId', (int) $question->getIid(), Types::INTEGER)
            ->orderBy('answer.position', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return $answer instanceof CQuizAnswer ? $answer : null;
    }

    /**
     * @return list<CQuizAnswer>
     */
    private function getOrderedAnswers(CQuizQuestion $question): array
    {
        return $this->entityManager->createQueryBuilder()
            ->select('answer')
            ->from(CQuizAnswer::class, 'answer')
            ->andWhere('IDENTITY(answer.question) = :questionId')
            ->setParameter('questionId', (int) $question->getIid(), Types::INTEGER)
            ->orderBy('answer.position', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }


    /**
     * @param array<int, array{answer: string, position: int, secondsSpent: int}> $rows
     *
     * @return array<string, mixed>
     */
    private function buildFeedback(CQuiz $quiz, CQuizQuestion $question, array $rows): array
    {
        $feedbackType = (int) $quiz->getFeedbackType();
        if (!\in_array($feedbackType, [self::FEEDBACK_TYPE_DIRECT, self::FEEDBACK_TYPE_POPUP], true)) {
            return [];
        }

        $answers = $this->getQuestionAnswerMap($question);
        $options = $this->getQuestionOptionMap($question);
        $score = $this->scoreFeedbackQuestion($quiz, $question, $answers, $options, $rows);
        if (0 === (int) $quiz->getPropagateNeg() && 0 > $score) {
            $score = 0.0;
        }

        $maxScore = $this->getQuestionWeight($question, $answers);
        $status = $this->getFeedbackStatus($question, $score, $maxScore, $rows);

        return [
            'enabled' => true,
            'mode' => self::FEEDBACK_TYPE_POPUP === $feedbackType ? 'popup' : 'direct',
            'questionId' => (int) $question->getIid(),
            'status' => $status,
            'title' => $this->getFeedbackTitle($status),
            'score' => $score,
            'maxScore' => $maxScore,
            'entries' => $this->buildFeedbackEntries($question, $answers, $rows),
            ...$this->resolveAdaptiveFeedbackAction($quiz, $question, $status),
        ];
    }

    /**
     * @return array<int, CQuizAnswer>
     */
    private function getQuestionAnswerMap(CQuizQuestion $question): array
    {
        $answers = [];
        foreach ($this->getOrderedAnswers($question) as $answer) {
            if (null !== $answer->getIid()) {
                $answers[(int) $answer->getIid()] = $answer;
            }
        }

        return $answers;
    }

    /**
     * @return array<int, CQuizQuestionOption>
     */
    private function getQuestionOptionMap(CQuizQuestion $question): array
    {
        $options = [];
        foreach ($question->getOptions() as $option) {
            if ($option instanceof CQuizQuestionOption && null !== $option->getIid()) {
                $options[(int) $option->getIid()] = $option;
            }
        }

        return $options;
    }

    /**
     * @param array<int, CQuizAnswer>         $answers
     * @param array<int, CQuizQuestionOption> $options
     * @param array<int, array{answer: string, position: int, secondsSpent: int}> $rows
     */

    /**
     * @return array<string, mixed>
     */
    private function resolveAdaptiveFeedbackAction(CQuiz $quiz, CQuizQuestion $question, string $status): array
    {
        if (self::FEEDBACK_TYPE_DIRECT !== (int) $quiz->getFeedbackType()) {
            return [];
        }

        $relation = $this->entityManager->getRepository(CQuizRelQuestion::class)->findOneBy([
            'quiz' => $quiz,
            'question' => $question,
        ]);

        if (!$relation instanceof CQuizRelQuestion || '' === trim((string) $relation->getDestination())) {
            return [];
        }

        $destination = json_decode((string) $relation->getDestination(), true);
        if (!\is_array($destination)) {
            return [];
        }

        $key = 'correct' === $status ? 'success' : 'failure';
        $scenario = \is_array($destination[$key] ?? null) ? $destination[$key] : [];
        $type = trim((string) ($scenario['type'] ?? ''));
        $url = trim((string) ($scenario['url'] ?? ''));

        if ('' === $type) {
            return [];
        }

        if ('-1' === $type) {
            return ['afterAction' => 'finish'];
        }

        if ('repeat' === $type) {
            return ['afterAction' => 'repeat'];
        }

        if ('url' === $type) {
            if ('' === $url) {
                return [];
            }

            return [
                'afterAction' => 'url',
                'targetUrl' => $url,
            ];
        }

        if (ctype_digit($type) && 0 < (int) $type) {
            return [
                'afterAction' => 'question',
                'targetQuestionId' => (int) $type,
            ];
        }

        return [];
    }

    private function scoreFeedbackQuestion(CQuiz $quiz, CQuizQuestion $question, array $answers, array $options, array $rows): float
    {
        $type = (int) $question->getType();

        if (\in_array($type, self::UNIQUE_TYPES, true) || \in_array($type, self::DROPDOWN_TYPES, true)) {
            return $this->scoreSelectedAnswerRows($answers, $rows);
        }

        if (\in_array($type, self::MULTIPLE_TYPES, true)) {
            if (\in_array($type, [9, 28], true)) {
                return $this->scoreCombinationRows($question, $answers, $rows);
            }

            return $this->scoreSelectedAnswerRows($answers, $rows);
        }

        if (\in_array($type, self::TRUE_FALSE_TYPES, true)) {
            return $this->scoreTrueFalseRows($question, $answers, $options, $rows, false);
        }

        if (\in_array($type, self::TRUE_FALSE_DEGREE_CERTAINTY_TYPES, true)) {
            return $this->scoreTrueFalseRows($question, $answers, $options, $rows, true);
        }

        if (\in_array($type, self::FILL_BLANK_TYPES, true)) {
            return $this->scoreFillBlankRowsForFeedback($quiz, $question, $rows);
        }

        if (\in_array($type, self::MATCHING_TYPES, true)) {
            if (\in_array($type, [24, 25], true)) {
                return $this->scoreMatchingCombinationRows($question, $answers, $rows);
            }

            return $this->scoreMatchingRowsForFeedback($answers, $rows);
        }

        if (\in_array($type, self::DRAGGABLE_TYPES, true)) {
            return $this->scoreDraggableRowsForFeedback($answers, $rows);
        }

        if (\in_array($type, self::CALCULATED_TYPES, true)) {
            return $this->scoreCalculatedRowsForFeedback($question, $answers, $rows);
        }

        if (self::HOTSPOT_DELINEATION === $type) {
            return $this->scoreHotspotDelineationRowsForFeedback($quiz, $question, $answers, $rows);
        }

        return 0.0;
    }

    /**
     * @param array<int, CQuizAnswer> $answers
     * @param array<int, array{answer: string, position: int, secondsSpent: int}> $rows
     */
    private function scoreSelectedAnswerRows(array $answers, array $rows): float
    {
        $score = 0.0;
        foreach ($rows as $row) {
            $answerId = (int) $row['answer'];
            if (isset($answers[$answerId])) {
                $score += (float) $answers[$answerId]->getPonderation();
            }
        }

        return $score;
    }

    /**
     * @param array<int, CQuizAnswer> $answers
     * @param array<int, array{answer: string, position: int, secondsSpent: int}> $rows
     */
    private function scoreCombinationRows(CQuizQuestion $question, array $answers, array $rows): float
    {
        $selected = array_flip($this->getRowAnswerIds($rows));
        foreach ($answers as $answer) {
            $answerId = (int) $answer->getIid();
            $isCorrect = 1 === (int) $answer->getCorrect();
            $isSelected = isset($selected[$answerId]);
            if ($isCorrect !== $isSelected) {
                return 0.0;
            }
        }

        $firstAnswer = reset($answers);
        if ($firstAnswer instanceof CQuizAnswer && 0.0 !== (float) $firstAnswer->getPonderation()) {
            return (float) $firstAnswer->getPonderation();
        }

        return (float) $question->getPonderation();
    }

    /**
     * @param array<int, CQuizAnswer>         $answers
     * @param array<int, CQuizQuestionOption> $options
     * @param array<int, array{answer: string, position: int, secondsSpent: int}> $rows
     */
    private function scoreTrueFalseRows(CQuizQuestion $question, array $answers, array $options, array $rows, bool $withDegreeCertainty): float
    {
        [$trueScore, $falseScore, $doubtScore] = $this->getTrueFalseScores((string) $question->getExtra());
        $choices = [];
        foreach ($rows as $row) {
            $parts = explode(':', (string) $row['answer']);
            $answerId = (int) ($parts[0] ?? 0);
            $optionId = (int) ($parts[1] ?? 0);
            $degreeId = (int) ($parts[2] ?? 0);
            if (0 < $answerId && 0 < $optionId) {
                $choices[$answerId] = ['choice' => $optionId, 'degree' => $degreeId];
            }
        }

        $score = 0.0;
        foreach ($answers as $answer) {
            $answerId = (int) $answer->getIid();
            $studentChoice = (int) ($choices[$answerId]['choice'] ?? 0);
            if (0 >= $studentChoice) {
                $score += $withDegreeCertainty ? 0.0 : $doubtScore;
                continue;
            }

            if ($this->isTrueFalseChoiceCorrect($studentChoice, (int) $answer->getCorrect(), $options)) {
                if (!$withDegreeCertainty) {
                    $score += $trueScore;
                    continue;
                }

                $degreePosition = $this->getTrueFalseOptionPosition((int) ($choices[$answerId]['degree'] ?? 0), $options);
                $score += 3 <= $degreePosition && 9 > $degreePosition ? $trueScore : $doubtScore;
                continue;
            }

            if ($withDegreeCertainty) {
                $degreePosition = $this->getTrueFalseOptionPosition((int) ($choices[$answerId]['degree'] ?? 0), $options);
                $score += 3 <= $degreePosition && 9 > $degreePosition ? $falseScore : $doubtScore;
                continue;
            }

            $optionTitle = $this->getTrueFalseOptionTitle($studentChoice, $options);
            $score += \in_array($optionTitle, ["Don't know", 'DoubtScore'], true) ? $doubtScore : $falseScore;
        }

        return $score;
    }

    /**
     * @return array{0: float, 1: float, 2: float}
     */
    private function getTrueFalseScores(string $extra): array
    {
        if ('' === trim($extra)) {
            return [1.0, -0.5, 0.0];
        }

        $parts = explode(':', $extra);

        return [
            isset($parts[0]) ? (float) trim($parts[0]) : 1.0,
            isset($parts[1]) ? (float) trim($parts[1]) : -0.5,
            isset($parts[2]) ? (float) trim($parts[2]) : 0.0,
        ];
    }

    /**
     * @param array<int, CQuizQuestionOption> $options
     */
    private function isTrueFalseChoiceCorrect(int $studentChoice, int $correctChoice, array $options): bool
    {
        if (0 >= $studentChoice || 0 >= $correctChoice) {
            return false;
        }

        if ($studentChoice === $correctChoice) {
            return true;
        }

        $studentPosition = $this->getTrueFalseOptionPosition($studentChoice, $options);
        $correctPosition = $this->getTrueFalseOptionPosition($correctChoice, $options);

        return 0 < $studentPosition && $studentPosition === $correctPosition;
    }

    /**
     * @param array<int, CQuizQuestionOption> $options
     */
    private function getTrueFalseOptionPosition(int $choice, array $options): int
    {
        $option = $options[$choice] ?? null;
        if ($option instanceof CQuizQuestionOption) {
            return (int) $option->getPosition();
        }

        foreach ($options as $candidate) {
            if ($candidate instanceof CQuizQuestionOption && (int) $candidate->getPosition() === $choice) {
                return (int) $candidate->getPosition();
            }
        }

        return $choice;
    }

    /**
     * @param array<int, CQuizQuestionOption> $options
     */
    private function getTrueFalseOptionTitle(int $choice, array $options): string
    {
        $option = $options[$choice] ?? null;
        if ($option instanceof CQuizQuestionOption) {
            return (string) $option->getTitle();
        }

        foreach ($options as $candidate) {
            if ($candidate instanceof CQuizQuestionOption && (int) $candidate->getPosition() === $choice) {
                return (string) $candidate->getTitle();
            }
        }

        return '';
    }

    /**
     * @param array<int, array{answer: string, position: int, secondsSpent: int}> $rows
     */
    private function scoreFillBlankRowsForFeedback(CQuiz $quiz, CQuizQuestion $question, array $rows): float
    {
        $row = $rows[0] ?? null;
        if (!\is_array($row)) {
            return 0.0;
        }

        $parsed = $this->parseFillBlankStudentRows((string) $row['answer']);
        $caseInsensitive = 'case:false' === (string) $question->getExtra();
        $score = 0.0;
        $allCorrect = [] !== $parsed['items'];

        foreach ($parsed['items'] as $index => $item) {
            $expected = (string) ($item['expected'] ?? '');
            $student = (string) ($item['student'] ?? '');
            $isCorrect = $caseInsensitive ? 0 === strcasecmp($expected, $student) : $expected === $student;
            if ($isCorrect) {
                $score += (float) ($parsed['weights'][$index] ?? 0.0);
                continue;
            }

            $allCorrect = false;
        }

        if (27 === (int) $question->getType()) {
            return $allCorrect ? (float) $question->getPonderation() : 0.0;
        }

        return $score;
    }

    /**
     * @return array{items: array<int, array{expected: string, student: string}>, weights: array<int, float>}
     */
    private function parseFillBlankStudentRows(string $encodedAnswer): array
    {
        $parts = explode('::', $encodedAnswer, 2);
        $text = (string) ($parts[0] ?? '');
        $system = (string) ($parts[1] ?? '');
        $separator = $this->getFillBlankSeparator($system);
        [$start, $end] = $this->getFillBlankSeparators($separator);
        $pattern = '/'.preg_quote($start, '/').'(.*?)'.preg_quote($end, '/').'/s';
        preg_match_all($pattern, $text, $matches);
        $values = $matches[1] ?? [];
        $items = [];
        for ($index = 0; $index < \count($values); $index += 3) {
            $items[] = [
                'expected' => htmlspecialchars_decode((string) ($values[$index] ?? ''), ENT_QUOTES),
                'student' => htmlspecialchars_decode((string) ($values[$index + 1] ?? ''), ENT_QUOTES),
            ];
        }

        $systemParts = explode('@', $system, 2);
        $details = explode(':', (string) ($systemParts[0] ?? ''));
        $weights = [];
        foreach (explode(',', (string) ($details[0] ?? '')) as $weight) {
            if ('' !== trim($weight)) {
                $weights[] = (float) trim($weight);
            }
        }

        return ['items' => $items, 'weights' => $weights];
    }

    /**
     * @param array<int, CQuizAnswer> $answers
     * @param array<int, array{answer: string, position: int, secondsSpent: int}> $rows
     */
    private function scoreMatchingRowsForFeedback(array $answers, array $rows): float
    {
        $score = 0.0;
        foreach ($rows as $row) {
            $promptId = (int) $row['position'];
            $optionId = (int) $row['answer'];
            $answer = $answers[$promptId] ?? null;
            if ($answer instanceof CQuizAnswer && $optionId === (int) $answer->getCorrect()) {
                $score += (float) $answer->getPonderation();
            }
        }

        return $score;
    }

    /**
     * @param array<int, CQuizAnswer> $answers
     * @param array<int, array{answer: string, position: int, secondsSpent: int}> $rows
     */
    private function scoreMatchingCombinationRows(CQuizQuestion $question, array $answers, array $rows): float
    {
        $choices = [];
        foreach ($rows as $row) {
            $choices[(int) $row['position']] = (int) $row['answer'];
        }

        $optionCount = 0;
        $correctCount = 0;
        foreach ($answers as $answer) {
            if (0 === (int) $answer->getCorrect()) {
                ++$optionCount;
                continue;
            }

            if (($choices[(int) $answer->getIid()] ?? 0) === (int) $answer->getCorrect()) {
                ++$correctCount;
            }
        }

        return 0 < $optionCount && $correctCount >= $optionCount ? (float) $question->getPonderation() : 0.0;
    }

    /**
     * @param array<int, CQuizAnswer> $answers
     * @param array<int, array{answer: string, position: int, secondsSpent: int}> $rows
     */
    private function scoreDraggableRowsForFeedback(array $answers, array $rows): float
    {
        $score = 0.0;
        foreach ($rows as $row) {
            $answerId = (int) $row['position'];
            $selectedPosition = (int) $row['answer'];
            $answer = $answers[$answerId] ?? null;
            if ($answer instanceof CQuizAnswer && $selectedPosition === (int) $answer->getCorrect()) {
                $score += (float) $answer->getPonderation();
            }
        }

        return $score;
    }

    /**
     * @param array<int, CQuizAnswer> $answers
     * @param array<int, array{answer: string, position: int, secondsSpent: int}> $rows
     */
    private function scoreCalculatedRowsForFeedback(CQuizQuestion $question, array $answers, array $rows): float
    {
        $row = $rows[0] ?? null;
        if (!\is_array($row)) {
            return 0.0;
        }

        [$answerId, $studentAnswer] = $this->parseCalculatedStudentAnswer((string) $row['answer']);
        $teacherAnswer = 0 < $answerId && isset($answers[$answerId]) ? $answers[$answerId] : reset($answers);
        if (!$teacherAnswer instanceof CQuizAnswer) {
            return 0.0;
        }

        $expectedAnswer = $this->extractCalculatedExpectedAnswer((string) $teacherAnswer->getAnswer());
        if ('' === $expectedAnswer || '' === $studentAnswer) {
            return 0.0;
        }

        return trim($studentAnswer) === trim($expectedAnswer) ? (float) $question->getPonderation() : 0.0;
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

    private function extractCalculatedExpectedAnswer(string $answer): string
    {
        $parts = explode('@@', $answer, 2);
        $text = (string) ($parts[0] ?? $answer);
        if (1 === preg_match('/\[([^\[\]]*)\]\s*$/', $text, $matches)) {
            return trim((string) ($matches[1] ?? ''));
        }

        return '';
    }

    /**
     * @param array<int, CQuizAnswer> $answers
     * @param array<int, array{answer: string, position: int, secondsSpent: int}> $rows
     */
    private function scoreHotspotDelineationRowsForFeedback(CQuiz $quiz, CQuizQuestion $question, array $answers, array $rows): float
    {
        $studentPolygon = $this->getSavedDelineationPolygonFromRows($rows);
        if (3 > \count($studentPolygon)) {
            return 0.0;
        }

        $teacherDelineation = null;
        $organsAtRisk = [];
        foreach ($answers as $answer) {
            $hotspotType = (string) $answer->getHotspotType();
            if ('delineation' === $hotspotType && null === $teacherDelineation) {
                $teacherDelineation = $answer;
                continue;
            }

            if ('oar' === $hotspotType) {
                $organsAtRisk[] = $answer;
            }
        }

        if (!$teacherDelineation instanceof CQuizAnswer) {
            return 0.0;
        }

        $teacherPolygon = $this->parseDelineationPolygon((string) $teacherDelineation->getHotspotCoordinates());
        if (3 > \count($teacherPolygon)) {
            return 0.0;
        }

        $metrics = $this->getDelineationOverlapMetrics($teacherPolygon, $studentPolygon);
        $thresholds = $this->getDelineationThresholds($quiz, $question, (int) $teacherDelineation->getPosition());

        if ($metrics['overlap'] < $thresholds['minOverlap']) {
            return 0.0;
        }

        if ($metrics['excess'] > $thresholds['maxExcess']) {
            return 0.0;
        }

        if ($metrics['missing'] > $thresholds['maxMissing']) {
            return 0.0;
        }

        foreach ($organsAtRisk as $organAtRisk) {
            $organPolygon = $this->parseDelineationPolygon((string) $organAtRisk->getHotspotCoordinates());
            if (3 <= \count($organPolygon) && $this->polygonsOverlap($studentPolygon, $organPolygon)) {
                return 0.0;
            }
        }

        $score = (float) $teacherDelineation->getPonderation();

        return 0.0 < $score ? $score : (float) $question->getPonderation();
    }

    /**
     * @param array<int, array{answer: string, position: int, secondsSpent: int}> $rows
     *
     * @return array<int, array{x: float, y: float}>
     */
    private function getSavedDelineationPolygonFromRows(array $rows): array
    {
        $row = $rows[0] ?? null;
        if (!\is_array($row)) {
            return [];
        }

        return $this->parseDelineationPolygon((string) ($row['answer'] ?? ''));
    }

    /**
     * @return array<int, array{x: float, y: float}>
     */
    private function parseDelineationPolygon(string $coordinates): array
    {
        $normalizedCoordinates = str_replace('/', '|', trim($coordinates));
        $points = [];
        foreach (explode('|', $normalizedCoordinates) as $coordinate) {
            $point = $this->decodeHotspotPoint($coordinate);
            if (null !== $point) {
                $points[] = ['x' => $point['x'], 'y' => $point['y']];
            }
        }

        return $this->removeDuplicateClosingPoint($points);
    }

    /**
     * @param array<int, array{x: float, y: float}> $points
     *
     * @return array<int, array{x: float, y: float}>
     */
    private function removeDuplicateClosingPoint(array $points): array
    {
        $count = \count($points);
        if (4 > $count) {
            return $points;
        }

        $first = $points[0];
        $last = $points[$count - 1];
        if (abs($first['x'] - $last['x']) < 0.0001 && abs($first['y'] - $last['y']) < 0.0001) {
            array_pop($points);
        }

        return $points;
    }

    /**
     * @param array<int, array{x: float, y: float}> $expectedPolygon
     * @param array<int, array{x: float, y: float}> $studentPolygon
     *
     * @return array{overlap: float, missing: float, excess: float}
     */
    private function getDelineationOverlapMetrics(array $expectedPolygon, array $studentPolygon): array
    {
        $bounds = $this->getPolygonUnionBounds($expectedPolygon, $studentPolygon);
        if (null === $bounds) {
            return ['overlap' => 0.0, 'missing' => 100.0, 'excess' => 100.0];
        }

        $maxDimension = max($bounds['maxX'] - $bounds['minX'], $bounds['maxY'] - $bounds['minY']);
        $step = max(1.0, ceil($maxDimension / 500.0));
        $expectedArea = 0;
        $studentArea = 0;
        $overlapArea = 0;
        $expectedCoordinates = $this->encodePolygonCoordinates($expectedPolygon);
        $studentCoordinates = $this->encodePolygonCoordinates($studentPolygon);

        for ($x = $bounds['minX']; $x <= $bounds['maxX']; $x += $step) {
            for ($y = $bounds['minY']; $y <= $bounds['maxY']; $y += $step) {
                $point = ['x' => $x + ($step / 2), 'y' => $y + ($step / 2)];
                $insideExpected = $this->isPointInPolygon($point, $expectedCoordinates);
                $insideStudent = $this->isPointInPolygon($point, $studentCoordinates);

                if ($insideExpected) {
                    ++$expectedArea;
                }
                if ($insideStudent) {
                    ++$studentArea;
                }
                if ($insideExpected && $insideStudent) {
                    ++$overlapArea;
                }
            }
        }

        if (0 >= $expectedArea) {
            return ['overlap' => 0.0, 'missing' => 100.0, 'excess' => 100.0];
        }

        $overlap = round(($overlapArea / $expectedArea) * 100.0, 2);
        $missing = max(0.0, 100.0 - $overlap);
        $excess = round((max(0, $studentArea - $overlapArea) / $expectedArea) * 100.0, 2);

        return [
            'overlap' => min(100.0, $overlap),
            'missing' => min(100.0, $missing),
            'excess' => min(100.0, $excess),
        ];
    }

    /**
     * @param array<int, array{x: float, y: float}> $firstPolygon
     * @param array<int, array{x: float, y: float}> $secondPolygon
     */
    private function polygonsOverlap(array $firstPolygon, array $secondPolygon): bool
    {
        $metrics = $this->getDelineationOverlapMetrics($secondPolygon, $firstPolygon);

        return $metrics['overlap'] > 0.0;
    }

    /**
     * @param array<int, array{x: float, y: float}> $firstPolygon
     * @param array<int, array{x: float, y: float}> $secondPolygon
     *
     * @return array{minX: float, maxX: float, minY: float, maxY: float}|null
     */
    private function getPolygonUnionBounds(array $firstPolygon, array $secondPolygon): ?array
    {
        $points = [...$firstPolygon, ...$secondPolygon];
        if ([] === $points) {
            return null;
        }

        $xValues = array_map(static fn (array $point): float => $point['x'], $points);
        $yValues = array_map(static fn (array $point): float => $point['y'], $points);

        return [
            'minX' => min($xValues),
            'maxX' => max($xValues),
            'minY' => min($yValues),
            'maxY' => max($yValues),
        ];
    }

    /**
     * @param array<int, array{x: float, y: float}> $polygon
     */
    private function encodePolygonCoordinates(array $polygon): string
    {
        return implode('|', array_map(static fn (array $point): string => $point['x'].';'.$point['y'], $polygon));
    }

    /**
     * @return array{minOverlap: float, maxExcess: float, maxMissing: float}
     */
    private function getDelineationThresholds(CQuiz $quiz, CQuizQuestion $question, int $position): array
    {
        $relation = $this->entityManager->getRepository(CQuizRelQuestion::class)->findOneBy([
            'quiz' => $quiz,
            'question' => $question,
        ]);

        if (!$relation instanceof CQuizRelQuestion || '' === (string) $relation->getDestination()) {
            return ['minOverlap' => 1.0, 'maxExcess' => 100.0, 'maxMissing' => 100.0];
        }

        $destination = json_decode((string) $relation->getDestination(), true);
        if (!\is_array($destination)) {
            return ['minOverlap' => 1.0, 'maxExcess' => 100.0, 'maxMissing' => 100.0];
        }

        $thresholds = \is_array($destination['thresholds'] ?? null) ? $destination['thresholds'] : [];
        $positionThresholds = \is_array($thresholds[(string) $position] ?? null) ? $thresholds[(string) $position] : [];

        return [
            'minOverlap' => $this->normalizePercentage($positionThresholds['minOverlap'] ?? 1.0, 1.0),
            'maxExcess' => $this->normalizePercentage($positionThresholds['maxExcess'] ?? 100.0, 100.0),
            'maxMissing' => $this->normalizePercentage($positionThresholds['maxMissing'] ?? 100.0, 100.0),
        ];
    }

    private function normalizePercentage(mixed $value, float $default): float
    {
        if (!is_numeric($value)) {
            return $default;
        }

        return min(100.0, max(0.0, (float) $value));
    }

    /**
     * @return array{x: float, y: float, answerId?: int}|null
     */
    private function decodeHotspotPoint(string $coordinate): ?array
    {
        $answerId = 0;
        $coordinateValue = trim($coordinate);
        if (str_contains($coordinateValue, ':')) {
            [$answerIdValue, $coordinateValue] = explode(':', $coordinateValue, 2);
            $answerId = (int) $answerIdValue;
        }

        $parts = array_map('trim', explode(';', $coordinateValue));
        if (2 > \count($parts) || !is_numeric($parts[0]) || !is_numeric($parts[1])) {
            return null;
        }

        $point = ['x' => (float) $parts[0], 'y' => (float) $parts[1]];
        if (0 < $answerId) {
            $point['answerId'] = $answerId;
        }

        return $point;
    }

    /**
     * @param array{x: float, y: float} $point
     */
    private function isPointInPolygon(array $point, string $coordinates): bool
    {
        $vertices = [];
        foreach (explode('|', $coordinates) as $coordinate) {
            $decoded = $this->decodeHotspotPoint($coordinate);
            if (null !== $decoded) {
                $vertices[] = $decoded;
            }
        }

        $count = \count($vertices);
        if (3 > $count) {
            return false;
        }

        $inside = false;
        for ($i = 0, $j = $count - 1; $i < $count; $j = $i++) {
            $xi = $vertices[$i]['x'];
            $yi = $vertices[$i]['y'];
            $xj = $vertices[$j]['x'];
            $yj = $vertices[$j]['y'];

            $intersects = (($yi > $point['y']) !== ($yj > $point['y']))
                && ($point['x'] < ($xj - $xi) * ($point['y'] - $yi) / (($yj - $yi) ?: 0.000001) + $xi);
            if ($intersects) {
                $inside = !$inside;
            }
        }

        return $inside;
    }

    /**
     * @param array<int, CQuizAnswer> $answers
     */
    private function getQuestionWeight(CQuizQuestion $question, array $answers): float
    {
        if (\in_array((int) $question->getType(), [9, 12, 24, 25, 27, 28], true)) {
            $firstAnswer = reset($answers);
            if ($firstAnswer instanceof CQuizAnswer && 0.0 !== (float) $firstAnswer->getPonderation()) {
                return (float) $firstAnswer->getPonderation();
            }
        }

        return (float) $question->getPonderation();
    }

    /**
     * @param array<int, array{answer: string, position: int, secondsSpent: int}> $rows
     *
     * @return array<int, int>
     */
    private function getRowAnswerIds(array $rows): array
    {
        $answerIds = [];
        foreach ($rows as $row) {
            $answerId = (int) $row['answer'];
            if (0 < $answerId && !\in_array($answerId, $answerIds, true)) {
                $answerIds[] = $answerId;
            }
        }

        return $answerIds;
    }

    /**
     * @param array<int, CQuizAnswer> $answers
     * @param array<int, array{answer: string, position: int, secondsSpent: int}> $rows
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildFeedbackEntries(CQuizQuestion $question, array $answers, array $rows): array
    {
        $entries = [];
        $type = (int) $question->getType();
        $answerIds = $this->getFeedbackAnswerIds($type, $rows);

        foreach ($answerIds as $answerId) {
            $answer = $answers[$answerId] ?? null;
            if (!$answer instanceof CQuizAnswer) {
                continue;
            }

            $entries[] = [
                'answer' => $answer->getAnswer(),
                'comment' => (string) $answer->getComment(),
                'correct' => 0 < (float) $answer->getPonderation() || 1 === (int) $answer->getCorrect(),
            ];
        }

        if ([] === $entries) {
            $firstAnswer = reset($answers);
            if ($firstAnswer instanceof CQuizAnswer && '' !== trim((string) $firstAnswer->getComment())) {
                $entries[] = [
                    'answer' => '',
                    'comment' => (string) $firstAnswer->getComment(),
                    'correct' => false,
                ];
            }
        }

        return $entries;
    }

    /**
     * @param array<int, array{answer: string, position: int, secondsSpent: int}> $rows
     *
     * @return array<int, int>
     */
    private function getFeedbackAnswerIds(int $type, array $rows): array
    {
        if (\in_array($type, self::MATCHING_TYPES, true)) {
            return array_values(array_filter(array_map(static fn (array $row): int => (int) $row['answer'], $rows)));
        }

        if (\in_array($type, self::TRUE_FALSE_TYPES, true) || \in_array($type, self::TRUE_FALSE_DEGREE_CERTAINTY_TYPES, true)) {
            return array_values(array_filter(array_map(static function (array $row): int {
                $parts = explode(':', (string) $row['answer']);

                return (int) ($parts[0] ?? 0);
            }, $rows)));
        }

        return $this->getRowAnswerIds($rows);
    }

    /**
     * @param array<int, array{answer: string, position: int, secondsSpent: int}> $rows
     */
    private function getFeedbackStatus(CQuizQuestion $question, float $score, float $maxScore, array $rows): string
    {
        if ([] === $rows) {
            return 'empty';
        }

        if (\in_array((int) $question->getType(), self::FREE_ANSWER_TYPES, true)) {
            return 'pending';
        }

        if (0.0 < $maxScore && $score >= $maxScore) {
            return 'correct';
        }

        if (0.0 < $score) {
            return 'partial';
        }

        return 'incorrect';
    }

    private function getFeedbackTitle(string $status): string
    {
        return match ($status) {
            'correct' => 'Correct',
            'partial' => 'Partially correct',
            'pending' => 'Pending correction',
            'empty' => 'No answer selected',
            default => 'Incorrect',
        };
    }

    private function deletePreviousDraftRows(TrackEExercise $attempt, int $questionId): void
    {
        $rows = $this->entityManager->createQueryBuilder()
            ->select('saved')
            ->from(TrackEAttempt::class, 'saved')
            ->andWhere('IDENTITY(saved.trackExercise) = :attemptId')
            ->andWhere('saved.questionId = :questionId')
            ->setParameter('attemptId', (int) $attempt->getExeId(), Types::INTEGER)
            ->setParameter('questionId', $questionId, Types::INTEGER)
            ->getQuery()
            ->getResult()
        ;

        foreach ($rows as $row) {
            if ($row instanceof TrackEAttempt) {
                $this->entityManager->remove($row);
            }
        }
    }

    /**
     * @return array<int, array{answer: string, position: int|null, secondsSpent: int}>
     */
    private function getSavedAnswerRows(int $attemptId, int $questionId): array
    {
        $rows = $this->entityManager->createQueryBuilder()
            ->select('saved.answer AS answer')
            ->addSelect('saved.position AS position')
            ->addSelect('saved.secondsSpent AS secondsSpent')
            ->from(TrackEAttempt::class, 'saved')
            ->andWhere('IDENTITY(saved.trackExercise) = :attemptId')
            ->andWhere('saved.questionId = :questionId')
            ->setParameter('attemptId', $attemptId, Types::INTEGER)
            ->setParameter('questionId', $questionId, Types::INTEGER)
            ->orderBy('saved.position', 'ASC')
            ->getQuery()
            ->getArrayResult()
        ;

        $result = [];
        foreach ($rows as $row) {
            if (!\is_array($row)) {
                continue;
            }

            $result[] = [
                'answer' => (string) ($row['answer'] ?? ''),
                'position' => null !== ($row['position'] ?? null) ? (int) $row['position'] : null,
                'secondsSpent' => (int) ($row['secondsSpent'] ?? 0),
            ];
        }

        return $result;
    }

    /**
     * @return array<int, int>
     */
    private function getReviewQuestionIds(int $attemptId): array
    {
        $attempt = $this->entityManager->createQueryBuilder()
            ->select('attempt.questionsToCheck')
            ->from(TrackEExercise::class, 'attempt')
            ->andWhere('attempt.exeId = :attemptId')
            ->setParameter('attemptId', $attemptId, Types::INTEGER)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        if (!\is_array($attempt)) {
            return [];
        }

        return $this->parseQuestionIds((string) ($attempt['questionsToCheck'] ?? ''));
    }

    /**
     * @return array<int, int>
     */
    private function getAnsweredQuestionIds(int $attemptId): array
    {
        $rows = $this->entityManager->createQueryBuilder()
            ->select('DISTINCT saved.questionId AS questionId')
            ->from(TrackEAttempt::class, 'saved')
            ->andWhere('IDENTITY(saved.trackExercise) = :attemptId')
            ->setParameter('attemptId', $attemptId, Types::INTEGER)
            ->orderBy('saved.questionId', 'ASC')
            ->getQuery()
            ->getArrayResult()
        ;

        $result = [];
        foreach ($rows as $row) {
            if (\is_array($row)) {
                $result[] = (int) ($row['questionId'] ?? 0);
            }
        }

        return array_values(array_filter($result));
    }
}
