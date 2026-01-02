<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\AiProvider;

use Chamilo\CoreBundle\Entity\AiRequests;
use Chamilo\CoreBundle\Repository\AiRequestsRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Exception;
use RuntimeException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Grok (xAI) provider for document generation.
 * Note: As of January 2026, document generation is not natively supported in the xAI API as binary files (e.g., PDF).
 * This implementation uses the text responses endpoint to generate structured text content (e.g., Markdown),
 * which can be post-processed into a document format client-side.
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

    public function __construct(
        HttpClientInterface $httpClient,
        AiRequestsRepository $aiRequestsRepository,
        SettingsManager $settingsManager,
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
        if (!isset($config['grok']['document'])) {
            throw new RuntimeException('Grok configuration for document generation is missing.');
        }
        $grokConfig = $config['grok'];

        $this->apiUrl = $grokConfig['url'] ?? 'https://api.x.ai/v1/responses';
        $this->apiKey = $grokConfig['api_key'] ?? '';
        $this->model = $grokConfig['model'] ?? 'grok-4-1-fast-reasoning';

        if (empty($this->apiKey)) {
            throw new RuntimeException('Grok API key is missing.');
        }

        $this->defaultOptions = [
            'temperature' => $grokConfig['temperature'] ?? 0.7,
            'format' => $grokConfig['format'] ?? 'pdf', // e.g., 'markdown', 'pdf' (for prompt instruction)
        ];
    }

    public function generateDocument(string $prompt, string $toolName, ?array $options = []): ?string
    {
        return $this->requestGrokAI($prompt, $toolName, $options);
    }

    private function requestGrokAI(string $prompt, string $toolName, array $options = []): ?string
    {
        $userId = $this->getUserId();
        if (!$userId) {
            throw new RuntimeException('User not authenticated.');
        }

        // Build system prompt to instruct document generation
        $systemContent = 'You are a helpful assistant that generates well-structured documents. Output in the specified format (e.g., Markdown for easy conversion to PDF).';

        // Append format instruction to user prompt if provided
        $userContent = $prompt;
        $format = $options['format'] ?? $this->defaultOptions['format'];
        if ($format) {
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

            // Check for error key first
            if (isset($data['error'])) {
                throw new RuntimeException('API error: '.$data['error']['message']);
            }

            // Extract generated content from response structure
            if (isset($data['output'][0]['content'][0]['text'])) {
                $generatedContent = $data['output'][0]['content'][0]['text'];

                // Usage is available for text generation
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
        return $this->user ? $this->user->getId() : null;
    }
}
