<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Exercise;

use Chamilo\CoreBundle\AiProvider\AiChatCompletionClientInterface;
use Chamilo\CoreBundle\AiProvider\AiProviderFactory;
use Chamilo\CoreBundle\Entity\AiRequests;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\AiDisclosureHelper;
use Chamilo\CoreBundle\Helpers\AiFeatureAccessHelper;
use Chamilo\CoreBundle\Service\Ai\AiRequestQuotaGuard;
use Chamilo\CoreBundle\Service\Mcp\McpTextAiService;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Entity\CQuizAnswer;
use Chamilo\CourseBundle\Entity\CQuizQuestion;
use Chamilo\CourseBundle\Entity\CQuizRelQuestion;
use Chamilo\CourseBundle\Repository\CDocumentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

use const ENT_HTML5;
use const ENT_QUOTES;

final readonly class AiCourseTestGenerator
{
    private const MAX_GENERATED_LENGTH = 100_000;
    private const MAX_QUESTION_COUNT = 20;
    private const MAX_SOURCE_LENGTH = 20_000;
    private const MIN_QUESTION_COUNT = 1;
    private const QUESTION_TYPE_UNIQUE_ANSWER = 1;
    private const TOTAL_SCORE = 20.0;

    public function __construct(
        private AiFeatureAccessHelper $aiFeatureAccessHelper,
        private AiProviderFactory $aiProviderFactory,
        private AiChatCompletionClientInterface $aiChatCompletionClient,
        private AiRequestQuotaGuard $quotaGuard,
        private McpTextAiService $mcpTextAiService,
        private AiDisclosureHelper $aiDisclosureHelper,
        private CDocumentRepository $documentRepository,
        private EntityManagerInterface $entityManager,
    ) {}

    /**
     * Generate and persist a complete test.
     *
     * @return array{
     *     quiz_id: int,
     *     resource_node_id: int,
     *     title: string,
     *     question_count: int,
     *     question_type: 'unique_answer',
     *     total_score: float,
     *     published: bool,
     *     provider_used: string,
     *     ai_assisted: true,
     *     source: array{type: 'topic'|'document', document_id: int|null, title: string},
     *     questions: list<array{question_id: int, title: string, score: float}>,
     *     content_url: string
     * }
     */
    public function createTest(
        Course $course,
        User $user,
        string $title,
        int $questionCount,
        string $sourceType,
        string $sourceTitle,
        string $sourceText,
        ?int $documentId,
        ?string $language,
        ?string $requestedProvider,
        bool $publish,
    ): array {
        $prepared = $this->prepareTest(
            $course,
            $user,
            $title,
            $questionCount,
            $sourceType,
            $sourceTitle,
            $sourceText,
            $language,
            $requestedProvider,
        );

        return $this->persistPreparedTest(
            $course,
            $user,
            $title,
            $prepared,
            $documentId,
            $publish,
        );
    }

    /**
     * Generate and validate all question data without writing a test resource.
     *
     * This method is used by compound operations such as MCP learning-path
     * creation so every external AI request completes before the first course
     * resource is persisted.
     *
     * @return array{
     *     provider_used: string,
     *     source_type: 'topic'|'document',
     *     source_title: string,
     *     source_text: string,
     *     question_count: int,
     *     questions: list<array{
     *         title: string,
     *         answers: list<string>,
     *         correct_index: int,
     *         feedback: string
     *     }>
     * }
     */
    public function prepareTest(
        Course $course,
        User $user,
        string $title,
        int $questionCount,
        string $sourceType,
        string $sourceTitle,
        string $sourceText,
        ?string $language,
        ?string $requestedProvider,
    ): array {
        $courseId = (int) $course->getId();
        if (!$this->aiFeatureAccessHelper->isFeatureEnabledForCourse('exercise_generator', $courseId)) {
            throw new RuntimeException('AI exercise generation is not enabled for this course.');
        }

        if (!\in_array($sourceType, ['topic', 'document'], true)) {
            throw new InvalidArgumentException('The test source type must be topic or document.');
        }

        if ($questionCount < self::MIN_QUESTION_COUNT || $questionCount > self::MAX_QUESTION_COUNT) {
            throw new InvalidArgumentException(\sprintf('The question count must be between %d and %d.', self::MIN_QUESTION_COUNT, self::MAX_QUESTION_COUNT));
        }

        $sourceText = $this->normalizeSourceText($sourceText);
        if ('' === $sourceText) {
            throw new InvalidArgumentException('The test source content is empty.');
        }

        $providerName = $this->resolveProviderName($requestedProvider);
        $this->quotaGuard->assertCanRequest($user, $providerName, 'text');

        $language = $this->normalizeLanguage($language, (string) $course->getCourseLanguage());
        $requestMarker = 'MCP_COURSE_TEST_REQUEST:'.hash(
            'sha256',
            $courseId.'|'.$title.'|'.$sourceType.'|'.$sourceText.'|'.microtime(true),
        );
        $lastRequestId = $this->getLastAiRequestId((int) $user->getId());
        $questions = null;
        $generationMode = 'ai';

        try {
            $generated = $this->requestAiken(
                $providerName,
                (string) $course->getTitle(),
                $title,
                $sourceType,
                $sourceTitle,
                $sourceText,
                $questionCount,
                $language,
                $requestMarker,
            );
            $questions = $this->tryParseAiken($generated, $questionCount);

            if (null === $questions) {
                $repaired = $this->requestAiken(
                    $providerName,
                    (string) $course->getTitle(),
                    $title,
                    $sourceType,
                    $sourceTitle,
                    $sourceText,
                    $questionCount,
                    $language,
                    $requestMarker,
                    $generated,
                );
                $questions = $this->tryParseAiken($repaired, $questionCount);
            }

            if (null === $questions) {
                $questions = $this->requestStructuredQuestions(
                    $user,
                    $providerName,
                    (string) $course->getTitle(),
                    $title,
                    $sourceType,
                    $sourceTitle,
                    $sourceText,
                    $questionCount,
                    $language,
                    $requestMarker,
                );
            }
        } catch (Throwable $exception) {
            error_log('[AI][mcp_course_test] Question generation failed: '.$exception->getMessage());

            throw new RuntimeException('The AI model could not generate the requested test: '.$exception->getMessage(), 0, $exception);
        } finally {
            $this->redactGeneratedRequestLogs(
                (int) $user->getId(),
                $lastRequestId,
                $providerName,
                $requestMarker,
                $sourceType,
                $sourceTitle,
                $sourceText,
            );
        }

        if (null === $questions) {
            $questions = $this->buildDeterministicFallbackQuestions(
                $sourceTitle,
                $sourceText,
                $questionCount,
            );
            $generationMode = 'deterministic_fallback';
        }

        return [
            'provider_used' => $providerName,
            'generation_mode' => $generationMode,
            'source_type' => $sourceType,
            'source_title' => $sourceTitle,
            'source_text' => $sourceText,
            'question_count' => $questionCount,
            'questions' => $questions,
        ];
    }

    /**
     * Persist a test from question data previously returned by prepareTest().
     *
     * @param array{
     *     provider_used: string,
     *     source_type: 'topic'|'document',
     *     source_title: string,
     *     source_text: string,
     *     question_count: int,
     *     questions: list<array{
     *         title: string,
     *         answers: list<string>,
     *         correct_index: int,
     *         feedback: string
     *     }>
     * } $prepared
     *
     * @return array{
     *     quiz_id: int,
     *     resource_node_id: int,
     *     title: string,
     *     question_count: int,
     *     question_type: 'unique_answer',
     *     total_score: float,
     *     published: bool,
     *     provider_used: string,
     *     ai_assisted: true,
     *     source: array{type: 'topic'|'document', document_id: int|null, title: string},
     *     questions: list<array{question_id: int, title: string, score: float}>,
     *     content_url: string
     * }
     */
    public function persistPreparedTest(
        Course $course,
        User $user,
        string $title,
        array $prepared,
        ?int $documentId,
        bool $publish,
    ): array {
        $providerName = trim((string) ($prepared['provider_used'] ?? ''));
        $generationMode = trim((string) ($prepared['generation_mode'] ?? 'ai'));
        $sourceType = (string) ($prepared['source_type'] ?? '');
        $sourceTitle = trim((string) ($prepared['source_title'] ?? ''));
        $sourceText = trim((string) ($prepared['source_text'] ?? ''));
        $questionCount = (int) ($prepared['question_count'] ?? 0);
        $questions = $prepared['questions'] ?? null;

        if (
            '' === $providerName
            || !\in_array($sourceType, ['topic', 'document'], true)
            || '' === $sourceTitle
            || '' === $sourceText
            || $questionCount < self::MIN_QUESTION_COUNT
            || $questionCount > self::MAX_QUESTION_COUNT
            || !\is_array($questions)
            || \count($questions) !== $questionCount
        ) {
            throw new InvalidArgumentException('The prepared test payload is invalid or incomplete.');
        }

        $created = $this->persistTest(
            $course,
            $user,
            $title,
            $questions,
            $publish,
        );

        $quizId = $created['quiz_id'];
        $this->markAiAssisted($quizId, $created['questions']);
        $this->aiDisclosureHelper->logAudit(
            targetKey: 'exercise:'.$quizId,
            userId: (int) $user->getId(),
            meta: [
                'feature' => 'mcp_course_test',
                'mode' => 'generated',
                'provider' => $providerName,
                'source_type' => $sourceType,
                'source_title' => mb_substr($this->oneLine($sourceTitle), 0, 200),
                'source_sha256' => hash('sha256', $sourceText),
                'source_length' => mb_strlen($sourceText),
                'question_count' => $questionCount,
                'question_type' => 'unique_answer',
                'published' => $publish,
                'generation_mode' => $generationMode,
            ],
            courseId: (int) $course->getId(),
            sessionId: 0,
        );

        return [
            'quiz_id' => $quizId,
            'resource_node_id' => $created['resource_node_id'],
            'title' => $title,
            'question_count' => $questionCount,
            'question_type' => 'unique_answer',
            'total_score' => self::TOTAL_SCORE,
            'published' => $publish,
            'provider_used' => $providerName,
            'ai_assisted' => true,
            'generation_mode' => $generationMode,
            'source' => [
                'type' => $sourceType,
                'document_id' => $documentId,
                'title' => $sourceTitle,
            ],
            'questions' => $created['questions'],
            'content_url' => '/main/exercise/overview.php?cid='
                .(int) $course->getId()
                .'&exerciseId='.$quizId,
        ];
    }

    public function getDocumentSource(CDocument $document): string
    {
        try {
            $content = (string) $this->documentRepository->getResourceFileContent($document);
        } catch (Throwable) {
            $content = '';
        }

        if ('' === trim($content)) {
            $content = (string) ($document->getResourceNode()?->getContent() ?? '');
        }

        return $this->normalizeSourceText($content);
    }

    private function resolveProviderName(?string $requestedProvider): string
    {
        $providerNames = array_values(array_filter(array_map(
            static fn (mixed $providerName): string => trim((string) $providerName),
            $this->aiProviderFactory->getProvidersForType('text'),
        )));

        if ([] === $providerNames) {
            throw new RuntimeException('No AI text provider is configured.');
        }

        $requestedProvider = null !== $requestedProvider ? trim($requestedProvider) : '';
        if ('' === $requestedProvider) {
            return $providerNames[0];
        }

        if (!\in_array($requestedProvider, $providerNames, true)) {
            throw new InvalidArgumentException('The selected AI text provider is not available.');
        }

        return $requestedProvider;
    }

    private function normalizeLanguage(?string $language, string $courseLanguage): string
    {
        $language = null !== $language ? trim($language) : '';
        if ('' === $language) {
            $language = trim($courseLanguage);
        }
        if ('' === $language) {
            $language = 'en';
        }

        if (!preg_match('/^[a-zA-Z0-9_-]{1,20}$/', $language)) {
            throw new InvalidArgumentException('The test language code is invalid.');
        }

        return $language;
    }

    private function normalizeSourceText(string $source): string
    {
        $source = (string) preg_replace('#<(script|style)\b[^>]*>.*?</\1>#is', ' ', $source);
        $source = html_entity_decode(strip_tags($source), ENT_QUOTES | ENT_HTML5);
        $source = trim((string) preg_replace('/\s+/u', ' ', $source));

        return mb_substr($source, 0, self::MAX_SOURCE_LENGTH);
    }

    private function requestAiken(
        string $providerName,
        string $courseTitle,
        string $testTitle,
        string $sourceType,
        string $sourceTitle,
        string $sourceText,
        int $questionCount,
        string $language,
        string $requestMarker,
        ?string $previousOutput = null,
    ): string {
        $systemPrompt = <<<'PROMPT'
Return only valid Aiken plain text. Generate the exact requested number of independent single-answer multiple-choice questions. Each question must have exactly four non-empty options labelled A. through D. and exactly one line in the form ANSWER: X. Do not use Markdown, code fences, numbering, headings, explanations, or introductory text. Use only the supplied source content.
PROMPT;

        $prompt = $requestMarker."\n"
            .'Language: '.$language."\n"
            .'Course: '.$this->oneLine($courseTitle)."\n"
            .'Test title: '.$this->oneLine($testTitle)."\n"
            .'Requested question count: '.$questionCount."\n"
            .'Source type: '.$sourceType."\n"
            .'Source title: '.$this->oneLine($sourceTitle)."\n\n"
            ."Create the test using only this source content:\n"
            .$sourceText;

        if (null !== $previousOutput) {
            $prompt .= "\n\nThe previous output was invalid or incomplete. Regenerate the complete test from the source. "
                ."Do not continue the previous output.\nPrevious invalid output:\n"
                .mb_substr($previousOutput, 0, 8_000);
        }

        $tokenBudget = min(12_000, max(800, 250 + ($questionCount * 220)));
        $result = $this->aiChatCompletionClient->chatWithMeta(
            $providerName,
            [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $prompt],
            ],
            [
                'temperature' => 0.2,
                'max_output_tokens' => $tokenBudget,
                'max_tokens' => $tokenBudget,
                'throw_on_error' => true,
            ],
        );

        $generated = trim(mb_substr((string) $result->text, 0, self::MAX_GENERATED_LENGTH));
        if ('' === $generated || str_starts_with($generated, 'Error:')) {
            throw new RuntimeException('The AI model returned an invalid test.');
        }

        return $generated;
    }

    /**
     * @return list<array{title: string, answers: list<string>, correct_index: int, feedback: string}>|null
     */
    private function requestStructuredQuestions(
        User $user,
        string $providerName,
        string $courseTitle,
        string $testTitle,
        string $sourceType,
        string $sourceTitle,
        string $sourceText,
        int $questionCount,
        string $language,
        string $requestMarker,
    ): ?array {
        $systemPrompt = <<<'PROMPT'
Return JSON only using this exact schema:
{"questions":[{"title":"Question text","answers":["Option A","Option B","Option C","Option D"],"correct_index":0,"feedback":"Optional short explanation"}]}
Generate the exact requested number of independent single-answer questions. Every question must have exactly four non-empty answers, correct_index must be an integer from 0 to 3, and all content must use only the supplied source.
PROMPT;
        $userPrompt = $requestMarker."\n"
            .'Language: '.$language."\n"
            .'Course: '.$this->oneLine($courseTitle)."\n"
            .'Test title: '.$this->oneLine($testTitle)."\n"
            .'Requested question count: '.$questionCount."\n"
            .'Source type: '.$sourceType."\n"
            .'Source title: '.$this->oneLine($sourceTitle)."\n\n"
            ."Create the test using only this source content:\n"
            .$sourceText;

        $result = $this->mcpTextAiService->requestJson(
            $user,
            $providerName,
            $systemPrompt,
            $userPrompt,
            min(12_000, max(1_200, 300 + ($questionCount * 260))),
        );

        return $this->normalizeStructuredQuestions(
            $result['questions'] ?? null,
            $questionCount,
        );
    }

    /**
     * @return list<array{title: string, answers: list<string>, correct_index: int, feedback: string}>|null
     */
    private function normalizeStructuredQuestions(mixed $rawQuestions, int $questionCount): ?array
    {
        if (!\is_array($rawQuestions) || \count($rawQuestions) < $questionCount) {
            return null;
        }

        $questions = [];

        foreach (\array_slice(array_values($rawQuestions), 0, $questionCount) as $rawQuestion) {
            if (!\is_array($rawQuestion)) {
                return null;
            }

            $title = $this->oneLine((string) ($rawQuestion['title'] ?? $rawQuestion['question'] ?? ''));
            $rawAnswers = $rawQuestion['answers'] ?? $rawQuestion['options'] ?? null;
            if ('' === $title || !\is_array($rawAnswers) || 4 !== \count($rawAnswers)) {
                return null;
            }

            $answers = array_map(
                fn (mixed $answer): string => $this->oneLine((string) $answer),
                array_values($rawAnswers),
            );
            if (4 !== \count(array_filter($answers, static fn (string $answer): bool => '' !== $answer))) {
                return null;
            }

            $correct = $rawQuestion['correct_index'] ?? $rawQuestion['correctIndex'] ?? $rawQuestion['answer'] ?? null;
            $correctIndex = $this->normalizeCorrectIndex($correct, $answers);
            if (null === $correctIndex) {
                return null;
            }

            $questions[] = [
                'title' => $title,
                'answers' => $answers,
                'correct_index' => $correctIndex,
                'feedback' => $this->oneLine((string) ($rawQuestion['feedback'] ?? '')),
            ];
        }

        return \count($questions) === $questionCount ? $questions : null;
    }

    /**
     * @param list<string> $answers
     */
    private function normalizeCorrectIndex(mixed $correct, array $answers): ?int
    {
        if (\is_int($correct) && $correct >= 0 && $correct <= 3) {
            return $correct;
        }

        if (is_numeric($correct)) {
            $numeric = (int) $correct;
            if ($numeric >= 0 && $numeric <= 3) {
                return $numeric;
            }
            if ($numeric >= 1 && $numeric <= 4) {
                return $numeric - 1;
            }
        }

        $correctText = $this->oneLine((string) $correct);
        if (preg_match('/^[A-D]$/i', $correctText)) {
            return \ord(strtoupper($correctText)) - \ord('A');
        }

        foreach ($answers as $index => $answer) {
            if (0 === strcasecmp($answer, $correctText)) {
                return $index;
            }
        }

        return null;
    }

    /**
     * @return list<array{title: string, answers: list<string>, correct_index: int, feedback: string}>|null
     */
    private function tryParseAiken(string $generated, int $questionCount): ?array
    {
        $generated = $this->normalizeAikenSyntax($generated);
        $questions = [];
        $current = null;

        foreach (explode("\n", $generated) as $line) {
            $line = trim($line);
            if ('' === $line) {
                continue;
            }

            if (preg_match('/^([A-D])\.\s+(.+)$/u', $line, $matches)) {
                if (null === $current) {
                    return null;
                }
                $current['answers'][$matches[1]] = $this->oneLine($matches[2]);

                continue;
            }

            if (preg_match('/^ANSWER:\s*([A-D])$/u', $line, $matches)) {
                if (null === $current) {
                    return null;
                }
                $current['correct'] = $matches[1];

                continue;
            }

            if (preg_match('/^ANSWER_EXPLANATION:\s*(.*)$/u', $line, $matches)) {
                if (null === $current) {
                    return null;
                }
                $current['feedback'] = $this->oneLine($matches[1]);

                continue;
            }

            if (null !== $current) {
                $normalized = $this->normalizeParsedQuestion($current);
                if (null === $normalized) {
                    return null;
                }
                $questions[] = $normalized;
            }

            $current = [
                'title' => $this->stripQuestionNumber($line),
                'answers' => [],
                'correct' => '',
                'feedback' => '',
            ];
        }

        if (null !== $current) {
            $normalized = $this->normalizeParsedQuestion($current);
            if (null === $normalized) {
                return null;
            }
            $questions[] = $normalized;
        }

        if ($questionCount !== \count($questions)) {
            return null;
        }

        return $questions;
    }

    /**
     * Last-resort exact-count fallback used only after every configured AI
     * format attempt fails. The result is intentionally transparent through
     * generation_mode=deterministic_fallback.
     *
     * @return list<array{
     *     title: string,
     *     answers: list<string>,
     *     correct_index: int,
     *     feedback: string
     * }>
     */
    private function buildDeterministicFallbackQuestions(
        string $sourceTitle,
        string $sourceText,
        int $questionCount,
    ): array {
        $sentences = preg_split(
            '/(?<=[.!?])\s+/u',
            trim($sourceText),
        ) ?: [];

        $sentences = array_values(array_unique(array_filter(array_map(
            fn (string $sentence): string => $this->oneLine($sentence),
            $sentences,
        ), static fn (string $sentence): bool => mb_strlen($sentence) >= 30)));

        if ([] === $sentences) {
            $sentences[] = $this->oneLine($sourceText);
        }

        $questions = [];

        for ($index = 0; $index < $questionCount; ++$index) {
            $statement = mb_substr(
                $sentences[$index % \count($sentences)],
                0,
                240,
            );
            if ('' === $statement) {
                $statement = 'The material presents an essential idea about '.$sourceTitle.'.';
            }

            $questions[] = [
                'title' => 'Which statement is explicitly supported by “'
                    .mb_substr($sourceTitle, 0, 120)
                    .'”?',
                'answers' => [
                    $statement,
                    'The material states that this topic has no educational relevance.',
                    'The material presents no relationship between its main concepts.',
                    'The material concludes that all described processes are identical.',
                ],
                'correct_index' => 0,
                'feedback' => 'The correct option is taken directly from the supplied learning content.',
            ];
        }

        return $questions;
    }

    private function normalizeAikenSyntax(string $generated): string
    {
        $generated = str_replace(["\r\n", "\r"], "\n", trim($generated));
        $generated = (string) preg_replace('/```(?:aiken|text)?\s*/iu', '', $generated);
        $generated = str_replace('```', '', $generated);

        $normalizedLines = [];
        foreach (explode("\n", $generated) as $line) {
            $line = trim($line);
            if ('' === $line) {
                $normalizedLines[] = '';

                continue;
            }

            if (preg_match('/^(?:[-*]\s*)?([A-D])\s*[\)\:\-\.]\s*(.+?)\s*$/iu', $line, $matches)) {
                $normalizedLines[] = strtoupper($matches[1]).'. '.trim($matches[2], " *\t");

                continue;
            }

            if (preg_match('/^\**\s*(?:ANSWER|RESPUESTA|R[ÉE]PONSE|CORRECT(?:\s+ANSWER)?|RESPUESTA\s+CORRECTA)\s*:\s*([A-D])\s*[.\s]*\**$/iu', $line, $matches)) {
                $normalizedLines[] = 'ANSWER: '.strtoupper($matches[1]);

                continue;
            }

            $normalizedLines[] = trim($line, " *\t");
        }

        return trim(implode("\n", $normalizedLines));
    }

    /**
     * @param array{title: string, answers: array<string, string>, correct: string, feedback: string} $question
     *
     * @return array{title: string, answers: list<string>, correct_index: int, feedback: string}|null
     */
    private function normalizeParsedQuestion(array $question): ?array
    {
        $title = $this->oneLine($question['title']);
        if ('' === $title) {
            return null;
        }

        $answers = [];
        foreach (['A', 'B', 'C', 'D'] as $letter) {
            $answer = $this->oneLine((string) ($question['answers'][$letter] ?? ''));
            if ('' === $answer) {
                return null;
            }
            $answers[] = $answer;
        }

        $correctIndex = array_search($question['correct'], ['A', 'B', 'C', 'D'], true);
        if (false === $correctIndex) {
            return null;
        }

        return [
            'title' => $title,
            'answers' => $answers,
            'correct_index' => (int) $correctIndex,
            'feedback' => $this->oneLine($question['feedback']),
        ];
    }

    private function stripQuestionNumber(string $title): string
    {
        $title = trim($title);
        $normalized = preg_replace('/^\d+\s*[\.\)\-:]\s+/u', '', $title);

        return $this->oneLine(null !== $normalized ? $normalized : $title);
    }

    /**
     * @param list<array{title: string, answers: list<string>, correct_index: int, feedback: string}> $questions
     *
     * @return array{
     *     quiz_id: int,
     *     resource_node_id: int,
     *     questions: list<array{question_id: int, title: string, score: float}>
     * }
     */
    private function persistTest(
        Course $course,
        User $user,
        string $title,
        array $questions,
        bool $publish,
    ): array {
        $visibility = $publish
            ? ResourceLink::VISIBILITY_PUBLISHED
            : ResourceLink::VISIBILITY_DRAFT;
        $scores = $this->buildQuestionScores(\count($questions));

        return $this->entityManager->wrapInTransaction(function () use (
            $course,
            $user,
            $title,
            $questions,
            $visibility,
            $scores,
        ): array {
            /*
             * Legacy Exercise::save() reloads and writes these fields through
             * non-nullable setters when a quiz is linked to a learning path.
             * Initialize every nullable legacy string/scalar explicitly to
             * prevent a sequence of TypeErrors during that round trip.
             */
            $quiz = (new CQuiz())
                ->setTitle($title)
                ->setDescription('')
                ->setSound('')
                ->setAccessCondition('')
                ->setTextWhenFinished('')
                ->setTextWhenFinishedFailure('')
                ->setNotifications('')
                ->setPassPercentage(0)
                ->setQuestionSelectionType(1)
                ->setParent($course)
                ->setCreator($user)
                ->addCourseLink($course, null, null, $visibility)
            ;

            $this->entityManager->persist($quiz);
            $createdQuestions = [];

            foreach ($questions as $index => $questionData) {
                $position = $index + 1;
                $score = $scores[$index];
                $question = (new CQuizQuestion())
                    ->setQuestion($questionData['title'])
                    ->setDescription('')
                    ->setPonderation($score)
                    ->setPosition($position)
                    ->setType(self::QUESTION_TYPE_UNIQUE_ANSWER)
                    ->setLevel(1)
                    ->setFeedback('' !== $questionData['feedback'] ? $questionData['feedback'] : null)
                    ->setParent($course)
                    ->setCreator($user)
                    ->addCourseLink($course)
                ;

                $this->entityManager->persist($question);

                foreach ($questionData['answers'] as $answerIndex => $answerText) {
                    $isCorrect = $answerIndex === $questionData['correct_index'];
                    $answer = (new CQuizAnswer())
                        ->setQuestion($question)
                        ->setAnswer($answerText)
                        ->setCorrect($isCorrect ? 1 : 0)
                        ->setComment($isCorrect ? $questionData['feedback'] : '')
                        ->setPonderation($isCorrect ? $score : 0.0)
                        ->setPosition($answerIndex + 1)
                    ;
                    $this->entityManager->persist($answer);
                }

                $relation = (new CQuizRelQuestion())
                    ->setQuiz($quiz)
                    ->setQuestion($question)
                    ->setQuestionOrder($position)
                ;
                $this->entityManager->persist($relation);
                $createdQuestions[] = [
                    'entity' => $question,
                    'title' => $questionData['title'],
                    'score' => $score,
                ];
            }

            $this->entityManager->flush();

            $quizId = (int) $quiz->getIid();
            $resourceNodeId = (int) ($quiz->getResourceNode()?->getId() ?? 0);
            if ($quizId <= 0 || $resourceNodeId <= 0) {
                throw new RuntimeException('Chamilo created an incomplete test resource.');
            }

            $questionRows = [];
            foreach ($createdQuestions as $createdQuestion) {
                $questionId = (int) $createdQuestion['entity']->getIid();
                if ($questionId <= 0) {
                    throw new RuntimeException('Chamilo created an incomplete test question.');
                }
                $questionRows[] = [
                    'question_id' => $questionId,
                    'title' => $createdQuestion['title'],
                    'score' => $createdQuestion['score'],
                ];
            }

            return [
                'quiz_id' => $quizId,
                'resource_node_id' => $resourceNodeId,
                'questions' => $questionRows,
            ];
        });
    }

    /**
     * @return list<float>
     */
    private function buildQuestionScores(int $questionCount): array
    {
        $baseScore = floor((self::TOTAL_SCORE / $questionCount) * 100) / 100;
        $scores = array_fill(0, $questionCount, $baseScore);
        $scores[$questionCount - 1] = round(
            self::TOTAL_SCORE - ($baseScore * ($questionCount - 1)),
            2,
        );

        return $scores;
    }

    /**
     * @param list<array{question_id: int, title: string, score: float}> $questions
     */
    private function markAiAssisted(int $quizId, array $questions): void
    {
        if (!$this->aiDisclosureHelper->isDisclosureEnabled()) {
            return;
        }

        $this->aiDisclosureHelper->markAiAssistedExtraField('exercise', $quizId, true);
        foreach ($questions as $question) {
            $this->aiDisclosureHelper->markAiAssistedExtraField(
                'question',
                $question['question_id'],
                true,
            );
        }
    }

    private function getLastAiRequestId(int $userId): int
    {
        if ($userId <= 0) {
            return 0;
        }

        $lastId = $this->entityManager->createQueryBuilder()
            ->select('MAX(aiRequest.id)')
            ->from(AiRequests::class, 'aiRequest')
            ->andWhere('aiRequest.userId = :userId')
            ->setParameter('userId', $userId, Types::INTEGER)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return (int) $lastId;
    }

    private function redactGeneratedRequestLogs(
        int $userId,
        int $lastRequestId,
        string $providerName,
        string $requestMarker,
        string $sourceType,
        string $sourceTitle,
        string $sourceText,
    ): void {
        if ($userId <= 0) {
            return;
        }

        try {
            /** @var AiRequests[] $requests */
            $requests = $this->entityManager->createQueryBuilder()
                ->select('aiRequest')
                ->from(AiRequests::class, 'aiRequest')
                ->andWhere('aiRequest.id > :lastRequestId')
                ->andWhere('aiRequest.userId = :userId')
                ->andWhere('aiRequest.aiProvider = :providerName')
                ->andWhere('aiRequest.requestText LIKE :requestMarker')
                ->setParameter('lastRequestId', $lastRequestId, Types::INTEGER)
                ->setParameter('userId', $userId, Types::INTEGER)
                ->setParameter('providerName', $providerName, Types::STRING)
                ->setParameter('requestMarker', '%'.$requestMarker.'%', Types::STRING)
                ->getQuery()
                ->getResult()
            ;

            $safeAudit = \sprintf(
                'MCP course test; source_type=%s; source_title=%s; source_sha256=%s; source_length=%d',
                $sourceType,
                mb_substr($this->oneLine($sourceTitle), 0, 200),
                hash('sha256', $sourceText),
                mb_strlen($sourceText),
            );

            foreach ($requests as $request) {
                $request->setRequestText($safeAudit);
                $this->entityManager->persist($request);
            }
            if ([] !== $requests) {
                $this->entityManager->flush();
            }
        } catch (Throwable) {
            // Audit redaction must never replace the main user-facing result.
        }
    }

    private function oneLine(string $value): string
    {
        $value = html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5);

        return trim((string) preg_replace('/\s+/u', ' ', $value));
    }
}
