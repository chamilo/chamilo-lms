<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Exercise;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\Exercise\ExerciseRuntime;
use Chamilo\CoreBundle\Entity\AttemptFile;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\ExtraFieldValues;
use Chamilo\CoreBundle\Entity\ResourceFile;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\TrackEAttempt;
use Chamilo\CoreBundle\Entity\TrackEExercise;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CGlossary;
use Chamilo\CourseBundle\Entity\CLpItem;
use Chamilo\CourseBundle\Entity\CLpItemView;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Entity\CQuizAnswer;
use Chamilo\CourseBundle\Entity\CQuizQuestion;
use Chamilo\CourseBundle\Entity\CQuizQuestionCategory;
use Chamilo\CourseBundle\Entity\CQuizQuestionOption;
use Chamilo\CourseBundle\Entity\CQuizRelQuestion;
use Chamilo\CourseBundle\Repository\CGlossaryRepository;
use Chamilo\CourseBundle\Repository\CQuizQuestionRepository;
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
 * Runtime data provider for the Vue exercise player foundation.
 *
 * This provider intentionally exposes only the data required to render the attempt UI.
 * Correct answers, scores per answer and feedback comments remain hidden for learners.
 * Submission, scoring and tracking are handled by the runtime processors.
 *
 * @implements ProviderInterface<ExerciseRuntime>
 */
