<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\LearningPath;

use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Language;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\AiDisclosureHelper;
use Chamilo\CoreBundle\Mcp\CreateCourseDocumentTool;
use Chamilo\CoreBundle\Service\Exercise\AiCourseTestGenerator;
use Chamilo\CoreBundle\Service\Mcp\McpCourseAiFeatureManager;
use Chamilo\CoreBundle\Service\Mcp\McpTextAiService;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Entity\CQuizQuestion;
use Chamilo\CourseBundle\Repository\CLpItemRepository;
use Chamilo\CourseBundle\Repository\CLpRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use InvalidArgumentException;
use learnpath;
use RuntimeException;
use Throwable;

final readonly class McpCourseLearningPathCreator
{
    private const MAX_PAGE_COUNT = 10;
    private const MAX_TOTAL_WORDS = 6000;
    private const MAX_WORDS_PER_PAGE = 1500;
    private const MIN_PAGE_COUNT = 1;
    private const MIN_WORDS_PER_PAGE = 50;

    public function __construct(
        private McpTextAiService $aiService,
        private McpCourseAiFeatureManager $courseAiFeatureManager,
        private AiCourseTestGenerator $testGenerator,
        private CLpRepository $lpRepository,
        private CLpItemRepository $lpItemRepository,
        private CreateCourseDocumentTool $documentTool,
        private EntityManagerInterface $entityManager,
        private ManagerRegistry $managerRegistry,
        private AiDisclosureHelper $aiDisclosureHelper,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function create(
        Course $course,
        User $user,
        string $topic,
        int $pageCount,
        int $wordsPerPage,
        int $questionsPerQuiz,
        ?string $provider,
        bool $publish,
    ): array {
        $topic = trim(strip_tags($topic));
        if ('' === $topic) {
            throw new InvalidArgumentException('The learning path topic is required.');
        }
        if (mb_strlen($topic) > 255) {
            throw new InvalidArgumentException('The learning path topic cannot be longer than 255 characters.');
        }
        if ($pageCount < self::MIN_PAGE_COUNT || $pageCount > self::MAX_PAGE_COUNT) {
            throw new InvalidArgumentException('The page count must be between 1 and 10.');
        }
        if ($wordsPerPage < self::MIN_WORDS_PER_PAGE || $wordsPerPage > self::MAX_WORDS_PER_PAGE) {
            throw new InvalidArgumentException('The word count per page must be between 50 and 1500.');
        }
        if ($questionsPerQuiz < 1 || $questionsPerQuiz > 5) {
            throw new InvalidArgumentException('The number of questions per mini-test must be between 1 and 5.');
        }
        if ($pageCount * $wordsPerPage > self::MAX_TOTAL_WORDS) {
            throw new InvalidArgumentException('The complete learning path cannot request more than 6000 words.');
        }

        $enabledFeatures = $this->courseAiFeatureManager->ensureAllEnabled(
            $course,
            $user,
            [
                'learning_path_generator',
                'exercise_generator',
            ],
            'create_course_learning_path',
        );

        /*
         * Complete every external AI operation before the first course
         * resource is persisted. If a provider, quota or format error occurs,
         * Chamilo returns the error without leaving a partial learning path.
         */
        $generated = $this->generatePages(
            $course,
            $user,
            $topic,
            $pageCount,
            $wordsPerPage,
            $provider,
        );
        $providerUsed = (string) $generated['_provider'];
        unset($generated['_provider']);

        /*
         * Resource files require an available ISO language. Older or imported
         * courses can contain a descriptive language value that is valid for
         * prompts but not for ResourceNode language assignment.
         */
        $resourceLanguage = $this->resolveAvailableResourceLanguage($course);

        $preparedPages = [];
        foreach ($generated['pages'] as $index => $page) {
            $pageNumber = $index + 1;
            $pageTitle = (string) $page['title'];
            $pageContent = (string) $page['content'];
            $quizTitle = 'Mini-test '.$pageNumber.': '.$pageTitle;

            $preparedPages[] = [
                'page_number' => $pageNumber,
                'title' => $pageTitle,
                'content' => $pageContent,
                'quiz_title' => $quizTitle,
                'prepared_quiz' => $this->testGenerator->prepareTest(
                    $course,
                    $user,
                    $quizTitle,
                    $questionsPerQuiz,
                    'topic',
                    $pageTitle,
                    $pageContent,
                    $course->getCourseLanguage(),
                    $providerUsed,
                ),
            ];
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
            ->setTitle($topic)
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
            require_once api_get_path(SYS_CODE_PATH).'exercise/exercise.class.php';

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

            foreach ($preparedPages as $preparedPage) {
                $pageNumber = (int) $preparedPage['page_number'];
                $pageTitle = (string) $preparedPage['title'];
                $pageContent = (string) $preparedPage['content'];
                $quizTitle = (string) $preparedPage['quiz_title'];

                $stage = 'creating document for page '.$pageNumber;
                $documentResult = $this->documentTool->createCourseDocument(
                    (int) $course->getId(),
                    $pageTitle,
                    $pageTitle,
                    $wordsPerPage,
                    $pageContent,
                    $resourceLanguage,
                    $publish,
                );
                $document = $documentResult['document'];
                $documentId = (int) $document['document_id'];
                if ($documentId <= 0) {
                    throw new RuntimeException('Chamilo returned an invalid document ID.');
                }
                $createdDocumentIds[] = $documentId;

                $stage = 'linking document for page '.$pageNumber;
                $documentItemId = (int) $legacyLearningPath->add_item(
                    $rootItem,
                    $previousItemId,
                    TOOL_DOCUMENT,
                    $documentId,
                    $pageTitle,
                );
                if ($documentItemId <= 0) {
                    throw new RuntimeException('A learning path document item could not be added.');
                }

                $previousItemId = $documentItemId;
                $this->markItem($documentItemId);

                $stage = 'persisting mini-test for page '.$pageNumber;
                $test = $this->testGenerator->persistPreparedTest(
                    $course,
                    $user,
                    $quizTitle,
                    $preparedPage['prepared_quiz'],
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
                $quizItemId = (int) $legacyLearningPath->add_item(
                    $rootItem,
                    $previousItemId,
                    TOOL_QUIZ,
                    $quizId,
                    $quizTitle,
                );
                if ($quizItemId <= 0) {
                    throw new RuntimeException('A learning path mini-test item could not be added.');
                }

                $previousItemId = $quizItemId;
                $this->markItem($quizItemId);

                $createdPages[] = [
                    'page_number' => $pageNumber,
                    'title' => $pageTitle,
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

        $this->aiDisclosureHelper->markAiAssistedExtraField('lp', $learningPathId, true);
        $this->aiDisclosureHelper->logAudit(
            targetKey: 'lp:'.$learningPathId,
            userId: (int) $user->getId(),
            meta: [
                'feature' => 'mcp_learning_path',
                'mode' => 'generated',
                'provider' => $providerUsed,
                'topic' => $topic,
                'page_count' => $pageCount,
                'words_per_page' => $wordsPerPage,
                'questions_per_quiz' => $questionsPerQuiz,
                'published' => $publish,
                'generation_mode' => $generated['_generation_mode'] ?? 'per_page_html',
                'repaired_page_count' => $generated['_repaired_page_count'] ?? 0,
            ],
            courseId: (int) $course->getId(),
            sessionId: 0,
        );

        return [
            'learning_path_id' => $learningPathId,
            'resource_node_id' => (int) $learningPath->getResourceNode()?->getId(),
            'title' => $learningPath->getTitle(),
            'page_count' => \count($createdPages),
            'questions_per_quiz' => $questionsPerQuiz,
            'published' => $publish,
            'provider_used' => $providerUsed,
            'ai_assisted' => true,
            'generation_mode' => $generated['_generation_mode'] ?? 'per_page_html',
            'repaired_page_count' => $generated['_repaired_page_count'] ?? 0,
            'course_features_enabled' => $enabledFeatures,
            'items' => $createdPages,
            'content_url' => '/resources/lp/'
                .(int) $courseNode->getId()
                .'/'.$learningPathId
                .'/builder?cid='.(int) $course->getId(),
        ];
    }

    /**
     * Generate exactly one page per controlled loop iteration. The provider
     * never decides the array length, so the requested page count is
     * deterministic even when structured JSON output is unreliable.
     *
     * @return array{
     *     pages: list<array{title: string, content: string}>,
     *     _provider: string,
     *     _generation_mode: 'per_page_html',
     *     _repaired_page_count: int
     * }
     */
    private function generatePages(
        Course $course,
        User $user,
        string $topic,
        int $pageCount,
        int $wordsPerPage,
        ?string $provider,
    ): array {
        $pages = [];
        $providerUsed = '';
        $repairedPageCount = 0;
        $previousTitles = [];

        for ($pageNumber = 1; $pageNumber <= $pageCount; ++$pageNumber) {
            $title = $this->buildPageTitle(
                $topic,
                $pageNumber,
                $pageCount,
            );
            $focus = $this->buildPageFocus(
                $pageNumber,
                $pageCount,
            );

            $systemPrompt = <<<'PROMPT'
Return only a valid HTML fragment for one educational page. Start with one <h1> using the exact supplied page title, followed by clear paragraphs and optional lists. Do not return JSON, Markdown fences, explanations, quizzes or metadata. The page must be self-contained, factual, non-repetitive and written in the requested language.
PROMPT;
            $userPrompt = 'Course: '.$course->getTitle()."\n"
                .'Language: '.$course->getCourseLanguage()."\n"
                .'Complete learning path topic: '.$topic."\n"
                .'Current page: '.$pageNumber.' of '.$pageCount."\n"
                .'Exact page title: '.$title."\n"
                .'Page focus: '.$focus."\n"
                .'Approximate words: '.$wordsPerPage."\n"
                .'Previous page titles: '
                .([] === $previousTitles ? 'none' : implode(' | ', $previousTitles));

            $tokenBudget = min(
                8_000,
                max(900, 400 + ($wordsPerPage * 4)),
            );

            $result = $this->aiService->requestText(
                $user,
                $provider,
                $systemPrompt,
                $userPrompt,
                $tokenBudget,
            );
            $providerUsed = (string) $result['provider'];
            if ((bool) $result['repaired']) {
                ++$repairedPageCount;
            }

            $pageContent = $this->normalizeGeneratedPageContent(
                (string) $result['content'],
                $title,
            );

            /*
             * A short response is still usable, but request one controlled
             * expansion before accepting it. This does not affect page count.
             */
            if ($this->countWords($pageContent) < max(40, (int) floor($wordsPerPage * 0.55))) {
                $expanded = $this->aiService->requestText(
                    $user,
                    $providerUsed,
                    $systemPrompt,
                    $userPrompt
                        ."\n\nExpand the page substantially. Preserve the exact title and "
                        .'return approximately '.$wordsPerPage.' words.',
                    $tokenBudget,
                );
                $pageContent = $this->normalizeGeneratedPageContent(
                    (string) $expanded['content'],
                    $title,
                );

                if ((bool) $expanded['repaired']) {
                    ++$repairedPageCount;
                }
            }

            $pages[] = [
                'title' => $title,
                'content' => $pageContent,
            ];
            $previousTitles[] = $title;
        }

        return [
            'pages' => $pages,
            '_provider' => $providerUsed,
            '_generation_mode' => 'per_page_html',
            '_repaired_page_count' => $repairedPageCount,
        ];
    }

    private function buildPageTitle(
        string $topic,
        int $pageNumber,
        int $pageCount,
    ): string {
        if (1 === $pageNumber) {
            return mb_substr('Introduction to '.$topic, 0, 255);
        }

        if ($pageNumber === $pageCount) {
            return mb_substr('Synthesis and application: '.$topic, 0, 255);
        }

        return mb_substr(
            'Key concepts '.$pageNumber.': '.$topic,
            0,
            255,
        );
    }

    private function buildPageFocus(
        int $pageNumber,
        int $pageCount,
    ): string {
        if (1 === $pageNumber) {
            return 'Introduce the topic, its purpose, context and essential vocabulary.';
        }

        if ($pageNumber === $pageCount) {
            return 'Integrate the previous ideas, explain applications and provide a concise conclusion.';
        }

        return 'Develop distinct mechanisms, components, relationships and examples without repeating previous pages.';
    }

    private function normalizeGeneratedPageContent(
        string $content,
        string $title,
    ): string {
        $content = trim((string) preg_replace(
            '#<(script|style)\b[^>]*>.*?</\1>#is',
            '',
            $content,
        ));
        $content = (string) preg_replace('/^```(?:html)?\s*/iu', '', $content);
        $content = (string) preg_replace('/\s*```$/u', '', $content);
        $content = trim($content);

        if ('' === trim(strip_tags($content))) {
            throw new RuntimeException('The AI model returned an empty learning path page.');
        }

        if (!preg_match('/<h1\b/i', $content)) {
            $content = '<h1>'.htmlspecialchars(
                $title,
                ENT_QUOTES | ENT_SUBSTITUTE,
                'UTF-8',
            ).'</h1>'.$content;
        }

        if (!preg_match('/<[^>]+>/', $content)) {
            $paragraphs = preg_split('/\R{2,}/u', $content) ?: [$content];
            $content = '<h1>'.htmlspecialchars(
                $title,
                ENT_QUOTES | ENT_SUBSTITUTE,
                'UTF-8',
            ).'</h1>';

            foreach ($paragraphs as $paragraph) {
                $paragraph = trim($paragraph);
                if ('' === $paragraph) {
                    continue;
                }

                $content .= '<p>'.htmlspecialchars(
                    $paragraph,
                    ENT_QUOTES | ENT_SUBSTITUTE,
                    'UTF-8',
                ).'</p>';
            }
        }

        return mb_substr($content, 0, 2_000_000);
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
            $entityManager->remove($resourceNode);
        }
    }

    private function resolveAvailableResourceLanguage(Course $course): ?string
    {
        $courseLanguage = trim($course->getCourseLanguage());
        if ('' === $courseLanguage) {
            return null;
        }

        $language = $this->entityManager->getRepository(Language::class)->findOneBy([
            'isocode' => $courseLanguage,
            'available' => true,
        ]);

        return $language instanceof Language
            ? $language->getIsocode()
            : null;
    }

    private function markItem(int $itemId): void
    {
        $this->aiDisclosureHelper->markAiAssistedExtraField('lp_item', $itemId, true);
    }
}
