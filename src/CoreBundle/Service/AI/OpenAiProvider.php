<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\AI;

use Chamilo\CoreBundle\Settings\SettingsManager;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class OpenAiProvider implements AiProviderInterface
{
    private string $apiUrl;
    private string $apiKey;
    private string $model;
    private float $temperature;
    private string $organizationId;
    private int $monthlyTokenLimit;
    private HttpClientInterface $httpClient;

    public function __construct(HttpClientInterface $httpClient, SettingsManager $settingsManager)
    {
        $this->httpClient = $httpClient;

        // Get AI providers from settings
        $configJson = $settingsManager->getSetting('ai_helpers.ai_providers', true);
        $config = json_decode($configJson, true) ?? [];

        if (!isset($config['openai'])) {
            throw new \RuntimeException('OpenAI configuration is missing.');
        }

        $this->apiUrl = $config['openai']['url'] ?? 'https://api.openai.com/v1';
        $this->apiKey = $config['openai']['api_key'] ?? '';
        $this->model = $config['openai']['model'] ?? 'gpt-3.5-turbo';
        $this->temperature = $config['openai']['temperature'] ?? 0.7;
        $this->organizationId = $config['openai']['organization_id'] ?? '';
        $this->monthlyTokenLimit = $config['openai']['monthly_token_limit'] ?? 10000;

        if (empty($this->apiKey)) {
            throw new \RuntimeException('OpenAI API key is missing.');
        }
    }

    public function generateQuestions(string $topic, int $numQuestions, string $questionType, string $language): ?string
    {
        $prompt = sprintf(
            'Generate %d "%s" questions in Aiken format in the %s language about "%s".',
            $numQuestions, $questionType, $language, $topic
        );

        $payload = [
            'model' => $this->model,
            'prompt' => $prompt,
            'temperature' => $this->temperature,
            'max_tokens' => 2000,
            'frequency_penalty' => 0,
            'presence_penalty' => 0.6,
            'top_p' => 1.0,
        ];

        try {
            $response = $this->httpClient->request('POST', $this->apiUrl . '/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'body' => json_encode($payload),
            ]);

            $statusCode = $response->getStatusCode();
            $responseContent = $response->getContent(false);

            if ($statusCode === 200) {
                $data = json_decode($responseContent, true);

                return $data['choices'][0]['text'] ?? null;
            }

            $errorData = json_decode($responseContent, true);

            if (isset($errorData['error']['code'])) {
                switch ($errorData['error']['code']) {
                    case 'insufficient_quota':
                        throw new \Exception("You have exceeded your OpenAI quota. Please check your OpenAI plan.");
                    case 'invalid_api_key':
                        throw new \Exception("Invalid API key. Please check your OpenAI configuration.");
                    case 'server_error':
                        throw new \Exception("OpenAI encountered an internal error. Try again later.");
                    default:
                        throw new \Exception("An error occurred: " . $errorData['error']['message']);
                }
            }

            throw new \Exception("Unexpected error from OpenAI.");

        } catch (\Exception $e) {
            error_log("ERROR - OpenAI Request failed: " . $e->getMessage());
            return "Error: " . $e->getMessage();
        }
    }
}
