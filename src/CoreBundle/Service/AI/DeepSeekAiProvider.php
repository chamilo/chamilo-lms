<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Service\AI;

use Chamilo\CoreBundle\Settings\SettingsManager;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class DeepSeekAiProvider implements AiProviderInterface
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

        if (!isset($config['deepseek'])) {
            throw new \RuntimeException('DeepSeek configuration is missing.');
        }

        $this->apiUrl = $config['deepseek']['url'] ?? 'https://api.deepseek.com/chat/completions';
        $this->apiKey = $config['deepseek']['api_key'] ?? '';
        $this->model = $config['deepseek']['model'] ?? 'deepseek-chat';
        $this->temperature = $config['deepseek']['temperature'] ?? 0.7;
        $this->organizationId = $config['deepseek']['organization_id'] ?? '';
        $this->monthlyTokenLimit = $config['deepseek']['monthly_token_limit'] ?? 5000;

        if (empty($this->apiKey)) {
            throw new \RuntimeException('DeepSeek API key is missing.');
        }
    }

    public function generateQuestions(string $topic, int $numQuestions, string $questionType, string $language): ?string
    {
        $prompt = sprintf(
            'Generate %d "%s" questions in Aiken format in the %s language about "%s", making sure there is a \'ANSWER\' line for each question. \'ANSWER\' lines must only mention the letter of the correct answer, not the full answer text and not a parenthesis. The line starting with \'ANSWER\' must not be separated from the last possible answer by a blank line. Each answer starts with an uppercase letter, a dot, one space and the answer text without quotes. Include an \'ANSWER_EXPLANATION\' line after the \'ANSWER\' line for each question. The terms between single quotes above must not be translated. There must be a blank line between each question.',
            $numQuestions, $questionType, $language, $topic
        );

        try {
            $response = $this->httpClient->request('POST', $this->apiUrl, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => $this->model,
                    'messages' => [
                        ['role' => 'system', 'content' => 'You are a helpful assistant that generates Aiken format questions.'],
                        ['role' => 'user', 'content' => $prompt]
                    ],
                    'temperature' => $this->temperature,
                    'max_tokens' => 1000,
                ],
            ]);

            $statusCode = $response->getStatusCode();
            $data = $response->toArray();

            if ($statusCode === 200 && isset($data['choices'][0]['message']['content'])) {
                return $data['choices'][0]['message']['content'];
            }

            return null;

        } catch (\Exception $e) {
            error_log("ERROR - DeepSeek Request failed: " . $e->getMessage());
            return null;
        }
    }
}
