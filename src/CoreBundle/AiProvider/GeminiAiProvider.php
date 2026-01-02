<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\AiProvider;

use Chamilo\CoreBundle\Entity\AiRequests;
use Chamilo\CoreBundle\Repository\AiRequestsRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Exception;
use RuntimeException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class GeminiAiProvider implements AiProviderInterface
{
    private string $apiUrl;
    private string $apiKey;
    private string $model;
    private float $temperature;
    private HttpClientInterface $httpClient;
    private AiRequestsRepository $aiRequestsRepository;
    private Security $security;

    public function __construct(
        HttpClientInterface $httpClient,
        SettingsManager $settingsManager,
        AiRequestsRepository $aiRequestsRepository,
        Security $security
    ) {
        $this->httpClient = $httpClient;
        $this->aiRequestsRepository = $aiRequestsRepository;
        $this->security = $security;

        // Get AI providers from settings
        $configJson = $settingsManager->getSetting('ai_helpers.ai_providers', true);
        $config = json_decode($configJson, true) ?? [];

        if (!isset($config['gemini'])) {
            throw new RuntimeException('Gemini configuration is missing.');
        }
        if (!isset($config['gemini']['text'])) {
            throw new RuntimeException('Gemini configuration for text processing is missing.');
        }

        $this->apiKey = $config['gemini']['api_key'] ?? '';
        // Gemini expects endpoint like: https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent
        $this->model = $config['gemini']['text']['model'] ?? 'gemini-2.5-flash';
        $this->apiUrl = $config['gemini']['text']['url'] ?? "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent";
        $this->temperature = $config['gemini']['text']['temperature'] ?? 0.7;

        if (empty($this->apiKey)) {
            throw new RuntimeException('Gemini API key is missing.');
        }
    }

    public function generateQuestions(string $topic, int $numQuestions, string $questionType, string $language): ?string
    {
        $prompt = \sprintf(
            'Generate %d "%s" questions in Aiken format in the %s language about "%s".
            Ensure each question follows this format:

            1. The question text.
            A. Option A
            B. Option B
            C. Option C
            D. Option D
            ANSWER: (Correct answer letter)

            The output should be plain text without additional symbols or markdown.',
            $numQuestions,
            $questionType,
            $language,
            $topic
        );

        return $this->requestGemini($prompt, 'quiz');
    }

    public function generateLearnPath(
        string $topic,
        int $chaptersCount,
        string $language,
        int $wordsCount,
        bool $addTests,
        int $numQuestions
    ): ?array {
        // Step 1: Generate the Table of Contents
        $tableOfContentsPrompt = \sprintf(
            'Generate a structured table of contents for a course in "%s" with %d chapters on "%s".
            Return a numbered list, each chapter on a new line. No conclusion.',
            $language,
            $chaptersCount,
            $topic
        );

        $lpStructure = $this->requestGemini($tableOfContentsPrompt, 'learnpath');
        if (!$lpStructure) {
            return ['success' => false, 'message' => 'Failed to generate course structure.'];
        }

        // Step 2: Generate content for each chapter
        $lpItems = [];
        $chapters = explode("\n", trim($lpStructure));
        foreach ($chapters as $index => $chapterTitle) {
            $chapterTitle = trim($chapterTitle);
            if (empty($chapterTitle)) {
                continue;
            }

            $chapterPrompt = \sprintf(
                'Create a learning chapter in HTML for "%s" in "%s" with %d words.
                Title: "%s". Assume the reader already knows the context.',
                $topic,
                $language,
                $wordsCount,
                $chapterTitle
            );

            $chapterContent = $this->requestGemini($chapterPrompt, 'learnpath');
            if (!$chapterContent) {
                continue;
            }

            $lpItems[] = [
                'title' => $chapterTitle,
                'content' => "<html><head><title>{$chapterTitle}</title></head><body>{$chapterContent}</body></html>",
            ];
        }

        // Step 3: Generate quizzes if enabled
        $quizItems = [];
        if ($addTests) {
            foreach ($lpItems as &$chapter) {
                $quizPrompt = \sprintf(
                    'Generate %d multiple-choice questions in Aiken format in %s about "%s".
            Ensure each question follows this format:

            1. The question text.
            A. Option A
            B. Option B
            C. Option C
            D. Option D
            ANSWER: (Correct answer letter)

            Each question must have exactly 4 options and one answer line.
            Return only valid questions without extra text.',
                    $numQuestions,
                    $language,
                    $chapter['title']
                );

                $quizContent = $this->requestGemini($quizPrompt, 'learnpath');

                if ($quizContent) {
                    $validQuestions = $this->filterValidAikenQuestions($quizContent);

                    if (!empty($validQuestions)) {
                        $quizItems[] = [
                            'title' => 'Quiz: '.$chapter['title'],
                            'content' => implode("\n\n", $validQuestions),
                        ];
                    }
                }
            }
        }

        return [
            'success' => true,
            'topic' => $topic,
            'lp_items' => $lpItems,
            'quiz_items' => $quizItems,
        ];
    }

    private function filterValidAikenQuestions(string $quizContent): array
    {
        $questions = preg_split('/\n{2,}/', trim($quizContent));

        $validQuestions = [];
        foreach ($questions as $questionBlock) {
            $lines = explode("\n", trim($questionBlock));

            if (\count($lines) < 6) {
                continue;
            }

            $options = \array_slice($lines, 1, 4);
            $validOptions = array_filter($options, fn($line) => preg_match('/^[A-D]\. .+/', $line));

            $answerLine = end($lines);
            if (4 === \count($validOptions) && preg_match('/^ANSWER: [A-D]$/', $answerLine)) {
                $validQuestions[] = implode("\n", $lines);
            }
        }

        return $validQuestions;
    }

    private function requestGemini(string $prompt, string $toolName): ?string
    {
        $userId = $this->getUserId();
        if (!$userId) {
            throw new RuntimeException('User not authenticated.');
        }

        // Gemini expects a "contents" array (of turns), with inner "parts" [{"text": "..."}]
        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => $this->temperature,
                'maxOutputTokens' => 1000,
            ],
        ];

        try {
            $response = $this->httpClient->request('POST', $this->apiUrl, [
                'headers' => [
                    'x-goog-api-key' => $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
            ]);

            $statusCode = $response->getStatusCode();
            $data = $response->toArray();

            // Gemini returns "candidates" (array), first candidate, first part of content
            if (
                200 === $statusCode
                && isset($data['candidates'][0]['content']['parts'][0]['text'])
            ) {
                $generatedContent = $data['candidates'][0]['content']['parts'][0]['text'];

                $aiRequest = new AiRequests();
                $aiRequest->setUserId($userId)
                    ->setToolName($toolName)
                    ->setRequestText($prompt)
                    // Gemini does not currently return token counts, so we set to 0
                    ->setPromptTokens($data['usage']['prompt_tokens'] ?? 0)
                    ->setCompletionTokens($data['usage']['completion_tokens'] ?? 0)
                    ->setTotalTokens($data['usage']['total_tokens'] ?? 0)
                    ->setAiProvider('gemini');

                $this->aiRequestsRepository->save($aiRequest);

                return $generatedContent;
            }

            return null;
        } catch (Exception $e) {
            error_log('[AI][Gemini] Exception: '.$e->getMessage());
            return null;
        }
    }

    public function gradeOpenAnswer(string $prompt, string $toolName): ?string
    {
        return $this->requestGemini($prompt, $toolName);
    }

    private function getUserId(): ?int
    {
        $user = $this->security->getUser();

        return $user instanceof UserInterface ? $user->getId() : null;
    }
}
