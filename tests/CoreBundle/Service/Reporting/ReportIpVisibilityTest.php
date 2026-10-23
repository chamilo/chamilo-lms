<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Service\Reporting;

use Chamilo\CoreBundle\Service\Reporting\ReportIpVisibility;
use Chamilo\CoreBundle\Settings\SettingsManager;
use LogicException;
use PHPUnit\Framework\TestCase;

final class ReportIpVisibilityTest extends TestCase
{
    /**
     * @dataProvider visibilityCases
     */
    public function testGlobalAndExercisePrivacySettings(
        string $globalValue,
        string $exerciseValue,
        bool $expectedGlobalHidden,
        bool $expectedExerciseHidden,
    ): void {
        $settings = $this->createMock(SettingsManager::class);
        $settings->method('getSetting')
            ->willReturnCallback(static function (string $name, bool $loadFromDb) use ($globalValue, $exerciseValue): string {
                self::assertTrue($loadFromDb);

                return match ($name) {
                    'tracking.hide_ip_address' => $globalValue,
                    'exercise.exercise_hide_ip' => $exerciseValue,
                    default => throw new LogicException('Unexpected setting: '.$name),
                };
            })
        ;

        $visibility = new ReportIpVisibility($settings);

        self::assertSame($expectedGlobalHidden, $visibility->isHidden());
        self::assertSame($expectedExerciseHidden, $visibility->isExerciseHidden());
    }

    public static function visibilityCases(): iterable
    {
        yield 'global privacy enabled' => ['true', 'false', true, true];

        yield 'exercise privacy enabled' => ['false', 'true', false, true];

        yield 'both settings disabled' => ['false', 'false', false, false];

        yield 'both settings enabled' => ['true', 'true', true, true];
    }
}
