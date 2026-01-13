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

/**
 * Grok (xAI) provider for document generation.
 * Note: This provider generates structured text (e.g., Markdown) that can be converted to PDF client-side.
 */
class GrokDocumentProvider implements AiDocumentProviderInterface
{
    private string $apiUrl;
    private string $apiKey;
    private string $model;
    private array $defaultOptions;

    private HttpClientInterface $httpClient;
    private AiRequestsRepository $aiRequestsRepository;
    private SettingsManager $settingsManager;
    private Security $security;

    public function __construct(
        HttpClientInterface $httpClient,
        SettingsManager $settingsManager,
        AiRequestsRepository $aiRequestsRepository,
        Security $security
    ) {
        $this->httpClient = $httpClient;
        $this->aiRequestsRepository = $aiRequestsRepository;
        $this->settingsManager = $settingsManager;
        $this->security = $security;

        // Get AI providers from settings
        $configJson = $settingsManager->getSetting('ai_helpers.ai_providers', true);
        $config = json_decode($configJson, true) ?? [];

        if (!isset($config['grok'])) {
            throw new RuntimeException('Grok configuration is missing.');
        }
        if (!isset($config['grok']['document'])) {
            throw new RuntimeException('Grok configuration for document generation is missing.');
        }

        $providerConfig = $config['grok'];
        $documentConfig = $providerConfig['document'];

        $this->apiKey = $providerConfig['api_key'] ?? '';
        if (empty($this->apiKey)) {
            throw new RuntimeException('Grok API key is missing.');
        }

        // Prefer type-specific config, fallback to root keys if needed
        $this->apiUrl = $documentConfig['url'] ?? ($providerConfig['url'] ?? 'https://api.x.ai/v1/responses');
        $this->model = $documentConfig['model'] ?? ($providerConfig['model'] ?? 'grok-4-1-fast-reasoning');

        $this->defaultOptions = [
            'temperature' => $documentConfig['temperature'] ?? ($providerConfig['temperature'] ?? 0.7),
            'format' => $documentConfig['format'] ?? 'pdf',
        ];
    }

    public function generateDocument(string $prompt, string $toolName, ?array $options = []): ?string
    {
        return $this->requestGrokAI($prompt, $toolName, $options ?? []);
    }

    private function requestGrokAI(string $prompt, string $toolName, array $options = []): ?string
    {
        $userId = $this->getUserId();
        if (!$userId) {
            throw new RuntimeException('User not authenticated.');
        }

        $systemContent = 'You are a helpful assistant that generates well-structured documents. Output in the specified format (e.g., Markdown for easy conversion to PDF).';

        $userContent = $prompt;
        $format = $options['format'] ?? $this->defaultOptions['format'];
        if (!empty($format)) {
            $userContent .= "\n\nOutput the document in {$format} format.";
        }

        $payload = [
            'model' => $this->model,
            'input' => [
                ['role' => 'system', 'content' => $systemContent],
                ['role' => 'user', 'content' => $userContent],
            ],
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

            if (isset($data['error'])) {
                throw new RuntimeException('API error: '.$data['error']['message']);
            }

            if (isset($data['output'][0]['content'][0]['text'])) {
                $generatedContent = (string) $data['output'][0]['content'][0]['text'];

                $usage = $data['usage'] ?? ['prompt_tokens' => 0, 'completion_tokens' => 0, 'total_tokens' => 0];

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

                return $generatedContent;
            }

            return null;
        } catch (Exception $e) {
            error_log('[AI][Grok][Document] Exception: '.$e->getMessage());

            return null;
        }
    }

    private function getUserId(): ?int
    {
        $user = $this->security->getUser();

        return $user instanceof UserInterface ? $user->getId() : null;
    }
}
