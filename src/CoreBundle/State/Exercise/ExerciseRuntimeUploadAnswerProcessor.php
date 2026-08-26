<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Exercise;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\Exercise\ExerciseRuntimeUploadAnswer;
use Chamilo\CoreBundle\Entity\AttemptFile;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceFile;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\ResourceType;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\Tool;
use Chamilo\CoreBundle\Entity\TrackEAttempt;
use Chamilo\CoreBundle\Entity\TrackEExercise;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CoreBundle\Helpers\CreateUploadedFileHelper;
use Chamilo\CoreBundle\Helpers\UserHelper;
use Chamilo\CoreBundle\Repository\ResourceNodeRepository;
use Chamilo\CoreBundle\Security\Upload\UploadFilenamePolicy;
use Chamilo\CourseBundle\Entity\CLpItem;
use Chamilo\CourseBundle\Entity\CLpItemView;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Entity\CQuizQuestion;
use Chamilo\CourseBundle\Entity\CQuizRelQuestion;
use Chamilo\CourseBundle\Repository\CQuizRepository;
use DateTime;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

use const PATHINFO_EXTENSION;

/**
 * Uploads a learner file, oral recording or completed Office document answer and attaches it to a track_e_attempt row as AttemptFile.
 *
 * @implements ProcessorInterface<ExerciseRuntimeUploadAnswer, ExerciseRuntimeUploadAnswer>
 */
