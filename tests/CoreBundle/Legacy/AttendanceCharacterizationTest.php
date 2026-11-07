<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use Attendance;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CAttendance;
use Chamilo\Tests\ChamiloTestTrait;
use Database;
use DateTime;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Throwable;

/**
 * Characterization of the raw-SQL methods of the legacy Attendance library
 * (public/main/inc/lib/attendance.lib.php).
 *
 * They pin what the queries return or write TODAY (rows, ordering, group scoping, counters, side effects), so a
 * rewrite to DQL that changes any of it fails here. Quirks are flagged "PINNED QUIRK". All titles start with
 * "at_" (sibling suites share the database).
 *
 * Attendances are inserted as bare c_attendance rows (no resource_node) with prepared statements: creating
 * resource nodes deadlocks on the shared database. The methods that need a resource-linked attendance
 * (getAttendanceList, getAttendanceData, getNumberOfAttendances, attendance_add/edit and everything built on
 * getAttendanceList) are therefore deliberately not covered here.
 *
 * Course 1 (fixture) has no subscribed users, so the users subscribed by a test are the whole course roster.
 */
final class AttendanceCharacterizationTest extends KernelTestCase
{
    use ChamiloTestTrait;

    private const COURSE = 1;
    private const ADMIN = 1;
    private const ANON = 2;
    private const FALLBACK = 3;

    private Connection $conn;
    private int $seq = 0;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        Container::setContainer($container);
        Container::setLegacyServices($container);
        $session = new Session(new MockArraySessionStorage());
        Container::setSession($session);
        $request = Request::create('/main/attendance/index.php');
        $request->setSession($session);
        Container::setRequest($request);

