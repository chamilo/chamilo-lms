<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\LearningPath;

use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\AiDisclosureHelper;
use Chamilo\CoreBundle\Mcp\CreateCourseDocumentTool;
use Chamilo\CoreBundle\Service\Exercise\AiCourseTestGenerator;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Entity\CLpItem;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Entity\CQuizQuestion;
use Chamilo\CourseBundle\Repository\CLpItemRepository;
use Chamilo\CourseBundle\Repository\CLpRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use InvalidArgumentException;
use learnpath;
use Mcp\Server\ClientGateway;
use RuntimeException;
use Throwable;

/**
 * Persists a learning path from pages fully authored by the MCP client
 * (page HTML and, optionally, mini-test questions). Chamilo does not call
 * any AI provider here — it only validates and stores what it is given.
 */
final readonly class McpCourseLearningPathCreator
{
    private const int MAX_PAGE_COUNT = 10;
    private const int MAX_PAGE_CONTENT_LENGTH = 2_000_000;
    private const int MIN_QUESTIONS_PER_QUIZ = 1;
    private const int MAX_QUESTIONS_PER_QUIZ = 20;
    private const int MIN_ANSWERS_PER_QUESTION = 2;
    private const int MAX_ANSWERS_PER_QUESTION = 10;

    // Matches CreateCourseDocumentTool's own requestedWordCount bounds — that
    // parameter no longer drives generation there either, it is only kept as
    // an advisory stat, so a value derived from the actual word count is
    // clamped into range rather than exposing a second, redundant parameter.
    private const int DOCUMENT_TOOL_MIN_REQUESTED_WORDS = 50;
    private const int DOCUMENT_TOOL_MAX_REQUESTED_WORDS = 5_000;

    public function __construct(
        private AiCourseTestGenerator $testGenerator,
        private CLpRepository $lpRepository,
        private CLpItemRepository $lpItemRepository,
        private CreateCourseDocumentTool $documentTool,
        private EntityManagerInterface $entityManager,
        private ManagerRegistry $managerRegistry,
        private AiDisclosureHelper $aiDisclosureHelper,
    ) {}

    /**
     * @param list<array{
     *     title: string,
     *     content: string,
     *     quiz?: array{
     *         title?: string,
     *         questions: list<array{
     *             title: string,
     *             answers: list<string>,
     *             correct_index: int,
     *             feedback?: string
     *         }>
     *     }
     * }> $pages
     *
     * @return array<string, mixed>
     */
    public function create(
        Course $course,
        User $user,
        string $title,
        array $pages,
        ?string $language,
        bool $publish,
        ?ClientGateway $client = null,
    ): array {
        $title = trim(strip_tags($title));
        if ('' === $title) {
            throw new InvalidArgumentException('The learning path title is required.');
        }
        if (mb_strlen($title) > 255) {
            throw new InvalidArgumentException('The learning path title cannot be longer than 255 characters.');
        }

        $pageCount = \count($pages);
        if ($pageCount < 1 || $pageCount > self::MAX_PAGE_COUNT) {
            throw new InvalidArgumentException(\sprintf('The learning path must have between 1 and %d pages.', self::MAX_PAGE_COUNT));
        }

        $normalizedPages = [];
        foreach (array_values($pages) as $index => $page) {
            $normalizedPage = $this->normalizePage($page, $index + 1);
            $normalizedPages[] = $normalizedPage;
        }

        $courseNode = $course->getResourceNode();
        if (!$courseNode instanceof ResourceNode) {
            throw new RuntimeException('The course resource node is missing.');
        }

        $visibility = $publish
            ? ResourceLink::VISIBILITY_PUBLISHED
            : ResourceLink::VISIBILITY_DRAFT;

        $learningPath = (new CLp())
            ->setLpType(CLp::LP_TYPE)
            ->setTitle($title)
            ->setDescription('')
            ->setParent($course)
            ->addCourseLink($course, null, null, $visibility)
        ;

        $learningPathId = 0;
        $createdDocumentIds = [];
        $createdQuizIds = [];
        $createdQuestionIds = [];
        $createdPages = [];
        $stage = 'creating the learning path';

        try {
            $this->lpRepository->createLp($learningPath);
            $this->entityManager->flush();

            $learningPathId = (int) $learningPath->getIid();
            $rootItem = $this->lpItemRepository->getRootItem($learningPathId);
            if (null === $rootItem) {
                throw new RuntimeException('The learning path root item could not be created.');
            }

            require_once api_get_path(SYS_CODE_PATH).'lp/learnpath.class.php';

            $courseInfo = api_get_course_info($course->getCode());
            if (!\is_array($courseInfo) || [] === $courseInfo) {
                throw new RuntimeException('The legacy course context could not be resolved.');
            }

            $legacyLearningPath = new learnpath(
                $learningPath,
                $courseInfo,
                (int) $user->getId(),
            );

            $previousItemId = 0;

            foreach ($normalizedPages as $index => $page) {
                $pageNumber = $index + 1;
                $pageTitle = $page['title'];
                $pageContent = $page['content'];

                $client?->progress(
                    ($pageNumber - 1) / $pageCount,
                    1.0,
                    \sprintf('Saving page %d of %d...', $pageNumber, $pageCount),
                );

                $requestedWordCount = max(
                    self::DOCUMENT_TOOL_MIN_REQUESTED_WORDS,
                    min(self::DOCUMENT_TOOL_MAX_REQUESTED_WORDS, $this->countWords($pageContent)),
                );

                $stage = 'creating document for page '.$pageNumber;
                $documentResult = $this->documentTool->createCourseDocument(
                    (int) $course->getId(),
                    $pageTitle,
                    $pageTitle,
                    $requestedWordCount,
                    $pageContent,
                    $language,
                    $publish,
                );
                $document = $documentResult['document'];
                $documentId = (int) $document['document_id'];
                if ($documentId <= 0) {
                    throw new RuntimeException('Chamilo returned an invalid document ID.');
                }
                $documentTitle = trim((string) ($document['title'] ?? $pageTitle));
                if ('' === $documentTitle) {
                    $documentTitle = $pageTitle;
                }
                $createdDocumentIds[] = $documentId;

                $stage = 'linking document for page '.$pageNumber;
                $documentItemId = (int) $legacyLearningPath->add_item(
                    $rootItem,
                    $previousItemId,
                    TOOL_DOCUMENT,
                    $documentId,
                    $documentTitle,
                );
                if ($documentItemId <= 0) {
                    throw new RuntimeException('A learning path document item could not be added.');
                }

                $previousItemId = $documentItemId;
                $this->markItem($documentItemId);

                $quizId = null;
                $quizItemId = null;

                if (null !== $page['quiz']) {
                    $stage = 'persisting mini-test for page '.$pageNumber;
                    $prepared = [
                        'provider_used' => 'mcp_client',
                        'generation_mode' => 'mcp_client_supplied',
                        'source_type' => 'topic',
                        'source_title' => $pageTitle,
                        'source_text' => $pageTitle,
                        'question_count' => \count($page['quiz']['questions']),
                        'questions' => $page['quiz']['questions'],
                    ];

                    $test = $this->testGenerator->persistPreparedTest(
                        $course,
                        $user,
                        $page['quiz']['title'],
                        $prepared,
                        null,
                        false,
                    );
                    $quizId = (int) $test['quiz_id'];
                    if ($quizId <= 0) {
                        throw new RuntimeException('Chamilo returned an invalid mini-test ID.');
                    }
                    $createdQuizIds[] = $quizId;

                    foreach ($test['questions'] as $createdQuestion) {
                        $questionId = (int) ($createdQuestion['question_id'] ?? 0);
                        if ($questionId <= 0) {
                            throw new RuntimeException('Chamilo returned an invalid mini-test question ID.');
                        }

                        $createdQuestionIds[] = $questionId;
                    }

                    $stage = 'linking mini-test for page '.$pageNumber;
                    $quizItemId = $this->createQuizLearningPathItem(
                        $learningPath,
                        $rootItem,
                        $quizId,
                        $page['quiz']['title'],
                        $test['questions'],
                    );

                    $previousItemId = $quizItemId;
                    $this->markItem($quizItemId);
                }

                $createdPages[] = [
                    'page_number' => $pageNumber,
                    'title' => $documentTitle,
                    'document_id' => $documentId,
                    'document_item_id' => $documentItemId,
                    'quiz_id' => $quizId,
                    'quiz_item_id' => $quizItemId,
                ];
            }
        } catch (Throwable $exception) {
            $rollbackSucceeded = $this->rollbackCreatedResources(
                $learningPathId,
                $createdDocumentIds,
                $createdQuizIds,
                $createdQuestionIds,
            );

            $message = 'Learning path creation failed while '.$stage.': '.$exception->getMessage();
            $message .= $rollbackSucceeded
                ? ' All resources created by this operation were rolled back.'
                : ' Automatic rollback could not be completed; review the course before retrying.';

            throw new RuntimeException($message, 0, $exception);
        }

        $orderedItemIds = [];
        foreach ($createdPages as $createdPage) {
            $orderedItemIds[] = (int) $createdPage['document_item_id'];
            if (null !== $createdPage['quiz_item_id']) {
                $orderedItemIds[] = (int) $createdPage['quiz_item_id'];
            }
        }

        $verifiedItemCount = $this->finalizeAndVerifyStructure(
            $learningPath,
            $course,
            $orderedItemIds,
        );
        $client?->progress(1.0, 1.0, 'Learning path created and verified.');

        $quizPageCount = \count(array_filter(
            $createdPages,
            static fn (array $page): bool => null !== $page['quiz_id'],
        ));

        $this->aiDisclosureHelper->markAiAssistedExtraField('lp', $learningPathId, true);
        $this->aiDisclosureHelper->logAudit(
            targetKey: 'lp:'.$learningPathId,
            userId: (int) $user->getId(),
            meta: [
                'feature' => 'mcp_learning_path',
                'mode' => 'mcp_client_supplied',
                'title' => $title,
                'page_count' => $pageCount,
                'quiz_page_count' => $quizPageCount,
                'published' => $publish,
            ],
            courseId: (int) $course->getId(),
            sessionId: 0,
        );

        return [
            'learning_path_id' => $learningPathId,
            'resource_node_id' => (int) $learningPath->getResourceNode()?->getId(),
            'title' => $learningPath->getTitle(),
            'page_count' => \count($createdPages),
            'quiz_page_count' => $quizPageCount,
            'published' => $publish,
            'verified_in_course' => true,
            'verified_item_count' => $verifiedItemCount,
            'ai_assisted' => true,
            'content_source' => 'mcp_client',
            'course_features_enabled' => [],
            'items' => $createdPages,
            'content_url' => '/resources/lp/'
                .(int) $courseNode->getId()
                .'/'.$learningPathId
                .'/builder?cid='.(int) $course->getId(),
        ];
    }

    /**
     * @return array{
     *     title: string,
     *     content: string,
     *     quiz: array{title: string, questions: list<array{title: string, answers: list<string>, correct_index: int, feedback: string}>}|null
     * }
     */
    private function normalizePage(mixed $page, int $pageNumber): array
    {
        if (!\is_array($page)) {
            throw new InvalidArgumentException(\sprintf('Page %d must be an object with a title and content.', $pageNumber));
        }

        $title = trim(strip_tags((string) ($page['title'] ?? '')));
        if ('' === $title) {
            throw new InvalidArgumentException(\sprintf('Page %d is missing a title.', $pageNumber));
        }
        if (mb_strlen($title) > 255) {
            throw new InvalidArgumentException(\sprintf('Page %d title cannot be longer than 255 characters.', $pageNumber));
        }

        $content = trim((string) ($page['content'] ?? ''));
        if ('' === $content) {
            throw new InvalidArgumentException(\sprintf('Page %d is missing content.', $pageNumber));
        }
        if (mb_strlen($content) > self::MAX_PAGE_CONTENT_LENGTH) {
            throw new InvalidArgumentException(\sprintf('Page %d content is too large.', $pageNumber));
        }

        $quiz = isset($page['quiz']) ? $this->normalizeQuiz($page['quiz'], $title, $pageNumber) : null;

        return [
            'title' => $title,
            'content' => $content,
            'quiz' => $quiz,
        ];
    }

    /**
     * @return array{title: string, questions: list<array{title: string, answers: list<string>, correct_index: int, feedback: string}>}
     */
    private function normalizeQuiz(mixed $quiz, string $pageTitle, int $pageNumber): array
    {
        if (!\is_array($quiz) || !isset($quiz['questions']) || !\is_array($quiz['questions'])) {
            throw new InvalidArgumentException(\sprintf('Page %d quiz must include a list of questions.', $pageNumber));
        }

        $quizTitle = trim(strip_tags((string) ($quiz['title'] ?? '')));
        if ('' === $quizTitle) {
            $quizTitle = 'Mini-test '.$pageNumber.': '.$pageTitle;
        }
        if (mb_strlen($quizTitle) > 255) {
            throw new InvalidArgumentException(\sprintf('Page %d quiz title cannot be longer than 255 characters.', $pageNumber));
        }

        $rawQuestions = array_values($quiz['questions']);
        $questionCount = \count($rawQuestions);
        if ($questionCount < self::MIN_QUESTIONS_PER_QUIZ || $questionCount > self::MAX_QUESTIONS_PER_QUIZ) {
            throw new InvalidArgumentException(\sprintf('Page %d quiz must have between %d and %d questions.', $pageNumber, self::MIN_QUESTIONS_PER_QUIZ, self::MAX_QUESTIONS_PER_QUIZ));
        }

        $questions = [];
        foreach ($rawQuestions as $questionIndex => $rawQuestion) {
            $questions[] = $this->normalizeQuestion($rawQuestion, $pageNumber, $questionIndex + 1);
        }

        return [
            'title' => $quizTitle,
            'questions' => $questions,
        ];
    }

    /**
     * @return array{title: string, answers: list<string>, correct_index: int, feedback: string}
     */
    private function normalizeQuestion(mixed $rawQuestion, int $pageNumber, int $questionNumber): array
    {
        if (!\is_array($rawQuestion)) {
            throw new InvalidArgumentException(\sprintf('Page %d, question %d must be an object.', $pageNumber, $questionNumber));
        }

        $title = trim(strip_tags((string) ($rawQuestion['title'] ?? '')));
        if ('' === $title) {
            throw new InvalidArgumentException(\sprintf('Page %d, question %d is missing a title.', $pageNumber, $questionNumber));
        }

        $rawAnswers = $rawQuestion['answers'] ?? null;
        if (!\is_array($rawAnswers)) {
            throw new InvalidArgumentException(\sprintf('Page %d, question %d is missing its answers.', $pageNumber, $questionNumber));
        }

        $answers = [];
        foreach (array_values($rawAnswers) as $answer) {
            $answerText = trim(strip_tags((string) $answer));
            if ('' === $answerText) {
                throw new InvalidArgumentException(\sprintf('Page %d, question %d has an empty answer.', $pageNumber, $questionNumber));
            }

            $answers[] = $answerText;
        }

        $answerCount = \count($answers);
        if ($answerCount < self::MIN_ANSWERS_PER_QUESTION || $answerCount > self::MAX_ANSWERS_PER_QUESTION) {
            throw new InvalidArgumentException(\sprintf('Page %d, question %d must have between %d and %d answers.', $pageNumber, $questionNumber, self::MIN_ANSWERS_PER_QUESTION, self::MAX_ANSWERS_PER_QUESTION));
        }

        $correctIndex = $rawQuestion['correct_index'] ?? null;
        if (!\is_int($correctIndex) || $correctIndex < 0 || $correctIndex >= $answerCount) {
            throw new InvalidArgumentException(\sprintf('Page %d, question %d has an invalid correct_index.', $pageNumber, $questionNumber));
        }

        $feedback = trim(strip_tags((string) ($rawQuestion['feedback'] ?? '')));

        return [
            'title' => $title,
            'answers' => $answers,
            'correct_index' => $correctIndex,
            'feedback' => $feedback,
        ];
    }

    /**
     * @param list<array{question_id: int, title: string, score: float}> $questions
     */
    private function createQuizLearningPathItem(
        CLp $learningPath,
        CLpItem $rootItem,
        int $quizId,
        string $title,
        array $questions,
    ): int {
        $maxScore = 0.0;
        foreach ($questions as $question) {
            $maxScore += $question['score'];
        }

        $item = (new CLpItem())
            ->setTitle($title)
            ->setDescription('')
            ->setPath((string) $quizId)
            ->setLp($learningPath)
            ->setItemType(TOOL_QUIZ)
            ->setMaxScore($maxScore)
            ->setMaxTimeAllowed('0')
            ->setPrerequisite('0')
            ->setParent($rootItem)
        ;

        $this->entityManager->persist($item);
        $this->entityManager->flush();

        $itemId = (int) $item->getIid();
        if ($itemId <= 0) {
            throw new RuntimeException('A learning path mini-test item could not be added.');
        }

        return $itemId;
    }

    private function countWords(string $html): int
    {
        $text = trim((string) preg_replace('/\s+/u', ' ', strip_tags($html)));
        if ('' === $text) {
            return 0;
        }

        return preg_match_all('/[\p{L}\p{N}]+(?:[’\'-][\p{L}\p{N}]+)*/u', $text);
    }

    /**
     * @param list<int> $documentIds
     * @param list<int> $quizIds
     * @param list<int> $questionIds
     */
    private function rollbackCreatedResources(
        int $learningPathId,
        array $documentIds,
        array $quizIds,
        array $questionIds,
    ): bool {
        try {
            /*
             * A failed flush can close or contaminate the request EntityManager.
             * Use a reset manager for compensating cleanup, then reload every
             * resource by ID before scheduling deletion.
             */
            $this->managerRegistry->resetManager();
            $rollbackEntityManager = $this->managerRegistry->getManager();

            if (!$rollbackEntityManager instanceof EntityManagerInterface) {
                return false;
            }

            if ($learningPathId > 0) {
                $learningPath = $rollbackEntityManager->find(CLp::class, $learningPathId);
                if ($learningPath instanceof CLp) {
                    $this->scheduleResourceRemoval($rollbackEntityManager, $learningPath);
                }
            }

            foreach (array_reverse($quizIds) as $quizId) {
                $quiz = $rollbackEntityManager->find(CQuiz::class, $quizId);
                if ($quiz instanceof CQuiz) {
                    $this->scheduleResourceRemoval($rollbackEntityManager, $quiz);
                }
            }

            foreach (array_reverse($questionIds) as $questionId) {
                $question = $rollbackEntityManager->find(CQuizQuestion::class, $questionId);
                if ($question instanceof CQuizQuestion) {
                    $this->scheduleResourceRemoval($rollbackEntityManager, $question);
                }
            }

            foreach (array_reverse($documentIds) as $documentId) {
                $document = $rollbackEntityManager->find(CDocument::class, $documentId);
                if ($document instanceof CDocument) {
                    $this->scheduleResourceRemoval($rollbackEntityManager, $document);
                }
            }

            $rollbackEntityManager->flush();

            return true;
        } catch (Throwable $rollbackException) {
            error_log(
                '[MCP][learning_path] Rollback failed: '
                .$rollbackException->getMessage()
            );

            return false;
        }
    }

    private function scheduleResourceRemoval(
        EntityManagerInterface $entityManager,
        AbstractResource $resource,
    ): void {
        $resourceNode = $resource->getResourceNode();

        $entityManager->remove($resource);
        if ($resourceNode instanceof ResourceNode) {
            $resourceLinks = $resourceNode->getResourceLinks();
            foreach ($resourceLinks as $resourceLink) {
                $entityManager->remove($resourceLink);
            }

            /*
             * ResourceDoctrineListener::postRemove() reads this same in-memory
             * collection to build a "deletion" tracking event, then postFlush()
             * persists it through its own constructor-injected EntityManager —
             * a reference captured before this rollback's resetManager() call,
             * so it is stale and can still hold unflushed state from the
             * original failed attempt. Clearing the collection here means
             * getResourceLinks()->first() finds nothing, so no tracking event
             * is queued and that stale EntityManager's nested flush() never
             * runs — sidestepping the stale reference entirely rather than
             * fixing tracking data for resources that never really existed.
             */
            $resourceLinks->clear();

            $entityManager->remove($resourceNode);
        }
    }

    /**
     * @param list<int> $orderedItemIds
     */
    private function finalizeAndVerifyStructure(
        CLp $learningPath,
        Course $course,
        array $orderedItemIds,
    ): int {
        if ([] === $orderedItemIds) {
            throw new RuntimeException('The learning path has no persisted content items.');
        }

        $rootItem = $this->lpItemRepository->getRootItem((int) $learningPath->getIid());
        if (!$rootItem instanceof CLpItem) {
            throw new RuntimeException('The learning path root item could not be verified.');
        }

        $rootItem
            ->setDisplayOrder(1)
            ->setPreviousItemId(null)
            ->setNextItemId(null)
        ;
        $this->entityManager->persist($rootItem);

        foreach ($orderedItemIds as $position => $itemId) {
            $item = $this->lpItemRepository->find($itemId);
            if (!$item instanceof CLpItem || (int) $item->getLp()->getIid() !== (int) $learningPath->getIid()) {
                throw new RuntimeException('A persisted learning path item could not be verified.');
            }

            $item
                ->setParent($rootItem)
                ->setDisplayOrder($position + 2)
                ->setPreviousItemId(null)
                ->setNextItemId(null)
            ;
            $this->entityManager->persist($item);
        }
        $this->entityManager->flush();

        $this->lpItemRepository->recoverNode($rootItem, 'displayOrder');
        $this->entityManager->persist($rootItem);
        $this->entityManager->flush();

        $verifiedInCourse = (int) $this->entityManager->createQueryBuilder()
            ->select('COUNT(verifiedLearningPath.iid)')
            ->from(CLp::class, 'verifiedLearningPath')
            ->innerJoin('verifiedLearningPath.resourceNode', 'verifiedNode')
            ->innerJoin('verifiedNode.resourceLinks', 'verifiedLink')
            ->andWhere('verifiedLearningPath.iid = :learningPathId')
            ->andWhere('IDENTITY(verifiedLink.course) = :courseId')
            ->andWhere('verifiedLink.session IS NULL')
            ->andWhere('verifiedLink.group IS NULL')
            ->andWhere('verifiedLink.userGroup IS NULL')
            ->andWhere('verifiedLink.user IS NULL')
            ->setParameter('learningPathId', (int) $learningPath->getIid(), Types::INTEGER)
            ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
            ->getQuery()
            ->getSingleScalarResult() > 0
        ;

        $persistedItemCount = (int) $this->entityManager->createQueryBuilder()
            ->select('COUNT(verifiedItem.iid)')
            ->from(CLpItem::class, 'verifiedItem')
            ->andWhere('IDENTITY(verifiedItem.lp) = :learningPathId')
            ->andWhere('verifiedItem.itemType != :rootType')
            ->setParameter('learningPathId', (int) $learningPath->getIid(), Types::INTEGER)
            ->setParameter('rootType', 'root', Types::STRING)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        if (!$verifiedInCourse || $persistedItemCount !== \count($orderedItemIds)) {
            throw new RuntimeException('The learning path was created but its persisted course structure could not be verified.');
        }

        return $persistedItemCount;
    }

    private function markItem(int $itemId): void
    {
        $this->aiDisclosureHelper->markAiAssistedExtraField('lp_item', $itemId, true);
    }
}
