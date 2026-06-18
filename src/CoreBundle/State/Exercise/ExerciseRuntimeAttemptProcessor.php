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
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CLpItem;
use Chamilo\CourseBundle\Entity\CLpItemView;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Entity\CQuizQuestion;
use Chamilo\CourseBundle\Entity\CQuizRelQuestion;
use Chamilo\CourseBundle\Repository\CQuizRepository;
use DateTime;
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
 * Starts or resumes an exercise runtime attempt without submitting answers yet.
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
    private const LP_ITEM_TYPE_QUIZ = 'quiz';
    private const STATUS_INCOMPLETE = 'incomplete';
    private const QUESTION_SELECTION_RANDOM = 2;
    private const QUESTION_SELECTION_CATEGORIES_ORDERED_QUESTIONS_ORDERED = 3;
    private const QUESTION_SELECTION_CATEGORIES_RANDOM_QUESTIONS_ORDERED = 4;
    private const QUESTION_SELECTION_CATEGORIES_ORDERED_QUESTIONS_RANDOM = 5;
    private const QUESTION_SELECTION_CATEGORIES_RANDOM_QUESTIONS_RANDOM = 6;
    private const UNIQUE_TYPES = [1, 10, 17, 21];
    private const MULTIPLE_TYPES = [2, 9, 14];
    private const TRUE_FALSE_TYPES = [11, 12, 22];
    private const FILL_BLANK_TYPES = [3, 27];
    private const MATCHING_TYPES = [4, 19, 24, 25];
    private const DRAGGABLE_TYPES = [18];
    private const DROPDOWN_TYPES = [28, 29];
    private const CALCULATED_TYPES = [16];
    private const FREE_ANSWER_TYPES = [5];
    private const ORAL_EXPRESSION_TYPES = [13];
    private const UPLOAD_ANSWER_TYPES = [23];
    private const ANNOTATION_TYPES = [20];
    private const HOTSPOT_TYPES = [6, 8, 26];
    private const PAGE_BREAK = 31;
    private const MEDIA_QUESTION = 15;
    private const STRUCTURAL_CONTENT_TYPES = [self::MEDIA_QUESTION, self::PAGE_BREAK];
    private const LEGACY_RUNTIME_REASON_UNSUPPORTED_QUESTION = 'This exercise contains a question type that requires a different test player.';

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
        $canManagePermission = $this->canManageExercises();
        $runsAsLearner = !$canManagePermission || $this->isLearnerRuntimeRequest($request);

        if (!$canManagePermission && !$this->canViewExercises()) {
            throw new AccessDeniedHttpException('You are not allowed to start this exercise.');
        }

        $exerciseId = isset($uriVariables['exerciseId']) ? (int) $uriVariables['exerciseId'] : (int) ($data->exerciseId ?? 0);
        if (0 >= $exerciseId) {
            throw new BadRequestHttpException('A valid exercise id is required.');
        }

        $quiz = $this->getExerciseFromCurrentContext($exerciseId, $course, $session, $canManagePermission);

        if ($canManagePermission && !$runsAsLearner) {
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
                'This exercise uses a question selection mode that requires a different test player.'
            );
        }

        if ($this->hasUnsupportedRuntimeQuestionType($quiz)) {
            return $this->createLegacyRequiredResponse(
                $quiz,
                $course,
                $session,
                $request,
                self::LEGACY_RUNTIME_REASON_UNSUPPORTED_QUESTION
            );
        }

        if (0 < (int) $quiz->getRandomByCategory() && 0 >= (int) $quiz->getRandom()) {
            return $this->createLegacyRequiredResponse(
                $quiz,
                $course,
                $session,
                $request,
                'Please select some random question.'
            );
        }

        $incompleteAttempt = $this->findIncompleteAttempt($quiz, $course, $session, $user, $request);
        if ($incompleteAttempt instanceof TrackEExercise) {
            return $this->normalizeAttemptResponse($quiz, $course, $session, $request, $incompleteAttempt, 'Attempt resumed');
        }

        if ($this->isSettingEnabled('exercise.exercises_disable_new_attempts')) {
            return $this->createBlockedResponse(
                $quiz,
                $course,
                $session,
                $request,
                'Disable new test attempts',
                $this->buildQuestionList($quiz)
            );
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
        if ($this->isQuestionLimitPerDayReached($questionIds, $course, $session, $user)) {
            return $this->createBlockedResponse(
                $quiz,
                $course,
                $session,
                $request,
                sprintf(
                    'Sorry, you have reached the maximum number of questions (%d) for the day. Please try again tomorrow.',
                    $this->getCourseSettingInt($course, 'quiz_question_limit_per_day')
                ),
                $questionIds
            );
        }

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

    private function isLearnerRuntimeRequest(Request $request): bool
    {
        $origin = (string) $request->query->get('origin', '');
        $isStudentView = strtolower(trim((string) $request->query->get('isStudentView', '')));
        $preview = strtolower(trim((string) $request->query->get('preview', '')));

        return 'learnpath' === $origin
            || $request->query->has('lp_init')
            || $request->query->has('learnpath_id')
            || in_array($isStudentView, ['1', 'true', 'yes'], true)
            || str_starts_with($isStudentView, 'true')
            || in_array($preview, ['1', 'true', 'yes'], true)
            || str_starts_with($preview, 'true');
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

    private function requiresLegacyQuestionSelection(CQuiz $quiz): bool
    {
        return (int) ($quiz->getQuestionSelectionType() ?? 0) > self::QUESTION_SELECTION_CATEGORIES_RANDOM_QUESTIONS_RANDOM;
    }

    private function hasUnsupportedRuntimeQuestionType(CQuiz $quiz): bool
    {
        foreach ($this->getOrderedQuestionRelations($quiz) as $relation) {
            if (!$relation instanceof CQuizRelQuestion) {
                continue;
            }

            $question = $relation->getQuestion();
            if (!$question instanceof CQuizQuestion) {
                continue;
            }

            $type = (int) $question->getType();
            if (\in_array($type, self::STRUCTURAL_CONTENT_TYPES, true)) {
                continue;
            }

            if (!$this->isFinishSupportedQuestionType($type)) {
                return true;
            }
        }

        return false;
    }

    private function isFinishSupportedQuestionType(int $type): bool
    {
        return \in_array($type, self::UNIQUE_TYPES, true)
            || \in_array($type, self::MULTIPLE_TYPES, true)
            || \in_array($type, self::TRUE_FALSE_TYPES, true)
            || \in_array($type, self::FILL_BLANK_TYPES, true)
            || \in_array($type, self::MATCHING_TYPES, true)
            || \in_array($type, self::DRAGGABLE_TYPES, true)
            || \in_array($type, self::DROPDOWN_TYPES, true)
            || \in_array($type, self::CALCULATED_TYPES, true)
            || \in_array($type, self::FREE_ANSWER_TYPES, true)
            || \in_array($type, self::ORAL_EXPRESSION_TYPES, true)
            || \in_array($type, self::UPLOAD_ANSWER_TYPES, true)
            || \in_array($type, self::ANNOTATION_TYPES, true)
            || \in_array($type, self::HOTSPOT_TYPES, true);
    }

    /**
     * @return array<int, CQuizRelQuestion>
     */
    private function getOrderedQuestionRelations(CQuiz $quiz): array
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

        return array_values(array_filter($relations, static fn (mixed $relation): bool => $relation instanceof CQuizRelQuestion));
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

        $selectionType = (int) ($quiz->getQuestionSelectionType() ?? 0);
        $randomCount = (int) $quiz->getRandom();
        if (0 < (int) $quiz->getRandomByCategory() && 0 !== $randomCount) {
            return $this->buildRandomByCategoryQuestionList($quiz, $relations, $randomCount);
        }

        if ($selectionType >= self::QUESTION_SELECTION_CATEGORIES_ORDERED_QUESTIONS_ORDERED
            && $selectionType <= self::QUESTION_SELECTION_CATEGORIES_RANDOM_QUESTIONS_RANDOM
        ) {
            return $this->buildCategoryMatrixQuestionList($quiz, $relations, $selectionType);
        }

        $mustShuffle = self::QUESTION_SELECTION_RANDOM === $selectionType;
        $questionIds = $this->buildOrderedQuestionIds($relations, $mustShuffle || 0 < $randomCount);

        if ($mustShuffle || 0 < $randomCount) {
            shuffle($questionIds);
        }

        if (0 < $randomCount && $randomCount < \count($questionIds)) {
            $questionIds = \array_slice($questionIds, 0, $randomCount);
        }

        return $questionIds;
    }

    /**
     * @param array<int, mixed> $relations
     *
     * @return array<int, int>
     */
    private function buildOrderedQuestionIds(array $relations, bool $skipStructuralQuestions): array
    {
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

            if ($skipStructuralQuestions && self::PAGE_BREAK === $type) {
                continue;
            }

            $questionIds[] = (int) $question->getIid();
        }

        return $questionIds;
    }

    /**
     * @param array<int, mixed> $relations
     *
     * @return array<int, int>
     */
    private function buildCategoryMatrixQuestionList(CQuiz $quiz, array $relations, int $selectionType): array
    {
        $categoryRows = $this->getExerciseCategoryRows((int) ($quiz->getIid() ?? 0));
        if ([] === $categoryRows) {
            return $this->buildOrderedQuestionIds($relations, true);
        }

        $questionsByCategory = $this->groupQuestionsByCategory($relations, true);
        if ([] === $questionsByCategory) {
            return $this->buildOrderedQuestionIds($relations, true);
        }

        if (\in_array($selectionType, [
            self::QUESTION_SELECTION_CATEGORIES_ORDERED_QUESTIONS_ORDERED,
            self::QUESTION_SELECTION_CATEGORIES_ORDERED_QUESTIONS_RANDOM,
        ], true)) {
            uasort(
                $categoryRows,
                static fn (array $left, array $right): int => strcasecmp((string) $left['title'], (string) $right['title'])
            );
        } else {
            shuffle($categoryRows);
        }

        $randomizeQuestions = \in_array($selectionType, [
            self::QUESTION_SELECTION_CATEGORIES_ORDERED_QUESTIONS_RANDOM,
            self::QUESTION_SELECTION_CATEGORIES_RANDOM_QUESTIONS_RANDOM,
        ], true);

        $questionIds = [];
        foreach ($categoryRows as $categoryRow) {
            $categoryId = (int) $categoryRow['categoryId'];
            $countQuestions = (int) $categoryRow['countQuestions'];
            if (0 === $countQuestions || !isset($questionsByCategory[$categoryId])) {
                continue;
            }

            $categoryQuestionIds = $questionsByCategory[$categoryId];
            $mandatoryQuestionIds = $this->getMandatoryQuestionIdsForCategory($quiz, $categoryId, $selectionType);
            if ($randomizeQuestions) {
                shuffle($categoryQuestionIds);
            }

            if (-1 !== $countQuestions) {
                $categoryQuestionIds = $this->pickCategoryQuestions(
                    $categoryQuestionIds,
                    max(0, $countQuestions),
                    $randomizeQuestions,
                    $mandatoryQuestionIds
                );
            }

            foreach ($categoryQuestionIds as $questionId) {
                if (\in_array($questionId, $questionIds, true)) {
                    continue;
                }

                $questionIds[] = $questionId;
            }
        }

        if ([] === $questionIds) {
            return $this->buildOrderedQuestionIds($relations, true);
        }

        return $questionIds;
    }

    /**
     * @param array<int, mixed> $relations
     *
     * @return array<int, int>
     */
    private function buildRandomByCategoryQuestionList(CQuiz $quiz, array $relations, int $randomCount): array
    {
        $questionsByCategory = $this->groupQuestionsByCategory($relations, true);
        if ([] === $questionsByCategory) {
            return $this->buildOrderedQuestionIds($relations, true);
        }

        $categoryRows = [];
        foreach (array_keys($questionsByCategory) as $categoryId) {
            $categoryRows[] = [
                'categoryId' => (int) $categoryId,
                'title' => $this->getCategoryTitle((int) $categoryId),
            ];
        }

        if (2 === (int) $quiz->getRandomByCategory()) {
            uasort(
                $categoryRows,
                static fn (array $left, array $right): int => strcasecmp((string) $left['title'], (string) $right['title'])
            );
        }

        $questionIds = [];
        foreach ($categoryRows as $categoryRow) {
            $categoryQuestionIds = $questionsByCategory[(int) $categoryRow['categoryId']] ?? [];
            if ([] === $categoryQuestionIds) {
                continue;
            }

            shuffle($categoryQuestionIds);
            if (-1 !== $randomCount) {
                $categoryQuestionIds = \array_slice($categoryQuestionIds, 0, max(0, $randomCount));
            }

            foreach ($categoryQuestionIds as $questionId) {
                if (\in_array($questionId, $questionIds, true)) {
                    continue;
                }

                $questionIds[] = $questionId;
            }
        }

        if (1 === (int) $quiz->getRandomByCategory()) {
            shuffle($questionIds);
        }

        if ([] === $questionIds) {
            return $this->buildOrderedQuestionIds($relations, true);
        }

        return $questionIds;
    }

    /**
     * @param array<int, int> $questionIds
     * @param array<int, int> $mandatoryQuestionIds
     *
     * @return array<int, int>
     */
    private function pickCategoryQuestions(array $questionIds, int $countQuestions, bool $randomizeQuestions, array $mandatoryQuestionIds): array
    {
        if ([] === $mandatoryQuestionIds) {
            return \array_slice($questionIds, 0, $countQuestions);
        }

        $mandatoryQuestionIds = array_values(array_intersect($mandatoryQuestionIds, $questionIds));
        if (\count($mandatoryQuestionIds) >= $countQuestions) {
            if ($randomizeQuestions) {
                shuffle($mandatoryQuestionIds);
            }

            return \array_slice($mandatoryQuestionIds, 0, $countQuestions);
        }

        $remainingQuestionIds = array_values(array_diff($questionIds, $mandatoryQuestionIds));
        if ($randomizeQuestions) {
            shuffle($remainingQuestionIds);
        }

        $selectedQuestionIds = array_merge(
            $mandatoryQuestionIds,
            \array_slice($remainingQuestionIds, 0, $countQuestions - \count($mandatoryQuestionIds))
        );

        if ($randomizeQuestions) {
            shuffle($selectedQuestionIds);
        }

        return $selectedQuestionIds;
    }

    /**
     * @return array<int, int>
     */
    private function getMandatoryQuestionIdsForCategory(CQuiz $quiz, int $categoryId, int $selectionType): array
    {
        if (self::QUESTION_SELECTION_CATEGORIES_ORDERED_QUESTIONS_RANDOM !== $selectionType) {
            return [];
        }

        if ('true' !== $this->settingsManager->getSetting('exercise.allow_mandatory_question_in_category', true)
            || !$this->hasMandatoryQuestionCategoryColumn()
        ) {
            return [];
        }

        $rows = $this->entityManager->getConnection()->fetchFirstColumn(
            'SELECT qrc.question_id '
            .'FROM c_quiz_question_rel_category qrc '
            .'INNER JOIN c_quiz_rel_question rel ON rel.question_id = qrc.question_id '
            .'WHERE rel.quiz_id = :exerciseId AND qrc.category_id = :categoryId AND qrc.mandatory = 1',
            [
                'exerciseId' => (int) ($quiz->getIid() ?? 0),
                'categoryId' => $categoryId,
            ],
            [
                'exerciseId' => Types::INTEGER,
                'categoryId' => Types::INTEGER,
            ]
        );

        return array_values(array_map(static fn (mixed $questionId): int => (int) $questionId, $rows));
    }

    private function hasMandatoryQuestionCategoryColumn(): bool
    {
        try {
            return $this->entityManager
                ->getConnection()
                ->createSchemaManager()
                ->introspectTable('c_quiz_question_rel_category')
                ->hasColumn('mandatory');
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @param array<int, mixed> $relations
     *
     * @return array<int, array<int, int>>
     */
    private function groupQuestionsByCategory(array $relations, bool $skipStructuralQuestions): array
    {
        $questionsByCategory = [];
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

            if ($skipStructuralQuestions && self::PAGE_BREAK === $type) {
                continue;
            }

            $questionId = (int) $question->getIid();
            $categories = $question->getCategories();
            if (0 === $categories->count()) {
                $questionsByCategory[0][] = $questionId;
                continue;
            }

            foreach ($categories as $category) {
                $categoryId = (int) ($category->getIid() ?? 0);
                $questionsByCategory[$categoryId][] = $questionId;
            }
        }

        return $questionsByCategory;
    }

    /**
     * @return array<int, array{categoryId: int, title: string, countQuestions: int}>
     */
    private function getExerciseCategoryRows(int $exerciseId): array
    {
        if (0 >= $exerciseId) {
            return [];
        }

        $rows = $this->entityManager->getConnection()->fetchAllAssociative(
            'SELECT rel.category_id, rel.count_questions, category.title '
            .'FROM c_quiz_rel_category rel '
            .'LEFT JOIN c_quiz_question_category category ON category.iid = rel.category_id '
            .'WHERE rel.exercise_id = :exerciseId',
            ['exerciseId' => $exerciseId],
            ['exerciseId' => Types::INTEGER]
        );

        $categories = [];
        foreach ($rows as $row) {
            $categoryId = (int) $row['category_id'];
            $categories[] = [
                'categoryId' => $categoryId,
                'title' => 0 === $categoryId ? 'General' : (string) ($row['title'] ?? ''),
                'countQuestions' => (int) $row['count_questions'],
            ];
        }

        return $categories;
    }

    private function getCategoryTitle(int $categoryId): string
    {
        if (0 === $categoryId) {
            return 'General';
        }

        $row = $this->entityManager->getConnection()->fetchAssociative(
            'SELECT title FROM c_quiz_question_category WHERE iid = :categoryId',
            ['categoryId' => $categoryId],
            ['categoryId' => Types::INTEGER]
        );

        return \is_array($row) ? (string) ($row['title'] ?? '') : '';
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

    /**
     * @param array<int, int> $questionIds
     */
    private function isQuestionLimitPerDayReached(array $questionIds, Course $course, ?Session $session, User $user): bool
    {
        $limit = $this->getCourseSettingInt($course, 'quiz_question_limit_per_day');
        if (0 >= $limit || [] === $questionIds) {
            return false;
        }

        if (null !== $session) {
            $answeredQuestionsCount = (int) $this->entityManager->createQueryBuilder()
                ->select('COUNT(attempt.id)')
                ->from(TrackEAttempt::class, 'attempt')
                ->innerJoin('attempt.trackExercise', 'track')
                ->andWhere('IDENTITY(attempt.user) = :userId')
                ->andWhere('IDENTITY(track.course) = :courseId')
                ->andWhere('IDENTITY(track.session) = :sessionId')
                ->andWhere('attempt.tms > :midnight')
                ->setParameter('userId', (int) $user->getId(), Types::INTEGER)
                ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
                ->setParameter('sessionId', (int) $session->getId(), Types::INTEGER)
                ->setParameter('midnight', new DateTime('today'), Types::DATETIME_MUTABLE)
                ->getQuery()
                ->getSingleScalarResult()
            ;
        } else {
            $answeredQuestionsCount = (int) $this->entityManager->createQueryBuilder()
                ->select('COUNT(attempt.id)')
                ->from(TrackEAttempt::class, 'attempt')
                ->innerJoin('attempt.trackExercise', 'track')
                ->andWhere('IDENTITY(attempt.user) = :userId')
                ->andWhere('IDENTITY(track.course) = :courseId')
                ->andWhere('track.session IS NULL')
                ->andWhere('attempt.tms > :midnight')
                ->setParameter('userId', (int) $user->getId(), Types::INTEGER)
                ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
                ->setParameter('midnight', new DateTime('today'), Types::DATETIME_MUTABLE)
                ->getQuery()
                ->getSingleScalarResult()
            ;
        }

        return ($answeredQuestionsCount + \count($questionIds)) > $limit;
    }

    private function getCourseSettingInt(Course $course, string $settingName): int
    {
        if (!\function_exists('api_get_course_setting')) {
            return 0;
        }

        return (int) api_get_course_setting($settingName, $course);
    }

    private function isSettingEnabled(string $name): bool
    {
        return 'true' === $this->settingsManager->getSetting($name, true);
    }

    private function buildExpiredAt(CQuiz $quiz): ?DateTime
    {
        $expiredMinutes = (int) $quiz->getExpiredTime();
        if (0 >= $expiredMinutes) {
            return null;
        }

        return (new DateTime())->modify(sprintf('+%d minutes', $expiredMinutes));
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

    /**
     * @param array<int, int> $questionIds
     */
    private function createBlockedResponse(CQuiz $quiz, Course $course, ?Session $session, Request $request, string $message, array $questionIds): ExerciseRuntimeAttempt
    {
        $response = $this->createBaseResponse($quiz, $course, $session, $request, $questionIds);
        $response->success = false;
        $response->usesLegacyRuntime = false;
        $response->message = $message;
        $response->status = 'blocked';
        $response->canFinish = false;

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
        $response->attemptNumber = $this->getAttemptNumber($attempt);
        $response->status = method_exists($attempt, 'getStatus') ? (string) $attempt->getStatus() : self::STATUS_INCOMPLETE;
        $response->message = $message;
        $response->savedAnswers = $this->getSavedAnswers((int) $attempt->getExeId());
        $response->currentQuestionIndex = $this->getResumeQuestionIndex($attempt, $quiz, $questionIds);
        $response->currentQuestionId = $questionIds[$response->currentQuestionIndex] ?? ($questionIds[0] ?? null);

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
    private function getResumeQuestionIndex(TrackEExercise $attempt, CQuiz $quiz, array $questionIds): int
    {
        if (1 !== (int) $quiz->getPreventBackwards()) {
            return 0;
        }

        if ([] === $questionIds) {
            return 0;
        }

        $stepsCounter = max(0, (int) $attempt->getStepsCounter());
        if (0 >= $stepsCounter) {
            return 0;
        }

        return min($stepsCounter, \count($questionIds) - 1);
    }

    private function getAttemptNumber(TrackEExercise $attempt): int
    {
        $quiz = $attempt->getQuiz();
        $attemptId = (int) $attempt->getExeId();
        if (null === $quiz || null === $quiz->getIid() || 0 >= $attemptId) {
            return 0;
        }

        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('COUNT(previousAttempt.exeId)')
            ->from(TrackEExercise::class, 'previousAttempt')
            ->andWhere('IDENTITY(previousAttempt.quiz) = :exerciseId')
            ->andWhere('IDENTITY(previousAttempt.course) = :courseId')
            ->andWhere('IDENTITY(previousAttempt.user) = :userId')
            ->andWhere('previousAttempt.exeId <= :attemptId')
            ->andWhere('previousAttempt.origLpId = :lpId')
            ->andWhere('previousAttempt.origLpItemId = :lpItemId')
            ->andWhere('previousAttempt.origLpItemViewId = :lpItemViewId')
            ->setParameter('exerciseId', (int) $quiz->getIid(), Types::INTEGER)
            ->setParameter('courseId', (int) $attempt->getCourse()->getId(), Types::INTEGER)
            ->setParameter('userId', (int) $attempt->getUser()->getId(), Types::INTEGER)
            ->setParameter('attemptId', $attemptId, Types::INTEGER)
            ->setParameter('lpId', (int) $attempt->getOrigLpId(), Types::INTEGER)
            ->setParameter('lpItemId', (int) $attempt->getOrigLpItemId(), Types::INTEGER)
            ->setParameter('lpItemViewId', (int) $attempt->getOrigLpItemViewId(), Types::INTEGER)
        ;

        $session = $attempt->getSession();
        if (null !== $session) {
            $queryBuilder
                ->andWhere('IDENTITY(previousAttempt.session) = :sessionId')
                ->setParameter('sessionId', (int) $session->getId(), Types::INTEGER)
            ;
        } else {
            $queryBuilder->andWhere('previousAttempt.session IS NULL');
        }

        return (int) $queryBuilder->getQuery()->getSingleScalarResult();
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
     * @return array<int|string, array<int, array{answer: string, position: int|null, secondsSpent: int}>>
     */
    private function getSavedAnswers(int $attemptId): array
    {
        $rows = $this->entityManager->createQueryBuilder()
            ->select('saved.questionId AS questionId')
            ->addSelect('saved.answer AS answer')
            ->addSelect('saved.position AS position')
            ->addSelect('saved.secondsSpent AS secondsSpent')
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
                'secondsSpent' => (int) ($row['secondsSpent'] ?? 0),
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
