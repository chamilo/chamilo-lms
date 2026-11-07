<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use Attendance;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CAttendance;
use Chamilo\Tests\ChamiloTestTrait;
use Database;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * An id taken from the request (attendance, calendar date, group, user) must be checked against the current
 * course before it is read or written: the attendance tables have no course column, so a teacher of one course
 * could otherwise edit or delete the dates of the attendance of another. Fixtures are tagged "aa_".
 */
final class AttendanceAccessControlTest extends KernelTestCase
{
    use ChamiloTestTrait;

    private const COURSE = 1;
    private const OTHER_COURSE = 2;
    private const ADMIN = 1;
    private const ANON = 2;

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
        $course = $this->getCourse(self::COURSE);
        \ChamiloSession::write('_user', ['id' => self::ADMIN]);
        \ChamiloSession::write('_real_cid', $course->getId());
        \ChamiloSession::write('_cid', $course->getCode());
        \ChamiloSession::write('_course', api_get_course_info($course->getCode()));
        \ChamiloSession::write('sid', 0);
        \ChamiloSession::erase('login_as');
    }

    private function att(int $courseId): int
    {
        $this->conn->executeStatement(
            'INSERT INTO c_attendance (title, active, attendance_qualify_max, attendance_weight, locked, require_unique)
             VALUES (?, 1, 0, 1, 0, 0)',
            ['aa_att_'.(++$this->seq)]
        );
        $id = (int) $this->conn->lastInsertId();
        $node = $this->conn->fetchOne('SELECT id FROM resource_node ORDER BY id LIMIT 1 OFFSET '.(5 + $this->seq));
        $this->conn->executeStatement('UPDATE c_attendance SET resource_node_id = ? WHERE iid = ?', [$node, $id]);
        $this->conn->executeStatement(
            "INSERT INTO resource_link (resource_node_id, c_id, visibility, display_order, resource_type_group, created_at, updated_at)
             VALUES (?, ?, 2, 0, 0, NOW(), NOW())",
            [$node, $courseId]
        );

        return $id;
    }

    private function cal(int $attId, string $dateTime): int
    {
        $this->conn->executeStatement(
            'INSERT INTO c_attendance_calendar (attendance_id, date_time, done_attendance, blocked) VALUES (?, ?, 0, 0)',
            [$attId, $dateTime]
        );

        return (int) $this->conn->lastInsertId();
    }

    private function entity(int $attId): CAttendance
    {
        $em = Database::getManager();
        $em->clear();

        return $em->find(CAttendance::class, $attId);
    }

    private function dateOf(int $calId): ?string
    {
        $v = $this->conn->fetchOne('SELECT date_time FROM c_attendance_calendar WHERE iid = ?', [$calId]);

        return false === $v ? null : (string) $v;
    }

    public function testAttendanceBelongsToTheCourseItIsLinkedTo(): void
    {
        $att = $this->att(self::COURSE);

        $this->assertTrue(Attendance::attendanceBelongsToCourse($att, self::COURSE));
        $this->assertFalse(Attendance::attendanceBelongsToCourse($att, self::OTHER_COURSE), 'another course must not reach it');
        $this->assertFalse(Attendance::attendanceBelongsToCourse(0, self::COURSE));
        $this->assertFalse(Attendance::attendanceBelongsToCourse(99999999, self::COURSE));
    }

    public function testCalendarBelongsOnlyToItsOwnAttendance(): void
    {
        $mine = $this->att(self::COURSE);
        $theirs = $this->att(self::OTHER_COURSE);
        $cal = $this->cal($theirs, '2033-01-01 08:00:00');

        $this->assertTrue(Attendance::attendanceCalendarBelongsToAttendance($cal, $theirs));
        $this->assertFalse(Attendance::attendanceCalendarBelongsToAttendance($cal, $mine));
        $this->assertFalse(Attendance::attendanceCalendarBelongsToAttendance(0, $mine));
    }

    public function testCalendarEditRefusesADateOfAnotherAttendance(): void
    {
        $mine = $this->att(self::COURSE);
        $theirs = $this->att(self::OTHER_COURSE);
        $cal = $this->cal($theirs, '2033-02-01 08:00:00');

        $lib = new Attendance();
        $lib->set_date_time('2034-02-01 08:00:00');
        $this->assertSame(0, $lib->attendance_calendar_edit($cal, $this->entity($mine)));
        $this->assertSame('2033-02-01 08:00:00', $this->dateOf($cal), 'the foreign date is untouched');

        $own = $this->cal($mine, '2033-03-01 08:00:00');
        $lib->set_date_time('2034-03-01 08:00:00');
        $this->assertSame(1, $lib->attendance_calendar_edit($own, $this->entity($mine)));
        $this->assertSame('2034-03-01 08:00:00', $this->dateOf($own));
    }

    public function testCalendarDeleteRefusesADateOfAnotherAttendance(): void
    {
        $mine = $this->att(self::COURSE);
        $theirs = $this->att(self::OTHER_COURSE);
        $cal = $this->cal($theirs, '2033-04-01 08:00:00');

        $this->assertSame(0, (new Attendance())->attendance_calendar_delete($cal, $this->entity($mine)));
        $this->assertSame('2033-04-01 08:00:00', $this->dateOf($cal), 'the foreign date is still there');

        $own = $this->cal($mine, '2033-05-01 08:00:00');
        (new Attendance())->attendance_calendar_delete($own, $this->entity($mine));
        $this->assertNull($this->dateOf($own));
    }

    public function testSheetAddRefusesADateOfAnotherAttendanceAndUsersOutsideTheCourse(): void
    {
        $mine = $this->att(self::COURSE);
        $theirs = $this->att(self::OTHER_COURSE);
        $foreign = $this->cal($theirs, '2033-06-01 08:00:00');

        $lib = new Attendance();
        $this->assertSame(0, $lib->attendance_sheet_add($foreign, [self::ANON], $this->entity($mine)));
        $this->assertSame(0, (int) $this->conn->fetchOne('SELECT COUNT(*) FROM c_attendance_sheet WHERE attendance_calendar_id = ?', [$foreign]));
        $this->assertSame(0, (int) $this->conn->fetchOne('SELECT done_attendance FROM c_attendance_calendar WHERE iid = ?', [$foreign]));

        // ANON is not subscribed to the course: it cannot be marked present
        $own = $this->cal($mine, '2033-07-01 08:00:00');
        $lib->attendance_sheet_add($own, [self::ANON], $this->entity($mine));
        $this->assertSame(0, (int) $this->conn->fetchOne('SELECT COUNT(*) FROM c_attendance_sheet WHERE attendance_calendar_id = ? AND user_id = ?', [$own, self::ANON]));
    }

    public function testFilterGroupIdsKeepsOnlyTheGroupsOfTheCourse(): void
    {
        $this->assertSame([5], Attendance::filterGroupIds('5', [5, 6]));
        $this->assertSame([6], Attendance::filterGroupIds(['7', '6', '6', 'x', ['y']], [5, 6]));
        $this->assertSame([], Attendance::filterGroupIds('9', [5, 6]), 'a group of another course is dropped');
        $this->assertSame([], Attendance::filterGroupIds('0', [0, 5]));
        $this->assertSame([], Attendance::filterGroupIds(null, [5]));
    }

    public function testStudentsOnlyOpenVisibleAttendances(): void
    {
        $att = (new CAttendance())->setActive(1);
        $hidden = (new CAttendance())->setActive(0);
        $deleted = (new CAttendance())->setActive(2);

        $this->assertTrue(Attendance::canOpenAttendance($att, false, false));
        $this->assertFalse(Attendance::canOpenAttendance($hidden, false, false));
        $this->assertFalse(Attendance::canOpenAttendance($deleted, false, false));
        $this->assertTrue(Attendance::canOpenAttendance($hidden, true, false), 'editors see hidden ones');
        $this->assertFalse(Attendance::canOpenAttendance($deleted, false, true), 'nobody but editors opens a deleted one');
    }
}
