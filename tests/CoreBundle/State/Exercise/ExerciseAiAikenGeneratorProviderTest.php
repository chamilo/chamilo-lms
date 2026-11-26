<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\State\Exercise;

use Chamilo\CoreBundle\AiProvider\AiProviderFactory;
use Chamilo\CoreBundle\AiProvider\AiProviderInterface;
use Chamilo\CoreBundle\Repository\AiRequestsRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CoreBundle\State\Exercise\ExerciseAiAikenGeneratorProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Contracts\HttpClient\HttpClientInterface;

use const JSON_THROW_ON_ERROR;

final class ExerciseAiAikenGeneratorProviderTest extends TestCase
{
    public function testTextOnlyProviderIsAvailable(): void
    {
        $config = ['grok' => $this->textConfig()];
        [$provider, $factory] = $this->createProvider(json_encode($config, JSON_THROW_ON_ERROR));

        self::assertSame(['grok' => 'grok (test-model)'], $this->textOptions($provider));
        self::assertSame([], $this->documentOptions($provider));
        self::assertInstanceOf(AiProviderInterface::class, $factory->getProvider('grok', 'text'));
    }

    public function testTextAndDocumentProviderRemainsAvailable(): void
    {
        $config = ['grok' => $this->textConfig() + ['document' => ['model' => 'document-model']]];
        [$provider] = $this->createProvider($config);

        self::assertSame(['grok' => 'grok (test-model)'], $this->textOptions($provider));
    }

    public function testDocumentProcessingOptionsRemainSeparate(): void
    {
        $config = ['openai' => $this->textConfig() + ['document_process' => ['model' => 'document-model']]];
        [$provider] = $this->createProvider($config);

        self::assertSame(['openai' => 'openai (test-model)'], $this->textOptions($provider));
        self::assertSame(['openai' => 'openai (document-model)'], $this->documentOptions($provider));
    }

    public function testUnusableTextProvidersAreNotListed(): void
    {
        $config = [
            'unknown' => $this->textConfig(),
            'grok' => ['text' => ['model' => 'missing-key']],
            'openai' => ['api_key' => 'test-key', 'text' => 'invalid'],
        ];
        [$provider] = $this->createProvider($config);

        self::assertSame([], $this->textOptions($provider));
    }

    public function testDocumentOnlyAndLegacyRootConfigurationAreNotTextProviders(): void
    {
        $config = [
            'openai' => ['api_key' => 'test-key', 'document_process' => ['model' => 'document-model']],
            'grok' => ['api_key' => 'test-key', 'model' => 'legacy-model'],
        ];
        [$provider] = $this->createProvider($config);

        self::assertSame([], $this->textOptions($provider));
        self::assertSame(['openai' => 'openai (document-model)'], $this->documentOptions($provider));
    }

    public function testInvalidJsonDoesNotExposeProviders(): void
    {
        [$provider] = $this->createProvider('{invalid');

        self::assertSame([], $this->textOptions($provider));
        self::assertSame([], $this->documentOptions($provider));
    }

    /**
     * @return array{api_key: string, text: array{model: string}}
     */
    private function textConfig(): array
    {
        return ['api_key' => 'test-key', 'text' => ['model' => 'test-model']];
    }

    /**
     * @return array{ExerciseAiAikenGeneratorProvider, AiProviderFactory}
     */
    private function createProvider(array|string $config): array
    {
        $settings = $this->createMock(SettingsManager::class);
        $settings->method('getSetting')->willReturn($config);
        $factory = new AiProviderFactory(
            $this->createMock(HttpClientInterface::class),
            $settings,
            $this->createMock(AiRequestsRepository::class),
            $this->createMock(Security::class)
        );

        // Only provider discovery is under test; no course or request context is needed.
        $provider = (new ReflectionClass(ExerciseAiAikenGeneratorProvider::class))->newInstanceWithoutConstructor();
        (new ReflectionProperty(ExerciseAiAikenGeneratorProvider::class, 'settingsManager'))->setValue($provider, $settings);
        (new ReflectionProperty(ExerciseAiAikenGeneratorProvider::class, 'aiProviderFactory'))->setValue($provider, $factory);

        return [$provider, $factory];
    }

    private function textOptions(ExerciseAiAikenGeneratorProvider $provider): array
    {
        return (new ReflectionMethod($provider, 'getTextProviderOptions'))->invoke($provider);
    }

    private function documentOptions(ExerciseAiAikenGeneratorProvider $provider): array
    {
        return (new ReflectionMethod($provider, 'getDocumentProviderOptions'))->invoke($provider);
    }
}
