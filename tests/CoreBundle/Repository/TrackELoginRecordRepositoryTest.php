<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Repository;

use Chamilo\CoreBundle\Repository\TrackELoginRecordRepository;
use Chamilo\Tests\ChamiloTestTrait;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Characterization of the failed-login reports of the admin security page (/admin/security/login-attempts).
 * Rows are tagged with "sv2_" usernames and documentation-range IPs (203.0.113.x) so that the assertions only look
 * at the fixtures; day-relative windows are anchored on the database CURRENT_DATE, the clock the SQL itself uses.
 */
final class TrackELoginRecordRepositoryTest extends KernelTestCase
{
    use ChamiloTestTrait;

    private TrackELoginRecordRepository $repo;
    private string $today;
    private string $tag;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->repo = self::getContainer()->get(TrackELoginRecordRepository::class);
        $this->today = (string) $this->conn()->fetchOne('SELECT CURRENT_DATE');
        $this->tag = 'sv2_'.getmypid().'_'.random_int(1000, 999999);
    }

    private function conn(): Connection
    {
        return $this->getEntityManager()->getConnection();
    }

    private function dayOffset(int $days): string
    {
        return (string) $this->conn()->fetchOne('SELECT CURRENT_DATE - INTERVAL '.(int) $days.' DAY');
    }

    private function record(string $username, string $ip, bool $success, string $date): void
    {
        $this->conn()->executeStatement(
            'INSERT INTO track_e_login_record (username, login_date, user_ip, success) VALUES (?, ?, ?, ?)',
            [$username, $date, $ip, $success ? 1 : 0]
        );
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    private function rowFor(array $rows, string $key, string $value): ?array
    {
        foreach ($rows as $row) {
            if ((string) $row[$key] === $value) {
                return $row;
            }
        }

        return null;
    }

    public function testAddTrackLoginStoresUsernameIpAndSuccessFlag(): void
    {
        $this->repo->addTrackLogin($this->tag.'_a', '203.0.113.1', false);
        $this->repo->addTrackLogin($this->tag.'_b', '203.0.113.2', true);

        $a = $this->conn()->fetchAssociative('SELECT * FROM track_e_login_record WHERE username = ?', [$this->tag.'_a']);
        $b = $this->conn()->fetchAssociative('SELECT * FROM track_e_login_record WHERE username = ?', [$this->tag.'_b']);

        $this->assertSame('203.0.113.1', $a['user_ip']);
        $this->assertSame('0', (string) $a['success']);
        $this->assertSame('1', (string) $b['success']);
        $this->assertNotEmpty($a['login_date']);
    }

    public function testTopUsernamesCountsOnlyFailuresInsideTheWindowOrderedByCount(): void
    {
        $in = $this->dayOffset(2).' 10:00:00';
        $out = $this->dayOffset(40).' 10:00:00';
        $many = $this->tag.'_many';
        $few = $this->tag.'_few';
        for ($i = 0; $i < 3; $i++) {
            $this->record($many, '203.0.113.10', false, $in);
        }
        $this->record($many, '203.0.113.10', true, $in);   // success is not a failure
        $this->record($many, '203.0.113.10', false, $out); // outside the 30 day window
        $this->record($few, '203.0.113.11', false, $in);

        $rows = $this->repo->topUsernames(30, 100000);
        $this->assertSame('3', (string) $this->rowFor($rows, 'username', $many)['failed']);
        $this->assertSame('1', (string) $this->rowFor($rows, 'username', $few)['failed']);

        $names = array_column($rows, 'username');
        $this->assertLessThan(array_search($few, $names, true), array_search($many, $names, true), 'Highest count first');

        $counts = array_map('intval', array_column($rows, 'failed'));
        $sorted = $counts;
        rsort($sorted);
        $this->assertSame($sorted, $counts);
    }

    public function testTopUsernamesHonoursTheLimitAndTheDaysWindow(): void
    {
        $this->record($this->tag.'_old', '203.0.113.12', false, $this->dayOffset(10).' 10:00:00');

        $this->assertCount(1, $this->repo->topUsernames(30, 1));
        $this->assertNull($this->rowFor($this->repo->topUsernames(5, 100000), 'username', $this->tag.'_old'));
        $this->assertNotNull($this->rowFor($this->repo->topUsernames(15, 100000), 'username', $this->tag.'_old'));
    }

    public function testTopIpsGroupsFailuresByAddress(): void
    {
        $in = $this->dayOffset(1).' 09:00:00';
        $this->record($this->tag.'_x', '203.0.113.20', false, $in);
        $this->record($this->tag.'_y', '203.0.113.20', false, $in);
        $this->record($this->tag.'_z', '203.0.113.21', false, $in);
        $this->record($this->tag.'_z', '203.0.113.21', true, $in);

        $rows = $this->repo->topIps(30, 100000);
        $this->assertGreaterThanOrEqual(2, (int) $this->rowFor($rows, 'ip', '203.0.113.20')['failed']);
        $first = $this->rowFor($rows, 'ip', '203.0.113.20');
        $second = $this->rowFor($rows, 'ip', '203.0.113.21');
        $this->assertGreaterThan((int) $second['failed'], (int) $first['failed']);
        $this->assertCount(1, $this->repo->topIps(30, 1));
    }

    public function testFailedByDayCountsPerCalendarDayAndIgnoresSuccessesAndOlderDays(): void
    {
        $day = $this->dayOffset(3);
        $before = $this->rowFor($this->repo->failedByDay(7), 'day', $day);
        $this->record($this->tag, '203.0.113.30', false, $day.' 00:00:00');
        $this->record($this->tag, '203.0.113.30', false, $day.' 23:59:59');
        $this->record($this->tag, '203.0.113.30', true, $day.' 12:00:00');
        $this->record($this->tag, '203.0.113.30', false, $this->dayOffset(9).' 12:00:00');

        $after = $this->rowFor($this->repo->failedByDay(7), 'day', $day);
        $this->assertSame((int) ($before['failed'] ?? 0) + 2, (int) $after['failed']);
        $this->assertNull($this->rowFor($this->repo->failedByDay(7), 'day', $this->dayOffset(9)));

        $days = array_column($this->repo->failedByDay(7), 'day');
        $sorted = $days;
        sort($sorted);
        $this->assertSame($sorted, $days, 'Oldest day first');
    }

    public function testFailedByDayWindowStartsAtMidnightOfTheFirstDay(): void
    {
        // CURRENT_DATE - 7 DAY at 00:00 is inside the window, one second before is outside.
        $edge = $this->dayOffset(7);
        $before = $this->rowFor($this->repo->failedByDay(7), 'day', $edge);
        $this->record($this->tag, '203.0.113.31', false, $edge.' 00:00:00');
        $after = $this->rowFor($this->repo->failedByDay(7), 'day', $edge);
        $this->assertSame((int) ($before['failed'] ?? 0) + 1, (int) $after['failed']);

        $prev = $this->dayOffset(8);
        $this->record($this->tag, '203.0.113.31', false, $prev.' 23:59:59');
        $this->assertNull($this->rowFor($this->repo->failedByDay(7), 'day', $prev));
    }

    public function testSuccessVsFailedByDaySplitsBothOutcomesAndLeavesNoEmptyDays(): void
    {
        $day = $this->dayOffset(4);
        $before = $this->rowFor($this->repo->successVsFailedByDay(30), 'day', $day);
        $this->record($this->tag, '203.0.113.40', true, $day.' 08:00:00');
        $this->record($this->tag, '203.0.113.40', true, $day.' 09:00:00');
        $this->record($this->tag, '203.0.113.40', false, $day.' 10:00:00');

        $after = $this->rowFor($this->repo->successVsFailedByDay(30), 'day', $day);
        $this->assertSame((int) ($before['success_cnt'] ?? 0) + 2, (int) $after['success_cnt']);
        $this->assertSame((int) ($before['failed_cnt'] ?? 0) + 1, (int) $after['failed_cnt']);
        $this->assertNull($this->rowFor($this->repo->successVsFailedByDay(30), 'day', $this->dayOffset(45)));
    }

    public function testFailedByHourOfDayGroupsByHourOnlyForFailures(): void
    {
        $day = $this->dayOffset(2);
        $before = $this->rowFor($this->repo->failedByHourOfDay(7), 'hour', '23');
        $this->record($this->tag, '203.0.113.50', false, $day.' 23:10:00');
        $this->record($this->tag, '203.0.113.50', false, $day.' 23:50:00');
        $this->record($this->tag, '203.0.113.50', true, $day.' 23:55:00');

        $after = $this->rowFor($this->repo->failedByHourOfDay(7), 'hour', '23');
        $this->assertSame((int) ($before['failed'] ?? 0) + 2, (int) $after['failed']);
    }

    public function testUniqueIpsByDayCountsDistinctAddressesOfFailures(): void
    {
        $day = $this->dayOffset(5);
        $before = $this->rowFor($this->repo->uniqueIpsByDay(30), 'day', $day);
        $this->record($this->tag, '203.0.113.60', false, $day.' 01:00:00');
        $this->record($this->tag, '203.0.113.60', false, $day.' 02:00:00');
        $this->record($this->tag, '203.0.113.61', false, $day.' 03:00:00');
        $this->record($this->tag, '203.0.113.62', true, $day.' 04:00:00');

        $after = $this->rowFor($this->repo->uniqueIpsByDay(30), 'day', $day);
        $this->assertSame((int) ($before['unique_ips'] ?? 0) + 2, (int) $after['unique_ips']);
    }

    public function testFailedByMonthBucketsByFirstOfMonth(): void
    {
        $month = substr($this->dayOffset(1), 0, 7).'-01';
        $before = $this->rowFor($this->repo->failedByMonth(12), 'month', $month);
        $this->record($this->tag, '203.0.113.70', false, $this->dayOffset(1).' 12:00:00');
        $this->record($this->tag, '203.0.113.70', true, $this->dayOffset(1).' 12:00:00');

        $after = $this->rowFor($this->repo->failedByMonth(12), 'month', $month);
        $this->assertSame((int) ($before['failed'] ?? 0) + 1, (int) $after['failed']);
        $this->record($this->tag, '203.0.113.70', false, '2001-03-15 12:00:00');
        $this->assertNull($this->rowFor($this->repo->failedByMonth(12), 'month', '2001-03-01'));
    }

    public function testFindFailedPaginatedFiltersAndOrdersNewestFirst(): void
    {
        $this->record($this->tag, '203.0.113.80', false, '2001-05-01 10:00:00');
        $this->record($this->tag, '203.0.113.80', false, '2001-05-03 10:00:00');
        $this->record($this->tag, '203.0.113.80', false, '2001-05-02 10:00:00');
        $this->record($this->tag, '203.0.113.80', true, '2001-05-04 10:00:00');
        $this->record($this->tag.'_other', '203.0.113.81', false, '2001-05-02 11:00:00');

        $result = $this->repo->findFailedPaginated(1, 10, ['username' => $this->tag]);
        $this->assertSame(3, $result['total']);
        $this->assertSame(
            ['2001-05-03 10:00:00', '2001-05-02 10:00:00', '2001-05-01 10:00:00'],
            array_column($result['items'], 'login_date')
        );
        $this->assertSame(['login_date', 'user_ip', 'username'], array_keys($result['items'][0]));

        $byIp = $this->repo->findFailedPaginated(1, 10, ['ip' => '203.0.113.81']);
        $this->assertSame(1, $byIp['total']);
        $this->assertSame($this->tag.'_other', $byIp['items'][0]['username']);
    }

    public function testFindFailedPaginatedPagesAndDateBounds(): void
    {
        foreach (['01', '02', '03', '04', '05'] as $d) {
            $this->record($this->tag, '203.0.113.82', false, '2001-06-'.$d.' 10:00:00');
        }
        $filters = ['username' => $this->tag];

        $page2 = $this->repo->findFailedPaginated(2, 2, $filters);
        $this->assertSame(5, $page2['total']);
        $this->assertSame(2, $page2['page']);
        $this->assertSame(2, $page2['pageSize']);
        $this->assertSame(['2001-06-03 10:00:00', '2001-06-02 10:00:00'], array_column($page2['items'], 'login_date'));
        $this->assertCount(1, $this->repo->findFailedPaginated(3, 2, $filters)['items']);

        $ranged = $this->repo->findFailedPaginated(1, 10, $filters + ['from' => '2001-06-02 00:00:00', 'to' => '2001-06-04 10:00:00']);
        $this->assertSame(3, $ranged['total'], 'Bounds are inclusive');

        // The admin form sends plain dates (<input type="date">): the "to" day must be included whole.
        $dateOnly = $this->repo->findFailedPaginated(1, 10, $filters + ['to' => '2001-06-04']);
        $this->assertSame(4, $dateOnly['total']);
        $fromOnly = $this->repo->findFailedPaginated(1, 10, $filters + ['from' => '2001-06-02', 'to' => '2001-06-02']);
        $this->assertSame(1, $fromOnly['total'], 'The same day as from and to selects that day');
    }

    public function testFindFailedPaginatedNeverReturnsSuccessfulLogins(): void
    {
        $this->record($this->tag, '203.0.113.83', true, '2001-07-01 10:00:00');
        $this->assertSame(0, $this->repo->findFailedPaginated(1, 10, ['username' => $this->tag])['total']);
    }

    public function testFindFailedPaginatedBindsFiltersInsteadOfInterpolatingThem(): void
    {
        $this->record($this->tag, '203.0.113.84', false, '2001-07-02 10:00:00');
        $result = $this->repo->findFailedPaginated(1, 10, ['username' => "' OR '1'='1"]);
        $this->assertSame(0, $result['total']);
    }

    public function testCountRecentFailedByUsernameCountsLast24HoursOfFailuresOfThatUser(): void
    {
        $now = (string) $this->conn()->fetchOne('SELECT NOW()');
        $this->record($this->tag, '203.0.113.90', false, $now);
        $this->record($this->tag, '203.0.113.90', false, $now);
        $this->record($this->tag, '203.0.113.90', true, $now);
        $this->record($this->tag, '203.0.113.90', false, (string) $this->conn()->fetchOne('SELECT NOW() - INTERVAL 25 HOUR'));
        $this->record($this->tag.'_o', '203.0.113.90', false, $now);

        $this->assertSame(2, $this->repo->countRecentFailedByUsername($this->tag));
        $this->assertSame(0, $this->repo->countRecentFailedByUsername($this->tag.'_nobody'));
    }
}
