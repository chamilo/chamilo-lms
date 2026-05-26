<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\AiProvider;

use Chamilo\CoreBundle\Entity\AiRequests;
use Chamilo\CoreBundle\Repository\AiRequestsRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Exception;
use RuntimeException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class DeepSeekProvider implements AiProviderInterface, AiDocumentProviderInterface
{
    private array $providerConfig;
    private string $apiUrl;
    private string $apiKey;
    private string $model;
    private float $temperature;
    private int $maxTokens;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        SettingsManager $settingsManager,
        private readonly AiRequestsRepository $aiRequestsRepository,
        private readonly Security $security
    ) {
        $configJson = $settingsManager->getSetting('ai_helpers.ai_providers', true);
        $config = \is_string($configJson) ? (json_decode($configJson, true) ?? []) : (\is_array($configJson) ? $configJson : []);

        if (!isset($config['deepseek']) || !\is_array($config['deepseek'])) {
            throw new RuntimeException('DeepSeek configuration is missing.');
        }

        $this->providerConfig = $config['deepseek'];
        $this->apiKey = (string) ($this->providerConfig['api_key'] ?? '');

        if ('' === trim($this->apiKey)) {
            throw new RuntimeException('DeepSeek API key is missing.');
        }

        $textCfg = $this->providerConfig['text'] ?? [];
        if (!\is_array($textCfg)) {
            $textCfg = [];
        }

        $this->apiUrl = (string) ($textCfg['url'] ?? 'https://api.deepseek.com/chat/completions');
        $this->model = (string) ($textCfg['model'] ?? 'deepseek-chat');
        $this->temperature = (float) ($textCfg['temperature'] ?? 0.7);
        $this->maxTokens = (int) ($textCfg['max_tokens'] ?? 1000);
    }

    /**
     * Chat-style entrypoint (OpenAI-compatible).
     *
     * @param array<int, array{role:string,content:string}> $messages
     * @param array<string,mixed>                           $options
     */
    public function chat(array $messages, array $options = []): string
    {
        $userId = $this->getUserId();
        if (!$userId) {
            error_log('[AI][DeepSeek][chat] User not authenticated.');

            return 'Error: User is not authenticated.';
        }

        $normalized = $this->normalizeChatMessages($messages);
        if (empty($normalized)) {
            error_log('[AI][DeepSeek][chat] Empty messages payload.');

            return 'Error: Empty chat messages.';
        }

        $resolved = $this->resolveTextOptions($options);

        $payload = [
            'model' => $resolved['model'],
            'messages' => $normalized,
            'temperature' => $resolved['temperature'],
            'max_tokens' => $resolved['max_tokens'],
        ];

        // Optional safe overrides
        if (isset($options['top_p'])) {
            $payload['top_p'] = (float) $options['top_p'];
        }
        if (isset($options['presence_penalty'])) {
            $payload['presence_penalty'] = (float) $options['presence_penalty'];
        }
        if (isset($options['frequency_penalty'])) {
            $payload['frequency_penalty'] = (float) $options['frequency_penalty'];
        }

        try {
            $response = $this->httpClient->request('POST', $resolved['url'], [
                'headers' => [
                    'Authorization' => 'Bearer '.$this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
            ]);

            $data = $response->toArray(false);

            if (\is_array($data) && isset($data['error'])) {
                $msg = $data['error']['message'] ?? 'DeepSeek returned an error response.';
                $msg = \is_string($msg) ? trim($msg) : 'DeepSeek returned an error response.';
                error_log('[AI][DeepSeek][chat] Error response: '.$msg);

                return 'Error: '.$msg;
            }

            $generated = $data['choices'][0]['message']['content'] ?? null;
            if (!\is_string($generated) || '' === trim($generated)) {
                error_log('[AI][DeepSeek][chat] Empty content returned.');

                return 'Error: Empty response from DeepSeek.';
            }

            $this->saveAiRequest(
                $userId,
                'chat',
                $this->messagesForLog($normalized, 900),
                'deepseek',
                (int) ($data['usage']['prompt_tokens'] ?? 0),
                (int) ($data['usage']['completion_tokens'] ?? 0),
                (int) ($data['usage']['total_tokens'] ?? 0)
            );

            return trim($generated);
        } catch (Exception $e) {
            error_log('[AI][DeepSeek][chat] Exception: '.$e->getMessage());

            return 'Error: '.$e->getMessage();
        }
    }

    /**
     * Prompt-style entrypoint used by TaskGrader text mode and other features.
     *
     * @param array<string,mixed> $options
     */
    public function generateText(string $prompt, array $options = []): string
    {
        $prompt = trim($prompt);
        if ('' === $prompt) {
            return 'Error: Empty prompt.';
        }

        $messages = [
            ['role' => 'system', 'content' => 'You are a helpful assistant.'],
            ['role' => 'user', 'content' => $prompt],
        ];

        return $this->chat($messages, $options);
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

        return $this->requestDeepSeek($prompt, 'quiz');
    }

    public function generateLearnPath(string $topic, int $chaptersCount, string $language, int $wordsCount, bool $addTests, int $numQuestions): ?array
    {
        $tableOfContentsPrompt = \sprintf(
            'Generate a structured table of contents for a course in "%s" with %d chapters on "%s".
            Return a numbered list, each chapter on a new line. No conclusion.',
            $language,
            $chaptersCount,
            $topic
        );

        $lpStructure = $this->requestDeepSeek($tableOfContentsPrompt, 'learnpath');
        if (!$lpStructure) {
            return ['success' => false, 'message' => 'Failed to generate course structure.'];
        }

        $lpItems = [];
        $chapters = explode("\n", trim($lpStructure));
        foreach ($chapters as $chapterTitle) {
            $chapterTitle = trim($chapterTitle);
            if ('' === $chapterTitle) {
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

            $chapterContent = $this->requestDeepSeek($chapterPrompt, 'learnpath');
            if (!$chapterContent) {
                continue;
            }

            $lpItems[] = [
                'title' => $chapterTitle,
                'content' => "<html><head><title>{$chapterTitle}</title></head><body>{$chapterContent}</body></html>",
            ];
        }

        $quizItems = [];
        if ($addTests) {
            foreach ($lpItems as $chapter) {
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

                $quizContent = $this->requestDeepSeek($quizPrompt, 'learnpath');
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

    public function gradeOpenAnswer(string $prompt, string $toolName): ?string
    {
        return $this->requestDeepSeek($prompt, $toolName);
    }

    public function generateDocument(string $prompt, string $toolName, ?array $options = []): ?string
    {
        // Document is text-like for DeepSeek
        return $this->requestDeepSeek($prompt, $toolName);
    }

    private function requestDeepSeek(string $prompt, string $toolName): ?string
    {
        $userId = $this->getUserId();
        if (!$userId) {
            throw new RuntimeException('User not authenticated.');
        }

        $payload = [
            'model' => $this->model,
            'messages' => [
                ['role' => 'system', 'content' => 'You are a helpful AI assistant that generates structured educational content.'],
                ['role' => 'user', 'content' => $prompt],
            ],
            'temperature' => $this->temperature,
            'max_tokens' => $this->maxTokens,
        ];

        try {
            $response = $this->httpClient->request('POST', $this->apiUrl, [
                'headers' => [
                    'Authorization' => 'Bearer '.$this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
            ]);

            $data = $response->toArray(false);

            if (\is_array($data) && isset($data['error'])) {
                $msg = $data['error']['message'] ?? 'DeepSeek returned an error response.';
                $msg = \is_string($msg) ? trim($msg) : 'DeepSeek returned an error response.';
                error_log('[AI][DeepSeek] Error response: '.$msg);

                return null;
            }

            $generatedContent = $data['choices'][0]['message']['content'] ?? null;
            if (!\is_string($generatedContent) || '' === trim($generatedContent)) {
                error_log('[AI][DeepSeek] Empty content returned.');

                return null;
            }

            $this->saveAiRequest(
                $userId,
                $toolName,
                $prompt,
                'deepseek',
                (int) ($data['usage']['prompt_tokens'] ?? 0),
                (int) ($data['usage']['completion_tokens'] ?? 0),
                (int) ($data['usage']['total_tokens'] ?? 0)
            );

            return $generatedContent;
        } catch (Exception $e) {
            error_log('[AI][DeepSeek] Exception: '.$e->getMessage());

            return null;
        }
    }

    private function filterValidAikenQuestions(string $quizContent): array
    {
        $questions = preg_split('/\n{2,}/', trim($quizContent)) ?: [];

        $validQuestions = [];
        foreach ($questions as $questionBlock) {
            $lines = explode("\n", trim($questionBlock));

            if (\count($lines) < 6) {
                continue;
            }

            $options = \array_slice($lines, 1, 4);
            $validOptions = array_filter($options, static fn ($line) => (bool) preg_match('/^[A-D]\. .+/', $line));

            $answerLine = (string) end($lines);
            if (4 === \count($validOptions) && preg_match('/^ANSWER: [A-D]$/', $answerLine)) {
                $validQuestions[] = implode("\n", $lines);
            }
        }

        return $validQuestions;
    }

    /**
     * @param array<int, array{role:string,content:string}> $messages
     *
     * @return array<int, array{role:string,content:string}>
     */
    private function normalizeChatMessages(array $messages): array
    {
        $out = [];

        foreach ($messages as $m) {
            if (!\is_array($m)) {
                continue;
            }

            $role = isset($m['role']) ? trim((string) $m['role']) : '';
            $content = isset($m['content']) ? trim((string) $m['content']) : '';

            if ('' === $role || '' === $content) {
                continue;
            }

            $role = strtolower($role);
            if (!\in_array($role, ['system', 'user', 'assistant', 'tool'], true)) {
                $role = 'user';
            }

            $out[] = [
                'role' => $role,
                'content' => $content,
            ];
        }

        return $out;
    }

    /**
     * @param array<int, array{role:string,content:string}> $messages
     */
    private function messagesForLog(array $messages, int $maxChars = 900): string
    {
        $parts = [];
        foreach ($messages as $m) {
            $role = $m['role'] ?? 'user';
            $content = trim((string) ($m['content'] ?? ''));

            if ('' === $content) {
                continue;
            }

            $parts[] = strtoupper((string) $role).': '.mb_substr($content, 0, 300);
        }

        $s = implode(' | ', $parts);

        return mb_substr($s, 0, $maxChars);
    }

    /**
     * Resolve per-request overrides for text/chat.
     *
     * @param array<string,mixed> $options
     *
     * @return array{url:string,model:string,temperature:float,max_tokens:int}
     */
    private function resolveTextOptions(array $options): array
    {
        $url = (string) (($options['url'] ?? null) ?? $this->apiUrl);
        $model = (string) (($options['model'] ?? null) ?? $this->model);

        $temperature = (float) (($options['temperature'] ?? null) ?? $this->temperature);

        // Accept either max_tokens or max_output_tokens (normalize to max_tokens).
        $maxTokens = $options['max_tokens'] ?? ($options['max_output_tokens'] ?? null);
        $maxTokens = (int) (($maxTokens ?? null) ?? $this->maxTokens);

        if ($maxTokens <= 0) {
            $maxTokens = $this->maxTokens > 0 ? $this->maxTokens : 1000;
        }

        return [
            'url' => $url,
            'model' => $model,
            'temperature' => $temperature,
            'max_tokens' => $maxTokens,
        ];
    }

    private function saveAiRequest(
        int $userId,
        string $toolName,
        string $requestText,
        string $provider,
        int $promptTokens = 0,
        int $completionTokens = 0,
        int $totalTokens = 0
    ): void {
        try {
            $aiRequest = new AiRequests();
            $aiRequest
                ->setUserId($userId)
                ->setToolName($toolName)
                ->setRequestText($requestText)
                ->setPromptTokens($promptTokens)
                ->setCompletionTokens($completionTokens)
                ->setTotalTokens($totalTokens)
                ->setAiProvider($provider)
            ;

            $this->aiRequestsRepository->save($aiRequest);
        } catch (Exception $e) {
            error_log('[AI] Failed to save AiRequests record: '.$e->getMessage());
        }
    }

    private function getUserId(): ?int
    {
        $user = $this->security->getUser();

        return $user instanceof UserInterface ? $user->getId() : null;
    }
}
