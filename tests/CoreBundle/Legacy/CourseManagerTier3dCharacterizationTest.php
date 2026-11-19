<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\Tests\ChamiloTestTrait;
use ChamiloSession;
use CourseManager;
use Database;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * Characterization tests for functions of CourseManager (public/main/inc/lib/course.lib.php) that no other
 * CourseManager*CharacterizationTest reaches. Fixtures start with "cm3d_".
 */
final class CourseManagerTier3dCharacterizationTest extends KernelTestCase
{
    use ChamiloTestTrait;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        Container::setContainer($container);
        Container::setLegacyServices($container);
        $session = new Session(new MockArraySessionStorage());
        Container::setSession($session);
        $request = Request::create('/main/inc/lib/course.lib.php');
        $request->setSession($session);
        Container::setRequest($request);
    }

    private function conn(): Connection
    {
        return Database::getManager()->getConnection();
    }

    private function sub(User $user, Course $course, int $status = STUDENT, int $relationType = 0): void
    {
        $this->conn()->executeStatement(
            'INSERT INTO course_rel_user (user_id, c_id, relation_type, status, is_tutor, sort, user_course_cat, legal_agreement, progress)
             VALUES (?, ?, ?, ?, 0, 0, 0, 0, 0)',
            [$user->getId(), $course->getId(), $relationType, $status]
        );
    }

    private function loginAs(User $user): void
    {
        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
        self::getContainer()->get('security.token_storage')->setToken($token);
        ChamiloSession::write('_user', ['id' => $user->getId()]);
    }

    public function testGetUsersCountInCourseIgnoresSoftDeletedUsers(): void
    {
        $course = $this->createCourse('cm3d_cnt_a');
        $alive = $this->createUser('cm3d_cnt_alive');
        $gone = $this->createUser('cm3d_cnt_gone');
        $this->sub($alive, $course);
        $this->sub($gone, $course);
        $this->assertEquals(2, CourseManager::get_users_count_in_course($course->getCode()));

        $this->conn()->executeStatement('UPDATE user SET active = ? WHERE id = ?', [USER_SOFT_DELETED, $gone->getId()]);
        $this->assertEquals(1, CourseManager::get_users_count_in_course($course->getCode()));
    }

    private function sessionCourse(int $sessionId, Course $course, int $position = 0): void
    {
        $this->conn()->executeStatement(
            'INSERT INTO session_rel_course (session_id, c_id, position, nbr_users) VALUES (?, ?, ?, 0)',
            [$sessionId, $course->getId(), $position]
        );
    }

    private function sessionCourseUser(int $sessionId, Course $course, User $user, int $status): void
    {
        $this->conn()->executeStatement(
            'INSERT INTO session_rel_course_rel_user (session_id, c_id, user_id, status, visibility, legal_agreement, progress)
             VALUES (?, ?, ?, ?, 1, 0, 0)',
            [$sessionId, $course->getId(), $user->getId(), $status]
        );
    }

    public function testStudentListSkipsSoftDeletedUsersAndPaginates(): void
    {
        $course = $this->createCourse('cm3d_stu_a');
        $users = [];
        foreach (['a', 'b', 'c'] as $k) {
            $users[$k] = $this->createUser('cm3d_stu_'.$k);
            $this->sub($users[$k], $course);
        }
        $gone = $this->createUser('cm3d_stu_gone');
        $this->sub($gone, $course);
        $this->conn()->executeStatement('UPDATE user SET active = ? WHERE id = ?', [USER_SOFT_DELETED, $gone->getId()]);

        $all = CourseManager::get_student_list_from_course_code($course->getCode());
        $this->assertEqualsCanonicalizing(array_map(static fn ($u) => $u->getId(), $users), array_keys($all));
        $this->assertSame(3, CourseManager::get_student_list_from_course_code($course->getCode(), false, 0, null, null, true, 0, true));

        $this->assertCount(2, CourseManager::get_student_list_from_course_code($course->getCode(), false, 0, null, null, true, 0, false, 0, 2));
        $this->assertCount(1, CourseManager::get_student_list_from_course_code($course->getCode(), false, 0, null, null, true, 0, false, 2, 2));
    }

    public function testStudentListByDirectSessionAndDateRange(): void
    {
        $course = $this->createCourse('cm3d_stus_a');
        $one = $this->createSession('cm3d_stus_s1');
        $two = $this->createSession('cm3d_stus_s2');
        $u1 = $this->createUser('cm3d_stus_u1');
        $u2 = $this->createUser('cm3d_stus_u2');
        $gone = $this->createUser('cm3d_stus_gone');
        foreach ([$one, $two] as $sess) {
            $this->sessionCourse($sess->getId(), $course);
        }
        $this->sessionCourseUser($one->getId(), $course, $u1, 0);
        $this->sessionCourseUser($two->getId(), $course, $u2, 0);
        $this->sessionCourseUser($one->getId(), $course, $gone, 0);
        $this->conn()->executeStatement('UPDATE user SET active = ? WHERE id = ?', [USER_SOFT_DELETED, $gone->getId()]);
        $this->conn()->executeStatement(
            "UPDATE session SET access_start_date = '1990-01-01 00:00:00', access_end_date = '1990-12-31 00:00:00' WHERE id = ?",
            [$one->getId()]
        );

        $bySession = CourseManager::get_student_list_from_course_code($course->getCode(), true, $one->getId());
        $this->assertSame([$u1->getId()], array_keys($bySession), 'only the students of that session, soft-deleted skipped');

        $inRange = CourseManager::get_student_list_from_course_code($course->getCode(), true, $one->getId(), '1990-01-01 00:00:00', '1990-12-31 00:00:00');
        $this->assertSame([$u1->getId()], array_keys($inRange));
        $this->assertSame(
            1,
            CourseManager::get_student_list_from_course_code($course->getCode(), true, $one->getId(), '1990-01-01 00:00:00', '1990-12-31 00:00:00', true, 0, true)
        );

        $outOfRange = CourseManager::get_student_list_from_course_code($course->getCode(), true, $one->getId(), '1995-01-01 00:00:00', '1995-12-31 00:00:00');
        $this->assertSame([], $outOfRange, 'the session dates must fall inside the range');
    }

    public function testSubscribeCoursesToHrmManagerOnlyDeletesHrRelationsOfThatManager(): void
    {
        $a = $this->createCourse('cm3d_hrm_a');
        $b = $this->createCourse('cm3d_hrm_b');
        $hrm = $this->createUser('cm3d_hrm_user');
        $otherHrm = $this->createUser('cm3d_hrm_other');
        $this->sub($hrm, $a, STUDENT); // plain membership, must survive
        $this->sub($hrm, $a, DRH, COURSE_RELATION_TYPE_RRHH);
        $this->sub($otherHrm, $a, DRH, COURSE_RELATION_TYPE_RRHH); // another manager, must survive

        $this->assertSame(1, CourseManager::subscribeCoursesToHrmManager($hrm->getId(), [$b->getCode()]));

        $this->assertSame(
            1,
            (int) $this->conn()->fetchOne('SELECT COUNT(*) FROM course_rel_user WHERE user_id = ? AND c_id = ? AND relation_type = 0', [$hrm->getId(), $a->getId()])
        );
        $this->assertSame(
            0,
            (int) $this->conn()->fetchOne('SELECT COUNT(*) FROM course_rel_user WHERE user_id = ? AND c_id = ? AND relation_type = ?', [$hrm->getId(), $a->getId(), COURSE_RELATION_TYPE_RRHH])
        );
        $this->assertSame(
            1,
            (int) $this->conn()->fetchOne('SELECT COUNT(*) FROM course_rel_user WHERE user_id = ? AND c_id = ? AND relation_type = ?', [$otherHrm->getId(), $a->getId(), COURSE_RELATION_TYPE_RRHH])
        );
    }

    public function testGetCoursesFollowedByUserShowAllAssignedCoursesFiltersOnTheRelationType(): void
    {
        $byStatus = $this->createCourse('cm3d_fbu_status');
        $byRelation = $this->createCourse('cm3d_fbu_relation');
        $user = $this->createUser('cm3d_fbu_user');
        $this->sub($user, $byStatus, COURSEMANAGER);
        $this->sub($user, $byRelation, STUDENT, COURSE_RELATION_TYPE_COURSE_MANAGER);

        $default = CourseManager::getCoursesFollowedByUser($user->getId(), COURSEMANAGER);
        $this->assertSame([$byStatus->getCode()], array_keys($default), 'status teacher, whatever the relation type');

        $all = CourseManager::getCoursesFollowedByUser($user->getId(), COURSEMANAGER, null, null, null, null, false, null, 0, true);
        $this->assertSame([$byRelation->getCode()], array_keys($all), 'the relation type replaces the status condition');
    }

    public function testGetCoursesFollowedByUserInASessionIsOrderedByPositionAndPaginates(): void
    {
        $a = $this->createCourse('cm3d_fbo_a');
        $b = $this->createCourse('cm3d_fbo_b');
        $c = $this->createCourse('cm3d_fbo_c');
        $session = $this->createSession('cm3d_fbo_session');
        $user = $this->createUser('cm3d_fbo_user');
        foreach ([[$a, 2], [$b, 3], [$c, 1]] as [$course, $position]) {
            $this->sub($user, $course, COURSEMANAGER);
            $this->sessionCourse($session->getId(), $course, $position);
        }

        $rows = CourseManager::getCoursesFollowedByUser($user->getId(), COURSEMANAGER, null, null, null, null, false, null, $session->getId());
        $this->assertSame([$c->getCode(), $a->getCode(), $b->getCode()], array_keys($rows), 'session_rel_course.position ASC');

        $page = CourseManager::getCoursesFollowedByUser($user->getId(), COURSEMANAGER, 1, 1, null, null, false, null, $session->getId());
        $this->assertSame([$a->getCode()], array_keys($page), 'LIMIT from, limit');
    }
}
