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
    private array $providersByType;
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

        // Get the first available provider from configuration as default
        $this->defaultProvider = array_key_first($config) ?? 'openai';

        // Define an array of types
        $serviceTypes = [
            'text' => '',
            'image' => 'Image',
            'video' => 'Video',
            'document' => 'Document',
            'document_process' => 'DocumentProcess',
        ];
        $possibleProviders = [
            'openai' => 'OpenAi',
            'deepseek' => 'DeepSeek',
            'grok' => 'Grok',
            'mistral' => 'Mistral',
            'gemini' => 'Gemini',
        ];
        // Initialize AI providers dynamically
        // Each available provider will have a form like ['deepseek']['image'] = Chamilo\CoreBundle\AiProvider\DeepSeekImageProvider $object;
        $this->providers = [];
        // For practical purposes, we also build a providersByType array in reverse order: ['image']['deepseek'] = Chamilo\CoreBundle\AiProvider\DeepSeekImageProvider $object;
        foreach ($config as $providerName => $providerConfig) {
            if (in_array($providerName, array_keys($possibleProviders))) {
                $providerPrefix = $possibleProviders[$providerName];
                foreach ($serviceTypes as $type => $name) {
                    $className = $providerPrefix.$name.'Provider';
                    if (class_exists($className)) {
                        $providerObject = new $className($httpClient, $settingsManager, $this->aiRequestsRepository, $this->security);
                        $this->providers[$providerName][$type] = $providerObject;
                        $this->providersByType[$type][$providerName] = $providerObject;
                    }
                }
            }
        }

        // Ensure the selected default provider exists
        if (!isset($this->providers[$this->defaultProvider])) {
            throw new InvalidArgumentException("The default AI provider '{$this->defaultProvider}' is not configured properly.");
        }
    }

    public function getProvider(?string $provider = null, ?string $serviceType = 'text'): AiProviderInterface
    {
        $provider = $provider ?? $this->defaultProvider;

        if (!isset($this->providers[$provider])) {
            throw new InvalidArgumentException("AI Provider '$provider' is not supported.");
        }
        if (!isset($this->providers[$provider][$serviceType])) {
            throw new InvalidArgumentException("AI Provider '$provider' is not supported for service type '$serviceType'.");
        }

        return $this->providers[$provider][$serviceType];
    }
}
