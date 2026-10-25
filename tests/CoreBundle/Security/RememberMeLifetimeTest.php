<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Security;

use Chamilo\CoreBundle\Security\RememberMeLifetime;
use Chamilo\CoreBundle\Settings\SettingsManager;
use PHPUnit\Framework\TestCase;

final class RememberMeLifetimeTest extends TestCase
{
    /**
     * @dataProvider lifetimeCases
     */
    public function testConfiguredLifetime(mixed $value, int $expected): void
    {
        $settings = $this->createMock(SettingsManager::class);
        $settings->expects(self::once())
            ->method('getSetting')
            ->with('security.remember_me_days', true)
            ->willReturn($value)
        ;

        self::assertSame($expected, (new RememberMeLifetime($settings))->getSeconds());
    }

    public static function lifetimeCases(): iterable
    {
        yield 'upgrade without setting' => [null, 14 * 86400];

        yield 'disabled' => ['0', 0];

        yield 'one day' => ['1', 86400];

        yield 'custom duration' => ['30', 30 * 86400];

        yield 'maximum duration' => ['90', 90 * 86400];

        yield 'negative' => ['-1', 0];

        yield 'too long' => ['91', 0];

        yield 'not numeric' => ['abc', 0];

        yield 'decimal' => ['1.5', 0];

        yield 'unexpected type' => [['14'], 0];
    }
}
