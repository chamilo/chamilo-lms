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

class GrokImageProvider implements AiImageProviderInterface
{
    private string $apiUrl;
    private string $apiKey;
    private string $model;
    private array $defaultOptions;
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

        if (!isset($config['grok'])) {
            throw new RuntimeException('Grok configuration is missing.');
        }
        if (!isset($config['grok']['image'])) {
            throw new RuntimeException('Grok configuration for image processing is missing.');
        }

        $this->apiKey = $config['grok']['api_key'] ?? '';
        $this->apiUrl = $config['grok']['image']['url'] ?? 'https://api.x.ai/v1/images/generations';
        $this->model = $config['grok']['image']['model'] ?? 'grok-2-image';
        $this->defaultOptions = [
            'response_format' => $config['grok']['image']['response_format'] ?? 'b64_json',
            'n' => 1,
        ];

        if (empty($this->apiKey)) {
            throw new RuntimeException('Grok API key is missing.');
        }
    }

    public function generateImage(string $prompt, string $toolName, ?array $options = []): string|array|null
    {
        return $this->requestGrokAI($prompt, $toolName, $options ?? []);
    }

    private function requestGrokAI(string $prompt, string $toolName, array $options = []): string|array|null
    {
        $userId = $this->getUserId();
        if (!$userId) {
            throw new RuntimeException('User not authenticated.');
        }

        $payload = [
            'model' => $this->model,
            // Direct prompt string (no messages array for this endpoint)
            'prompt' => $prompt,
            ...array_merge($this->defaultOptions, $options),
        ];

        try {
            $response = $this->httpClient->request('POST', $this->apiUrl, [
                'headers' => [
                    'Authorization' => 'Bearer '.$this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
            ]);

            $statusCode = $response->getStatusCode();
            if (200 !== $statusCode) {
                throw new RuntimeException('API request failed with status: '.$statusCode);
            }

            $data = $response->toArray();

            // Check for error key first
            if (isset($data['error'])) {
                throw new RuntimeException('API error: '.$data['error']['message']);
            }

            // Usage might not exist for images; default to 0
            $usage = $data['usage'] ?? ['prompt_tokens' => 0, 'completion_tokens' => 0, 'total_tokens' => 0];

            // Log request
            $aiRequest = new AiRequests();
            $aiRequest->setUserId($userId)
                ->setToolName($toolName)
                ->setRequestText($prompt)
                ->setPromptTokens((int) ($usage['prompt_tokens'] ?? 0))
                ->setCompletionTokens((int) ($usage['completion_tokens'] ?? 0))
                ->setTotalTokens((int) ($usage['total_tokens'] ?? 0))
                ->setAiProvider('grok')
            ;
            $this->aiRequestsRepository->save($aiRequest);

            // Preferred response: base64
            if (isset($data['data'][0]['b64_json'])) {
                return [
                    'content' => (string) $data['data'][0]['b64_json'],
                    'is_base64' => true,
                    'content_type' => 'image/png',
                    'revised_prompt' => $data['data'][0]['revised_prompt'] ?? null,
                ];
            }

            // Alternative response: URL
            if (isset($data['data'][0]['url'])) {
                return [
                    'url' => (string) $data['data'][0]['url'],
                    'is_base64' => false,
                    'content_type' => 'image/png',
                    'revised_prompt' => $data['data'][0]['revised_prompt'] ?? null,
                ];
            }

            // Legacy fallback (should rarely happen)
            return null;
        } catch (Exception $e) {
            error_log('[AI][Grok][Image] Exception: '.$e->getMessage());

            return null;
        }
    }

    private function getUserId(): ?int
    {
        $user = $this->security->getUser();

        return $user instanceof UserInterface ? $user->getId() : null;
    }
}
