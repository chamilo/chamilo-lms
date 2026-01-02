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
    private string $url;
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
        $this->apiUrl = $config['grok']['image']['url'] ?? 'https://api.x.ai/v1/chat/completions';
        $this->model = $config['grok']['image']['model'] ?? 'grok-beta';
        $this->temperature = $config['grok']['image']['temperature'] ?? 0.7;

        if (empty($this->apiKey)) {
            throw new RuntimeException('Grok API key is missing.');
        }
    }

    public function generateImage(string $prompt, ?array $options = []): ?string
    {
        return $this->requestGrokAI($prompt, $options);
    }
    private function requestGrokAI(string $prompt, string $toolName): ?string
    {
        $userId = $this->getUserId();
        if (!$userId) {
            throw new RuntimeException('User not authenticated.');
        }

        $payload = [
            'model' => $this->model,
            'input' => [  // Changed from 'messages'
                ['role' => 'system', 'content' => 'You are a helpful AI assistant that generates structured educational content.'],
                ['role' => 'user', 'content' => $prompt],
            ],
            'temperature' => $this->temperature,
            'max_tokens' => 1000,
            // Optional: Add if needed, e.g., 'store' => true, 'parallel_tool_calls' => false
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
            $data = $response->toArray();

            if (200 === $statusCode && isset($data['output'][0]['content'][0]['text']) && $data['status'] === 'completed') {  // Updated parsing and status check
                $generatedContent = $data['output'][0]['content'][0]['text'];

                $aiRequest = new AiRequests();
                $aiRequest->setUserId($userId)
                    ->setToolName($toolName)
                    ->setRequestText($prompt)
                    ->setPromptTokens($data['usage']['prompt_tokens'] ?? 0)
                    ->setCompletionTokens($data['usage']['completion_tokens'] ?? 0)
                    ->setTotalTokens($data['usage']['total_tokens'] ?? 0)
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
