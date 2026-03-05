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
use Throwable;

use const ENT_QUOTES;

final class GeminiProvider implements AiProviderInterface, AiImageProviderInterface, AiDocumentProviderInterface, AiVideoJobProviderInterface
{
    private array $providerConfig;
    private string $apiKey;

    // Text defaults
    private string $textModel;
    private string $textUrlTemplate;
    private float $textTemperature;
    private int $textMaxOutputTokens;

    // Document defaults
    private string $documentModel;
    private string $documentUrlTemplate;
    private float $documentTemperature;
    private int $documentMaxOutputTokens;

    // Image defaults (optional)
    private string $imageModel = '';
    private string $imageUrlTemplate = '';
    private string $imageRequestFormat = ''; // 'generateContent' or 'predict'

    /**
     * @var array<int, string>
     */
    private array $imageResponseModalities = ['IMAGE', 'TEXT'];
    private int $imageN = 1;

    // Video defaults (optional)
    private string $videoModel = '';
    private string $videoUrlTemplate = '';
    private string $videoStatusBaseUrl = 'https://generativelanguage.googleapis.com/v1beta';
    private string $videoDefaultAspectRatio = '16:9';
    private string $videoDefaultResolution = '720p';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly SettingsManager $settingsManager,
        private readonly AiRequestsRepository $aiRequestsRepository,
        private readonly Security $security
    ) {
        $config = $this->readProvidersConfig();

        if (!isset($config['gemini']) || !\is_array($config['gemini'])) {
            throw new RuntimeException('Gemini configuration is missing.');
        }

        $providerConfig = $config['gemini'];
        $this->providerConfig = $providerConfig;

        $this->apiKey = (string) ($providerConfig['api_key'] ?? '');
        if ('' === $this->apiKey) {
            throw new RuntimeException('Gemini API key is missing.');
        }

        $textCfg = $providerConfig['text'] ?? null;
        if (!\is_array($textCfg)) {
            throw new RuntimeException('Gemini configuration for text processing is missing.');
        }

        $this->textModel = (string) ($textCfg['model'] ?? 'gemini-2.5-flash');
        $this->textUrlTemplate = (string) ($textCfg['url'] ?? 'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent');
        $this->textTemperature = (float) ($textCfg['temperature'] ?? 0.7);
        $this->textMaxOutputTokens = (int) ($textCfg['max_output_tokens'] ?? 1000);

        $docCfg = $providerConfig['document'] ?? null;
        $this->documentModel = \is_array($docCfg) ? (string) ($docCfg['model'] ?? $this->textModel) : $this->textModel;
        $this->documentUrlTemplate = \is_array($docCfg) ? (string) ($docCfg['url'] ?? $this->textUrlTemplate) : $this->textUrlTemplate;
        $this->documentTemperature = \is_array($docCfg) ? (float) ($docCfg['temperature'] ?? $this->textTemperature) : $this->textTemperature;
        $this->documentMaxOutputTokens = \is_array($docCfg) ? (int) ($docCfg['max_output_tokens'] ?? $this->textMaxOutputTokens) : $this->textMaxOutputTokens;

        // Image config is optional: do not fail if missing.
        $imgCfg = $providerConfig['image'] ?? null;
        if (\is_array($imgCfg)) {
            $this->imageModel = (string) ($imgCfg['model'] ?? '');
            $this->imageUrlTemplate = (string) ($imgCfg['url'] ?? '');
            $this->imageRequestFormat = (string) ($imgCfg['request_format'] ?? '');
            $this->imageN = (int) ($imgCfg['n'] ?? 1);
            if ($this->imageN <= 0) {
                $this->imageN = 1;
            }

            $mods = $imgCfg['response_modalities'] ?? null;
            if (\is_array($mods) && !empty($mods)) {
                $clean = [];
                foreach ($mods as $m) {
                    $s = strtoupper(trim((string) $m));
                    if ('' !== $s) {
                        $clean[] = $s;
                    }
                }
                if (!empty($clean)) {
                    $this->imageResponseModalities = array_values(array_unique($clean));
                }
            }
        }

        // Video config is optional: do not fail if missing.
        $videoCfg = $providerConfig['video'] ?? null;
        if (\is_array($videoCfg)) {
            $this->videoModel = (string) ($videoCfg['model'] ?? '');
            $this->videoUrlTemplate = (string) ($videoCfg['url'] ?? '');
            $this->videoStatusBaseUrl = (string) ($videoCfg['status_base_url'] ?? $this->videoStatusBaseUrl);

            $ar = (string) ($videoCfg['aspect_ratio'] ?? $this->videoDefaultAspectRatio);
            $res = (string) ($videoCfg['resolution'] ?? $this->videoDefaultResolution);

            if ('' !== trim($ar)) {
                $this->videoDefaultAspectRatio = $ar;
            }
            if ('' !== trim($res)) {
                $this->videoDefaultResolution = $res;
            }
        }
    }

    /**
     * Chat-style entrypoint. Gemini is not OpenAI-chat compatible, so we convert the message list into a single prompt.
     *
     * @param array<int, array{role:string,content:string}> $messages
     * @param array<string,mixed>                           $options
     */
    public function chat(array $messages, array $options = []): string
    {
        $prompt = $this->messagesToPrompt($messages);
        if ('' === trim($prompt)) {
            return 'Error: Empty chat messages.';
        }

        return $this->generateText($prompt, $options);
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

        $userId = $this->getUserId();
        if (!$userId) {
            error_log('[AI][Gemini][generateText] User not authenticated.');

            return 'Error: User is not authenticated.';
        }

        $resolved = $this->resolveTextOptions($options);

        $result = $this->requestGeminiText(
            url: $resolved['url'],
            temperature: $resolved['temperature'],
            maxOutputTokens: $resolved['max_output_tokens'],
            prompt: $prompt,
            toolName: 'generateText'
        );

        if (null === $result || '' === trim($result)) {
            return 'Error: Empty response from Gemini.';
        }

        return trim($result);
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

        return $this->requestGeminiText(
            $this->buildUrl($this->textUrlTemplate, $this->textModel),
            $this->textTemperature,
            $this->textMaxOutputTokens,
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

        $lpStructure = $this->requestGeminiText(
            $this->buildUrl($this->textUrlTemplate, $this->textModel),
            $this->textTemperature,
            $this->textMaxOutputTokens,
            $tocPrompt,
            'learnpath'
        );

        if (!$lpStructure) {
            return ['success' => false, 'message' => 'Failed to generate course structure.'];
        }

        $lpItems = [];
        $chapters = explode("\n", trim((string) $lpStructure));
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

            $chapterContent = $this->requestGeminiText(
                $this->buildUrl($this->textUrlTemplate, $this->textModel),
                $this->textTemperature,
                $this->textMaxOutputTokens,
                $chapterPrompt,
                'learnpath'
            );

            if (!$chapterContent) {
                continue;
            }

            $safeTitle = htmlspecialchars($chapterTitle, ENT_QUOTES, 'UTF-8');
            $lpItems[] = [
                'title' => $chapterTitle,
                'content' => "<html><head><title>{$safeTitle}</title></head><body>{$chapterContent}</body></html>",
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
                    (string) ($chapter['title'] ?? '')
                );

                $quizContent = $this->requestGeminiText(
                    $this->buildUrl($this->textUrlTemplate, $this->textModel),
                    $this->textTemperature,
                    $this->textMaxOutputTokens,
                    $quizPrompt,
                    'learnpath'
                );

                if (!$quizContent) {
                    continue;
                }

                $valid = $this->filterValidAikenQuestions($quizContent);
                if (!empty($valid)) {
                    $quizItems[] = [
                        'title' => 'Quiz: '.$chapter['title'],
                        'content' => implode("\n\n", $valid),
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
        return $this->requestGeminiText(
            $this->buildUrl($this->textUrlTemplate, $this->textModel),
            $this->textTemperature,
            $this->textMaxOutputTokens,
            $prompt,
            $toolName
        );
    }

    public function generateDocument(string $prompt, string $toolName, ?array $options = []): ?string
    {
        $format = isset($options['format']) ? (string) $options['format'] : '';
        if ('' !== $format) {
            $prompt .= "\n\nOutput format: {$format}.";
        }

        return $this->requestGeminiText(
            $this->buildUrl($this->documentUrlTemplate, $this->documentModel),
            $this->documentTemperature,
            $this->documentMaxOutputTokens,
            $prompt,
            $toolName
        );
    }

    /**
     * Generate an image.
     *
     * @param ?array $options Provider-specific options
     */
    public function generateImage(string $prompt, string $toolName, ?array $options = []): array|string|null
    {
        $prompt = trim($prompt);
        $toolName = trim($toolName);

        if ('' === $toolName) {
            $toolName = 'image';
        }

        if ('' === $prompt) {
            return [
                'is_base64' => false,
                'content' => null,
                'url' => null,
                'content_type' => 'image/png',
                'revised_prompt' => null,
                'error' => 'Empty prompt.',
            ];
        }

        $opts = \is_array($options) ? $options : [];

        $resolved = $this->resolveImageOptions($opts);

        if ('' === $resolved['url'] || '' === $resolved['model']) {
            return [
                'is_base64' => false,
                'content' => null,
                'url' => null,
                'content_type' => 'image/png',
                'revised_prompt' => null,
                'error' => 'Gemini image configuration is missing.',
            ];
        }

        $format = $resolved['request_format'];
        if ('' === $format) {
            $format = str_starts_with($resolved['model'], 'imagen-') ? 'predict' : 'generateContent';
        }

        return 'predict' === $format
            ? $this->requestImagenPredict($resolved['url'], $prompt, $resolved['n'], $toolName)
            : $this->requestGeminiGenerateContentImage(
                $resolved['url'],
                $prompt,
                $resolved['n'],
                $resolved['response_modalities'],
                $toolName
            );
    }

    private function requestGeminiText(string $url, float $temperature, int $maxOutputTokens, string $prompt, string $toolName): ?string
    {
        $userId = $this->getUserId();
        if (!$userId) {
            throw new RuntimeException('User not authenticated.');
        }

        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt],
                    ],
                ],
            ],
            'generationConfig' => [
                'temperature' => $temperature,
                'maxOutputTokens' => $maxOutputTokens,
            ],
        ];

        try {
            $data = $this->postJson($url, $payload);
            if (empty($data)) {
                return null;
            }

            $text = $this->extractFirstTextFromGenerateContent($data);
            if (null === $text || '' === trim($text)) {
                return null;
            }

            $usageMeta = \is_array($data['usageMetadata'] ?? null) ? $data['usageMetadata'] : [];
            $promptTokens = (int) ($usageMeta['promptTokenCount'] ?? 0);
            $completionTokens = (int) ($usageMeta['candidatesTokenCount'] ?? 0);
            $totalTokens = (int) ($usageMeta['totalTokenCount'] ?? ($promptTokens + $completionTokens));

            $this->saveAiRequest(
                userId: $userId,
                toolName: $toolName,
                requestText: $prompt,
                promptTokens: $promptTokens,
                completionTokens: $completionTokens,
                totalTokens: $totalTokens
            );

            return trim($text);
        } catch (Exception $e) {
            error_log('[AI][Gemini] Exception: '.$e->getMessage());

            return null;
        }
    }

    private function requestGeminiGenerateContentImage(
        string $url,
        string $prompt,
        int $n,
        array $modalities,
        string $toolName
    ): array {
        $userId = $this->getUserId();
        if (!$userId) {
            return [
                'is_base64' => false,
                'content' => null,
                'url' => null,
                'content_type' => 'image/png',
                'revised_prompt' => null,
                'error' => 'User is not authenticated.',
            ];
        }

        $cleanModalities = [];
        foreach ($modalities as $m) {
            $s = strtoupper(trim((string) $m));
            if ('' !== $s) {
                $cleanModalities[] = $s;
            }
        }
        if (empty($cleanModalities)) {
            $cleanModalities = ['IMAGE', 'TEXT'];
        }

        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt],
                    ],
                ],
            ],
            'generationConfig' => [
                'responseModalities' => array_values(array_unique($cleanModalities)),
            ],
        ];

        if ($n > 1) {
            $payload['generationConfig']['candidateCount'] = $n;
        }

        $data = $this->postJson($url, $payload);
        if (empty($data)) {
            return [
                'is_base64' => false,
                'content' => null,
                'url' => null,
                'content_type' => 'image/png',
                'revised_prompt' => null,
                'error' => 'Gemini image request failed.',
            ];
        }

        $extracted = $this->extractFirstInlineImageFromGenerateContent($data);
        if (null !== $extracted) {
            // Tokens might be missing for image models; store best-effort.
            $usageMeta = \is_array($data['usageMetadata'] ?? null) ? $data['usageMetadata'] : [];
            $promptTokens = (int) ($usageMeta['promptTokenCount'] ?? 0);
            $completionTokens = (int) ($usageMeta['candidatesTokenCount'] ?? 0);
            $totalTokens = (int) ($usageMeta['totalTokenCount'] ?? ($promptTokens + $completionTokens));

            $this->saveAiRequest(
                userId: $userId,
                toolName: $toolName,
                requestText: $prompt,
                promptTokens: $promptTokens,
                completionTokens: $completionTokens,
                totalTokens: $totalTokens
            );

            return $extracted;
        }

        $err = $this->extractGeminiErrorMessage($data) ?? 'No inline image returned by Gemini generateContent.';

        return [
            'is_base64' => false,
            'content' => null,
            'url' => null,
            'content_type' => 'image/png',
            'revised_prompt' => null,
            'error' => $err,
        ];
    }

    private function requestImagenPredict(string $url, string $prompt, int $n, string $toolName): array
    {
        $userId = $this->getUserId();
        if (!$userId) {
            return [
                'is_base64' => false,
                'content' => null,
                'url' => null,
                'content_type' => 'image/png',
                'revised_prompt' => null,
                'error' => 'User is not authenticated.',
            ];
        }

        $payload = [
            'instances' => [
                ['prompt' => $prompt],
            ],
            'parameters' => [
                'sampleCount' => $n > 0 ? $n : 1,
            ],
        ];

        $data = $this->postJson($url, $payload);
        if (empty($data)) {
            return [
                'is_base64' => false,
                'content' => null,
                'url' => null,
                'content_type' => 'image/png',
                'revised_prompt' => null,
                'error' => 'Imagen predict request failed.',
            ];
        }

        $b64 = $this->extractFirstBase64FromImagenPredict($data);
        if (null !== $b64 && '' !== trim($b64)) {
            $this->saveAiRequest(
                userId: $userId,
                toolName: $toolName,
                requestText: $prompt,
                promptTokens: 0,
                completionTokens: 0,
                totalTokens: 0
            );

            return [
                'is_base64' => true,
                'content' => trim($b64),
                'url' => null,
                'content_type' => 'image/png',
                'revised_prompt' => null,
                'error' => null,
            ];
        }

        $err = $this->extractGeminiErrorMessage($data) ?? 'No base64 image returned by Imagen predict.';

        return [
            'is_base64' => false,
            'content' => null,
            'url' => null,
            'content_type' => 'image/png',
            'revised_prompt' => null,
            'error' => $err,
        ];
    }

    /**
     * POST JSON helper for Gemini endpoints.
     *
     * @param array<string,mixed> $payload
     *
     * @return array<string,mixed>
     */
    private function postJson(string $url, array $payload): array
    {
        try {
            $response = $this->httpClient->request('POST', $url, [
                'headers' => [
                    'x-goog-api-key' => $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
            ]);

            $status = $response->getStatusCode();
            $raw = (string) $response->getContent(false);
            $data = json_decode($raw, true);

            if (!\is_array($data)) {
                error_log('[AI][Gemini] Invalid JSON response (status='.$status.'). Raw: '.mb_substr($raw, 0, 800));

                return [];
            }

            if ($status < 200 || $status >= 300) {
                $msg = $this->extractGeminiErrorMessage($data) ?? 'Request failed.';
                error_log('[AI][Gemini] HTTP '.$status.': '.$msg);
            }

            return $data;
        } catch (Throwable $e) {
            error_log('[AI][Gemini] HTTP exception: '.$e->getMessage());

            return [];
        }
    }

    public function generateVideo(string $prompt, string $toolName, ?array $options = []): array|string|null
    {
        $prompt = trim($prompt);
        if ('' === $prompt) {
            return 'Error: Empty prompt.';
        }

        if ('' === trim($this->videoModel) || '' === trim($this->videoUrlTemplate)) {
            error_log('[AI][Gemini][Video] Video is not configured for this provider.');

            return null;
        }

        $userId = $this->getUserId();
        if (!$userId) {
            return 'Error: User is not authenticated.';
        }

        $toolName = trim($toolName);
        if ('' === $toolName) {
            $toolName = 'video';
        }

        $opts = \is_array($options) ? $options : [];

        // Veo requires durationSeconds as NUMBER (int).
        $durationSeconds = null;
        if (isset($opts['seconds']) && is_numeric($opts['seconds'])) {
            $durationSeconds = (int) $opts['seconds'];
        } elseif (isset($opts['durationSeconds']) && is_numeric($opts['durationSeconds'])) {
            $durationSeconds = (int) $opts['durationSeconds'];
        }

        if (null !== $durationSeconds) {
            if ($durationSeconds < 1) {
                $durationSeconds = 1;
            }
            if ($durationSeconds > 15) {
                $durationSeconds = 15;
            }
        }

        $aspectRatio = (string) ($opts['aspect_ratio'] ?? $this->videoDefaultAspectRatio);
        $resolution = (string) ($opts['resolution'] ?? $this->videoDefaultResolution);

        if (isset($opts['size'])) {
            $this->applySizeHintsToVeoParams((string) $opts['size'], $aspectRatio, $resolution);
        }

        $payload = [
            'instances' => [
                ['prompt' => $prompt],
            ],
            'parameters' => [
                'sampleCount' => 1,
            ],
        ];

        if ('' !== trim($aspectRatio)) {
            $payload['parameters']['aspectRatio'] = $aspectRatio;
        }
        if ('' !== trim($resolution)) {
            $payload['parameters']['resolution'] = $resolution;
        }
        if (null !== $durationSeconds) {
            $payload['parameters']['durationSeconds'] = $durationSeconds; // int
        }

        $url = $this->buildUrl($this->videoUrlTemplate, $this->videoModel);
        $data = $this->postJson($url, $payload);

        $opName = isset($data['name']) ? trim((string) $data['name']) : '';
        if ('' === $opName) {
            $err = $this->extractGeminiErrorMessage($data) ?? 'Missing operation name for video generation.';

            return 'Error: '.$err;
        }

        $publicId = $this->encodeVideoJobId($opName);

        $this->saveAiRequest(
            userId: $userId,
            toolName: $toolName,
            requestText: $prompt,
            promptTokens: 0,
            completionTokens: 0,
            totalTokens: 0
        );

        return [
            'id' => $publicId,
            'status' => 'pending',
            'is_base64' => false,
            'content' => null,
            'url' => null,
            'content_type' => 'video/mp4',
            'revised_prompt' => null,
        ];
    }

    public function getVideoJobStatus(string $jobId): ?array
    {
        $jobId = trim($jobId);
        if ('' === $jobId) {
            return null;
        }

        // Decode URL-safe id back to the real operation name.
        $opName = $this->decodeVideoJobId($jobId);

        $base = rtrim($this->videoStatusBaseUrl, '/');
        $path = ltrim($opName, '/');
        $url = $base.'/'.$path;

        try {
            $resp = $this->httpClient->request('GET', $url, [
                'headers' => [
                    'x-goog-api-key' => $this->apiKey,
                    'Accept' => 'application/json',
                ],
                'timeout' => 20,
                'max_duration' => 40,
            ]);

            $statusCode = $resp->getStatusCode();
            $raw = (string) $resp->getContent(false);
            $data = json_decode($raw, true);

            if ($statusCode < 200 || $statusCode >= 300 || !\is_array($data)) {
                return [
                    'id' => $jobId,
                    'status' => 'error',
                    'error' => 'Failed to fetch Veo operation status.',
                    'job' => $raw,
                ];
            }

            $done = (bool) ($data['done'] ?? false);

            $errMsg = null;
            if (isset($data['error']) && \is_array($data['error'])) {
                $msg = $data['error']['message'] ?? null;
                $errMsg = \is_string($msg) ? trim($msg) : 'Veo returned an error response.';
            }

            return [
                'id' => $jobId,
                'status' => $done ? 'done' : 'pending',
                'error' => $errMsg,
                'job' => $data,
            ];
        } catch (Throwable $e) {
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

        $status = strtolower(trim((string) ($job['status'] ?? '')));
        if ('done' !== $status) {
            return [
                'is_base64' => false,
                'content' => null,
                'url' => null,
                'content_type' => 'video/mp4',
                'error' => null,
            ];
        }

        $raw = $job['job'] ?? null;
        if (!\is_array($raw)) {
            return [
                'is_base64' => false,
                'content' => null,
                'url' => null,
                'content_type' => 'video/mp4',
                'error' => (string) ($job['error'] ?? 'Missing operation payload.'),
            ];
        }

        $uri = $this->extractVeoVideoUri($raw);
        if ('' === $uri) {
            $err = $this->extractGeminiErrorMessage($raw) ?? 'Missing video URI in Veo operation response.';

            return [
                'is_base64' => false,
                'content' => null,
                'url' => null,
                'content_type' => 'video/mp4',
                'error' => $err,
            ];
        }

        $dl = $this->downloadGeminiFileWithLimit($uri, $maxBytes, 'video/mp4');
        if (null === $dl) {
            return [
                'is_base64' => false,
                'content' => null,
                'url' => $uri,
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

    private function encodeVideoJobId(string $operationName): string
    {
        $operationName = trim($operationName);
        $b64 = base64_encode($operationName);

        // URL-safe base64 (no "+", "/", "=")
        return rtrim(strtr($b64, '+/', '-_'), '=');
    }

    private function decodeVideoJobId(string $publicId): string
    {
        $publicId = trim($publicId);
        if ('' === $publicId) {
            return '';
        }

        // If somebody passes the raw op name (already contains "models/.../operations/..."), keep it.
        if (str_contains($publicId, '/operations/') || str_starts_with($publicId, 'models/')) {
            return $publicId;
        }

        $b64 = strtr($publicId, '-_', '+/');
        $pad = \strlen($b64) % 4;
        if (0 !== $pad) {
            $b64 .= str_repeat('=', 4 - $pad);
        }

        $decoded = base64_decode($b64, true);
        if (false === $decoded) {
            // Fallback: treat as raw
            return $publicId;
        }

        $decoded = trim((string) $decoded);

        return '' !== $decoded ? $decoded : $publicId;
    }

    private function extractVeoVideoUri(array $operation): string
    {
        // Try several known/observed shapes (best-effort).
        $candidates = [
            $operation['response']['generateVideoResponse']['generatedSamples'][0]['video']['uri'] ?? null,
            $operation['response']['generateVideoResponse']['generatedSamples'][0]['video']['url'] ?? null,
            $operation['response']['generateVideoResponse']['generatedSamples'][0]['uri'] ?? null,

            $operation['response']['videos'][0]['uri'] ?? null,
            $operation['response']['videos'][0]['url'] ?? null,

            $operation['response']['generatedVideos'][0]['uri'] ?? null,
            $operation['response']['generatedVideos'][0]['url'] ?? null,

            $operation['response']['video']['uri'] ?? null,
            $operation['response']['video']['url'] ?? null,

            $operation['response']['uri'] ?? null,
            $operation['response']['url'] ?? null,
        ];

        foreach ($candidates as $u) {
            if (\is_string($u) && '' !== trim($u)) {
                return trim($u);
            }
        }

        return '';
    }

    private function applySizeHintsToVeoParams(string $size, string &$aspectRatio, string &$resolution): void
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

        if ('' === trim($aspectRatio)) {
            $aspectRatio = ($w >= $h) ? '16:9' : '9:16';
        }

        if ('' === trim($resolution)) {
            $maxDim = max($w, $h);
            if ($maxDim >= 2160) {
                $resolution = '4k';
            } elseif ($maxDim >= 1080) {
                $resolution = '1080p';
            } else {
                $resolution = '720p';
            }
        }
    }

    /**
     * @return array{bytes:string,content_type:string}|null
     */
    private function downloadGeminiFileWithLimit(string $url, int $maxBytes, string $fallbackType): ?array
    {
        try {
            $resp = $this->httpClient->request('GET', $url, [
                'headers' => [
                    'x-goog-api-key' => $this->apiKey,
                    'Accept' => '*/*',
                ],
                'max_redirects' => 5,
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
        } catch (Throwable) {
            return null;
        }
    }

    private function extractGeminiErrorMessage(array $data): ?string
    {
        // Common error format: { "error": { "message": "...", ... } }
        $err = $data['error'] ?? null;
        if (\is_array($err)) {
            $msg = $err['message'] ?? null;
            if (\is_string($msg) && '' !== trim($msg)) {
                return trim($msg);
            }
        }

        return null;
    }

    private function extractFirstTextFromGenerateContent(array $data): ?string
    {
        $parts = $data['candidates'][0]['content']['parts'] ?? null;
        if (!\is_array($parts)) {
            return null;
        }

        foreach ($parts as $p) {
            $t = $p['text'] ?? null;
            if (\is_string($t) && '' !== trim($t)) {
                return trim($t);
            }
        }

        return null;
    }

    /**
     * Extract the first inline image from generateContent response.
     *
     * @return array<string,mixed>|null
     */
    private function extractFirstInlineImageFromGenerateContent(array $data): ?array
    {
        $parts = $data['candidates'][0]['content']['parts'] ?? null;
        if (!\is_array($parts)) {
            return null;
        }

        $firstText = null;
        foreach ($parts as $p) {
            if (null === $firstText && \is_string($p['text'] ?? null) && '' !== trim((string) $p['text'])) {
                $firstText = trim((string) $p['text']);
            }

            $inline = $p['inlineData'] ?? $p['inline_data'] ?? null;
            if (\is_array($inline) && \is_string($inline['data'] ?? null) && '' !== trim((string) $inline['data'])) {
                $mime = (string) ($inline['mimeType'] ?? $inline['mime_type'] ?? 'image/png');

                return [
                    'is_base64' => true,
                    'content' => trim((string) $inline['data']),
                    'url' => null,
                    'content_type' => '' !== trim($mime) ? trim($mime) : 'image/png',
                    'revised_prompt' => $firstText,
                    'error' => null,
                ];
            }
        }

        return null;
    }

    private function extractFirstBase64FromImagenPredict(array $data): ?string
    {
        // Common: { predictions: [ { bytesBase64Encoded: "..." } ] }
        $pred = $data['predictions'] ?? null;
        if (\is_array($pred) && isset($pred[0]) && \is_array($pred[0])) {
            $b64 = $pred[0]['bytesBase64Encoded'] ?? null;
            if (\is_string($b64) && '' !== trim($b64)) {
                return $b64;
            }

            // Some variants: { image: { imageBytes: "..." } }
            $img = $pred[0]['image'] ?? null;
            if (\is_array($img)) {
                $b = $img['imageBytes'] ?? null;
                if (\is_string($b) && '' !== trim($b)) {
                    return $b;
                }
            }
        }

        // Alternative keys
        $gen = $data['generatedImages'] ?? $data['generated_images'] ?? null;
        if (\is_array($gen) && isset($gen[0]) && \is_array($gen[0])) {
            $b64 = $gen[0]['bytesBase64Encoded'] ?? null;
            if (\is_string($b64) && '' !== trim($b64)) {
                return $b64;
            }
        }

        return null;
    }

    private function saveAiRequest(
        int $userId,
        string $toolName,
        string $requestText,
        int $promptTokens,
        int $completionTokens,
        int $totalTokens
    ): void {
        $aiRequest = new AiRequests();
        $aiRequest
            ->setUserId($userId)
            ->setToolName($toolName)
            ->setRequestText($requestText)
            ->setPromptTokens($promptTokens)
            ->setCompletionTokens($completionTokens)
            ->setTotalTokens($totalTokens)
            ->setAiProvider('gemini')
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
            $validOptions = array_filter($options, static fn ($line) => (bool) preg_match('/^[A-D]\. .+/', $line));

            $answerLine = (string) end($lines);
            if (4 === \count($validOptions) && preg_match('/^ANSWER: [A-D]$/', $answerLine)) {
                $validQuestions[] = implode("\n", $lines);
            }
        }

        return $validQuestions;
    }

    private function buildUrl(string $template, string $model): string
    {
        return str_contains($template, '%s') ? \sprintf($template, $model) : $template;
    }

    /**
     * Convert message history into a single prompt for Gemini.
     *
     * @param array<int, array{role:string,content:string}> $messages
     */
    private function messagesToPrompt(array $messages): string
    {
        $lines = [];

        foreach ($messages as $m) {
            if (!\is_array($m)) {
                continue;
            }

            $role = isset($m['role']) ? trim((string) $m['role']) : '';
            $content = isset($m['content']) ? trim((string) $m['content']) : '';

            if ('' === $content) {
                continue;
            }

            $role = strtolower($role);
            if (!\in_array($role, ['system', 'user', 'assistant', 'tool'], true)) {
                $role = 'user';
            }

            $lines[] = strtoupper($role).': '.$content;
        }

        return trim(implode("\n", $lines));
    }

    /**
     * Resolve per-request overrides for text/chat.
     *
     * @param array<string,mixed> $options
     *
     * @return array{url:string,temperature:float,max_output_tokens:int}
     */
    private function resolveTextOptions(array $options): array
    {
        $model = (string) (($options['model'] ?? null) ?? $this->textModel);
        $template = (string) (($options['url_template'] ?? null) ?? $this->textUrlTemplate);

        // Allow direct "url" override too.
        $url = isset($options['url']) ? (string) $options['url'] : $this->buildUrl($template, $model);

        $temperature = (float) (($options['temperature'] ?? null) ?? $this->textTemperature);

        // Accept either max_output_tokens or max_tokens
        $max = $options['max_output_tokens'] ?? ($options['max_tokens'] ?? null);
        $max = (int) (($max ?? null) ?? $this->textMaxOutputTokens);

        if ($max <= 0) {
            $max = $this->textMaxOutputTokens > 0 ? $this->textMaxOutputTokens : 1000;
        }

        return [
            'url' => $url,
            'temperature' => $temperature,
            'max_output_tokens' => $max,
        ];
    }

    /**
     * Resolve per-request overrides for image.
     *
     * @param array<string,mixed> $options
     *
     * @return array{url:string,model:string,request_format:string,n:int,response_modalities:array<int,string>}
     */
    private function resolveImageOptions(array $options): array
    {
        $model = (string) (($options['model'] ?? null) ?? $this->imageModel);
        $template = (string) (($options['url_template'] ?? null) ?? $this->imageUrlTemplate);
        $url = isset($options['url']) ? (string) $options['url'] : $this->buildUrl($template, $model);

        $format = (string) (($options['request_format'] ?? null) ?? $this->imageRequestFormat);

        $n = (int) (($options['n'] ?? null) ?? $this->imageN);
        if ($n <= 0) {
            $n = 1;
        }

        $mods = $options['response_modalities'] ?? null;
        $modalities = $this->imageResponseModalities;
        if (\is_array($mods) && !empty($mods)) {
            $clean = [];
            foreach ($mods as $m) {
                $s = strtoupper(trim((string) $m));
                if ('' !== $s) {
                    $clean[] = $s;
                }
            }
            if (!empty($clean)) {
                $modalities = array_values(array_unique($clean));
            }
        }

        return [
            'url' => $url,
            'model' => $model,
            'request_format' => $format,
            'n' => $n,
            'response_modalities' => $modalities,
        ];
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
}
