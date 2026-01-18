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

final class GrokProvider implements AiProviderInterface, AiImageProviderInterface, AiDocumentProviderInterface
{
    private string $apiKey;

    // Text
    private string $textApiUrl;
    private string $textModel;
    private float $textTemperature;
    private int $textMaxTokens;

    // Image
    private string $imageApiUrl;
    private string $imageModel;
    private array $imageDefaultOptions = [];

    // Document (usually same as text, but configurable)
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

        if (!isset($config['grok']) || !\is_array($config['grok'])) {
            throw new RuntimeException('Grok configuration is missing.');
        }

        $providerConfig = $config['grok'];

        $this->apiKey = (string) ($providerConfig['api_key'] ?? '');
        if ('' === $this->apiKey) {
            throw new RuntimeException('Grok API key is missing.');
        }

        // TEXT config (required for AiProviderInterface usage)
        $textCfg = $providerConfig['text'] ?? null;
        if (!\is_array($textCfg)) {
            throw new RuntimeException('Grok configuration for text processing is missing.');
        }

        $this->textApiUrl = (string) ($textCfg['url'] ?? 'https://api.x.ai/v1/chat/completions');
        $this->textModel = (string) ($textCfg['model'] ?? 'grok-beta');
        $this->textTemperature = (float) ($textCfg['temperature'] ?? 0.7);
        $this->textMaxTokens = (int) ($textCfg['max_tokens'] ?? 1000);

        // IMAGE config (optional but required to support "image" type)
        $imageCfg = $providerConfig['image'] ?? null;
        if (\is_array($imageCfg)) {
            $this->imageApiUrl = (string) ($imageCfg['url'] ?? 'https://api.x.ai/v1/images/generations');
            $this->imageModel = (string) ($imageCfg['model'] ?? 'grok-2-image');

            // Default options; can be overridden per-call via $options
            $this->imageDefaultOptions = [
                'response_format' => (string) ($imageCfg['response_format'] ?? 'b64_json'),
                'n' => (int) ($imageCfg['n'] ?? 1),
            ];
        } else {
            $this->imageApiUrl = '';
            $this->imageModel = '';
            $this->imageDefaultOptions = [];
        }

        // DOCUMENT config (optional; fallback to text if absent)
        $docCfg = $providerConfig['document'] ?? null;

        $this->documentApiUrl = \is_array($docCfg) ? (string) ($docCfg['url'] ?? $this->textApiUrl) : $this->textApiUrl;
        $this->documentModel = \is_array($docCfg) ? (string) ($docCfg['model'] ?? $this->textModel) : $this->textModel;
        $this->documentTemperature = \is_array($docCfg) ? (float) ($docCfg['temperature'] ?? $this->textTemperature) : $this->textTemperature;
        $this->documentMaxTokens = \is_array($docCfg) ? (int) ($docCfg['max_tokens'] ?? $this->textMaxTokens) : $this->textMaxTokens;
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

        return $this->requestText($this->textApiUrl, $this->textModel, $this->textTemperature, $this->textMaxTokens, $prompt, 'quiz');
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

        $lpStructure = $this->requestText($this->textApiUrl, $this->textModel, $this->textTemperature, $this->textMaxTokens, $tocPrompt, 'learnpath');
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

            $chapterContent = $this->requestText($this->textApiUrl, $this->textModel, $this->textTemperature, $this->textMaxTokens, $chapterPrompt, 'learnpath');
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

                $quizContent = $this->requestText($this->textApiUrl, $this->textModel, $this->textTemperature, $this->textMaxTokens, $quizPrompt, 'learnpath');
                if (!$quizContent) {
                    continue;
                }

