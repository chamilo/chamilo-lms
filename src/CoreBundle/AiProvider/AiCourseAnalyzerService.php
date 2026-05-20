<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\AiProvider;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceFile;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Entity\CLpItem;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Entity\CQuizAnswer;
use Chamilo\CourseBundle\Entity\CQuizQuestion;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\File;
use Throwable;
use ZipArchive;

use const ENT_QUOTES;
use const ENT_SUBSTITUTE;
use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;
use const PATHINFO_EXTENSION;
use function http_build_query;

final class AiCourseAnalyzerService
{
    private const MAX_LESSONS = 20;
    private const MAX_ITEMS_PER_LESSON = 120;
    private const MAX_STANDALONE_DOCUMENTS = 25;
    private const MAX_STANDALONE_EXERCISES = 20;
    private const MAX_EXERCISES = 20;
    private const MAX_QUESTIONS_PER_EXERCISE = 80;
    private const MAX_ANSWERS_PER_QUESTION = 20;
    private const MAX_CHARS_PER_DOCUMENT = 12000;
    private const MAX_TOTAL_TEXT_CHARS = 90000;

    /**
     * @var string[]
     */
    private const DOCUMENT_LIKE_ITEM_TYPES = [
        'document',
        'video',
    ];

    /**
     * @var string[]
     */
    private const EXERCISE_LIKE_ITEM_TYPES = [
        'quiz',
        'exercise',
        'test',
    ];

    /**
     * @var string[]
     */
    private const READABLE_EXTENSIONS = [
        'txt',
        'md',
        'markdown',
        'html',
        'htm',
        'csv',
        'json',
        'xml',
        'yaml',
        'yml',
        'log',
        'docx',
    ];

