<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service;

use Chamilo\CoreBundle\Service\AdminStatistics\AdminStatisticsUserSystemQueryService;
use Chamilo\Tests\ChamiloTestTrait;
use DateTimeImmutable;
use DateTimeZone;
use ReflectionMethod;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * The daily login chart of the "recent logins" report. The 31-day window is computed from the wall clock, so the
 * chart builder is called directly with a FIXED start bound: the assertions do not depend on the hour the suite runs.
 */
final class AdminStatisticsLoginChartTest extends KernelTestCase
{
    use ChamiloTestTrait;
    use RawReportingFixturesTrait;

    /**
     * @return array<string, int>
     */
    private function dailyCounts(DateTimeImmutable $start, DateTimeImmutable $end, bool $distinct = false): array
    {
        $service = self::getContainer()->get(AdminStatisticsUserSystemQueryService::class);
        $method = new ReflectionMethod($service, 'getDailyLoginCounts');
        $this->fresh();

        return $method->invoke($service, $start, $end, $distinct, 0);
    }

    private function login(int $userId, string $in, string $out): void
    {
        $this->conn()->executeStatement(
            "INSERT INTO track_e_login (login_user_id, login_date, user_ip, logout_date) VALUES (?, ?, '', ?)",
            [$userId, $in, $out]
        );
    }

    /**
     * The lower bound is a 24-hour clock instant: with a start bound of 15:30, a login at 10:00 the same day lies
     * before it (formatted with the 12-hour "h" it became 03:30 and let that login in). One login after the bound
     * proves the boundary is not simply shifted the other way.
     */
    public function testStartBoundUsesATwentyFourHourClock(): void
    {
        self::bootKernel();
        $user = $this->rawUser('r2lc');
        $this->login($user, '2001-12-10 10:00:00', '2001-12-10 10:30:00');
        $this->login($user, '2001-12-10 16:00:00', '2001-12-10 16:30:00');
        $this->login($user, '2001-12-11 09:00:00', '2001-12-11 09:30:00');
        $start = new DateTimeImmutable('2001-12-10 15:30:00', new DateTimeZone('UTC'));
        $end = new DateTimeImmutable('2001-12-13 15:30:00', new DateTimeZone('UTC'));

        $counts = $this->dailyCounts($start, $end);

        $this->assertSame(1, $counts['12-10'], 'the 10:00 login is before the 15:30 bound');
        $this->assertSame(1, $counts['12-11']);
        $this->assertSame(0, $counts['12-12']);
        $this->assertSame(['12-10', '12-11', '12-12'], array_keys($counts));
    }
}
