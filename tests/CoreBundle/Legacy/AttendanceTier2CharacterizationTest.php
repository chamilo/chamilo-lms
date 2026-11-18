<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use Attendance;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CAttendance;
use Chamilo\Tests\ChamiloTestTrait;
use ChamiloSession;
use Database;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session as HttpSession;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * Pins for the Attendance methods AttendanceCharacterizationTest leaves out because they need a resource-linked
 * attendance: the faults averages (read the per-user results of the attendances of a course) and the session half
 * of getCoursesWithAttendance(). Titles start with "at2_" (sibling suites share the database).
 *
 * NOT covered, on purpose: attendance_add() / attendance_edit() only reach the Database::query() calls when the
 * attendance is already linked to the gradebook, and those two UPDATEs filter gradebook_link on a column called
 * "iid" which the table does not have (it is "id"): the branch cannot run without a SQL error.
 */
final class AttendanceTier2CharacterizationTest extends KernelTestCase
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
        $session = new HttpSession(new MockArraySessionStorage());
        Container::setSession($session);
        $request = Request::create('/main/attendance/index.php');
        $request->setSession($session);
        Container::setRequest($request);

        $this->conn = $container->get('doctrine')->getConnection();
        $course = $this->getCourse(self::COURSE);
        ChamiloSession::write('_user', ['id' => self::ADMIN]);
        ChamiloSession::write('_real_cid', $course->getId());
        ChamiloSession::write('_cid', $course->getCode());
        ChamiloSession::write('_course', api_get_course_info($course->getCode()));
        ChamiloSession::write('sid', 0);
        ChamiloSession::erase('login_as');
    }

    public function testFaultsAverageByCourseSumsTheMissingScoresOfTheUserOnly(): void
    {
        $a1 = $this->att(10);
        $a2 = $this->att(4);
        $a3 = $this->att(2);
        $this->result(self::ANON, $a1, 7);
        $this->result(self::FALLBACK, $a1, 0);
        $this->result(self::FALLBACK, $a2, 1);
        $this->result(self::ANON, $a3, 5);
        $lib = $this->libWithAttendances([$a1, $a2, $a3]);

        $r = $lib->get_faults_average_by_course(self::ANON, $this->getCourse(self::COURSE));

        // a1: 10-7=3; a2: no result row => score 0 => 4; a3: score above the max => no fault; total weight 16.
        $this->assertSame(7, (int) $r['faults']);
        $this->assertSame(16, (int) $r['total']);
        $this->assertSame(44.0, (float) $r['percent']);

        $other = $lib->get_faults_average_by_course(self::FALLBACK, $this->getCourse(self::COURSE));
        // a1: 10-0=10; a2: 4-1=3; a3: no row => 2.
        $this->assertSame(15, (int) $other['faults']);
        $this->assertSame(16, (int) $other['total']);
    }

    public function testFaultsAverageByCourseWithoutAnyAttendanceIsAllZero(): void
    {
        $r = $this->libWithAttendances([])->get_faults_average_by_course(self::ANON, $this->getCourse(self::COURSE));

        $this->assertSame(['faults' => 0, 'total' => 0, 'percent' => 0], $r);
    }

    public function testFaultsAverageInsideCoursesWalksTheCoursesOfTheUser(): void
    {
        $this->subscribe(self::ANON);
        $a1 = $this->att(10);
        $a2 = $this->att(4);
        $this->result(self::ANON, $a1, 7);
        $this->result(self::FALLBACK, $a2, 4);
        $lib = $this->libWithAttendances([$a1, $a2]);

        $r = $lib->get_faults_average_inside_courses(self::ANON);

        // a1: 3 faults; a2: the result of another user does not count => 4 faults; total 14.
        $this->assertSame(7, (int) $r['faults']);
        $this->assertSame(14, (int) $r['total']);
        $this->assertSame(50.0, (float) $r['percent']);
    }

    public function testCoursesWithAttendanceReadsTheSessionsOfTheUser(): void
    {
        $sessionId = $this->session();
        $other = $this->session();
        $att = $this->att(3, self::COURSE, $sessionId);
        $cal = $this->cal($att, '2033-05-06 10:00:00');
        $this->conn->executeStatement(
            "INSERT INTO c_attendance_sheet (user_id, attendance_calendar_id, presence, signature) VALUES (?, ?, 1, '')",
            [self::ANON, $cal]
        );
        $this->enrol($sessionId, self::ANON);
        $this->enrol($other, self::FALLBACK);

        $mine = (new Attendance())->getCoursesWithAttendance(self::ANON);
        $theirs = (new Attendance())->getCoursesWithAttendance(self::FALLBACK);

        $this->assertSame(['2033-05-06'], array_keys($mine));
        $row = $mine['2033-05-06'][0];
        $this->assertSame($sessionId, (int) $row['session']);
        $this->assertSame($att, (int) $row['attendanceId']);
        $this->assertSame(self::COURSE, (int) $row['courseId']);
        $this->assertSame([], $theirs, 'a user enrolled in another session sees none of these dates');
    }

    public function testSheetAddUpdatesOnlyTheRowOfEachUser(): void
    {
        $att = $this->att(0);
        $c1 = $this->cal($att, '2034-01-01 08:00:00');
        $other = (int) $this->createUser('at2_other')->getId();
        $this->subscribe(self::ANON);
        $this->subscribe($other);
        $lib = new Attendance();
        $lib->attendance_sheet_add($c1, [self::ANON], $this->entity($att));
        $this->assertSame([self::ANON => 1, $other => 0], $this->presences($c1));

        // The roles are swapped: each row must be rewritten on its own, not the whole date.
        $lib->attendance_sheet_add($c1, [$other], $this->entity($att));

        $this->assertSame([self::ANON => 0, $other => 1], $this->presences($c1));
    }

    public function testUpdateUsersResultsRewritesEachExistingResultWithItsOwnCount(): void
    {
        $att = $this->att(0);
        $c1 = $this->cal($att, '2034-02-01 08:00:00');
        $c2 = $this->cal($att, '2034-02-02 08:00:00');
        $this->sheet(self::ANON, $c1, 1);
        $this->sheet(self::ANON, $c2, 1);
        $this->sheet(self::FALLBACK, $c1, 1);
        $this->sheet(self::FALLBACK, $c2, 0);
        $this->result(self::ANON, $att, 9);
        $this->result(self::FALLBACK, $att, 9);

        (new Attendance())->updateUsersResults([self::ANON, self::FALLBACK], $this->entity($att));

        $scores = $this->conn->fetchAllKeyValue('SELECT user_id, score FROM c_attendance_result WHERE attendance_id = ?', [$att]);
        $this->assertSame([self::ANON => 2, self::FALLBACK => 1], array_map('intval', $scores));
    }

    public function testUserScoreOfAGroupCountsOnlyThePresencesOnItsDates(): void
    {
        $att = $this->att(0);
        $c1 = $this->cal($att, '2034-03-01 08:00:00');
        $c2 = $this->cal($att, '2034-03-02 08:00:00');
        $c3 = $this->cal($att, '2034-03-03 08:00:00');
        $c4 = $this->cal($att, '2034-03-04 08:00:00');
        $this->conn->executeStatement(
            'INSERT INTO c_group_info (title, status, max_student, doc_state, calendar_state, work_state,
                announcements_state, forum_state, wiki_state, chat_state, self_registration_allowed,
                self_unregistration_allowed, document_access)
             VALUES (?, 1, 0, 1, 1, 1, 1, 1, 1, 1, 0, 0, 0)',
            ['at2_group']
        );
        $group = (int) $this->conn->lastInsertId();
        foreach ([$c1, $c2, $c3] as $c) {
            $this->conn->executeStatement('INSERT INTO c_attendance_calendar_rel_group (group_id, calendar_id) VALUES (?, ?)', [$group, $c]);
        }
        foreach ([[$c1, 1], [$c2, 1], [$c3, 0], [$c4, 1]] as [$c, $presence]) {
            $this->sheet(self::ANON, $c, $presence);
        }
        $this->sheet(self::FALLBACK, $c1, 1);

        $lib = new Attendance();

        // c1 and c2 present; c3 absent; c4 is not a date of the group.
        $this->assertSame(2, (int) $lib->get_user_score(self::ANON, $att, $group));
        $this->assertSame(1, (int) $lib->get_user_score(self::FALLBACK, $att, $group));
    }

    private function entity(int $attId): CAttendance
    {
        $em = Database::getManager();
        $em->clear();

        return $em->find(CAttendance::class, $attId);
    }

    private function sheet(int $userId, int $calId, int $presence): void
    {
        $this->conn->executeStatement(
            "INSERT INTO c_attendance_sheet (user_id, attendance_calendar_id, presence, signature) VALUES (?, ?, ?, '')",
            [$userId, $calId, $presence]
        );
    }

    /**
     * @return array<int, int> user id => presence
     */
    private function presences(int $calId): array
    {
        $rows = $this->conn->fetchAllKeyValue('SELECT user_id, presence FROM c_attendance_sheet WHERE attendance_calendar_id = ? ORDER BY user_id', [$calId]);

        return array_map('intval', $rows);
    }

    private function libWithAttendances(array $attendanceIds): Attendance
    {
        $em = Database::getManager();
        $em->clear();
        $entities = array_map(static fn (int $id): CAttendance => $em->find(CAttendance::class, $id), $attendanceIds);

        return new class($entities) extends Attendance {
            public function __construct(
                private array $entities
            ) {}

            public function getAttendanceList(Course $course, ?Session $session = null)
            {
                return $this->entities;
            }
        };
    }

    private function att(int $qualifyMax, int $courseId = self::COURSE, int $sessionId = 0): int
    {
        $this->conn->executeStatement(
            'INSERT INTO c_attendance (title, active, attendance_qualify_max, attendance_weight, locked, require_unique)
             VALUES (?, 1, ?, 1, 0, 0)',
            ['at2_att_'.(++$this->seq), $qualifyMax]
        );
        $id = (int) $this->conn->lastInsertId();
        $node = $this->conn->fetchOne('SELECT id FROM resource_node ORDER BY id LIMIT 1 OFFSET '.(20 + $this->seq));
        $this->conn->executeStatement('UPDATE c_attendance SET resource_node_id = ? WHERE iid = ?', [$node, $id]);
        // getAttendanceList() only sees nodes of the attendances resource type (rolled back with the test).
        $this->conn->executeStatement(
            "UPDATE resource_node SET resource_type_id = (SELECT id FROM resource_type WHERE title = 'attendances') WHERE id = ?",
            [$node]
        );
        $this->conn->executeStatement(
            'INSERT INTO resource_link (resource_node_id, c_id, session_id, visibility, display_order, resource_type_group, created_at, updated_at)
             VALUES (?, ?, ?, 2, 0, 0, NOW(), NOW())',
            [$node, $courseId, $sessionId ?: null]
        );

        return $id;
    }

    private function cal(int $attId, string $dateTime): int
    {
        $this->conn->executeStatement(
            'INSERT INTO c_attendance_calendar (attendance_id, date_time, done_attendance, blocked) VALUES (?, ?, 1, 0)',
            [$attId, $dateTime]
        );

        return (int) $this->conn->lastInsertId();
    }

    private function result(int $userId, int $attId, int $score): void
    {
        $this->conn->executeStatement(
            'INSERT INTO c_attendance_result (user_id, attendance_id, score) VALUES (?, ?, ?)',
            [$userId, $attId, $score]
        );
    }

    private function subscribe(int $userId): void
    {
        $this->conn->executeStatement(
            'INSERT INTO course_rel_user (user_id, c_id, relation_type, status, is_tutor, sort, user_course_cat, legal_agreement, progress)
             VALUES (?, ?, 0, 5, 0, 0, 0, 0, 0)',
            [$userId, self::COURSE]
        );
    }

    private function session(): int
    {
        $this->conn->executeStatement(
            'INSERT INTO session (title, nbr_courses, nbr_users, nbr_classes, visibility, status, position)
             VALUES (?, 0, 0, 0, 1, 0, 0)',
            ['at2_session_'.(++$this->seq)]
        );

        return (int) $this->conn->lastInsertId();
    }

    private function enrol(int $sessionId, int $userId): void
    {
        $this->conn->executeStatement(
            'INSERT INTO session_rel_course_rel_user (user_id, session_id, c_id, status, visibility, legal_agreement, progress)
             VALUES (?, ?, ?, 0, 1, 0, 0)',
            [$userId, $sessionId, self::COURSE]
        );
    }
}