    /**
     * @var string[]
     */
    private const READABLE_MIME_PREFIXES = [
        'text/',
        'application/json',
        'application/xml',
        'application/xhtml+xml',
        'application/x-yaml',
        'application/yaml',
    ];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AiChatCompletionClientInterface $chatCompletionClient,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function analyze(
        Course $course,
        ?Session $session,
        string $teacherPrompt,
        string $provider,
        bool $includeStandaloneDocuments = false,
        bool $includeStandaloneExercises = false,
    ): array {
        $payload = $this->buildPayload(
            $course,
            $session,
            $teacherPrompt,
            $includeStandaloneDocuments,
            $includeStandaloneExercises,
        );
        $messages = $this->buildMessages($payload);

        $rawResponse = $this->chatCompletionClient->chat($provider, $messages, [
            'temperature' => 0.2,
            'max_tokens' => 6000,
            'throw_on_error' => true,
        ]);

        $structuredResponse = $this->decodeStructuredResponse($rawResponse);
        if (\is_array($structuredResponse)) {
            $structuredResponse = $this->normalizeStructuredResponse($payload, $structuredResponse);
        }

        return [
            'payload' => $payload,
            'rawResponse' => $rawResponse,
            'structuredResponse' => $structuredResponse,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function buildPayload(
        Course $course,
        ?Session $session,
        string $teacherPrompt,
        bool $includeStandaloneDocuments = false,
        bool $includeStandaloneExercises = false,
    ): array {
        $totalCharacters = 0;
        $lessonDocumentIds = [];
        $lessonExerciseIds = [];
        $lessons = $this->collectVisibleLessons(
            $course,
            $session,
            $totalCharacters,
            $lessonDocumentIds,
            $lessonExerciseIds,
        );
        $standaloneDocuments = $includeStandaloneDocuments
            ? $this->collectVisibleStandaloneDocuments($course, $session, $lessonDocumentIds, $totalCharacters)
            : [];
        $standaloneExercises = $includeStandaloneExercises
            ? $this->collectVisibleStandaloneExercises($course, $session, $lessonExerciseIds)
            : [];

        return [
            'course' => [
                'id' => $course->getId(),
                'code' => $course->getCode(),
                'title' => $course->getTitle(),
                'description' => $this->cleanText((string) $course->getDescription()),
            ],
            'teacherPrompt' => trim($teacherPrompt),
            'analysisScope' => [
                'defaultScope' => 'visible_lessons',
                'includeStandaloneDocuments' => $includeStandaloneDocuments,
                'includeStandaloneExercises' => $includeStandaloneExercises,
                'standaloneDocumentsDescription' => 'Standalone documents are visible course documents that were not referenced by any analyzed lesson item.',
                'standaloneExercisesDescription' => 'Standalone exercises are visible course tests that were not referenced by any analyzed lesson item.',
            ],
            'limits' => [
                'maxLessons' => self::MAX_LESSONS,
                'maxItemsPerLesson' => self::MAX_ITEMS_PER_LESSON,
                'maxStandaloneDocuments' => self::MAX_STANDALONE_DOCUMENTS,
                'maxStandaloneExercises' => self::MAX_STANDALONE_EXERCISES,
                'maxExercises' => self::MAX_EXERCISES,
                'maxQuestionsPerExercise' => self::MAX_QUESTIONS_PER_EXERCISE,
                'maxCharactersPerDocument' => self::MAX_CHARS_PER_DOCUMENT,
                'maxTotalTextCharacters' => self::MAX_TOTAL_TEXT_CHARS,
            ],
            'lessons' => $lessons,
            'standaloneDocuments' => $standaloneDocuments,
            'standaloneExercises' => $standaloneExercises,
        ];
    }

    /**
     * @param array<int, int> $lessonDocumentIds
     *
     * @return array<int, array<string, mixed>>
     */
    private function collectVisibleLessons(
        Course $course,
        ?Session $session,
        int &$totalCharacters,
        array &$lessonDocumentIds,
        array &$lessonExerciseIds,
    ): array {
        $qb = $this->entityManager->createQueryBuilder();
        $qb
            ->select('learningPath', 'resourceNode', 'resourceLink')
            ->from(CLp::class, 'learningPath')
            ->innerJoin('learningPath.resourceNode', 'resourceNode')
            ->innerJoin('resourceNode.resourceLinks', 'resourceLink')
            ->andWhere('resourceLink.course = :course')
            ->andWhere('resourceLink.visibility = :visibility')
            ->setParameter('course', $course)
            ->setParameter('visibility', ResourceLink::VISIBILITY_PUBLISHED, Types::INTEGER)
            ->setMaxResults(self::MAX_LESSONS)
            ->orderBy('resourceLink.displayOrder', 'ASC')
        ;

        if ($session instanceof Session) {
            $qb
                ->andWhere('(resourceLink.session IS NULL OR resourceLink.session = :session)')
                ->setParameter('session', $session)
            ;
        } else {
            $qb->andWhere('resourceLink.session IS NULL');
        }

        /** @var CLp[] $learningPaths */
        $learningPaths = $qb->getQuery()->getResult();

        $lessons = [];
        foreach ($learningPaths as $learningPath) {
            $items = $this->sortLessonItems($learningPath->getItems()->toArray());
            $lessonItems = [];
            $itemCount = 0;

            foreach ($items as $item) {
                if ($itemCount >= self::MAX_ITEMS_PER_LESSON) {
                    break;
                }

                $lessonItems[] = $this->buildLessonItemPayload(
                    $learningPath,
                    $item,
                    $course,
                    $session,
                    $totalCharacters,
                    $lessonDocumentIds,
                    $lessonExerciseIds,
                );
                $itemCount++;
            }

            $resourceNode = $learningPath->getResourceNode();

            $lessons[] = [
                'id' => $learningPath->getIid(),
                'title' => $learningPath->getTitle(),
                'description' => $this->cleanText((string) $learningPath->getDescription()),
                'type' => $learningPath->getLpType(),
                'resourceNodeId' => $resourceNode instanceof ResourceNode ? $resourceNode->getId() : null,
                'resourcePath' => $resourceNode instanceof ResourceNode ? $resourceNode->getPathForDisplay() : null,
                'editUrl' => $this->buildLearningPathEditUrl($learningPath, $course, $session),
                'items' => $lessonItems,
            ];
        }

        return $lessons;
    }

    /**
     * @param CLpItem[] $items
     *
     * @return CLpItem[]
     */
    private function sortLessonItems(array $items): array
    {
        usort(
            $items,
            static function (CLpItem $left, CLpItem $right): int {
                $leftOrder = (int) $left->getDisplayOrder();
                $rightOrder = (int) $right->getDisplayOrder();

                if ($leftOrder === $rightOrder) {
                    return (int) $left->getIid() <=> (int) $right->getIid();
                }

                return $leftOrder <=> $rightOrder;
            }
        );

        return $items;
    }

    /**
     * @param array<int, int> $lessonDocumentIds
     * @param array<int, int> $lessonExerciseIds
     *
     * @return array<string, mixed>
     */
    private function buildLessonItemPayload(
        CLp $learningPath,
        CLpItem $item,
        Course $course,
        ?Session $session,
        int &$totalCharacters,
        array &$lessonDocumentIds,
        array &$lessonExerciseIds,
    ): array {
        $itemType = strtolower($item->getItemType());
        $payload = [
            'id' => $item->getIid(),
            'title' => $item->getTitle(),
            'type' => $itemType,
            'kind' => $this->getLessonItemKind($itemType),
            'ref' => $item->getRef(),
            'description' => $this->cleanText((string) $item->getDescription()),
            'path' => $item->getPath(),
            'position' => $item->getDisplayOrder(),
            'level' => $item->getLvl(),
            'parentItemId' => $item->getParentItemId(),
            'previousItemId' => $item->getPreviousItemId(),
            'nextItemId' => $item->getNextItemId(),
            'prerequisite' => $this->cleanText((string) $item->getPrerequisite()),
            'maxScore' => $item->getMaxScore(),
            'masteryScore' => $item->getMasteryScore(),
            'editUrl' => $this->buildLearningPathItemEditUrl($learningPath, $item, $course, $session),
            'contentIncluded' => false,
            'notice' => null,
        ];

        if (\in_array($itemType, self::DOCUMENT_LIKE_ITEM_TYPES, true)) {
            $document = $this->findVisibleDocumentByReference($item->getRef(), $course, $session);
            if (!$document instanceof CDocument) {
                $payload['notice'] = 'The referenced lesson document is not visible or no longer available.';

                return $payload;
            }

            $documentId = (int) $document->getIid();
            $lessonDocumentIds[$documentId] = $documentId;
            $payload['document'] = $this->buildDocumentPayload($document, $totalCharacters, $course, $session);
            $payload['contentIncluded'] = true === ($payload['document']['textIncluded'] ?? false);

            return $payload;
        }

        if (\in_array($itemType, self::EXERCISE_LIKE_ITEM_TYPES, true)) {
            $quiz = $this->findVisibleQuizByReference($item->getRef(), $course, $session);
            if (!$quiz instanceof CQuiz) {
                $payload['notice'] = 'The referenced lesson exercise is not visible or no longer available.';

                return $payload;
            }

            $exerciseId = (int) $quiz->getIid();
            $lessonExerciseIds[$exerciseId] = $exerciseId;
            $payload['exercise'] = $this->buildExercisePayload($quiz, $course, $session);
            $payload['contentIncluded'] = true;

            return $payload;
        }

        $payload['notice'] = 'This lesson item type is included as metadata only in this proof of concept.';

        return $payload;
    }

    private function getLessonItemKind(string $itemType): string
    {
        if (\in_array($itemType, self::DOCUMENT_LIKE_ITEM_TYPES, true)) {
            return 'document';
        }

        if (\in_array($itemType, self::EXERCISE_LIKE_ITEM_TYPES, true)) {
            return 'exercise';
        }

        if ('dir' === $itemType || 'step' === $itemType) {
            return 'section';
        }

        return 'metadata';
    }

    private function findVisibleDocumentByReference(string $reference, Course $course, ?Session $session): ?CDocument
    {
        $documentId = $this->normalizePositiveIntegerReference($reference);
        if (null === $documentId) {
            return null;
        }

        /** @var CDocument|null $document */
        $document = $this->entityManager->getRepository(CDocument::class)->find($documentId);
        if (!$document instanceof CDocument || 'file' !== $document->getFiletype()) {
            return null;
        }

        $resourceNode = $document->getResourceNode();
        if (!$resourceNode instanceof ResourceNode || !$this->isResourceNodeVisibleInCourse($resourceNode, $course, $session)) {
            return null;
        }

        return $document;
    }

    private function findVisibleQuizByReference(string $reference, Course $course, ?Session $session): ?CQuiz
    {
        $quizId = $this->normalizePositiveIntegerReference($reference);
        if (null === $quizId) {
            return null;
        }

        /** @var CQuiz|null $quiz */
        $quiz = $this->entityManager->getRepository(CQuiz::class)->find($quizId);
        if (!$quiz instanceof CQuiz) {
            return null;
        }

        $resourceNode = $quiz->getResourceNode();
        if (!$resourceNode instanceof ResourceNode || !$this->isResourceNodeVisibleInCourse($resourceNode, $course, $session)) {
            return null;
        }

        return $quiz;
    }

    private function normalizePositiveIntegerReference(string $reference): ?int
    {
        if (!ctype_digit($reference)) {
            return null;
        }

        $id = (int) $reference;

        return $id > 0 ? $id : null;
    }

    private function isResourceNodeVisibleInCourse(ResourceNode $resourceNode, Course $course, ?Session $session): bool
    {
        foreach ($resourceNode->getResourceLinks() as $resourceLink) {
            if (!$resourceLink instanceof ResourceLink) {
                continue;
            }

            if (!$this->isSameCourse($resourceLink->getCourse(), $course)) {
                continue;
            }

            if (ResourceLink::VISIBILITY_PUBLISHED !== $resourceLink->getVisibility()) {
                continue;
            }

            if (!$session instanceof Session && null === $resourceLink->getSession()) {
                return true;
            }

            if ($session instanceof Session && (null === $resourceLink->getSession() || $this->isSameSession($resourceLink->getSession(), $session))) {
                return true;
            }
        }

        return false;
    }

    private function isSameCourse(?Course $left, Course $right): bool
    {
        return $left instanceof Course && $left->getId() === $right->getId();
    }

    private function isSameSession(?Session $left, Session $right): bool
    {
        return $left instanceof Session && $left->getId() === $right->getId();
    }

    /**
     * @param array<string, string|int> $parameters
     */
    private function buildLegacyCourseUrl(string $path, Course $course, ?Session $session, array $parameters = []): string
    {
        $baseParameters = [
            'cid' => (int) $course->getId(),
            'sid' => $session instanceof Session ? (int) $session->getId() : 0,
            'gid' => 0,
            'gradebook' => 0,
            'origin' => '',
        ];

        return $path.'?'.http_build_query(array_merge($baseParameters, $parameters));
    }

    private function buildLearningPathEditUrl(CLp $learningPath, Course $course, ?Session $session): string
    {
        return $this->buildLegacyCourseUrl('/main/lp/lp_controller.php', $course, $session, [
            'action' => 'admin_view',
            'lp_id' => (int) $learningPath->getIid(),
            'isStudentView' => 'false',
        ]);
    }

    private function buildLearningPathItemEditUrl(CLp $learningPath, CLpItem $item, Course $course, ?Session $session): string
    {
        return $this->buildLegacyCourseUrl('/main/lp/lp_controller.php', $course, $session, [
            'action' => 'edit_item',
            'view' => 'build',
            'id' => (int) $item->getIid(),
            'lp_id' => (int) $learningPath->getIid(),
            'path_item' => $item->getRef(),
        ]);
    }

    private function buildDocumentEditUrl(CDocument $document, Course $course, ?Session $session): string
    {
        $courseResourceNode = $course->getResourceNode();
        if (!$courseResourceNode instanceof ResourceNode || null === $courseResourceNode->getId()) {
            return $this->buildLegacyCourseUrl('/main/document/document.php', $course, $session);
        }

        $query = [
            'cid' => (int) $course->getId(),
            'sid' => $session instanceof Session ? (int) $session->getId() : 0,
            'id' => '/api/documents/'.(int) $document->getIid(),
            'filetype' => $document->getFiletype(),
        ];

        return '/resources/document/'.(int) $courseResourceNode->getId().'/edit_file?'.http_build_query($query);
    }

    private function buildExerciseEditUrl(CQuiz $quiz, Course $course, ?Session $session): string
    {
        return $this->buildLegacyCourseUrl('/main/exercise/exercise_admin.php', $course, $session, [
            'exerciseId' => (int) $quiz->getIid(),
        ]);
    }


    /**
     * @param array<int, int> $excludedDocumentIds
     *
     * @return array<int, array<string, mixed>>
     */
    private function collectVisibleStandaloneDocuments(
        Course $course,
        ?Session $session,
        array $excludedDocumentIds,
        int &$totalCharacters,
    ): array {
        $qb = $this->entityManager->createQueryBuilder();
        $qb
            ->select('document', 'resourceNode', 'resourceLink', 'resourceFile')
            ->from(CDocument::class, 'document')
            ->innerJoin('document.resourceNode', 'resourceNode')
            ->innerJoin('resourceNode.resourceLinks', 'resourceLink')
            ->leftJoin('resourceNode.resourceFiles', 'resourceFile')
            ->andWhere('resourceLink.course = :course')
            ->andWhere('resourceLink.visibility = :visibility')
            ->andWhere('document.filetype = :filetype')
            ->setParameter('course', $course)
            ->setParameter('visibility', ResourceLink::VISIBILITY_PUBLISHED, Types::INTEGER)
            ->setParameter('filetype', 'file', Types::STRING)
            ->setMaxResults(self::MAX_STANDALONE_DOCUMENTS)
            ->orderBy('resourceLink.displayOrder', 'ASC')
        ;

        if ([] !== $excludedDocumentIds) {
            $qb
                ->andWhere('document.iid NOT IN (:excludedDocumentIds)')
                ->setParameter('excludedDocumentIds', array_values($excludedDocumentIds), ArrayParameterType::INTEGER)
            ;
        }

        if ($session instanceof Session) {
            $qb
                ->andWhere('(resourceLink.session IS NULL OR resourceLink.session = :session)')
                ->setParameter('session', $session)
            ;
        } else {
            $qb->andWhere('resourceLink.session IS NULL');
        }

        /** @var CDocument[] $documentList */
        $documentList = $qb->getQuery()->getResult();

        $documents = [];
        foreach ($documentList as $document) {
            $documents[] = $this->buildDocumentPayload($document, $totalCharacters, $course, $session);
        }

        return $documents;
    }

    /**
     * @param array<int, int> $excludedExerciseIds
     *
     * @return array<int, array<string, mixed>>
     */
    private function collectVisibleStandaloneExercises(Course $course, ?Session $session, array $excludedExerciseIds): array
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb
            ->select('quiz', 'resourceNode', 'resourceLink', 'quizQuestionRel', 'question', 'answer')
            ->from(CQuiz::class, 'quiz')
            ->innerJoin('quiz.resourceNode', 'resourceNode')
            ->innerJoin('resourceNode.resourceLinks', 'resourceLink')
            ->leftJoin('quiz.questions', 'quizQuestionRel')
            ->leftJoin('quizQuestionRel.question', 'question')
            ->leftJoin('question.answers', 'answer')
            ->andWhere('resourceLink.course = :course')
            ->andWhere('resourceLink.visibility = :visibility')
            ->setParameter('course', $course)
            ->setParameter('visibility', ResourceLink::VISIBILITY_PUBLISHED, Types::INTEGER)
            ->setMaxResults(self::MAX_STANDALONE_EXERCISES)
            ->orderBy('resourceLink.displayOrder', 'ASC')
        ;

        if ([] !== $excludedExerciseIds) {
            $qb
                ->andWhere('quiz.iid NOT IN (:excludedExerciseIds)')
                ->setParameter('excludedExerciseIds', array_values($excludedExerciseIds), ArrayParameterType::INTEGER)
            ;
        }

        if ($session instanceof Session) {
            $qb
                ->andWhere('(resourceLink.session IS NULL OR resourceLink.session = :session)')
                ->setParameter('session', $session)
            ;
        } else {
            $qb->andWhere('resourceLink.session IS NULL');
        }

        /** @var CQuiz[] $quizList */
        $quizList = $qb->getQuery()->getResult();

        $exercises = [];
        foreach ($quizList as $quiz) {
            $exercises[] = $this->buildExercisePayload($quiz, $course, $session);
        }

        return $exercises;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildDocumentPayload(
        CDocument $document,
        int &$totalCharacters,
        ?Course $course = null,
        ?Session $session = null,
    ): array
    {
        $resourceNode = $document->getResourceNode();

        $metadata = [
            'id' => $document->getIid(),
            'title' => $document->getTitle(),
            'comment' => $this->cleanText((string) $document->getComment()),
            'fileType' => $document->getFiletype(),
            'resourceNodeId' => $resourceNode instanceof ResourceNode ? $resourceNode->getId() : null,
            'resourcePath' => $resourceNode instanceof ResourceNode ? $resourceNode->getPathForDisplay() : null,
            'editUrl' => $course instanceof Course ? $this->buildDocumentEditUrl($document, $course, $session) : null,
            'fileName' => null,
            'mimeType' => null,
            'size' => null,
            'textIncluded' => false,
            'text' => '',
            'notice' => null,
        ];

        if (!$resourceNode instanceof ResourceNode) {
            $metadata['notice'] = 'The document has no resource node.';

            return $metadata;
        }

        /** @var ResourceFile|false $resourceFile */
        $resourceFile = $resourceNode->getResourceFiles()->first();

        if (!$resourceFile instanceof ResourceFile) {
            $metadata['notice'] = 'The document has no attached resource file.';

            return $metadata;
        }

        $metadata['fileName'] = $resourceFile->getOriginalName() ?: $resourceFile->getTitle();
        $metadata['mimeType'] = $resourceFile->getMimeType();
        $metadata['size'] = $resourceFile->getSize();

        $remainingCharacters = self::MAX_TOTAL_TEXT_CHARS - $totalCharacters;
        if ($remainingCharacters <= 0) {
            $metadata['notice'] = 'The global text limit was reached before this document could be included.';

            return $metadata;
        }

        $text = $this->extractResourceFileText($resourceFile, min(self::MAX_CHARS_PER_DOCUMENT, $remainingCharacters));
        if ('' === $text) {
            $metadata['notice'] = 'This file type is listed but was not included as text in this proof of concept.';

            return $metadata;
        }

        $metadata['textIncluded'] = true;
        $metadata['text'] = $text;
        $totalCharacters += mb_strlen($text);

        return $metadata;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildExercisePayload(CQuiz $quiz, ?Course $course = null, ?Session $session = null): array
    {
        $questions = [];

        foreach ($quiz->getQuestions() as $quizQuestionRel) {
            if (\count($questions) >= self::MAX_QUESTIONS_PER_EXERCISE) {
                break;
            }

            if (!method_exists($quizQuestionRel, 'getQuestion')) {
                continue;
            }

            $question = $quizQuestionRel->getQuestion();
            if (!$question instanceof CQuizQuestion) {
                continue;
            }

            $answers = [];
            foreach ($question->getAnswers() as $answer) {
                if (\count($answers) >= self::MAX_ANSWERS_PER_QUESTION) {
                    break;
                }

                if (!$answer instanceof CQuizAnswer) {
                    continue;
                }

                $answers[] = [
                    'answer' => $this->cleanText($answer->getAnswer()),
                    'correct' => $answer->getCorrect(),
                    'comment' => $this->cleanText((string) $answer->getComment()),
                    'ponderation' => $answer->getPonderation(),
                    'position' => $answer->getPosition(),
                ];
            }

            $questions[] = [
                'id' => $question->getIid(),
                'type' => $question->getType(),
                'question' => $this->cleanText($question->getQuestion()),
                'description' => $this->cleanText((string) $question->getDescription()),
                'ponderation' => $question->getPonderation(),
                'position' => $question->getPosition(),
                'answers' => $answers,
            ];
        }

        return [
            'id' => $quiz->getIid(),
            'title' => $quiz->getTitle(),
            'description' => $this->cleanText((string) $quiz->getDescription()),
            'type' => $quiz->getType(),
            'editUrl' => $course instanceof Course ? $this->buildExerciseEditUrl($quiz, $course, $session) : null,
            'questions' => $questions,
        ];
    }

    private function extractResourceFileText(ResourceFile $resourceFile, int $maxCharacters): string
    {
        if ($maxCharacters <= 0) {
            return '';
        }

        $file = $resourceFile->getFile();
        if (!$file instanceof File || !$file->isReadable()) {
            return '';
        }

        $fileName = (string) ($resourceFile->getOriginalName() ?: $resourceFile->getTitle() ?: $file->getFilename());
        $extension = strtolower((string) pathinfo($fileName, PATHINFO_EXTENSION));
        $mimeType = strtolower((string) $resourceFile->getMimeType());

        if ('docx' === $extension) {
            return $this->truncateText($this->extractDocxText($file), $maxCharacters);
        }

        if (!$this->isPlainTextResource($extension, $mimeType)) {
            return '';
        }

        $contents = file_get_contents($file->getPathname());
        if (!\is_string($contents)) {
            return '';
        }

        return $this->truncateText($this->cleanText($contents), $maxCharacters);
    }

    private function isPlainTextResource(string $extension, string $mimeType): bool
    {
        if (\in_array($extension, self::READABLE_EXTENSIONS, true) && 'docx' !== $extension) {
            return true;
        }

        foreach (self::READABLE_MIME_PREFIXES as $prefix) {
            if (str_starts_with($mimeType, $prefix)) {
                return true;
            }
        }

        return false;
    }

    private function extractDocxText(File $file): string
    {
        if (!class_exists(ZipArchive::class)) {
            return '';
        }

        $zip = new ZipArchive();
        if (true !== $zip->open($file->getPathname())) {
            return '';
        }

        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        if (!\is_string($xml)) {
            return '';
        }

        $xml = preg_replace('/<\/w:p>/', "\n", $xml) ?? $xml;
        $xml = strip_tags($xml);

        return $this->cleanText($xml);
    }

    private function cleanText(string $text): string
    {
        $text = html_entity_decode($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $text = strip_tags($text);
        $text = preg_replace('/[ \t]+/', ' ', $text) ?? $text;
        $text = preg_replace('/\R{3,}/', "\n\n", $text) ?? $text;

        return trim($text);
    }

    private function truncateText(string $text, int $maxCharacters): string
    {
        $text = trim($text);
        if (mb_strlen($text) <= $maxCharacters) {
            return $text;
        }

        return mb_substr($text, 0, $maxCharacters).'…';
    }


    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $structured
     *
     * @return array<string, mixed>
     */
    private function normalizeStructuredResponse(array $payload, array $structured): array
    {
        $structuredLessons = $this->arrayList($structured['lessons'] ?? []);
        $structuredStandaloneDocuments = $this->arrayList($structured['standaloneDocuments'] ?? []);
        $structuredStandaloneExercises = $this->arrayList($structured['standaloneExercises'] ?? []);

        $structured['lessons'] = $this->normalizeLessonFeedbackList(
            $this->arrayList($payload['lessons'] ?? []),
            $structuredLessons,
        );
        $structured['standaloneDocuments'] = $this->normalizeStandaloneDocumentFeedbackList(
            $this->arrayList($payload['standaloneDocuments'] ?? []),
            $structuredStandaloneDocuments,
        );
        $structured['standaloneExercises'] = $this->normalizeStandaloneExerciseFeedbackList(
            $this->arrayList($payload['standaloneExercises'] ?? []),
            $structuredStandaloneExercises,
        );

        return $structured;
    }

    /**
     * @param array<int, mixed> $payloadLessons
     * @param array<int, mixed> $structuredLessons
     *
     * @return array<int, array<string, mixed>>
     */
    private function normalizeLessonFeedbackList(array $payloadLessons, array $structuredLessons): array
    {
        $items = [];

        foreach ($payloadLessons as $payloadLesson) {
            if (!\is_array($payloadLesson)) {
                continue;
            }

            $structuredLesson = $this->findStructuredFeedback($structuredLessons, $payloadLesson);
            $items[] = [
                'id' => $payloadLesson['id'] ?? null,
                'title' => $payloadLesson['title'] ?? 'Learning path',
                'feedback' => $this->stringValue($structuredLesson['feedback'] ?? ''),
                'sequenceFeedback' => $this->stringValue($structuredLesson['sequenceFeedback'] ?? ''),
                'items' => $this->normalizeLessonItemFeedbackList(
                    $this->arrayList($payloadLesson['items'] ?? []),
                    $this->arrayList($structuredLesson['items'] ?? []),
                ),
                'recommendations' => $this->stringList($structuredLesson['recommendations'] ?? []),
            ];
        }

        return $items;
    }

    /**
     * @param array<int, mixed> $payloadItems
     * @param array<int, mixed> $structuredItems
     *
     * @return array<int, array<string, mixed>>
     */
    private function normalizeLessonItemFeedbackList(array $payloadItems, array $structuredItems): array
    {
        $items = [];

        foreach ($payloadItems as $payloadItem) {
            if (!\is_array($payloadItem)) {
                continue;
            }

            $structuredItem = $this->findStructuredFeedback($structuredItems, $payloadItem);
            $exercise = \is_array($payloadItem['exercise'] ?? null) ? $payloadItem['exercise'] : [];

            $items[] = [
                'id' => $payloadItem['id'] ?? null,
                'title' => $payloadItem['title'] ?? 'Lesson item',
                'type' => $payloadItem['type'] ?? '',
                'purpose' => $this->stringValue($structuredItem['purpose'] ?? ''),
                'feedback' => $this->stringValue($structuredItem['feedback'] ?? ''),
                'questions' => $this->normalizeQuestionFeedbackList(
                    $this->arrayList($exercise['questions'] ?? []),
                    $this->arrayList($structuredItem['questions'] ?? []),
                ),
                'recommendations' => $this->stringList($structuredItem['recommendations'] ?? []),
            ];
        }

        return $items;
    }

    /**
     * @param array<int, mixed> $payloadDocuments
     * @param array<int, mixed> $structuredDocuments
     *
     * @return array<int, array<string, mixed>>
     */
    private function normalizeStandaloneDocumentFeedbackList(array $payloadDocuments, array $structuredDocuments): array
    {
        $items = [];

        foreach ($payloadDocuments as $payloadDocument) {
            if (!\is_array($payloadDocument)) {
                continue;
            }

            $structuredDocument = $this->findStructuredFeedback($structuredDocuments, $payloadDocument);

            $items[] = [
                'id' => $payloadDocument['id'] ?? null,
                'title' => $payloadDocument['title'] ?? 'Document',
                'feedback' => $this->stringValue($structuredDocument['feedback'] ?? ''),
                'recommendations' => $this->stringList($structuredDocument['recommendations'] ?? []),
            ];
        }

        return $items;
    }

    /**
     * @param array<int, mixed> $payloadExercises
     * @param array<int, mixed> $structuredExercises
     *
     * @return array<int, array<string, mixed>>
     */
    private function normalizeStandaloneExerciseFeedbackList(array $payloadExercises, array $structuredExercises): array
    {
        $items = [];

        foreach ($payloadExercises as $payloadExercise) {
            if (!\is_array($payloadExercise)) {
                continue;
            }

            $structuredExercise = $this->findStructuredFeedback($structuredExercises, $payloadExercise);

            $items[] = [
                'id' => $payloadExercise['id'] ?? null,
                'title' => $payloadExercise['title'] ?? 'Exercise',
                'feedback' => $this->stringValue($structuredExercise['feedback'] ?? ''),
                'questions' => $this->normalizeQuestionFeedbackList(
                    $this->arrayList($payloadExercise['questions'] ?? []),
                    $this->arrayList($structuredExercise['questions'] ?? []),
                ),
                'recommendations' => $this->stringList($structuredExercise['recommendations'] ?? []),
            ];
        }

        return $items;
    }

    /**
     * @param array<int, mixed> $payloadQuestions
     * @param array<int, mixed> $structuredQuestions
     *
     * @return array<int, array<string, mixed>>
     */
    private function normalizeQuestionFeedbackList(array $payloadQuestions, array $structuredQuestions): array
    {
        $items = [];

        foreach ($payloadQuestions as $payloadQuestion) {
            if (!\is_array($payloadQuestion)) {
                continue;
            }

            $structuredQuestion = $this->findStructuredFeedback($structuredQuestions, $payloadQuestion);

            $items[] = [
                'id' => $payloadQuestion['id'] ?? null,
                'question' => $payloadQuestion['question'] ?? 'Question',
                'feedback' => $this->stringValue($structuredQuestion['feedback'] ?? ''),
                'answersFeedback' => $this->stringValue($structuredQuestion['answersFeedback'] ?? ''),
                'recommendations' => $this->stringList($structuredQuestion['recommendations'] ?? []),
            ];
        }

        return $items;
    }

    /**
     * @param array<int, mixed> $structuredItems
     * @param array<string, mixed> $payloadItem
     *
     * @return array<string, mixed>
     */
    private function findStructuredFeedback(array $structuredItems, array $payloadItem): array
    {
        $payloadId = $this->positiveIntegerOrNull($payloadItem['id'] ?? null);
        $payloadTitle = $this->normalizeFeedbackKey($payloadItem['title'] ?? '');
        $payloadFileName = $this->normalizeFeedbackKey($payloadItem['fileName'] ?? '');
        $payloadQuestion = $this->normalizeFeedbackKey($payloadItem['question'] ?? '');

        foreach ($structuredItems as $structuredItem) {
            if (!\is_array($structuredItem)) {
                continue;
            }

            $structuredId = $this->positiveIntegerOrNull($structuredItem['id'] ?? null);
            if (null !== $payloadId && $payloadId === $structuredId) {
                return $structuredItem;
            }

            $structuredTitle = $this->normalizeFeedbackKey($structuredItem['title'] ?? '');
            if ('' !== $payloadTitle && $payloadTitle === $structuredTitle) {
                return $structuredItem;
            }

            if ('' !== $payloadFileName && $payloadFileName === $structuredTitle) {
                return $structuredItem;
            }

            $structuredQuestion = $this->normalizeFeedbackKey($structuredItem['question'] ?? '');
            if ('' !== $payloadQuestion && $payloadQuestion === $structuredQuestion) {
                return $structuredItem;
            }
        }

        return [];
    }

    /**
     * @return array<int, mixed>
     */
    private function arrayList(mixed $value): array
    {
        if (!\is_array($value)) {
            return [];
        }

        return array_values($value);
    }

    private function stringValue(mixed $value): string
    {
        return \is_string($value) ? $value : '';
    }

    /**
     * @return array<int, string>
     */
    private function stringList(mixed $value): array
    {
        if (!\is_array($value)) {
            return [];
        }

        $items = [];
        foreach ($value as $item) {
            if (\is_string($item) && '' !== trim($item)) {
                $items[] = $item;
            }
        }

        return $items;
    }

    private function positiveIntegerOrNull(mixed $value): ?int
    {
        if (\is_int($value) && $value > 0) {
            return $value;
        }

        if (\is_string($value) && ctype_digit($value)) {
            $integerValue = (int) $value;

            return $integerValue > 0 ? $integerValue : null;
        }

        return null;
    }

    private function normalizeFeedbackKey(mixed $value): string
    {
        if (!\is_string($value)) {
            return '';
        }

        $value = mb_strtolower(trim($value));
        $value = preg_replace('/\s+/', ' ', $value) ?? $value;

        return $value;
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<int, array{role:string,content:string}>
     */
    private function buildMessages(array $payload): array
    {
        $payloadJson = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (!\is_string($payloadJson)) {
            $payloadJson = '{}';
        }

        return [
            [
                'role' => 'system',
                'content' => implode("\n", [
                    'You are an expert instructional designer and e-learning quality reviewer.',
                    'Analyze the Chamilo course by prioritizing the visible learning path structure.',
                    'Review each lesson as an ordered pedagogical sequence, and explain the purpose and quality of each lesson item when possible.',
                    'When an item or standalone exercise includes questions and answers, analyze question wording, answer quality, correct-answer alignment, feedback comments and scoring.',
                    'Documents and exercises inside lessons are the primary analysis scope. Standalone documents and standalone exercises are secondary and only included when the teacher explicitly selected those options.',
                    'Return only valid JSON, without markdown fences.',
                    'Do not invent lessons, items, documents, exercises or questions.',
                    'Use the exact id and title/question values from the payload whenever possible.',
                    'Return one feedback object for every lesson, lesson item, standalone document, standalone exercise and exercise question present in the payload. If a file has metadata only, still provide a short recommendation based on its title, type and position.',
                    'Use this exact structure:',
                    '{',
                    '  "generalFeedback": "string",',
                    '  "strengths": ["string"],',
                    '  "risks": ["string"],',
                    '  "recommendations": ["string"],',
                    '  "lessons": [',
                    '    {',
                    '      "id": 0,',
                    '      "title": "string",',
                    '      "feedback": "string",',
                    '      "sequenceFeedback": "string",',
                    '      "items": [{"id": 0, "title": "string", "type": "string", "purpose": "string", "feedback": "string", "questions": [{"id": 0, "question": "string", "feedback": "string", "answersFeedback": "string", "recommendations": ["string"]}], "recommendations": ["string"]}],',
                    '      "recommendations": ["string"]',
                    '    }',
                    '  ],',
                    '  "standaloneDocuments": [{"id": 0, "title": "string", "feedback": "string", "recommendations": ["string"]}],',
                    '  "standaloneExercises": [{"id": 0, "title": "string", "feedback": "string", "questions": [{"id": 0, "question": "string", "feedback": "string", "answersFeedback": "string", "recommendations": ["string"]}], "recommendations": ["string"]}]',
                    '}',
                ]),
            ],
            [
                'role' => 'user',
                'content' => $payloadJson,
            ],
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decodeStructuredResponse(string $rawResponse): ?array
    {
        $response = trim($rawResponse);
        $response = preg_replace('/^```(?:json)?\s*/i', '', $response) ?? $response;
        $response = preg_replace('/\s*```$/', '', $response) ?? $response;

        try {
            $decoded = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            return null;
        }

        return \is_array($decoded) ? $decoded : null;
    }
}
