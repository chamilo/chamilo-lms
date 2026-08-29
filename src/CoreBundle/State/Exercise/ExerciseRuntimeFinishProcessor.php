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
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CoreBundle\Helpers\ExerciseHotspotGeometryHelper;
use Chamilo\CoreBundle\Helpers\ExerciseLearnpathVisibilityHelper;
use Chamilo\CoreBundle\Helpers\UserHelper;
use Chamilo\CoreBundle\Service\Exercise\ExerciseAttemptScoringService;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CLpItem;
use Chamilo\CourseBundle\Entity\CLpItemView;
use Chamilo\CourseBundle\Entity\CLpView;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Entity\CQuizDestinationResult;
use Chamilo\CourseBundle\Entity\CQuizQuestion;
use Chamilo\CourseBundle\Entity\CQuizQuestionCategory;
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
    private const FEEDBACK_TYPE_POPUP = 3;
    private const FEEDBACK_TYPE_PROGRESSIVE_ADAPTIVE = 4;
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
    public function __construct(
        private CidReqHelper $cidReqHelper,
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager,
        private CQuizRepository $quizRepository,
        private Security $security,
        private SettingsManager $settingsManager,
        private UserHelper $userHelper,
        private ExerciseLearnpathVisibilityHelper $exerciseLearnpathVisibilityHelper,
        private ExerciseHotspotGeometryHelper $exerciseHotspotGeometryHelper,
        private ExerciseAttemptScoringService $exerciseAttemptScoringService,
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

        $course = $this->cidReqHelper->requireDoctrineCourseEntity();
        $session = $this->cidReqHelper->getDoctrineSessionEntity();
        if (\function_exists('session_write_close')) {
            session_write_close();
        }
        $exerciseId = isset($uriVariables['exerciseId']) ? (int) $uriVariables['exerciseId'] : (int) ($data->exerciseId ?? 0);
        $attemptId = isset($uriVariables['attemptId']) ? (int) $uriVariables['attemptId'] : (int) ($data->attemptId ?? 0);

        if ($exerciseId <= 0 || $attemptId <= 0) {
            throw new BadRequestHttpException('A valid exercise and attempt are required.');
        }

        $quiz = $this->getExerciseFromCurrentContext($exerciseId, $course, $session, $this->userHelper->isTeacherOfCurrentCourse());
        $attempt = $this->getIncompleteAttempt($attemptId, $quiz, $course, $session, $user);
        $questionIds = $this->exerciseAttemptScoringService->parseQuestionIds((string) $attempt->getDataTracking());
        if ([] === $questionIds) {
            throw new BadRequestHttpException('The attempt does not contain a persisted question list.');
        }

        $questionIds = $this->normalizeCompletedQuestionIds($quiz, $attempt, $questionIds);
        if ([] === $questionIds) {
            throw new BadRequestHttpException('The attempt does not contain answered questions.');
        }

        $questions = $this->exerciseAttemptScoringService->getQuestions($quiz, $questionIds);
        $unsupportedTypes = $this->exerciseAttemptScoringService->getUnsupportedQuestionTypes($questions);
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

            $rows = $this->exerciseAttemptScoringService->getAttemptRows($attemptId, $questionId);
            $answers = $this->exerciseAttemptScoringService->getQuestionAnswers($questionId);
            $options = $this->exerciseAttemptScoringService->getQuestionOptions($questionId);
            $score = $this->exerciseAttemptScoringService->scoreQuestion($quiz, $question, $answers, $options, $rows);
            $weight = $this->exerciseAttemptScoringService->getQuestionWeight($question, $answers);

            if (0 === (int) $quiz->getPropagateNeg() && $score < 0) {
                $score = 0.0;
            }

            $this->exerciseAttemptScoringService->updateQuestionAttemptRows($question, $rows, $score);

            if ($this->exerciseAttemptScoringService->requiresManualCorrection($question)) {
                $questionsToCheck[] = $questionId;
            }

            $totalScore += $score;
            $totalWeight += $weight;
        }

        if ($totalWeight <= 0.0) {
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

        if ($lpId <= 0 || $lpItemId <= 0) {
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
            || (int) $attempt->getOrigLpId() > 0
            || (int) $attempt->getOrigLpItemId() > 0
            || (int) $attempt->getOrigLpItemViewId() > 0;
    }

    private function getPositiveQueryInt(Request $request, string $name, int $fallback = 0): int
    {
        $value = $request->query->get($name);
        if (null !== $value && is_numeric($value)) {
            $intValue = (int) $value;
            if ($intValue > 0) {
                return $intValue;
            }
        }

        return $fallback > 0 ? $fallback : 0;
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

        if ($lpItemViewId > 0) {
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
        $passPercentage = (float) ($quiz->getPassPercentage() ?? 0);
        if ($passPercentage <= 0.0) {
            return self::LP_STATUS_PASSED;
        }

        if ($maxScore <= 0.0) {
            return self::STATUS_COMPLETED;
        }

        $percentage = ($score / $maxScore) * 100;

        return $percentage >= $passPercentage ? self::LP_STATUS_PASSED : self::LP_STATUS_FAILED;
    }

    /**
     * @return array{completedItems: int, totalItems: int, progress: int, progressMode: string}
     */
    private function updateLearnpathProgress(CLpView $lpView): array
    {
        $lpId = (int) ($lpView->getLp()->getIid() ?? 0);
        if ($lpId <= 0) {
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

        if ($totalItems <= 0) {
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
        if ($attemptId <= 0) {
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
            || $this->userHelper->isTeacherOfCurrentCourse();
    }

    private function getExerciseFromCurrentContext(int $exerciseId, Course $course, ?Session $session, bool $canManage): CQuiz
    {
        $quiz = $this->quizRepository->find($exerciseId);
        if (!$quiz instanceof CQuiz) {
            throw new NotFoundHttpException('The requested exercise was not found.');
        }

        $context = $this->quizRepository->findInCourseContextWithVisibility($exerciseId, $course, $session);
        if (null === $context) {
            throw new AccessDeniedHttpException('The requested exercise does not belong to the current course context.');
        }

        if (!$canManage) {
            $visibility = $context['visibility'];
            $now = new DateTime();
            if (self::VISIBILITY_PUBLISHED !== $visibility
                && !$this->exerciseLearnpathVisibilityHelper->isVisibleThroughLearnpath($quiz, $course, $session)
            ) {
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
     * In direct feedback/adaptive exercises, only questions really reached by the learner must be scored.
     * Branch-only questions that were not visited must not lower the final score.
     *
     * @param array<int, int> $questionIds
     *
     * @return array<int, int>
     */
    private function normalizeCompletedQuestionIds(CQuiz $quiz, TrackEExercise $attempt, array $questionIds): array
    {
        if (!\in_array(
            (int) $quiz->getFeedbackType(),
            [self::FEEDBACK_TYPE_DIRECT, self::FEEDBACK_TYPE_POPUP, self::FEEDBACK_TYPE_PROGRESSIVE_ADAPTIVE],
            true,
        )) {
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
     * @param array<int, int>           $questionIds
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
     * @param array<int, int>           $questionIds
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
            if ($questionId > 0 && !\in_array($questionId, $questionIds, true)) {
                $questionIds[] = $questionId;
            }
        }

        return $questionIds;
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
