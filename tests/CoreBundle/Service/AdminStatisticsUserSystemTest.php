<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service;

use Chamilo\CoreBundle\Service\AdminStatistics\AdminStatisticsQueryService;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\Tests\ChamiloTestTrait;
use DateTimeImmutable;
use DateTimeZone;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Characterization of the user/system reports of the Vue admin statistics page (AdminStatisticsUserSystemQueryService
 * reached through AdminStatisticsQueryService). Their raw SQL uses DATE_FORMAT, TIMESTAMPDIFF, DATE_SUB, LIMIT x,y:
 * a portability rewrite must keep every figure below. Rows are tagged "rp_" and dated in 2001 so that the figures
 * do not depend on the content of the test database or on the clock; reports that can only be read relative to
 * "now" are compared as before/after deltas. The route is ROLE_ADMIN only (AdminStatisticsReport ApiResource).
 */
final class AdminStatisticsUserSystemTest extends KernelTestCase
{
    use ChamiloTestTrait;
    use RawReportingFixturesTrait;

    private AdminStatisticsQueryService $service;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->service = self::getContainer()->get(AdminStatisticsQueryService::class);
        $settings = self::getContainer()->get(SettingsManager::class);
        $settings->updateSetting('platform.timezone', 'UTC');
        $settings->updateSetting('profile.use_users_timezone', 'false');
        $this->fresh();
    }

    /**
     * @return array<string, mixed>
     */
    private function report(string $name, array $parameters = []): array
    {
        $this->fresh();

        return $this->service->getReport($name, $parameters);
    }

    /**
     * @param array<int, array{label: string, value: int|float}> $stats
     *
     * @return array<string, int|float>
     */
    private function byLabel(array $stats): array
    {
        $map = [];
        foreach ($stats as $row) {
            $map[$row['label']] = $row['value'];
        }

        return $map;
    }

    private function login(int $userId, string $in, ?string $out): void
    {
        $this->conn()->executeStatement(
            "INSERT INTO track_e_login (login_user_id, login_date, user_ip, logout_date) VALUES (?, ?, '', ?)",
            [$userId, $in, $out]
        );
    }

    // ------------------------------------------------------------- dispatch

    public function testUnknownReportIsRejected(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->service->getReport('does_not_exist');
    }

    public function testOnlyThreeReportsAreExportableFromTheUserSystemGroup(): void
    {
        $this->expectException(BadRequestHttpException::class);
        $this->service->getExportData('users', []);
    }

    // ------------------------------------------------------------ users

    public function testUsersReportCountsLiveUsersOfEachStatusAndNeverSoftDeletedOnes(): void
    {
        $before = $this->byLabel($this->report('users')['statsGroups'][0]['items']);
        $this->rawUser('n1', 1);
        $this->rawUser('n2', 5);
        $this->rawUser('n3', 5);
        $this->rawUser('n4', 5, -2);
        $this->rawUser('n5', 6);
        $after = $this->byLabel($this->report('users')['statsGroups'][0]['items']);
        $this->assertSame($before['Trainers'] + 1, $after['Trainers']);
        $this->assertSame($before['Learners'] + 2, $after['Learners']);
        $this->assertSame($after['Trainers'], $this->report('users')['charts'][0]['data']['datasets'][0]['data'][0]);
    }

    public function testUsersReportIsConfinedToTheCurrentAccessUrlOnceThePortalIsMultiUrl(): void
    {
        $before = $this->byLabel($this->report('users')['statsGroups'][0]['items']);
        $here = $this->rawUser('m1', 5);
        $elsewhere = $this->rawUser('m2', 5, 1, null);
        $other = $this->secondUrl();
        $this->conn()->executeStatement('INSERT INTO access_url_rel_user (access_url_id, user_id) VALUES (?, ?)', [$other, $elsewhere]);
        $after = $this->byLabel($this->report('users')['statsGroups'][0]['items']);
        $this->assertSame($before['Learners'] + 1, $after['Learners']);
        $this->assertNotSame(0, $here);
    }

    public function testUsersByLanguageCountsTheUsersOfTheUrlPerLocale(): void
    {
        $report = $this->report('usersbylanguage');
        $total = array_sum(array_column($report['stats'], 'value'));
        $this->rawUser('l1', 5);
        $this->rawUser('l2', 5);
        $this->rawUser('l3', 5, -2);
        $after = $this->report('usersbylanguage');
        $this->assertSame($total + 2, array_sum(array_column($after['stats'], 'value')));
    }

    // ----------------------------------------------------------- logins

    public function testLoginsByMonthGroupsAndSortsNewestFirst(): void
    {
        $a = $this->rawUser('g1');
        $b = $this->rawUser('g2');
        $this->login($a, '2001-01-10 10:00:00', '2001-01-10 10:05:00');
        $this->login($b, '2001-01-31 23:59:59', '2001-02-01 00:00:10');
        $this->login($a, '2001-02-01 00:00:00', null);
        $this->login($a, '2001-02-15 12:00:00', null);
        $this->login($b, '2001-02-28 12:00:00', null);
        $items = $this->report('logins')['statsGroups'][0]['items'];
        $map = $this->byLabel($items);
        $this->assertSame(2, $map['2001 January']);
        $this->assertSame(3, $map['2001 February']);
        $labels = array_column($items, 'label');
        $this->assertLessThan(array_search('2001 January', $labels, true), array_search('2001 February', $labels, true));
    }

    public function testLoginsByMonthCountsOnlyLoginsOfTheCurrentUrl(): void
    {
        $here = $this->rawUser('g3');
        $elsewhere = $this->rawUser('g4', 5, 1, null);
        $other = $this->secondUrl();
        $this->conn()->executeStatement('INSERT INTO access_url_rel_user (access_url_id, user_id) VALUES (?, ?)', [$other, $elsewhere]);
        $this->login($here, '2001-03-10 10:00:00', null);
        $this->login($elsewhere, '2001-03-11 10:00:00', null);
        $this->login($elsewhere, '2001-03-12 10:00:00', null);
        $this->assertSame(1, $this->byLabel($this->report('logins')['statsGroups'][0]['items'])['2001 March']);
    }

    public function testLoginsByWeekdayAndByHourUseMysqlWeekdayAndHourFormats(): void
    {
        $delta = function (string $type): array {
            $groups = $this->report('logins', ['type' => $type])['statsGroups'];

            return $this->byLabel($groups[1]['items']);
        };
        $weekdaysBefore = $delta('day');
        $hoursBefore = $delta('hour');
        $u = $this->rawUser('g5');
        // 2001-04-01 is a Sunday, 2001-04-02 a Monday.
        $this->login($u, '2001-04-01 07:30:00', null);
        $this->login($u, '2001-04-01 07:45:00', null);
        $this->login($u, '2001-04-02 19:00:00', null);
        $weekdays = $delta('day');
        $hours = $delta('hour');
        $this->assertSame(($weekdaysBefore['Sunday'] ?? 0) + 2, $weekdays['Sunday']);
        $this->assertSame(($weekdaysBefore['Monday'] ?? 0) + 1, $weekdays['Monday']);
        $this->assertSame(($hoursBefore['07'] ?? 0) + 2, $hours['07']);
        $this->assertSame(($hoursBefore['19'] ?? 0) + 1, $hours['19']);
        $titles = array_column($this->report('logins', ['type' => 'day'])['statsGroups'], 'title');
        $this->assertSame(['Last logins (Day)', 'All logins (Day)'], $titles);
    }

    public function testLoginsReportFallsBackToMonthOnAnUnknownType(): void
    {
        $report = $this->report('logins', ['type' => 'year; DROP TABLE user']);
        $this->assertSame('month', $report['filters']['type']);
        $this->assertCount(1, $report['statsGroups']);
    }

    public function testRecentLoginsRecentWindowsCountTodayAndExcludeOpenVisits(): void
    {
        $count = fn (): array => $this->byLabel($this->report('recentlogins')['statsGroups'][0]['items']);
        $before = $count();
        $u = $this->rawUser('r1');
        $now = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s');
        // A visit is counted only once it has a logout date different from the login date.
        $this->login($u, $now, '2099-01-01 00:00:00');
        $this->login($u, $now, $now);
        $this->login($u, $now, null);
        $after = $count();
        $this->assertSame($before['Today'] + 1, $after['Today']);
        $this->assertSame($before['Total'] + 1, $after['Total']);
        $this->assertSame($before['Last 7 days'] + 1, $after['Last 7 days']);
        $distinct = $this->byLabel($this->report('recentlogins')['statsGroups'][1]['items']);
        $this->assertGreaterThanOrEqual(1, $distinct['Today']);
    }

    // ---------------------------------------------------- logins by date

    public function testLoginsByDateSumsTimeSpentPerUserWithinTheRange(): void
    {
        $a = $this->rawUser('d1');
        $b = $this->rawUser('d2');
        $deleted = $this->rawUser('d3', 5, -2);
        $this->login($a, '2001-06-01 10:00:00', '2001-06-01 11:01:01');
        $this->login($a, '2001-06-02 10:00:00', '2001-06-02 10:00:59');
        // Outside the range, open visit (NULL logout adds nothing), soft-deleted user.
        $this->login($a, '2001-06-03 10:00:00', '2001-06-03 12:00:00');
        $this->login($b, '2001-06-02 08:00:00', null);
        $this->login($deleted, '2001-06-01 10:00:00', '2001-06-01 11:00:00');

        $report = $this->report('logins_by_date', ['rangeStart' => '2001-06-01', 'rangeEnd' => '2001-06-02']);
        $items = $report['table']['items'];
        usort($items, static fn (array $x, array $y): int => strcmp($x['username'], $y['username']));
        $this->assertCount(2, $items);
        $this->assertSame('01:02:00', $items[0]['totalTime']);
        $this->assertSame('00:00:00', $items[1]['totalTime']);
        $this->assertSame(2, $report['table']['totalItems']);
        $this->assertTrue($report['meta']['canExportXls']);
        $this->assertSame('Firstd1', $items[0]['firstname']);
    }

    public function testLoginsByDateIncludesTheWholeLastDayAndRejectsBadRanges(): void
    {
        $a = $this->rawUser('d4');
        $this->login($a, '2001-07-05 23:59:59', '2001-07-06 00:00:01');
        $inclusive = $this->report('logins_by_date', ['rangeStart' => '2001-07-05', 'rangeEnd' => '2001-07-05']);
        $this->assertCount(1, $inclusive['table']['items']);

        foreach ([['rangeStart' => '2001-07-06', 'rangeEnd' => '2001-07-05'], ['rangeStart' => 'x', 'rangeEnd' => '2001-07-05'], []] as $bad) {
            $empty = $this->report('logins_by_date', $bad);
            $this->assertSame([], $empty['table']['items']);
            $this->assertTrue($empty['meta']['requiresDateRange']);
        }
    }

    public function testLoginsByDateIsConfinedToTheCurrentUrl(): void
    {
        $here = $this->rawUser('d5');
        $elsewhere = $this->rawUser('d6', 5, 1, null);
        $other = $this->secondUrl();
        $this->conn()->executeStatement('INSERT INTO access_url_rel_user (access_url_id, user_id) VALUES (?, ?)', [$other, $elsewhere]);
        $this->login($here, '2001-08-01 10:00:00', '2001-08-01 10:00:10');
        $this->login($elsewhere, '2001-08-01 10:00:00', '2001-08-01 10:00:10');
        $items = $this->report('logins_by_date', ['rangeStart' => '2001-08-01', 'rangeEnd' => '2001-08-01'])['table']['items'];
        $this->assertCount(1, $items);
    }

    public function testLoginsByDateIsExportable(): void
    {
        $a = $this->rawUser('d7');
        $this->login($a, '2001-09-01 10:00:00', '2001-09-01 10:00:10');
        $export = $this->service->getExportData('logins_by_date', ['rangeStart' => '2001-09-01', 'rangeEnd' => '2001-09-01']);
        $this->assertCount(1, $export['items']);
        $this->assertSame('00:00:10', $export['items'][0]['totalTime']);
        $this->assertNotEmpty($export['columns']);
    }

    // ------------------------------------------------------ no login users

    public function testNoLoginUsersCountsUsersWithoutRecentOrAnyLogin(): void
    {
        $before = $this->byLabel($this->report('no_login_users')['stats']);
        $a = $this->rawUser('o1');
        $this->rawUser('o2');
        $this->rawUser('o3', 5, -2);
        $this->login($a, '2001-01-01 10:00:00', null);
        $after = $this->byLabel($this->report('no_login_users')['stats']);
        // Two new live users, the soft-deleted one never counts. "a" logged in once, in 2001: not recent.
        $this->assertSame($before['Never connected'] + 1, $after['Never connected']);
        $this->assertSame($before['In the last 7 days'] + 2, $after['In the last 7 days']);
        $this->assertSame($before['This day'] + 2, $after['This day']);
    }

    // ------------------------------------------------------- registrations

    public function testRegistrationsWithoutRangeAskForOne(): void
    {
        $this->assertTrue($this->report('new_user_registrations')['meta']['requiresDateRange']);
    }

    public function testRegistrationsByDayFillMissingDaysOfAShortRange(): void
    {
        $a = $this->rawUser('rg1');
        $b = $this->rawUser('rg2');
        $c = $this->rawUser('rg3');
        $this->conn()->executeStatement("UPDATE user SET created_at = '2001-05-01 09:00:00' WHERE id IN (?, ?)", [$a, $b]);
        $this->conn()->executeStatement("UPDATE user SET created_at = '2001-05-03 00:00:00' WHERE id = ?", [$c]);
        $report = $this->report('new_user_registrations', ['rangeStart' => '2001-05-01', 'rangeEnd' => '2001-05-03']);
        $values = $report['charts'][0]['data']['datasets'][0]['data'];
        $this->assertSame(['2001-05-01', '2001-05-02', '2001-05-03'], $report['charts'][0]['data']['labels']);
        $this->assertSame([2, 0, 1], $values);
        $this->assertFalse($report['meta']['registrationMonthly']);
    }

    public function testRegistrationsOfARangeLongerThanAMonthAreGroupedByMonth(): void
    {
        $a = $this->rawUser('rg4');
        $b = $this->rawUser('rg5');
        $this->conn()->executeStatement("UPDATE user SET created_at = '2001-10-01 09:00:00' WHERE id = ?", [$a]);
        $this->conn()->executeStatement("UPDATE user SET created_at = '2001-12-01 00:00:00' WHERE id = ?", [$b]);
        $report = $this->report('new_user_registrations', ['rangeStart' => '2001-10-01', 'rangeEnd' => '2001-12-31']);
        $this->assertTrue($report['meta']['registrationMonthly']);
        $this->assertSame(['2001-10', '2001-12'], $report['meta']['registrationMonths']);
        $this->assertSame([1, 1], $report['charts'][0]['data']['datasets'][0]['data']);
    }

    public function testRegistrationsOfTheLastDayOfTheRangeAreCounted(): void
    {
        $a = $this->rawUser('rg6');
        $this->conn()->executeStatement("UPDATE user SET created_at = '2001-05-20 15:00:00' WHERE id = ?", [$a]);
        $report = $this->report('new_user_registrations', ['rangeStart' => '2001-05-10', 'rangeEnd' => '2001-05-20']);
        $this->assertArrayNotHasKey('noData', $report['meta']);
        $values = array_combine($report['charts'][0]['data']['labels'], $report['charts'][0]['data']['datasets'][0]['data']);
        $this->assertSame(1, $values['2001-05-20']);
    }

    public function testRegistrationsOfTheLastDayOfTheMonthAreCountedInTheDrilldown(): void
    {
        $a = $this->rawUser('rg7');
        $this->conn()->executeStatement("UPDATE user SET created_at = '2001-05-31 18:00:00' WHERE id = ?", [$a]);
        $report = $this->report('new_user_registrations', ['rangeStart' => '2001-05-01', 'rangeEnd' => '2001-06-30', 'month' => '2001-05']);
        $values = array_combine($report['chart']['data']['labels'], $report['chart']['data']['datasets'][0]['data']);
        $this->assertSame(1, $values['2001-05-31']);
    }

    public function testRegistrationsAreConfinedToTheUsersOfTheCurrentUrl(): void
    {
        $here = $this->rawUser('rg8');
        $elsewhere = $this->rawUser('rg9', 5, 1, null);
        $other = $this->secondUrl();
        $this->conn()->executeStatement('INSERT INTO access_url_rel_user (access_url_id, user_id) VALUES (?, ?)', [$other, $elsewhere]);
        $this->conn()->executeStatement("UPDATE user SET created_at = '2001-06-05 10:00:00' WHERE id IN (?, ?)", [$here, $elsewhere]);
        $report = $this->report('new_user_registrations', ['rangeStart' => '2001-06-01', 'rangeEnd' => '2001-06-07']);
        $values = array_combine($report['charts'][0]['data']['labels'], $report['charts'][0]['data']['datasets'][0]['data']);
        $this->assertSame(1, $values['2001-06-05']);
    }

    // ------------------------------------------------------ subscription by day

    public function testSubscriptionsByDayCountsSubscribeAndUnsubscribeEventsPerDay(): void
    {
        $events = [
            ['2001-11-01 10:00:00', 'user_subscribed'],
            ['2001-11-01 23:59:59', 'user_subscribed'],
            ['2001-11-02 00:00:00', 'user_unsubscribed'],
            ['2001-11-02 05:00:00', 'session_delete_user'],
            ['2001-11-02 06:00:00', 'user_subscribed'],
            ['2001-11-04 06:00:00', 'user_subscribed'],
            ['2001-11-03 06:00:00', 'something_else'],
        ];
        foreach ($events as [$date, $type]) {
            $this->conn()->executeStatement(
                "INSERT INTO track_e_default (default_user_id, default_date, default_event_type, default_value_type, default_value)
                 VALUES (1, ?, ?, 'x', 'y')",
                [$date, $type]
            );
        }
        $report = $this->report('subscription_by_day', ['rangeStart' => '2001-11-01', 'rangeEnd' => '2001-11-03']);
        $this->assertSame([
            ['date' => '2001-11-01', 'subscriptions' => 2, 'unsubscriptions' => 0],
            ['date' => '2001-11-02', 'subscriptions' => 1, 'unsubscriptions' => 2],
            ['date' => '2001-11-03', 'subscriptions' => 0, 'unsubscriptions' => 0],
        ], $report['table']['items']);
        $this->assertSame([2, 1, 0], $report['chart']['data']['datasets'][0]['data']);
    }

    /**
     * Once the portal is multi-URL an event only counts when its course or its session belongs to the current URL.
     */
    public function testSubscriptionsByDayAreConfinedToTheCoursesAndSessionsOfTheCurrentUrl(): void
    {
        $other = $this->secondUrl();
        $this->conn()->executeStatement('UPDATE access_url_rel_course SET access_url_id = ? WHERE c_id = 2', [$other]);
        $mySession = $this->rawSession('sub1');
        $foreignSession = $this->rawSession('sub2', $other);
        $events = [
            [1, null, 'user_subscribed'],
            [2, null, 'user_subscribed'],
            [null, null, 'user_subscribed'],
            [null, $mySession, 'user_unsubscribed'],
            [null, $foreignSession, 'user_unsubscribed'],
        ];
        foreach ($events as [$courseId, $sessionId, $type]) {
            $this->conn()->executeStatement(
                "INSERT INTO track_e_default (default_user_id, c_id, session_id, default_date, default_event_type, default_value_type, default_value)
                 VALUES (1, ?, ?, '2001-11-01 10:00:00', ?, 'x', 'y')",
                [$courseId, $sessionId, $type]
            );
        }
        $report = $this->report('subscription_by_day', ['rangeStart' => '2001-11-01', 'rangeEnd' => '2001-11-01']);
        $this->assertSame([['date' => '2001-11-01', 'subscriptions' => 1, 'unsubscriptions' => 1]], $report['table']['items']);
    }

    /**
     * Sessions are logged as LOG_SESSION_DELETE_USER ("session_delete_user"): they count as unsubscriptions.
     */
    public function testSubscriptionsByDayCountUsersRemovedFromASession(): void
    {
        $this->conn()->executeStatement(
            "INSERT INTO track_e_default (default_user_id, default_date, default_event_type, default_value_type, default_value)
             VALUES (1, '2001-11-01 10:00:00', 'session_delete_user', 'x', 'y')"
        );
        $report = $this->report('subscription_by_day', ['rangeStart' => '2001-11-01', 'rangeEnd' => '2001-11-01']);
        $this->assertSame(1, $report['table']['items'][0]['unsubscriptions']);
    }

    // ---------------------------------------------------------- user session

    public function testUserSessionCountsLearnersRegisteredInTheRangePerUrlAndSession(): void
    {
        $s = $this->rawSession('us');
        $this->sessionCourse($s, 2);
        $this->sessionCourse($s, 1);
        $in1 = $this->rawUser('us1');
        $in2 = $this->rawUser('us2');
        $outside = $this->rawUser('us3');
        $coach = $this->rawUser('us4', 1);
        $this->conn()->executeStatement("INSERT INTO session_rel_user (session_id, user_id, relation_type, duration, registered_at) VALUES
            (?, ?, 0, 0, '2001-12-01 00:00:00'), (?, ?, 0, 0, '2001-12-31 23:59:59'), (?, ?, 0, 0, '2002-01-01 00:00:00'), (?, ?, 3, 0, '2001-12-10 00:00:00')",
            [$s, $in1, $s, $in2, $s, $outside, $s, $coach]);
        $items = $this->report('user_session', ['rangeStart' => '2001-12-01', 'rangeEnd' => '2001-12-31'])['table']['items'];
        $mine = array_values(array_filter($items, static fn (array $i): bool => $i['sessionId'] === $s));
        $this->assertCount(1, $mine);
        $this->assertSame(2, $mine[0]['count'], 'only learners (relation 0) registered within the range');
        $this->assertSame('/main/session/resume_session.php?id_session='.$s, $mine[0]['sessionUrl']);
        // Course titles follow the session_rel_course position then course id: course 1 (position 0) first.
        $titles = $this->conn()->fetchFirstColumn('SELECT title FROM course WHERE id IN (1, 2) ORDER BY id');
        $this->assertSame(implode(', ', $titles), $mine[0]['course']);
    }

    public function testUserSessionRejectsAnInvalidRangeAndSortsByUrl(): void
    {
        $this->expectException(BadRequestHttpException::class);
        $this->report('user_session', ['rangeStart' => '2001-12-31', 'rangeEnd' => '2001-12-01']);
    }

    public function testUserSessionListsSessionsOfEveryUrlForTheAdministrator(): void
    {
        $s1 = $this->rawSession('us5', 1);
        $other = $this->secondUrl();
        $s2 = $this->rawSession('us6', $other);
        $hidden = $this->rawSession('us7', null);
        $items = $this->report('user_session', ['rangeStart' => '2001-12-01', 'rangeEnd' => '2001-12-31'])['table']['items'];
        $ids = array_column($items, 'sessionId');
        $this->assertContains($s1, $ids);
        $this->assertContains($s2, $ids, 'portal-wide report: every URL is listed');
        $this->assertNotContains($hidden, $ids, 'a session on no URL is not listed');
        $asc = array_column($items, 'url');
        $desc = array_column($this->report('user_session', ['rangeStart' => '2001-12-01', 'rangeEnd' => '2001-12-31', 'sortOrder' => 'desc'])['table']['items'], 'url');
        $sorted = $asc;
        sort($sorted);
        $this->assertSame($sorted, $asc);
        $this->assertSame(array_reverse($sorted), $desc);
    }

    // ----------------------------------------------------------- users online

    public function testUsersOnlineCountsRecentActivityOfLiveNonAnonymousUsers(): void
    {
        $settings = self::getContainer()->get(SettingsManager::class);
        $settings->updateSetting('display.time_limit_whosonline', '30');
        $this->fresh();
        $before = $this->report('users_online');
        $a = $this->rawUser('on1');
        $b = $this->rawUser('on2');
        $deleted = $this->rawUser('on3', 5, -2);
        $anonymous = $this->rawUser('on4', 6);
        $old = $this->rawUser('on5');
        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        foreach ([
            [$a, '-1 minute', 1], [$b, '-4 minutes', 1], [$deleted, '-1 minute', 1],
            [$anonymous, '-1 minute', 1], [$old, '-3 hours', 1],
        ] as [$uid, $ago, $url]) {
            $this->conn()->executeStatement(
                "INSERT INTO track_e_online (login_user_id, login_date, user_ip, c_id, session_id, access_url_id) VALUES (?, ?, '', 0, 0, ?)",
                [$uid, $now->modify($ago)->format('Y-m-d H:i:s'), $url]
            );
        }
        $report = $this->report('users_online');
        $cards = [];
        foreach ($report['meta']['onlineCards'] as $card) {
            $cards[$card['minutes']] = $card['value'] - ($this->cardValue($before, $card['minutes']));
        }
        $this->assertSame([3 => 1, 5 => 2, 30 => 2, 120 => 2], $cards);
        $this->assertSame(30, $report['meta']['configuredOnlineMinutes']);
        $this->assertSame($before['table']['totalItems'] + 2, $report['table']['totalItems']);
        $names = array_column($report['table']['items'], 'username');
        $this->assertNotContains($this->conn()->fetchOne('SELECT username FROM user WHERE id = ?', [$deleted]), $names);
        $this->assertNotContains($this->conn()->fetchOne('SELECT username FROM user WHERE id = ?', [$old]), $names);
    }

    private function cardValue(array $report, int $minutes): int
    {
        foreach ($report['meta']['onlineCards'] as $card) {
            if ($card['minutes'] === $minutes) {
                return $card['value'];
            }
        }

        return 0;
    }

    public function testUsersOnlinePagesWithMysqlLimitOffset(): void
    {
        $settings = self::getContainer()->get(SettingsManager::class);
        $settings->updateSetting('display.time_limit_whosonline', '30');
        $this->fresh();
        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $ids = [];
        foreach (['p1', 'p2', 'p3'] as $i => $suffix) {
            $ids[] = $uid = $this->rawUser($suffix);
            $this->conn()->executeStatement(
                "INSERT INTO track_e_online (login_user_id, login_date, user_ip, c_id, session_id, access_url_id) VALUES (?, ?, '', 0, 0, 1)",
                [$uid, $now->modify('-'.($i + 1).' seconds')->format('Y-m-d H:i:s')]
            );
        }
        $page1 = $this->report('users_online', ['itemsPerPage' => 5, 'page' => 1]);
        $total = $page1['table']['totalItems'];
        $this->assertGreaterThanOrEqual(3, $total);
        // Most recent activity first: p1 (1s ago) before p2 before p3 on the page that holds them.
        $all = $this->report('users_online', ['itemsPerPage' => 100]);
        $order = array_column($all['table']['items'], 'id');
        $this->assertSame($ids, array_values(array_intersect($order, $ids)));
        $this->assertCount(min(5, $total), $page1['table']['items']);
        $this->assertSame('/main/admin/user_information.php?user_id='.$order[0], $all['table']['items'][0]['detailsUrl']);
    }
}
