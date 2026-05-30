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

class ClaudeProvider implements AiProviderInterface, AiDocumentProviderInterface
{
    private string $apiKey;
    private string $anthropicVersion;
    private string $anthropicBeta;
    private string $textApiUrl;
    private string $textModel;
    private float $textTemperature;
    private int $textMaxTokens;
    private string $documentApiUrl;
    private string $documentModel;
    private float $documentTemperature;
    private int $documentMaxTokens;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly SettingsManager $settingsManager,
        private readonly AiRequestsRepository $aiRequestsRepository,
        private readonly Security $security
    ) {
        $config = $this->readProvidersConfig();
        $providerKey = $this->getProviderKey();

        if (!isset($config[$providerKey]) || !\is_array($config[$providerKey])) {
            throw new RuntimeException($this->getProviderLabel().' configuration is missing.');
        }

        $providerConfig = $config[$providerKey];

        $this->apiKey = (string) ($providerConfig['api_key'] ?? '');
        if ('' === trim($this->apiKey)) {
            throw new RuntimeException($this->getProviderLabel().' API key is missing.');
        }

        $this->anthropicVersion = (string) ($providerConfig['anthropic_version'] ?? '2023-06-01');
        $this->anthropicBeta = (string) ($providerConfig['anthropic_beta'] ?? '');

        $textCfg = $providerConfig['text'] ?? null;
        if (!\is_array($textCfg)) {
            throw new RuntimeException($this->getProviderLabel().' configuration for text processing is missing.');
        }

        $this->textApiUrl = (string) ($textCfg['url'] ?? 'https://api.anthropic.com/v1/messages');
        $this->textModel = (string) ($textCfg['model'] ?? 'claude-sonnet-4-6');
        $this->textTemperature = (float) ($textCfg['temperature'] ?? 0.7);
        $this->textMaxTokens = (int) ($textCfg['max_tokens'] ?? ($textCfg['max_output_tokens'] ?? 1000));

        $docCfg = $providerConfig['document'] ?? null;
        $this->documentApiUrl = \is_array($docCfg) ? (string) ($docCfg['url'] ?? $this->textApiUrl) : $this->textApiUrl;
        $this->documentModel = \is_array($docCfg) ? (string) ($docCfg['model'] ?? $this->textModel) : $this->textModel;
        $this->documentTemperature = \is_array($docCfg) ? (float) ($docCfg['temperature'] ?? $this->textTemperature) : $this->textTemperature;
        $this->documentMaxTokens = \is_array($docCfg) ? (int) ($docCfg['max_tokens'] ?? ($docCfg['max_output_tokens'] ?? $this->textMaxTokens)) : $this->textMaxTokens;
    }

    /**
     * @param array<int, array{role:string,content:string}> $messages
     * @param array<string,mixed>                           $options
     */
    public function chat(array $messages, array $options = []): string
    {
        $userId = $this->getUserId();
        if (!$userId) {
            error_log('[AI]['.$this->getProviderLabel().'][chat] User not authenticated.');

            return 'Error: User is not authenticated.';
        }

        $normalized = $this->normalizeChatMessages($messages);
        if (empty($normalized)) {
            error_log('[AI]['.$this->getProviderLabel().'][chat] Empty messages payload.');

            return 'Error: Empty chat messages.';
        }

        $resolved = $this->resolveTextOptions($options);
        $payload = $this->buildMessagesPayload(
            $normalized,
            $resolved['model'],
            $resolved['temperature'],
            $resolved['max_tokens'],
            $options
        );

        try {
            $response = $this->httpClient->request('POST', $resolved['url'], [
                'headers' => $this->buildHeaders(),
                'json' => $payload,
            ]);

            $statusCode = $response->getStatusCode();
            $data = $response->toArray(false);

            if (200 !== $statusCode) {
                $message = $this->extractErrorMessage($data) ?? 'Invalid response from '.$this->getProviderLabel().'.';
                error_log('[AI]['.$this->getProviderLabel().'][chat] Invalid response (status='.$statusCode.'): '.$message);

                return 'Error: '.$message;
            }

            $errorMessage = $this->extractErrorMessage($data);
            if (null !== $errorMessage) {
                error_log('[AI]['.$this->getProviderLabel().'][chat] Error response: '.$errorMessage);

                return 'Error: '.$errorMessage;
            }

            $generated = $this->extractTextContent($data);
            if ('' === trim($generated)) {
                error_log('[AI]['.$this->getProviderLabel().'][chat] Empty content returned.');

                return 'Error: Empty response from '.$this->getProviderLabel().'.';
            }

            $usage = $this->extractUsage($data);
            $this->saveAiRequest(
                $userId,
                'chat',
                $this->messagesForLog($normalized, 900),
                $usage['prompt_tokens'],
                $usage['completion_tokens'],
                $usage['total_tokens']
            );

            return trim($generated);
        } catch (Exception $e) {
            error_log('[AI]['.$this->getProviderLabel().'][chat] Exception: '.$e->getMessage());

            return 'Error: '.$e->getMessage();
        }
    }

    /**
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

        return $this->requestClaude(
            $this->textApiUrl,
            $this->textModel,
            $this->textTemperature,
            $this->textMaxTokens,
            $prompt,
            'quiz'
        );
    }

    public function generateLearnPath(string $topic, int $chaptersCount, string $language, int $wordsCount, bool $addTests, int $numQuestions): ?array
    {
        $tocPrompt = \sprintf(
            'Generate a structured table of contents for a course in "%s" with %d chapters on "%s".
Return a numbered list, each chapter on a new line. No conclusion.',
            $language,
            $chaptersCount,
            $topic
        );

        $lpStructure = $this->requestClaude(
            $this->textApiUrl,
            $this->textModel,
            $this->textTemperature,
            $this->textMaxTokens,
            $tocPrompt,
            'learnpath'
        );

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

            $chapterContent = $this->requestClaude(
                $this->textApiUrl,
                $this->textModel,
                $this->textTemperature,
                $this->textMaxTokens,
                $chapterPrompt,
                'learnpath'
            );

            if (!$chapterContent) {
                continue;
            }

            $lpItems[] = [
                'title' => $chapterTitle,
                'content' => '<html><head><title>'.$chapterTitle.'</title></head><body>'.$chapterContent.'</body></html>',
            ];
        }

        $quizItems = [];
        if ($addTests) {
            foreach ($lpItems as $chapter) {
                $chapterTitle = (string) ($chapter['title'] ?? '');
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
                    $chapterTitle
                );

                $quizContent = $this->requestClaude(
                    $this->textApiUrl,
                    $this->textModel,
                    $this->textTemperature,
                    $this->textMaxTokens,
                    $quizPrompt,
                    'learnpath'
                );

                if (!$quizContent) {
                    continue;
                }

                $validQuestions = $this->filterValidAikenQuestions($quizContent);
                if (!empty($validQuestions)) {
                    $quizItems[] = [
                        'title' => 'Quiz: '.$chapterTitle,
                        'content' => implode("\n\n", $validQuestions),
                    ];
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
        return $this->requestClaude(
            $this->textApiUrl,
            $this->textModel,
            $this->textTemperature,
            $this->textMaxTokens,
            $prompt,
            $toolName
        );
    }

    public function generateDocument(string $prompt, string $toolName, ?array $options = []): ?string
    {
        $format = isset($options['format']) ? (string) $options['format'] : '';
        if ('' !== $format) {
            $prompt .= "\n\nOutput format: ".$format.'.';
        }

        return $this->requestClaude(
            $this->documentApiUrl,
            $this->documentModel,
            $this->documentTemperature,
            $this->documentMaxTokens,
            $prompt,
            $toolName
        );
    }

    protected function getProviderKey(): string
    {
        return 'claude';
    }

    protected function getProviderLabel(): string
    {
        return 'Claude';
    }

    private function requestClaude(string $url, string $model, float $temperature, int $maxTokens, string $prompt, string $toolName): ?string
    {
        $userId = $this->getUserId();
        if (!$userId) {
            throw new RuntimeException('User not authenticated.');
        }

        $messages = [
            ['role' => 'system', 'content' => 'You are a helpful AI assistant that generates structured educational content.'],
            ['role' => 'user', 'content' => trim($prompt)],
        ];

        $payload = $this->buildMessagesPayload(
            $messages,
            $model,
            $temperature,
            $maxTokens,
            []
        );

        try {
            $response = $this->httpClient->request('POST', $url, [
                'headers' => $this->buildHeaders(),
                'json' => $payload,
            ]);

            $statusCode = $response->getStatusCode();
            $data = $response->toArray(false);

            if (200 !== $statusCode) {
                $message = $this->extractErrorMessage($data) ?? 'Invalid response from '.$this->getProviderLabel().'.';
                error_log('[AI]['.$this->getProviderLabel().'] Invalid response (status='.$statusCode.'): '.$message);

                return null;
            }

            $errorMessage = $this->extractErrorMessage($data);
            if (null !== $errorMessage) {
                error_log('[AI]['.$this->getProviderLabel().'] Error response: '.$errorMessage);

                return null;
            }

            $generatedContent = $this->extractTextContent($data);
            if ('' === trim($generatedContent)) {
                error_log('[AI]['.$this->getProviderLabel().'] Empty content returned.');

                return null;
            }

            $usage = $this->extractUsage($data);
            $this->saveAiRequest(
                $userId,
                $toolName,
                mb_substr($prompt, 0, 900),
                $usage['prompt_tokens'],
                $usage['completion_tokens'],
                $usage['total_tokens']
            );

            return trim($generatedContent);
        } catch (Exception $e) {
            error_log('[AI]['.$this->getProviderLabel().'] Exception: '.$e->getMessage());

            return null;
        }
    }

    /**
     * @param array<int, array{role:string,content:string}> $messages
     * @param array<string,mixed>                           $options
     *
     * @return array<string,mixed>
     */
    private function buildMessagesPayload(array $messages, string $model, float $temperature, int $maxTokens, array $options): array
    {
        $converted = $this->convertMessagesForAnthropic($messages);
        $resolvedMaxTokens = $maxTokens > 0 ? $maxTokens : 1000;

        $payload = [
            'model' => $model,
            'messages' => $converted['messages'],
            'max_tokens' => $resolvedMaxTokens,
            'temperature' => $temperature,
        ];

        if ('' !== $converted['system']) {
            $payload['system'] = $converted['system'];
        }

        if (isset($options['top_p'])) {
            $payload['top_p'] = (float) $options['top_p'];
        }

        if (isset($options['top_k'])) {
            $payload['top_k'] = (int) $options['top_k'];
        }

        if (isset($options['stop_sequences']) && \is_array($options['stop_sequences'])) {
            $payload['stop_sequences'] = array_values(array_filter(
                array_map(static fn (mixed $value): string => trim((string) $value), $options['stop_sequences']),
                static fn (string $value): bool => '' !== $value
            ));
        }

        return $payload;
    }

    /**
     * @param array<int, array{role:string,content:string}> $messages
     *
     * @return array{system:string,messages:array<int, array{role:string,content:string}>}
     */
    private function convertMessagesForAnthropic(array $messages): array
    {
        $systemParts = [];
        $converted = [];

        foreach ($messages as $message) {
            $role = strtolower(trim((string) ($message['role'] ?? 'user')));
            $content = trim((string) ($message['content'] ?? ''));

            if ('' === $content) {
                continue;
            }

            if ('system' === $role) {
                $systemParts[] = $content;

                continue;
            }

            if ('assistant' !== $role) {
                $role = 'user';
            }

            $lastIndex = \count($converted) - 1;
            if ($lastIndex >= 0 && $role === $converted[$lastIndex]['role']) {
                $converted[$lastIndex]['content'] .= "\n\n".$content;

                continue;
            }

            $converted[] = [
                'role' => $role,
                'content' => $content,
            ];
        }

        if (empty($converted)) {
            $converted[] = [
                'role' => 'user',
                'content' => 'Continue.',
            ];
        }

        return [
            'system' => implode("\n\n", $systemParts),
            'messages' => $converted,
        ];
    }

    /**
     * @param array<int, array{role:string,content:string}> $messages
     *
     * @return array<int, array{role:string,content:string}>
     */
    private function normalizeChatMessages(array $messages): array
    {
        $out = [];

        foreach ($messages as $message) {
            if (!\is_array($message)) {
                continue;
            }

            $role = isset($message['role']) ? trim((string) $message['role']) : '';
            $content = isset($message['content']) ? trim((string) $message['content']) : '';

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

        foreach ($messages as $message) {
            $role = $message['role'] ?? 'user';
            $content = trim((string) ($message['content'] ?? ''));

            if ('' === $content) {
                continue;
            }

            $parts[] = strtoupper((string) $role).': '.mb_substr($content, 0, 300);
        }

        return mb_substr(implode(' | ', $parts), 0, $maxChars);
    }

    /**
     * @param array<string,mixed> $options
     *
     * @return array{url:string,model:string,temperature:float,max_tokens:int}
     */
    private function resolveTextOptions(array $options): array
    {
        $url = (string) (($options['url'] ?? null) ?? $this->textApiUrl);
        $model = (string) (($options['model'] ?? null) ?? $this->textModel);
        $temperature = (float) (($options['temperature'] ?? null) ?? $this->textTemperature);

        $maxTokens = $options['max_tokens'] ?? ($options['max_output_tokens'] ?? null);
        $maxTokens = (int) (($maxTokens ?? null) ?? $this->textMaxTokens);

        if ($maxTokens <= 0) {
            $maxTokens = $this->textMaxTokens > 0 ? $this->textMaxTokens : 1000;
        }

        return [
            'url' => $url,
            'model' => $model,
            'temperature' => $temperature,
            'max_tokens' => $maxTokens,
        ];
    }

    /**
     * @return array<string,string>
     */
    private function buildHeaders(): array
    {
        $headers = [
            'Content-Type' => 'application/json',
            'x-api-key' => $this->apiKey,
            'anthropic-version' => $this->anthropicVersion,
        ];

        if ('' !== trim($this->anthropicBeta)) {
            $headers['anthropic-beta'] = $this->anthropicBeta;
        }

        return $headers;
    }

    /**
     * @param array<string,mixed> $data
     */
    private function extractTextContent(array $data): string
    {
        $contentBlocks = $data['content'] ?? null;
        if (!\is_array($contentBlocks)) {
            return '';
        }

        $parts = [];
        foreach ($contentBlocks as $block) {
            if (!\is_array($block)) {
                continue;
            }

            $text = $block['text'] ?? null;
            if (\is_string($text) && '' !== trim($text)) {
                $parts[] = $text;
            }
        }

        return implode("\n", $parts);
    }

    /**
     * @param array<string,mixed> $data
     */
    private function extractErrorMessage(array $data): ?string
    {
        $error = $data['error'] ?? null;
        if (!\is_array($error)) {
            return null;
        }

        $message = $error['message'] ?? null;
        if (!\is_string($message) || '' === trim($message)) {
            return 'The provider returned an error response.';
        }

        return trim($message);
    }

    /**
     * @param array<string,mixed> $data
     *
     * @return array{prompt_tokens:int,completion_tokens:int,total_tokens:int}
     */
    private function extractUsage(array $data): array
    {
        $usage = $data['usage'] ?? [];
        if (!\is_array($usage)) {
            $usage = [];
        }

        $promptTokens = (int) ($usage['input_tokens'] ?? 0);
        $completionTokens = (int) ($usage['output_tokens'] ?? 0);

        return [
            'prompt_tokens' => $promptTokens,
            'completion_tokens' => $completionTokens,
            'total_tokens' => $promptTokens + $completionTokens,
        ];
    }

    private function saveAiRequest(int $userId, string $toolName, string $requestText, int $promptTokens, int $completionTokens, int $totalTokens): void
    {
        $aiRequest = new AiRequests();
        $aiRequest
            ->setUserId($userId)
            ->setToolName($toolName)
            ->setRequestText($requestText)
            ->setPromptTokens($promptTokens)
            ->setCompletionTokens($completionTokens)
            ->setTotalTokens($totalTokens)
            ->setAiProvider($this->getProviderKey())
        ;

        $this->aiRequestsRepository->save($aiRequest);
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
            $validOptions = array_filter($options, static fn (string $line): bool => (bool) preg_match('/^[A-D]\. .+/', $line));

            $answerLine = (string) end($lines);
            if (4 === \count($validOptions) && preg_match('/^ANSWER: [A-D]$/', $answerLine)) {
                $validQuestions[] = implode("\n", $lines);
            }
        }

        return $validQuestions;
    }

    private function getUserId(): ?int
    {
        $user = $this->security->getUser();

        return $user instanceof UserInterface ? $user->getId() : null;
    }

    /**
     * @return array<string,mixed>
     */
    private function readProvidersConfig(): array
    {
        $configJson = $this->settingsManager->getSetting('ai_helpers.ai_providers', true);

        if (\is_string($configJson)) {
            return json_decode($configJson, true) ?? [];
        }

        if (\is_array($configJson)) {
            return $configJson;
        }

        return [];
    }
}
