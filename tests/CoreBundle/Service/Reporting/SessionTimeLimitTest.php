<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service\Reporting;

use Chamilo\CoreBundle\Service\Reporting\SessionTimeLimit;
use Chamilo\CoreBundle\Settings\SettingsManager;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SessionTimeLimitTest extends TestCase
{
    public function testConfiguredMinutesDetermineCapInSeconds(): void
    {
        self::assertSame(7200, SessionTimeLimit::secondsForMinutes(120));
        self::assertSame(21600, SessionTimeLimit::secondsForMinutes(360));
        self::assertSame(21600, SessionTimeLimit::secondsForMinutes(0));
        self::assertSame(21600, SessionTimeLimit::secondsForMinutes(-5));
    }

    public function testReadsTheConfiguredEffectiveAccessUrlThreshold(): void
    {
        $settings = $this->createMock(SettingsManager::class);
        $settings->expects(self::once())
            ->method('getSetting')
            ->with('tracking.max_time_spent_per_session_threshold', true)
            ->willReturn('120')
        ;

        $limit = new SessionTimeLimit($settings);
        self::assertSame(7200, $limit->getMaximumSeconds());
        self::assertSame(0, SessionTimeLimit::clampSeconds(-60, 7200));
    }

    public function testQueryCapsEachRecordedSessionBeforeSumming(): void
    {
        $expression = SessionTimeLimit::sqlForSeconds('login_date', 'logout_date', 21600);
        self::assertSame('LEAST(GREATEST(TIMESTAMPDIFF(SECOND, login_date, logout_date), 0), 21600)', $expression);

        $durations = [8 * 3600, 2 * 3600, 8 * 3600, -60];
        $total = array_sum(array_map(static fn (int $duration): int => SessionTimeLimit::clampSeconds($duration, 21600), $durations));
        self::assertSame(14 * 3600, $total);
    }

    public function testQuerySupportsNullableLogoutWithoutCountingOpenConnections(): void
    {
        $expression = SessionTimeLimit::sqlForSeconds(
            'login.login_date',
            'COALESCE(login.logout_date, login.login_date)',
            7200,
        );
        self::assertStringContainsString('COALESCE(login.logout_date, login.login_date)', $expression);
        self::assertStringEndsWith(', 7200)', $expression);
    }

    public function testSqlIdentifiersCannotContainUserInput(): void
    {
        $this->expectException(InvalidArgumentException::class);
        SessionTimeLimit::sqlForSeconds('login_date); DROP TABLE user; --', 'logout_date', 3600);
    }
}
