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

final class GrokProvider implements AiProviderInterface, AiImageProviderInterface, AiDocumentProviderInterface, AiVideoJobProviderInterface
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

    // Video (optional)
    private string $videoApiUrl = '';
    private string $videoModel = '';
    private array $videoDefaultOptions = [];

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

        // TEXT config (required)
        $textCfg = $providerConfig['text'] ?? null;
        if (!\is_array($textCfg)) {
            throw new RuntimeException('Grok configuration for text processing is missing.');
        }

        $this->textApiUrl = (string) ($textCfg['url'] ?? 'https://api.x.ai/v1/chat/completions');
        $this->textModel = (string) ($textCfg['model'] ?? 'grok-beta');
        $this->textTemperature = (float) ($textCfg['temperature'] ?? 0.7);
        $this->textMaxTokens = (int) ($textCfg['max_tokens'] ?? 1000);

        // IMAGE config (optional)
        $imageCfg = $providerConfig['image'] ?? null;
        if (\is_array($imageCfg)) {
            $this->imageApiUrl = (string) ($imageCfg['url'] ?? 'https://api.x.ai/v1/images/generations');
            $this->imageModel = (string) ($imageCfg['model'] ?? 'grok-imagine-image');

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

        // VIDEO config (optional)
        $videoCfg = $providerConfig['video'] ?? null;
        if (\is_array($videoCfg)) {
            $this->videoApiUrl = (string) ($videoCfg['url'] ?? 'https://api.x.ai/v1/videos/generations');
            $this->videoModel = (string) ($videoCfg['model'] ?? 'grok-video');

            $this->videoDefaultOptions = [
                'duration' => (int) ($videoCfg['duration'] ?? 8),
                'aspect_ratio' => (string) ($videoCfg['aspect_ratio'] ?? '16:9'),
                'resolution' => (string) ($videoCfg['resolution'] ?? '480p'),
            ];
        }
    }

    /**
     * Chat-style entrypoint.
     *
     * @param array<int, array{role:string,content:string}> $messages
     * @param array<string,mixed>                           $options
     */
    public function chat(array $messages, array $options = []): string
    {
        $userId = $this->getUserId();
        if (!$userId) {
            error_log('[AI][Grok][chat] User not authenticated.');

            return 'Error: User is not authenticated.';
        }

        $normalized = $this->normalizeChatMessages($messages);
        if (empty($normalized)) {
            error_log('[AI][Grok][chat] Empty messages payload.');

            return 'Error: Empty chat messages.';
        }

        $resolved = $this->resolveTextOptions($options);

        $payload = $this->buildChatPayload(
            url: $resolved['url'],
            model: $resolved['model'],
            messages: $normalized,
            temperature: $resolved['temperature'],
            maxTokens: $resolved['max_tokens'],
            options: $options
        );

        try {
            $response = $this->httpClient->request('POST', $resolved['url'], [
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
                error_log('[AI][Grok][chat] Invalid response (status='.$status.').');

                return 'Error: Invalid response from Grok.';
            }

            if (isset($data['error'])) {
                $msg = $data['error']['message'] ?? 'Grok returned an error response.';
                $msg = \is_string($msg) ? trim($msg) : 'Grok returned an error response.';
                error_log('[AI][Grok][chat] Error response: '.$msg);

                return 'Error: '.$msg;
            }

            $generated = $this->extractTextContent($data);
            if (null === $generated || '' === trim($generated)) {
                error_log('[AI][Grok][chat] Empty content returned.');

                return 'Error: Empty response from Grok.';
            }

            $usage = $this->extractUsage($data);

            $this->logRequest(
                $userId,
                'chat',
                $this->messagesForLog($normalized, 900),
                $usage['prompt_tokens'],
                $usage['completion_tokens'],
                $usage['total_tokens'],
                'grok'
            );

            return trim($generated);
        } catch (Exception $e) {
            error_log('[AI][Grok][chat] Exception: '.$e->getMessage());

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

        // Support xAI SDK style option as well.
        // Docs show `image_format="base64"` in SDK, and `response_format:"b64_json"` in raw HTTP examples.
        if (!isset($merged['response_format']) && isset($merged['image_format'])) {
            $imgFmt = (string) $merged['image_format'];
            if ('base64' === $imgFmt) {
                $merged['response_format'] = 'b64_json';
            }
        }

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

        $attempts = 3;
        $delayMs = 400;

        for ($i = 0; $i < $attempts; $i++) {
            try {
                $response = $this->httpClient->request('POST', $this->imageApiUrl, [
                    'headers' => [
                        'Authorization' => 'Bearer '.$this->apiKey,
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                        'User-Agent' => 'Chamilo/2 (Grok image)',
                    ],
                    'json' => $payload,
                    'timeout' => 30,
                    'max_duration' => 60,
                ]);

                $status = $response->getStatusCode();
                $rawBody = $response->getContent(false);
                $headers = $response->getHeaders(false);

                $data = json_decode($rawBody, true);

                if (200 !== $status || !\is_array($data)) {
                    if (\in_array($status, [429, 503, 504], true) && $i < $attempts - 1) {
                        usleep($delayMs * 1000);
                        $delayMs *= 2;

                        continue;
                    }

                    return null;
                }

                if (isset($data['error'])) {
                    $msg = $data['error']['message'] ?? 'Grok returned an error response.';
                    $msg = \is_string($msg) ? trim($msg) : 'Grok returned an error response.';
                    error_log('[AI][Grok][Image] Error response: '.$msg);

                    return null;
                }

                $this->logRequest($userId, $toolName, $prompt, 0, 0, 0, 'grok');

                // Prefer base64 when available
                if (isset($data['data'][0]['b64_json']) && \is_string($data['data'][0]['b64_json']) && '' !== trim($data['data'][0]['b64_json'])) {
                    return [
                        'content' => (string) $data['data'][0]['b64_json'],
                        'is_base64' => true,
                        // xAI commonly returns JPEG URLs; base64 response is the image bytes as well.
                        'content_type' => 'image/jpeg',
                        'revised_prompt' => $data['data'][0]['revised_prompt'] ?? null,
                    ];
                }

                // If API returns URL, download it here and return base64 to keep Chamilo pipeline stable.
                if (isset($data['data'][0]['url']) && \is_string($data['data'][0]['url']) && '' !== trim($data['data'][0]['url'])) {
                    $url = (string) $data['data'][0]['url'];

                    $download = $this->downloadImageWithRetry($url);
                    if (null === $download) {
                        error_log('[AI][Grok][Image] Image URL download failed. url='.$url);

                        return null;
                    }

                    return [
                        'content' => base64_encode($download['bytes']),
                        'is_base64' => true,
                        'content_type' => $download['content_type'] ?: 'image/jpeg',
                        'revised_prompt' => $data['data'][0]['revised_prompt'] ?? null,
                    ];
                }

                return null;
            } catch (Exception $e) {
                error_log('[AI][Grok][Image] Exception: '.$e->getMessage());

                // Retry on transient network errors as well
                if ($i < $attempts - 1) {
                    usleep($delayMs * 1000);
                    $delayMs *= 2;

                    continue;
                }

                return null;
            }
        }

        return null;
    }

    private function downloadImageWithRetry(string $url): ?array
    {
        $attempts = 3;
        $delayMs = 400;

        for ($i = 0; $i < $attempts; $i++) {
            try {
                $resp = $this->httpClient->request('GET', $url, [
                    'headers' => [
                        'Accept' => 'image/*',
                        'User-Agent' => 'Chamilo/2 (Grok image download)',
                    ],
                    'timeout' => 30,
                    'max_duration' => 60,
                ]);

                $status = $resp->getStatusCode();
                $bytes = $resp->getContent(false);
                $headers = $resp->getHeaders(false);

                if ($status >= 200 && $status < 300 && '' !== $bytes) {
                    return [
                        'bytes' => $bytes,
                        'content_type' => (string) ($headers['content-type'][0] ?? ''),
                    ];
                }

                if (\in_array($status, [429, 503, 504], true) && $i < $attempts - 1) {
                    usleep($delayMs * 1000);
                    $delayMs *= 2;

                    continue;
                }

                return null;
            } catch (Exception $e) {
                error_log('[AI][Grok][Image] Download exception: '.$e->getMessage());

                if ($i < $attempts - 1) {
                    usleep($delayMs * 1000);
                    $delayMs *= 2;

                    continue;
                }

                return null;
            }
        }

        return null;
    }

    private function preview(string $raw, int $max = 300): string
    {
        $s = trim($raw);
        if ('' === $s) {
            return '';
        }

        return mb_substr($s, 0, $max);
    }

    /**
     * Build payload for chat-style calls with arbitrary message history.
     *
     * @param array<int, array{role:string,content:string}> $messages
     * @param array<string,mixed>                           $options
     */
    private function buildChatPayload(
        string $url,
        string $model,
        array $messages,
        float $temperature,
        int $maxTokens,
        array $options
    ): array {
        // If using /responses endpoint
        if ($this->isResponsesEndpoint($url)) {
            $input = [];
            foreach ($messages as $m) {
                $input[] = [
                    'role' => $m['role'],
                    'content' => $m['content'],
                ];
            }

            return [
                'model' => $model,
                'input' => $input,
                'temperature' => $temperature,
                'max_output_tokens' => $maxTokens,
            ];
        }

        $payload = [
            'model' => $model,
            'messages' => $messages,
            'temperature' => $temperature,
            'max_tokens' => $maxTokens,
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

        return $payload;
    }

    private function buildTextPayload(string $url, string $model, string $system, string $prompt, float $temperature, int $maxTokens): array
    {
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
        if (isset($data['output'][0]['content'][0]['text']) && \is_string($data['output'][0]['content'][0]['text'])) {
            return $data['output'][0]['content'][0]['text'];
        }

        if (isset($data['output_text']) && \is_string($data['output_text'])) {
            return $data['output_text'];
        }

        if (isset($data['choices'][0]['message']['content']) && \is_string($data['choices'][0]['message']['content'])) {
            return $data['choices'][0]['message']['content'];
        }

        if (isset($data['choices'][0]['text']) && \is_string($data['choices'][0]['text'])) {
            return $data['choices'][0]['text'];
        }

        return null;
    }

    private function extractUsage(array $data): array
    {
        $usage = [
            'prompt_tokens' => 0,
            'completion_tokens' => 0,
            'total_tokens' => 0,
        ];

        if (isset($data['usage']) && \is_array($data['usage'])) {
            $u = $data['usage'];

            $usage['prompt_tokens'] = (int) ($u['prompt_tokens'] ?? $usage['prompt_tokens']);
            $usage['completion_tokens'] = (int) ($u['completion_tokens'] ?? $usage['completion_tokens']);
            $usage['total_tokens'] = (int) ($u['total_tokens'] ?? $usage['total_tokens']);

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

    public function generateVideo(string $prompt, string $toolName, ?array $options = []): array|string|null
    {
        if ('' === $this->videoApiUrl || '' === $this->videoModel) {
            error_log('[AI][Grok][Video] Video is not configured for this provider.');

            return null;
        }

        $userId = $this->getUserId();
        if (!$userId) {
            return 'Error: User is not authenticated.';
        }

        $prompt = trim($prompt);
        if ('' === $prompt) {
            return 'Error: Empty prompt.';
        }

        $toolName = trim($toolName);
        if ('' === $toolName) {
            $toolName = 'video';
        }

        $opts = \is_array($options) ? $options : [];
        $merged = array_merge($this->videoDefaultOptions, $opts);

        // Controller sends "seconds" and "size".
        if (isset($merged['seconds']) && !isset($merged['duration'])) {
            $merged['duration'] = (int) $merged['seconds'];
        }
        if (isset($merged['size'])) {
            $this->applySizeHintsToVideoOptions((string) $merged['size'], $merged);
        }

        $duration = (int) ($merged['duration'] ?? 8);
        if ($duration < 1) {
            $duration = 1;
        }
        if ($duration > 15) {
            $duration = 15;
        }

        $aspectRatio = (string) ($merged['aspect_ratio'] ?? '16:9');
        $resolution = (string) ($merged['resolution'] ?? '480p');

        $payload = [
            'model' => $this->videoModel,
            'prompt' => $prompt,
            'duration' => $duration,
            'aspect_ratio' => $aspectRatio,
            'resolution' => $resolution,
        ];

        try {
            $response = $this->httpClient->request('POST', $this->videoApiUrl, [
                'headers' => [
                    'Authorization' => 'Bearer '.$this->apiKey,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
                'json' => $payload,
                'timeout' => 30,
                'max_duration' => 60,
            ]);

            $status = $response->getStatusCode();
            $rawBody = (string) $response->getContent(false);
            $data = json_decode($rawBody, true);

            if ($status < 200 || $status >= 300 || !\is_array($data)) {
                error_log('[AI][Grok][Video] Invalid response (status='.$status.'). Raw: '.mb_substr($rawBody, 0, 800));

                return 'Error: Invalid response from Grok video API.';
            }

            if (isset($data['error'])) {
                $msg = $data['error']['message'] ?? 'Grok returned an error response.';
                $msg = \is_string($msg) ? trim($msg) : 'Grok returned an error response.';

                return 'Error: '.$msg;
            }

            $requestId = (string) ($data['request_id'] ?? '');
            $requestId = trim($requestId);

            if ('' === $requestId) {
                return 'Error: Missing request_id from Grok video API.';
            }

            $this->logRequest($userId, $toolName, $prompt, 0, 0, 0, 'grok');

            return [
                'id' => $requestId,
                'status' => 'pending',
                'is_base64' => false,
                'content' => null,
                'url' => null,
                'content_type' => 'video/mp4',
                'revised_prompt' => null,
            ];
        } catch (Exception $e) {
            error_log('[AI][Grok][Video] Exception: '.$e->getMessage());

            return 'Error: '.$e->getMessage();
        }
    }

    public function getVideoJobStatus(string $jobId): ?array
    {
        $jobId = trim($jobId);
        if ('' === $jobId) {
            return null;
        }

        $url = 'https://api.x.ai/v1/videos/'.rawurlencode($jobId);

        try {
            $resp = $this->httpClient->request('GET', $url, [
                'headers' => [
                    'Authorization' => 'Bearer '.$this->apiKey,
                    'Accept' => 'application/json',
                ],
                'timeout' => 20,
                'max_duration' => 40,
            ]);

            $statusCode = $resp->getStatusCode();
            $rawBody = (string) $resp->getContent(false);
            $data = json_decode($rawBody, true);

            if ($statusCode < 200 || $statusCode >= 300 || !\is_array($data)) {
                return [
                    'id' => $jobId,
                    'status' => 'error',
                    'error' => 'Failed to fetch Grok video status.',
                    'job' => $rawBody,
                ];
            }

            $status = (string) ($data['status'] ?? '');
            $status = trim($status);

            $err = null;
            if (isset($data['error'])) {
                $msg = $data['error']['message'] ?? 'Grok returned an error response.';
                $err = \is_string($msg) ? trim($msg) : 'Grok returned an error response.';
            }

            return [
                'id' => $jobId,
                'status' => '' !== $status ? $status : 'pending',
                'error' => $err,
                'job' => $data,
            ];
        } catch (Exception $e) {
            return [
                'id' => $jobId,
                'status' => 'error',
                'error' => $e->getMessage(),
                'job' => null,
            ];
        }
    }

    public function getVideoJobContentAsBase64(string $jobId, int $maxBytes = 15728640): ?array
    {
        $job = $this->getVideoJobStatus($jobId);
        if (!\is_array($job)) {
            return null;
        }

        $raw = $job['job'] ?? null;
        if (!\is_array($raw)) {
            return [
                'is_base64' => false,
                'content' => null,
                'url' => null,
                'content_type' => 'video/mp4',
                'error' => (string) ($job['error'] ?? 'Missing job payload.'),
            ];
        }

        $status = strtolower(trim((string) ($job['status'] ?? '')));
        if (!\in_array($status, ['done', 'completed', 'succeeded'], true)) {
            return [
                'is_base64' => false,
                'content' => null,
                'url' => null,
                'content_type' => 'video/mp4',
                'error' => null,
            ];
        }

        $videoUrl = (string) ($raw['video']['url'] ?? ($raw['url'] ?? ''));
        $videoUrl = trim($videoUrl);

        if ('' === $videoUrl) {
            return [
                'is_base64' => false,
                'content' => null,
                'url' => null,
                'content_type' => 'video/mp4',
                'error' => 'No video URL returned by Grok.',
            ];
        }

        $dl = $this->downloadBinaryWithLimit($videoUrl, $maxBytes, 'video/mp4');
        if (null === $dl) {
            return [
                'is_base64' => false,
                'content' => null,
                'url' => $videoUrl,
                'content_type' => 'video/mp4',
                'error' => 'Failed to download video bytes (or exceeded size limit).',
            ];
        }

        return [
            'is_base64' => true,
            'content' => base64_encode($dl['bytes']),
            'url' => null,
            'content_type' => $dl['content_type'],
            'error' => null,
        ];
    }

    /**
     * Map UI "size" (e.g. 720x1280) into aspect_ratio + resolution (best-effort).
     *
     * @param array<string,mixed> $opts
     */
    private function applySizeHintsToVideoOptions(string $size, array &$opts): void
    {
        $s = strtolower(trim($size));
        if (!str_contains($s, 'x')) {
            return;
        }

        $parts = explode('x', $s, 2);
        $w = (int) trim((string) ($parts[0] ?? ''));
        $h = (int) trim((string) ($parts[1] ?? ''));

        if ($w <= 0 || $h <= 0) {
            return;
        }

        if (!isset($opts['aspect_ratio'])) {
            $opts['aspect_ratio'] = ($w >= $h) ? '16:9' : '9:16';
        }

        if (!isset($opts['resolution'])) {
            $maxDim = max($w, $h);
            $opts['resolution'] = $maxDim >= 720 ? '720p' : '480p';
        }
    }

    /**
     * @return array{bytes:string,content_type:string}|null
     */
    private function downloadBinaryWithLimit(string $url, int $maxBytes, string $fallbackType): ?array
    {
        try {
            $resp = $this->httpClient->request('GET', $url, [
                'headers' => [
                    'Accept' => '*/*',
                    'User-Agent' => 'Chamilo/2 (Grok video download)',
                ],
                'timeout' => 30,
                'max_duration' => 120,
            ]);

            $headers = $resp->getHeaders(false);

            $len = $headers['content-length'][0] ?? null;
            if (null !== $len && is_numeric($len) && (int) $len > $maxBytes) {
                return null;
            }

            $bytes = (string) $resp->getContent(false);
            if ('' === $bytes || \strlen($bytes) > $maxBytes) {
                return null;
            }

            $ct = (string) ($headers['content-type'][0] ?? $fallbackType);
            $ct = '' !== trim($ct) ? trim($ct) : $fallbackType;

            return [
                'bytes' => $bytes,
                'content_type' => $ct,
            ];
        } catch (Exception) {
            return null;
        }
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
}
