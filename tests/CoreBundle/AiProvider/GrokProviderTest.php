<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\AiProvider;

use Chamilo\CoreBundle\AiProvider\GrokProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

final class GrokProviderTest extends TestCase
{
    public function testTextTimeoutDefaultsToThreeMinutes(): void
    {
        $reflection = new ReflectionClass(GrokProvider::class);

        self::assertSame(180.0, $reflection->getConstant('DEFAULT_TEXT_TIMEOUT'));
    }

    public function testResolveTextOptionsUsesConfiguredTimeoutAndAllowsPerRequestOverride(): void
    {
        $reflection = new ReflectionClass(GrokProvider::class);
        $provider = $reflection->newInstanceWithoutConstructor();

        $this->setProperty($provider, 'textApiUrl', 'https://api.x.ai/v1/chat/completions');
        $this->setProperty($provider, 'textModel', 'grok-test');
        $this->setProperty($provider, 'textTemperature', 0.2);
        $this->setProperty($provider, 'textMaxTokens', 12000);
        $this->setProperty($provider, 'textTimeout', 180.0);

        $method = new ReflectionMethod(GrokProvider::class, 'resolveTextOptions');

        $defaults = $method->invoke($provider, []);
        self::assertSame(180.0, $defaults['timeout']);

        $overridden = $method->invoke($provider, ['timeout' => 300]);
        self::assertSame(300.0, $overridden['timeout']);

        $invalid = $method->invoke($provider, ['timeout' => 0]);
        self::assertSame(180.0, $invalid['timeout']);
    }

    private function setProperty(GrokProvider $provider, string $property, mixed $value): void
    {
        $reflection = new ReflectionProperty(GrokProvider::class, $property);
        $reflection->setValue($provider, $value);
    }
}
