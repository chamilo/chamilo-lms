<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service;

use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Service\AdminStatistics\AdminStatisticsQueryService;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\Tests\ChamiloTestTrait;
use DateTimeImmutable;
use DateTimeZone;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Characterization of the course, session and social reports of the admin statistics page
 * (AdminStatisticsQueryService): last course visits (GROUP BY / DATEDIFF), courses usage (DISTINCT + CAST AS DATE),
 * messages, contacts, sessions by date. Rows are tagged "rp_", dated in 2001 or compared as before/after deltas.
 * The route is ROLE_ADMIN only, the service never checks a role itself.
 */
final class AdminStatisticsQueryServiceTest extends KernelTestCase
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

    private function lastAccess(int $courseId, string $date): void
    {
        $this->conn()->executeStatement(
            "INSERT INTO track_e_lastaccess (access_user_id, access_date, c_id, access_tool, session_id) VALUES (1, ?, ?, 'x', 0)",
            [$date, $courseId]
        );
    }

    private function courseAccess(int $courseId, int $userId, string $date, int $sessionId = 0): void
    {
        $this->conn()->executeStatement(
            "INSERT INTO track_e_course_access (user_id, c_id, login_course_date, logout_course_date, counter, session_id, user_ip)
             VALUES (?, ?, ?, NULL, 1, ?, '')",
            [$userId, $courseId, $date, $sessionId]
        );
    }

    public function testUnknownReportIsNotFoundAndNonExportableReportsAreRejected(): void
    {
        try {
            $this->service->getReport('nope');
            $this->fail('unknown report accepted');
        } catch (NotFoundHttpException) {
            $this->addToAssertionCount(1);
        }
        $this->expectException(BadRequestHttpException::class);
        $this->service->getExportData('courses', []);
    }

    // ----------------------------------------------------- courselastvisit

    public function testCourseLastVisitListsCoursesNotVisitedSinceTheThreshold(): void
    {
        $this->conn()->executeStatement('DELETE FROM track_e_lastaccess');
        $this->lastAccess(1, '2001-01-01 10:00:00');
        $this->lastAccess(2, '2099-01-01 10:00:00');
        $report = $this->report('courselastvisit', ['dateDiff' => 30]);
        $this->assertSame(1, $report['table']['totalItems']);
        $this->assertSame(1, $report['table']['items'][0]['courseId']);
        $this->assertSame('2001-01-01 10:00:00', $report['table']['items'][0]['lastAccess']);
        $this->assertNotSame('', $report['table']['items'][0]['courseTitle']);
        $this->assertSame(30, $report['filters']['dateDiff']);
    }

    public function testCourseLastVisitSortsAndPaginates(): void
    {
        $this->conn()->executeStatement('DELETE FROM track_e_lastaccess');
        $this->lastAccess(1, '2001-01-01 10:00:00');
        $this->lastAccess(2, '2002-01-01 10:00:00');
        $asc = $this->report('courselastvisit', ['direction' => 'ASC']);
        $this->assertSame([1, 2], array_column($asc['table']['items'], 'courseId'));
        $desc = $this->report('courselastvisit', ['direction' => 'DESC']);
        $this->assertSame([2, 1], array_column($desc['table']['items'], 'courseId'));
        $this->assertSame(3, $desc['filters']['direction']);
        $byDateDesc = $this->report('courselastvisit', ['column' => 2, 'direction' => '3']);
        $this->assertSame([2, 1], array_column($byDateDesc['table']['items'], 'courseId'));
        $page2 = $this->report('courselastvisit', ['itemsPerPage' => 1, 'page' => 2]);
        $this->assertSame([2], array_column($page2['table']['items'], 'courseId'));
        $this->assertSame(2, $page2['table']['totalItems']);
        // An out of range column falls back to the course id.
        $this->assertSame(0, $this->report('courselastvisit', ['column' => 9])['filters']['column']);
    }

    public function testCourseLastVisitIsConfinedToTheCoursesOfTheCurrentUrl(): void
    {
        $this->conn()->executeStatement('DELETE FROM track_e_lastaccess');
        $this->lastAccess(1, '2001-01-01 10:00:00');
        $this->lastAccess(2, '2001-01-01 10:00:00');
        $other = $this->secondUrl();
        $this->conn()->executeStatement('UPDATE access_url_rel_course SET access_url_id = ? WHERE c_id = 2', [$other]);
        $report = $this->report('courselastvisit');
        $this->assertSame([1], array_column($report['table']['items'], 'courseId'));
    }

    // -------------------------------------------------------- courses usage

    public function testCoursesUsageCountsDistinctVisitorDaysPerPeriodAndSessionBucket(): void
    {
        $this->conn()->executeStatement('DELETE FROM track_e_course_access');
        $yesterday = (new DateTimeImmutable('yesterday 12:00', new DateTimeZone('UTC')))->format('Y-m-d H:i:s');
        // Same user twice the same day counts once; another user and another day count again.
        $this->courseAccess(1, 1, $yesterday);
        $this->courseAccess(1, 1, $yesterday);
        $this->courseAccess(1, 2, $yesterday);
        $this->courseAccess(1, 1, '2001-01-01 10:00:00');
        $this->courseAccess(1, 1, '2001-01-01 11:00:00', 7);
        $this->courseAccess(1, 3, '2001-01-02 11:00:00', 7);
        $report = $this->report('courses_usage', ['itemsPerPage' => 100]);
        $row = null;
        foreach ($report['table']['items'] as $item) {
            if ($item['allTimeTotal'] > 0) {
                $row = $item;
            }
        }
        $this->assertNotNull($row);
        $this->assertSame(2, $row['today'], 'the two visitors of yesterday');
        $this->assertSame(2, $row['week']);
        $this->assertSame(2, $row['month']);
        $this->assertSame(2, $row['twoYears']);
        $this->assertSame(3, $row['allTimeOutsideSessions']);
        $this->assertSame(2, $row['allTimeInSessions']);
        $this->assertSame(5, $row['allTimeTotal']);
        $this->assertSame($this->conn()->fetchOne('SELECT title FROM course WHERE id = 1'), $row['course']);
    }

    public function testCoursesUsageNeverVisitedCourseIsListedWithZeros(): void
    {
        $this->conn()->executeStatement('DELETE FROM track_e_course_access');
        $report = $this->report('courses_usage', ['itemsPerPage' => 100]);
        $this->assertSame(0, $report['table']['items'][0]['allTimeTotal']);
        $this->assertSame((int) $this->conn()->fetchOne('SELECT COUNT(*) FROM course'), $report['table']['totalItems']);
    }

    /**
     * The course list and its total are restricted to the courses of the current access URL, like the other course
     * reports: an admin of a secondary portal must not see the course titles of another portal.
     */
    public function testCoursesUsageIsConfinedToTheCoursesOfTheCurrentUrl(): void
    {
        $other = $this->secondUrl();
        $this->conn()->executeStatement('UPDATE access_url_rel_course SET access_url_id = ? WHERE c_id = 2', [$other]);
        $expected = (int) $this->conn()->fetchOne('SELECT COUNT(*) FROM access_url_rel_course WHERE access_url_id = 1');
        $report = $this->report('courses_usage', ['itemsPerPage' => 100]);
        $this->assertSame($expected, $report['table']['totalItems']);
        $this->assertCount($expected, $report['table']['items']);
        $this->assertNotContains($this->conn()->fetchOne('SELECT title FROM course WHERE id = 2'), array_column($report['table']['items'], 'course'));
        $this->assertContains($this->conn()->fetchOne('SELECT title FROM course WHERE id = 1'), array_column($report['table']['items'], 'course'));
    }

    // ----------------------------------------------------------- messages

    private function message(int $senderId, int $receiverId, int $msgType = 1): void
    {
        $this->conn()->executeStatement(
            "INSERT INTO message (user_sender_id, msg_type, status, send_date, title, content, mail_answer)
             VALUES (?, ?, 0, '2001-01-01 00:00:00', 'rp_msg', 'x', 0)",
            [$senderId, $msgType]
        );
        $id = (int) $this->conn()->lastInsertId();
        $this->conn()->executeStatement(
            'INSERT INTO message_rel_user (message_id, user_id, msg_read, receiver_type, starred) VALUES (?, ?, 0, 1, 0), (?, ?, 0, 8, 0)',
            [$id, $receiverId, $id, $senderId]
        );
    }

    private function messageStats(string $report): array
    {
        return $this->byLabel($this->report($report)['stats']);
    }

    public function testMessagesSentAndReceivedAreCountedPerLiveUser(): void
    {
        $a = $this->rawUser('m1');
        $b = $this->rawUser('m2');
        $deleted = $this->rawUser('m3', 5, -2);
        $this->message($a, $b);
        $this->message($a, $b);
        $this->message($b, $a);
        $this->message($deleted, $a);
        $labelA = 'Firstm1 Lastm1'."\n(".$this->conn()->fetchOne('SELECT username FROM user WHERE id = ?', [$a]).')';
        $labelB = 'Firstm2 Lastm2'."\n(".$this->conn()->fetchOne('SELECT username FROM user WHERE id = ?', [$b]).')';
        $sent = $this->messageStats('messagesent');
        $received = $this->messageStats('messagereceived');
        $this->assertSame(2, $sent[$labelA]);
        $this->assertSame(1, $sent[$labelB]);
        $this->assertSame(2, $received[$labelB]);
        $this->assertSame(2, $received[$labelA], 'the message of the soft-deleted sender is still received');
        $this->assertCount(2, array_filter(array_keys($sent), static fn (string $l): bool => str_contains($l, 'rp_m')));
        $this->assertSame('Number of messages sent', $this->report('messagesent')['title']);
        $this->assertSame('Number of messages received', $this->report('messagereceived')['title']);
        $this->assertSame([$labelA, $labelB], array_values(array_filter(array_keys($sent), static fn (string $l): bool => str_contains($l, 'rp_m'))), 'most active sender first');
    }

    public function testMessagesAreConfinedToTheUsersOfTheCurrentUrl(): void
    {
        $a = $this->rawUser('m4');
        $elsewhere = $this->rawUser('m5', 5, 1, null);
        $other = $this->secondUrl();
        $this->conn()->executeStatement('INSERT INTO access_url_rel_user (access_url_id, user_id) VALUES (?, ?)', [$other, $elsewhere]);
        $this->message($a, $elsewhere);
        $this->message($elsewhere, $a);
        $sent = $this->messageStats('messagesent');
        $names = array_keys($sent);
        $this->assertCount(1, array_filter($names, static fn (string $l): bool => str_contains($l, 'rp_m4')));
        $this->assertCount(0, array_filter($names, static fn (string $l): bool => str_contains($l, 'rp_m5')));
    }

    // ------------------------------------------------------------- friends

    public function testContactsCountIgnoresHumanResourcesRelationsAndIsPerUser(): void
    {
        $a = $this->rawUser('f1');
        $b = $this->rawUser('f2');
        $c = $this->rawUser('f3');
        $this->userRel($a, $b, 3);
        $this->userRel($a, $c, 3);
        $this->userRel($b, $a, 3);
        $this->userRel($c, $a, 7);
        $stats = $this->report('friends')['stats'];
        $mine = array_values(array_filter($stats, static fn (array $r): bool => str_contains($r['label'], 'rp_f')));
        $this->assertCount(2, $mine);
        $this->assertSame(2, $mine[0]['value']);
        $this->assertStringContainsString('Firstf1 Lastf1', $mine[0]['label']);
        $this->assertSame(1, $mine[1]['value']);
    }

    // ------------------------------------------------------ courses reports

    public function testCoursesByLanguageCountsEveryCourseOnce(): void
    {
        $report = $this->report('coursebylanguage');
        $this->assertSame(
            (int) $this->conn()->fetchOne('SELECT COUNT(*) FROM course'),
            array_sum(array_column($report['stats'], 'value'))
        );
        $this->assertSame(array_column($report['stats'], 'label'), $report['chart']['data']['labels']);
    }

    public function testCoursesByLanguageIsConfinedToTheCurrentUrl(): void
    {
        $before = array_sum(array_column($this->report('coursebylanguage')['stats'], 'value'));
        $other = $this->secondUrl();
        $this->conn()->executeStatement('UPDATE access_url_rel_course SET access_url_id = ? WHERE c_id = 2', [$other]);
        $after = array_sum(array_column($this->report('coursebylanguage')['stats'], 'value'));
        $this->assertSame($before - 1, $after);
    }

    public function testCoursesReportTotalsMatchTheChart(): void
    {
        $report = $this->report('courses');
        $this->assertSame(array_column($report['stats'], 'value'), $report['chart']['data']['datasets'][0]['data']);
    }

    // ---------------------------------------------------------- by date

    private function datedSession(string $title, string $start, string $end, int $status): int
    {
        $id = $this->rawSession($title);
        $this->conn()->executeStatement(
            'UPDATE session SET display_start_date = ?, display_end_date = ?, status = ? WHERE id = ?',
            [$start, $end, $status, $id]
        );

        return $id;
    }

    public function testSessionByDateWithoutRangeAsksForOne(): void
    {
        $report = $this->report('session_by_date');
        $this->assertSame([], $report['table']['items']);
        $this->assertTrue($report['meta']['requiresDateRange']);
    }

    public function testSessionByDateRejectsAnInvertedRange(): void
    {
        $this->expectException(BadRequestHttpException::class);
        $this->report('session_by_date', ['rangeStart' => '2001-02-01', 'rangeEnd' => '2001-01-01']);
    }

    public function testSessionByDateListsSessionsStartingOrEndingInTheRange(): void
    {
        $inside = $this->datedSession('sbd1', '2001-03-05 08:00:00', '2002-03-05 08:00:00', Session::STATUS_PROGRESS);
        $endsInside = $this->datedSession('sbd2', '2000-01-01 00:00:00', '2001-03-20 00:00:00', Session::STATUS_FINISHED);
        $outside = $this->datedSession('sbd3', '2000-01-01 00:00:00', '2000-12-31 00:00:00', Session::STATUS_FINISHED);
        $learners = [$this->rawUser('sbd_a'), $this->rawUser('sbd_b')];
        foreach ($learners as $uid) {
            $this->sessionUser($inside, $uid, 0);
        }
        $this->sessionUser($inside, $this->rawUser('sbd_c', 1), 3);
        $this->sessionCourse($inside, 1);
        $report = $this->report('session_by_date', ['rangeStart' => '2001-03-01', 'rangeEnd' => '2001-03-31']);
        $items = array_column($report['table']['items'], null, 'name');
        $names = array_keys($items);
        $this->assertCount(2, array_filter($names, static fn (string $n): bool => str_starts_with($n, 'rp_sbd')));
        $this->assertNotContains($this->conn()->fetchOne('SELECT title FROM session WHERE id = ?', [$outside]), $names);
        $row = $items[$this->conn()->fetchOne('SELECT title FROM session WHERE id = ?', [$inside])];
        $this->assertSame('2001-03-05 08:00:00', $row['startDate']);
        $this->assertSame('2002-03-05 08:00:00', $row['endDate']);
        $this->assertSame('In progress', $row['status']);
        $this->assertSame(2, $row['students']);
        $this->assertNotSame('', $row['language'], 'language of the first course');
        $this->assertSame(0, $items[$this->conn()->fetchOne('SELECT title FROM session WHERE id = ?', [$endsInside])]['students']);
        $this->assertSame(4, $report['stats'][0]['value'], '30 days = 4 full weeks');
        $this->assertTrue($report['meta']['canExportXls']);
    }

    public function testSessionByDateFiltersByStatusAndExportsTheSameRows(): void
    {
        $this->datedSession('sbd4', '2001-04-05 08:00:00', '2001-04-06 08:00:00', Session::STATUS_PLANNED);
        $this->datedSession('sbd5', '2001-04-07 08:00:00', '2001-04-08 08:00:00', Session::STATUS_CANCELLED);
        $params = ['rangeStart' => '2001-04-01', 'rangeEnd' => '2001-04-30', 'statusId' => Session::STATUS_CANCELLED];
        $report = $this->report('session_by_date', $params);
        $this->assertSame(1, $report['table']['totalItems']);
        $this->assertSame('Cancelled', $report['table']['items'][0]['status']);
        $export = $this->service->getSessionByDateExportData($params);
        $this->assertSame($report['table']['items'], $export['items']);
        $viaDispatcher = $this->service->getExportData('session_by_date', $params);
        $this->assertSame($export, $viaDispatcher);
    }

    /**
     * Sessions are restricted to the current access URL once the portal is multi-URL: a session of another portal or
     * of no portal at all is not reported (the other session reports and lists confine themselves the same way).
     */
    public function testSessionByDateIsConfinedToTheSessionsOfTheCurrentUrl(): void
    {
        $other = $this->secondUrl();
        $this->datedSession('sbd6', '2001-05-05 08:00:00', '2001-05-06 08:00:00', Session::STATUS_PLANNED);
        $orphan = $this->datedSession('sbd7', '2001-05-05 08:00:00', '2001-05-06 08:00:00', Session::STATUS_PLANNED);
        $foreign = $this->datedSession('sbd8', '2001-05-05 08:00:00', '2001-05-06 08:00:00', Session::STATUS_PLANNED);
        $this->conn()->executeStatement('DELETE FROM access_url_rel_session WHERE session_id = ?', [$orphan]);
        $this->conn()->executeStatement('UPDATE access_url_rel_session SET access_url_id = ? WHERE session_id = ?', [$other, $foreign]);
        $report = $this->report('session_by_date', ['rangeStart' => '2001-05-01', 'rangeEnd' => '2001-05-31']);
        $this->assertSame(1, $report['table']['totalItems']);
        $this->assertStringContainsString('rp_sbd6', $report['table']['items'][0]['name']);
    }
}
