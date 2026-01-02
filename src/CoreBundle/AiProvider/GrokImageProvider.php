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
            'n' => 1, // Number of images is fixed until we add an interface for choosing
        ];

        if (empty($this->apiKey)) {
            throw new RuntimeException('Grok API key is missing.');
        }
    }

    public function generateImage(string $prompt, string $toolName, ?array $options = []): ?string
    {
        return $this->requestGrokAI($prompt, $toolName, $options);
    }
    private function requestGrokAI(string $prompt, string $toolName, array $options = []): ?string
    {
        $userId = $this->getUserId();
        if (!$userId) {
            throw new RuntimeException('User not authenticated.');
        }

        $payload = [
            'model' => $this->model,
            'prompt' => $prompt,  // Direct prompt string, no messages array
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

            // Proper access: assuming response_format 'b64_json'
            if (isset($data['data'][0]['b64_json'])) {
                $generatedContent = $data['data'][0]['b64_json'];

                // Usage might not exist for images; default to 0
                $usage = $data['usage'] ?? ['prompt_tokens' => 0, 'completion_tokens' => 0, 'total_tokens' => 0];

                // Log request
                $aiRequest = new AiRequests();
                $aiRequest->setUserId($userId)
                    ->setToolName($toolName)
                    ->setRequestText($prompt)
                    ->setPromptTokens($usage['prompt_tokens'])
                    ->setCompletionTokens($usage['completion_tokens'])
                    ->setTotalTokens($usage['total_tokens'])
                    ->setAiProvider('grok')
                ;

                $this->aiRequestsRepository->save($aiRequest);

                return $generatedContent;
            }

            return null;
        } catch (Exception $e) {
            error_log('[AI][Grok] Exception: '.$e->getMessage());

            return null;
        }
    }

    private function getUserId(): ?int
    {
        $user = $this->security->getUser();

        return $user instanceof UserInterface ? $user->getId() : null;
    }
}