final readonly class ExerciseRuntimeProvider implements ProviderInterface
{
    private const VISIBILITY_PUBLISHED = 2;
    private const LP_ITEM_TYPE_QUIZ = 'quiz';
    private const STATUS_INCOMPLETE = 'incomplete';
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
    private const HOTSPOT_TYPES = [6, 26];
    private const MEDIA_QUESTION = 15;
    private const PAGE_BREAK = 31;
    private const QUESTION_SELECTION_RANDOM = 2;
    private const QUESTION_SELECTION_CATEGORIES_ORDERED_QUESTIONS_ORDERED = 3;
    private const QUESTION_SELECTION_CATEGORIES_RANDOM_QUESTIONS_RANDOM = 6;
    private const STRUCTURAL_CONTENT_TYPES = [self::MEDIA_QUESTION, self::PAGE_BREAK];
    private const LEGACY_RUNTIME_REASON_UNSUPPORTED_QUESTION = 'This exercise contains a question type that requires a different test player.';

    public function __construct(
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager,
        private CQuizRepository $quizRepository,
        private CQuizQuestionRepository $questionRepository,
        private CGlossaryRepository $glossaryRepository,
        private Security $security,
        private SettingsManager $settingsManager,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ExerciseRuntime
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        $course = $this->getCourse($request);
        $session = $this->getSession($request);
        $canManagePermission = $this->canManageExercises();
        $runsAsLearner = !$canManagePermission || $this->isLearnerRuntimeRequest($request);
        $canManage = $canManagePermission && !$runsAsLearner;

        if (!$canManagePermission && !$this->canViewExercises()) {
            throw new AccessDeniedHttpException('You are not allowed to open this exercise.');
        }

        $exerciseId = isset($uriVariables['exerciseId']) ? (int) $uriVariables['exerciseId'] : 0;
        if (0 >= $exerciseId) {
            throw new BadRequestHttpException('A valid exercise id is required.');
        }

        $quiz = $this->getExerciseFromCurrentContext($exerciseId, $course, $session, $canManagePermission);
        $attempt = $this->getRuntimeAttempt($request, $quiz, $course, $session, !$runsAsLearner);
        $attemptQuestionIds = $attempt instanceof TrackEExercise ? $this->parseQuestionIds((string) $attempt->getDataTracking()) : null;
        $questions = $this->getRuntimeQuestions($quiz, $course, $session, $canManage, $attemptQuestionIds, $attempt);
        $runtimePages = $this->buildRuntimePages($quiz, $questions);
        $settings = $this->getRuntimeSettings($quiz, $course, $session);
        $legacyRuntimeReasons = $this->getLegacyRuntimeReasons($quiz);
        $settings['runtimePages'] = $runtimePages['pages'];
        $settings['usesStructuralPages'] = $runtimePages['usesStructuralPages'];
        $settings['forceGroupedByMedia'] = $runtimePages['forceGroupedByMedia'];
        $settings['effectiveOneQuestionPerPage'] = $runtimePages['effectiveOneQuestionPerPage'];
        $settings['requiresLegacyRuntime'] = [] !== $legacyRuntimeReasons;
        $settings['legacyRuntimeReasons'] = $legacyRuntimeReasons;

        $response = new ExerciseRuntime();
        $response->exerciseId = $exerciseId;
        $response->title = $quiz->getTitle();
        $response->description = (string) $quiz->getDescription();
        $response->settings = $settings;
        $response->questions = $questions;
        $response->legacyUrls = $this->getLegacyUrls($quiz, $course, $session);
        $response->questionCount = $this->countAnswerableQuestions($questions);
        $response->totalScore = $this->getTotalScore($questions);
        $requiresLegacyRuntime = true === ($settings['requiresLegacyRuntime'] ?? false);
        $canSubmit = $runsAsLearner
            && !$requiresLegacyRuntime
            && $attempt instanceof TrackEExercise
            && self::STATUS_INCOMPLETE === (string) $attempt->getStatus()
            && $this->canFinishWithVue($questions);

        $response->canManage = $canManage;
        $response->attempt = $attempt instanceof TrackEExercise ? $this->normalizeAttempt($attempt, $questions, $quiz) : null;
        $response->canStartAttempt = $runsAsLearner;
        $response->canSubmit = $canSubmit;
        $response->usesLegacySubmit = $requiresLegacyRuntime || !$canSubmit;

        return $response;
    }

    /**
     * @param array<int, array<string, mixed>> $questions
     */
    private function canFinishWithVue(array $questions): bool
    {
        if ([] === $questions) {
            return false;
        }

        foreach ($questions as $question) {
            $type = (int) ($question['type'] ?? 0);
            if (!$this->isFinishSupportedQuestionType($type)) {
                return false;
            }
        }

        return true;
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
            || \in_array($type, self::HOTSPOT_TYPES, true)
            || \in_array($type, self::STRUCTURAL_CONTENT_TYPES, true);
    }

    private function getRuntimeAttempt(Request $request, CQuiz $quiz, Course $course, ?Session $session, bool $canManage): ?TrackEExercise
    {
        $attemptId = $request->query->getInt('attemptId');
        if (0 >= $attemptId) {
            return null;
        }

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

        return $attempt instanceof TrackEExercise ? $attempt : null;
    }

    /**
     * @param array<int, array<string, mixed>> $questions
     *
     * @return array<string, mixed>
     */
    private function normalizeAttempt(TrackEExercise $attempt, array $questions, CQuiz $quiz): array
    {
        $questionIds = $this->parseQuestionIds((string) $attempt->getDataTracking());
        $expiredAt = null;
        $remainingSeconds = null;
        if (method_exists($attempt, 'getExpiredTimeControl')) {
            $expiredTimeControl = $attempt->getExpiredTimeControl();
            if ($expiredTimeControl instanceof DateTimeInterface) {
                $expiredAt = $expiredTimeControl->format(DateTimeInterface::ATOM);
                $remainingSeconds = max(0, $expiredTimeControl->getTimestamp() - time());
            }
        }

        $currentQuestionIndex = $this->getResumeQuestionIndex($attempt, $quiz, $questionIds);

        return [
            'attemptId' => (int) $attempt->getExeId(),
            'attemptNumber' => $this->getAttemptNumber($attempt),
            'status' => method_exists($attempt, 'getStatus') ? (string) $attempt->getStatus() : 'incomplete',
            'questionIds' => $questionIds,
            'currentQuestionIndex' => $currentQuestionIndex,
            'currentQuestionId' => (int) (
                $questionIds[$currentQuestionIndex]
                ?? ($questions[$currentQuestionIndex]['id'] ?? ($questionIds[0] ?? ($questions[0]['id'] ?? 0)))
            ),
            'totalQuestions' => \count($questions),
            'expiredAt' => $expiredAt,
            'remainingSeconds' => $remainingSeconds,
            'savedAnswers' => $this->getSavedAnswers((int) $attempt->getExeId(), $attempt->getCourse(), $attempt->getSession()),
            'reviewQuestionIds' => $this->parseQuestionIds((string) $attempt->getQuestionsToCheck()),
        ];
    }

    /**
     * @param array<int, int> $questionIds
     */
    private function getResumeQuestionIndex(TrackEExercise $attempt, ?CQuiz $quiz, array $questionIds): int
    {
        if (!$quiz instanceof CQuiz || 1 !== (int) $quiz->getPreventBackwards()) {
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
     * @return array<int|string, array<int, array<string, mixed>>>
     */
    private function getSavedAnswers(int $attemptId, Course $course, ?Session $session): array
    {
        $rows = $this->entityManager->createQueryBuilder()
            ->select('saved')
            ->addSelect('attemptFile', 'resourceNode', 'resourceFile')
            ->from(TrackEAttempt::class, 'saved')
            ->leftJoin('saved.attemptFiles', 'attemptFile')
            ->leftJoin('attemptFile.resourceNode', 'resourceNode')
            ->leftJoin('resourceNode.resourceFiles', 'resourceFile')
            ->andWhere('IDENTITY(saved.trackExercise) = :attemptId')
            ->setParameter('attemptId', $attemptId, Types::INTEGER)
            ->orderBy('saved.questionId', 'ASC')
            ->addOrderBy('saved.position', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        $savedAnswers = [];
        foreach ($rows as $row) {
            if (!$row instanceof TrackEAttempt) {
                continue;
            }

            $questionId = (int) $row->getQuestionId();
            if (0 >= $questionId) {
                continue;
            }

            $savedRow = [
                'answer' => $row->getAnswer(),
                'position' => null !== $row->getPosition() ? (int) $row->getPosition() : null,
                'secondsSpent' => method_exists($row, 'getSecondsSpent') ? (int) $row->getSecondsSpent() : 0,
            ];

            $files = $this->normalizeSavedAttemptFiles($row, $course, $session);
            if ([] !== $files) {
                $savedRow['files'] = $files;
            }

            $savedAnswers[$questionId][] = $savedRow;
        }

        return $savedAnswers;
    }

    /**
     * @return array<int, array{id: int, name: string, size: int, mimeType: string, url: string, inlineUrl: string}>
     */
    private function normalizeSavedAttemptFiles(TrackEAttempt $attemptRow, Course $course, ?Session $session): array
    {
        $files = [];
        foreach ($attemptRow->getAttemptFiles() as $attemptFile) {
            if (!$attemptFile instanceof AttemptFile) {
                continue;
            }

            $resourceNode = $attemptFile->getResourceNode();
            if (!$resourceNode instanceof ResourceNode || null === $resourceNode->getId()) {
                continue;
            }

            $resourceFile = $resourceNode->getResourceFiles()->first();
            $name = $resourceNode->getTitle();
            $size = 0;
            $mimeType = '';
            if ($resourceFile instanceof ResourceFile) {
                $name = $resourceFile->getOriginalName() ?: $resourceNode->getTitle();
                $size = (int) $resourceFile->getSize();
                $mimeType = (string) $resourceFile->getMimeType();
            }

            $files[] = [
                'id' => (int) $resourceNode->getId(),
                'name' => (string) ($name ?: 'answer-file'),
                'size' => $size,
                'mimeType' => $mimeType,
                'url' => $this->getAttemptFileDownloadUrl($attemptRow, $resourceNode, $course, $session),
                'inlineUrl' => $this->getAttemptFileDownloadUrl($attemptRow, $resourceNode, $course, $session, true),
            ];
        }

        return $files;
    }

    private function getAttemptFileDownloadUrl(
        TrackEAttempt $attemptRow,
        ResourceNode $resourceNode,
        Course $course,
        ?Session $session,
        bool $inline = false,
    ): string {
        $attempt = $attemptRow->getTrackEExercise();
        $quiz = $attempt->getQuiz();
        if (null === $quiz || null === $quiz->getIid() || null === $resourceNode->getId()) {
            return '';
        }

        $query = [
            'cid' => (int) $course->getId(),
        ];

        if (null !== $session) {
            $query['sid'] = (int) $session->getId();
        }

        if ($inline) {
            $query['inline'] = 1;
        }

        $request = $this->requestStack->getCurrentRequest();
        if (null !== $request) {
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
     * @return array<int, int>
     */
    private function parseQuestionIds(string $value): array
    {
        if ('' === trim($value)) {
            return [];
        }

        return array_values(array_filter(array_map(static fn (string $id): int => (int) trim($id), explode(',', $value))));
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

    /**
     * @return array<string, mixed>
     */
    private function getRuntimeSettings(CQuiz $quiz, Course $course, ?Session $session): array
    {
        $showGlossary = $this->shouldShowGlossaryInRuntime();

        return [
            'type' => (int) $quiz->getType(),
            'oneQuestionPerPage' => CQuiz::ONE_PER_PAGE === (int) $quiz->getType(),
            'randomQuestions' => (int) $quiz->getRandom(),
            'randomAnswers' => true === $quiz->getRandomAnswers(),
            'resultsDisabled' => (int) $quiz->getResultsDisabled(),
            'maxAttempt' => (int) $quiz->getMaxAttempt(),
            'feedbackType' => (int) $quiz->getFeedbackType(),
            'expiredTime' => (int) $quiz->getExpiredTime(),
            'duration' => $quiz->getDuration(),
            'propagateNegative' => (int) $quiz->getPropagateNeg(),
            'saveCorrectAnswers' => (int) ($quiz->getSaveCorrectAnswers() ?? 0),
            'reviewAnswers' => (int) $quiz->getReviewAnswers(),
            'randomByCategory' => (int) $quiz->getRandomByCategory(),
            'displayCategoryName' => (int) $quiz->getDisplayCategoryName(),
            'passPercentage' => $quiz->getPassPercentage(),
            'preventBackwards' => 1 === (int) $quiz->getPreventBackwards(),
            'hideQuestionNumber' => 1 === (int) $quiz->getHideQuestionNumber(),
            'hideQuestionTitle' => true === $quiz->isHideQuestionTitle(),
            'showPreviousButton' => true === $quiz->isShowPreviousButton(),
            'hideAttemptsTable' => true === $quiz->isHideAttemptsTable(),
            'questionSelectionType' => (int) ($quiz->getQuestionSelectionType() ?? 0),
            'pageResultConfiguration' => $quiz->getPageResultConfiguration(),
            'autoLaunch' => true === $quiz->isAutoLaunch(),
            'notifications' => $quiz->getNotifications(),
            'accessCondition' => (string) $quiz->getAccessCondition(),
            'sound' => (string) ($quiz->getSound() ?? ''),
            'quizCategoryId' => null !== $quiz->getQuizCategory()?->getId() ? (int) $quiz->getQuizCategory()->getId() : null,
            'displayChartDegreeCertainty' => (int) $quiz->getDisplayChartDegreeCertainty(),
            'sendEmailChartDegreeCertainty' => (int) $quiz->getSendEmailChartDegreeCertainty(),
            'notDisplayBalancePercentageCategorieQuestion' => (int) $quiz->getNotDisplayBalancePercentageCategorieQuestion(),
            'displayChartDegreeCertaintyCategory' => (int) $quiz->getDisplayChartDegreeCertaintyCategory(),
            'gatherQuestionsCategories' => (int) $quiz->getGatherQuestionsCategories(),
            'startTime' => $this->formatDate($quiz->getStartTime()),
            'endTime' => $this->formatDate($quiz->getEndTime()),
            'textWhenFinished' => (string) $quiz->getTextWhenFinished(),
            'textWhenFinishedFailure' => (string) $quiz->getTextWhenFinishedFailure(),
            'preventCopyPaste' => $this->isSettingEnabled('exercise.quiz_prevent_copy_paste'),
            'keepAlivePingInterval' => $this->getKeepAlivePingInterval(),
            'confirmSavedAnswers' => $this->isSettingEnabled('exercise.quiz_confirm_saved_answers'),
            'checkAllAnswersBeforeEndTest' => $this->isSettingEnabled('exercise.quiz_check_all_answers_before_end_test'),
            'disableNewAttempts' => $this->isSettingEnabled('exercise.exercises_disable_new_attempts'),
            'allowTimePerQuestion' => $this->isSettingEnabled('exercise.allow_time_per_question'),
            'hasTimedQuestions' => $this->hasTimedQuestions($quiz),
            'blockCategoryQuestions' => $this->isBlockCategoryRuntimeEnabled($quiz),
            'imageZoomEnabled' => $this->isImageZoomEnabled(),
            'glossary' => [
                'enabled' => $showGlossary,
                'terms' => $showGlossary ? $this->getRuntimeGlossaryTerms($course, $session) : [],
            ],
        ];
    }

    /**
     * @return array<int, string>
     */
    private function getLegacyRuntimeReasons(CQuiz $quiz): array
    {
        $reasons = [];

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
                $reasons[] = self::LEGACY_RUNTIME_REASON_UNSUPPORTED_QUESTION;

                break;
            }
        }

        return array_values(array_unique($reasons));
    }

    private function hasTimedQuestions(CQuiz $quiz): bool
    {
        foreach ($this->getOrderedQuestionRelations($quiz) as $relation) {
            if (!$relation instanceof CQuizRelQuestion) {
                continue;
            }

            $question = $relation->getQuestion();
            if (!$question instanceof CQuizQuestion || \in_array((int) $question->getType(), self::STRUCTURAL_CONTENT_TYPES, true)) {
                continue;
            }

            if (0 < (int) $question->getDuration()) {
                return true;
            }
        }

        return false;
    }

    private function isBlockCategoryRuntimeEnabled(CQuiz $quiz): bool
    {
        if (!$this->isSettingEnabled('exercise.block_category_questions')) {
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

    private function isImageZoomEnabled(): bool
    {
        $value = $this->settingsManager->getSetting('exercise.quiz_image_zoom', true);
        if (\is_array($value)) {
            return isset($value['options'])
                || true === ($value['value'] ?? false)
                || 'true' === strtolower((string) ($value['value'] ?? ''))
                || '1' === (string) ($value['value'] ?? '');
        }

        return true === $value || 'true' === strtolower((string) $value) || '1' === (string) $value;
    }

    private function shouldShowGlossaryInRuntime(): bool
    {
        $value = strtolower(
            trim((string) $this->settingsManager->getSetting('glossary.show_glossary_in_extra_tools', true))
        );
        $isLearnpathContext = $this->isLearnpathRuntimeContext();

        return match ($value) {
            'exercise' => !$isLearnpathContext,
            'lp' => $isLearnpathContext,
            'exercise_and_lp', 'true', '1', 'yes' => true,
            default => false,
        };
    }

    private function isLearnpathRuntimeContext(): bool
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            return false;
        }

        return 'learnpath' === strtolower(trim((string) $request->query->get('origin', '')))
            || $request->query->has('lp_init')
            || 0 < $request->query->getInt('learnpath_id')
            || 0 < $request->query->getInt('lp_id')
            || 0 < $request->query->getInt('learnpath_item_id')
            || 0 < $request->query->getInt('learnpath_item_view_id');
    }

    /**
     * @return array<int, array{id: int, title: string, description: string}>
     */
    private function getRuntimeGlossaryTerms(Course $course, ?Session $session): array
    {
        $items = $this->glossaryRepository
            ->getResourcesByCourse($course, $session, null, null, true, true)
            ->setMaxResults(200)
            ->getQuery()
            ->getResult()
        ;

        $terms = [];
        foreach ($items as $item) {
            if (!$item instanceof CGlossary || null === $item->getIid()) {
                continue;
            }

            $title = trim(strip_tags($item->getTitle()));
            if ('' === $title) {
                continue;
            }

            $terms[] = [
                'id' => (int) $item->getIid(),
                'title' => $title,
                'description' => trim(strip_tags((string) $item->getDescription())),
            ];
        }

        return $terms;
    }

    private function getKeepAlivePingInterval(): int
    {
        $interval = (int) $this->settingsManager->getSetting('exercise.quiz_keep_alive_ping_interval', true);
        if (0 >= $interval) {
            return 0;
        }

        return max(60, $interval);
    }

    private function isSettingEnabled(string $name): bool
    {
        return 'true' === $this->settingsManager->getSetting($name, true);
    }

    /**
     * @param array<int, int>|null $questionIds
     *
     * @return array<int, array<string, mixed>>
     */
    private function getRuntimeQuestions(CQuiz $quiz, Course $course, ?Session $session, bool $canManage, ?array $questionIds = null, ?TrackEExercise $attempt = null): array
    {
        $relations = $this->getOrderedQuestionRelations($quiz);
        $selectedQuestionIds = null !== $questionIds && [] !== $questionIds
            ? array_values(array_unique(array_map('intval', $questionIds)))
            : null;
        $selectedQuestionIdMap = null !== $selectedQuestionIds ? array_flip($selectedQuestionIds) : [];
        $mediaParents = [];
        $normalizedById = [];
        $includePageBreaks = !$this->usesRandomQuestionOrder($quiz);

        foreach ($relations as $relation) {
            if (!$relation instanceof CQuizRelQuestion) {
                continue;
            }

            $question = $relation->getQuestion();
            if (!$question instanceof CQuizQuestion || null === $question->getIid()) {
                continue;
            }

            $questionId = (int) $question->getIid();
            $questionType = (int) $question->getType();

            if (self::MEDIA_QUESTION === $questionType) {
                $mediaParents[$questionId] = $this->normalizeMediaParentQuestion($question);
                continue;
            }

            $isSelectedAttemptQuestion = null === $selectedQuestionIds || isset($selectedQuestionIdMap[$questionId]);
            $isPageBreak = self::PAGE_BREAK === $questionType;

            if (!$isSelectedAttemptQuestion && !($isPageBreak && $includePageBreaks)) {
                continue;
            }

            if ($isPageBreak && !$includePageBreaks) {
                continue;
            }

            $item = $this->normalizeQuestion(
                $question,
                $relation,
                $course,
                $session,
                $canManage,
                $attempt,
                true === $quiz->getRandomAnswers(),
                $this->buildAnswerShuffleSeed($quiz, $question, $attempt)
            );
            $normalizedById[$questionId] = $item;
        }

        foreach ($normalizedById as $questionId => &$item) {
            $parentId = (int) ($item['parentId'] ?? 0);
            if (0 < $parentId) {
                if (!isset($mediaParents[$parentId])) {
                    $mediaQuestion = $this->entityManager->getRepository(CQuizQuestion::class)->find($parentId);
                    if ($mediaQuestion instanceof CQuizQuestion && self::MEDIA_QUESTION === (int) $mediaQuestion->getType()) {
                        $mediaParents[$parentId] = $this->normalizeMediaParentQuestion($mediaQuestion);
                    }
                }

                if (isset($mediaParents[$parentId])) {
                    $item['parent'] = $mediaParents[$parentId];
                }
            }
        }
        unset($item);

        if (null !== $selectedQuestionIds && $this->usesRandomQuestionOrder($quiz)) {
            $orderedItems = [];
            foreach ($selectedQuestionIds as $position => $questionId) {
                if (!isset($normalizedById[$questionId])) {
                    continue;
                }

                $item = $normalizedById[$questionId];
                $item['position'] = $position + 1;
                $orderedItems[] = $item;
            }

            return $orderedItems;
        }

        $orderedItems = [];
        $position = 1;
        foreach ($normalizedById as $questionId => $item) {
            if (self::PAGE_BREAK !== (int) ($item['type'] ?? 0)) {
                $item['position'] = $position;
                $position++;
            }

            $orderedItems[] = $item;
        }

        return $orderedItems;
    }

    /**
     * @return array<int, CQuizRelQuestion>
     */
    private function getOrderedQuestionRelations(CQuiz $quiz): array
    {
        $relations = $this->entityManager->createQueryBuilder()
            ->select('relQuestion')
            ->addSelect('question')
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

    /**
     * @return array<string, mixed>
     */
    private function normalizeQuestion(CQuizQuestion $question, CQuizRelQuestion $relation, Course $course, ?Session $session, bool $canManage, ?TrackEExercise $attempt = null, bool $shuffleAnswers = false, string $answerShuffleSeed = ""): array
    {
        $type = (int) $question->getType();

        $categorySummary = $this->getQuestionCategorySummary($question);

        return [
            'id' => (int) $question->getIid(),
            'title' => $question->getQuestion(),
            'description' => (string) $question->getDescription(),
            'type' => $type,
            'typeLabel' => $this->getQuestionTypeLabel($type),
            'score' => (float) $question->getPonderation(),
            'position' => (int) $relation->getQuestionOrder(),
            'parentId' => (int) ($question->getParentMediaId() ?? 0),
            'parent' => null,
            'mandatory' => 1 === (int) $question->getMandatory(),
            'duration' => $question->getDuration(),
            'difficulty' => max(1, (int) $question->getLevel()),
            'categoryIds' => $categorySummary['ids'],
            'primaryCategoryId' => $categorySummary['primaryId'],
            'primaryCategoryTitle' => $categorySummary['primaryTitle'],
            'canRevealTeacherData' => $canManage,
            'choices' => $this->getChoiceItems($question, $shuffleAnswers, $answerShuffleSeed),
            'trueFalseOptions' => $this->getQuestionOptions($question),
            'fillBlanks' => $this->getFillBlanksRuntime($question),
            'matching' => $this->getMatchingRuntime($question, $shuffleAnswers, $answerShuffleSeed),
            'draggable' => $this->getDraggableRuntime($question, $shuffleAnswers, $answerShuffleSeed),
            'dropdown' => $this->getDropdownRuntime($question, $shuffleAnswers, $answerShuffleSeed),
            'calculated' => $this->getCalculatedRuntime($question, $attempt),
            'annotation' => $this->getImageRuntime($question, $course, $session, [20]),
            'hotspot' => $this->getHotspotRuntime($question, $course, $session, $canManage),
            'reading' => $this->getReadingRuntime($question),
            'content' => $this->getContentRuntime($question),
            'isContent' => \in_array($type, self::STRUCTURAL_CONTENT_TYPES, true),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeMediaParentQuestion(CQuizQuestion $question): array
    {
        return [
            'id' => (int) $question->getIid(),
            'title' => $question->getQuestion(),
            'description' => (string) $question->getDescription(),
            'type' => self::MEDIA_QUESTION,
            'typeLabel' => $this->getQuestionTypeLabel(self::MEDIA_QUESTION),
            'content' => [
                'title' => $question->getQuestion(),
                'description' => (string) $question->getDescription(),
            ],
        ];
    }

    private function usesRandomQuestionOrder(CQuiz $quiz): bool
    {
        $selectionType = (int) ($quiz->getQuestionSelectionType() ?? 0);

        return 0 < (int) $quiz->getRandom()
            || 0 < (int) $quiz->getRandomByCategory()
            || self::QUESTION_SELECTION_RANDOM === $selectionType
            || ($selectionType >= self::QUESTION_SELECTION_CATEGORIES_ORDERED_QUESTIONS_ORDERED
                && $selectionType <= self::QUESTION_SELECTION_CATEGORIES_RANDOM_QUESTIONS_RANDOM);
    }

    /**
     * @param array<int, array<string, mixed>> $questions
     *
     * @return array{pages: array<int, array<string, mixed>>, usesStructuralPages: bool, forceGroupedByMedia: bool, effectiveOneQuestionPerPage: bool}
     */
    private function buildRuntimePages(CQuiz $quiz, array $questions): array
    {
        $questions = $this->trimPageBreakEdges($questions);
        $oneQuestionPerPage = CQuiz::ONE_PER_PAGE === (int) $quiz->getType();
        $hasMediaWithChildren = $this->hasMediaQuestionChildren($questions);
        $forceGroupedByMedia = $oneQuestionPerPage && $hasMediaWithChildren;
        $pages = [];

        if ($hasMediaWithChildren) {
            $groupIndexByParent = [];
            foreach ($questions as $question) {
                $type = (int) ($question['type'] ?? 0);
                if (self::PAGE_BREAK === $type || self::MEDIA_QUESTION === $type) {
                    continue;
                }

                $parentId = (int) ($question['parentId'] ?? 0);
                if (0 < $parentId) {
                    if (!isset($groupIndexByParent[$parentId])) {
                        $groupIndexByParent[$parentId] = \count($pages);
                        $pages[] = [
                            'type' => 'media_group',
                            'media' => $question['parent'] ?? null,
                            'questionIds' => [],
                        ];
                    }

                    $pages[$groupIndexByParent[$parentId]]['questionIds'][] = (int) $question['id'];
                    continue;
                }

                $pages[] = [
                    'type' => 'questions',
                    'media' => null,
                    'questionIds' => [(int) $question['id']],
                ];
            }

            return [
                'pages' => $this->reindexPages($pages),
                'usesStructuralPages' => true,
                'forceGroupedByMedia' => $forceGroupedByMedia,
                'effectiveOneQuestionPerPage' => true,
            ];
        }

        if (!$oneQuestionPerPage) {
            $currentPage = [
                'type' => 'questions',
                'media' => null,
                'pageBreak' => null,
                'questionIds' => [],
            ];

            foreach ($questions as $question) {
                $type = (int) ($question['type'] ?? 0);
                if (self::MEDIA_QUESTION === $type) {
                    continue;
                }

                if (self::PAGE_BREAK === $type) {
                    if ([] !== $currentPage['questionIds'] || null !== $currentPage['pageBreak']) {
                        $pages[] = $currentPage;
                    }

                    $currentPage = [
                        'type' => 'questions',
                        'media' => null,
                        'pageBreak' => $this->normalizePageBreakForPage($question),
                        'questionIds' => [],
                    ];
                    continue;
                }

                $currentPage['questionIds'][] = (int) $question['id'];
            }

            if ([] !== $currentPage['questionIds'] || null !== $currentPage['pageBreak']) {
                $pages[] = $currentPage;
            }

            if ([] === $pages) {
                $pages[] = [
                    'type' => 'questions',
                    'media' => null,
                    'pageBreak' => null,
                    'questionIds' => [],
                ];
            }

            $usesStructuralPages = 1 < \count($pages) || $this->containsPageBreak($questions);

            return [
                'pages' => $this->reindexPages($pages),
                'usesStructuralPages' => $usesStructuralPages,
                'forceGroupedByMedia' => false,
                'effectiveOneQuestionPerPage' => $usesStructuralPages,
            ];
        }

        foreach ($questions as $question) {
            $type = (int) ($question['type'] ?? 0);
            if (\in_array($type, [self::MEDIA_QUESTION, self::PAGE_BREAK], true)) {
                continue;
            }

            $pages[] = [
                'type' => 'questions',
                'media' => null,
                'pageBreak' => null,
                'questionIds' => [(int) $question['id']],
            ];
        }

        return [
            'pages' => $this->reindexPages($pages),
            'usesStructuralPages' => false,
            'forceGroupedByMedia' => false,
            'effectiveOneQuestionPerPage' => true,
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $questions
     *
     * @return array<int, array<string, mixed>>
     */
    private function trimPageBreakEdges(array $questions): array
    {
        while ([] !== $questions && self::PAGE_BREAK === (int) ($questions[array_key_first($questions)]['type'] ?? 0)) {
            array_shift($questions);
        }

        while ([] !== $questions && self::PAGE_BREAK === (int) ($questions[array_key_last($questions)]['type'] ?? 0)) {
            array_pop($questions);
        }

        return array_values($questions);
    }

    /**
     * @param array<int, array<string, mixed>> $questions
     */
    private function hasMediaQuestionChildren(array $questions): bool
    {
        foreach ($questions as $question) {
            if (0 < (int) ($question['parentId'] ?? 0)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<int, array<string, mixed>> $questions
     */
    private function containsPageBreak(array $questions): bool
    {
        foreach ($questions as $question) {
            if (self::PAGE_BREAK === (int) ($question['type'] ?? 0)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, mixed> $question
     *
     * @return array<string, mixed>
     */
    private function normalizePageBreakForPage(array $question): array
    {
        return [
            'id' => (int) ($question['id'] ?? 0),
            'title' => (string) ($question['title'] ?? ''),
            'description' => (string) ($question['description'] ?? ''),
            'content' => $question['content'] ?? null,
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $pages
     *
     * @return array<int, array<string, mixed>>
     */
    private function reindexPages(array $pages): array
    {
        $result = [];
        foreach ($this->removeEmptyRuntimePages($pages) as $index => $page) {
            $page['index'] = $index;
            $page['number'] = $index + 1;
            $result[] = $page;
        }

        return $result;
    }

    /**
     * @param array<int, array<string, mixed>> $pages
     *
     * @return array<int, array<string, mixed>>
     */
    private function removeEmptyRuntimePages(array $pages): array
    {
        $result = [];
        foreach ($pages as $page) {
            if (!\is_array($page)) {
                continue;
            }

            $questionIds = array_values(array_filter(
                array_map(static fn (mixed $questionId): int => (int) $questionId, (array) ($page['questionIds'] ?? [])),
                static fn (int $questionId): bool => $questionId > 0
            ));
            $page['questionIds'] = $questionIds;

            if ([] !== $questionIds || $this->hasVisibleRuntimePageContent($page)) {
                $result[] = $page;
            }
        }

        return array_values($result);
    }

    /**
     * @param array<string, mixed> $page
     */
    private function hasVisibleRuntimePageContent(array $page): bool
    {
        $media = \is_array($page['media'] ?? null) ? $page['media'] : [];
        $mediaContent = \is_array($media['content'] ?? null) ? $media['content'] : [];
        $pageBreak = \is_array($page['pageBreak'] ?? null) ? $page['pageBreak'] : [];
        $pageBreakContent = \is_array($pageBreak['content'] ?? null) ? $pageBreak['content'] : [];

        return $this->hasNonEmptyText($media['title'] ?? null)
            || $this->hasNonEmptyText($media['description'] ?? null)
            || $this->hasNonEmptyText($mediaContent['description'] ?? null)
            || $this->hasNonEmptyText($pageBreak['title'] ?? null)
            || $this->hasNonEmptyText($pageBreak['description'] ?? null)
            || $this->hasNonEmptyText($pageBreakContent['description'] ?? null);
    }

    private function hasNonEmptyText(mixed $value): bool
    {
        return \is_string($value) && '' !== trim($value);
    }

    /**
     * @param array<int, array<string, mixed>> $questions
     */
    private function countAnswerableQuestions(array $questions): int
    {
        $total = 0;
        foreach ($questions as $question) {
            if (\in_array((int) ($question['type'] ?? 0), self::STRUCTURAL_CONTENT_TYPES, true)) {
                continue;
            }

            $total++;
        }

        return $total;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getChoiceItems(CQuizQuestion $question, bool $shuffleAnswers = false, string $shuffleSeed = ""): array
    {
        $type = (int) $question->getType();
        if (!\in_array($type, [1, 2, 9, 10, 11, 12, 14, 17, 21, 22], true)) {
            return [];
        }

        $items = [];
        foreach ($this->getOrderedAnswers($question) as $answer) {
            $items[] = [
                'id' => (int) $answer->getIid(),
                'answer' => $answer->getAnswer(),
                'position' => (int) $answer->getPosition(),
            ];
        }

        return $this->stableShuffleItems($items, $shuffleAnswers, $shuffleSeed);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getQuestionOptions(CQuizQuestion $question): array
    {
        $options = [];
        foreach ($question->getOptions() as $option) {
            if (!$option instanceof CQuizQuestionOption || null === $option->getIid()) {
                continue;
            }

            $options[] = [
                'id' => (int) $option->getIid(),
                'title' => (string) $option->getTitle(),
                'position' => (int) $option->getPosition(),
            ];
        }

        usort($options, static fn (array $a, array $b): int => ((int) $a['position']) <=> ((int) $b['position']));

        return $options;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function getFillBlanksRuntime(CQuizQuestion $question): ?array
    {
        if (!\in_array((int) $question->getType(), [3, 27], true)) {
            return null;
        }

        $answer = $this->getFirstAnswer($question);
        if (!$answer instanceof CQuizAnswer) {
            return null;
        }

        $parsed = $this->parseFillBlanksAnswer($answer->getAnswer());

        return [
            'segments' => $this->buildFillBlankSegments($parsed['text'], $parsed['sizes'], $parsed['separator']),
            'separator' => $parsed['separator'],
            'switchable' => $parsed['switchable'],
            'caseInsensitive' => 'case:false' === (string) $question->getExtra(),
        ];
    }

    /**
     * @return array{text: string, weights: array<int, float>, sizes: array<int, int>, separator: int, switchable: bool}
     */
    private function parseFillBlanksAnswer(string $encodedAnswer): array
    {
        $parts = explode('::', $encodedAnswer, 2);
        $text = (string) ($parts[0] ?? '');
        $systemString = (string) ($parts[1] ?? '');
        $switchableParts = explode('@', $systemString, 2);
        $details = explode(':', (string) ($switchableParts[0] ?? ''));
        $weights = $this->parseFloatList((string) ($details[0] ?? ''));
        $sizes = [];
        $separator = 0;

        if (\count($details) >= 3) {
            $sizes = $this->parseIntegerList((string) ($details[1] ?? ''));
            $separator = max(0, (int) ($details[2] ?? 0));
        }

        if ([] === $sizes) {
            foreach ($this->extractFillBlankAnswers($text, $separator) as $_blank) {
                $sizes[] = 200;
            }
        }

        return [
            'text' => $text,
            'weights' => $weights,
            'sizes' => $sizes,
            'separator' => $separator,
            'switchable' => '1' === (string) ($switchableParts[1] ?? ''),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildFillBlankSegments(string $text, array $sizes, int $separator): array
    {
        [$start, $end] = $this->getFillBlankSeparators($separator);
        $pattern = '/'.preg_quote($start, '/').'(.*?)'.preg_quote($end, '/').'/s';
        $segments = [];
        $offset = 0;
        $blankIndex = 0;

        if (preg_match_all($pattern, $text, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[0] as $match) {
                $fullMatch = (string) $match[0];
                $position = (int) $match[1];
                $plainText = substr($text, $offset, $position - $offset);

                if ('' !== $plainText) {
                    $segments[] = ['type' => 'text', 'text' => $plainText];
                }

                $segments[] = [
                    'type' => 'blank',
                    'position' => $blankIndex + 1,
                    'inputSize' => (int) ($sizes[$blankIndex] ?? 200),
                ];

                $offset = $position + \strlen($fullMatch);
                ++$blankIndex;
            }
        }

        $remainingText = substr($text, $offset);
        if (false !== $remainingText && '' !== $remainingText) {
            $segments[] = ['type' => 'text', 'text' => $remainingText];
        }

        return $segments;
    }

    /**
     * @return array<int, string>
     */
    private function extractFillBlankAnswers(string $text, int $separator): array
    {
        [$start, $end] = $this->getFillBlankSeparators($separator);
        $pattern = '/'.preg_quote($start, '/').'(.*?)'.preg_quote($end, '/').'/s';
        preg_match_all($pattern, $text, $matches);

        return array_map(static fn (string $value): string => trim($value), $matches[1] ?? []);
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

    /**
     * @return array<string, mixed>|null
     */
    private function getMatchingRuntime(CQuizQuestion $question, bool $shuffleAnswers = false, string $shuffleSeed = ""): ?array
    {
        if (!\in_array((int) $question->getType(), [4, 19, 24, 25], true)) {
            return null;
        }

        $options = [];
        $prompts = [];
        foreach ($this->getOrderedAnswers($question) as $answer) {
            $correct = (int) ($answer->getCorrect() ?? 0);
            if (0 >= $correct) {
                $options[] = [
                    'id' => (int) $answer->getIid(),
                    'label' => $this->getMatchingOptionLabel(\count($options) + 1),
                    'answer' => $answer->getAnswer(),
                    'position' => (int) $answer->getPosition(),
                ];

                continue;
            }

            $prompts[] = [
                'id' => (int) $answer->getIid(),
                'answer' => $answer->getAnswer(),
                'position' => (int) $answer->getPosition(),
            ];
        }

        return [
            'options' => $this->stableShuffleItems($options, $shuffleAnswers, $shuffleSeed.':matching-options'),
            'prompts' => $prompts,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function getDraggableRuntime(CQuizQuestion $question, bool $shuffleAnswers = false, string $shuffleSeed = ""): ?array
    {
        if (18 !== (int) $question->getType()) {
            return null;
        }

        $items = [];
        foreach ($this->getOrderedAnswers($question) as $answer) {
            $targetPosition = (int) ($answer->getCorrect() ?? 0);
            if (0 >= $targetPosition) {
                continue;
            }

            $items[] = [
                'id' => (int) $answer->getIid(),
                'answer' => $answer->getAnswer(),
                'position' => (int) $answer->getPosition(),
                'targetPosition' => $targetPosition,
                'score' => (float) $answer->getPonderation(),
            ];
        }

        return [
            'orientation' => \in_array((string) $question->getExtra(), ['h', 'v'], true) ? (string) $question->getExtra() : 'h',
            'items' => $this->stableShuffleItems($items, $shuffleAnswers, $shuffleSeed.':draggable-items'),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function getDropdownRuntime(CQuizQuestion $question, bool $shuffleAnswers = false, string $shuffleSeed = ""): ?array
    {
        if (!\in_array((int) $question->getType(), [28, 29], true)) {
            return null;
        }

        $options = [];
        foreach ($this->getOrderedAnswers($question) as $answer) {
            $options[] = [
                'id' => (int) $answer->getIid(),
                'answer' => $answer->getAnswer(),
                'position' => (int) $answer->getPosition(),
            ];
        }

        return ['options' => $this->stableShuffleItems($options, $shuffleAnswers, $shuffleSeed.':dropdown-options')];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function getCalculatedRuntime(CQuizQuestion $question, ?TrackEExercise $attempt = null): ?array
    {
        if (!\in_array((int) $question->getType(), self::CALCULATED_TYPES, true)) {
            return null;
        }

        $variations = [];
        foreach ($this->getOrderedAnswers($question) as $answer) {
            $parsedAnswer = $this->parseCalculatedAnswer((string) $answer->getAnswer());
            $variations[] = [
                'id' => (int) $answer->getIid(),
                'text' => $parsedAnswer['text'],
                'position' => (int) $answer->getPosition(),
            ];
        }

        $selectedVariation = $this->selectCalculatedRuntimeVariation($question, $variations, $attempt);

        return [
            'answerId' => null !== $selectedVariation ? (int) $selectedVariation['id'] : null,
            'text' => (string) ($selectedVariation['text'] ?? ''),
            'variations' => $variations,
        ];
    }

    /**
     * The previous runtime stores the random calculated-answer variation in session for the current attempt.
     * The current runtime must keep the same variation stable for an attempt without relying on session state.
     *
     * @param array<int, array{id: int, text: string, position: int}> $variations
     *
     * @return array{id: int, text: string, position: int}|null
     */
    private function selectCalculatedRuntimeVariation(CQuizQuestion $question, array $variations, ?TrackEExercise $attempt): ?array
    {
        if ([] === $variations) {
            return null;
        }

        $savedAnswerId = $this->getSavedCalculatedRuntimeAnswerId($question, $attempt);
        if (0 < $savedAnswerId) {
            foreach ($variations as $variation) {
                if ((int) ($variation['id'] ?? 0) === $savedAnswerId) {
                    return $variation;
                }
            }
        }

        if (!$attempt instanceof TrackEExercise || null === $attempt->getExeId()) {
            return $variations[0];
        }

        $index = abs((int) crc32(sprintf(
            '%d:%d:%d',
            (int) $attempt->getExeId(),
            (int) ($question->getIid() ?? 0),
            \count($variations)
        ))) % \count($variations);

        return $variations[$index] ?? $variations[0];
    }

    private function getSavedCalculatedRuntimeAnswerId(CQuizQuestion $question, ?TrackEExercise $attempt): int
    {
        if (!$attempt instanceof TrackEExercise || null === $attempt->getExeId() || null === $question->getIid()) {
            return 0;
        }

        $row = $this->entityManager->createQueryBuilder()
            ->select('attemptRow.answer')
            ->from(TrackEAttempt::class, 'attemptRow')
            ->andWhere('IDENTITY(attemptRow.trackExercise) = :attemptId')
            ->andWhere('attemptRow.questionId = :questionId')
            ->setParameter('attemptId', (int) $attempt->getExeId(), Types::INTEGER)
            ->setParameter('questionId', (int) $question->getIid(), Types::INTEGER)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        $answer = \is_array($row) ? (string) ($row['answer'] ?? '') : '';
        if ('' === trim($answer)) {
            return 0;
        }

        $parts = explode(':', $answer, 2);
        if (2 !== \count($parts)) {
            return 0;
        }

        return max(0, (int) $parts[0]);
    }

    /**
     * @return array{ids: array<int, int>, primaryId: int, primaryTitle: string}
     */
    private function getQuestionCategorySummary(CQuizQuestion $question): array
    {
        $ids = [];
        $primaryId = 0;
        $primaryTitle = 'General';

        foreach ($question->getCategories() as $category) {
            if (!$category instanceof CQuizQuestionCategory || null === $category->getIid()) {
                continue;
            }

            $categoryId = (int) $category->getIid();
            $ids[] = $categoryId;
            if (0 === $primaryId) {
                $primaryId = $categoryId;
                $primaryTitle = $category->getTitle();
            }
        }

        return [
            'ids' => array_values(array_unique($ids)),
            'primaryId' => $primaryId,
            'primaryTitle' => $primaryTitle,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function getHotspotRuntime(CQuizQuestion $question, Course $course, ?Session $session, bool $canManage): ?array
    {
        if (!\in_array((int) $question->getType(), self::HOTSPOT_TYPES, true)) {
            return null;
        }

        $image = $this->getImageRuntime($question, $course, $session, self::HOTSPOT_TYPES) ?? [
            'imageName' => '',
            'imageUrl' => '',
        ];
        $zones = [];
        $maxClicks = 0;

        foreach ($this->getOrderedAnswers($question) as $answer) {
            $hotspotType = (string) ($answer->getHotspotType() ?: 'square');
            if (!\in_array($hotspotType, ['square', 'circle', 'poly'], true)) {
                continue;
            }

            if (0.0 < (float) $answer->getPonderation()) {
                ++$maxClicks;
            }

            $zone = [
                'id' => (int) $answer->getIid(),
                'answer' => $answer->getAnswer(),
                'position' => (int) $answer->getPosition(),
                'hotspotType' => $hotspotType,
            ];

            if ($canManage) {
                $zone['score'] = (float) $answer->getPonderation();
                $zone['coordinates'] = (string) ($answer->getHotspotCoordinates() ?: '');
            }

            $zones[] = $zone;
        }

        return [
            'imageName' => (string) ($image['imageName'] ?? ''),
            'imageUrl' => (string) ($image['imageUrl'] ?? ''),
            'maxClicks' => max(1, $maxClicks),
            'combination' => 26 === (int) $question->getType(),
            'zones' => $zones,
        ];
    }

    /**
     * @param array<int, int> $allowedTypes
     *
     * @return array<string, mixed>|null
     */
    private function getImageRuntime(CQuizQuestion $question, Course $course, ?Session $session, array $allowedTypes): ?array
    {
        if (!\in_array((int) $question->getType(), $allowedTypes, true)) {
            return null;
        }

        $imageName = '';
        $imageUrl = '';
        $resourceNode = $question->getResourceNode();
        if (null !== $resourceNode) {
            $resourceFile = $resourceNode->getResourceFiles()->first();
            if ($resourceFile instanceof ResourceFile) {
                $imageName = (string) $resourceFile->getOriginalName();
                $imageUrl = $this->appendCourseContextToUrl($this->questionRepository->getHotSpotImageUrl($question), $course, $session);
            }
        }

        return [
            'imageName' => $imageName,
            'imageUrl' => $imageUrl,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function getReadingRuntime(CQuizQuestion $question): ?array
    {
        if (21 !== (int) $question->getType()) {
            return null;
        }

        return [
            'speed' => match (max(1, (int) $question->getLevel())) {
                1 => 50,
                2 => 100,
                3 => 175,
                4 => 300,
                default => 600,
            },
            'text' => (string) $question->getDescription(),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function getContentRuntime(CQuizQuestion $question): ?array
    {
        if (!\in_array((int) $question->getType(), self::STRUCTURAL_CONTENT_TYPES, true)) {
            return null;
        }

        return [
            'title' => $question->getQuestion(),
            'description' => (string) $question->getDescription(),
        ];
    }


    private function buildAnswerShuffleSeed(CQuiz $quiz, CQuizQuestion $question, ?TrackEExercise $attempt): string
    {
        $attemptPart = $attempt instanceof TrackEExercise ? (string) $attempt->getExeId() : 'preview';

        return implode(':', [
            'exercise',
            (string) $quiz->getIid(),
            'attempt',
            $attemptPart,
            'question',
            (string) $question->getIid(),
        ]);
    }

    /**
     * @param array<int, array<string, mixed>> $items
     *
     * @return array<int, array<string, mixed>>
     */
    private function stableShuffleItems(array $items, bool $shuffle, string $seed): array
    {
        if (!$shuffle || 2 > \count($items)) {
            return $items;
        }

        usort(
            $items,
            static function (array $first, array $second) use ($seed): int {
                $firstId = (string) ($first['id'] ?? $first['position'] ?? '');
                $secondId = (string) ($second['id'] ?? $second['position'] ?? '');

                return crc32($seed.':'.$firstId) <=> crc32($seed.':'.$secondId);
            }
        );

        return array_values($items);
    }

    /**
     * @return array<int, CQuizAnswer>
     */
    private function getOrderedAnswers(CQuizQuestion $question): array
    {
        $answers = $this->entityManager->createQueryBuilder()
            ->select('answer')
            ->from(CQuizAnswer::class, 'answer')
            ->andWhere('IDENTITY(answer.question) = :questionId')
            ->setParameter('questionId', (int) $question->getIid(), Types::INTEGER)
            ->orderBy('answer.position', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        return array_values(array_filter($answers, static fn (mixed $answer): bool => $answer instanceof CQuizAnswer));
    }

    private function getFirstAnswer(CQuizQuestion $question): ?CQuizAnswer
    {
        $answers = $this->getOrderedAnswers($question);

        return $answers[0] ?? null;
    }

    private function appendCourseContextToUrl(string $url, Course $course, ?Session $session): string
    {
        if ('' === $url) {
            return '';
        }

        $request = $this->requestStack->getCurrentRequest();
        $params = [
            'cid' => (int) $course->getId(),
            'sid' => (int) ($session?->getId() ?? $request?->query->getInt('sid', 0) ?? 0),
            'gid' => (int) ($request?->query->getInt('gid', 0) ?? 0),
        ];

        return $url.(str_contains($url, '?') ? '&' : '?').http_build_query($params);
    }

    /**
     * @return array{text: string, expectedAnswer: string, formula: string}
     */
    private function parseCalculatedAnswer(string $answer): array
    {
        $parts = explode('@@', $answer, 2);
        $textWithExpectedAnswer = (string) ($parts[0] ?? $answer);
        $formula = (string) ($parts[1] ?? '');
        $expectedAnswer = '';
        $text = $textWithExpectedAnswer;

        if (1 === preg_match('/\[([^\[\]]*)\]\s*$/', $textWithExpectedAnswer, $matches)) {
            $expectedAnswer = trim((string) ($matches[1] ?? ''));
            $text = (string) preg_replace('/\s*\[[^\[\]]*\]\s*$/', '', $textWithExpectedAnswer);
        }

        return [
            'text' => $text,
            'expectedAnswer' => $expectedAnswer,
            'formula' => $formula,
        ];
    }

    /**
     * @return array<int, float>
     */
    private function parseFloatList(string $value): array
    {
        if ('' === trim($value)) {
            return [];
        }

        return array_map(static fn (string $item): float => (float) trim($item), explode(',', $value));
    }

    /**
     * @return array<int, int>
     */
    private function parseIntegerList(string $value): array
    {
        if ('' === trim($value)) {
            return [];
        }

        return array_map(static fn (string $item): int => max(40, (int) trim($item)), explode(',', $value));
    }

    private function getMatchingOptionLabel(int $position): string
    {
        if (1 <= $position && 26 >= $position) {
            return chr(64 + $position);
        }

        return (string) $position;
    }

    /**
     * @param array<int, array<string, mixed>> $questions
     */
    private function getTotalScore(array $questions): float
    {
        $total = 0.0;
        foreach ($questions as $question) {
            if (\in_array((int) ($question['type'] ?? 0), self::STRUCTURAL_CONTENT_TYPES, true)) {
                continue;
            }

            $total += (float) ($question['score'] ?? 0.0);
        }

        return $total;
    }

    private function formatDate(?DateTimeInterface $date): ?string
    {
        if (null === $date) {
            return null;
        }

        return $date->format(DateTimeInterface::ATOM);
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
            13 => 'Oral expression',
            14 => 'Global multiple answer',
            15 => 'Media question',
            16 => 'Calculated answer',
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
     * @return array<string, string>
     */
    private function getLegacyUrls(CQuiz $quiz, Course $course, ?Session $session): array
    {
        $baseParams = [
            'exerciseId' => (int) $quiz->getIid(),
            'cid' => (int) $course->getId(),
            'sid' => (int) ($session?->getId() ?? 0),
        ];

        return [
            'overview' => '/main/exercise/overview.php?'.http_build_query($baseParams),
            'show' => '/main/exercise/exercise_show.php?'.http_build_query($baseParams),
            'submit' => '/main/exercise/exercise_submit.php?'.http_build_query($baseParams),
            'results' => '/main/exercise/exercise_report.php?'.http_build_query($baseParams),
            'list' => '/main/exercise/exercise.php?'.http_build_query($baseParams),
        ];
    }
}