                $validQuestions = $this->filterValidAikenQuestions($quizContent);
                if (!empty($validQuestions)) {
                    $quizItems[] = [
                        'title' => 'Quiz: '.$chapter['title'],
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
        return $this->requestText($this->textApiUrl, $this->textModel, $this->textTemperature, $this->textMaxTokens, $prompt, $toolName);
    }

    public function generateImage(string $prompt, string $toolName, ?array $options = []): array|string|null
    {
        if ('' === $this->imageApiUrl || '' === $this->imageModel) {
            error_log('[AI][Grok][Image] Image is not configured for this provider.');

            return null;
        }

        return $this->requestImage($prompt, $toolName, $options ?? []);
    }

    public function generateDocument(string $prompt, string $toolName, ?array $options = []): ?string
    {
        // Document generation is treated as structured text generation (Markdown/HTML/etc).
        // The caller can decide how to convert it to a file (PDF, DOCX...) later.
        $format = isset($options['format']) ? (string) $options['format'] : '';
        if ('' !== $format) {
            $prompt .= "\n\nOutput format: {$format}.";
        }

        return $this->requestText(
            $this->documentApiUrl,
            $this->documentModel,
            $this->documentTemperature,
            $this->documentMaxTokens,
            $prompt,
            $toolName
        );
    }

    private function filterValidAikenQuestions(string $quizContent): array
    {
        $questions = preg_split('/\n{2,}/', trim($quizContent)) ?: [];

        $valid = [];
        foreach ($questions as $block) {
            $lines = explode("\n", trim($block));
            if (\count($lines) < 6) {
                continue;
            }

            $options = \array_slice($lines, 1, 4);
            $validOptions = array_filter($options, static fn ($line) => (bool) preg_match('/^[A-D]\. .+/', $line));

            $answerLine = (string) end($lines);
            if (4 === \count($validOptions) && preg_match('/^ANSWER: [A-D]$/', $answerLine)) {
                $valid[] = implode("\n", $lines);
            }
        }

        return $valid;
    }

    private function requestText(string $url, string $model, float $temperature, int $maxTokens, string $prompt, string $toolName): ?string
    {
        $userId = $this->getUserId();
        if (!$userId) {
            throw new RuntimeException('User not authenticated.');
        }

        $system = 'You are a helpful AI assistant that generates structured educational content.';
        $payload = $this->buildTextPayload($url, $model, $system, $prompt, $temperature, $maxTokens);

        try {
            $response = $this->httpClient->request('POST', $url, [
                'headers' => [
                    'Authorization' => 'Bearer '.$this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
            ]);

            $status = $response->getStatusCode();
            $rawBody = $response->getContent(false);
            $data = json_decode($rawBody, true);

            if (200 !== $status || !\is_array($data)) {
                error_log('[AI][Grok][Text] Invalid response (status='.$status.').');

                return null;
            }

            $generated = $this->extractTextContent($data);
            if (null === $generated || '' === trim($generated)) {
                error_log('[AI][Grok][Text] Empty content returned by API.');

                return null;
            }

            $usage = $this->extractUsage($data);

            $this->logRequest($userId, $toolName, $prompt, $usage['prompt_tokens'], $usage['completion_tokens'], $usage['total_tokens'], 'grok');

            return $generated;
        } catch (Exception $e) {
            error_log('[AI][Grok][Text] Exception: '.$e->getMessage());

            return null;
        }
    }

    private function requestImage(string $prompt, string $toolName, array $options = []): array|string|null
    {
        $userId = $this->getUserId();
        if (!$userId) {
            throw new RuntimeException('User not authenticated.');
        }

        $merged = array_merge($this->imageDefaultOptions, $options);

        // Normalize response_format values
        if (isset($merged['response_format'])) {
            $rf = (string) $merged['response_format'];
            if ('base64' === $rf) {
                $merged['response_format'] = 'b64_json';
            }
        }

        $payload = array_merge([
            'model' => $this->imageModel,
            'prompt' => $prompt,
        ], $merged);

        try {
            $response = $this->httpClient->request('POST', $this->imageApiUrl, [
                'headers' => [
                    'Authorization' => 'Bearer '.$this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
            ]);

            $status = $response->getStatusCode();
            $rawBody = $response->getContent(false);
            $data = json_decode($rawBody, true);

            if (200 !== $status || !\is_array($data)) {
                error_log('[AI][Grok][Image] Invalid response (status='.$status.').');

                return null;
            }

            // Log request (images usually have no usage)
            $this->logRequest($userId, $toolName, $prompt, 0, 0, 0, 'grok');

            // Prefer base64
            if (isset($data['data'][0]['b64_json'])) {
                return [
                    'content' => (string) $data['data'][0]['b64_json'],
                    'is_base64' => true,
                    'content_type' => 'image/png',
                    'revised_prompt' => $data['data'][0]['revised_prompt'] ?? null,
                ];
            }

            // URL fallback
            if (isset($data['data'][0]['url'])) {
                return [
                    'url' => (string) $data['data'][0]['url'],
                    'is_base64' => false,
                    'content_type' => 'image/png',
                    'revised_prompt' => $data['data'][0]['revised_prompt'] ?? null,
                ];
            }

            error_log('[AI][Grok][Image] No usable image content found in response.');

            return null;
        } catch (Exception $e) {
            error_log('[AI][Grok][Image] Exception: '.$e->getMessage());

            return null;
        }
    }

    private function buildTextPayload(string $url, string $model, string $system, string $prompt, float $temperature, int $maxTokens): array
    {
        // If using /responses endpoint
        if ($this->isResponsesEndpoint($url)) {
            return [
                'model' => $model,
                'input' => [
                    ['role' => 'system', 'content' => $system],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => $temperature,
                'max_output_tokens' => $maxTokens,
            ];
        }

        // OpenAI-compatible /chat/completions (xAI supports legacy endpoint)
        return [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $prompt],
            ],
            'temperature' => $temperature,
            'max_tokens' => $maxTokens,
        ];
    }

    private function extractTextContent(array $data): ?string
    {
        // /responses style
        if (isset($data['output'][0]['content'][0]['text']) && \is_string($data['output'][0]['content'][0]['text'])) {
            return $data['output'][0]['content'][0]['text'];
        }

        // Sometimes APIs return output_text
        if (isset($data['output_text']) && \is_string($data['output_text'])) {
            return $data['output_text'];
        }

        // /chat/completions style
        if (isset($data['choices'][0]['message']['content']) && \is_string($data['choices'][0]['message']['content'])) {
            return $data['choices'][0]['message']['content'];
        }

        // Some providers may return choices[0].text
        if (isset($data['choices'][0]['text']) && \is_string($data['choices'][0]['text'])) {
            return $data['choices'][0]['text'];
        }

        return null;
    }

    private function extractUsage(array $data): array
    {
        // Default usage
        $usage = [
            'prompt_tokens' => 0,
            'completion_tokens' => 0,
            'total_tokens' => 0,
        ];

        if (isset($data['usage']) && \is_array($data['usage'])) {
            $u = $data['usage'];

            // Common OpenAI-compatible keys
            $usage['prompt_tokens'] = (int) ($u['prompt_tokens'] ?? $usage['prompt_tokens']);
            $usage['completion_tokens'] = (int) ($u['completion_tokens'] ?? $usage['completion_tokens']);
            $usage['total_tokens'] = (int) ($u['total_tokens'] ?? $usage['total_tokens']);

            // Some responses APIs use input_tokens/output_tokens
            if (isset($u['input_tokens'])) {
                $usage['prompt_tokens'] = (int) $u['input_tokens'];
            }
            if (isset($u['output_tokens'])) {
                $usage['completion_tokens'] = (int) $u['output_tokens'];
            }
            if (0 === $usage['total_tokens']) {
                $usage['total_tokens'] = $usage['prompt_tokens'] + $usage['completion_tokens'];
            }
        }

        return $usage;
    }

    private function logRequest(int $userId, string $toolName, string $prompt, int $promptTokens, int $completionTokens, int $totalTokens, string $provider): void
    {
        $aiRequest = new AiRequests();
        $aiRequest
            ->setUserId($userId)
            ->setToolName($toolName)
            ->setRequestText($prompt)
            ->setPromptTokens($promptTokens)
            ->setCompletionTokens($completionTokens)
            ->setTotalTokens($totalTokens)
            ->setAiProvider($provider)
        ;

        $this->aiRequestsRepository->save($aiRequest);
    }

    private function getUserId(): ?int
    {
        $user = $this->security->getUser();

        return $user instanceof UserInterface ? $user->getId() : null;
    }

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

    private function isResponsesEndpoint(string $url): bool
    {
        return str_contains($url, '/responses');
    }
}
