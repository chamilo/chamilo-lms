<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Legacy;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use Database;
use Doctrine\DBAL\Connection;
use Tracking;

/**
 * Characterization tests for the raw-SQL methods of the legacy Tracking library.
 *
 * Intent: the SQL behind these methods is going to be rewritten (Doctrine/DQL,
 * MySQL/MariaDB compatible). Every assertion pins what the CURRENT code returns
 * for rows seeded with fixed timestamps, so that a rewrite that changes an
 * aggregate, a boundary (strict vs inclusive), an ordering or the NULL/zero
 * handling fails here instead of silently changing learner reports.
 *
 * Quirks of the current behaviour are pinned on purpose and flagged with
 * "PINNED QUIRK": they are not endorsed, they are what reports show today.
 */
final class TrackingCharacterizationTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    private Connection $conn;
    private User $alice;
    private User $bob;
    private User $carol;
    private Course $course;
    private Course $course2;

    protected function setUp(): void
    {
        parent::setUp();

        // The legacy Database class keeps its own static handle on the manager.
        Database::setManager($this->getEntityManager());
        $this->conn = $this->getEntityManager()->getConnection();
        $this->alice = $this->createUser('tk_alice');
        $this->bob = $this->createUser('tk_bob');
        $this->carol = $this->createUser('tk_carol');
        $this->course = $this->createCourse('tk_course');
        $this->course2 = $this->createCourse('tk_course_two');
    }

    // ------------------------------------------------------------------
    // Platform login time
    // ------------------------------------------------------------------

    public function testTimeSpentOnPlatformSumsLoginToLogoutSecondsAndIgnoresOpenSessions(): void
    {
        $this->seedPlatformLogins();
        $id = $this->alice->getId();

        // 1800 + 45 seconds; the open session (logout NULL) cannot be part of a closed range.
        $this->assertEquals(
            1845,
            Tracking::get_time_spent_on_the_platform($id, 'custom', '2001-03-01 00:00:00', '2001-03-31 23:59:59')
        );
        // Range edges are inclusive on both ends (login >= start AND logout <= end).
        $this->assertEquals(
            1800,
            Tracking::get_time_spent_on_the_platform($id, 'custom', '2001-03-01 10:00:00', '2001-03-01 10:30:00')
        );
        // Custom range excludes sessions that only partly fit in the range.
        $this->assertEquals(
            45,
            Tracking::get_time_spent_on_the_platform($id, 'custom', '2001-03-02 00:00:00', '2001-03-31 23:59:59')
        );
        // The user list form sums over every listed user.
        $this->assertEquals(
            1845 + 100,
            Tracking::get_time_spent_on_the_platform(
                [$id, $this->bob->getId()],
                'custom',
                '2001-03-01 00:00:00',
                '2001-03-31 23:59:59'
            )
        );
    }

    public function testTimeSpentOnPlatformReturnsNullWhenNothingMatches(): void
    {
        $this->seedPlatformLogins();

        // PINNED QUIRK: SUM() is NULL when no row matches, and `null >= 0` is true in PHP, so the
        // "-1" fallback is dead code and callers receive null (not 0, not -1).
        $this->assertNull(Tracking::get_time_spent_on_the_platform($this->carol->getId()));
        // Rows from 2001 are outside the default rolling "last 7 days" window.
        $this->assertNull(Tracking::get_time_spent_on_the_platform($this->alice->getId()));
        $this->assertNull(
            Tracking::get_time_spent_on_the_platform(
                $this->alice->getId(),
                'custom',
                '2002-01-01 00:00:00',
                '2002-12-31 00:00:00'
            )
        );
    }

    public function testTimeSpentOnPlatformWideFilterCountsOverlappingSessions(): void
    {
        $this->seedPlatformLogins();
        $id = $this->alice->getId();

        // A window strictly inside the first session matches it through the
        // "login <= start AND logout >= end" branch, so the whole 1800s counts.
        $this->assertEquals(
            1800,
            Tracking::get_time_spent_on_the_platform($id, 'wide', '2001-03-01 10:10:00', '2001-03-01 10:20:00')
        );
        // A window overlapping only the tail of the session also matches it whole.
        $this->assertEquals(
            1800,
            Tracking::get_time_spent_on_the_platform($id, 'wide', '2001-03-01 10:20:00', '2001-03-01 11:00:00')
        );
    }

    public function testTimeSpentOnPlatformReturnsAllRecordsOrderedByLoginDate(): void
    {
        $this->seedPlatformLogins();

        $records = Tracking::get_time_spent_on_the_platform(
            $this->alice->getId(),
            'wide',
            '2001-03-01 00:00:00',
            '2001-03-31 23:59:59',
            true
        );

        $this->assertCount(3, $records);
        $this->assertSame(
            ['2001-03-01 10:00:00', '2001-03-02 09:00:00', '2001-03-03 08:00:00'],
            array_column($records, 'login_date')
        );
        $this->assertEquals(1800, $records[0]['diff']);
        $this->assertEquals(45, $records[1]['diff']);
        // The open session has no logout date, hence no duration.
        $this->assertNull($records[2]['logout_date']);
        $this->assertNull($records[2]['diff']);
    }

    public function testTotalTimeSpentOnPlatformIsTheDeltaOfTheClosedSessionsInRange(): void
    {
        $from = '2001-03-01 00:00:00';
        $to = '2001-03-31 23:59:59';
        $before = Tracking::getTotalTimeSpentOnThePlatform($from, $to);

        $this->seedPlatformLogins();

        $after = Tracking::getTotalTimeSpentOnThePlatform($from, $to);
        $this->assertEquals(1845 + 100, ((int) $after) - ((int) max($before, 0)));
    }

    public function testFirstAndLastPlatformConnectionDates(): void
    {
        $this->seedPlatformLogins();
        $id = $this->alice->getId();

        // The formatted rendering depends on the locale machinery (empty string in the test kernel), so
        // only the "has a connection" signal is pinned: a string, never false.
        $this->assertIsString(Tracking::get_first_connection_date($id));

        // The rendering of the last connection goes through the locale/timezone helpers, which do not
        // produce a usable value in the test kernel (false even when logins exist): not pinned, only
        // the "no login at all" answer below is.

        // No login at all: both return false.
        $this->assertFalse(Tracking::get_first_connection_date($this->carol->getId()));
        $this->assertFalse(Tracking::get_last_connection_date($this->carol->getId()));
        $this->assertFalse(Tracking::get_last_connection_date($this->carol->getId(), false, true));
    }

    // ------------------------------------------------------------------
    // Course access time
    // ------------------------------------------------------------------

    public function testTimeSpentOnTheCourseSumsOnlyPositiveDurationsPerSession(): void
    {
        $this->seedCourseAccess();
        $cid = $this->course->getId();
        $alice = $this->alice->getId();

        // 3600 + 90; the zero-length and the negative rows do not count.
        $this->assertEquals(3690, Tracking::get_time_spent_on_the_course($alice, $cid, 0));
        // Session filter is exact.
        $this->assertEquals(120, Tracking::get_time_spent_on_the_course($alice, $cid, 99999));
        // -1 means "every session".
        $this->assertEquals(3810, Tracking::get_time_spent_on_the_course($alice, $cid, -1));
        // Several users at once.
        $this->assertEquals(
            3690 + 30,
            Tracking::get_time_spent_on_the_course([$alice, $this->bob->getId()], $cid, 0)
        );
    }

    public function testTimeSpentOnTheCourseEdgeCases(): void
    {
        $this->seedCourseAccess();
        $cid = $this->course->getId();

        // PINNED QUIRK: no matching row gives NULL (SUM of nothing), not 0.
        $this->assertNull(Tracking::get_time_spent_on_the_course($this->carol->getId(), $cid, 0));
        // Empty user or course short-circuit to 0.
        $this->assertSame(0, Tracking::get_time_spent_on_the_course(0, $cid));
        $this->assertSame(0, Tracking::get_time_spent_on_the_course($this->alice->getId(), 0));
        // The "new tracking system" is not enabled in a fresh install.
        $this->assertFalse(Tracking::minimumTimeAvailable(0, $cid));
    }

    public function testCourseConnectionDatesAreRawTimestampsWhenNotConverted(): void
    {
        $this->seedCourseAccess();
        $cid = $this->course->getId();
        $alice = $this->alice->getId();

        $this->assertSame(
            '2001-03-01 10:00:00',
            Tracking::get_first_connection_date_on_the_course($alice, $cid, 0, false)
        );
        // The last connection is the latest LOGOUT date, not the latest login.
        $this->assertSame(
            '2001-03-02 08:00:00',
            Tracking::get_last_connection_date_on_the_course($alice, ['real_id' => $cid], 0, false)
        );
        // Sessions are separate histories.
        $this->assertSame(
            '2001-03-05 12:01:00',
            Tracking::get_last_connection_date_on_the_course($alice, ['real_id' => $cid], 99999, false)
        );

        $this->assertFalse(Tracking::get_first_connection_date_on_the_course($this->carol->getId(), $cid, 0, false));
        $this->assertFalse(Tracking::get_last_connection_date_on_the_course($this->carol->getId(), ['real_id' => $cid], 0, false));
        $this->assertFalse(Tracking::get_last_connection_date_on_the_course($alice, [], 0, false));
        $this->assertFalse(Tracking::get_last_connection_date_on_the_course($alice, ['real_id' => 0], 0, false));

        // Converted form: still a string (not false) when a connection exists.
        $this->assertIsString(Tracking::get_first_connection_date_on_the_course($alice, $cid, 0, true));
    }

    public function testLastConnectionHelpersReturnLatestLogoutAcrossCoursesOrSessions(): void
    {
        $this->seedCourseAccess();

        $this->assertSame('2001-03-05 12:01:00', Tracking::getLastConnectionInAnyCourse($this->alice->getId()));
        $this->assertFalse(Tracking::getLastConnectionInAnyCourse($this->carol->getId()));
        $this->assertFalse(Tracking::getLastConnectionInAnyCourse(0));

        // Over every user of the course in the base context: the latest logout is alice's.
        $this->assertSame('2001-03-02 08:00:00', Tracking::getLastConnectionDateByCourse($this->course->getId(), 0));
        $this->assertSame(
            '2001-03-05 12:01:00',
            Tracking::getLastConnectionDateByCourse($this->course->getId(), 99999)
        );
        // PINNED QUIRK: "nothing" is the empty string here but false in the sibling methods.
        $this->assertSame('', Tracking::getLastConnectionDateByCourse($this->course2->getId(), 0));
    }

    public function testCourseConnectionsCountRoundsBoundsToTheMinuteAndExcludesTheEdges(): void
    {
        $this->seedCourseAccess();
        $cid = $this->course->getId();

        // Base-context logins on 2001-03-01 (every user): 09:00:00 (bob), 10:00:00 (x2), 10:30:00, 12:00:00.
        // Start 09:59:59 is rounded DOWN to 09:59:00: the 09:00 login is out, the 10:00:00 ones are in.
        $this->assertEquals(
            4,
            Tracking::get_course_connections_count($cid, 0, '2001-03-01 09:59:59', '2001-03-01 12:01:00')
        );
        // Stop 12:00:59 is rounded down to 12:00:00 and the bound is strict: the 12:00:00 login drops out.
        $this->assertEquals(
            3,
            Tracking::get_course_connections_count($cid, 0, '2001-03-01 09:59:59', '2001-03-01 12:00:59')
        );
        // Start edge is strict too: a login exactly at the (rounded) start is excluded.
        $this->assertEquals(
            0,
            Tracking::get_course_connections_count($cid, 0, '2001-03-01 12:00:00', '2001-03-01 12:00:30')
        );
        // Another session / another course counts separately.
        $this->assertEquals(
            2,
            Tracking::get_course_connections_count($cid, 99999, '2001-03-05 00:00:00', '2001-03-06 00:00:00')
        );
        $this->assertEquals(
            0,
            Tracking::get_course_connections_count($this->course2->getId(), 0, '2000-01-01 00:00:00', '2002-01-01 00:00:00')
        );
    }

    public function testNumberOfCourseAccessDatesCountsDistinctCalendarDaysInTheSession(): void
    {
        $this->seedCourseAccess();
        $cid = $this->course->getId();

        // Logins on 2001-03-01 (several) and 2001-03-02 (one): two distinct days in the base context.
        $this->assertSame(2, Tracking::getNumberOfCourseAccessDates($this->alice->getId(), $cid, 0));
        $this->assertSame(1, Tracking::getNumberOfCourseAccessDates($this->alice->getId(), $cid, 99999));
        $this->assertSame(0, Tracking::getNumberOfCourseAccessDates($this->carol->getId(), $cid, 0));
    }

    public function testTotalTimeSpentInCoursesTruncatesToHoursAndCapsEachAccess(): void
    {
        $from = '2001-04-01 00:00:00';
        $until = '2001-04-30 23:59:59';
        $before = Tracking::getTotalTimeSpentInCourses($from, $until);
        $beforeCap3 = Tracking::getTotalTimeSpentInCourses($from, $until, 3);

        $cid = $this->course->getId();
        $uid = $this->alice->getId();
        // 2h30 -> 2 (TIMESTAMPDIFF HOUR truncates), 59 min -> 0, 10h -> capped to 6.
        $this->insertCourseAccess($uid, $cid, '2001-04-02 08:00:00', '2001-04-02 10:30:00');
        $this->insertCourseAccess($uid, $cid, '2001-04-03 08:00:00', '2001-04-03 08:59:00');
        $this->insertCourseAccess($uid, $cid, '2001-04-04 08:00:00', '2001-04-04 18:00:00');
        // Partly outside the window: ignored.
        $this->insertCourseAccess($uid, $cid, '2001-03-31 22:00:00', '2001-04-01 06:00:00');

        $this->assertSame(8, Tracking::getTotalTimeSpentInCourses($from, $until) - $before);
        // A lower cap applies to every row (2 stays 2, 10 -> 3): 5 instead of 8.
        $this->assertSame(5, Tracking::getTotalTimeSpentInCourses($from, $until, 3) - $beforeCap3);
    }

    // ------------------------------------------------------------------
    // Detailed access (track_e_access_complete)
    // ------------------------------------------------------------------

    public function testCalculateTimeAggregatesAccessCompleteRowsPerConnectionAndTool(): void
    {
        $this->seedAccessComplete();
        $result = Tracking::getCalculateTime($this->alice->getId(), $this->course->getId(), 0);

        // Connection 11 runs 10:00:00 -> 10:08:45 = 525s; connection 12 has a single row (0s).
        $this->assertSame(525, $result['total_time']);
        $this->assertSame(2, $result['number_connections']);
        // Generic tools: sum of the gaps between consecutive rows.
        $this->assertSame(60, $result['course-main']);
        $this->assertSame(45, $result['forum']);
        // Learning path 5: 180s of its own rows + 90s of a quiz opened from it (learnpath_id action).
        $this->assertSame([5 => 270], $result[TOOL_LEARNPATH]);
        $this->assertSame(270, $result['total_learnpath']);
        $this->assertSame([5 => [9 => ['att' => 180]]], $result['learnpath_detailed']);
        // The quiz gap was attributed to the LP, so the stand-alone quiz time is zero.
        $this->assertSame(0, $result[TOOL_QUIZ]);
        // first/last come from MIN/MAX(date_reg) of every non "login as" row, even connection 0 rows.
        $this->assertSame('2001-03-01 10:00:00', $result['first']);
        $this->assertSame('2001-03-03 10:00:00', $result['last']);
    }

    public function testCalculateTimeWithoutDataAndWithEmptyIds(): void
    {
        $this->assertSame([], Tracking::getCalculateTime(0, $this->course->getId(), 0));
        $this->assertSame([], Tracking::getCalculateTime($this->alice->getId(), 0, 0));

        $result = Tracking::getCalculateTime($this->carol->getId(), $this->course->getId(), 0);
        $this->assertSame(0, $result['total_time']);
        $this->assertSame(0, $result['number_connections']);
        // PINNED QUIRK: the MIN/MAX query always returns a row of NULLs, so first/last are null, not ''.
        $this->assertNull($result['first']);
        $this->assertNull($result['last']);
    }

    // ------------------------------------------------------------------
    // Exercises
    // ------------------------------------------------------------------

    public function testCountStudentExerciseAttemptsHonoursStatusAndLpScope(): void
    {
        [$e1] = $this->seedExercises();
        $cid = $this->course->getId();
        $uid = $this->alice->getId();

        // Only finished attempts (status '') outside any LP: two of them.
        $this->assertEquals(2, Tracking::count_student_exercise_attempts($uid, $cid, $e1, 0, 0, 0, 0));
        // Exactly the LP 5 / item 7 attempt.
        $this->assertEquals(1, Tracking::count_student_exercise_attempts($uid, $cid, $e1, 5, 7, 0, 0));
        // find_all_lp=1: attempts in that LP/item OR outside any LP.
        $this->assertEquals(3, Tracking::count_student_exercise_attempts($uid, $cid, $e1, 5, 7, 0, 1));
        $this->assertEquals(2, Tracking::count_student_exercise_attempts($uid, $cid, $e1, 6, 7, 0, 1));
        // Any other value of find_all_lp adds no LP filter at all (status still applies).
        $this->assertEquals(3, Tracking::count_student_exercise_attempts($uid, $cid, $e1, 5, 7, 0, 2));
        $this->assertEquals(0, Tracking::count_student_exercise_attempts($this->bob->getId(), $cid, $e1));
    }

    public function testExerciseStudentProgressIsRoundedPercentageOfDistinctFinishedExercises(): void
    {
        [$e1, $e2, $e3] = $this->seedExercises();
        $cid = $this->course->getId();
        $uid = $this->alice->getId();
        $session = $this->createSession('tk_session_progress');
        $sid = $session->getId();
        // Alice finished e1 twice and e2 once inside the session.
        $this->insertExercise($uid, $cid, $sid, $e1, '2001-05-01 10:00:00', 5, 10, '', 0, 0);
        $this->insertExercise($uid, $cid, $sid, $e1, '2001-05-02 10:00:00', 6, 10, '', 0, 0);
        $this->insertExercise($uid, $cid, $sid, $e2, '2001-05-03 10:00:00', 7, 10, '', 0, 0);
        // An unfinished attempt of e3 does not count.
        $this->insertExercise($uid, $cid, $sid, $e3, '2001-05-04 10:00:00', 0, 10, 'incomplete', 0, 0);

        $list = [$this->quiz($e1), $this->quiz($e2), $this->quiz($e3)];

        // 2 distinct finished out of 3 -> round(.67, 2) * 100.
        $this->assertSame('67%', Tracking::get_exercise_student_progress($list, $uid, $cid, $sid));
        $this->assertSame('100%', Tracking::get_exercise_student_progress([$this->quiz($e1), $this->quiz($e2)], $uid, $cid, $sid));
        $this->assertSame('50%', Tracking::get_exercise_student_progress([$this->quiz($e1), $this->quiz($e3)], $uid, $cid, $sid));
        $this->assertSame('0%', Tracking::get_exercise_student_progress([], $uid, $cid, $sid));
        $this->assertSame('0%', Tracking::get_exercise_student_progress($list, $this->bob->getId(), $cid, $sid));
    }

    public function testExerciseStudentProgressInBaseCourseNeverMatchesNullSession(): void
    {
        [$e1] = $this->seedExercises();

        // PINNED QUIRK (probable bug): attempts made outside a session are stored with a NULL
        // session_id (the column is a nullable FK), but this method filters `session_id = 0`
        // strictly, so the base-course progress is always '0%' even with finished attempts.
        $this->assertSame(
            '0%',
            Tracking::get_exercise_student_progress([$this->quiz($e1)], $this->alice->getId(), $this->course->getId(), 0)
        );
    }

    public function testCourseQuizLastFinalizationDateIgnoresUnfinishedAttempts(): void
    {
        [$e1] = $this->seedExercises();
        $cid = $this->course->getId();
        $uid = $this->alice->getId();
        $sid = $this->createSession('tk_session_quiz_final')->getId();
        $this->insertExercise($uid, $cid, $sid, $e1, '2001-02-01 10:00:00', 5, 10, '', 0, 0);
        $this->insertExercise($uid, $cid, $sid, $e1, '2001-02-02 10:00:00', 8, 10, '', 0, 0);
        $this->insertExercise($uid, $cid, $sid, $e1, '2001-02-04 10:00:00', 0, 10, 'incomplete', 0, 0);

        // The latest FINISHED attempt (status '') wins over the later 'incomplete' one.
        $this->assertSame('2001-02-02 10:00:00', Tracking::getCourseQuizLastFinalizationDate($uid, $cid, $sid, false));
        $this->assertFalse(Tracking::getCourseQuizLastFinalizationDate($this->bob->getId(), $cid, $sid, false));
        // PINNED QUIRK: attempts outside a session have a NULL session_id, but `session_id = 0` is
        // filtered strictly, so the base-course finalization date is always false.
        $this->assertFalse(Tracking::getCourseQuizLastFinalizationDate($uid, $cid, 0, false));
    }

    // ------------------------------------------------------------------
    // Learning paths
    // ------------------------------------------------------------------

    public function testTimeSpentInLpSumsItemViewTimeForTheStudentOnly(): void
    {
        [$lp] = $this->seedLearningPath();
        $course = $this->course;

        $this->assertSame(420, Tracking::get_time_spent_in_lp($this->alice->getId(), $course, [$lp], 0));
        // Rows-with-id form is accepted too.
        $this->assertSame(420, Tracking::get_time_spent_in_lp($this->alice->getId(), $course, [['iid' => $lp]], 0));
        $this->assertSame(55, Tracking::get_time_spent_in_lp($this->bob->getId(), $course, [$lp], 0));
        $this->assertSame(0, Tracking::get_time_spent_in_lp($this->carol->getId(), $course, [$lp], 0));
        // Another course (no view row) gives 0.
        $this->assertSame(0, Tracking::get_time_spent_in_lp($this->alice->getId(), $this->course2, [$lp], 0));
    }

    public function testLpFirstAndLastConnectionTimesIgnoreNotAttemptedItems(): void
    {
        $sid = $this->createSession('tk_session_lp_times')->getId();
        [$lp] = $this->seedLearningPath($sid);
        $code = $this->course->getCode();
        $uid = $this->alice->getId();

        // Items start at 1000000 (completed), 2000000 (incomplete), 3000000 ("not attempted": ignored)
        // and 1000000000 (the completed final item): MAX/MIN of start_time over attempted items.
        $this->assertEquals(1000000000, Tracking::get_last_connection_time_in_lp($uid, $code, $lp, $sid));
        $this->assertEquals(1000000, Tracking::getFirstConnectionTimeInLp($uid, $code, $lp, $sid));

        // PINNED QUIRK: views outside a session have a NULL session_id but these queries filter
        // `view.session_id = 0`, so for the base course they find nothing (NULL), session or not.
        $this->assertNull(Tracking::get_last_connection_time_in_lp($uid, $code, $lp, 0));
        $this->assertNull(Tracking::getFirstConnectionTimeInLp($uid, $code, $lp, 0));

        // Unknown LP, user without view, unknown course: 0 (or NULL-ish) results.
        $this->assertEmpty(Tracking::get_last_connection_time_in_lp($uid, $code, 999999, $sid));
        $this->assertEmpty(Tracking::getFirstConnectionTimeInLp($uid, $code, 999999, $sid));
        $this->assertEmpty(Tracking::get_last_connection_time_in_lp($this->carol->getId(), $code, $lp, $sid));
        $this->assertSame(0, Tracking::get_last_connection_time_in_lp($uid, 'tk_no_such_course', $lp, 0));
        $this->assertSame(0, Tracking::getFirstConnectionTimeInLp($uid, 'tk_no_such_course', $lp, 0));
    }

    public function testCourseLpFinalizationDateIsTheLatestCompletedFinalItemStart(): void
    {
        $sid = $this->createSession('tk_session_lp_final')->getId();
        [$lp] = $this->seedLearningPath($sid);
        $cid = $this->course->getId();
        $uid = $this->alice->getId();

        // FROM_UNIXTIME renders in the MySQL session time zone: derive that zone's offset from the server.
        $ts = 1000000000;
        $offset = (int) $this->conn->fetchOne(
            'SELECT TIMESTAMPDIFF(SECOND, ?, FROM_UNIXTIME(?))',
            ['1970-01-01 00:00:00', $ts]
        ) - $ts;
        $this->assertSame(
            gmdate('Y-m-d H:i:s', $ts + $offset),
            Tracking::getCourseLpFinalizationDate($uid, $cid, $sid, false)
        );
        $this->assertFalse(Tracking::getCourseLpFinalizationDate($this->bob->getId(), $cid, $sid, false));
        $this->assertNotFalse(Tracking::getCourseLpFinalizationDate($uid, $cid, $sid, true));
        // PINNED QUIRK: same NULL-vs-0 session mismatch as above for views outside a session.
        $this->assertFalse(Tracking::getCourseLpFinalizationDate($uid, $cid, 0, false));
        $this->assertNotNull($lp);
    }

    public function testAverageStudentScoreIsSumScoreOverSumMaxScoreOfScoAndQuizItems(): void
    {
        [$lp] = $this->seedLearningPath();
        $code = $this->course->getCode();

        // (5 + 15) / (10 + 20) * 100; the 'document' item is not part of the score.
        $this->assertEqualsWithDelta(
            66.6666666,
            Tracking::getAverageStudentScore($this->alice->getId(), $code, [$lp], 0),
            0.0001
        );
        $this->assertEqualsWithDelta(
            66.6666666,
            Tracking::getAverageStudentScore([$this->alice->getId()], $code, [$lp], 0),
            0.0001
        );
        // A student without tracking, or no student: the method answers the integer 0.
        $this->assertSame(0, Tracking::getAverageStudentScore($this->carol->getId(), $code, [$lp], 0));
        $this->assertSame(0, Tracking::getAverageStudentScore(0, $code, [$lp], 0));
    }

    public function testAverageLpProgressForStudentListTakesBestViewPerLpAndCountsMissingAsZero(): void
    {
        [$lp, $lp2] = $this->seedLearningPath();

        // Alice: LP1/course1 best view = 60 (not 40), LP2/course1 NULL ignored, LP2/course2 = 20.
        $this->assertEqualsWithDelta(
            40.0,
            Tracking::getAverageLearningPathProgressForStudentList([$this->alice->getId()]),
            0.0001
        );
        // bob has no progress at all: counted as zero in the divisor.
        $this->assertEqualsWithDelta(
            20.0,
            Tracking::getAverageLearningPathProgressForStudentList([$this->alice->getId(), $this->bob->getId()]),
            0.0001
        );
        $this->assertSame(0.0, Tracking::getAverageLearningPathProgressForStudentList([]));
        // Invalid / duplicated ids are cleaned before counting.
        $this->assertEqualsWithDelta(
            40.0,
            Tracking::getAverageLearningPathProgressForStudentList([0, -3, $this->alice->getId(), $this->alice->getId()]),
            0.0001
        );
        $this->assertNotNull($lp2);
    }

    // ------------------------------------------------------------------
    // Activity counters
    // ------------------------------------------------------------------

    public function testCountCoursesPerStudentExcludesHrRelationsAndCanAddSessions(): void
    {
        $this->course->addUserAsStudent($this->alice);
        $this->course2->addUserAsStudent($this->alice);
        $this->getEntityManager()->flush();
        // Turn the second subscription into an HR (RRHH) relation.
        $this->conn->update(
            'course_rel_user',
            ['relation_type' => COURSE_RELATION_TYPE_RRHH],
            ['user_id' => $this->alice->getId(), 'c_id' => $this->course2->getId()]
        );

        $this->assertSame(1, Tracking::count_course_per_student($this->alice->getId(), false));

        $session = $this->createSession('tk_session_count');
        $this->conn->insert('session_rel_course_rel_user', [
            'user_id' => $this->alice->getId(),
            'session_id' => $session->getId(),
            'c_id' => $this->course->getId(),
            'status' => 0,
            'visibility' => 1,
            'legal_agreement' => 0,
            'progress' => 0,
        ]);
        $this->assertSame(2, Tracking::count_course_per_student($this->alice->getId(), true));
        $this->assertSame(1, Tracking::count_course_per_student($this->alice->getId(), false));
        $this->assertSame(0, Tracking::count_course_per_student($this->carol->getId()));
    }

    public function testSessionCourseListsAndCountsPerStudent(): void
    {
        $session = $this->createSession('tk_session_lists');
        $sid = $session->getId();
        $this->conn->insert('session_rel_course', [
            'session_id' => $sid,
            'c_id' => $this->course->getId(),
            'position' => 0,
            'nbr_users' => 0,
        ]);
        $this->conn->insert('session_rel_course_rel_user', [
            'user_id' => $this->alice->getId(),
            'session_id' => $sid,
            'c_id' => $this->course->getId(),
            'status' => 0,
            'visibility' => 1,
            'legal_agreement' => 0,
            'progress' => 0,
        ]);
        $this->conn->insert('session_rel_user', [
            'user_id' => $this->alice->getId(),
            'session_id' => $sid,
            'relation_type' => 0,
            'duration' => 0,
            'registered_at' => '2001-01-01 00:00:00',
        ]);

        $courses = Tracking::get_courses_list_from_session($sid);
        $this->assertSame([$this->course->getCode()], array_keys($courses));
        $this->assertEquals($this->course->getId(), $courses[$this->course->getCode()]['c_id']);
        $this->assertSame([], Tracking::get_courses_list_from_session(0));

        $this->assertSame(1, Tracking::countSessionsPerStudent($this->alice->getId()));
        $this->assertSame(0, Tracking::countSessionsPerStudent($this->carol->getId()));

        // PINNED QUIRK: the INNER JOIN on the course table has no ON clause, so every course code
        // of the platform is returned, not only the courses the student follows in the session.
        $list = Tracking::get_course_list_in_session_from_student($this->alice->getId(), $sid);
        $this->assertArrayHasKey($this->course->getCode(), $list);
        $this->assertArrayHasKey($this->course2->getCode(), $list);
        $this->assertSame($this->course->getCode(), $list[$this->course->getCode()]);
        $this->assertSame([], Tracking::get_course_list_in_session_from_student($this->carol->getId(), $sid));
    }

    public function testVisitedLinksChatAndToolUsageCounters(): void
    {
        $cid = $this->course->getId();
        $a = $this->alice->getId();
        $b = $this->bob->getId();

        // Links: one row per click.
        $link1 = $this->insertLink('https://tk.example.org/one', 'tk_link_one');
        $link2 = $this->insertLink('https://tk.example.org/two', 'tk_link_two');
        foreach ([[$a, $link1], [$a, $link1], [$b, $link1], [$a, $link2]] as [$u, $l]) {
            $this->conn->insert('track_e_links', [
                'links_user_id' => $u,
                'links_date' => '2001-06-01 10:00:00',
                'c_id' => $cid,
                'links_link_id' => $l,
                'session_id' => 0,
            ]);
        }
        $this->assertSame(3, Tracking::count_student_visited_links($a, $cid));
        $this->assertSame(1, Tracking::count_student_visited_links($b, $cid));
        $this->assertSame(0, Tracking::count_student_visited_links($a, $cid, 99999));

        $links = Tracking::get_links_most_visited_by_course($this->course->getCode(), 0);
        $this->assertCount(2, $links);
        $this->assertSame('tk_link_one', $links[0]['title']);
        $this->assertEquals(2, $links[0][2]); // distinct visitors
        $this->assertEquals(3, $links[0]['count_visits']);
        $this->assertSame('tk_link_two', $links[1]['title']);
        $this->assertEquals(1, $links[1]['count_visits']);
        $this->assertSame([], Tracking::get_links_most_visited_by_course('tk_no_such_course'));

        // Chat connections: rolling window relative to "now", so use far past / far future rows.
        $this->conn->insert('track_e_access', [
            'access_user_id' => $a,
            'access_date' => '2001-06-01 10:00:00',
            'c_id' => $cid,
            'access_tool' => 'chat',
            'session_id' => 0,
            'user_ip' => '127.0.0.1',
        ]);
        $this->conn->insert('track_e_access', [
            'access_user_id' => $a,
            'access_date' => '2099-06-01 10:00:00',
            'c_id' => $cid,
            'access_tool' => 'chat',
            'session_id' => 0,
            'user_ip' => '127.0.0.1',
        ]);
        $this->conn->insert('track_e_access', [
            'access_user_id' => $a,
            'access_date' => '2099-06-01 10:00:00',
            'c_id' => $cid,
            'access_tool' => 'document',
            'session_id' => 0,
            'user_ip' => '127.0.0.1',
        ]);
        // Only the future chat row is within the last 7 days; the 2001 one is not, nor the document one.
        $this->assertEquals(1, Tracking::chat_connections_during_last_x_days_by_course($this->course->getCode(), 7));
        $this->assertEquals(0, Tracking::chat_connections_during_last_x_days_by_course($this->course->getCode(), 7, 99999));
        $this->assertNull(Tracking::chat_connections_during_last_x_days_by_course('tk_no_such_course', 7));
    }

    public function testToolsMostUsedAndChatLastConnectionReadTheLastAccessTable(): void
    {
        $cid = $this->course->getId();
        $a = $this->alice->getId();
        $b = $this->bob->getId();

        // tool => [user ids...]: counts 4 / 3 / 2 / 1 so the ordering is unambiguous.
        $usage = [
            'tk_tool_a' => [$a, $a, $b, $b],
            'tk_tool_b' => [$a, $b, $b],
            'tk_tool_c' => [$a, $a],
            'tk_tool_d' => [$a],
        ];
        foreach ($usage as $tool => $users) {
            foreach ($users as $u) {
                $this->conn->insert('track_e_lastaccess', [
                    'access_user_id' => $u,
                    'access_date' => '2001-07-01 10:00:00',
                    'c_id' => $cid,
                    'access_tool' => $tool,
                    'session_id' => 0,
                ]);
            }
        }
        // Empty tool names are ignored.
        $this->conn->insert('track_e_lastaccess', [
            'access_user_id' => $a,
            'access_date' => '2001-07-01 10:00:00',
            'c_id' => $cid,
            'access_tool' => '',
            'session_id' => 0,
        ]);

        $tools = Tracking::get_tools_most_used_by_course($cid, 0);
        // Top 3 only, most used first.
        $this->assertSame(['tk_tool_a', 'tk_tool_b', 'tk_tool_c'], array_column($tools, 'access_tool'));
        $this->assertEquals([4, 3, 2], array_column($tools, 'count_access_tool'));
        $this->assertEquals(2, $tools[0][1]); // distinct users of tk_tool_a
        $this->assertSame([], Tracking::get_tools_most_used_by_course($cid, 99999));

        // Chat last connection: latest row of the chat tool, '' when none.
        foreach (['2001-07-02 11:00:00', '2001-07-03 12:34:56', '2001-07-01 09:00:00'] as $date) {
            $this->conn->insert('track_e_lastaccess', [
                'access_user_id' => $a,
                'access_date' => $date,
                'c_id' => $cid,
                'access_tool' => 'chat',
                'session_id' => 0,
            ]);
        }
        // The formatted date cannot be pinned (empty in the test kernel); only the no-row answer is.
        $this->assertSame('', Tracking::chat_last_connection($this->carol->getId(), $cid, 0));
    }

    public function testInactiveStudentsInCourse(): void
    {
        $cid = $this->course->getId();
        $this->course->addUserAsStudent($this->alice);
        $this->course->addUserAsStudent($this->bob);
        $this->course->addUserAsStudent($this->carol);
        $this->getEntityManager()->flush();

        // alice: old access in this course (2001). bob: none anywhere. carol: access only in ANOTHER course.
        $this->insertCourseAccess($this->alice->getId(), $cid, '2001-03-01 10:00:00', '2001-03-01 11:00:00');
        $this->insertCourseAccess($this->carol->getId(), $this->course2->getId(), '2001-03-01 10:00:00', '2001-03-01 11:00:00');

        // PINNED QUIRK: "never connected" joins the access table on user only, not on the course:
        // carol is NOT reported although she never opened THIS course.
        $never = array_map('intval', Tracking::getInactiveStudentsInCourse($cid, 'never'));
        $this->assertSame([$this->bob->getId()], $never);

        // Everyone whose latest access in this course is older than N days (alice, from 2001).
        $since = array_map('intval', Tracking::getInactiveStudentsInCourse($cid, 30));
        $this->assertSame([$this->alice->getId()], $since);

        // A far-future access is never "older than N days".
        $this->insertCourseAccess($this->bob->getId(), $cid, '2099-03-01 10:00:00', '2099-03-01 11:00:00');
        $since = array_map('intval', Tracking::getInactiveStudentsInCourse($cid, 30));
        $this->assertSame([$this->alice->getId()], $since);

        $this->assertFalse(Tracking::getInactiveStudentsInCourse(0));
    }

    // ------------------------------------------------------------------
    // Fixtures
    // ------------------------------------------------------------------

    private function seedPlatformLogins(): void
    {
        $a = $this->alice->getId();
        $rows = [
            [$a, '2001-03-01 10:00:00', '2001-03-01 10:30:00'],
            [$a, '2001-03-02 09:00:00', '2001-03-02 09:00:45'],
            [$a, '2001-03-03 08:00:00', null],
            [$this->bob->getId(), '2001-03-01 12:00:00', '2001-03-01 12:01:40'],
        ];
        foreach ($rows as [$user, $in, $out]) {
            $this->conn->insert('track_e_login', [
                'login_user_id' => $user,
                'login_date' => $in,
                'user_ip' => '127.0.0.1',
                'logout_date' => $out,
            ]);
        }
    }

    private function insertCourseAccess(int $userId, int $courseId, string $in, ?string $out, int $sessionId = 0): void
    {
        $this->conn->insert('track_e_course_access', [
            'user_id' => $userId,
            'c_id' => $courseId,
            'login_course_date' => $in,
            'logout_course_date' => $out,
            'counter' => 1,
            'session_id' => $sessionId,
            'user_ip' => '127.0.0.1',
        ]);
    }

    private function seedCourseAccess(): void
    {
        $cid = $this->course->getId();
        $a = $this->alice->getId();

        $this->insertCourseAccess($a, $cid, '2001-03-01 10:00:00', '2001-03-01 11:00:00'); // 3600
        $this->insertCourseAccess($a, $cid, '2001-03-01 10:00:00', '2001-03-01 10:00:00'); // 0: ignored
        $this->insertCourseAccess($a, $cid, '2001-03-01 10:30:00', '2001-03-01 10:00:00'); // negative: ignored
        $this->insertCourseAccess($a, $cid, '2001-03-01 12:00:00', '2001-03-01 12:01:30'); // 90
        // Logout is the latest of all base-context rows.
        $this->insertCourseAccess($a, $cid, '2001-03-02 08:00:00', '2001-03-02 08:00:00'); // 0 but latest logout
        $this->insertCourseAccess($this->bob->getId(), $cid, '2001-03-01 09:00:00', '2001-03-01 09:00:30'); // 30
        $this->insertCourseAccess($a, $cid, '2001-03-05 12:00:00', '2001-03-05 12:01:00', 99999); // 60
        $this->insertCourseAccess($a, $cid, '2001-03-05 11:00:00', '2001-03-05 11:01:00', 99999); // 60
    }

    private function insertAccessComplete(int $currentId, string $date, string $tool, int $toolId = 0, int $detail = 0, string $action = '', string $actionDetails = '', int $loginAs = 0): void
    {
        $this->conn->insert('track_e_access_complete', [
            'user_id' => $this->alice->getId(),
            'date_reg' => $date,
            'tool' => $tool,
            'tool_id' => $toolId,
            'tool_id_detail' => $detail,
            'action' => $action,
            'action_details' => $actionDetails,
            'current_id' => $currentId,
            'ip_user' => '127.0.0.1',
            'user_agent' => 'tk',
            'session_id' => 0,
            'c_id' => $this->course->getId(),
            'ch_sid' => 'tk',
            'login_as' => $loginAs,
            'info' => '',
            'url' => '',
        ]);
    }

    private function seedAccessComplete(): void
    {
        // Connection 11.
        $this->insertAccessComplete(11, '2001-03-01 10:00:00', 'course-main');
        $this->insertAccessComplete(11, '2001-03-01 10:01:00', 'course-main');
        $this->insertAccessComplete(11, '2001-03-01 10:02:00', TOOL_LEARNPATH, 5, 9, 'view', 'att');
        $this->insertAccessComplete(11, '2001-03-01 10:05:00', TOOL_LEARNPATH, 5, 9, 'view', 'att');
        $this->insertAccessComplete(11, '2001-03-01 10:06:00', TOOL_QUIZ, 0, 0, 'learnpath_id', '5');
        $this->insertAccessComplete(11, '2001-03-01 10:07:30', TOOL_QUIZ, 0, 0, 'other', '5');
        $this->insertAccessComplete(11, '2001-03-01 10:08:00', TOOL_FORUM);
        $this->insertAccessComplete(11, '2001-03-01 10:08:45', TOOL_FORUM);
        // Connection 12: a single row, so a zero-length connection that adds no time.
        $this->insertAccessComplete(12, '2001-03-02 10:00:00', 'course-main');
        // Rows that must be ignored by the time computation: connection 0, and "login as".
        $this->insertAccessComplete(0, '2001-03-03 10:00:00', 'course-main');
        $this->insertAccessComplete(13, '2001-03-04 10:00:00', 'course-main', 0, 0, '', '', 1);
        $this->insertAccessComplete(13, '2001-03-04 11:00:00', 'course-main', 0, 0, '', '', 1);
    }

    private function insertQuiz(string $title): int
    {
        $this->conn->insert('c_quiz', [
            'title' => $title,
            'type' => 1,
            'random' => 0,
            'random_answers' => 0,
            'results_disabled' => 0,
            'max_attempt' => 0,
            'feedback_type' => 0,
            'expired_time' => 0,
            'propagate_neg' => 0,
            'review_answers' => 0,
            'random_by_category' => 0,
            'display_category_name' => 0,
            'hide_question_title' => 0,
            'page_result_configuration' => '{}',
        ]);

        return (int) $this->conn->lastInsertId();
    }

    private function insertExercise(
        int $userId,
        int $courseId,
        ?int $sessionId,
        int $quizId,
        string $date,
        float $score,
        float $maxScore,
        string $status,
        int $lpId,
        int $lpItemId
    ): void {
        $this->conn->insert('track_e_exercises', [
            'exe_user_id' => $userId,
            'c_id' => $courseId,
            'session_id' => $sessionId,
            'exe_exo_id' => $quizId,
            'exe_date' => $date,
            'score' => $score,
            'max_score' => $maxScore,
            'user_ip' => '127.0.0.1',
            'status' => $status,
            'data_tracking' => '',
            'start_date' => $date,
            'steps_counter' => 0,
            'orig_lp_id' => $lpId,
            'orig_lp_item_id' => $lpItemId,
            'exe_duration' => 0,
            'orig_lp_item_view_id' => 0,
            'questions_to_check' => '',
        ]);
    }

    /**
     * @return int[] ids of the three quizzes
     */
    private function seedExercises(): array
    {
        $e1 = $this->insertQuiz('tk_quiz_one');
        $e2 = $this->insertQuiz('tk_quiz_two');
        $e3 = $this->insertQuiz('tk_quiz_three');
        $cid = $this->course->getId();
        $uid = $this->alice->getId();

        // Outside a session the session column is NULL (see the session quirk test).
        $this->insertExercise($uid, $cid, null, $e1, '2001-02-01 10:00:00', 5, 10, '', 0, 0);
        $this->insertExercise($uid, $cid, null, $e1, '2001-02-02 10:00:00', 8, 10, '', 0, 0);
        $this->insertExercise($uid, $cid, null, $e1, '2001-02-03 10:00:00', 9, 10, '', 5, 7);
        $this->insertExercise($uid, $cid, null, $e1, '2001-02-04 10:00:00', 0, 10, 'incomplete', 0, 0);

        return [$e1, $e2, $e3];
    }

    private function quiz(int $id): object
    {
        return new class($id) {
            public function __construct(private readonly int $id)
            {
            }

            public function getIid(): int
            {
                return $this->id;
            }
        };
    }

    private function insertLink(string $url, string $title): int
    {
        $this->conn->insert('c_link', ['url' => $url, 'title' => $title]);

        return (int) $this->conn->lastInsertId();
    }

    private function insertLp(string $title): int
    {
        $this->conn->insert('c_lp', [
            'lp_type' => 1,
            'title' => $title,
            'path' => '',
            'force_commit' => 0,
            'content_maker' => '',
            'content_license' => '',
            'js_lib' => '',
            'debug' => 0,
            'theme' => '',
            'author' => '',
            'prerequisite' => 0,
            'hide_toc_frame' => 0,
            'seriousgame_mode' => 0,
            'autolaunch' => 0,
            'max_attempts' => 0,
            'subscribe_users' => 0,
            'created_on' => '2001-01-01 00:00:00',
            'modified_on' => '2001-01-01 00:00:00',
        ]);

        return (int) $this->conn->lastInsertId();
    }

    private function insertLpItem(int $lpId, string $type, float $maxScore, int $order): int
    {
        $this->conn->insert('c_lp_item', [
            'lp_id' => $lpId,
            'title' => 'tk_item_'.$order,
            'item_type' => $type,
            'ref' => '',
            'path' => '',
            'min_score' => 0,
            'max_score' => $maxScore,
            'display_order' => $order,
            'launch_data' => '',
            'lvl' => 1,
        ]);

        return (int) $this->conn->lastInsertId();
    }

    private function insertLpView(int $userId, int $lpId, int $courseId, ?int $progress, ?int $sessionId = null): int
    {
        $this->conn->insert('c_lp_view', [
            'user_id' => $userId,
            'lp_id' => $lpId,
            'c_id' => $courseId,
            'session_id' => $sessionId,
            'view_count' => 1,
            'last_item' => 0,
            'progress' => $progress,
        ]);

        return (int) $this->conn->lastInsertId();
    }

    private function insertLpItemView(int $itemId, int $viewId, int $start, int $total, float $score, string $status): void
    {
        $this->conn->insert('c_lp_item_view', [
            'lp_item_id' => $itemId,
            'lp_view_id' => $viewId,
            'view_count' => 1,
            'start_time' => $start,
            'total_time' => $total,
            'score' => $score,
            'status' => $status,
        ]);
    }

    /**
     * Two LPs. LP 1 has a 'sco' item (max 10), a 'quiz' item (max 20), a 'document' item
     * (max 100) and a final item. Alice has progress 40 and 60 on LP 1 (two views) plus
     * 20 on LP 2 and 100 on LP 1 in the second course; bob has a small time only.
     *
     * @return int[] [lp1, lp2]
     */
    private function seedLearningPath(?int $aliceSessionId = null): array
    {
        $cid = $this->course->getId();
        $alice = $this->alice->getId();
        $lp = $this->insertLp('tk_lp_one');
        $lp2 = $this->insertLp('tk_lp_two');

        $sco = $this->insertLpItem($lp, 'sco', 10, 1);
        $quiz = $this->insertLpItem($lp, 'quiz', 20, 2);
        $doc = $this->insertLpItem($lp, 'document', 100, 3);
        $final = $this->insertLpItem($lp, TOOL_LP_FINAL_ITEM, 0, 4);

        $view1 = $this->insertLpView($alice, $lp, $cid, 40, $aliceSessionId);
        $this->insertLpItemView($sco, $view1, 1000000, 120, 5, 'completed');
        $this->insertLpItemView($quiz, $view1, 2000000, 300, 15, 'incomplete');
        $this->insertLpItemView($doc, $view1, 3000000, 0, 100, 'not attempted');
        $this->insertLpItemView($final, $view1, 1000000000, 0, 0, 'completed');

        // Second view of the same LP with a better progress and a NULL-progress view are both ignored by sums.
        $this->conn->insert('c_lp_view', [
            'user_id' => $alice,
            'lp_id' => $lp,
            'c_id' => $cid,
            'session_id' => null,
            'view_count' => 2,
            'last_item' => 0,
            'progress' => 60,
        ]);
        $this->insertLpView($alice, $lp2, $cid, null);
        // Alice, LP 2 has a measured progress in another course (separate group in the average).
        $this->insertLpView($alice, $lp2, $this->course2->getId(), 20);

        $bobView = $this->insertLpView($this->bob->getId(), $lp, $cid, null);
        $this->insertLpItemView($sco, $bobView, 1500000, 55, 0, 'completed');

        return [$lp, $lp2];
    }
}
