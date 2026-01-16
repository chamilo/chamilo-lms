<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\AiProvider;

use Chamilo\CoreBundle\Repository\AiRequestsRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Exception;
use InvalidArgumentException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class AiProviderFactory
{
    /**
     * @var array<string, array<string, object>>
     */
    private array $providers = [];

    /**
     * @var array<string, array<string, object>>
     */
    private array $providersByType = [];

    private string $defaultProvider;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly SettingsManager $settingsManager,
        private readonly AiRequestsRepository $aiRequestsRepository,
        private readonly Security $security
    ) {
        $config = $this->readProvidersConfig();

        // Default provider = first key in config (if any)
        $this->defaultProvider = array_key_first($config) ?? 'openai';

        // Provider name -> class prefix
        $possibleProviders = [
            'openai' => 'OpenAi',
            'deepseek' => 'DeepSeek',
            'grok' => 'Grok',
            'mistral' => 'Mistral',
            'gemini' => 'Gemini',
        ];

        // Suffix used only if you create type-specific classes (optional)
        $typeSuffix = [
            'text' => '',
            'image' => 'Image',
            'video' => 'Video',
            'document' => 'Document',
            'document_process' => 'DocumentProcess',
        ];

        // Expected interface per known type
        $typeInterface = [
            'text' => AiProviderInterface::class,
            'image' => AiImageProviderInterface::class,
            'video' => AiVideoProviderInterface::class,
            'document' => AiDocumentProviderInterface::class,
            // 'document_process' intentionally not validated (no interface yet)
        ];

        $this->providers = [];
        $this->providersByType = [];

        foreach ($config as $providerName => $providerConfig) {
            if (!isset($possibleProviders[$providerName])) {
                error_log('[AI] Unsupported provider in config: "'.$providerName.'". Skipping.');

                continue;
            }

            if (!\is_array($providerConfig)) {
                error_log('[AI] Provider config for "'.$providerName.'" must be an array. Skipping.');

                continue;
            }

            $providerPrefix = $possibleProviders[$providerName];

            // Base provider class (e.g. OpenAiProvider)
            $baseClass = 'Chamilo\CoreBundle\AiProvider\\'.$providerPrefix.'Provider';
            $baseObject = $this->instantiateProvider($baseClass);

            // If base class cannot be instantiated, we can still allow type-specific classes.
            if (!$baseObject && !class_exists($baseClass)) {
                error_log('[AI] Base provider class not found: '.$baseClass.'.');
            } elseif (!$baseObject) {
                error_log('[AI] Base provider class exists but could not be instantiated: '.$baseClass.'.');
            }

            foreach ($typeSuffix as $type => $suffix) {
                // Only types explicitly present in provider config are considered enabled
                if (!\array_key_exists($type, $providerConfig)) {
                    continue;
                }

                $typeClass = 'Chamilo\CoreBundle\AiProvider\\'.$providerPrefix.$suffix.'Provider';

                // Known types: we can fallback to base provider if it implements the right interface
                if (isset($typeInterface[$type])) {
                    $iface = $typeInterface[$type];
                    $obj = null;

                    // 1) Prefer type-specific class if exists (optional architecture)
                    if ($typeClass !== $baseClass && class_exists($typeClass)) {
                        $obj = $this->instantiateProvider($typeClass);

                        if ($obj && !($obj instanceof $iface)) {
                            error_log('[AI] Provider "'.$providerName.'" type-class "'.$typeClass.'" does not implement '.$iface.'. Falling back to base provider.');
                            $obj = null;
                        }
                    }

                    // 2) Fallback to base provider
                    if (!$obj) {
                        if ($baseObject && ($baseObject instanceof $iface)) {
                            $obj = $baseObject;

                            if ($typeClass !== $baseClass && !class_exists($typeClass)) {
                                error_log('[AI] Provider "'.$providerName.'" uses base provider for type "'.$type.'" (no dedicated type class found).');
                            }
                        } else {
                            error_log('[AI] Provider "'.$providerName.'" is configured for type "'.$type.'" but no usable implementation was found (expected '.$iface.').');

                            continue;
                        }
                    }

                    $this->providers[$providerName][$type] = $obj;
                    $this->providersByType[$type][$providerName] = $obj;

                    continue;
                }

                // Unknown type (e.g. document_process): do NOT fallback to base provider.
                // Require a dedicated class to avoid claiming support when methods don't exist.
                if ($typeClass !== $baseClass && class_exists($typeClass)) {
                    $obj = $this->instantiateProvider($typeClass);

                    if (!$obj) {
                        error_log('[AI] Provider "'.$providerName.'" is configured for unknown type "'.$type.'" but could not instantiate "'.$typeClass.'".');

                        continue;
                    }

                    $this->providers[$providerName][$type] = $obj;
                    $this->providersByType[$type][$providerName] = $obj;
                } else {
                    error_log('[AI] Provider "'.$providerName.'" is configured for unknown type "'.$type.'" but no dedicated class was found (expected "'.$typeClass.'").');
                }
            }
        }

        // Ensure default provider exists when config is not empty
        if (!empty($config) && !isset($this->providers[$this->defaultProvider])) {
            throw new InvalidArgumentException("The default AI provider '{$this->defaultProvider}' is not configured properly.");
        }
    }

    public function hasProvidersForType(string $serviceType): bool
    {
        return !empty($this->providersByType[$serviceType] ?? []);
    }

    /**
     * @return string[] Provider names supporting the given service type
     */
    public function getProvidersForType(string $serviceType): array
    {
        return array_keys($this->providersByType[$serviceType] ?? []);
    }

    public function getProvider(?string $provider = null, ?string $serviceType = 'text'): object
    {
        $serviceType = $serviceType ?? 'text';

        if (null === $provider) {
            if (isset($this->providers[$this->defaultProvider][$serviceType])) {
                $provider = $this->defaultProvider;
            } else {
                $provider = array_key_first($this->providersByType[$serviceType] ?? []);
            }
        }

        if (empty($provider) || !isset($this->providers[$provider])) {
            throw new InvalidArgumentException("AI Provider '{$provider}' is not supported or not configured.");
        }

        if (!isset($this->providers[$provider][$serviceType])) {
            throw new InvalidArgumentException("AI Provider '{$provider}' is not supported for service type '{$serviceType}'.");
        }

        return $this->providers[$provider][$serviceType];
    }

    /**
     * Read and normalize JSON config from settings.
     *
     * @return array<string, mixed>
     */
    private function readProvidersConfig(): array
    {
        $configJson = $this->settingsManager->getSetting('ai_helpers.ai_providers', true);

        if (\is_string($configJson)) {
            return json_decode($configJson, true) ?? [];
        }

        if (\is_array($configJson)) {
            return $configJson;
        }

        return [];
    }

    /**
     * Instantiate a provider class using the unified constructor signature.
     */
    private function instantiateProvider(string $fqcn): ?object
    {
        if (!class_exists($fqcn)) {
            return null;
        }

        try {
            return new $fqcn(
                $this->httpClient,
                $this->settingsManager,
                $this->aiRequestsRepository,
                $this->security
            );
        } catch (Exception $e) {
            error_log('[AI] Could not instantiate '.$fqcn.': '.$e->getMessage());

            return null;
        }
    }
}
