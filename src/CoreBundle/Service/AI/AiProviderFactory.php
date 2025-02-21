<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Service\AI;

use Chamilo\CoreBundle\Settings\SettingsManager;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use InvalidArgumentException;

class AiProviderFactory
{
    private array $providers;
    private string $defaultProvider;

    public function __construct(HttpClientInterface $httpClient, SettingsManager $settingsManager)
    {
        // Get AI providers from settings
        $configJson = $settingsManager->getSetting('ai_helpers.ai_providers', true);
        $config = json_decode($configJson, true) ?? [];

        // Get the first available provider as default
        $this->defaultProvider = array_key_first($config) ?? 'openai';

        // Initialize AI providers dynamically
        $this->providers = [];
        foreach ($config as $providerName => $providerConfig) {
            if ($providerName === 'openai') {
                $this->providers[$providerName] = new OpenAiProvider($httpClient, $settingsManager);
            } elseif ($providerName === 'deepseek') {
                $this->providers[$providerName] = new DeepSeekAiProvider($httpClient, $settingsManager);
            }
        }

        // Ensure the selected default provider exists
        if (!isset($this->providers[$this->defaultProvider])) {
            throw new InvalidArgumentException("The default AI provider '{$this->defaultProvider}' is not configured properly.");
        }
    }

    public function getProvider(string $provider = null): AiProviderInterface
    {
        $provider = $provider ?? $this->defaultProvider;

        if (!isset($this->providers[$provider])) {
            throw new InvalidArgumentException("AI Provider '$provider' is not supported.");
        }

        return $this->providers[$provider];
    }
}
