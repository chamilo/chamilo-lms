<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\AiProvider;

use Chamilo\CoreBundle\Entity\AiRequests;
use Chamilo\CoreBundle\Repository\AiRequestsRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Exception;
use RuntimeException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\Multipart\FormDataPart;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class OpenAiProvider implements AiProviderInterface, AiImageProviderInterface, AiVideoProviderInterface, AiDocumentProviderInterface
{
    private array $providerConfig;
    private string $apiKey;
    private string $organizationId;
    private int $monthlyTokenLimit;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        SettingsManager $settingsManager,
        private readonly AiRequestsRepository $aiRequestsRepository,
        private readonly Security $security
    ) {
        $configJson = $settingsManager->getSetting('ai_helpers.ai_providers', true);
        $config = \is_string($configJson) ? (json_decode($configJson, true) ?? []) : (\is_array($configJson) ? $configJson : []);

        if (!isset($config['openai']) || !\is_array($config['openai'])) {
            throw new RuntimeException('OpenAI configuration is missing.');
        }

        $this->providerConfig = $config['openai'];

        $this->apiKey = (string) ($this->providerConfig['api_key'] ?? '');
        $this->organizationId = (string) ($this->providerConfig['organization_id'] ?? '');
        $this->monthlyTokenLimit = (int) ($this->providerConfig['monthly_token_limit'] ?? 0);

        if ('' === trim($this->apiKey)) {
            throw new RuntimeException('OpenAI API key is missing.');
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

        return $this->requestChatCompletion($prompt, 'quiz', 'text');
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

        $lpStructure = $this->requestChatCompletion($tableOfContentsPrompt, 'learnpath', 'text');
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

            $chapterContent = $this->requestChatCompletion($chapterPrompt, 'learnpath', 'text');
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

                $quizContent = $this->requestChatCompletion($quizPrompt, 'learnpath', 'text');

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
        return $this->requestChatCompletion($prompt, $toolName, 'text');
    }

    public function generateDocument(string $prompt, string $toolName, ?array $options = []): ?string
    {
        return $this->requestChatCompletion($prompt, $toolName, 'document');
    }

    public function generateImage(string $prompt, string $toolName, ?array $options = []): string|array|null
    {
        $userId = $this->getUserId();
        if (!$userId) {
            throw new RuntimeException('User not authenticated.');
        }

        $cfg = $this->getTypeConfig('image');
        $url = (string) ($cfg['url'] ?? 'https://api.openai.com/v1/images/generations');
        $model = (string) ($cfg['model'] ?? 'gpt-image-1');
        $size = (string) ($cfg['size'] ?? '1024x1024');
        $quality = (string) ($cfg['quality'] ?? 'standard');
        $n = (int) (($options['n'] ?? null) ?? ($cfg['n'] ?? 1));

        $promptTrimmed = trim($prompt);
        $promptForLog = mb_substr($promptTrimmed, 0, 200);

        if ('dall-e-3' === $model && $n !== 1) {
            error_log(sprintf('[AI][OpenAI][image] Model "%s" only supports n=1. Forcing n from %d to 1.', $model, $n));
            $n = 1;
        }

        $payload = [
            'model' => $model,
            'prompt' => $promptTrimmed,
            'size' => $size,
            'quality' => $quality,
            'n' => $n,
        ];

        // Best-effort: allow response_format for any model that supports it.
        $responseFormat = (string) ($cfg['response_format'] ?? 'b64_json');
        if ('' !== trim($responseFormat)) {
            $payload['response_format'] = $responseFormat;
        }

        error_log(sprintf(
            '[AI][OpenAI][image] Request: userId=%d tool=%s url=%s model=%s size=%s quality=%s n=%d prompt="%s"',
            $userId,
            $toolName,
            $url,
            $model,
            $size,
            $quality,
            $n,
            $promptForLog
        ));

        try {
            $response = $this->httpClient->request('POST', $url, [
                'headers' => $this->buildAuthHeaders(true),
                'json' => $payload,
            ]);

            $status = $response->getStatusCode();
            $headers = $response->getHeaders(false);
            $requestId = $this->extractRequestId($headers);

            $raw = $response->getContent(false);
            $rawForLog = mb_substr((string) $raw, 0, 2000);

            if ($status >= 400) {
                $decoded = json_decode((string) $raw, true);
                $msg = $decoded['error']['message'] ?? null;
                $type = $decoded['error']['type'] ?? null;
                $code = $decoded['error']['code'] ?? null;
                $param = $decoded['error']['param'] ?? null;

                $finalMsg = $msg ?: 'OpenAI returned an error response.';
                error_log(sprintf(
                    '[AI][OpenAI][image] HTTP %d request_id=%s type=%s code=%s param=%s body=%s',
                    $status,
                    (string) $requestId,
                    (string) $type,
                    (string) $code,
                    (string) $param,
                    $rawForLog
                ));

                return 'Error: '.$finalMsg;
            }

            $data = json_decode((string) $raw, true);
            if (!is_array($data)) {
                error_log(sprintf(
                    '[AI][OpenAI][image] Invalid JSON response. HTTP %d request_id=%s body=%s',
                    $status,
                    (string) $requestId,
                    $rawForLog
                ));

                return 'Error: Invalid JSON response from OpenAI.';
            }

            if (!isset($data['data'][0]) || !is_array($data['data'][0])) {
                error_log(sprintf(
                    '[AI][OpenAI][image] Missing image data[0]. HTTP %d request_id=%s keys=%s body=%s',
                    $status,
                    (string) $requestId,
                    implode(',', array_keys($data)),
                    $rawForLog
                ));

                return 'Error: OpenAI response missing image data.';
            }

            $item = $data['data'][0];

            $result = [
                'content' => null,
                'url' => null,
                'is_base64' => false,
                'content_type' => 'image/png',
                'revised_prompt' => $item['revised_prompt'] ?? null,
            ];

            if (isset($item['b64_json']) && \is_string($item['b64_json']) && '' !== $item['b64_json']) {
                $result['content'] = $item['b64_json'];
                $result['is_base64'] = true;
            } elseif (isset($item['url']) && \is_string($item['url']) && '' !== $item['url']) {
                $result['url'] = $item['url'];
                $result['is_base64'] = false;
            } else {
                error_log(sprintf(
                    '[AI][OpenAI][image] Response did not include b64_json or url. HTTP %d request_id=%s body=%s',
                    $status,
                    (string) $requestId,
                    $rawForLog
                ));

                return 'Error: OpenAI response did not include image content.';
            }

            $this->saveAiRequest(
                $userId,
                $toolName,
                $promptTrimmed,
                'openai',
                (int) ($data['usage']['prompt_tokens'] ?? 0),
                (int) ($data['usage']['completion_tokens'] ?? 0),
                (int) ($data['usage']['total_tokens'] ?? 0)
            );

            error_log(sprintf(
                '[AI][OpenAI][image] Success. HTTP %d request_id=%s revised_prompt=%s returned_base64=%s returned_url=%s',
                $status,
                (string) $requestId,
                isset($result['revised_prompt']) ? (string) $result['revised_prompt'] : '',
                $result['is_base64'] ? 'yes' : 'no',
                !empty($result['url']) ? 'yes' : 'no'
            ));

            return $result;
        } catch (Exception $e) {
            error_log('[AI][OpenAI][image] Exception: '.$e->getMessage());
            return 'Error: '.$e->getMessage();
        }
    }

    public function generateVideo(string $prompt, string $toolName, ?array $options = []): string|array|null
    {
        $userId = $this->getUserId();
        if (!$userId) {
            throw new RuntimeException('User not authenticated.');
        }

        $cfg = $this->getTypeConfig('video');
        $url = (string) ($cfg['url'] ?? 'https://api.openai.com/v1/videos');
        $model = (string) ($cfg['model'] ?? 'sora-2');
        $seconds = (string) (($options['seconds'] ?? null) ?? ($cfg['seconds'] ?? '4'));
        $size = (string) (($options['size'] ?? null) ?? ($cfg['size'] ?? '720x1280'));

        $promptTrimmed = trim($prompt);
        $promptForLog = mb_substr($promptTrimmed, 0, 200);

        error_log(sprintf(
            '[AI][OpenAI][video] Request: userId=%d tool=%s url=%s model=%s seconds=%s size=%s prompt="%s"',
            $userId,
            $toolName,
            $url,
            $model,
            $seconds,
            $size,
            $promptForLog
        ));

        try {
            $fields = [
                'model' => $model,
                'prompt' => $promptTrimmed,
                'seconds' => $seconds,
                'size' => $size,
            ];

            if (isset($options['input_reference_path']) && is_string($options['input_reference_path']) && '' !== $options['input_reference_path']) {
                $path = $options['input_reference_path'];
                if (is_readable($path)) {
                    $fields['input_reference'] = DataPart::fromPath($path);
                } else {
                    error_log('[AI][OpenAI][video] input_reference_path is not readable: '.$path);
                }
            }

            $formData = new FormDataPart($fields);

            $headers = array_merge(
                $this->buildAuthHeaders(false),
                $formData->getPreparedHeaders()->toArray(),
                [
                    'Accept' => 'application/json',
                ]
            );

            $response = $this->httpClient->request('POST', $url, [
                'headers' => $headers,
                'body' => $formData->bodyToIterable(),
            ]);

            $status = $response->getStatusCode();
            $respHeaders = $response->getHeaders(false);
            $requestId = $this->extractRequestId($respHeaders);

            $raw = (string) $response->getContent(false);
            $rawForLog = mb_substr($raw, 0, 2000);

            if ($status >= 400) {
                $err = $this->decodeOpenAiError($raw);
                $finalMsg = (string) ($err['message'] ?? '');

                if ('' === trim($finalMsg)) {
                    // Fallback when body is empty or not JSON.
                    $finalMsg = sprintf(
                        'OpenAI returned HTTP %d. Ensure your project/org has access to model "%s" and the organization is verified if required.',
                        $status,
                        $model
                    );
                }

                error_log(sprintf(
                    '[AI][OpenAI][video] HTTP %d request_id=%s type=%s code=%s param=%s error="%s" body=%s',
                    $status,
                    (string) $requestId,
                    (string) ($err['type'] ?? ''),
                    (string) ($err['code'] ?? ''),
                    (string) ($err['param'] ?? ''),
                    $finalMsg,
                    $rawForLog !== '' ? $rawForLog : '(empty body)'
                ));

                return 'Error: '.$finalMsg;
            }

            $data = json_decode($raw, true);
            if (!is_array($data)) {
                error_log(sprintf(
                    '[AI][OpenAI][video] Invalid JSON response. HTTP %d request_id=%s body=%s',
                    $status,
                    (string) $requestId,
                    $rawForLog !== '' ? $rawForLog : '(empty body)'
                ));
                return 'Error: Invalid JSON response from OpenAI.';
            }

            if (!isset($data['id']) || !is_string($data['id']) || '' === trim($data['id'])) {
                error_log(sprintf(
                    '[AI][OpenAI][video] Missing "id" in response. HTTP %d request_id=%s keys=%s body=%s',
                    $status,
                    (string) $requestId,
                    implode(',', array_keys($data)),
                    $rawForLog
                ));
                return 'Error: OpenAI response missing "id".';
            }

            $result = [
                'id' => $data['id'],
                'status' => (string) ($data['status'] ?? ''),
                'content' => null,
                'url' => null,
                'is_base64' => false,
                'content_type' => 'video/mp4',
                'revised_prompt' => null,
                'job' => $data,
            ];

            // Job created -> tokens not provided here (keep 0s)
            $this->saveAiRequest($userId, $toolName, $promptTrimmed, 'openai', 0, 0, 0);

            error_log(sprintf(
                '[AI][OpenAI][video] Job created. HTTP %d request_id=%s id=%s status=%s',
                $status,
                (string) $requestId,
                (string) $result['id'],
                (string) $result['status']
            ));

            return $result;
        } catch (Exception $e) {
            error_log('[AI][OpenAI][video] Exception: '.$e->getMessage());
            return 'Error: '.$e->getMessage();
        }
    }

    public function getVideoJobStatus(string $jobId): ?array
    {
        $cfg = $this->getTypeConfig('video');
        $statusUrlTpl = (string) ($cfg['status_url'] ?? 'https://api.openai.com/v1/videos/{id}');
        $statusUrl = str_replace('{id}', rawurlencode($jobId), $statusUrlTpl);

        try {
            $response = $this->httpClient->request('GET', $statusUrl, [
                'headers' => $this->buildAuthHeaders(false),
            ]);

            $status = $response->getStatusCode();
            $headers = $response->getHeaders(false);
            $requestId = $this->extractRequestId($headers);

            $raw = (string) $response->getContent(false);

            if ($status >= 400) {
                $msg = $this->extractOpenAiErrorMessage($raw);
                error_log(sprintf(
                    '[AI][OpenAI][video] Status HTTP %d request_id=%s id=%s error="%s" body=%s',
                    $status,
                    $requestId,
                    $jobId,
                    $msg,
                    $this->safeTruncate($raw, 2000)
                ));
                return [
                    'id' => $jobId,
                    'status' => 'failed',
                    'error' => $msg,
                    'job' => null,
                ];
            }

            $data = json_decode($raw, true);
            if (!is_array($data)) {
                return [
                    'id' => $jobId,
                    'status' => '',
                    'error' => 'Invalid JSON response from OpenAI.',
                    'job' => null,
                ];
            }

            return [
                'id' => (string) ($data['id'] ?? $jobId),
                'status' => (string) ($data['status'] ?? ''),
                'error' => null,
                'job' => $data,
            ];
        } catch (Exception $e) {
            error_log('[AI][OpenAI][video] getVideoJobStatus exception: '.$e->getMessage());
            return [
                'id' => $jobId,
                'status' => '',
                'error' => $e->getMessage(),
                'job' => null,
            ];
        }
    }

    public function getVideoJobContentAsBase64(string $jobId, int $maxBytes = 15728640): ?array
    {
        $cfg = $this->getTypeConfig('video');
        $contentUrlTpl = (string) ($cfg['content_url'] ?? 'https://api.openai.com/v1/videos/{id}/content');
        $contentUrl = str_replace('{id}', rawurlencode($jobId), $contentUrlTpl);

        try {
            $response = $this->httpClient->request('GET', $contentUrl, [
                'headers' => array_merge($this->buildAuthHeaders(false), [
                    'Accept' => '*/*',
                ]),
            ]);

            $status = $response->getStatusCode();
            $headers = $response->getHeaders(false);
            $requestId = $this->extractRequestId($headers);

            $contentType = (string) ($headers['content-type'][0] ?? 'video/mp4');
            $raw = (string) $response->getContent(false);

            if ($status >= 400) {
                $msg = $this->extractOpenAiErrorMessage($raw);
                error_log(sprintf(
                    '[AI][OpenAI][video] Content HTTP %d request_id=%s id=%s error="%s" body=%s',
                    $status,
                    $requestId,
                    $jobId,
                    $msg,
                    $this->safeTruncate($raw, 2000)
                ));
                return [
                    'is_base64' => false,
                    'content' => null,
                    'url' => null,
                    'content_type' => $contentType,
                    'error' => $msg,
                ];
            }

            if ($raw !== '' && ($raw[0] === '{' || $raw[0] === '[')) {
                $json = json_decode($raw, true);
                if (is_array($json)) {
                    $url = $json['url'] ?? ($json['data']['url'] ?? null);
                    if (is_string($url) && '' !== trim($url)) {
                        return [
                            'is_base64' => false,
                            'content' => null,
                            'url' => trim($url),
                            'content_type' => 'video/mp4',
                            'error' => null,
                        ];
                    }
                }

                return [
                    'is_base64' => false,
                    'content' => null,
                    'url' => null,
                    'content_type' => 'video/mp4',
                    'error' => 'Content endpoint returned JSON but no URL was found.',
                ];
            }

            if (strlen($raw) > $maxBytes) {
                return [
                    'is_base64' => false,
                    'content' => null,
                    'url' => null,
                    'content_type' => $contentType,
                    'error' => 'Video exceeded the maximum allowed size.',
                ];
            }

            return [
                'is_base64' => true,
                'content' => base64_encode($raw),
                'url' => null,
                'content_type' => $contentType,
                'error' => null,
            ];
        } catch (Exception $e) {
            error_log('[AI][OpenAI][video] getVideoJobContentAsBase64 exception: '.$e->getMessage());
            return [
                'is_base64' => false,
                'content' => null,
                'url' => null,
                'content_type' => 'video/mp4',
                'error' => $e->getMessage(),
            ];
        }
    }

    private function requestChatCompletion(string $prompt, string $toolName, string $type): ?string
    {
        $userId = $this->getUserId();
        if (!$userId) {
            throw new RuntimeException('User not authenticated.');
        }

        $cfg = $this->getTypeConfig($type);
        if ('document' === $type && empty($cfg)) {
            $cfg = $this->getTypeConfig('text');
        }

        if (empty($cfg)) {
            error_log('[AI][OpenAI] Missing config for type: '.$type);
            return null;
        }

        $url = (string) ($cfg['url'] ?? 'https://api.openai.com/v1/chat/completions');
        $model = (string) ($cfg['model'] ?? 'gpt-4o-mini');
        $temperature = (float) ($cfg['temperature'] ?? 0.7);
        $maxTokens = (int) ($cfg['max_tokens'] ?? 1000);

        $payload = [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => 'You are a helpful AI assistant that generates structured educational content.'],
                ['role' => 'user', 'content' => $prompt],
            ],
            'temperature' => $temperature,
            'max_tokens' => $maxTokens,
        ];

        try {
            $response = $this->httpClient->request('POST', $url, [
                'headers' => $this->buildAuthHeaders(true),
                'json' => $payload,
            ]);

            $data = $response->toArray(false);

            $generatedContent = $data['choices'][0]['message']['content'] ?? null;
            if (!\is_string($generatedContent) || '' === trim($generatedContent)) {
                error_log('[AI][OpenAI] Empty content returned for type: '.$type);
                return null;
            }

            $this->saveAiRequest(
                $userId,
                $toolName,
                $prompt,
                'openai',
                (int) ($data['usage']['prompt_tokens'] ?? 0),
                (int) ($data['usage']['completion_tokens'] ?? 0),
                (int) ($data['usage']['total_tokens'] ?? 0)
            );

            return $generatedContent;
        } catch (Exception $e) {
            error_log('[AI][OpenAI] Exception: '.$e->getMessage());
            return null;
        }
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
            $validOptions = array_filter($options, static fn ($line) => (bool) preg_match('/^[A-D]\. .+/', $line));

            $answerLine = end($lines);
            if (4 === \count($validOptions) && \is_string($answerLine) && preg_match('/^ANSWER: [A-D]$/', $answerLine)) {
                $validQuestions[] = implode("\n", $lines);
            }
        }

        return $validQuestions;
    }

    private function getTypeConfig(string $type): array
    {
        $cfg = $this->providerConfig[$type] ?? null;
        return \is_array($cfg) ? $cfg : [];
    }

    private function buildAuthHeaders(bool $json): array
    {
        $headers = [
            'Authorization' => 'Bearer '.$this->apiKey,
        ];

        if ('' !== trim($this->organizationId)) {
            $headers['OpenAI-Organization'] = $this->organizationId;
        }

        $projectId = (string) ($this->providerConfig['project_id'] ?? '');
        if ('' !== trim($projectId)) {
            $headers['OpenAI-Project'] = $projectId;
        }

        if ($json) {
            $headers['Content-Type'] = 'application/json';
        }

        return $headers;
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

    private function extractRequestId(array $headers): string
    {
        return (string) ($headers['x-request-id'][0] ?? $headers['request-id'][0] ?? '');
    }

    private function extractOpenAiErrorMessage(string $raw): string
    {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            $msg = $decoded['error']['message'] ?? null;
            if (is_string($msg) && trim($msg) !== '') {
                return trim($msg);
            }
        }
        return 'OpenAI returned an error response.';
    }

    private function safeTruncate(string $s, int $max = 2000): string
    {
        return mb_substr($s, 0, $max);
    }

    private function decodeOpenAiError(string $raw): array
    {
        $decoded = json_decode($raw, true);

        if (!is_array($decoded)) {
            return [
                'message' => null,
                'type' => null,
                'code' => null,
                'param' => null,
            ];
        }

        $err = $decoded['error'] ?? null;
        if (!is_array($err)) {
            return [
                'message' => null,
                'type' => null,
                'code' => null,
                'param' => null,
            ];
        }

        return [
            'message' => isset($err['message']) && is_string($err['message']) ? $err['message'] : null,
            'type' => isset($err['type']) && is_string($err['type']) ? $err['type'] : null,
            'code' => isset($err['code']) && is_string($err['code']) ? $err['code'] : null,
            'param' => isset($err['param']) && is_string($err['param']) ? $err['param'] : null,
        ];
    }
}