final readonly class ExerciseRuntimeUploadAnswerProcessor implements ProcessorInterface
{
    private const VISIBILITY_PUBLISHED = 2;
    private const LP_ITEM_TYPE_QUIZ = 'quiz';
    private const STATUS_INCOMPLETE = 'incomplete';
    private const ORAL_EXPRESSION = 13;
    private const UPLOAD_ANSWER = 23;
    private const ANSWER_IN_OFFICE_DOC = 30;
    private const ATTEMPT_FILE_RESOURCE_TYPE = 'attempt_file';
    private const ORAL_EXPRESSION_ALLOWED_EXTENSIONS = ['wav', 'ogg'];
    private const OFFICE_DOCUMENT_ALLOWED_EXTENSIONS = ['doc', 'docx', 'xls', 'xlsx'];

    public function __construct(
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager,
        private CQuizRepository $quizRepository,
        private ResourceNodeRepository $resourceNodeRepository,
        private UploadFilenamePolicy $uploadFilenamePolicy,
        private Security $security,
        private CidReqHelper $cidReqHelper,
        private UserHelper $userHelper,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ExerciseRuntimeUploadAnswer
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        if (!$this->canUploadAnswer()) {
            throw new AccessDeniedHttpException('You are not allowed to upload answers for this exercise.');
        }

        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedHttpException('A valid authenticated user is required.');
        }

        $course = $this->cidReqHelper->requireDoctrineCourseEntity();
        $session = $this->cidReqHelper->getDoctrineSessionEntity();
        $jsonPayload = $this->getJsonPayload($request);
        $exerciseId = isset($uriVariables['exerciseId'])
            ? (int) $uriVariables['exerciseId']
            : $this->getRequestInt($request, $jsonPayload, 'exerciseId');
        $attemptId = isset($uriVariables['attemptId'])
            ? (int) $uriVariables['attemptId']
            : $this->getRequestInt($request, $jsonPayload, 'attemptId');
        $questionId = $this->getRequestInt($request, $jsonPayload, 'questionId');
        $secondsSpent = max(0, $this->getRequestInt($request, $jsonPayload, 'secondsSpent'));
        $reviewLater = $this->getRequestNullableBool($request, $jsonPayload, 'reviewLater');

        if ($exerciseId <= 0 || $attemptId <= 0 || $questionId <= 0) {
            throw new BadRequestHttpException('A valid exercise, attempt and question are required.');
        }

        $quiz = $this->getExerciseFromCurrentContext($exerciseId, $course, $session, $this->userHelper->isTeacherOfCurrentCourse());
        $attempt = $this->getIncompleteAttempt($attemptId, $quiz, $course, $session, $user);
        $question = $this->getQuestionFromExercise($questionId, $quiz);
        if (!$question instanceof CQuizQuestion) {
            throw new NotFoundHttpException('The requested question was not found in this exercise.');
        }

        if (!\in_array(
            (int) $question->getType(),
            [self::UPLOAD_ANSWER, self::ORAL_EXPRESSION, self::ANSWER_IN_OFFICE_DOC],
            true
        )) {
            throw new BadRequestHttpException('This endpoint only supports upload answer, oral expression and Office document questions.');
        }

        if (!$this->questionBelongsToAttempt($questionId, $attempt)) {
            throw new AccessDeniedHttpException('The requested question does not belong to this attempt.');
        }

        $this->assertAttemptAcceptsAnswer($attempt, $quiz, $questionId);

        $uploadedFiles = $this->getUploadedFiles($request, $jsonPayload);
        if ([] === $uploadedFiles) {
            $existingAttemptRow = $this->getExistingFileAttemptRow($attemptId, $questionId);
            if (!$existingAttemptRow instanceof TrackEAttempt) {
                throw new BadRequestHttpException('A file is required for this answer.');
            }

            if (null !== $reviewLater) {
                $this->syncReviewQuestion($attempt, $questionId, true === $reviewLater);
            }

            $navigationAction = strtolower(trim($this->getRequestString($request, $jsonPayload, 'navigationAction')));
            $this->lockPreventBackwardsStepIfNeeded($attempt, $quiz, $questionId, $navigationAction);
            $this->entityManager->flush();

            return $this->buildResponse($exerciseId, $attemptId, $questionId, $existingAttemptRow, $question);
        }

        $resourceNodes = [];
        foreach ($uploadedFiles as $uploadedFile) {
            $this->validateUploadedFileForQuestion($uploadedFile, $question);
            $resourceNodes[] = $this->createAttemptFileResourceNode($uploadedFile, $user);
        }

        if ([] === $resourceNodes) {
            throw new BadRequestHttpException('No valid uploaded file could be attached to this answer.');
        }

        $this->deletePreviousDraftRows($attempt, $questionId);

        $attemptRow = (new TrackEAttempt())
            ->setTrackEExercise($attempt)
            ->setUser($user)
            ->setQuestionId($questionId)
            ->setAnswer('')
            ->setTeacherComment('')
            ->setMarks(0.0)
            ->setPosition(0)
            ->setTms(new DateTime())
            ->setSecondsSpent($secondsSpent)
        ;
        $this->entityManager->persist($attemptRow);

        foreach ($resourceNodes as $resourceNode) {
            $attemptFile = new AttemptFile();
            $attemptFile->setResourceNode($resourceNode);
            $attemptRow->addAttemptFile($attemptFile);
            $this->entityManager->persist($attemptFile);
        }

        if (null !== $reviewLater) {
            $this->syncReviewQuestion($attempt, $questionId, true === $reviewLater);
        }

        $navigationAction = strtolower(trim($this->getRequestString($request, $jsonPayload, 'navigationAction')));
        $this->lockPreventBackwardsStepIfNeeded($attempt, $quiz, $questionId, $navigationAction);

        $this->entityManager->flush();

        if (self::ANSWER_IN_OFFICE_DOC === (int) $question->getType()) {
            $firstResourceNode = $resourceNodes[0] ?? null;
            if ($firstResourceNode instanceof ResourceNode && null !== $firstResourceNode->getId()) {
                $attemptRow->setAnswer('onlyoffice:'.(int) $firstResourceNode->getId());
                $this->entityManager->flush();
            }
        }

        return $this->buildResponse($exerciseId, $attemptId, $questionId, $attemptRow, $question);
    }

    private function getExistingFileAttemptRow(int $attemptId, int $questionId): ?TrackEAttempt
    {
        $row = $this->entityManager->createQueryBuilder()
            ->select('saved')
            ->addSelect('attemptFile', 'resourceNode', 'resourceFile')
            ->from(TrackEAttempt::class, 'saved')
            ->innerJoin('saved.attemptFiles', 'attemptFile')
            ->innerJoin('attemptFile.resourceNode', 'resourceNode')
            ->leftJoin('resourceNode.resourceFiles', 'resourceFile')
            ->andWhere('IDENTITY(saved.trackExercise) = :attemptId')
            ->andWhere('saved.questionId = :questionId')
            ->setParameter('attemptId', $attemptId, Types::INTEGER)
            ->setParameter('questionId', $questionId, Types::INTEGER)
            ->orderBy('saved.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return $row instanceof TrackEAttempt ? $row : null;
    }

    private function buildResponse(
        int $exerciseId,
        int $attemptId,
        int $questionId,
        TrackEAttempt $attemptRow,
        CQuizQuestion $question,
    ): ExerciseRuntimeUploadAnswer {
        $response = new ExerciseRuntimeUploadAnswer();
        $response->exerciseId = $exerciseId;
        $response->attemptId = $attemptId;
        $response->questionId = $questionId;
        $response->success = true;
        $response->message = self::ANSWER_IN_OFFICE_DOC === (int) $question->getType()
            ? 'Office document saved'
            : 'Draft answer saved';
        $response->files = $this->normalizeAttemptFiles($attemptRow);
        $response->savedAnswer = $this->getSavedAnswerRows($attemptId, $questionId);
        $response->answeredQuestionIds = $this->getAnsweredQuestionIds($attemptId);
        $response->reviewQuestionIds = $this->getReviewQuestionIds($attemptId);
        $response->answeredCount = \count($response->answeredQuestionIds);
        $response->canFinish = false;

        return $response;
    }

    private function assertAttemptAcceptsAnswer(TrackEExercise $attempt, CQuiz $quiz, int $questionId): void
    {
        if ($this->isAttemptExpired($attempt)) {
            throw new AccessDeniedHttpException('The time for this exercise has expired.');
        }

        if (!$this->isPreventBackwardsEnabled($quiz)) {
            return;
        }

        $questionIndex = $this->getAttemptQuestionIndex($attempt, $questionId);
        if ($questionIndex < 0) {
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
        if ($questionIndex < 0) {
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

    private function canUploadAnswer(): bool
    {
        return $this->security->isGranted('ROLE_CURRENT_COURSE_STUDENT')
            || $this->security->isGranted('ROLE_CURRENT_COURSE_SESSION_STUDENT');
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
            || $learnpathId > 0
            || $learnpathItemId > 0
            || $learnpathItemViewId > 0;

        if (!$hasLearnpathContext || $learnpathId <= 0 || $learnpathItemId <= 0) {
            return false;
        }

        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return false;
        }

        $exerciseId = (int) ($quiz->getIid() ?? 0);
        if ($exerciseId <= 0) {
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

        if ($learnpathItemViewId <= 0) {
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
            if (null === $value || '' === (string) $value) {
                $value = $request->request->get($name);
            }

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
     * @param array<string, mixed> $jsonPayload
     *
     * @return array<int, UploadedFile>
     */
    private function getUploadedFiles(Request $request, array $jsonPayload): array
    {
        $file = $request->files->get('file');
        if ($file instanceof UploadedFile) {
            return [$file];
        }

        $files = $request->files->all('files');
        if (\is_array($files)) {
            $uploadedFiles = array_values(array_filter(
                $files,
                static fn (mixed $item): bool => $item instanceof UploadedFile
            ));
            if ([] !== $uploadedFiles) {
                return $uploadedFiles;
            }
        }

        if ([] === $jsonPayload) {
            return [];
        }

        $fileName = trim((string) ($jsonPayload['fileName'] ?? ''));
        $mimeType = trim((string) ($jsonPayload['mimeType'] ?? 'application/octet-stream'));
        $base64Content = trim((string) ($jsonPayload['base64Content'] ?? ''));
        if ('' === $fileName || '' === $base64Content) {
            return [];
        }

        $content = base64_decode($base64Content, true);
        if (false === $content) {
            throw new BadRequestHttpException('The uploaded file content is not valid base64.');
        }

        $maxFileSize = UploadedFile::getMaxFilesize();
        if ($maxFileSize > 0 && \strlen($content) > $maxFileSize) {
            throw new BadRequestHttpException('The uploaded file exceeds the server upload limit.');
        }

        $decision = $this->uploadFilenamePolicy->filter($fileName);
        if (!($decision['allowed'] ?? false)) {
            throw new BadRequestHttpException('File upload rejected by extension policy.');
        }

        $safeFileName = (string) ($decision['filename'] ?? $fileName);

        return [CreateUploadedFileHelper::fromString($safeFileName, $mimeType ?: 'application/octet-stream', $content)];
    }

    /**
     * @return array<string, mixed>
     */
    private function getJsonPayload(Request $request): array
    {
        if (!str_contains(strtolower((string) $request->headers->get('Content-Type', '')), 'json')) {
            return [];
        }

        try {
            $payload = $request->toArray();
        } catch (Throwable) {
            throw new BadRequestHttpException('The JSON upload payload is invalid.');
        }

        return \is_array($payload) ? $payload : [];
    }

    /**
     * @param array<string, mixed> $jsonPayload
     */
    private function getRequestInt(Request $request, array $jsonPayload, string $key): int
    {
        if ($request->request->has($key)) {
            return $request->request->getInt($key);
        }

        $value = $jsonPayload[$key] ?? null;

        return is_numeric($value) ? (int) $value : 0;
    }

    /**
     * @param array<string, mixed> $jsonPayload
     */
    private function getRequestNullableBool(Request $request, array $jsonPayload, string $key): ?bool
    {
        if ($request->request->has($key)) {
            return $request->request->getBoolean($key);
        }

        if (!\array_key_exists($key, $jsonPayload)) {
            return null;
        }

        $value = $jsonPayload[$key];
        if (\is_bool($value)) {
            return $value;
        }

        if (\is_int($value) || \is_float($value)) {
            return 0 !== (int) $value;
        }

        if (\is_string($value)) {
            $normalized = strtolower(trim($value));
            if (\in_array($normalized, ['1', 'true', 'yes', 'on'], true)) {
                return true;
            }
            if (\in_array($normalized, ['0', 'false', 'no', 'off', ''], true)) {
                return false;
            }
        }

        throw new BadRequestHttpException(\sprintf('The "%s" value must be boolean.', $key));
    }

    /**
     * @param array<string, mixed> $jsonPayload
     */
    private function getRequestString(Request $request, array $jsonPayload, string $key): string
    {
        if ($request->request->has($key)) {
            return (string) $request->request->get($key, '');
        }

        $value = $jsonPayload[$key] ?? '';

        return \is_scalar($value) ? (string) $value : '';
    }

    private function validateUploadedFileForQuestion(UploadedFile $uploadedFile, CQuizQuestion $question): void
    {
        $type = (int) $question->getType();
        $extension = strtolower((string) $uploadedFile->getClientOriginalExtension());
        if ('' === $extension) {
            $extension = strtolower((string) $uploadedFile->guessExtension());
        }

        if (self::ORAL_EXPRESSION === $type) {
            if (!\in_array($extension, self::ORAL_EXPRESSION_ALLOWED_EXTENSIONS, true)) {
                throw new BadRequestHttpException('Only WAV and OGG audio files are accepted for oral expression questions.');
            }

            return;
        }

        if (self::ANSWER_IN_OFFICE_DOC !== $type) {
            return;
        }

        if (!\in_array($extension, self::OFFICE_DOCUMENT_ALLOWED_EXTENSIONS, true)) {
            throw new BadRequestHttpException('Only DOC, DOCX, XLS and XLSX files are accepted for Office document questions.');
        }

        $templateResourceNode = $question->getResourceNode();
        if (!$templateResourceNode instanceof ResourceNode) {
            throw new BadRequestHttpException('This Office document question does not have a template document.');
        }

        $templateResourceFile = $templateResourceNode->getResourceFiles()->first();
        if (!$templateResourceFile instanceof ResourceFile) {
            throw new BadRequestHttpException('This Office document question does not have a template document.');
        }

        $templateName = (string) (
            $templateResourceFile->getOriginalName()
            ?: $templateResourceFile->getTitle()
            ?: $question->getExtra()
        );
        $templateExtension = strtolower((string) pathinfo($templateName, PATHINFO_EXTENSION));
        if ('' !== $templateExtension && $extension !== $templateExtension) {
            throw new BadRequestHttpException('The completed Office document must use the same file format as the template.');
        }
    }

    private function createAttemptFileResourceNode(UploadedFile $uploadedFile, User $user): ResourceNode
    {
        $resourceType = $this->getOrCreateAttemptFileResourceType();

        $originalName = $this->sanitizeFileName($uploadedFile->getClientOriginalName());
        $node = new ResourceNode();
        $node->setTitle($originalName);
        $node->setResourceType($resourceType);
        $node->setCreator($user);
        $this->entityManager->persist($node);

        $resourceFile = new ResourceFile();
        $resourceFile->setResourceNode($node);
        $resourceFile->setFile($uploadedFile);
        $this->entityManager->persist($resourceFile);

        return $node;
    }

    private function getOrCreateAttemptFileResourceType(): ResourceType
    {
        $resourceType = $this->entityManager->getRepository(ResourceType::class)->findOneBy([
            'title' => self::ATTEMPT_FILE_RESOURCE_TYPE,
        ]);
        if ($resourceType instanceof ResourceType) {
            return $resourceType;
        }

        $tool = $this->entityManager->getRepository(Tool::class)->findOneBy([
            'title' => 'quiz',
        ]);
        if (!$tool instanceof Tool) {
            throw new BadRequestHttpException('Missing Tool "quiz" for attempt file uploads.');
        }

        $resourceType = new ResourceType();
        $resourceType->setTitle(self::ATTEMPT_FILE_RESOURCE_TYPE);
        $resourceType->setTool($tool);

        $this->entityManager->persist($resourceType);

        return $resourceType;
    }

    private function sanitizeFileName(string $fileName): string
    {
        $safeName = preg_replace('/[^A-Za-z0-9._-]+/', '_', $fileName);
        if (!\is_string($safeName)) {
            return 'answer_file';
        }

        $safeName = trim($safeName, '._-');

        return '' !== $safeName ? $safeName : 'answer_file';
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
     * @return array<int, array{id: int, name: string, size: int, mimeType: string, url: string}>
     */
    private function normalizeAttemptFiles(TrackEAttempt $attemptRow): array
    {
        $files = [];
        foreach ($attemptRow->getAttemptFiles() as $attemptFile) {
            $resourceNode = $attemptFile->getResourceNode();
            if (!$resourceNode instanceof ResourceNode) {
                continue;
            }

            $resourceFile = $resourceNode->getResourceFiles()->first();
            $url = $this->getAttemptFileDownloadUrl($attemptRow, $resourceNode);

            $files[] = [
                'id' => (int) $resourceNode->getId(),
                'name' => (string) ($resourceFile instanceof ResourceFile ? $resourceFile->getOriginalName() : $resourceNode->getTitle()),
                'size' => (int) ($resourceFile instanceof ResourceFile ? $resourceFile->getSize() : 0),
                'mimeType' => (string) ($resourceFile instanceof ResourceFile ? $resourceFile->getMimeType() : ''),
                'url' => $url,
            ];
        }

        return $files;
    }

    private function getAttemptFileDownloadUrl(TrackEAttempt $attemptRow, ResourceNode $resourceNode): string
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
            foreach (['gid', 'origin', 'learnpath_id', 'learnpath_item_id', 'learnpath_item_view_id'] as $queryKey) {
                $value = $request->query->get($queryKey);
                if (null !== $value && '' !== (string) $value) {
                    $query[$queryKey] = (string) $value;
                }
            }
        }

        return \sprintf(
            '/api/exercise/runtime/%d/attempt/%d/file/%d/download?%s',
            (int) $quiz->getIid(),
            (int) $attempt->getExeId(),
            (int) $resourceNode->getId(),
            http_build_query($query)
        );
    }

    /**
     * @return array<int, array{answer: string, position: int|null}>
     */
    private function getSavedAnswerRows(int $attemptId, int $questionId): array
    {
        $rows = $this->entityManager->createQueryBuilder()
            ->select('saved.answer AS answer')
            ->addSelect('saved.position AS position')
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
