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
use Chamilo\CourseBundle\Entity\CStudentPublication;
use Chamilo\CourseBundle\Entity\CSurvey;
use Chamilo\CourseBundle\Repository\CDocumentRepository;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
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

final class AiCourseAnalyzerService
{
    private const MAX_LESSONS = 20;
    private const MAX_ITEMS_PER_LESSON = 120;
    private const MAX_STANDALONE_DOCUMENTS = 25;
    private const MAX_STANDALONE_EXERCISES = 20;
    private const MAX_ASSIGNMENTS = 20;
    private const MAX_SURVEYS = 15;
    private const MAX_SURVEY_QUESTIONS = 50;
    private const MAX_SURVEY_OPTIONS = 20;
    private const MAX_EXERCISES = 20;
    private const MAX_QUESTIONS_PER_EXERCISE = 80;
    private const MAX_ANSWERS_PER_QUESTION = 20;
    private const MAX_CHARS_PER_DOCUMENT = 12000;
    private const MAX_TOTAL_TEXT_CHARS = 90000;
    private const MAX_ANALYSIS_OUTPUT_TOKENS = 12000;
    private const MAX_COMPACT_OUTPUT_TOKENS = 7000;

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
        private readonly CDocumentRepository $documentRepository,
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
        bool $includeDraftResources = false,
    ): array
    {
        $payload = $this->buildPayload(
            $course,
            $session,
            $teacherPrompt,
            $includeStandaloneDocuments,
            $includeStandaloneExercises,
            $includeDraftResources,
        );
        $messages = $this->buildMessages($payload, false);
        $rawResponse = $this->requestStructuredAnalysis(
            $provider,
            $messages,
            self::MAX_ANALYSIS_OUTPUT_TOKENS,
        );

        $structuredResponse = $this->decodeStructuredResponse($rawResponse);
        $responseRepaired = false;
        $responseMode = 'full';

        if (!\is_array($structuredResponse)) {
            /*
             * A long per-resource analysis can reach a provider output limit.
             * Retry once with a compact contract instead of exposing a
             * truncated JSON response to the MCP client.
             */
            $responseRepaired = true;
            $responseMode = 'compact_retry';
            $rawResponse = $this->requestStructuredAnalysis(
                $provider,
                $this->buildMessages($payload, true),
                self::MAX_COMPACT_OUTPUT_TOKENS,
            );
            $structuredResponse = $this->decodeStructuredResponse($rawResponse);
        }

        if (\is_array($structuredResponse)) {
            $structuredResponse = $this->normalizeStructuredResponse($payload, $structuredResponse);
        }

        return [
            'payload' => $payload,
            'rawResponse' => $rawResponse,
            'structuredResponse' => $structuredResponse,
            'responseRepaired' => $responseRepaired,
            'responseMode' => $responseMode,
            'rawResponseLength' => mb_strlen($rawResponse),
            'payloadStats' => $this->buildPayloadStats($payload),
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
        bool $includeDraftResources = false,
    ): array
    {
        $totalCharacters = 0;
        $lessonDocumentIds = [];
        $lessonExerciseIds = [];
        $lessons = $this->collectVisibleLessons(
            $course,
            $session,
            $totalCharacters,
            $lessonDocumentIds,
            $lessonExerciseIds,
            $includeDraftResources,
        );
        $standaloneDocuments = $includeStandaloneDocuments
            ? $this->collectVisibleStandaloneDocuments($course, $session, $lessonDocumentIds, $totalCharacters, $includeDraftResources)
            : [];
        $standaloneExercises = $includeStandaloneExercises
            ? $this->collectVisibleStandaloneExercises($course, $session, $lessonExerciseIds, $includeDraftResources)
            : [];
        $assignments = $this->collectReviewAssignments(
            $course,
            $session,
            $includeDraftResources,
        );
        $surveys = $this->collectReviewSurveys(
            $course,
            $session,
            $includeDraftResources,
        );

        return [
            'course' => [
                'id' => $course->getId(),
                'code' => $course->getCode(),
                'title' => $course->getTitle(),
                'description' => $this->cleanText((string) $course->getDescription()),
            ],
            'teacherPrompt' => trim($teacherPrompt),
            'analysisScope' => [
                'defaultScope' => $includeDraftResources
                    ? 'teacher_course_resources_including_drafts'
                    : 'published_lessons',
                'includeStandaloneDocuments' => $includeStandaloneDocuments,
                'includeStandaloneExercises' => $includeStandaloneExercises,
                'includeDraftResources' => $includeDraftResources,
                'resourceVisibilityDescription' => $includeDraftResources
                    ? 'Published, pending and draft resources are included for teacher review. Their visibility is reported in each payload item.'
                    : 'Only published resources are included.',
                'standaloneDocumentsDescription' => $includeDraftResources
                    ? 'Standalone documents include teacher-reviewable published, pending and draft course documents that were not referenced by any analyzed lesson item.'
                    : 'Standalone documents are published course documents that were not referenced by any analyzed lesson item.',
                'standaloneExercisesDescription' => $includeDraftResources
                    ? 'Standalone exercises include teacher-reviewable published, pending and draft course tests that were not referenced by any analyzed lesson item.'
                    : 'Standalone exercises are published course tests that were not referenced by any analyzed lesson item.',
            ],
            'limits' => [
                'maxLessons' => self::MAX_LESSONS,
                'maxItemsPerLesson' => self::MAX_ITEMS_PER_LESSON,
                'maxStandaloneDocuments' => self::MAX_STANDALONE_DOCUMENTS,
                'maxStandaloneExercises' => self::MAX_STANDALONE_EXERCISES,
                'maxAssignments' => self::MAX_ASSIGNMENTS,
                'maxSurveys' => self::MAX_SURVEYS,
                'maxSurveyQuestions' => self::MAX_SURVEY_QUESTIONS,
                'maxExercises' => self::MAX_EXERCISES,
                'maxQuestionsPerExercise' => self::MAX_QUESTIONS_PER_EXERCISE,
                'maxCharactersPerDocument' => self::MAX_CHARS_PER_DOCUMENT,
                'maxTotalTextCharacters' => self::MAX_TOTAL_TEXT_CHARS,
            ],
            'lessons' => $lessons,
            'standaloneDocuments' => $standaloneDocuments,
            'standaloneExercises' => $standaloneExercises,
            'assignments' => $assignments,
            'surveys' => $surveys,
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
        bool $includeDraftResources,
    ): array
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb
            ->select('learningPath', 'resourceNode', 'resourceLink')
            ->from(CLp::class, 'learningPath')
            ->innerJoin('learningPath.resourceNode', 'resourceNode')
            ->innerJoin('resourceNode.resourceLinks', 'resourceLink')
            ->andWhere('resourceLink.course = :course')
            ->setParameter('course', (int) $course->getId())
            ->setMaxResults(self::MAX_LESSONS)
            ->orderBy('resourceLink.displayOrder', 'ASC')
        ;

        $this->applyReviewVisibilityFilter(
            $qb,
            'resourceLink',
            $includeDraftResources,
        );

        if ($session instanceof Session) {
            $qb
                ->andWhere('(resourceLink.session IS NULL OR resourceLink.session = :session)')
                ->setParameter('session', (int) $session->getId())
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
                    $includeDraftResources,
                );
                $itemCount++;
            }

            $resourceNode = $learningPath->getResourceNode();
            $resourceLink = $resourceNode instanceof ResourceNode
                ? $this->findReviewableResourceLink($resourceNode, $course, $session, $includeDraftResources)
                : null;

            $lessons[] = [
                'id' => $learningPath->getIid(),
                'title' => $learningPath->getTitle(),
                'description' => $this->cleanText((string) $learningPath->getDescription()),
                'type' => $learningPath->getLpType(),
                'resourceNodeId' => $resourceNode instanceof ResourceNode ? $resourceNode->getId() : null,
                'resourcePath' => $resourceNode instanceof ResourceNode ? $resourceNode->getPathForDisplay() : null,
                'editUrl' => $this->buildLearningPathEditUrl($learningPath, $course, $session),
                'visibility' => $this->buildVisibilityPayload($resourceLink),
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
        bool $includeDraftResources,
    ): array
    {
        $itemType = strtolower($item->getItemType());
        $resourceReference = $this->resolveLessonItemResourceReference($item);

        $payload = [
            'id' => $item->getIid(),
            'title' => $item->getTitle(),
            'type' => $itemType,
            'kind' => $this->getLessonItemKind($itemType),
            'ref' => $item->getRef(),
            'description' => $this->cleanText((string) $item->getDescription()),
            'path' => $item->getPath(),
            'resourceReferenceId' => $resourceReference['id'],
            'resourceReferenceSource' => $resourceReference['source'],
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
            $document = $this->findVisibleDocumentByReference(
                $resourceReference['value'],
                $course,
                $session,
                $includeDraftResources,
            );
            if (!$document instanceof CDocument) {
                $payload['notice'] = 'The referenced lesson document is not available in the selected teacher-review scope.';

                return $payload;
            }

            $documentId = (int) $document->getIid();
            $lessonDocumentIds[$documentId] = $documentId;
            $payload['document'] = $this->buildDocumentPayload($document, $totalCharacters, $course, $session, $includeDraftResources);
            $payload['contentIncluded'] = true === ($payload['document']['textIncluded'] ?? false);

            return $payload;
        }

        if (\in_array($itemType, self::EXERCISE_LIKE_ITEM_TYPES, true)) {
            $quiz = $this->findVisibleQuizByReference(
                $resourceReference['value'],
                $course,
                $session,
                $includeDraftResources,
            );
            if (!$quiz instanceof CQuiz) {
                $payload['notice'] = 'The referenced lesson exercise is not available in the selected teacher-review scope.';

                return $payload;
            }

            $exerciseId = (int) $quiz->getIid();
            $lessonExerciseIds[$exerciseId] = $exerciseId;
            $payload['exercise'] = $this->buildExercisePayload($quiz, $course, $session, $includeDraftResources);
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

    /**
     * Legacy learning-path items do not consistently use the `ref` column.
     * Items created through learnpath::add_item() can keep `ref` empty and
     * store the numeric document or quiz identifier in `path`.
     *
     * Prefer a valid numeric `ref` and use a numeric `path` only as the
     * compatibility fallback. Non-numeric paths remain metadata and cannot be
     * mistaken for local resource identifiers.
     *
     * @return array{id:int|null,value:string,source:string|null}
     */
    private function resolveLessonItemResourceReference(CLpItem $item): array
    {
        $references = [
            'ref' => trim((string) $item->getRef()),
            'path' => trim((string) $item->getPath()),
        ];

        foreach ($references as $source => $reference) {
            $resourceId = $this->normalizePositiveIntegerReference($reference);
            if (null === $resourceId) {
                continue;
            }

            return [
                'id' => $resourceId,
                'value' => (string) $resourceId,
                'source' => $source,
            ];
        }

        return [
            'id' => null,
            'value' => '',
            'source' => null,
        ];
    }

    private function findVisibleDocumentByReference(
        string $reference,
        Course $course,
        ?Session $session,
        bool $includeDraftResources,
    ): ?CDocument
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
        if (!$resourceNode instanceof ResourceNode || !$this->isResourceNodeVisibleInCourse($resourceNode, $course, $session, $includeDraftResources)) {
            return null;
        }

        return $document;
    }

    private function findVisibleQuizByReference(
        string $reference,
        Course $course,
        ?Session $session,
        bool $includeDraftResources,
    ): ?CQuiz
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
        if (!$resourceNode instanceof ResourceNode || !$this->isResourceNodeVisibleInCourse($resourceNode, $course, $session, $includeDraftResources)) {
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

    private function isResourceNodeVisibleInCourse(
        ResourceNode $resourceNode,
        Course $course,
        ?Session $session,
        bool $includeDraftResources,
    ): bool
    {
        return $this->findReviewableResourceLink(
            $resourceNode,
            $course,
            $session,
            $includeDraftResources,
        ) instanceof ResourceLink;
    }

    private function findReviewableResourceLink(
        ResourceNode $resourceNode,
        Course $course,
        ?Session $session,
        bool $includeDraftResources,
    ): ?ResourceLink
    {
        foreach ($resourceNode->getResourceLinks() as $resourceLink) {
            if (!$resourceLink instanceof ResourceLink) {
                continue;
            }

            if (!$this->isSameCourse($resourceLink->getCourse(), $course)) {
                continue;
            }

            if (!$this->isReviewableVisibility($resourceLink->getVisibility(), $includeDraftResources)) {
                continue;
            }

            if (!$session instanceof Session && null === $resourceLink->getSession()) {
                return $resourceLink;
            }

            if ($session instanceof Session && (null === $resourceLink->getSession() || $this->isSameSession($resourceLink->getSession(), $session))) {
                return $resourceLink;
            }
        }

        return null;
    }

    private function isReviewableVisibility(int $visibility, bool $includeDraftResources): bool
    {
        if (!$includeDraftResources) {
            return ResourceLink::VISIBILITY_PUBLISHED === $visibility;
        }

        return \in_array($visibility, [
            ResourceLink::VISIBILITY_DRAFT,
            ResourceLink::VISIBILITY_PENDING,
            ResourceLink::VISIBILITY_PUBLISHED,
        ], true);
    }

    /**
     * @return array{value:int|null,label:string,published:bool}
     */
    private function buildVisibilityPayload(?ResourceLink $resourceLink): array
    {
        $visibility = $resourceLink?->getVisibility();

        return [
            'value' => $visibility,
            'label' => match ($visibility) {
                ResourceLink::VISIBILITY_DRAFT => 'draft',
                ResourceLink::VISIBILITY_PENDING => 'pending',
                ResourceLink::VISIBILITY_PUBLISHED => 'published',
                default => 'unknown',
            },
            'published' => ResourceLink::VISIBILITY_PUBLISHED === $visibility,
        ];
    }

    private function applyReviewVisibilityFilter(
        QueryBuilder $queryBuilder,
        string $resourceLinkAlias,
        bool $includeDraftResources,
    ): void
    {
        if ($includeDraftResources) {
            $queryBuilder
                ->andWhere($resourceLinkAlias.'.visibility IN (:reviewVisibilities)')
                ->setParameter('reviewVisibilities', [
                    ResourceLink::VISIBILITY_DRAFT,
                    ResourceLink::VISIBILITY_PENDING,
                    ResourceLink::VISIBILITY_PUBLISHED,
                ], ArrayParameterType::INTEGER)
            ;

            return;
        }

        $queryBuilder
            ->andWhere($resourceLinkAlias.'.visibility = :publishedVisibility')
            ->setParameter('publishedVisibility', ResourceLink::VISIBILITY_PUBLISHED, Types::INTEGER)
        ;
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
        bool $includeDraftResources,
    ): array
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb
            ->select('document', 'resourceNode', 'resourceLink', 'resourceFile')
            ->from(CDocument::class, 'document')
            ->innerJoin('document.resourceNode', 'resourceNode')
            ->innerJoin('resourceNode.resourceLinks', 'resourceLink')
            ->leftJoin('resourceNode.resourceFiles', 'resourceFile')
            ->andWhere('resourceLink.course = :course')
            ->andWhere('document.filetype = :filetype')
            ->setParameter('course', (int) $course->getId())
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

        $this->applyReviewVisibilityFilter(
            $qb,
            'resourceLink',
            $includeDraftResources,
        );

        if ($session instanceof Session) {
            $qb
                ->andWhere('(resourceLink.session IS NULL OR resourceLink.session = :session)')
                ->setParameter('session', (int) $session->getId())
            ;
        } else {
            $qb->andWhere('resourceLink.session IS NULL');
        }

        /** @var CDocument[] $documentList */
        $documentList = $qb->getQuery()->getResult();

        $documents = [];
        foreach ($documentList as $document) {
            $documents[] = $this->buildDocumentPayload($document, $totalCharacters, $course, $session, $includeDraftResources);
        }

        return $documents;
    }

    /**
     * @param array<int, int> $excludedExerciseIds
     *
     * @return array<int, array<string, mixed>>
     */
    private function collectVisibleStandaloneExercises(
        Course $course,
        ?Session $session,
        array $excludedExerciseIds,
        bool $includeDraftResources,
    ): array
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
            ->setParameter('course', (int) $course->getId())
            ->setMaxResults(self::MAX_STANDALONE_EXERCISES)
            ->orderBy('resourceLink.displayOrder', 'ASC')
        ;

        if ([] !== $excludedExerciseIds) {
            $qb
                ->andWhere('quiz.iid NOT IN (:excludedExerciseIds)')
                ->setParameter('excludedExerciseIds', array_values($excludedExerciseIds), ArrayParameterType::INTEGER)
            ;
        }

        $this->applyReviewVisibilityFilter(
            $qb,
            'resourceLink',
            $includeDraftResources,
        );

        if ($session instanceof Session) {
            $qb
                ->andWhere('(resourceLink.session IS NULL OR resourceLink.session = :session)')
                ->setParameter('session', (int) $session->getId())
            ;
        } else {
            $qb->andWhere('resourceLink.session IS NULL');
        }

        /** @var CQuiz[] $quizList */
        $quizList = $qb->getQuery()->getResult();

        $exercises = [];
        foreach ($quizList as $quiz) {
            $exercises[] = $this->buildExercisePayload($quiz, $course, $session, $includeDraftResources);
        }

        return $exercises;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function collectReviewAssignments(
        Course $course,
        ?Session $session,
        bool $includeDraftResources,
    ): array {
        $qb = $this->entityManager->createQueryBuilder();
        $qb
            ->select('assignment', 'resourceNode', 'resourceLink')
            ->from(CStudentPublication::class, 'assignment')
            ->innerJoin('assignment.resourceNode', 'resourceNode')
            ->innerJoin('resourceNode.resourceLinks', 'resourceLink')
            ->andWhere('resourceLink.course = :course')
            ->andWhere('assignment.publicationParent IS NULL')
            ->setParameter('course', (int) $course->getId())
            ->setMaxResults(self::MAX_ASSIGNMENTS)
            ->orderBy('resourceLink.displayOrder', 'ASC')
        ;

        $this->applyReviewVisibilityFilter(
            $qb,
            'resourceLink',
            $includeDraftResources,
        );

        if ($session instanceof Session) {
            $qb
                ->andWhere('(resourceLink.session IS NULL OR resourceLink.session = :session)')
                ->setParameter('session', (int) $session->getId())
            ;
        } else {
            $qb->andWhere('resourceLink.session IS NULL');
        }

        /** @var CStudentPublication[] $assignmentList */
        $assignmentList = $qb->getQuery()->getResult();

        $assignments = [];
        foreach ($assignmentList as $assignment) {
            $resourceNode = $assignment->getResourceNode();
            $resourceLink = $resourceNode instanceof ResourceNode
                ? $this->findReviewableResourceLink(
                    $resourceNode,
                    $course,
                    $session,
                    $includeDraftResources,
                )
                : null;

            $assignments[] = [
                'id' => $assignment->getIid(),
                'title' => $assignment->getTitle(),
                'description' => $this->cleanText((string) $assignment->getDescription()),
                'maximumScore' => $assignment->getQualification(),
                'submissionMode' => $assignment->getAllowTextAssignment(),
                'active' => $assignment->getActive(),
                'visibility' => $this->buildVisibilityPayload($resourceLink),
            ];
        }

        return $assignments;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function collectReviewSurveys(
        Course $course,
        ?Session $session,
        bool $includeDraftResources,
    ): array {
        $qb = $this->entityManager->createQueryBuilder();
        $qb
            ->select('survey', 'resourceNode', 'resourceLink')
            ->from(CSurvey::class, 'survey')
            ->innerJoin('survey.resourceNode', 'resourceNode')
            ->innerJoin('resourceNode.resourceLinks', 'resourceLink')
            ->andWhere('resourceLink.course = :course')
            ->setParameter('course', (int) $course->getId())
            ->setMaxResults(self::MAX_SURVEYS)
            ->orderBy('resourceLink.displayOrder', 'ASC')
        ;

        $this->applyReviewVisibilityFilter(
            $qb,
            'resourceLink',
            $includeDraftResources,
        );

        if ($session instanceof Session) {
            $qb
                ->andWhere('(resourceLink.session IS NULL OR resourceLink.session = :session)')
                ->setParameter('session', (int) $session->getId())
            ;
        } else {
            $qb->andWhere('resourceLink.session IS NULL');
        }

        /** @var CSurvey[] $surveyList */
        $surveyList = $qb->getQuery()->getResult();

        $surveys = [];
        foreach ($surveyList as $survey) {
            $questions = [];

            foreach ($survey->getQuestions() as $question) {
                if (\count($questions) >= self::MAX_SURVEY_QUESTIONS) {
                    break;
                }

                $options = [];
                foreach ($question->getOptions() as $option) {
                    if (\count($options) >= self::MAX_SURVEY_OPTIONS) {
                        break;
                    }

                    $options[] = [
                        'text' => $this->cleanText((string) $option->getOptionText()),
                        'value' => $option->getValue(),
                    ];
                }

                $questions[] = [
                    'id' => $question->getIid(),
                    'question' => $this->cleanText($question->getSurveyQuestion()),
                    'comment' => $this->cleanText((string) $question->getSurveyQuestionComment()),
                    'type' => $question->getType(),
                    'mandatory' => $question->isMandatory(),
                    'position' => $question->getSort(),
                    'options' => $options,
                ];
            }

            $resourceNode = $survey->getResourceNode();
            $resourceLink = $resourceNode instanceof ResourceNode
                ? $this->findReviewableResourceLink(
                    $resourceNode,
                    $course,
                    $session,
                    $includeDraftResources,
                )
                : null;

            $surveys[] = [
                'id' => $survey->getIid(),
                'title' => $survey->getTitle(),
                'introduction' => $this->cleanText((string) $survey->getIntro()),
                'thanks' => $this->cleanText((string) $survey->getSurveythanks()),
                'language' => $survey->getLang(),
                'anonymous' => '1' === $survey->getAnonymous(),
                'questionCount' => \count($questions),
                'visibility' => $this->buildVisibilityPayload($resourceLink),
                'questions' => $questions,
            ];
        }

        return $surveys;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildDocumentPayload(
        CDocument $document,
        int &$totalCharacters,
        ?Course $course = null,
        ?Session $session = null,
        bool $includeDraftResources = false,
    ): array
    {
        $resourceNode = $document->getResourceNode();
        $resourceLink = $resourceNode instanceof ResourceNode && $course instanceof Course
            ? $this->findReviewableResourceLink($resourceNode, $course, $session, $includeDraftResources)
            : null;

        $metadata = [
            'id' => $document->getIid(),
            'title' => $document->getTitle(),
            'comment' => $this->cleanText((string) $document->getComment()),
            'fileType' => $document->getFiletype(),
            'resourceNodeId' => $resourceNode instanceof ResourceNode ? $resourceNode->getId() : null,
            'resourcePath' => $resourceNode instanceof ResourceNode ? $resourceNode->getPathForDisplay() : null,
            'editUrl' => $course instanceof Course ? $this->buildDocumentEditUrl($document, $course, $session) : null,
            'visibility' => $this->buildVisibilityPayload($resourceLink),
            'fileName' => null,
            'mimeType' => null,
            'size' => null,
            'textIncluded' => false,
            'textSource' => null,
            'textLength' => 0,
            'textTruncated' => false,
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

        $extracted = $this->extractDocumentText(
            $document,
            $resourceFile,
            min(self::MAX_CHARS_PER_DOCUMENT, $remainingCharacters),
        );
        $text = $extracted['text'];

        if ('' === $text) {
            $metadata['notice'] = 'The document content could not be read from Chamilo storage.';

            return $metadata;
        }

        $metadata['textIncluded'] = true;
        $metadata['textSource'] = $extracted['source'];
        $metadata['textLength'] = mb_strlen($text);
        $metadata['textTruncated'] = $extracted['truncated'];
        $metadata['text'] = $text;
        $totalCharacters += mb_strlen($text);

        return $metadata;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildExercisePayload(
        CQuiz $quiz,
        ?Course $course = null,
        ?Session $session = null,
        bool $includeDraftResources = false,
    ): array
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

        $resourceNode = $quiz->getResourceNode();
        $resourceLink = $resourceNode instanceof ResourceNode && $course instanceof Course
            ? $this->findReviewableResourceLink($resourceNode, $course, $session, $includeDraftResources)
            : null;

        return [
            'id' => $quiz->getIid(),
            'title' => $quiz->getTitle(),
            'description' => $this->cleanText((string) $quiz->getDescription()),
            'type' => $quiz->getType(),
            'editUrl' => $course instanceof Course ? $this->buildExerciseEditUrl($quiz, $course, $session) : null,
            'visibility' => $this->buildVisibilityPayload($resourceLink),
            'questions' => $questions,
        ];
    }

    /**
     * Read through the Chamilo repository first so Flysystem/Vich-backed
     * resources and editable text content are available outside a file upload
     * request. Fall back to the direct ResourceFile path for legacy files.
     *
     * @return array{text:string,source:string|null,truncated:bool}
     */
    private function extractDocumentText(
        CDocument $document,
        ResourceFile $resourceFile,
        int $maxCharacters,
    ): array {
        if ($maxCharacters <= 0) {
            return [
                'text' => '',
                'source' => null,
                'truncated' => false,
            ];
        }

        $fileName = (string) (
            $resourceFile->getOriginalName()
            ?: $resourceFile->getTitle()
            ?: ''
        );
        $extension = strtolower((string) pathinfo($fileName, PATHINFO_EXTENSION));
        $mimeType = strtolower((string) $resourceFile->getMimeType());

        if ('docx' !== $extension && $this->isPlainTextResource($extension, $mimeType)) {
            try {
                $rawContent = $this->documentRepository->getResourceFileContent($document);
                $cleanContent = $this->cleanText($rawContent);

                if ('' !== $cleanContent) {
                    $truncated = mb_strlen($cleanContent) > $maxCharacters;

                    return [
                        'text' => $this->truncateText($cleanContent, $maxCharacters),
                        'source' => 'chamilo_repository',
                        'truncated' => $truncated,
                    ];
                }
            } catch (Throwable) {
                // Continue with the direct ResourceFile fallback.
            }
        }

        $text = $this->extractResourceFileText($resourceFile, $maxCharacters);

        return [
            'text' => $text,
            'source' => '' !== $text ? 'resource_file_fallback' : null,
            'truncated' => '' !== $text && mb_strlen($text) >= $maxCharacters,
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
        $structuredAssignments = $this->arrayList($structured['assignments'] ?? []);
        $structuredSurveys = $this->arrayList($structured['surveys'] ?? []);

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
        $structured['assignments'] = $this->normalizeSimpleResourceFeedbackList(
            $this->arrayList($payload['assignments'] ?? []),
            $structuredAssignments,
            'Assignment',
        );
        $structured['surveys'] = $this->normalizeSimpleResourceFeedbackList(
            $this->arrayList($payload['surveys'] ?? []),
            $structuredSurveys,
            'Survey',
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
     * @param array<int, mixed> $payloadItems
     * @param array<int, mixed> $structuredItems
     *
     * @return array<int, array<string, mixed>>
     */
    private function normalizeSimpleResourceFeedbackList(
        array $payloadItems,
        array $structuredItems,
        string $fallbackTitle,
    ): array {
        $items = [];

        foreach ($payloadItems as $payloadItem) {
            if (!\is_array($payloadItem)) {
                continue;
            }

            $structuredItem = $this->findStructuredFeedback(
                $structuredItems,
                $payloadItem,
            );

            $items[] = [
                'id' => $payloadItem['id'] ?? null,
                'title' => $payloadItem['title'] ?? $fallbackTitle,
                'feedback' => $this->stringValue($structuredItem['feedback'] ?? ''),
                'recommendations' => $this->stringList(
                    $structuredItem['recommendations'] ?? [],
                ),
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

            $structuredQuestion = $this->findStructuredFeedback(
                $structuredQuestions,
                $payloadQuestion,
            );
            $feedback = $this->stringValue($structuredQuestion['feedback'] ?? '');
            $answersFeedback = $this->stringValue(
                $structuredQuestion['answersFeedback'] ?? '',
            );
            $recommendations = $this->stringList(
                $structuredQuestion['recommendations'] ?? [],
            );

            if ('' === $feedback && '' === $answersFeedback && [] === $recommendations) {
                continue;
            }

            $items[] = [
                'id' => $payloadQuestion['id'] ?? null,
                'question' => $payloadQuestion['question'] ?? 'Question',
                'feedback' => $feedback,
                'answersFeedback' => $answersFeedback,
                'recommendations' => $recommendations,
            ];
        }

        return $items;
    }

    /**
     * @param array<int, mixed>    $structuredItems
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

        return preg_replace('/\s+/', ' ', $value) ?? $value;
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, int>
     */
    private function buildPayloadStats(array $payload): array
    {
        $documents = [];
        $exerciseCount = 0;
        $questionCount = 0;

        foreach ($this->arrayList($payload['lessons'] ?? []) as $lesson) {
            if (!\is_array($lesson)) {
                continue;
            }

            foreach ($this->arrayList($lesson['items'] ?? []) as $item) {
                if (!\is_array($item)) {
                    continue;
                }

                if (\is_array($item['document'] ?? null)) {
                    $documents[] = $item['document'];
                }

                if (\is_array($item['exercise'] ?? null)) {
                    ++$exerciseCount;
                    $questionCount += \count(
                        $this->arrayList($item['exercise']['questions'] ?? []),
                    );
                }
            }
        }

        foreach ($this->arrayList($payload['standaloneDocuments'] ?? []) as $document) {
            if (\is_array($document)) {
                $documents[] = $document;
            }
        }

        foreach ($this->arrayList($payload['standaloneExercises'] ?? []) as $exercise) {
            if (!\is_array($exercise)) {
                continue;
            }

            ++$exerciseCount;
            $questionCount += \count(
                $this->arrayList($exercise['questions'] ?? []),
            );
        }

        $documentsWithText = 0;
        $documentsWithoutText = 0;
        $truncatedDocuments = 0;
        $includedTextCharacters = 0;

        foreach ($documents as $document) {
            if (true === ($document['textIncluded'] ?? false)) {
                ++$documentsWithText;
                $includedTextCharacters += (int) ($document['textLength'] ?? 0);

                if (true === ($document['textTruncated'] ?? false)) {
                    ++$truncatedDocuments;
                }

                continue;
            }

            ++$documentsWithoutText;
        }

        return [
            'documents_total' => \count($documents),
            'documents_with_text' => $documentsWithText,
            'documents_without_text' => $documentsWithoutText,
            'documents_truncated' => $truncatedDocuments,
            'included_text_characters' => $includedTextCharacters,
            'exercises_total' => $exerciseCount,
            'questions_total' => $questionCount,
            'assignments_total' => \count($this->arrayList($payload['assignments'] ?? [])),
            'surveys_total' => \count($this->arrayList($payload['surveys'] ?? [])),
        ];
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<int, array{role:string,content:string}>
     */
    private function buildMessages(
        array $payload,
        bool $compact,
    ): array {
        $payloadJson = json_encode(
            $payload,
            JSON_PRETTY_PRINT
                | JSON_UNESCAPED_UNICODE
                | JSON_UNESCAPED_SLASHES,
        );
        if (!\is_string($payloadJson)) {
            $payloadJson = '{}';
        }

        $detailRules = $compact
            ? [
                'This is a compact retry after an invalid or truncated response.',
                'Keep general feedback under 900 characters.',
                'Return at most 5 strengths, 5 risks and 7 recommendations.',
                'Keep each resource feedback under 280 characters and each resource recommendation list to at most 3 concise items.',
                'Only include question-level feedback for questions with a concrete defect. Omit question objects that need no correction.',
            ]
            : [
                'Keep general feedback under 1400 characters.',
                'Return at most 7 strengths, 7 risks and 10 recommendations.',
                'Keep each resource feedback concise and evidence-based.',
                'Question-level feedback should focus only on concrete wording, answer, scoring or feedback defects.',
            ];

        return [
            [
                'role' => 'system',
                'content' => implode("\n", array_merge([
                    'You are an expert instructional designer and e-learning quality reviewer.',
                    'Analyze the Chamilo course by prioritizing its learning path structure and respecting each resource visibility state.',
                    'Review each lesson as an ordered pedagogical sequence, and explain the purpose and quality of each lesson item when possible.',
                    'When a document includes text, base your feedback on that text rather than its title or generation topic.',
                    'When an exercise includes questions and answers, analyze wording, answer quality, correct-answer alignment, feedback comments and scoring.',
                    'Review assignment descriptions, scoring and submission mode, and review survey structure, wording, option quality and anonymity.',
                    'Draft and pending resources are teacher work in progress. Analyze them and do not describe the course as empty merely because resources are unpublished.',
                    'Return only valid JSON, without markdown fences.',
                    'Do not invent lessons, items, documents, exercises, assignments, surveys or questions.',
                    'Use exact IDs and titles from the payload whenever possible.',
                ], $detailRules, [
                    'Use this JSON structure:',
                    '{',
                    '  "generalFeedback": "string",',
                    '  "strengths": ["string"],',
                    '  "risks": ["string"],',
                    '  "recommendations": ["string"],',
                    '  "lessons": [{"id": 0, "title": "string", "feedback": "string", "sequenceFeedback": "string", "items": [{"id": 0, "title": "string", "type": "string", "purpose": "string", "feedback": "string", "questions": [{"id": 0, "question": "string", "feedback": "string", "answersFeedback": "string", "recommendations": ["string"]}], "recommendations": ["string"]}], "recommendations": ["string"]}],',
                    '  "standaloneDocuments": [{"id": 0, "title": "string", "feedback": "string", "recommendations": ["string"]}],',
                    '  "standaloneExercises": [{"id": 0, "title": "string", "feedback": "string", "questions": [{"id": 0, "question": "string", "feedback": "string", "answersFeedback": "string", "recommendations": ["string"]}], "recommendations": ["string"]}],',
                    '  "assignments": [{"id": 0, "title": "string", "feedback": "string", "recommendations": ["string"]}],',
                    '  "surveys": [{"id": 0, "title": "string", "feedback": "string", "recommendations": ["string"]}]',
                    '}',
                ])),
            ],
            [
                'role' => 'user',
                'content' => $payloadJson,
            ],
        ];
    }

    /**
     * @param array<int, array{role:string,content:string}> $messages
     */
    private function requestStructuredAnalysis(
        string $provider,
        array $messages,
        int $maxTokens,
    ): string {
        return $this->chatCompletionClient->chat($provider, $messages, [
            'temperature' => 0.15,
            'max_tokens' => $maxTokens,
            'max_output_tokens' => $maxTokens,
            'response_mime_type' => 'application/json',
            'throw_on_error' => true,
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decodeStructuredResponse(string $rawResponse): ?array
    {
        $response = trim($rawResponse);
        $response = preg_replace('/^```(?:json)?\\s*/i', '', $response) ?? $response;
        $response = preg_replace('/\\s*```$/', '', $response) ?? $response;

        $start = strpos($response, '{');
        $end = strrpos($response, '}');

        if (false !== $start && false !== $end && $end >= $start) {
            $response = substr($response, $start, $end - $start + 1);
        }

        try {
            $decoded = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            return null;
        }

        return \is_array($decoded) ? $decoded : null;
    }
}
