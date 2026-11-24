<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Legacy;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use Database;
use Doctrine\DBAL\Connection;
use Tracking;

/**
 * Wave 3 characterization tests for the Tracking library: they pin what the existing tests left open
 * before the raw SQL of the library is rewritten with the QueryBuilder.
 */
final class TrackingTier3CharacterizationTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    private Connection $conn;
    private User $alice;
    private User $bob;
    private Course $course;
    private Course $course2;

    protected function setUp(): void
    {
        parent::setUp();

        Database::setManager($this->getEntityManager());
        $this->conn = $this->getEntityManager()->getConnection();
        $this->alice = $this->createUser('t3_alice');
        $this->bob = $this->createUser('t3_bob');
        $this->course = $this->createCourse('t3_course');
        $this->course2 = $this->createCourse('t3_course_two');
    }

    public function testCourseConnectionsCountStartBoundIsStrictAfterRounding(): void
    {
        $cid = $this->course->getId();
        foreach (['2001-03-01 10:00:00', '2001-03-01 10:30:00', '2001-03-01 12:00:00'] as $in) {
            $this->insertCourseAccess($this->alice->getId(), $cid, $in, $in);
        }

        // 10:00:30 is rounded down to 10:00:00 and the bound is strict: the 10:00:00 login is out.
        $this->assertEquals(
            2,
            Tracking::get_course_connections_count($cid, 0, '2001-03-01 10:00:30', '2001-03-01 12:01:00')
        );
        // 09:59:59 rounds to 09:59:00: the 10:00:00 login is in.
        $this->assertEquals(
            3,
            Tracking::get_course_connections_count($cid, 0, '2001-03-01 09:59:59', '2001-03-01 12:01:00')
        );
    }

    public function testLastConnectionOnTheCourseWithNewTrackingIsTheLatestLogout(): void
    {
        $cid = $this->course->getId();
        $alice = $this->alice->getId();
        $this->insertCourseAccess($alice, $cid, '2001-03-01 10:00:00', '2001-03-03 11:00:00');
        $this->insertCourseAccess($alice, $cid, '2001-03-04 10:00:00', '2001-03-04 10:30:00');
        $this->insertCourseAccess($alice, $cid, '2001-03-02 10:00:00', '2001-03-02 10:30:00');

        $previous = (string) Container::getSettingsManager()->getSetting('lp.lp_minimum_time');

        try {
            Container::getSettingsManager()->updateSetting('lp.lp_minimum_time', 'true');
            $fieldId = (int) $this->conn->fetchOne("SELECT id FROM extra_field WHERE item_type = 2 AND variable = 'new_tracking_system'");
            $this->assertGreaterThan(0, $fieldId);
            $this->conn->executeStatement(
                "INSERT INTO extra_field_values (field_id, item_id, field_value, created_at, updated_at)
                 VALUES (?, ?, '1', '2001-01-01 00:00:00', '2001-01-01 00:00:00')",
                [$fieldId, $cid]
            );
            $this->assertTrue(Tracking::minimumTimeAvailable(0, $cid));
            $this->assertSame(
                '2001-03-04 10:30:00',
                Tracking::get_last_connection_date_on_the_course($alice, ['real_id' => $cid], 0, false)
            );
            $this->assertFalse(Tracking::get_last_connection_date_on_the_course($this->bob->getId(), ['real_id' => $cid], 0, false));
        } finally {
            Container::getSettingsManager()->updateSetting('lp.lp_minimum_time', $previous);
        }
    }

    public function testCountCoursesPerStudentCountsDistinctCoursesAndIgnoresHrOnlyCourses(): void
    {
        $this->course->addUserAsStudent($this->alice);
        $this->course2->addUserAsStudent($this->alice);
        $this->getEntityManager()->flush();
        $this->conn->update(
            'course_rel_user',
            ['relation_type' => COURSE_RELATION_TYPE_RRHH],
            ['user_id' => $this->alice->getId(), 'c_id' => $this->course2->getId()]
        );
        // Only an HR relation for bob: no course for him.
        $this->course2->addUserAsStudent($this->bob);
        $this->getEntityManager()->flush();
        $this->conn->update(
            'course_rel_user',
            ['relation_type' => COURSE_RELATION_TYPE_RRHH],
            ['user_id' => $this->bob->getId(), 'c_id' => $this->course2->getId()]
        );
        $this->assertSame(0, Tracking::count_course_per_student($this->bob->getId(), false));

        // The same course in two sessions counts once per session row set (DISTINCT c_id within the table).
        foreach (['t3_session_a', 't3_session_b'] as $name) {
            $session = $this->createSession($name);
            $this->conn->insert('session_rel_course_rel_user', [
                'user_id' => $this->alice->getId(),
                'session_id' => $session->getId(),
                'c_id' => $this->course->getId(),
                'status' => 0,
                'visibility' => 1,
                'legal_agreement' => 0,
                'progress' => 0,
            ]);
        }
        $this->assertSame(2, Tracking::count_course_per_student($this->alice->getId(), true));
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
}
