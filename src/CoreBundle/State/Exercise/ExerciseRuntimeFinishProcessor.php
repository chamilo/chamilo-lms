<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Exercise;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\Exercise\ExerciseRuntimeFinish;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\TrackEAttempt;
use Chamilo\CoreBundle\Entity\TrackEExercise;
use Chamilo\CoreBundle\Entity\TrackEExerciseConfirmation;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CLpItem;
use Chamilo\CourseBundle\Entity\CLpItemView;
use Chamilo\CourseBundle\Entity\CLpView;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Entity\CQuizAnswer;
use Chamilo\CourseBundle\Entity\CQuizDestinationResult;
use Chamilo\CourseBundle\Entity\CQuizQuestion;
use Chamilo\CourseBundle\Entity\CQuizQuestionCategory;
use Chamilo\CourseBundle\Entity\CQuizQuestionOption;
use Chamilo\CourseBundle\Entity\CQuizRelQuestion;
use Chamilo\CourseBundle\Repository\CQuizRepository;
use DateTime;
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
 * Finishes Vue runtime attempts using native Symfony/Doctrine scoring rules mirrored from the verified legacy rules.
 *
 * @implements ProcessorInterface<ExerciseRuntimeFinish, ExerciseRuntimeFinish>
 */
final readonly class ExerciseRuntimeFinishProcessor implements ProcessorInterface
{
    private const VISIBILITY_PUBLISHED = 2;
    private const STATUS_INCOMPLETE = 'incomplete';
    private const STATUS_COMPLETED = 'completed';
    private const LP_ITEM_TYPE_QUIZ = 'quiz';
    private const LP_STATUS_FAILED = 'failed';
    private const LP_STATUS_PASSED = 'passed';
    private const FEEDBACK_TYPE_DIRECT = 1;
    private const FEEDBACK_TYPE_PROGRESSIVE_ADAPTIVE = 3;
    private const UNIQUE_ANSWER = 1;
    private const MULTIPLE_ANSWER = 2;
    private const FILL_IN_BLANKS = 3;
    private const MATCHING = 4;
    private const FREE_ANSWER = 5;
    private const HOT_SPOT = 6;
    private const HOT_SPOT_DELINEATION = 8;
    private const CALCULATED_ANSWER = 16;
    private const DRAGGABLE = 18;
    private const MEDIA_QUESTION = 15;
    private const READING_COMPREHENSION = 21;
    private const PAGE_BREAK = 31;
    private const ORAL_EXPRESSION = 13;
    private const UPLOAD_ANSWER = 23;
    private const ANSWER_IN_OFFICE_DOC = 30;
    private const ANNOTATION = 20;
    private const MULTIPLE_ANSWER_COMBINATION = 9;
    private const UNIQUE_ANSWER_NO_OPTION = 10;
    private const MULTIPLE_ANSWER_TRUE_FALSE = 11;
    private const MULTIPLE_ANSWER_COMBINATION_TRUE_FALSE = 12;
    private const MULTIPLE_ANSWER_TRUE_FALSE_DEGREE_CERTAINTY = 22;
    private const GLOBAL_MULTIPLE_ANSWER = 14;
    private const UNIQUE_ANSWER_IMAGE = 17;
    private const MATCHING_DRAGGABLE = 19;
    private const FILL_IN_BLANKS_COMBINATION = 27;
    private const MULTIPLE_ANSWER_DROPDOWN_COMBINATION = 28;
    private const MULTIPLE_ANSWER_DROPDOWN = 29;
    private const MATCHING_COMBINATION = 24;
    private const MATCHING_DRAGGABLE_COMBINATION = 25;
    private const HOT_SPOT_COMBINATION = 26;

    /**
     * @var array<int, string>
     */
    private const SUPPORTED_TYPE_NAMES = [
        self::UNIQUE_ANSWER => 'Unique answer',
        self::UNIQUE_ANSWER_NO_OPTION => 'Unique answer no option',
        self::UNIQUE_ANSWER_IMAGE => 'Unique answer with images',
        self::MULTIPLE_ANSWER => 'Multiple answer',
        self::GLOBAL_MULTIPLE_ANSWER => 'Global multiple answer',
        self::MULTIPLE_ANSWER_COMBINATION => 'Multiple answer combination',
        self::MULTIPLE_ANSWER_TRUE_FALSE => 'Multiple answer true/false',
        self::MULTIPLE_ANSWER_COMBINATION_TRUE_FALSE => 'Multiple answer combination true/false',
        self::MULTIPLE_ANSWER_TRUE_FALSE_DEGREE_CERTAINTY => 'Multiple answer true/false with degree of certainty',
        self::FILL_IN_BLANKS => 'Fill in blanks',
        self::FILL_IN_BLANKS_COMBINATION => 'Fill in blanks combination',
        self::MATCHING => 'Matching',
        self::MATCHING_DRAGGABLE => 'Matching draggable',
        self::MATCHING_COMBINATION => 'Matching combination',
        self::MATCHING_DRAGGABLE_COMBINATION => 'Matching draggable combination',
        self::HOT_SPOT_COMBINATION => 'Hotspot combination',
        self::HOT_SPOT_DELINEATION => 'Hotspot delineation',
        self::MULTIPLE_ANSWER_DROPDOWN => 'Multiple answer dropdown',
        self::MULTIPLE_ANSWER_DROPDOWN_COMBINATION => 'Multiple answer dropdown combination',
        self::FREE_ANSWER => 'Free answer',
        self::HOT_SPOT => 'Hotspot',
        self::CALCULATED_ANSWER => 'Calculated answer',
        self::DRAGGABLE => 'Sequence ordering',
        self::ORAL_EXPRESSION => 'Oral expression',
        self::UPLOAD_ANSWER => 'Upload answer',
        self::ANSWER_IN_OFFICE_DOC => 'Answer in Office document',
        self::ANNOTATION => 'Annotation',
        self::MEDIA_QUESTION => 'Media question',
        self::READING_COMPREHENSION => 'Reading comprehension',
        self::PAGE_BREAK => 'Page break',
    ];

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
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ExerciseRuntimeFinish
    {
        if (!$data instanceof ExerciseRuntimeFinish) {
            throw new BadRequestHttpException('Invalid exercise runtime finish payload.');
        }

        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        if (!$this->canFinishAttempt()) {
            throw new AccessDeniedHttpException('You are not allowed to finish this exercise attempt.');
        }

        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedHttpException('A valid authenticated user is required.');
        }

        $course = $this->getCourse($request);
        $session = $this->getSession($request);
        $exerciseId = isset($uriVariables['exerciseId']) ? (int) $uriVariables['exerciseId'] : (int) ($data->exerciseId ?? 0);
        $attemptId = isset($uriVariables['attemptId']) ? (int) $uriVariables['attemptId'] : (int) ($data->attemptId ?? 0);

        if (0 >= $exerciseId || 0 >= $attemptId) {
            throw new BadRequestHttpException('A valid exercise and attempt are required.');
        }

        $quiz = $this->getExerciseFromCurrentContext($exerciseId, $course, $session, $this->canManageExercises());
        $attempt = $this->getIncompleteAttempt($attemptId, $quiz, $course, $session, $user);
        $questionIds = $this->parseQuestionIds((string) $attempt->getDataTracking());
        if ([] === $questionIds) {
            throw new BadRequestHttpException('The attempt does not contain a persisted question list.');
        }

        $questionIds = $this->normalizeCompletedQuestionIds($quiz, $attempt, $questionIds);
        if ([] === $questionIds) {
            throw new BadRequestHttpException('The attempt does not contain answered questions.');
        }

        $questions = $this->getQuestions($quiz, $questionIds);
        $unsupportedTypes = $this->getUnsupportedQuestionTypes($questions);
        if ([] !== $unsupportedTypes) {
            throw new BadRequestHttpException('This attempt contains question types that are not supported by the Vue finish scorer yet: '.implode(', ', $unsupportedTypes).'.');
        }

        $totalScore = 0.0;
        $totalWeight = 0.0;
        $questionsToCheck = [];

        foreach ($questionIds as $questionId) {
            $question = $questions[$questionId] ?? null;
            if (!$question instanceof CQuizQuestion) {
                continue;
            }

            $rows = $this->getAttemptRows($attemptId, $questionId);
            $answers = $this->getQuestionAnswers($questionId);
            $options = $this->getQuestionOptions($questionId);
            $score = $this->scoreQuestion($quiz, $question, $answers, $options, $rows);
            $weight = $this->getQuestionWeight($question, $answers);

            if (0 === (int) $quiz->getPropagateNeg() && 0 > $score) {
                $score = 0.0;
            }

            $this->updateQuestionAttemptRows($question, $rows, $score);

            if ($this->requiresManualCorrection($question)) {
                $questionsToCheck[] = $questionId;
            }

            $totalScore += $score;
            $totalWeight += $weight;
        }

        if (0.0 >= $totalWeight) {
            $totalWeight = (float) $attempt->getMaxScore();
        }

        $finishedAt = $this->getFinishedAt($attempt);
        $duration = max(0, $finishedAt->getTimestamp() - $attempt->getStartDate()->getTimestamp());

        $attempt
            ->setScore($totalScore)
            ->setMaxScore($totalWeight)
            ->setDataTracking(implode(',', $questionIds))
            ->setStatus(self::STATUS_COMPLETED)
            ->setExeDate($finishedAt)
            ->setExeDuration($duration)
            ->setQuestionsToCheck(implode(',', $questionsToCheck))
        ;

        $this->recordProgressiveAdaptiveResult($quiz, $attempt, $questions, $questionIds, $user);
        $this->recordSavedAnswersConfirmation($data, $attempt, $quiz, $course, $session, $user, \count($questionIds));
        $learnpathTracking = $this->synchronizeLearnpathTracking($request, $attempt, $quiz, $course, $session, $user);

        $this->entityManager->flush();

        $response = new ExerciseRuntimeFinish();
        $response->exerciseId = $exerciseId;
        $response->attemptId = $attemptId;
        $response->success = true;
        $response->message = 'Attempt finished';
        $response->status = self::STATUS_COMPLETED;
        $response->score = $totalScore;
        $response->maxScore = $totalWeight;
        $response->completedAt = $finishedAt->format(DateTimeInterface::ATOM);
        $response->resultUrl = '';
        $response->learnpathTracking = $learnpathTracking;

        return $response;
    }


    /**
     * @return array<string, mixed>
     */
    private function synchronizeLearnpathTracking(
        Request $request,
        TrackEExercise $attempt,
        CQuiz $quiz,
        Course $course,
        ?Session $session,
        User $user,
    ): array {
        if (!$this->isLearnpathRuntimeRequest($request, $attempt)) {
            return [];
        }

        $lpId = $this->getPositiveQueryInt($request, 'learnpath_id', $attempt->getOrigLpId());
        $lpItemId = $this->getPositiveQueryInt($request, 'learnpath_item_id', $attempt->getOrigLpItemId());
        $lpItemViewId = $this->getPositiveQueryInt($request, 'learnpath_item_view_id', $attempt->getOrigLpItemViewId());

        if (0 >= $lpId || 0 >= $lpItemId) {
            return [];
        }

        $lpItem = $this->getValidExerciseLpItem($lpItemId, $lpId, $quiz);
        if (!$lpItem instanceof CLpItem) {
            return [];
        }

        $lpItemView = $this->getLpItemViewForCurrentUser($lpItem, $lpItemViewId, $lpId, $course, $session, $user);
        if (!$lpItemView instanceof CLpItemView) {
            return [];
        }

        $lpView = $lpItemView->getView();
        $status = $this->getLearnpathExerciseStatus($quiz, $attempt->getScore(), $attempt->getMaxScore());

        $lpItem->setMaxScore((float) $attempt->getMaxScore());
        $lpItemView
            ->setStatus($status)
            ->setScore((float) $attempt->getScore())
            ->setTotalTime((int) $attempt->getExeDuration())
        ;

        $attempt
            ->setOrigLpId($lpId)
            ->setOrigLpItemId($lpItemId)
            ->setOrigLpItemViewId((int) $lpItemView->getIid())
        ;

        $lpView->setLastItem($lpItemId);
        $progressData = $this->updateLearnpathProgress($lpView);

        return [
            'enabled' => true,
            'lpId' => $lpId,
            'lpItemId' => $lpItemId,
            'lpItemViewId' => (int) $lpItemView->getIid(),
            'lpViewId' => (int) $lpView->getIid(),
            'status' => $status,
            'completedItems' => $progressData['completedItems'],
            'totalItems' => $progressData['totalItems'],
            'progress' => $progressData['progress'],
            'progressMode' => $progressData['progressMode'],
        ];
    }

    private function isLearnpathRuntimeRequest(Request $request, TrackEExercise $attempt): bool
    {
        $origin = (string) $request->query->get('origin', '');

        return 'learnpath' === $origin
            || $request->query->has('lp_init')
            || $request->query->has('learnpath_id')
            || $request->query->has('learnpath_item_id')
            || $request->query->has('learnpath_item_view_id')
            || 0 < (int) $attempt->getOrigLpId()
            || 0 < (int) $attempt->getOrigLpItemId()
            || 0 < (int) $attempt->getOrigLpItemViewId();
    }

    private function getPositiveQueryInt(Request $request, string $name, int $fallback = 0): int
    {
        $value = $request->query->get($name);
        if (null !== $value) {
            $value = \is_array($value) ? reset($value) : $value;
            if (is_numeric($value)) {
                $intValue = (int) $value;
                if (0 < $intValue) {
                    return $intValue;
                }
            }
        }

        return 0 < $fallback ? $fallback : 0;
    }

    private function getValidExerciseLpItem(int $lpItemId, int $lpId, CQuiz $quiz): ?CLpItem
    {
        $lpItem = $this->entityManager->getRepository(CLpItem::class)->find($lpItemId);
        if (!$lpItem instanceof CLpItem) {
            return null;
        }

        if ((int) ($lpItem->getLp()->getIid() ?? 0) !== $lpId) {
            return null;
        }

        if (self::LP_ITEM_TYPE_QUIZ !== (string) $lpItem->getItemType()) {
            return null;
        }

        if ((int) $lpItem->getPath() !== (int) ($quiz->getIid() ?? 0)) {
            return null;
        }

        return $lpItem;
    }

    private function getLpItemViewForCurrentUser(
        CLpItem $lpItem,
        int $lpItemViewId,
        int $lpId,
        Course $course,
        ?Session $session,
        User $user,
    ): ?CLpItemView {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('itemView')
            ->addSelect('lpView')
            ->from(CLpItemView::class, 'itemView')
            ->innerJoin('itemView.view', 'lpView')
            ->andWhere('IDENTITY(itemView.item) = :lpItemId')
            ->andWhere('IDENTITY(lpView.lp) = :lpId')
            ->andWhere('IDENTITY(lpView.course) = :courseId')
            ->andWhere('IDENTITY(lpView.user) = :userId')
            ->setParameter('lpItemId', (int) $lpItem->getIid(), Types::INTEGER)
            ->setParameter('lpId', $lpId, Types::INTEGER)
            ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
            ->setParameter('userId', (int) $user->getId(), Types::INTEGER)
            ->setMaxResults(1)
        ;

        if (0 < $lpItemViewId) {
            $queryBuilder
                ->andWhere('itemView.iid = :lpItemViewId')
                ->setParameter('lpItemViewId', $lpItemViewId, Types::INTEGER)
            ;
        }

        if (null !== $session) {
            $queryBuilder
                ->andWhere('IDENTITY(lpView.session) = :sessionId')
                ->setParameter('sessionId', (int) $session->getId(), Types::INTEGER)
            ;
        } else {
            $queryBuilder->andWhere('lpView.session IS NULL');
        }

        $queryBuilder->orderBy('itemView.iid', 'DESC');

        $lpItemView = $queryBuilder->getQuery()->getOneOrNullResult();

        return $lpItemView instanceof CLpItemView ? $lpItemView : null;
    }

    private function getLearnpathExerciseStatus(CQuiz $quiz, float $score, float $maxScore): string
    {
        $status = self::STATUS_COMPLETED;
        $passPercentage = $quiz->getPassPercentage();

        if (self::FEEDBACK_TYPE_DIRECT !== (int) $quiz->getFeedbackType() && null !== $passPercentage && 0 < $passPercentage) {
            $status = self::LP_STATUS_FAILED;
            $percentage = 0.0 < $maxScore ? ($score / $maxScore) * 100 : 0.0;
            if ($percentage >= (float) $passPercentage) {
                $status = self::LP_STATUS_PASSED;
            }
        }

        return $status;
    }

    /**
     * @return array{completedItems: int, totalItems: int, progress: int, progressMode: string}
     */
    private function updateLearnpathProgress(CLpView $lpView): array
    {
        $lpId = (int) ($lpView->getLp()->getIid() ?? 0);
        if (0 >= $lpId) {
            return ['completedItems' => 0, 'totalItems' => 0, 'progress' => 0, 'progressMode' => '%'];
        }

        $totalItems = (int) $this->entityManager->createQueryBuilder()
            ->select('COUNT(item.iid)')
            ->from(CLpItem::class, 'item')
            ->andWhere('IDENTITY(item.lp) = :lpId')
            ->andWhere('item.itemType != :directoryType')
            ->setParameter('lpId', $lpId, Types::INTEGER)
            ->setParameter('directoryType', 'dir')
            ->getQuery()
            ->getSingleScalarResult()
        ;

        if (0 >= $totalItems) {
            $lpView->setProgress(0);

            return ['completedItems' => 0, 'totalItems' => 0, 'progress' => 0, 'progressMode' => '%'];
        }

        $completedItems = (int) $this->entityManager->createQueryBuilder()
            ->select('COUNT(DISTINCT item.iid)')
            ->from(CLpItemView::class, 'itemView')
            ->innerJoin('itemView.item', 'item')
            ->andWhere('IDENTITY(itemView.view) = :lpViewId')
            ->andWhere('item.itemType != :directoryType')
            ->andWhere('itemView.status IN (:completedStatuses)')
            ->setParameter('lpViewId', (int) $lpView->getIid(), Types::INTEGER)
            ->setParameter('directoryType', 'dir')
            ->setParameter('completedStatuses', [self::STATUS_COMPLETED, self::LP_STATUS_PASSED, 'succeeded', 'browsed', self::LP_STATUS_FAILED], ArrayParameterType::STRING)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        $progress = max(0, min(100, (int) round(($completedItems / $totalItems) * 100)));
        $lpView->setProgress($progress);

        return [
            'completedItems' => $completedItems,
            'totalItems' => $totalItems,
            'progress' => $progress,
            'progressMode' => '%',
        ];
    }

    private function recordSavedAnswersConfirmation(
        ExerciseRuntimeFinish $data,
        TrackEExercise $attempt,
        CQuiz $quiz,
        Course $course,
        ?Session $session,
        User $user,
        int $questionsCount,
    ): void {
        if ('true' !== $this->settingsManager->getSetting('exercise.quiz_confirm_saved_answers', true)) {
            return;
        }

        $attemptId = (int) $attempt->getExeId();
        if (0 >= $attemptId) {
            return;
        }

        $savedAnswersCount = (int) $this->entityManager->createQueryBuilder()
            ->select('COUNT(DISTINCT answer.questionId)')
            ->from(TrackEAttempt::class, 'answer')
            ->andWhere('IDENTITY(answer.trackExercise) = :attemptId')
            ->setParameter('attemptId', $attemptId, Types::INTEGER)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        $confirmation = (new TrackEExerciseConfirmation())
            ->setUser($user)
            ->setQuizId((int) ($quiz->getIid() ?? 0))
            ->setAttemptId($attemptId)
            ->setQuestionsCount($questionsCount)
            ->setSavedAnswersCount($savedAnswersCount)
            ->setCourseId((int) $course->getId())
            ->setSessionId((int) ($session?->getId() ?? 0))
            ->setConfirmed(true === $data->confirmedSavedAnswers)
        ;

        $this->entityManager->persist($confirmation);
    }

    private function canFinishAttempt(): bool
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
            $now = new DateTime();
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
     * @param array<int, int> $questionIds
     *
     * @return array<int, CQuizQuestion>
     */
    private function getQuestions(CQuiz $quiz, array $questionIds): array
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

            $questions[(int) $question->getIid()] = $question;
        }

        return $questions;
    }

    /**
     * @param array<int, CQuizQuestion> $questions
     *
     * @return array<int, string>
     */
    private function getUnsupportedQuestionTypes(array $questions): array
    {
        $unsupportedTypes = [];
        foreach ($questions as $question) {
            $type = (int) $question->getType();
            if (!$this->isSupportedQuestionType($type)) {
                $unsupportedTypes[$type] = self::SUPPORTED_TYPE_NAMES[$type] ?? (string) $type;
            }
        }

        return $unsupportedTypes;
    }

    private function isSupportedQuestionType(int $type): bool
    {
        return isset(self::SUPPORTED_TYPE_NAMES[$type]);
    }

    /**
     * In direct feedback/adaptive exercises, only questions really reached by the learner must be scored.
     * Branch-only questions that were not visited must not lower the final score.
     *
     * @param array<int, int> $questionIds
     *
     * @return array<int, int>
     */
    private function normalizeCompletedQuestionIds(CQuiz $quiz, TrackEExercise $attempt, array $questionIds): array
    {
        if (!\in_array((int) $quiz->getFeedbackType(), [self::FEEDBACK_TYPE_DIRECT, self::FEEDBACK_TYPE_PROGRESSIVE_ADAPTIVE], true)) {
            return $questionIds;
        }

        $answeredQuestionIds = $this->getAnsweredQuestionIds((int) $attempt->getExeId());
        if ([] === $answeredQuestionIds) {
            return $questionIds;
        }

        return array_values(array_filter(
            $questionIds,
            static fn (int $questionId): bool => \in_array($questionId, $answeredQuestionIds, true)
        ));
    }

    /**
     * @param array<int, CQuizQuestion> $questions
     * @param array<int, int> $questionIds
     */
    private function recordProgressiveAdaptiveResult(CQuiz $quiz, TrackEExercise $attempt, array $questions, array $questionIds, User $user): void
    {
        if (self::FEEDBACK_TYPE_PROGRESSIVE_ADAPTIVE !== (int) $quiz->getFeedbackType()) {
            return;
        }

        $achievedLevel = $this->resolveProgressiveAdaptiveAchievedLevel($questions, $questionIds);
        if ('' === $achievedLevel) {
            return;
        }

        $destinationResult = $this->entityManager->getRepository(CQuizDestinationResult::class)->findOneBy([
            'exe' => $attempt,
        ]);

        if (!$destinationResult instanceof CQuizDestinationResult) {
            $destinationResult = (new CQuizDestinationResult())
                ->setExe($attempt)
                ->setUser($user)
                ->setHash(hash('sha256', uniqid((string) $attempt->getExeId(), true)))
            ;
        }

        $destinationResult->setAchievedLevel($achievedLevel);
        $this->entityManager->persist($destinationResult);
    }

    /**
     * @param array<int, CQuizQuestion> $questions
     * @param array<int, int> $questionIds
     */
    private function resolveProgressiveAdaptiveAchievedLevel(array $questions, array $questionIds): string
    {
        for ($index = \count($questionIds) - 1; $index >= 0; $index--) {
            $question = $questions[$questionIds[$index]] ?? null;
            if (!$question instanceof CQuizQuestion) {
                continue;
            }

            $category = $this->getPrimaryQuestionCategory($question);
            if ($category instanceof CQuizQuestionCategory) {
                return $category->getTitle();
            }
        }

        return '';
    }

    private function getPrimaryQuestionCategory(CQuizQuestion $question): ?CQuizQuestionCategory
    {
        foreach ($question->getCategories() as $category) {
            if ($category instanceof CQuizQuestionCategory) {
                return $category;
            }
        }

        return null;
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
            ->getQuery()
            ->getArrayResult()
        ;

        $questionIds = [];
        foreach ($rows as $row) {
            if (!\is_array($row)) {
                continue;
            }

            $questionId = (int) ($row['questionId'] ?? 0);
            if (0 < $questionId && !\in_array($questionId, $questionIds, true)) {
                $questionIds[] = $questionId;
            }
        }

        return $questionIds;
    }

    /**
     * @return array<int, TrackEAttempt>
     */
    private function getAttemptRows(int $attemptId, int $questionId): array
    {
        $rows = $this->entityManager->createQueryBuilder()
            ->select('saved')
            ->from(TrackEAttempt::class, 'saved')
            ->andWhere('IDENTITY(saved.trackExercise) = :attemptId')
            ->andWhere('saved.questionId = :questionId')
            ->setParameter('attemptId', $attemptId, Types::INTEGER)
            ->setParameter('questionId', $questionId, Types::INTEGER)
            ->orderBy('saved.position', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        $result = [];
        foreach ($rows as $row) {
            if ($row instanceof TrackEAttempt) {
                $result[] = $row;
            }
        }

        return $result;
    }

    /**
     * @return array<int, CQuizAnswer>
     */
    private function getQuestionAnswers(int $questionId): array
    {
        $rows = $this->entityManager->createQueryBuilder()
            ->select('answer')
            ->from(CQuizAnswer::class, 'answer')
            ->andWhere('IDENTITY(answer.question) = :questionId')
            ->setParameter('questionId', $questionId, Types::INTEGER)
            ->orderBy('answer.position', 'ASC')
            ->addOrderBy('answer.iid', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        $answers = [];
        foreach ($rows as $row) {
            if (!$row instanceof CQuizAnswer || null === $row->getIid()) {
                continue;
            }

            $answers[(int) $row->getIid()] = $row;
        }

        return $answers;
    }

    /**
     * @return array<int, CQuizQuestionOption>
     */
    private function getQuestionOptions(int $questionId): array
    {
        $rows = $this->entityManager->createQueryBuilder()
            ->select('questionOption')
            ->from(CQuizQuestionOption::class, 'questionOption')
            ->andWhere('IDENTITY(questionOption.question) = :questionId')
            ->setParameter('questionId', $questionId, Types::INTEGER)
            ->getQuery()
            ->getResult()
        ;

        $options = [];
        foreach ($rows as $row) {
            if (!$row instanceof CQuizQuestionOption || null === $row->getIid()) {
                continue;
            }

            $options[(int) $row->getIid()] = $row;
        }

        return $options;
    }

    /**
     * @param array<int, CQuizAnswer>         $answers
     * @param array<int, CQuizQuestionOption> $options
     * @param array<int, TrackEAttempt>       $rows
     */
    private function scoreQuestion(CQuiz $quiz, CQuizQuestion $question, array $answers, array $options, array $rows): float
    {
        return match ((int) $question->getType()) {
            self::UNIQUE_ANSWER,
            self::UNIQUE_ANSWER_NO_OPTION,
            self::UNIQUE_ANSWER_IMAGE,
            self::READING_COMPREHENSION => $this->scoreUniqueAnswer($answers, $rows),
            self::MULTIPLE_ANSWER,
            self::GLOBAL_MULTIPLE_ANSWER,
            self::MULTIPLE_ANSWER_DROPDOWN => $this->scoreMultipleAnswer($answers, $rows),
            self::MULTIPLE_ANSWER_COMBINATION,
            self::MULTIPLE_ANSWER_DROPDOWN_COMBINATION => $this->scoreMultipleCombination($question, $answers, $rows),
            self::MULTIPLE_ANSWER_TRUE_FALSE => $this->scoreTrueFalseAnswer($question, $answers, $options, $rows),
            self::MULTIPLE_ANSWER_TRUE_FALSE_DEGREE_CERTAINTY => $this->scoreTrueFalseDegreeCertaintyAnswer($question, $answers, $options, $rows),
            self::MULTIPLE_ANSWER_COMBINATION_TRUE_FALSE => $this->scoreTrueFalseCombination($question, $answers, $options, $rows),
            self::FILL_IN_BLANKS,
            self::FILL_IN_BLANKS_COMBINATION => $this->scoreFillBlanks($quiz, $question, $answers, $rows),
            self::MATCHING,
            self::MATCHING_DRAGGABLE => $this->scoreMatchingAnswer($answers, $rows),
            self::DRAGGABLE => $this->scoreDraggableAnswer($answers, $rows),
            self::MATCHING_COMBINATION,
            self::MATCHING_DRAGGABLE_COMBINATION => $this->scoreMatchingCombination($question, $answers, $rows),
            self::CALCULATED_ANSWER => $this->scoreCalculatedAnswer($question, $answers, $rows),
            self::HOT_SPOT => $this->scoreHotspotAnswer($answers, $rows, false, (float) $question->getPonderation()),
            self::HOT_SPOT_DELINEATION => $this->scoreHotspotDelineationAnswer($quiz, $question, $answers, $rows),
            self::HOT_SPOT_COMBINATION => $this->scoreHotspotAnswer($answers, $rows, true, (float) $question->getPonderation()),
            self::FREE_ANSWER,
            self::ORAL_EXPRESSION,
            self::UPLOAD_ANSWER,
            self::ANSWER_IN_OFFICE_DOC,
            self::ANNOTATION => $this->scoreManualAnswer($rows),
            default => 0.0,
        };
    }

    /**
     * @param array<int, CQuizAnswer>   $answers
     * @param array<int, TrackEAttempt> $rows
     */
    private function scoreUniqueAnswer(array $answers, array $rows): float
    {
        $selectedAnswerId = $this->getFirstSavedAnswerId($rows);
        if (0 >= $selectedAnswerId || !isset($answers[$selectedAnswerId])) {
            return 0.0;
        }

        return $answers[$selectedAnswerId]->getPonderation();
    }

    /**
     * @param array<int, CQuizAnswer>   $answers
     * @param array<int, TrackEAttempt> $rows
     */
    private function scoreMultipleAnswer(array $answers, array $rows): float
    {
        $score = 0.0;
        foreach ($this->getSavedAnswerIds($rows) as $answerId) {
            if (!isset($answers[$answerId])) {
                continue;
            }

            $score += $answers[$answerId]->getPonderation();
        }

        return $score;
    }

    /**
     * @param array<int, CQuizAnswer>   $answers
     * @param array<int, TrackEAttempt> $rows
     */
    private function scoreMultipleCombination(CQuizQuestion $question, array $answers, array $rows): float
    {
        $selectedAnswerIds = array_flip($this->getSavedAnswerIds($rows));
        foreach ($answers as $answer) {
            $answerId = (int) $answer->getIid();
            $isCorrect = 1 === (int) $answer->getCorrect();
            $isSelected = isset($selectedAnswerIds[$answerId]);
            if ($isCorrect !== $isSelected) {
                return 0.0;
            }
        }

        $firstAnswer = reset($answers);
        if ($firstAnswer instanceof CQuizAnswer && 0.0 !== $firstAnswer->getPonderation()) {
            return $firstAnswer->getPonderation();
        }

        return (float) $question->getPonderation();
    }

    /**
     * @param array<int, CQuizAnswer>         $answers
     * @param array<int, CQuizQuestionOption> $options
     * @param array<int, TrackEAttempt>       $rows
     */
    private function scoreTrueFalseAnswer(CQuizQuestion $question, array $answers, array $options, array $rows): float
    {
        [$trueScore, $falseScore, $doubtScore] = $this->getTrueFalseScores((string) $question->getExtra());
        $choices = $this->getSavedTrueFalseChoices($rows);
        $score = 0.0;

        foreach ($answers as $answer) {
            $answerId = (int) $answer->getIid();
            $studentChoice = $choices[$answerId] ?? 0;
            if (0 >= $studentChoice) {
                $score += $doubtScore;
                continue;
            }

            if ($this->isTrueFalseChoiceCorrect($studentChoice, (int) $answer->getCorrect(), $options)) {
                $score += $trueScore;
                continue;
            }

            $optionTitle = $this->getTrueFalseOptionTitle($studentChoice, $options);
            $score += \in_array($optionTitle, ["Don't know", 'DoubtScore'], true) ? $doubtScore : $falseScore;
        }

        return $score;
    }

    /**
     * @param array<int, CQuizAnswer>         $answers
     * @param array<int, CQuizQuestionOption> $options
     * @param array<int, TrackEAttempt>       $rows
     */
    /**
     * @param array<int, CQuizAnswer>         $answers
     * @param array<int, CQuizQuestionOption> $options
     * @param array<int, TrackEAttempt>       $rows
     */
    private function scoreTrueFalseDegreeCertaintyAnswer(CQuizQuestion $question, array $answers, array $options, array $rows): float
    {
        [$trueScore, $falseScore, $doubtScore] = $this->getTrueFalseScores((string) $question->getExtra());
        $choices = $this->getSavedTrueFalseDegreeCertaintyChoices($rows);
        $score = 0.0;

        foreach ($answers as $answer) {
            $answerId = (int) $answer->getIid();
            $studentChoice = (int) ($choices[$answerId]['choice'] ?? 0);
            if (0 >= $studentChoice) {
                continue;
            }

            $studentDegreeChoice = (int) ($choices[$answerId]['degree'] ?? 0);
            $studentDegreeChoicePosition = $this->getTrueFalseOptionPosition($studentDegreeChoice, $options);
            $hasCertainty = 3 <= $studentDegreeChoicePosition && 9 > $studentDegreeChoicePosition;

            if ($this->isTrueFalseChoiceCorrect($studentChoice, (int) $answer->getCorrect(), $options)) {
                $score += $hasCertainty ? $trueScore : $doubtScore;
                continue;
            }

            $score += $hasCertainty ? $falseScore : $doubtScore;
        }

        return $score;
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

    private function scoreTrueFalseCombination(CQuizQuestion $question, array $answers, array $options, array $rows): float
    {
        $choices = $this->getSavedTrueFalseChoices($rows);
        foreach ($answers as $answer) {
            $answerId = (int) $answer->getIid();
            if (!$this->isTrueFalseChoiceCorrect($choices[$answerId] ?? 0, (int) $answer->getCorrect(), $options)) {
                return 0.0;
            }
        }

        $firstAnswer = reset($answers);
        if ($firstAnswer instanceof CQuizAnswer && 0.0 !== $firstAnswer->getPonderation()) {
            return $firstAnswer->getPonderation();
        }

        return (float) $question->getPonderation();
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
            if (!$candidate instanceof CQuizQuestionOption) {
                continue;
            }

            if ((int) $candidate->getPosition() === $choice) {
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
            if (!$candidate instanceof CQuizQuestionOption) {
                continue;
            }

            if ((int) $candidate->getPosition() === $choice) {
                return (string) $candidate->getTitle();
            }
        }

        return '';
    }


    /**
     * @param array<int, CQuizAnswer>   $answers
     * @param array<int, TrackEAttempt> $rows
     */
    private function scoreFillBlanks(CQuiz $quiz, CQuizQuestion $question, array $answers, array $rows): float
    {
        $row = $rows[0] ?? null;
        if (!$row instanceof TrackEAttempt) {
            return 0.0;
        }

        $answer = reset($answers);
        if (!$answer instanceof CQuizAnswer) {
            return 0.0;
        }

        $teacherInfo = $this->parseFillBlankAnswer($answer->getAnswer(), false);
        $studentInfo = $this->parseFillBlankAnswer($row->getAnswer(), true);
        $caseInsensitive = 'case:false' === (string) $question->getExtra();
        $studentScores = [];
        $score = 0.0;
        $blankCount = \count($teacherInfo['words']);

        for ($index = 0; $index < $blankCount; ++$index) {
            $correctAnswer = (string) ($teacherInfo['words'][$index] ?? '');
            $studentAnswer = (string) ($studentInfo['student_answer'][$index] ?? '');
            $isCorrect = $this->isFillBlankStudentAnswerGood($studentAnswer, $correctAnswer, $caseInsensitive);
            $studentScores[$index] = $isCorrect ? '1' : '0';
            if ($isCorrect) {
                $score += (float) ($teacherInfo['weighting'][$index] ?? 0.0);
            }
        }

        if (self::FILL_IN_BLANKS_COMBINATION === (int) $question->getType()) {
            $score = $blankCount > 0 && !\in_array('0', $studentScores, true) ? (float) $question->getPonderation() : 0.0;
        }

        $row->setAnswer($this->rebuildFillBlankStudentAnswer($teacherInfo, $studentInfo['student_answer'], $studentScores));

        return $score;
    }

    /**
     * @return array{
     *     text: string,
     *     system_string: string,
     *     words_count: int,
     *     words: array<int, string>,
     *     words_with_bracket: array<int, string>,
     *     weighting: array<int, string>,
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
            $parts = ['', ''];
        }

        $systemString = $parts[1];
        $systemParts = explode('@', $systemString, 2);
        $details = explode(':', (string) ($systemParts[0] ?? ''));
        $weighting = '' !== (string) ($details[0] ?? '') ? explode(',', (string) $details[0]) : [];
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
            'words_count' => \count($words),
            'words' => $words,
            'words_with_bracket' => $wordsWithBracket,
            'weighting' => $weighting,
            'common_words' => explode('::', $commonWordsString),
            'student_answer' => $studentAnswer,
            'student_score' => $studentScore,
            'blank_separator_start' => $start,
            'blank_separator_end' => $end,
        ];
    }

    /**
     * @param array{
     *     common_words: array<int, string>,
     *     words_with_bracket: array<int, string>,
     *     system_string: string,
     *     blank_separator_start: string,
     *     blank_separator_end: string
     * } $teacherInfo
     * @param array<int, string> $studentAnswers
     * @param array<int, string> $studentScores
     */
    private function rebuildFillBlankStudentAnswer(array $teacherInfo, array $studentAnswers, array $studentScores): string
    {
        $start = (string) $teacherInfo['blank_separator_start'];
        $end = (string) $teacherInfo['blank_separator_end'];
        $commonWords = $teacherInfo['common_words'];
        $result = '';
        $count = \count($teacherInfo['words_with_bracket']);

        for ($index = 0; $index < $count; ++$index) {
            $result .= (string) ($commonWords[$index] ?? '');
            $result .= (string) ($teacherInfo['words_with_bracket'][$index] ?? '');
            $result .= $start.(string) ($studentAnswers[$index] ?? '').$end;
            $result .= $start.(string) ($studentScores[$index] ?? '0').$end;
        }

        $result .= (string) ($commonWords[$count] ?? '');
        $result .= '::'.(string) $teacherInfo['system_string'];

        return $result;
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

    private function isFillBlankStudentAnswerGood(string $studentAnswer, string $correctAnswer, bool $caseInsensitive): bool
    {
        $normalizedStudentAnswer = $caseInsensitive ? mb_strtolower($studentAnswer) : $studentAnswer;

        if (false !== strpos($correctAnswer, '|') && false === strpos($correctAnswer, '||')) {
            $menuAnswers = array_map([$this, 'trimFillBlankOption'], explode('|', $correctAnswer));
            $firstAnswer = (string) ($menuAnswers[0] ?? '');
            $normalizedFirstAnswer = $caseInsensitive ? mb_strtolower($firstAnswer) : $firstAnswer;

            return $normalizedStudentAnswer === $normalizedFirstAnswer || $normalizedStudentAnswer === sha1($normalizedFirstAnswer);
        }

        if (false !== strpos($correctAnswer, '||')) {
            $answers = array_map([$this, 'trimFillBlankOption'], preg_split('/\|\|/', $correctAnswer) ?: []);
            foreach ($answers as $answer) {
                $candidate = $caseInsensitive ? mb_strtolower($answer) : $answer;
                if ($normalizedStudentAnswer === $candidate) {
                    return true;
                }
            }

            return false;
        }

        $normalizedCorrectAnswer = $caseInsensitive ? mb_strtolower($this->trimFillBlankOption($correctAnswer)) : $this->trimFillBlankOption($correctAnswer);

        return $normalizedStudentAnswer === $normalizedCorrectAnswer;
    }

    private function trimFillBlankOption(string $value): string
    {
        return trim(html_entity_decode($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
    }

    /**
     * @param array<int, CQuizAnswer>   $answers
     * @param array<int, TrackEAttempt> $rows
     */
    private function scoreMatchingAnswer(array $answers, array $rows): float
    {
        $choices = $this->getSavedMatchingChoices($rows);
        $score = 0.0;
        foreach ($answers as $answer) {
            if (0 === (int) $answer->getCorrect()) {
                continue;
            }

            $answerId = (int) $answer->getIid();
            if (($choices[$answerId] ?? 0) === (int) $answer->getCorrect()) {
                $score += $answer->getPonderation();
            }
        }

        return $score;
    }

    /**
     * @param array<int, CQuizAnswer>   $answers
     * @param array<int, TrackEAttempt> $rows
     */
    private function scoreDraggableAnswer(array $answers, array $rows): float
    {
        $positions = $this->getSavedDraggablePositions($rows);
        $score = 0.0;

        foreach ($answers as $answer) {
            $correctPosition = (int) ($answer->getCorrect() ?? 0);
            if (0 >= $correctPosition) {
                continue;
            }

            $answerId = (int) $answer->getIid();
            if (($positions[$answerId] ?? 0) === $correctPosition) {
                $score += $answer->getPonderation();
            }
        }

        return $score;
    }

    /**
     * @param array<int, CQuizAnswer>   $answers
     * @param array<int, TrackEAttempt> $rows
     */
    private function scoreMatchingCombination(CQuizQuestion $question, array $answers, array $rows): float
    {
        $choices = $this->getSavedMatchingChoices($rows);
        $optionCount = 0;
        $correctCount = 0;

        foreach ($answers as $answer) {
            if (0 === (int) $answer->getCorrect()) {
                continue;
            }

            ++$optionCount;
            $answerId = (int) $answer->getIid();
            if (($choices[$answerId] ?? 0) === (int) $answer->getCorrect()) {
                ++$correctCount;
            }
        }

        return 0 < $optionCount && $correctCount === $optionCount ? (float) $question->getPonderation() : 0.0;
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
     * @param array<int, CQuizAnswer>   $answers
     * @param array<int, TrackEAttempt> $rows
     */
    private function scoreHotspotAnswer(array $answers, array $rows, bool $combination, float $questionWeight): float
    {
        $points = $this->getSavedHotspotPoints($rows);
        if ([] === $points) {
            return 0.0;
        }

        $matchedAnswerIds = [];
        $score = 0.0;
        $scoringZoneCount = 0;

        foreach ($answers as $answer) {
            $answerId = (int) $answer->getIid();
            $hotspotType = (string) ($answer->getHotspotType() ?: 'square');
            if (!\in_array($hotspotType, ['square', 'circle', 'poly'], true)) {
                continue;
            }

            if (0.0 < (float) $answer->getPonderation()) {
                ++$scoringZoneCount;
            }

            foreach ($points as $point) {
                $pointAnswerId = (int) ($point['answerId'] ?? 0);
                if (0 < $pointAnswerId && $pointAnswerId !== $answerId) {
                    continue;
                }

                if ($this->isPointInHotspot($point, $hotspotType, (string) $answer->getHotspotCoordinates())) {
                    $matchedAnswerIds[$answerId] = true;
                    if (!$combination) {
                        $score += (float) $answer->getPonderation();
                    }
                    break;
                }
            }
        }

        if (!$combination) {
            return $score;
        }

        return 0 < $scoringZoneCount && \count($matchedAnswerIds) >= $scoringZoneCount ? $questionWeight : 0.0;
    }

    /**
     * @param array<int, CQuizAnswer>   $answers
     * @param array<int, TrackEAttempt> $rows
     */
    private function scoreHotspotDelineationAnswer(CQuiz $quiz, CQuizQuestion $question, array $answers, array $rows): float
    {
        $studentPolygon = $this->getSavedDelineationPolygon($rows);
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
     * @param array<int, TrackEAttempt> $rows
     *
     * @return array<int, array{x: float, y: float}>
     */
    private function getSavedDelineationPolygon(array $rows): array
    {
        $row = $rows[0] ?? null;
        if (!$row instanceof TrackEAttempt) {
            return [];
        }

        return $this->parseDelineationPolygon((string) $row->getAnswer());
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
     * @param array<int, TrackEAttempt> $rows
     *
     * @return array<int, array{x: float, y: float, answerId?: int}>
     */
    private function getSavedHotspotPoints(array $rows): array
    {
        $points = [];
        foreach ($rows as $row) {
            foreach (explode('|', (string) $row->getAnswer()) as $coordinate) {
                $point = $this->decodeHotspotPoint($coordinate);
                if (null !== $point) {
                    $points[] = $point;
                }
            }
        }

        return $points;
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
    private function isPointInHotspot(array $point, string $hotspotType, string $coordinates): bool
    {
        return match ($hotspotType) {
            'square' => $this->isPointInSquare($point, $coordinates),
            'circle' => $this->isPointInEllipse($point, $coordinates),
            'poly' => $this->isPointInPolygon($point, $coordinates),
            default => false,
        };
    }

    /**
     * @param array{x: float, y: float} $point
     */
    private function isPointInSquare(array $point, string $coordinates): bool
    {
        [$origin, $width, $height] = $this->parseBoxCoordinates($coordinates);
        if (null === $origin) {
            return false;
        }

        return $point['x'] >= $origin['x']
            && $point['x'] <= $origin['x'] + $width
            && $point['y'] >= $origin['y']
            && $point['y'] <= $origin['y'] + $height;
    }

    /**
     * @param array{x: float, y: float} $point
     */
    private function isPointInEllipse(array $point, string $coordinates): bool
    {
        [$origin, $width, $height] = $this->parseBoxCoordinates($coordinates);
        if (null === $origin || 0.0 >= $width || 0.0 >= $height) {
            return false;
        }

        $radiusX = $width / 2;
        $radiusY = $height / 2;
        $centerX = $origin['x'] + $radiusX;
        $centerY = $origin['y'] + $radiusY;

        return ((($point['x'] - $centerX) ** 2) / ($radiusX ** 2))
            + ((($point['y'] - $centerY) ** 2) / ($radiusY ** 2)) <= 1.0;
    }

    /**
     * @return array{0: array{x: float, y: float}|null, 1: float, 2: float}
     */
    private function parseBoxCoordinates(string $coordinates): array
    {
        $parts = explode('|', $coordinates);
        $origin = $this->decodeHotspotPoint((string) ($parts[0] ?? ''));
        $width = isset($parts[1]) && is_numeric($parts[1]) ? (float) $parts[1] : 0.0;
        $height = isset($parts[2]) && is_numeric($parts[2]) ? (float) $parts[2] : 0.0;

        return [$origin, $width, $height];
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
     * @param array<int, CQuizAnswer>   $answers
     * @param array<int, TrackEAttempt> $rows
     */
    private function scoreCalculatedAnswer(CQuizQuestion $question, array $answers, array $rows): float
    {
        $row = $rows[0] ?? null;
        if (!$row instanceof TrackEAttempt) {
            return 0.0;
        }

        [$answerId, $studentAnswer] = $this->parseCalculatedStudentAnswer((string) $row->getAnswer());
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
     * @param array<int, TrackEAttempt> $rows
     */
    private function scoreManualAnswer(array $rows): float
    {
        $row = $rows[0] ?? null;

        return $row instanceof TrackEAttempt ? (float) $row->getMarks() : 0.0;
    }

    /**
     * @param array<int, CQuizAnswer> $answers
     */
    private function getQuestionWeight(CQuizQuestion $question, array $answers): float
    {
        $type = (int) $question->getType();
        if (\in_array($type, [self::MEDIA_QUESTION, self::PAGE_BREAK], true)) {
            return 0.0;
        }

        if (self::CALCULATED_ANSWER === $type) {
            return (float) $question->getPonderation();
        }

        if (\in_array($type, [self::MULTIPLE_ANSWER_COMBINATION, self::MULTIPLE_ANSWER_COMBINATION_TRUE_FALSE], true)) {
            $firstAnswer = reset($answers);
            if ($firstAnswer instanceof CQuizAnswer && 0.0 !== $firstAnswer->getPonderation()) {
                return $firstAnswer->getPonderation();
            }
        }

        return (float) $question->getPonderation();
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
    private function getSavedDraggablePositions(array $rows): array
    {
        $positions = [];
        foreach ($rows as $row) {
            $answerId = (int) $row->getPosition();
            $selectedPosition = (int) $row->getAnswer();
            if (0 < $answerId && 0 < $selectedPosition) {
                $positions[$answerId] = $selectedPosition;
            }
        }

        return $positions;
    }

    /**
     * @param array<int, TrackEAttempt> $rows
     *
     * @return array<int, int>
     */
    private function requiresManualCorrection(CQuizQuestion $question): bool
    {
        return \in_array((int) $question->getType(), [self::FREE_ANSWER, self::ORAL_EXPRESSION, self::UPLOAD_ANSWER, self::ANSWER_IN_OFFICE_DOC, self::ANNOTATION], true);
    }

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
    private function updateQuestionAttemptRows(CQuizQuestion $question, array $rows, float $score): void
    {
        foreach ($rows as $row) {
            $row->setMarks($score);
            $row->setTms(new DateTime());
        }

        if ([] === $rows && !$this->requiresManualCorrection($question)) {
            return;
        }
    }

    private function getFinishedAt(TrackEExercise $attempt): DateTime
    {
        $finishedAt = new DateTime();
        $expiredAt = $attempt->getExpiredTimeControl();
        if ($expiredAt instanceof DateTime && $expiredAt < $finishedAt) {
            return $expiredAt;
        }

        return $finishedAt;
    }
}
