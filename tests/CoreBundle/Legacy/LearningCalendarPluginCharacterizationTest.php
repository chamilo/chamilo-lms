<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use Chamilo\CoreBundle\Exception\NotAllowedException;
use LearningCalendarPlugin;

/**
 * Characterization of public/plugin/LearningCalendar/LearningCalendarPlugin.php.
 *
 * LIVE (called from a plugin page, from the my-progress / my-space / agenda / usergroup libraries or from
 * model.ajax.php): getCalendarList, getCalendar, getCalendarCount, getCalendarEventCount, getUsersPerCalendar,
 * getUsersPerCalendarCount, getUserCalendar, assignUserToCalendar, findUserForCalendarAssignment,
 * deleteAllCalendarFromUser, copyCalendar, deleteCalendar, toogleDayType, getEvents, protectCalendar,
 * getPersonalEvents(ForApi), getUserEvents, getCalendarsEventsByDate, getUserStats(Panel), getControlPoints,
 * getGradebookEvaluationList(ToString), getItemCountChecked, toggleVisibility.
 * DEAD (no caller outside the class): getCalendars, getFirstCalendarDate, addControlPoint, getControlPointsToPlot
 * (only through getUserStatsPanel, so live), getUserCalendarToString, updateUserToCalendar,
 * getAddUserToCalendarForm.
 *
 * The plugin tables are created before DAMA opens its transaction (see PluginCharacterizationTestCase).
 * Fixture tag: lc_.
 */