        $this->conn = $container->get('doctrine')->getConnection();
        $this->setContext();
    }

    // ---------------------------------------------------------------- helpers

    private function setContext(int $sessionId = 0): void
    {
        $course = $this->getCourse(self::COURSE);
        \ChamiloSession::write('_user', ['id' => self::ADMIN]);
        \ChamiloSession::write('_real_cid', $course->getId());
        \ChamiloSession::write('_cid', $course->getCode());
        \ChamiloSession::write('_course', api_get_course_info($course->getCode()));
        \ChamiloSession::write('sid', $sessionId);
        \ChamiloSession::erase('login_as');
    }

    private function att(string $title = 'at_att', int $active = 1, int $qualifyMax = 0, int $locked = 0): int
    {
        $this->conn->executeStatement(
            'INSERT INTO c_attendance (title, active, attendance_qualify_max, attendance_weight, locked, require_unique)
             VALUES (?, ?, ?, 1, ?, 0)',
            [$title.'_'.(++$this->seq), $active, $qualifyMax, $locked]
        );
        $id = (int) $this->conn->lastInsertId();
        // CAttendanceRepository::update() insists on a resource node: borrow an existing fixture node (inserting a
        // new resource_node deadlocks on the shared database; the transaction is rolled back anyway).
        $node = $this->conn->fetchOne('SELECT id FROM resource_node ORDER BY id LIMIT 1 OFFSET '.(int) $this->seq);
        $this->conn->executeStatement('UPDATE c_attendance SET resource_node_id = ? WHERE iid = ?', [$node, $id]);

        return $id;
    }

    private function cal(int $attId, string $dateTime, int $done = 0): int
    {
        $this->conn->executeStatement(
            'INSERT INTO c_attendance_calendar (attendance_id, date_time, done_attendance, blocked) VALUES (?, ?, ?, 0)',
            [$attId, $dateTime, $done]
        );

        return (int) $this->conn->lastInsertId();
    }

    private function sheet(int $userId, int $calId, int $presence): void
    {
        $this->conn->executeStatement(
            "INSERT INTO c_attendance_sheet (user_id, attendance_calendar_id, presence, signature) VALUES (?, ?, ?, '')",
            [$userId, $calId, $presence]
        );
    }

    private function result(int $userId, int $attId, int $score): void
    {
        $this->conn->executeStatement(
            'INSERT INTO c_attendance_result (user_id, attendance_id, score) VALUES (?, ?, ?)',
            [$userId, $attId, $score]
        );
    }

    private function group(): int
    {
        $this->conn->executeStatement(
            "INSERT INTO c_group_info (title, status, max_student, doc_state, calendar_state, work_state,
                announcements_state, forum_state, wiki_state, chat_state, self_registration_allowed,
                self_unregistration_allowed, document_access)
             VALUES (?, 1, 0, 1, 1, 1, 1, 1, 1, 1, 0, 0, 0)",
            ['at_group_'.(++$this->seq)]
        );

        return (int) $this->conn->lastInsertId();
    }

    private function calGroup(int $calId, int $groupId): void
    {
        $this->conn->executeStatement(
            'INSERT INTO c_attendance_calendar_rel_group (group_id, calendar_id) VALUES (?, ?)',
            [$groupId, $calId]
        );
    }

    private function groupUser(int $userId, int $groupId): void
    {
        $this->conn->executeStatement(
            "INSERT INTO c_group_rel_user (user_id, group_id, c_id, status, role) VALUES (?, ?, ?, 0, '')",
            [$userId, $groupId, self::COURSE]
        );
    }

    private function subscribe(int $userId, int $status = STUDENT): void
    {
        $this->conn->executeStatement(
            'INSERT INTO course_rel_user (user_id, c_id, relation_type, status, is_tutor, sort, user_course_cat, legal_agreement, progress)
             VALUES (?, ?, 0, ?, 0, 0, 0, 0, 0)',
            [$userId, self::COURSE, $status]
        );
    }

    private function entity(int $attId): CAttendance
    {
        $em = Database::getManager();
        $em->clear();

        return $em->find(CAttendance::class, $attId);
    }

    private function row(string $sql, array $params = []): array|false
    {
        return $this->conn->fetchAssociative($sql, $params);
    }

    private function sheetRows(int $calId): array
    {
        return $this->conn->fetchAllAssociative(
            'SELECT user_id, presence, signature FROM c_attendance_sheet WHERE attendance_calendar_id = ? ORDER BY user_id',
            [$calId]
        );
    }

    private function logTypes(int $attId): array
    {
        return $this->conn->fetchFirstColumn(
            'SELECT lastedit_type FROM c_attendance_sheet_log WHERE attendance_id = ? ORDER BY iid',
            [$attId]
        );
    }

    private function ids(array $calendars): array
    {
        return array_map(static fn ($c) => (int) $c['iid'], $calendars);
    }

    // ---------------------------------------------------------------- simple readers

    public function testGetAttendanceByIdReturnsTheRowOrEmptyArray(): void
    {
        $id = $this->att('at_byid', 1, 7);

        $data = (new Attendance())->get_attendance_by_id($id);

        $this->assertSame((string) $id, (string) $data['iid']);
        $this->assertSame('at_byid_'.$this->seq, $data['title']);
        $this->assertSame(7, (int) $data['attendance_qualify_max']);
        $this->assertNull($data['description']);
        $this->assertArrayHasKey(0, $data, 'fetch_array style: numeric keys are present next to the names');
        $this->assertSame([], (new Attendance())->get_attendance_by_id(0));
        $this->assertSame([], (new Attendance())->get_attendance_by_id('abc'), 'non numeric id is cast to 0');
    }

    public function testGetAttendanceCalendarByIdLocalizesDateAndEmptyWhenMissing(): void
    {
        $att = $this->att();
        $cal = $this->cal($att, '2031-03-04 10:20:30', 1);

        $data = (new Attendance())->get_attendance_calendar_by_id($cal);

        $this->assertSame((string) $att, (string) $data['attendance_id']);
        $this->assertSame(1, (int) $data['done_attendance']);
        $this->assertSame(api_get_local_time('2031-03-04 10:20:30'), $data['date_time'], 'date_time goes through api_get_local_time');
        $this->assertSame([], (new Attendance())->get_attendance_calendar_by_id(0));
    }

    public function testGetCountDatesInsideAttendanceCalendarCountsOnlyThatAttendance(): void
    {
        $a = $this->att();
        $b = $this->att();
        $this->cal($a, '2031-01-01 08:00:00');
        $this->cal($a, '2031-01-02 08:00:00', 1);
        $this->cal($b, '2031-01-03 08:00:00');

        $this->assertSame(2, (int) Attendance::get_count_dates_inside_attendance_calendar($a));
        $this->assertSame(1, (int) Attendance::get_count_dates_inside_attendance_calendar($b));
        $this->assertSame(0, (int) Attendance::get_count_dates_inside_attendance_calendar(0));
    }

    public function testNextCalendarIsTheEarliestNotDoneDateOrZero(): void
    {
        $att = $this->att();
        $this->cal($att, '2031-01-01 08:00:00', 1);
        $later = $this->cal($att, '2031-03-01 08:00:00');
        $sooner = $this->cal($att, '2031-02-01 08:00:00');
        $lib = new Attendance();

        $this->assertSame($sooner, (int) $lib->get_next_attendance_calendar_id($att));
        $this->assertSame(api_get_local_time('2031-02-01 08:00:00'), $lib->getNextAttendanceCalendarDatetime($att));

        $this->conn->executeStatement('UPDATE c_attendance_calendar SET done_attendance = 1 WHERE iid = ?', [$sooner]);
        $this->assertSame($later, (int) $lib->get_next_attendance_calendar_id($att));

        $this->conn->executeStatement('UPDATE c_attendance_calendar SET done_attendance = 1 WHERE iid = ?', [$later]);
        $this->assertSame(0, $lib->get_next_attendance_calendar_id($att), 'int 0 when nothing is left');
        $this->assertSame(0, $lib->getNextAttendanceCalendarDatetime($att), 'int 0 (not null/false) when nothing is left');
    }

    // ---------------------------------------------------------------- get_attendance_calendar

    public function testGetAttendanceCalendarIsOrderedByDateAndDecoratesEachRow(): void
    {
        $att = $this->att();
        $c3 = $this->cal($att, '2031-05-03 09:15:00');
        $c1 = $this->cal($att, '2031-05-01 09:15:00', 1);
        $c2 = $this->cal($att, '2031-05-02 09:15:00');
        $other = $this->cal($this->att(), '2031-05-01 00:00:00');

        $rows = (new Attendance())->get_attendance_calendar($att);

        $this->assertSame([$c1, $c2, $c3], $this->ids($rows), 'ordered by date_time ASC, only this attendance');
        $this->assertNotContains($other, $this->ids($rows));
        $first = $rows[0];
        $this->assertSame('2031-05-01 09:15:00', $first['db_date_time'], 'raw database value');
        $this->assertSame(api_get_local_time('2031-05-01 09:15:00'), $first['date_time']);
        $this->assertSame(api_format_date($first['date_time'], DATE_FORMAT_SHORT), $first['date']);
        $this->assertSame(api_format_date($first['date_time'], TIME_NO_SEC_FORMAT), $first['time']);
        $this->assertSame([], $first['groups'], 'groups key is an empty list when no group is linked');
        $this->assertSame([], (new Attendance())->get_attendance_calendar(0));
    }

    public function testGetAttendanceCalendarTypeFiltersAndFallback(): void
    {
        $att = $this->att();
        $done = $this->cal($att, '2031-05-01 09:00:00', 1);
        $todo1 = $this->cal($att, '2031-05-02 09:00:00');
        $todo2 = $this->cal($att, '2031-05-03 09:00:00');
        $lib = new Attendance();

        $this->assertSame([$done], $this->ids($lib->get_attendance_calendar($att, 'all_done')));
        $this->assertSame([$todo1, $todo2], $this->ids($lib->get_attendance_calendar($att, 'all_not_done')));
        $this->assertSame([$todo1], $this->ids($lib->get_attendance_calendar($att, 'calendar_id', $todo1)));
        $this->assertSame(
            [$done, $todo1, $todo2],
            $this->ids($lib->get_attendance_calendar($att, 'calendar_id', null)),
            'calendar_id type without an id applies no filter'
        );
        $this->assertSame([$done, $todo1, $todo2], $this->ids($lib->get_attendance_calendar($att, 'nonsense')), 'unknown type behaves like all');
        $this->assertSame([], $this->ids($lib->get_attendance_calendar($att, 'today')), 'future dates are not "today"');
    }

    public function testGetAttendanceCalendarDateRangeIsInclusiveAndOnlyAppliedWithSomeBranches(): void
    {
        $att = $this->att();
        $a = $this->cal($att, '2031-06-01 00:00:00');
        $b = $this->cal($att, '2031-06-10 12:00:00');
        $c = $this->cal($att, '2031-06-20 23:59:59');
        $lib = new Attendance();

        $rows = $lib->get_attendance_calendar($att, 'all', null, 0, true, 0, new DateTime('2031-06-01 00:00:00'), new DateTime('2031-06-10 12:00:00'));
        $this->assertSame([$a, $b], $this->ids($rows), 'both bounds are inclusive (>= and <=)');

        $rows = $lib->get_attendance_calendar($att, 'all', null, 0, false, 0, new DateTime('2031-06-10 12:00:01'), null);
        $this->assertSame([$c], $this->ids($rows), 'start only');

        $rows = $lib->get_attendance_calendar($att, 'all', null, 0, true, 0, null, new DateTime('2031-06-01 00:00:00'));
        $this->assertSame([$a], $this->ids($rows), 'end only');
    }

    public function testGetAttendanceCalendarGroupScoping(): void
    {
        $att = $this->att();
        $plain = $this->cal($att, '2031-07-01 08:00:00');
        $grouped = $this->cal($att, '2031-07-02 08:00:00', 1);
        $g = $this->group();
        $this->calGroup($grouped, $g);
        $lib = new Attendance();

        $this->assertSame([$plain], $this->ids($lib->get_attendance_calendar($att)), 'showAll=false hides the group-linked dates');
        $this->assertSame([$plain, $grouped], $this->ids($lib->get_attendance_calendar($att, 'all', null, 0, true)), 'showAll=true shows everything');
        $this->assertSame([$grouped], $this->ids($lib->get_attendance_calendar($att, 'all', null, $g)), 'a group id returns only that group dates');
        $this->assertSame([$grouped], $this->ids($lib->get_attendance_calendar($att, 'all_done', null, $g)));
        $this->assertSame([], $this->ids($lib->get_attendance_calendar($att, 'all_not_done', null, $g)));
        $this->assertSame([], $lib->get_attendance_calendar($att, 'all', null, $this->group()), 'an unlinked group has no dates');

        $rows = $lib->get_attendance_calendar($att, 'all', null, 0, true);
        $this->assertSame([], $rows[0]['groups']);
        $this->assertCount(1, $rows[1]['groups']);
        $this->assertSame($g, (int) $rows[1]['groups'][0]['group_id']);
    }

    public function testGetAttendanceCalendarGroupBranchIgnoresDateRange(): void
    {
        // PINNED QUIRK: when a group id is given the SQL is rebuilt from scratch, dropping the $whereDate range.
        $att = $this->att();
        $g = $this->group();
        $old = $this->cal($att, '2031-08-01 08:00:00');
        $this->calGroup($old, $g);

        $rows = (new Attendance())->get_attendance_calendar(
            $att,
            'all',
            null,
            $g,
            true,
            0,
            new DateTime('2032-01-01 00:00:00'),
            new DateTime('2032-12-31 00:00:00')
        );

        $this->assertSame([$old], $this->ids($rows));
    }

    // ---------------------------------------------------------------- get_number_of_attendance_calendar

    public function testNumberOfCalendarsWithoutUserAndGroupExcludesGroupDates(): void
    {
        $att = $this->att();
        $this->cal($att, '2031-09-01 08:00:00', 1);
        $this->cal($att, '2031-09-02 08:00:00');
        $grouped = $this->cal($att, '2031-09-03 08:00:00', 1);
        $this->calGroup($grouped, $this->group());

        $this->assertSame(2, (int) Attendance::get_number_of_attendance_calendar($att));
        $this->assertSame(1, (int) Attendance::get_number_of_attendance_calendar($att, 0, true), 'done only');
        $this->assertSame(0, (int) Attendance::get_number_of_attendance_calendar(0));
    }

    public function testNumberOfCalendarsForAGroupCountsItsLinkedDates(): void
    {
        $att = $this->att();
        $g = $this->group();
        $this->cal($att, '2031-09-01 08:00:00', 1);
        $a = $this->cal($att, '2031-09-02 08:00:00');
        $b = $this->cal($att, '2031-09-03 08:00:00', 1);
        $this->calGroup($a, $g);
        $this->calGroup($b, $g);

        $this->assertSame(2, (int) Attendance::get_number_of_attendance_calendar($att, $g));
        $this->assertSame(1, (int) Attendance::get_number_of_attendance_calendar($att, $g, true));
        $this->assertSame(2, (int) Attendance::get_number_of_attendance_calendar($att, $g, null, self::ANON), 'with a user and a group the user is not looked at');
    }

    public function testNumberOfCalendarsForAUserCountsPlainDatesAndHisGroupsDates(): void
    {
        $att = $this->att();
        $mine = $this->group();
        $theirs = $this->group();
        $this->groupUser(self::ANON, $mine);
        $this->cal($att, '2031-09-01 08:00:00', 1);
        $forMine = $this->cal($att, '2031-09-02 08:00:00');
        $forTheirs = $this->cal($att, '2031-09-03 08:00:00', 1);
        $this->calGroup($forMine, $mine);
        $this->calGroup($forTheirs, $theirs);

        $this->assertSame(2, (int) Attendance::get_number_of_attendance_calendar($att, 0, null, self::ANON), 'plain date + my group date');
        $this->assertSame(1, (int) Attendance::get_number_of_attendance_calendar($att, 0, true, self::ANON), 'done: only the plain one');
        $this->assertSame(1, (int) Attendance::get_number_of_attendance_calendar($att, 0, null, self::FALLBACK), 'a user in no group only sees the plain date');
    }

    // ---------------------------------------------------------------- scores and faults

    public function testGetUserScoreWithoutGroupReadsTheResultTable(): void
    {
        $att = $this->att();
        $this->result(self::ANON, $att, 4);
        $lib = new Attendance();

        $this->assertSame(4, (int) $lib->get_user_score(self::ANON, $att));
        $this->assertSame(0, $lib->get_user_score(self::FALLBACK, $att), 'no result row: int 0');
    }

    public function testGetUserScoreWithGroupCountsPresencesOnTheGroupDatesOnly(): void
    {
        $att = $this->att();
        $g = $this->group();
        $inGroup1 = $this->cal($att, '2031-10-01 08:00:00');
        $inGroup2 = $this->cal($att, '2031-10-02 08:00:00');
        $outside = $this->cal($att, '2031-10-03 08:00:00');
        $this->calGroup($inGroup1, $g);
        $this->calGroup($inGroup2, $g);
        $this->sheet(self::ANON, $inGroup1, 1);
        $this->sheet(self::ANON, $inGroup2, 0);
        $this->sheet(self::ANON, $outside, 1);
        $this->result(self::ANON, $att, 99);

        $this->assertSame(1, (int) (new Attendance())->get_user_score(self::ANON, $att, $g), 'live count, result table ignored');
        $this->assertSame(0, (int) (new Attendance())->get_user_score(self::FALLBACK, $att, $g));
    }

    public function testGetFaultsOfUserComputesFaultsPercentAndColor(): void
    {
        $att = $this->att();
        $lib = new Attendance();
        $done = [];
        for ($i = 1; $i <= 8; $i++) {
            $done[] = $this->cal($att, sprintf('2031-11-%02d 08:00:00', $i), 1);
        }
        $this->cal($att, '2031-11-20 08:00:00', 0);
        $this->cal($att, '2031-11-21 08:00:00', 0);
        // 10 dates, 8 done. Score 8 => no fault.
        $this->result(self::ANON, $att, 8);
        $this->assertSame(
            ['faults' => 0, 'total' => 10, 'faults_porcent' => 0.0, 'color_bar' => ''],
            $this->normalize($lib->get_faults_of_user(self::ANON, $att))
        );

        // Score 7 => 1 fault = 10% => still no colour (strictly greater than 10 needed).
        $this->conn->executeStatement('UPDATE c_attendance_result SET score = 7 WHERE attendance_id = ?', [$att]);
        $r = $this->normalize($lib->get_faults_of_user(self::ANON, $att));
        $this->assertSame([1, 10, 10.0, ''], [$r['faults'], $r['total'], $r['faults_porcent'], $r['color_bar']]);

        // Score 6 => 2 faults = 20% => orange.
        $this->conn->executeStatement('UPDATE c_attendance_result SET score = 6 WHERE attendance_id = ?', [$att]);
        $r = $this->normalize($lib->get_faults_of_user(self::ANON, $att));
        $this->assertSame([2, 20.0, '#F90'], [$r['faults'], $r['faults_porcent'], $r['color_bar']]);

        // Score 5 => 3 faults = 30% => red.
        $this->conn->executeStatement('UPDATE c_attendance_result SET score = 5 WHERE attendance_id = ?', [$att]);
        $r = $this->normalize($lib->get_faults_of_user(self::ANON, $att));
        $this->assertSame([3, 30.0, '#f28989'], [$r['faults'], $r['faults_porcent'], $r['color_bar']]);

        // Score above the done count never gives negative faults.
        $this->conn->executeStatement('UPDATE c_attendance_result SET score = 50 WHERE attendance_id = ?', [$att]);
        $this->assertSame(0, $this->normalize($lib->get_faults_of_user(self::ANON, $att))['faults']);

        // No result row at all: every done date is a fault.
        $r = $this->normalize($lib->get_faults_of_user(self::FALLBACK, $att));
        $this->assertSame([8, 10, 80.0, '#f28989'], [$r['faults'], $r['total'], $r['faults_porcent'], $r['color_bar']]);
    }

    public function testGetFaultsOfUserWithoutAnyDateIsAllZero(): void
    {
        $att = $this->att();
        $this->result(self::ANON, $att, 3);

        $r = $this->normalize((new Attendance())->get_faults_of_user(self::ANON, $att));

        $this->assertSame(['faults' => 0, 'total' => 0, 'faults_porcent' => 0.0, 'color_bar' => ''], $r);
    }

    public function testGetFaultsOfUserOnlyCountsDatesOfTheUsersGroups(): void
    {
        $att = $this->att();
        $mine = $this->group();
        $theirs = $this->group();
        $this->groupUser(self::ANON, $mine);
        $this->cal($att, '2031-11-01 08:00:00', 1); // plain
        $forMine = $this->cal($att, '2031-11-02 08:00:00', 1);
        $forTheirs = $this->cal($att, '2031-11-03 08:00:00', 1);
        $this->calGroup($forMine, $mine);
        $this->calGroup($forTheirs, $theirs);
        $this->result(self::ANON, $att, 1);

        $r = $this->normalize((new Attendance())->get_faults_of_user(self::ANON, $att));

        $this->assertSame([2, 1, 50.0], [$r['total'], $r['faults'], $r['faults_porcent']], 'total = plain + own group dates; 2 done - score 1');
    }

    // ---------------------------------------------------------------- roster

    public function testGetUsersRelCourseListsStudentsOnlyAndAddsResultsWhenAnAttendanceIsGiven(): void
    {
        $att = $this->att();
        $this->subscribe(self::ADMIN, COURSEMANAGER);
        $this->subscribe(self::ANON);
        $this->cal($att, '2031-12-01 08:00:00', 1);
        $this->result(self::ANON, $att, 0);
        $lib = new Attendance();

        $users = $lib->get_users_rel_course();

        $this->assertCount(1, $users, 'user id <= 1 and course managers are skipped');
        $user = reset($users);
        $this->assertSame(self::ANON, (int) $user['user_id']);
        $this->assertSame('anon', $user['username']);
        $this->assertSame('Joe', $user['lastname']);
        $this->assertSame('Anonymous', $user['firstname']);
        $this->assertArrayNotHasKey('attendance_result', $user);
        $this->assertArrayNotHasKey('official_code', $user, 'official code column is off by default');

        $with = $lib->get_users_rel_course($att);
        $withUser = reset($with);
        $this->assertSame('1/1 (100%)', $withUser['attendance_result'], 'faults/total (percent%)');
        $this->assertSame('#f28989', $withUser['result_color_bar']);
    }

    public function testGetUsersRelCourseFiltersByGroupMembership(): void
    {
        $this->subscribe(self::ANON);
        $g = $this->group();
        $other = $this->group();
        $this->groupUser(self::ANON, $other);
        $lib = new Attendance();

        $this->assertSame([], $lib->get_users_rel_course(0, $g), 'not a member of that group');

        $this->groupUser(self::ANON, $g);
        $users = $lib->get_users_rel_course(0, $g);
        $this->assertCount(1, $users);
        $this->assertSame(self::ANON, (int) reset($users)['user_id']);
    }

    // ---------------------------------------------------------------- sheets

    public function testAttendanceSheetAddStoresPresenceAndAbsenceAndMarksTheDateDone(): void
    {
        $att = $this->att('at_sheet', 1, 0);
        $c1 = $this->cal($att, '2032-01-01 08:00:00');
        $this->cal($att, '2032-01-02 08:00:00');
        $this->subscribe(self::ANON);
        $this->subscribe(self::FALLBACK);
        $lib = new Attendance();

        $affected = $lib->attendance_sheet_add($c1, [self::ANON], $this->entity($att));

        $roster = array_map('intval', array_keys($lib->get_users_rel_course()));
        $this->assertNotEmpty($roster);
        $rows = $this->sheetRows($c1);
        $presence = [];
        foreach ($rows as $row) {
            $presence[(int) $row['user_id']] = (int) $row['presence'];
            $this->assertSame('', $row['signature']);
        }
        $this->assertSame(1, $presence[self::ANON], 'present user stored with presence 1');
        $this->assertSame(count($roster), count($rows), 'every roster user gets a row (absent ones with 0)');
        $this->assertSame(count($roster), $affected, 'affected rows = inserted rows');
        foreach ($roster as $uid) {
            if (self::ANON !== $uid) {
                $this->assertSame(0, $presence[$uid]);
            }
        }
        $this->assertSame(1, (int) $this->row('SELECT done_attendance FROM c_attendance_calendar WHERE iid = ?', [$c1])['done_attendance']);
        $this->assertSame(1, (int) $this->row('SELECT attendance_qualify_max FROM c_attendance WHERE iid = ?', [$att])['attendance_qualify_max'], 'qualify max = number of done dates');
        $this->assertSame(1, (int) $this->row('SELECT score FROM c_attendance_result WHERE attendance_id = ? AND user_id = ?', [$att, self::ANON])['score']);
        $this->assertSame([Attendance::DONE_ATTENDANCE_LOG_TYPE], $this->logTypes($att));
        $log = $this->row('SELECT * FROM c_attendance_sheet_log WHERE attendance_id = ?', [$att]);
        $this->assertSame(self::ADMIN, (int) $log['lastedit_user_id']);
        $this->assertSame('2032-01-01 08:00:00', $log['calendar_date_value'], 'the logged date is the (UTC) calendar date');
        $this->assertLessThanOrEqual(30, abs(time() - strtotime($log['lastedit_date'].' UTC')));
    }

    public function testAttendanceSheetAddASecondTimeUpdatesAndLogsAnUpdate(): void
    {
        $att = $this->att();
        $c1 = $this->cal($att, '2032-02-01 08:00:00');
        $this->subscribe(self::ANON);
        $this->subscribe(self::FALLBACK);
        $lib = new Attendance();
        $lib->attendance_sheet_add($c1, [self::ANON], $this->entity($att));
        $before = (int) $this->row('SELECT COUNT(*) c FROM c_attendance_sheet WHERE attendance_calendar_id = ?', [$c1])['c'];

        // Same data again: MySQL reports 0 changed rows, so nothing is logged.
        $same = $lib->attendance_sheet_add($c1, [self::ANON], $this->entity($att));
        $this->assertSame(0, $same, 'identical presence data changes no row');
        $this->assertSame([Attendance::DONE_ATTENDANCE_LOG_TYPE], $this->logTypes($att), 'no extra log when nothing changed');

        // Flip the presence of the present user to absent.
        $changed = $lib->attendance_sheet_add($c1, [], $this->entity($att));
        $this->assertSame(1, $changed);
        $this->assertSame(
            $before,
            (int) $this->row('SELECT COUNT(*) c FROM c_attendance_sheet WHERE attendance_calendar_id = ?', [$c1])['c'],
            'no duplicate rows'
        );
        $this->assertSame(0, (int) $this->row('SELECT presence FROM c_attendance_sheet WHERE attendance_calendar_id = ? AND user_id = ?', [$c1, self::ANON])['presence']);
        $this->assertSame(0, (int) $this->row('SELECT score FROM c_attendance_result WHERE attendance_id = ? AND user_id = ?', [$att, self::ANON])['score']);
        $this->assertSame([Attendance::DONE_ATTENDANCE_LOG_TYPE, Attendance::UPDATED_ATTENDANCE_LOG_TYPE], $this->logTypes($att));
    }

    public function testUpdateUsersResultsCountsPresencesInsertsOrUpdatesAndSetsQualifyMax(): void
    {
        $att = $this->att('at_res', 1, 99);
        $c1 = $this->cal($att, '2032-03-01 08:00:00', 1);
        $c2 = $this->cal($att, '2032-03-02 08:00:00', 1);
        $c3 = $this->cal($att, '2032-03-03 08:00:00', 0);
        $this->sheet(self::ANON, $c1, 1);
        $this->sheet(self::ANON, $c2, 0);
        $this->sheet(self::ANON, $c3, 1);
        $this->sheet(self::FALLBACK, $c1, 1);
        $this->sheet(self::FALLBACK, $c2, 1);
        $this->result(self::FALLBACK, $att, 50);

        (new Attendance())->updateUsersResults([self::ANON, self::FALLBACK], $this->entity($att));

        $scores = $this->conn->fetchAllKeyValue('SELECT user_id, score FROM c_attendance_result WHERE attendance_id = ?', [$att]);
        ksort($scores);
        $this->assertSame([self::ANON => 2, self::FALLBACK => 2], array_map('intval', $scores), 'present on ALL dates (done or not); existing row updated, new row inserted');
        $this->assertSame(2, (int) $this->row('SELECT COUNT(*) c FROM c_attendance_result WHERE attendance_id = ?', [$att])['c']);
        $this->assertSame(2, (int) $this->row('SELECT attendance_qualify_max FROM c_attendance WHERE iid = ?', [$att])['attendance_qualify_max'], 'overwritten with the done-date count');
    }

    public function testUpdateUsersResultsWithoutDatesWritesZeroScores(): void
    {
        $att = $this->att('at_res0', 1, 5);

        (new Attendance())->updateUsersResults([self::ANON], $this->entity($att));

        $this->assertSame(0, (int) $this->row('SELECT score FROM c_attendance_result WHERE attendance_id = ? AND user_id = ?', [$att, self::ANON])['score']);
        $this->assertSame(0, (int) $this->row('SELECT attendance_qualify_max FROM c_attendance WHERE iid = ?', [$att])['attendance_qualify_max']);
    }

    public function testGetUsersAttendanceSheetForAllUsersIsKeyedByUserAndCalendar(): void
    {
        $att = $this->att();
        $c1 = $this->cal($att, '2032-04-01 08:00:00');
        $c2 = $this->cal($att, '2032-04-02 08:00:00');
        $this->subscribe(self::ANON);
        $this->sheet(self::ANON, $c1, 1);
        $this->sheet(self::ANON, $c2, 0);
        $this->sheet(self::ADMIN, $c1, 1); // not on the roster
        $lib = new Attendance();

        $data = $lib->get_users_attendance_sheet($att);

        $this->assertSame([self::ANON], array_keys($data), 'only roster users');
        $this->assertSame(1, (int) $data[self::ANON][$c1]['presence']);
        $this->assertSame(0, (int) $data[self::ANON][$c2]['presence']);
        $this->assertSame([], $lib->get_users_attendance_sheet($this->att()), 'attendance without dates gives nothing');
    }

    public function testGetUsersAttendanceSheetForOneUserIsOrderedByDateWithinTheRange(): void
    {
        $att = $this->att();
        $c3 = $this->cal($att, '2032-05-03 08:00:00');
        $c1 = $this->cal($att, '2032-05-01 08:00:00');
        $c2 = $this->cal($att, '2032-05-02 08:00:00');
        $this->sheet(self::ANON, $c3, 0);
        $this->sheet(self::ANON, $c1, 1);
        $this->sheet(self::ANON, $c2, 1);
        $lib = new Attendance();

        $data = $lib->get_users_attendance_sheet($att, self::ANON);

        $this->assertSame([self::ANON], array_keys($data));
        $this->assertSame(
            [api_convert_and_format_date('2032-05-01 08:00:00', null, date_default_timezone_get()), api_convert_and_format_date('2032-05-02 08:00:00', null, date_default_timezone_get()), api_convert_and_format_date('2032-05-03 08:00:00', null, date_default_timezone_get())],
            array_column($data[self::ANON], 'date_time')
        );
        $this->assertSame([1, 1, 0], array_map('intval', array_column($data[self::ANON], 'presence')));

        $ranged = $lib->get_users_attendance_sheet($att, self::ANON, 0, 0, new DateTime('2032-05-02 00:00:00'), new DateTime('2032-05-02 23:59:59'));
        $this->assertSame([1], array_map('intval', array_column($ranged[self::ANON], 'presence')));

        // Both bounds are inclusive (>= start, <= end): a range that starts and ends exactly on a
        // stored date still returns it. A rewrite that makes either bound exclusive must fail here.
        $exact = $lib->get_users_attendance_sheet($att, self::ANON, 0, 0, new DateTime('2032-05-02 08:00:00'), new DateTime('2032-05-02 08:00:00'));
        $this->assertSame([1], array_map('intval', array_column($exact[self::ANON], 'presence')), 'start and end bounds are inclusive');

        $this->assertSame([], $lib->get_users_attendance_sheet($att, self::FALLBACK), 'a user without sheet rows has no data');
    }

    // ---------------------------------------------------------------- calendar writes

    public function testAttendanceCalendarAddPersistsTheDateLinksGroupsAndUnlocks(): void
    {
        $att = $this->att();
        $g1 = $this->group();
        $g2 = $this->group();
        $lib = new Attendance();
        $lib->set_date_time('2032-06-01 10:00:00');

        $this->assertTrue($lib->attendance_calendar_add($this->entity($att), [$g1, 0, $g2, $g1]));

        $cal = $this->row('SELECT * FROM c_attendance_calendar WHERE attendance_id = ?', [$att]);
        $this->assertSame('2032-06-01 10:00:00', $cal['date_time']);
        $this->assertSame([0, 0], [(int) $cal['done_attendance'], (int) $cal['blocked']]);
        $groups = array_map('intval', $this->conn->fetchFirstColumn('SELECT group_id FROM c_attendance_calendar_rel_group WHERE calendar_id = ? ORDER BY group_id', [$cal['iid']]));
        // Fixed: the duplicate check now looks up the right group, so a repeated id is linked once.
        $this->assertSame([$g1, $g2], $groups, 'empty ids skipped, repeated id linked once');
        $this->assertSame(0, (int) $this->row('SELECT locked FROM c_attendance WHERE iid = ?', [$att])['locked'], 'a not-done date keeps the attendance unlocked');
        $this->assertSame([Attendance::LOCKED_ATTENDANCE_LOG_TYPE], $this->logTypes($att), 'lock() logs even when it unlocks');
    }

    public function testAddAttendanceCalendarToGroupReturnsFalseForAnEmptyList(): void
    {
        $lib = new Attendance();
        $cal = $this->cal($this->att(), '2032-06-02 10:00:00');
        $g = $this->group();

        $this->assertFalse($lib->addAttendanceCalendarToGroup($cal, self::COURSE, []));
        $this->assertTrue($lib->addAttendanceCalendarToGroup($cal, self::COURSE, [0, $g]));
        // Fixed: the duplicate check passed ($calendarId, $courseId, $groupId) to a ($calendarId, $groupId) method, so it
        // looked for group_id = $courseId and linked the same group twice.
        $this->assertTrue($lib->addAttendanceCalendarToGroup($cal, self::COURSE, [$g]));
        $this->assertSame(1, (int) $this->row('SELECT COUNT(*) c FROM c_attendance_calendar_rel_group WHERE calendar_id = ?', [$cal])['c']);
    }

    public function testGroupLinkReadersAndDelete(): void
    {
        $lib = new Attendance();
        $cal = $this->cal($this->att(), '2032-06-03 10:00:00');
        $g1 = $this->group();
        $g2 = $this->group();
        $this->calGroup($cal, $g1);
        $this->calGroup($cal, $g2);

        $this->assertCount(2, $lib->getGroupListByAttendanceCalendar($cal));
        $one = $lib->getAttendanceCalendarGroup($cal, $g2);
        $this->assertCount(1, $one);
        $this->assertSame($g2, (int) $one[0]['group_id']);
        $this->assertSame([], $lib->getAttendanceCalendarGroup($cal, 0));

        $lib->deleteAttendanceCalendarGroup($cal, self::COURSE);
        $this->assertSame([], $lib->getGroupListByAttendanceCalendar($cal), 'all group links of the date are removed');
    }

    public function testAttendanceRepeatCalendarAddDailyAndWeekly(): void
    {
        $start = strtotime('2032-07-01 08:00:00 UTC');
        $lib = new Attendance();

        $daily = $this->att('at_daily');
        $lib->attendance_repeat_calendar_add($this->entity($daily), $start, $start + 2 * 86400, 'daily');
        $this->assertSame(
            [gmdate('Y-m-d H:i:s', $start), gmdate('Y-m-d H:i:s', $start + 86400), gmdate('Y-m-d H:i:s', $start + 2 * 86400)],
            $this->conn->fetchFirstColumn('SELECT date_time FROM c_attendance_calendar WHERE attendance_id = ? ORDER BY date_time', [$daily]),
            'start date + one per day, end date inclusive'
        );

        $weekly = $this->att('at_weekly');
        $lib->attendance_repeat_calendar_add($this->entity($weekly), $start, $start + 14 * 86400 - 1, 'weekly');
        $this->assertSame(
            [gmdate('Y-m-d H:i:s', $start), gmdate('Y-m-d H:i:s', $start + 604800)],
            $this->conn->fetchFirstColumn('SELECT date_time FROM c_attendance_calendar WHERE attendance_id = ? ORDER BY date_time', [$weekly])
        );

        $none = $this->att('at_unknown_repeat');
        $lib->attendance_repeat_calendar_add($this->entity($none), $start, $start + 86400, 'bogus');
        $this->assertSame(1, (int) $this->row('SELECT COUNT(*) c FROM c_attendance_calendar WHERE attendance_id = ?', [$none])['c'], 'unknown repeat type: only the start date');
    }

    public function testAttendanceCalendarEditMovesTheDateUnlessItAlreadyExists(): void
    {
        $att = $this->att();
        $c1 = $this->cal($att, '2032-08-01 08:00:00');
        $this->cal($att, '2032-08-02 08:00:00');
        $lib = new Attendance();

        $lib->set_date_time('2032-08-05 09:30:00');
        $this->assertSame(0, $lib->attendance_calendar_edit($c1, $this->entity($att)), 'always returns 0');
        $this->assertSame('2032-08-05 09:30:00', $this->row('SELECT date_time FROM c_attendance_calendar WHERE iid = ?', [$c1])['date_time']);

        // PINNED QUIRK: moving onto a date that another row of the same attendance already has is silently ignored.
        $lib->set_date_time('2032-08-02 08:00:00');
        $lib->attendance_calendar_edit($c1, $this->entity($att));
        $this->assertSame('2032-08-05 09:30:00', $this->row('SELECT date_time FROM c_attendance_calendar WHERE iid = ?', [$c1])['date_time']);
        $this->assertSame(0, (int) $this->row('SELECT locked FROM c_attendance WHERE iid = ?', [$att])['locked']);
    }

    public function testAttendanceCalendarDeleteOneDateRemovesSheetAndRecomputesResults(): void
    {
        $att = $this->att('at_del', 1, 2);
        $c1 = $this->cal($att, '2032-09-01 08:00:00', 1);
        $c2 = $this->cal($att, '2032-09-02 08:00:00', 1);
        $this->subscribe(self::ANON);
        $this->sheet(self::ANON, $c1, 1);
        $this->sheet(self::ANON, $c2, 1);
        $this->result(self::ANON, $att, 2);

        $affected = (new Attendance())->attendance_calendar_delete($c1, $this->entity($att));

        $this->assertSame(1, $affected);
        $this->assertFalse($this->row('SELECT iid FROM c_attendance_calendar WHERE iid = ?', [$c1]));
        $this->assertSame([], $this->sheetRows($c1));
        $this->assertCount(1, $this->sheetRows($c2), 'other dates untouched');
        $this->assertSame(1, (int) $this->row('SELECT score FROM c_attendance_result WHERE attendance_id = ? AND user_id = ?', [$att, self::ANON])['score']);
        $this->assertSame(1, (int) $this->row('SELECT attendance_qualify_max FROM c_attendance WHERE iid = ?', [$att])['attendance_qualify_max']);
    }

    public function testAttendanceCalendarDeleteAllRemovesEveryDateOfTheAttendanceOnly(): void
    {
        $att = $this->att();
        $other = $this->att();
        $c1 = $this->cal($att, '2032-10-01 08:00:00');
        $c2 = $this->cal($att, '2032-10-02 08:00:00', 1);
        $keep = $this->cal($other, '2032-10-01 08:00:00');
        $this->sheet(self::ANON, $c2, 1);

        $affected = (new Attendance())->attendance_calendar_delete(0, $this->entity($att), true);

        $this->assertSame(2, $affected);
        $this->assertSame(0, (int) $this->row('SELECT COUNT(*) c FROM c_attendance_calendar WHERE attendance_id = ?', [$att])['c']);
        $this->assertSame([], $this->sheetRows($c2));
        $this->assertNotFalse($this->row('SELECT iid FROM c_attendance_calendar WHERE iid = ?', [$keep]));
        $this->assertNotContains($c1, $this->conn->fetchFirstColumn('SELECT iid FROM c_attendance_calendar'));
    }

    public function testAttendanceCalendarDeleteOfAGroupLinkedDateRemovesLinksSheetAndDate(): void
    {
        // Fixed: the calendar row used to be deleted before its group links, and the FK has no ON DELETE rule, so
        // deleting a group-linked date failed and left the date behind. Links and sheet rows now go first.
        $att = $this->att();
        $c1 = $this->cal($att, '2032-11-01 08:00:00');
        $this->calGroup($c1, $this->group());
        $this->sheet(self::ANON, $c1, 1);

        $affected = (new Attendance())->attendance_calendar_delete($c1, $this->entity($att));

        $this->assertSame(1, $affected);
        $this->assertFalse($this->row('SELECT iid FROM c_attendance_calendar WHERE iid = ?', [$c1]), 'the date is gone');
        $this->assertSame([], $this->sheetRows($c1));
        $this->assertSame(0, (int) $this->row('SELECT COUNT(*) c FROM c_attendance_calendar_rel_group WHERE calendar_id = ?', [$c1])['c']);
    }

    public function testAttendanceCalendarDeleteAllRemovesGroupLinkedDates(): void
    {
        $att = $this->att();
        $c1 = $this->cal($att, '2032-11-02 08:00:00');
        $this->calGroup($c1, $this->group());

        $affected = (new Attendance())->attendance_calendar_delete(0, $this->entity($att), true);

        $this->assertSame(1, $affected);
        $this->assertSame(0, (int) $this->row('SELECT COUNT(*) c FROM c_attendance_calendar WHERE attendance_id = ?', [$att])['c']);
        $this->assertSame(0, (int) $this->row('SELECT COUNT(*) c FROM c_attendance_calendar_rel_group WHERE calendar_id = ?', [$c1])['c']);
    }

    // ---------------------------------------------------------------- state changes through the repository

    public function testLockAndUnlockPersistTheFlagAndLogEachCall(): void
    {
        $att = $this->att();
        $lib = new Attendance();

        $this->assertTrue($lib->lock($this->entity($att)));
        $this->assertSame(1, (int) $this->row('SELECT locked FROM c_attendance WHERE iid = ?', [$att])['locked']);

        $this->assertTrue($lib->lock($this->entity($att), false));
        $this->assertSame(0, (int) $this->row('SELECT locked FROM c_attendance WHERE iid = ?', [$att])['locked']);

        $this->assertSame([Attendance::LOCKED_ATTENDANCE_LOG_TYPE, Attendance::LOCKED_ATTENDANCE_LOG_TYPE], $this->logTypes($att));
        $log = $this->row('SELECT * FROM c_attendance_sheet_log WHERE attendance_id = ? ORDER BY iid LIMIT 1', [$att]);
        $this->assertSame(self::ADMIN, (int) $log['lastedit_user_id']);
        $this->assertNull($log['calendar_date_value']);
    }

    public function testChangeVisibilityAndDeleteSetTheActiveFlag(): void
    {
        $att = $this->att('at_vis', 1);
        $lib = new Attendance();

        $this->assertTrue($lib->changeVisibility($this->entity($att), 0));
        $this->assertSame(0, (int) $this->row('SELECT active FROM c_attendance WHERE iid = ?', [$att])['active']);

        $this->assertTrue($lib->changeVisibility($this->entity($att)));
        $this->assertSame(1, (int) $this->row('SELECT active FROM c_attendance WHERE iid = ?', [$att])['active'], 'default status is visible');

        // allow_delete_attendance is "true" in the test database: delete is a soft delete (active = 2).
        $this->assertTrue($lib->attendance_delete($this->entity($att)));
        $this->assertSame(2, (int) $this->row('SELECT active FROM c_attendance WHERE iid = ?', [$att])['active'], 'row kept, flagged 2');
    }

    public function testAttendanceRestoreReactivatesAndReturnsAffectedRows(): void
    {
        $a = $this->att('at_rest_a', 2);
        $b = $this->att('at_rest_b', 2);
        $active = $this->att('at_rest_c', 1);
        $lib = new Attendance();

        $this->assertSame(1, $lib->attendance_restore($a));
        $this->assertSame(1, (int) $this->row('SELECT active FROM c_attendance WHERE iid = ?', [$a])['active']);
        $this->assertSame(0, $lib->attendance_restore($active), 'already active: MySQL reports 0 changed rows');
        $this->assertSame(0, $lib->attendance_restore(0), 'unknown id: 0');

        // PINNED QUIRK: with an array only the affected rows of the LAST update are returned.
        $this->assertSame(0, $lib->attendance_restore([$b, $active]));
        $this->assertSame(1, (int) $this->row('SELECT active FROM c_attendance WHERE iid = ?', [$b])['active'], 'but every id was restored');
    }

    public function testIsAllCalendarDoneComparesDoneAndTotalDates(): void
    {
        $att = $this->att();
        $this->cal($att, '2033-01-01 08:00:00', 1);
        $this->cal($att, '2033-01-02 08:00:00', 0);

        $this->assertFalse(Attendance::is_all_attendance_calendar_done($this->entity($att)));
        $this->assertSame(1, Attendance::get_done_attendance_calendar($this->entity($att)));

        $this->conn->executeStatement('UPDATE c_attendance_calendar SET done_attendance = 1 WHERE attendance_id = ?', [$att]);
        $this->assertTrue(Attendance::is_all_attendance_calendar_done($this->entity($att)));
        $this->assertSame(2, Attendance::get_done_attendance_calendar($this->entity($att)));
    }

    // ---------------------------------------------------------------- login grid

    public function testGetAttendanceLoginBuildsDateColumnsAndMarksCourseAccessDays(): void
    {
        $this->subscribe(self::ANON);
        $courseId = (int) $this->getCourse(self::COURSE)->getId();
        foreach ([['2033-02-01 10:00:00', 0], ['2033-02-03 23:00:00', 0], ['2033-02-02 10:00:00', 99999], ['2033-03-01 10:00:00', 0]] as [$date, $sid]) {
            $this->conn->executeStatement(
                "INSERT INTO track_e_course_access (user_id, c_id, login_course_date, counter, session_id, user_ip) VALUES (?, ?, ?, 1, ?, '127.0.0.1')",
                [self::ANON, $courseId, $date, $sid]
            );
        }
        $lib = new Attendance();

        $data = $lib->getAttendanceLogin('2033-02-01 00:00:00', '2033-02-03 23:59:59');

        $this->assertSame(['2033-02-01', '2033-02-02', '2033-02-03'], $data['dateList']);
        $this->assertSame(
            [true, true],
            [$data['results'][self::ANON]['2033-02-01'], $data['results'][self::ANON]['2033-02-03']]
        );
        $this->assertArrayNotHasKey('2033-02-02', $data['results'][self::ANON], 'other session and out-of-range accesses are ignored');
        $this->assertSame([self::ANON], array_map(static fn ($u) => (int) $u['user_id'], array_values($data['users'])));
        $this->assertFalse($lib->getAttendanceLogin('0000-00-00', '2033-02-03'));
        $this->assertFalse($lib->getAttendanceLogin('2033-02-01', ''));
    }

    // ---------------------------------------------------------------- internal

    /**
     * faults_porcent may come back as int 0 or a float from round(); compare it as a float.
     */
    private function normalize(array $r): array
    {
        $r['faults_porcent'] = (float) $r['faults_porcent'];

        return $r;
    }
}
