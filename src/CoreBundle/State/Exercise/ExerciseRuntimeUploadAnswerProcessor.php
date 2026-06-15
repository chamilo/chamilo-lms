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
use Chamilo\CoreBundle\Repository\ResourceNodeRepository;
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

/**
 * Uploads a learner file/audio answer and attaches it to a track_e_attempt row as AttemptFile, matching legacy manual answer tracking.
 *
 * @implements ProcessorInterface<ExerciseRuntimeUploadAnswer, ExerciseRuntimeUploadAnswer>
 */
final readonly class ExerciseRuntimeUploadAnswerProcessor implements ProcessorInterface
{
    private const VISIBILITY_PUBLISHED = 2;
    private const STATUS_INCOMPLETE = 'incomplete';
    private const ORAL_EXPRESSION = 13;
    private const UPLOAD_ANSWER = 23;
    private const ATTEMPT_FILE_RESOURCE_TYPE = 'attempt_file';
    private const ORAL_EXPRESSION_ALLOWED_EXTENSIONS = ['wav', 'ogg'];

    public function __construct(
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager,
        private CQuizRepository $quizRepository,
        private ResourceNodeRepository $resourceNodeRepository,
        private Security $security,
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

        $course = $this->getCourse($request);
        $session = $this->getSession($request);
        $exerciseId = isset($uriVariables['exerciseId']) ? (int) $uriVariables['exerciseId'] : $request->request->getInt('exerciseId');
        $attemptId = isset($uriVariables['attemptId']) ? (int) $uriVariables['attemptId'] : $request->request->getInt('attemptId');
        $questionId = $request->request->getInt('questionId');
        $secondsSpent = max(0, $request->request->getInt('secondsSpent'));

        if (0 >= $exerciseId || 0 >= $attemptId || 0 >= $questionId) {
            throw new BadRequestHttpException('A valid exercise, attempt and question are required.');
        }

        $quiz = $this->getExerciseFromCurrentContext($exerciseId, $course, $session, $this->canManageExercises());
        $attempt = $this->getIncompleteAttempt($attemptId, $quiz, $course, $session, $user);
        $question = $this->getQuestionFromExercise($questionId, $quiz);
        if (!$question instanceof CQuizQuestion) {
            throw new NotFoundHttpException('The requested question was not found in this exercise.');
        }

        if (!\in_array((int) $question->getType(), [self::UPLOAD_ANSWER, self::ORAL_EXPRESSION], true)) {
            throw new BadRequestHttpException('This endpoint only supports upload answer and oral expression questions.');
        }

        if (!$this->questionBelongsToAttempt($questionId, $attempt)) {
            throw new AccessDeniedHttpException('The requested question does not belong to this attempt.');
        }

        $uploadedFiles = $this->getUploadedFiles($request);
        if ([] === $uploadedFiles) {
            throw new BadRequestHttpException('A file is required for this answer.');
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

        $this->entityManager->flush();

        $response = new ExerciseRuntimeUploadAnswer();
        $response->exerciseId = $exerciseId;
        $response->attemptId = $attemptId;
        $response->questionId = $questionId;
        $response->success = true;
        $response->message = 'Draft answer saved';
        $response->files = $this->normalizeAttemptFiles($attemptRow);
        $response->savedAnswer = $this->getSavedAnswerRows($attemptId, $questionId);
        $response->answeredQuestionIds = $this->getAnsweredQuestionIds($attemptId);
        $response->answeredCount = \count($response->answeredQuestionIds);
        $response->canFinish = false;

        return $response;
    }

    private function canUploadAnswer(): bool
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
     * @return array<int, UploadedFile>
     */
    private function getUploadedFiles(Request $request): array
    {
        $file = $request->files->get('file');
        if ($file instanceof UploadedFile) {
            return [$file];
        }

        $files = $request->files->all('files');
        if (!\is_array($files)) {
            return [];
        }

        return array_values(array_filter($files, static fn (mixed $item): bool => $item instanceof UploadedFile));
    }


    private function validateUploadedFileForQuestion(UploadedFile $uploadedFile, CQuizQuestion $question): void
    {
        if (self::ORAL_EXPRESSION !== (int) $question->getType()) {
            return;
        }

        $extension = strtolower((string) $uploadedFile->getClientOriginalExtension());
        if ('' === $extension) {
            $extension = strtolower((string) $uploadedFile->guessExtension());
        }

        if (!\in_array($extension, self::ORAL_EXPRESSION_ALLOWED_EXTENSIONS, true)) {
            throw new BadRequestHttpException('Only WAV and OGG audio files are accepted for oral expression questions.');
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

        return sprintf(
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