final class LearningCalendarPluginCharacterizationTest extends PluginCharacterizationTestCase
{
    private LearningCalendarPlugin $plugin;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::ensurePluginTables(
            ['learning_calendar', 'learning_calendar_events', 'learning_calendar_user', 'learning_calendar_control_point'],
            'LearningCalendar',
            'LearningCalendar/LearningCalendarPlugin.php',
            'LearningCalendarPlugin'
        );
    }

    protected function setUp(): void
    {
        parent::setUp();
        require_once api_get_path(SYS_PLUGIN_PATH).'LearningCalendar/LearningCalendarPlugin.php';
        $this->plugin = LearningCalendarPlugin::create();
    }

    // ---------------------------------------------------------------- fixtures

    private function calendar(string $title, int $authorId = 1, int $hours = 10, int $minutes = 30): int
    {
        $this->conn->executeStatement(
            'INSERT INTO learning_calendar (title, description, total_hours, minutes_per_day, disabled, author_id)
             VALUES (?, ?, ?, ?, 0, ?)',
            ['lc_'.$title, 'desc '.$title, $hours, $minutes, $authorId]
        );

        return (int) $this->conn->lastInsertId();
    }

    private function event(int $calendarId, string $start, ?string $end, int $type = 1, string $title = ''): int
    {
        $this->conn->executeStatement(
            'INSERT INTO learning_calendar_events (title, calendar_id, start_date, end_date, type) VALUES (?, ?, ?, ?, ?)',
            [$title, $calendarId, $start, $end ?? $start, $type]
        );

        return (int) $this->conn->lastInsertId();
    }

    private function assign(int $calendarId, int $userId): int
    {
        $this->conn->executeStatement(
            'INSERT INTO learning_calendar_user (user_id, calendar_id) VALUES (?, ?)',
            [$userId, $calendarId]
        );

        return (int) $this->conn->lastInsertId();
    }

    private function events(int $calendarId): array
    {
        return $this->conn->fetchAllAssociative(
            'SELECT start_date, end_date, type FROM learning_calendar_events WHERE calendar_id = ? ORDER BY start_date, id',
            [$calendarId]
        );
    }

    // ---------------------------------------------------------------- event types

    public function testEventTypeColorsAreFixedPerType(): void
    {
        $this->assertSame(
            [
                LearningCalendarPlugin::EVENT_TYPE_TAKEN => 'red',
                LearningCalendarPlugin::EVENT_TYPE_EXAM => 'yellow',
                LearningCalendarPlugin::EVENT_TYPE_FREE => 'green',
            ],
            $this->plugin->getEventTypeColorList()
        );
        $this->assertSame([1, 2, 3], array_keys($this->plugin->getEventTypeList()));
    }

    // ---------------------------------------------------------------- calendars

    public function testGetCalendarReturnsTheRowOrAnEmptyArray(): void
    {
        $id = $this->calendar('one', 1, 12, 45);

        $row = $this->plugin->getCalendar($id);
        $this->assertSame('lc_one', $row['title']);
        $this->assertSame(12, (int) $row['total_hours']);
        $this->assertSame(45, (int) $row['minutes_per_day']);
        $this->assertSame([], $this->plugin->getCalendar(0));
        $this->assertSame([], $this->plugin->getCalendar(987654321));
        $this->assertSame($id, (int) $this->plugin->getCalendar((string) $id)['id']);
    }

    public function testCalendarListOfAnAdminHoldsEveryCalendarSortedByTitleWithCounts(): void
    {
        $teacher = $this->rawUser('lc_t', ['ROLE_TEACHER'], 1);
        $b = $this->calendar('b_cal', $teacher);
        $a = $this->calendar('a_cal', 1);
        $this->event($a, '2026-03-10', '2026-03-10');
        $this->event($a, '2026-03-11', '2026-03-11');
        $this->assign($a, 1);

        $this->loginAs(1);
        $byTitle = array_column($this->plugin->getCalendarList(), null, 'title');

        $this->assertArrayHasKey('lc_a_cal', $byTitle);
        $this->assertArrayHasKey('lc_b_cal', $byTitle);
        $this->assertSame(2, $byTitle['lc_a_cal']['event_count']);
        $this->assertSame(1, $byTitle['lc_a_cal']['user_count']);
        $this->assertSame(0, $byTitle['lc_b_cal']['event_count']);
        $titles = array_column($this->plugin->getCalendarList(), 'title');
        $this->assertLessThan(array_search('lc_b_cal', $titles, true), array_search('lc_a_cal', $titles, true));
        $this->assertSame($a, (int) $byTitle['lc_a_cal']['id']);
        $this->assertSame($b, (int) $byTitle['lc_b_cal']['id']);
    }

    public function testCalendarListAndCountOfANonAdminAreLimitedToTheirOwnCalendars(): void
    {
        $teacher = $this->rawUser('lc_t', ['ROLE_TEACHER'], 1);
        $other = $this->rawUser('lc_o', ['ROLE_TEACHER'], 1);
        $this->calendar('z_mine', $teacher);
        $this->calendar('a_mine', $teacher);
        $this->calendar('theirs', $other);

        $this->loginAs($teacher);

        $this->assertSame(['lc_a_mine', 'lc_z_mine'], array_column($this->plugin->getCalendarList(), 'title'));
        $this->assertSame(2, $this->plugin->getCalendarCount());
        $this->assertCount(2, $this->plugin->getCalendars(0, 100, 0));
    }

    public function testCalendarCountOfAnAdminIsTheWholeTable(): void
    {
        $before = (int) $this->conn->fetchOne('SELECT COUNT(*) FROM learning_calendar');
        $this->calendar('c1');
        $this->calendar('c2', $this->rawUser('lc_t', ['ROLE_TEACHER'], 1));

        $this->loginAs(1);

        $this->assertSame($before + 2, $this->plugin->getCalendarCount());
    }

    public function testCalendarsPagingAppliesLimitAndIgnoresTheRequestedSortColumn(): void
    {
        // PINNED QUIRK (dead method): $column is cast and then unused, the rows come in table order whatever
        // $direction says; only LIMIT from,count is honoured. Rows link to the plugin pages.
        $first = $this->calendar('p1');
        $second = $this->calendar('p2');
        $this->loginAs(1);
        $before = (int) $this->conn->fetchOne('SELECT COUNT(*) FROM learning_calendar') - 2;

        $asc = $this->plugin->getCalendars($before, 10, 0, 'ASC');
        $desc = $this->plugin->getCalendars($before, 10, 0, 'DESC');

        $this->assertSame(array_column($asc, 'id'), array_column($desc, 'id'));
        $this->assertSame([(string) $first, (string) $second], array_map('strval', array_column($asc, 'id')));
        $this->assertCount(1, $this->plugin->getCalendars($before, 1, 0));
        $this->assertStringContainsString('calendar.php?id='.$first, $asc[0]['title']);
        $this->assertStringContainsString('action=delete&amp;id='.$first, $asc[0]['actions']);
    }

    // ---------------------------------------------------------------- events

    public function testEventCountAndEventListOfACalendar(): void
    {
        $cal = $this->calendar('ev');
        $other = $this->calendar('ev_other');
        $this->event($cal, '2026-03-10', '2026-03-10', 1, 'Own title');
        $this->event($cal, '2026-03-12', '2026-03-13', 2);
        $this->event($other, '2026-03-12', '2026-03-12');

        $this->assertSame(2, $this->plugin->getCalendarEventCount($cal));
        $list = $this->plugin->getEvents($cal);

        $this->assertCount(2, $list);
        $this->assertSame('Own title', $list[0]['title']);
        $this->assertSame('red', $list[0]['color']);
        // An event without its own title takes the title of its type.
        $this->assertSame($this->plugin->getEventTypeList()[2]['title'], $list[1]['title']);
        $this->assertSame('2026-03-13', $list[1]['end_date']);
        $this->assertSame('yellow', $list[1]['color']);
        $this->assertSame(2, $list[1]['type']);
    }

    public function testEventsOfAnUnknownTypeFallBackToTheFreeColourAndNullEndDateToTheStartDate(): void
    {
        $cal = $this->calendar('ev_unknown');
        $this->conn->executeStatement(
            'INSERT INTO learning_calendar_events (title, calendar_id, start_date, end_date, type) VALUES (?, ?, ?, ?, ?)',
            ['', $cal, '2026-04-01', '0000-00-00', 9]
        );

        $list = $this->plugin->getEvents($cal);

        $this->assertSame('green', $list[0]['color']);
        $this->assertSame($this->plugin->getEventTypeList()[3]['title'], $list[0]['title']);
        $this->assertSame(9, $list[0]['type']);
    }

    public function testEventsByDateFilterOnStartEndAndType(): void
    {
        $cal = $this->calendar('by_date');
        $this->event($cal, '2026-03-10', '2026-03-10', 1);
        $this->event($cal, '2026-03-11', '2026-03-11', 2);
        $this->event($cal, '2026-03-12', '2026-03-12', 1);
        $info = $this->plugin->getCalendar($cal);
        $ts = static fn (string $d): int => (int) strtotime($d.' 00:00:00 UTC');

        $all = $this->plugin->getCalendarsEventsByDate($info, 0, 0);
        $this->assertSame($info['id'], $all['calendar']['id']);
        $this->assertCount(3, $all['events']);

        // start is inclusive, end is inclusive as well because the column is a DATE (midnight).
        $window = $this->plugin->getCalendarsEventsByDate($info, $ts('2026-03-11'), $ts('2026-03-12'));
        $this->assertSame(['2026-03-11', '2026-03-12'], array_column($window['events'], 'start_date'));
        $this->assertCount(1, $this->plugin->getCalendarsEventsByDate($info, $ts('2026-03-11'), $ts('2026-03-11'))['events']);

        $taken = $this->plugin->getCalendarsEventsByDate($info, 0, 0, 1);
        $this->assertSame(['2026-03-10', '2026-03-12'], array_column($taken['events'], 'start_date'));

        $this->assertEquals(2, $this->plugin->getCalendarsEventsByDate($info, 0, 0, 1, true));
        $this->assertEquals(3, $this->plugin->getCalendarsEventsByDate($info, 0, 0, 0, true));
        $this->assertEquals(0, $this->plugin->getCalendarsEventsByDate($info, $ts('2026-04-01'), 0, 0, true));
    }

    public function testEventsByDateOfAnEmptyCalendarInfo(): void
    {
        $this->assertSame([], $this->plugin->getCalendarsEventsByDate([], 0, 0));
        $this->assertSame(0, $this->plugin->getCalendarsEventsByDate([], 0, 0, 0, true));
    }

    public function testFirstCalendarDateIsTheEarliestStartDate(): void
    {
        $cal = $this->calendar('first');
        $this->event($cal, '2026-05-02', '2026-05-02');
        $this->event($cal, '2026-01-09', '2026-01-09');

        $this->assertSame('2026-01-09', $this->plugin->getFirstCalendarDate($this->plugin->getCalendar($cal)));
        $this->assertSame([], $this->plugin->getFirstCalendarDate([]));
    }

    public function testToggleDayCyclesTakenExamFreeThenRemovesTheDay(): void
    {
        $cal = $this->calendar('toggle');
        $other = $this->calendar('toggle_other');
        $this->event($other, '2026-06-01', '2026-06-01', 1);

        $this->plugin->toogleDayType($cal, '2026-06-01');
        $this->assertSame([['start_date' => '2026-06-01', 'end_date' => '2026-06-01', 'type' => 1]], $this->events($cal));

        $this->plugin->toogleDayType($cal, '2026-06-01');
        $this->assertSame(2, (int) $this->events($cal)[0]['type']);

        $this->plugin->toogleDayType($cal, '2026-06-01');
        $this->assertSame(3, (int) $this->events($cal)[0]['type']);

        $this->plugin->toogleDayType($cal, '2026-06-01');
        $this->assertSame([], $this->events($cal));
        // The same day of another calendar is never touched.
        $this->assertSame(1, (int) $this->events($other)[0]['type']);
    }

    // ---------------------------------------------------------------- users of a calendar

    public function testUsersPerCalendarSkipUnknownUsersAndKeepAssignmentOrder(): void
    {
        $cal = $this->calendar('users');
        $u1 = $this->rawUser('lc_u1');
        $u2 = $this->rawUser('lc_u2');
        $row2 = $this->assign($cal, $u2);
        $row1 = $this->assign($cal, $u1);
        $this->assign($cal, 987654);

        $list = $this->plugin->getUsersPerCalendar($cal);

        $this->assertSame([$u2, $u1], array_column($list, 'user_id'));
        $this->assertSame([$row2, $row1], array_column($list, 'calendar_user_id'));
        $this->assertSame('exam', $list[0]['exam']);
        // PINNED QUIRK: the count includes the assignment of a user that does not exist, the list does not.
        $this->assertSame(3, $this->plugin->getUsersPerCalendarCount($cal));
        $this->assertSame(0, $this->plugin->getUsersPerCalendarCount(0));
    }

    public function testAssignUserToCalendarReportsAddedAlreadyMovedAndInvalid(): void
    {
        $calA = $this->calendar('assign_a');
        $calB = $this->calendar('assign_b');
        $user = $this->rawUser('lc_a');

        $this->assertSame('invalid', $this->plugin->assignUserToCalendar(0, $user));
        $this->assertSame('invalid', $this->plugin->assignUserToCalendar($calA, 0));
        $this->assertSame('added', $this->plugin->assignUserToCalendar($calA, $user));
        $this->assertSame('already', $this->plugin->assignUserToCalendar($calA, $user));
        $this->assertSame('moved', $this->plugin->assignUserToCalendar($calB, $user));

        $this->assertSame(0, $this->plugin->getUsersPerCalendarCount($calA));
        $this->assertSame(1, $this->plugin->getUsersPerCalendarCount($calB));
        $this->assertSame($calB, (int) $this->plugin->getUserCalendar($user)['calendar_id']);
    }

    public function testAUserHasAtMostOneCalendarThroughAddUserToCalendar(): void
    {
        $calA = $this->calendar('add_a');
        $calB = $this->calendar('add_b');
        $user = $this->rawUser('lc_b');

        $this->assertTrue($this->plugin->addUserToCalendar($calA, $user));
        $this->assertFalse($this->plugin->addUserToCalendar($calB, $user));
        $this->assertSame($calA, (int) $this->plugin->getUserCalendar($user)['calendar_id']);

        $this->assertTrue($this->plugin->updateUserToCalendar($calB, $user));
        $this->assertSame($calB, (int) $this->plugin->getUserCalendar($user)['calendar_id']);
        // Updating a user that has no calendar does nothing but still answers true.
        $loner = $this->rawUser('lc_loner');
        $this->assertTrue($this->plugin->updateUserToCalendar($calB, $loner));
        $this->assertFalse($this->plugin->getUserCalendar($loner));
        $this->assertSame('lc_add_b', $this->plugin->getUserCalendarToString($user));
        $this->assertSame('', $this->plugin->getUserCalendarToString($loner));
    }

    public function testDeleteAllCalendarFromUserOnlyRemovesThatPair(): void
    {
        $cal = $this->calendar('del_pair');
        $other = $this->calendar('del_pair_other');
        $u1 = $this->rawUser('lc_d1');
        $u2 = $this->rawUser('lc_d2');
        $this->assign($cal, $u1);
        $this->assign($cal, $u2);
        $this->assign($other, $u1);

        $this->assertTrue($this->plugin->deleteAllCalendarFromUser($cal, $u1));

        $this->assertSame(1, $this->plugin->getUsersPerCalendarCount($cal));
        $this->assertSame($other, (int) $this->plugin->getUserCalendar($u1)['calendar_id']);
    }

    public function testFindUserForCalendarAssignmentByIdOrUsername(): void
    {
        $user = $this->rawUser('lc_find');
        $name = $this->conn->fetchOne('SELECT username FROM user WHERE id = ?', [$user]);

        $byId = $this->plugin->findUserForCalendarAssignment((string) $user);
        $byName = $this->plugin->findUserForCalendarAssignment('  '.$name.' ');

        $this->assertSame($user, (int) $byId['id']);
        $this->assertSame($name, $byId['username']);
        $this->assertSame($user, (int) $byName['id']);
        $this->assertSame([], $this->plugin->findUserForCalendarAssignment(''));
        $this->assertSame([], $this->plugin->findUserForCalendarAssignment('   '));
        $this->assertSame([], $this->plugin->findUserForCalendarAssignment('no_such_lc_user'));
        $this->assertSame([], $this->plugin->findUserForCalendarAssignment("x' OR '1'='1"));
        // PINNED QUIRK: an all-digit identifier is always an id, never a username.
        $this->assertSame([], $this->plugin->findUserForCalendarAssignment('987654321'));
    }

    // ---------------------------------------------------------------- user events

    public function testUserEventsComeFromTheCalendarOfTheUser(): void
    {
        $cal = $this->calendar('user_events');
        $user = $this->rawUser('lc_ue');
        $this->event($cal, '2026-03-10', '2026-03-10', 1);
        $this->event($cal, '2026-03-11', '2026-03-11', 2);
        $this->assign($cal, $user);
        $nobody = $this->rawUser('lc_nobody');

        $events = $this->plugin->getUserEvents($user, 0, 0);
        $this->assertCount(2, $events['events']);
        $this->assertSame('lc_user_events', $events['calendar']['title']);
        $this->assertEquals(1, $this->plugin->getUserEvents($user, 0, 0, 2, true));
        $this->assertSame([], $this->plugin->getUserEvents($nobody, 0, 0));
        $this->assertSame(0, $this->plugin->getUserEvents($nobody, 0, 0, 0, true));
    }

    public function testPersonalEventsForTheApiOverlapTheWindowAndAreSortedByStartDate(): void
    {
        $cal = $this->calendar('api');
        $user = $this->rawUser('lc_api');
        $this->assign($cal, $user);
        $late = $this->event($cal, '2026-03-20', '2026-03-22', 2);
        $early = $this->event($cal, '2026-03-05', '2026-03-06', 1);
        $before = $this->event($cal, '2026-02-01', '2026-02-02', 1);
        $spanning = $this->event($cal, '2026-02-25', '2026-03-03', 3);

        $list = $this->plugin->getPersonalEventsForApi($user, '2026-03-01', '2026-03-21');

        // PINNED: window = start_date < end AND end_date >= start; sorted by start date then id.
        $this->assertSame(
            ['learning_calendar_'.$spanning, 'learning_calendar_'.$early, 'learning_calendar_'.$late],
            array_column($list, 'id')
        );
        $this->assertNotContains('learning_calendar_'.$before, array_column($list, 'id'));
        $this->assertSame('2026-03-20T00:00:00+00:00', $list[2]['startDate']);
        $this->assertSame('2026-03-22T23:59:00+00:00', $list[2]['endDate']);
        $this->assertSame('yellow', $list[2]['color']);
        $this->assertSame('green', $list[0]['color']);
        $this->assertSame('lc_api', $list[1]['title']);
        $this->assertSame('learning_calendar', $list[1]['objectType']);
        $this->assertSame($cal, $list[1]['learningCalendar']['id']);
        $this->assertTrue($list[1]['allDay']);
    }

    public function testPersonalEventsForTheApiAreEmptyWithoutUserOrCalendar(): void
    {
        $cal = $this->calendar('api_empty');
        $this->event($cal, '2026-03-05', '2026-03-06');
        $nobody = $this->rawUser('lc_api_none');

        $this->assertSame([], $this->plugin->getPersonalEventsForApi(0, '2026-03-01', '2026-03-31'));
        $this->assertSame([], $this->plugin->getPersonalEventsForApi($nobody, '2026-03-01', '2026-03-31'));
        // A calendar assignment pointing at a deleted calendar is ignored too.
        $dangling = $this->rawUser('lc_api_dangling');
        $this->assign(987654, $dangling);
        $this->assertSame([], $this->plugin->getPersonalEventsForApi($dangling, '2026-03-01', '2026-03-31'));
    }

    public function testPersonalEventsForTheApiNormalizeDatesAndFallBackOnGarbage(): void
    {
        $cal = $this->calendar('api_dates');
        $user = $this->rawUser('lc_api_dates');
        $this->assign($cal, $user);
        $id = $this->event($cal, '2026-03-05', '2026-03-05');

        // A datetime is cut to its day; a window that does not parse falls back to today / today+1 month
        // (so a 2026 event is outside of it unless today is that day: only the explicit window is asserted).
        $inside = $this->plugin->getPersonalEventsForApi($user, '2026-03-05 18:30:00', '2026-03-06T10:00:00');
        $this->assertSame(['learning_calendar_'.$id], array_column($inside, 'id'));
        $this->assertSame([], $this->plugin->getPersonalEventsForApi($user, 'not a date', 'garbage'));
    }

    public function testPersonalEventsForTheAgendaAreReadOnlyAllDayEvents(): void
    {
        $cal = $this->calendar('agenda');
        $user = $this->rawUser('lc_agenda');
        $this->assign($cal, $user);
        $id = $this->event($cal, '2026-03-05', '2026-03-06', 2);
        $this->loginAs($user);
        $agenda = new class() {
            public function formatEventDate(string $date): string
            {
                return 'fmt:'.$date;
            }
        };

        $list = $this->plugin->getPersonalEvents($agenda, 0, 0);

        $this->assertCount(1, $list);
        $this->assertSame('personal_'.$id, $list[0]['id']);
        $this->assertSame('lc_agenda', $list[0]['title']);
        $this->assertSame('yellow', $list[0]['backgroundColor']);
        $this->assertFalse($list[0]['editable']);
        $this->assertSame('fmt:2026-03-05', $list[0]['start']);
        $this->assertSame('fmt:2026-03-06', $list[0]['end']);
        $this->assertSame('plugin', $list[0]['description']);
        $this->assertSame([], $this->plugin->getPersonalEvents($agenda, 4000000000, 4000000001));
    }

    // ---------------------------------------------------------------- copy / delete / access

    public function testCopyCalendarDuplicatesTheCalendarAndItsEventsForTheCurrentUser(): void
    {
        $teacher = $this->rawUser('lc_cp', ['ROLE_TEACHER'], 1);
        $cal = $this->calendar('to_copy', $teacher, 33, 20);
        $this->event($cal, '2026-03-10', '2026-03-10', 1);
        $this->event($cal, '2026-03-11', '2026-03-11', 2);
        $this->assign($cal, $this->rawUser('lc_cp_user'));
        $this->loginAs($teacher);
        $before = (int) $this->conn->fetchOne('SELECT MAX(id) FROM learning_calendar');

        $this->assertTrue($this->plugin->copyCalendar($cal));

        $copy = $this->conn->fetchAssociative('SELECT * FROM learning_calendar WHERE id > ? ORDER BY id LIMIT 1', [$before]);
        $this->assertSame('lc_to_copy', $copy['title']);
        $this->assertSame(33, (int) $copy['total_hours']);
        $this->assertSame($teacher, (int) $copy['author_id']);
        $this->assertSame($this->events($cal), $this->events((int) $copy['id']));
        // The users are not copied.
        $this->assertSame(0, $this->plugin->getUsersPerCalendarCount((int) $copy['id']));
    }

    public function testDeleteCalendarRemovesItsEventsAndUserAssignmentsOnly(): void
    {
        $cal = $this->calendar('to_delete');
        $keep = $this->calendar('to_keep');
        $this->event($cal, '2026-03-10', '2026-03-10');
        $this->event($keep, '2026-03-10', '2026-03-10');
        $this->assign($cal, $this->rawUser('lc_del_u'));
        $this->assign($keep, $this->rawUser('lc_keep_u'));
        $this->loginAs(1);

        $this->assertTrue($this->plugin->deleteCalendar($cal));

        $this->assertSame([], $this->plugin->getCalendar($cal));
        $this->assertSame(0, $this->plugin->getCalendarEventCount($cal));
        $this->assertSame(0, $this->plugin->getUsersPerCalendarCount($cal));
        $this->assertSame(1, $this->plugin->getCalendarEventCount($keep));
        $this->assertSame(1, $this->plugin->getUsersPerCalendarCount($keep));
    }

    public function testATeacherCannotCopyOrDeleteTheCalendarOfAnotherTeacher(): void
    {
        $owner = $this->rawUser('lc_owner', ['ROLE_TEACHER'], 1);
        $intruder = $this->rawUser('lc_intruder', ['ROLE_TEACHER'], 1);
        $cal = $this->calendar('protected', $owner);
        $this->loginAs($intruder);

        try {
            $this->plugin->deleteCalendar($cal);
            $this->fail('A teacher deleted a calendar of someone else');
        } catch (NotAllowedException) {
        }
        try {
            $this->plugin->copyCalendar($cal);
            $this->fail('A teacher copied a calendar of someone else');
        } catch (NotAllowedException) {
        }

        $this->assertNotSame([], $this->plugin->getCalendar($cal));
    }

    public function testProtectCalendarRejectsStudentsMissingCalendarsAndAcceptsTheOwnerAndAdmins(): void
    {
        $owner = $this->rawUser('lc_p_owner', ['ROLE_TEACHER'], 1);
        $student = $this->rawUser('lc_p_student');
        $cal = $this->calendar('prot', $owner);
        $info = $this->plugin->getCalendar($cal);

        $this->loginAs($student);
        $this->assertDenied(fn () => $this->plugin->protectCalendar($info));
        $this->loginAs($owner);
        $this->assertDenied(fn () => $this->plugin->protectCalendar([]));
        $this->plugin->protectCalendar($info);
        $this->loginAs(1);
        $this->plugin->protectCalendar($info);
        $this->assertDenied(fn () => $this->plugin->protectCalendar([]));
        $this->loginAs(0);
        $this->assertDenied(fn () => $this->plugin->protectCalendar($info));
    }

    private function assertDenied(callable $call): void
    {
        try {
            $call();
        } catch (NotAllowedException) {
            $this->addToAssertionCount(1);

            return;
        }
        $this->fail('Access should have been denied');
    }

    // ---------------------------------------------------------------- control points and statistics

    public function testControlPointsAreOrderedByDateAndOneIsKeptPerDay(): void
    {
        $user = $this->rawUser('lc_cpts');
        $other = $this->rawUser('lc_cpts_other');
        foreach ([['2026-03-05', 4], ['2026-01-02', -3]] as [$date, $value]) {
            $this->conn->executeStatement(
                'INSERT INTO learning_calendar_control_point (user_id, control_date, control_value, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?)',
                [$user, $date, $value, '2026-01-01 00:00:00', '2026-01-01 00:00:00']
            );
        }
        $this->conn->executeStatement(
            "INSERT INTO learning_calendar_control_point (user_id, control_date, control_value, created_at, updated_at)
             VALUES (?, '2026-02-02', 9, '2026-01-01 00:00:00', '2026-01-01 00:00:00')",
            [$other]
        );

        $this->assertSame(
            [['control_date' => '2026-01-02', 'control_value' => -3], ['control_date' => '2026-03-05', 'control_value' => 4]],
            array_map(
                static fn (array $r): array => ['control_date' => $r['control_date'], 'control_value' => (int) $r['control_value']],
                $this->plugin->getControlPoints($user)
            )
        );
        $this->assertSame([['2026-01-02', -3], ['2026-03-05', 4]], array_map(
            static fn (array $p): array => [$p[0], (int) $p[1]],
            $this->plugin->getControlPointsToPlot($user)
        ));

        $fresh = $this->rawUser('lc_cpts_fresh');
        $this->plugin->addControlPoint($fresh, 5);
        $this->plugin->addControlPoint($fresh, -7);
        $rows = $this->plugin->getControlPoints($fresh);
        $this->assertCount(1, $rows);
        $this->assertSame(-7, (int) $rows[0]['control_value']);
        $this->assertSame(substr(api_get_local_time(), 0, 10), $rows[0]['control_date']);
    }

    public function testUserStatsCountTheTakenDaysOfTheYearUntilNow(): void
    {
        $cal = $this->calendar('stats');
        $user = $this->rawUser('lc_stats');
        $this->assign($cal, $user);
        $year = date('Y');
        $this->event($cal, $year.'-01-01', $year.'-01-01', 1);
        $this->event($cal, $year.'-01-02', $year.'-01-02', 1);
        $this->event($cal, $year.'-01-03', $year.'-01-03', 2);
        // Next year: never counted.
        $this->event($cal, ($year + 1).'-01-03', ($year + 1).'-01-03', 1);

        $stats = $this->plugin->getUserStats($user, []);

        $this->assertEquals(2, $stats['user_event_count']);
        $this->assertSame(0, $stats['completed']);
        $this->assertSame(0, $stats['diff']);
    }

    public function testUserStatsPanelShowsTheControlPointChartOnlyWhenThereAreControlPoints(): void
    {
        $cal = $this->calendar('panel');
        $user = $this->rawUser('lc_panel');
        $this->assign($cal, $user);

        $without = $this->plugin->getUserStatsPanel($user, []);
        $this->assertStringNotContainsString('control_point_chart', $without);

        $this->conn->executeStatement(
            "INSERT INTO learning_calendar_control_point (user_id, control_date, control_value, created_at, updated_at)
             VALUES (?, '2026-01-02', 3, '2026-01-01 00:00:00', '2026-01-01 00:00:00')",
            [$user]
        );
        $with = $this->plugin->getUserStatsPanel($user, []);
        $this->assertStringContainsString('control_point_chart', $with);
        $this->assertStringContainsString('[["2026-01-02",3]]', str_replace('"3"', '3', $with));
    }

    public function testItemCountCheckedAndGradebookListAreZeroWithoutCourses(): void
    {
        $user = $this->rawUser('lc_nocourse');

        $this->assertSame(0, $this->plugin->getItemCountChecked($user, []));
        // PINNED QUIRK: documented as an array, answers the integer 0 when there is nothing to look at.
        $this->assertSame(0, $this->plugin->getGradebookEvaluationList($user, []));
        $this->assertSame(0, $this->plugin->getGradebookEvaluationList($user, [0 => []]));
        $this->assertSame('', $this->plugin->getGradebookEvaluationListToString($user, []));
    }

    // ---------------------------------------------------------------- learning path completion

    private function calendarFieldId(): int
    {
        return (int) $this->conn->fetchOne("SELECT id FROM extra_field WHERE variable = 'calendar' ORDER BY id LIMIT 1");
    }

    /**
     * A learning path with $items items, the first $marked of them carrying the "calendar day" mark.
     *
     * @return int[] [lp id, item ids...]
     */
    private function learningPath(int $items, int $marked): array
    {
        $this->conn->executeStatement(
            "INSERT INTO c_lp (lp_type, title, path, force_commit, content_maker, content_license, js_lib, debug, theme,
                author, prerequisite, hide_toc_frame, seriousgame_mode, autolaunch, max_attempts, subscribe_users,
                created_on, modified_on)
             VALUES (1, 'lc_lp', '', 0, '', '', '', 0, '', '', 0, 0, 0, 0, 0, 0, '2026-01-01 00:00:00', '2026-01-01 00:00:00')"
        );
        $lpId = (int) $this->conn->lastInsertId();
        $ids = [$lpId];
        for ($i = 0; $i < $items; ++$i) {
            $this->conn->executeStatement(
                "INSERT INTO c_lp_item (lp_id, title, item_type, ref, path, min_score, display_order, launch_data, lvl)
                 VALUES (?, ?, 'document', '', '', 0, ?, '', 0)",
                [$lpId, 'lc_item_'.$i, $i]
            );
            $itemId = (int) $this->conn->lastInsertId();
            $ids[] = $itemId;
            if ($i < $marked) {
                $this->conn->executeStatement(
                    "INSERT INTO extra_field_values (field_id, item_id, field_value, created_at, updated_at)
                     VALUES (?, ?, '1', '2026-01-01 00:00:00', '2026-01-01 00:00:00')",
                    [$this->calendarFieldId(), $itemId]
                );
            }
        }

        return $ids;
    }

    /**
     * Records one attempt of the user at a learning path: $statusByItem = [item id => status].
     */
    private function view(int $userId, int $lpId, int $courseId, int $sessionId, array $statusByItem, int $viewCount = 1): void
    {
        $this->conn->executeStatement(
            'INSERT INTO c_lp_view (user_id, lp_id, c_id, session_id, view_count, last_item) VALUES (?, ?, ?, ?, ?, 0)',
            [$userId, $lpId, $courseId, $sessionId ?: null, $viewCount]
        );
        $viewId = (int) $this->conn->lastInsertId();
        foreach ($statusByItem as $itemId => $status) {
            $this->conn->executeStatement(
                'INSERT INTO c_lp_item_view (lp_item_id, lp_view_id, view_count, start_time, total_time, score, status)
                 VALUES (?, ?, ?, 0, 0, 0, ?)',
                [$itemId, $viewId, $viewCount, $status]
            );
        }
    }

    public function testItemCountCheckedCountsTheCompletedCalendarItemsOfTheUser(): void
    {
        $user = $this->rawUser('lc_count');
        $other = $this->rawUser('lc_count_other');
        [$lp, $a, $b, $c] = $this->learningPath(3, 2);
        $this->view($user, $lp, 1, 0, [$a => 'completed', $b => 'incomplete', $c => 'completed']);
        $this->view($other, $lp, 1, 0, [$a => 'completed', $b => 'completed']);

        // Only the marked items (a, b) count, and only the completed ones: a.
        $this->assertSame(1, (int) $this->plugin->getItemCountChecked($user, [0 => [1]]));
        $this->assertSame(2, (int) $this->plugin->getItemCountChecked($other, [0 => [1]]));
        // Another course, or a session the learning path was not seen in, counts nothing.
        $this->assertSame(0, (int) $this->plugin->getItemCountChecked($user, [0 => [2]]));
        $this->assertSame(0, (int) $this->plugin->getItemCountChecked($user, [9999 => [2]]));
    }

    public function testItemCountCheckedCountsAnItemOnceWhateverTheNumberOfAttempts(): void
    {
        $user = $this->rawUser('lc_attempts');
        [$lp, $a, $b] = $this->learningPath(2, 2);
        $this->view($user, $lp, 1, 0, [$a => 'completed'], 1);
        $this->view($user, $lp, 1, 0, [$a => 'completed', $b => 'completed'], 2);

        $this->assertSame(2, (int) $this->plugin->getItemCountChecked($user, [0 => [1]]));
    }

    public function testItemCountCheckedUnderASessionAlsoCountsTheBaseCourseViews(): void
    {
        $user = $this->rawUser('lc_sess');
        $this->conn->executeStatement(
            "INSERT INTO session (title, nbr_courses, nbr_users, nbr_classes, visibility, status) VALUES ('lc_session', 0, 0, 0, 1, 0)"
        );
        $sessionId = (int) $this->conn->lastInsertId();
        [$lp, $a, $b] = $this->learningPath(2, 2);
        $this->view($user, $lp, 1, 0, [$a => 'completed']);
        $this->view($user, $lp, 1, $sessionId, [$b => 'completed']);

        $this->assertSame(1, (int) $this->plugin->getItemCountChecked($user, [0 => [1]]), 'no session: base views only');
        $this->assertSame(2, (int) $this->plugin->getItemCountChecked($user, [$sessionId => ['course_list' => [1 => 'AIACT']]]));
    }

    private function evaluation(int $courseId, string $title, string $type = 'evaluation'): int
    {
        $this->conn->executeStatement(
            "INSERT INTO gradebook_evaluation (c_id, title, created_at, weight, max, visible, type, locked)
             VALUES (?, ?, '2026-01-01 00:00:00', 1, 10, 1, ?, 0)",
            [$courseId, 'lc_'.$title, $type]
        );

        return (int) $this->conn->lastInsertId();
    }

    private function result(int $evaluationId, int $userId, float $score): void
    {
        $this->conn->executeStatement(
            "INSERT INTO gradebook_result (evaluation_id, user_id, score, created_at) VALUES (?, ?, ?, '2026-01-01 00:00:00')",
            [$evaluationId, $userId, $score]
        );
    }

    public function testGradebookEvaluationListHoldsTheEvaluationsPassedWithAtLeastTwoPoints(): void
    {
        $user = $this->rawUser('lc_grade');
        $passed = $this->evaluation(1, 'passed');
        $low = $this->evaluation(1, 'low');
        $otherCourse = $this->evaluation(2, 'other_course');
        $category = $this->evaluation(1, 'a_category', 'category');
        $this->result($passed, $user, 2.0);
        $this->result($low, $user, 1.99);
        $this->result($otherCourse, $user, 9);
        $this->result($category, $user, 9);
        $stranger = $this->rawUser('lc_grade_other');
        $this->result($this->evaluation(1, 'stranger'), $stranger, 9);

        $list = $this->plugin->getGradebookEvaluationList($user, [0 => [1]]);

        $this->assertSame([$passed], array_keys($list));
        $this->assertSame('lc_passed', $list[$passed]['title']);
        $this->assertSame('lc_passed', $this->plugin->getGradebookEvaluationListToString($user, [0 => [1]]));
    }

    public function testGradebookEvaluationListLooksInEveryCourseOfEverySession(): void
    {
        $user = $this->rawUser('lc_grade_all');
        $one = $this->evaluation(1, 'course_one');
        $two = $this->evaluation(2, 'course_two');
        $this->result($one, $user, 5);
        $this->result($two, $user, 5);

        $list = $this->plugin->getGradebookEvaluationList(
            $user,
            [0 => [1], 5 => ['course_list' => [2 => 'USINGCHAMILO']]]
        );

        $this->assertEqualsCanonicalizing([$one, $two], array_keys($list));
    }

    public function testFirstToggleOfAnLpItemMarksItAndEveryOtherToggleFlipsTheMark(): void
    {
        $itemId = 987001;

        $this->plugin->toggleVisibility($itemId);
        $this->assertSame(1, (int) $this->plugin->getItemVisibility($itemId)['field_value']);
        $this->plugin->toggleVisibility($itemId);
        $this->assertSame(0, (int) $this->plugin->getItemVisibility($itemId)['field_value']);
        $this->plugin->toggleVisibility($itemId);
        $this->assertSame(1, (int) $this->plugin->getItemVisibility($itemId)['field_value']);
        $this->assertSame([], $this->plugin->getItemVisibility(987002));
    }
}
