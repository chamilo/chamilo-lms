<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\AiProvider;

use Chamilo\CoreBundle\Repository\AiRequestsRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use InvalidArgumentException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AiProviderFactory
{
    private array $providers;
    private string $defaultProvider;
    private AiRequestsRepository $aiRequestsRepository;
    private Security $security;

    public function __construct(
        HttpClientInterface $httpClient,
        SettingsManager $settingsManager,
        AiRequestsRepository $aiRequestsRepository,
        Security $security
    ) {
        $this->aiRequestsRepository = $aiRequestsRepository;
        $this->security = $security;

        // Get AI providers from settings
        $configJson = $settingsManager->getSetting('ai_helpers.ai_providers', true);
        $config = json_decode($configJson, true) ?? [];

        // Get the first available provider as default
        $this->defaultProvider = array_key_first($config) ?? 'openai';

        // Initialize AI providers dynamically
        $this->providers = [];
        foreach ($config as $providerName => $providerConfig) {
            if ('openai' === $providerName) {
                $this->providers[$providerName] = new OpenAiProvider($httpClient, $settingsManager, $this->aiRequestsRepository, $this->security);
            } elseif ('deepseek' === $providerName) {
                $this->providers[$providerName] = new DeepSeekAiProvider($httpClient, $settingsManager, $this->aiRequestsRepository, $this->security);
            }
        }

        // Ensure the selected default provider exists
        if (!isset($this->providers[$this->defaultProvider])) {
            throw new InvalidArgumentException("The default AI provider '{$this->defaultProvider}' is not configured properly.");
        }
    }

    public function getProvider(?string $provider = null): AiProviderInterface
    {
        $provider = $provider ?? $this->defaultProvider;

        if (!isset($this->providers[$provider])) {
            throw new InvalidArgumentException("AI Provider '$provider' is not supported.");
        }

        return $this->providers[$provider];
    }
}
