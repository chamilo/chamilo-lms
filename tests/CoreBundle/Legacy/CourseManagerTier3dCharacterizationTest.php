<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\Usergroup;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CQuiz;
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

    public function testGenerateNiceNextCourseCodeCountsTheCodesStartingWithTheBase(): void
    {
        $a = $this->createCourse('cm3d_nxt');
        $this->createCourse('cm3d_nxtmore'); // starts with the same base, so it is counted too
        $base = CourseManager::generate_course_code($a->getCode());
        $count = (int) $this->conn()->fetchOne('SELECT COUNT(id) FROM course WHERE code LIKE ?', [$base.'%']);
        $this->assertGreaterThanOrEqual(2, $count);

        $this->assertSame($base.'_'.($count + 1), CourseManager::generate_nice_next_course_code($a->getCode()));
    }

    public function testGetCoursesFollowedByGroupAdminListsTheCoursesOfTheOtherGroupMembers(): void
    {
        $viaMembers = $this->createCourse('cm3d_gadm_members');
        $hrOnly = $this->createCourse('cm3d_gadm_hr');
        $outside = $this->createCourse('cm3d_gadm_outside');
        $ownCourse = $this->createCourse('cm3d_gadm_own');
        $admin = $this->createUser('cm3d_gadm_admin');
        $m1 = $this->createUser('cm3d_gadm_m1');
        $m2 = $this->createUser('cm3d_gadm_m2');
        $stranger = $this->createUser('cm3d_gadm_stranger');

        $group = (new Usergroup())
            ->setTitle('cm3d_gadm_group')
            ->setDescription('desc')
            ->setCreator($this->getUser('admin'))
            ->setGroupType(Usergroup::SOCIAL_CLASS)
            ->addAccessUrl($this->getAccessUrl())
        ;
        $this->getEntityManager()->persist($group);
        $this->getEntityManager()->flush();
        foreach ([[$admin, GROUP_USER_PERMISSION_ADMIN], [$m1, GROUP_USER_PERMISSION_READER], [$m2, GROUP_USER_PERMISSION_READER]] as [$user, $relation]) {
            $this->conn()->insert('usergroup_rel_user', ['usergroup_id' => $group->getId(), 'user_id' => $user->getId(), 'relation_type' => $relation]);
        }

        $this->sub($m1, $viaMembers);
        $this->sub($m2, $viaMembers); // same course twice: listed once
        $this->sub($m1, $hrOnly, DRH, COURSE_RELATION_TYPE_RRHH); // only relation_type 0 counts
        $this->sub($stranger, $outside);
        $this->sub($admin, $ownCourse); // the admin himself is not part of the list of users

        $rows = CourseManager::getCoursesFollowedByGroupAdmin($admin->getId());

        $this->assertCount(1, $rows);
        $this->assertSame((int) $viaMembers->getId(), (int) $rows[0]['id']);
        $this->assertSame('cm3d_gadm_members', $rows[0]['title']);
        $this->assertSame(['id', 'title'], array_keys($rows[0]));

        $this->assertSame([], CourseManager::getCoursesFollowedByGroupAdmin($stranger->getId()), 'not the admin of any group');
    }

    public function testGetCourseParamsForDisplayIgnoresTheRowsOfOtherUsers(): void
    {
        $course = $this->createCourse('cm3d_disp_b');
        $teacher = $this->createUser('cm3d_disp_other_teacher');
        $student = $this->createUser('cm3d_disp_mine');
        $this->sub($teacher, $course, COURSEMANAGER);
        $this->sub($student, $course, STUDENT);
        $course->setVisibility(Course::CLOSED);
        $this->getEntityManager()->flush();

        // Logged as the student: the teacher's row must not leak into the status used for the link.
        $this->loginAs($student);
        $params = CourseManager::getCourseParamsForDisplay($course->getId());
        $this->assertSame('', $params['link'], 'a student cannot open a closed course');
        $this->assertStringContainsString('item_closed', $params['title']);
    }

    public function testGetCourseParamsForDisplayLinksTheCourseAndItsSettingsForTeachersAndAdministrators(): void
    {
        $course = $this->createCourse('cm3d_disp_c');
        $teacher = $this->createUser('cm3d_disp_teacher');
        $admin = $this->createUser('cm3d_disp_admin', '', '', 'ROLE_ADMIN');
        $this->sub($teacher, $course, COURSEMANAGER);
        $this->sub($admin, $course, COURSEMANAGER);
        $id = (int) $course->getId();

        foreach ([$teacher, $admin] as $user) {
            $this->loginAs($user);
            $params = CourseManager::getCourseParamsForDisplay($id);
            $this->assertStringContainsString('course-settings/'.$id.'"', $params['right_actions'], 'the edit link names the course');
            $this->assertSame('/course/'.$id.'/home?sid=0', $params['link'], 'the title links to the course home, outside any session');
            $this->assertStringContainsString('href="/course/'.$id.'/home', $params['title']);
        }

        $this->loginAs($admin);
        $withDirs = CourseManager::getCourseParamsForDisplay($id, true);
        $this->assertStringContainsString('course-settings/'.$id.'"', $withDirs['right_actions'], 'also with the document quick-loader');
    }

    public function testGetCountExercisesFromOpenCourseCountsAQuizOnceAndSkipsSoftDeletedLinks(): void
    {
        $before = CourseManager::getCountExercisesFromOpenCourse();

        $teacher = $this->createUser('cm3d_quiz_teacher');
        $open = $this->createCourse('cm3d_quiz_open');
        $open->setVisibility(Course::OPEN_WORLD);
        $session = $this->createSession('cm3d_quiz_session');

        $em = $this->getEntityManager();
        // Two live links to the same course (base course and session): the quiz is counted once.
        $twice = (new CQuiz())->setTitle('cm3d_quiz_twice')->setParent($open)->setCreator($teacher)
            ->addCourseLink($open)->addCourseLink($open, $session)
        ;
        $gone = (new CQuiz())->setTitle('cm3d_quiz_gone')->setParent($open)->setCreator($teacher)->addCourseLink($open);
        $em->persist($twice);
        $em->persist($gone);
        $em->flush();
        $this->assertSame($before + 2, CourseManager::getCountExercisesFromOpenCourse());

        $this->conn()->executeStatement(
            'UPDATE resource_link SET deleted_at = NOW() WHERE resource_node_id = ?',
            [$gone->getResourceNode()->getId()]
        );
        $this->assertSame($before + 1, CourseManager::getCountExercisesFromOpenCourse(), 'soft-deleted links are skipped');
    }

    public function testWouldOperationExceedUsersPerCourseLimitCountsOnlyNewStudents(): void
    {
        $course = $this->createCourse('cm3d_limit_a');
        $s1 = $this->createUser('cm3d_limit_s1');
        $s2 = $this->createUser('cm3d_limit_s2');
        $new1 = $this->createUser('cm3d_limit_n1');
        $new2 = $this->createUser('cm3d_limit_n2');
        $hrm = $this->createUser('cm3d_limit_hrm');
        $viaSession = $this->createUser('cm3d_limit_sess');
        $this->sub($s1, $course, STUDENT);
        $this->sub($s2, $course, STUDENT);
        $this->sub($hrm, $course, DRH, COURSE_RELATION_TYPE_RRHH);
        $session = $this->createSession('cm3d_limit_session');
        $this->sessionCourse($session->getId(), $course);
        $this->sessionCourseUser($session->getId(), $course, $viaSession, 0);
        $cid = (int) $course->getId();

        $settings = self::getContainer()->get(SettingsManager::class);

        try {
            $settings->updateSetting('platform.hosting_limit_users_per_course', '4');
            $this->assertSame(4, CourseManager::getEffectiveUsersPerCourseLimit($cid), 'the test relies on the global fallback setting');
            $this->assertSame(3, CourseManager::countStudentsForUsersPerCourseLimit($cid));

            // 3 students now, limit 4: one new user fits, two do not.
            $this->assertFalse(CourseManager::wouldOperationExceedUsersPerCourseLimit($cid, [$new1->getId()]));
            $this->assertTrue(CourseManager::wouldOperationExceedUsersPerCourseLimit($cid, [$new1->getId(), $new2->getId()]));
            // Users that already have a (non-HR) relation in the course or session do not count as new.
            $this->assertFalse(CourseManager::wouldOperationExceedUsersPerCourseLimit($cid, [$s1->getId(), $s2->getId(), $viaSession->getId(), $new1->getId()]));
            // An HR relation is not a subscription: the HR manager is a new user.
            $this->assertTrue(CourseManager::wouldOperationExceedUsersPerCourseLimit($cid, [$hrm->getId(), $new1->getId()]));
            // Duplicates, zero and negative ids are ignored.
            $this->assertFalse(CourseManager::wouldOperationExceedUsersPerCourseLimit($cid, [$new1->getId(), $new1->getId(), 0, -5]));
            $this->assertFalse(CourseManager::wouldOperationExceedUsersPerCourseLimit($cid, []));
        } finally {
            $settings->updateSetting('platform.hosting_limit_users_per_course', '0');
        }
        $settings->updateSetting('platform.hosting_limit_users_per_course', '0');
        $this->assertFalse(CourseManager::wouldOperationExceedUsersPerCourseLimit($cid, [$new1->getId(), $new2->getId()]), 'zero means unlimited');
    }

    public function testGetAccessCourseDateRangeIsInclusiveAtMidnightOfTheUtcDay(): void
    {
        $course = $this->createCourse('cm3d_acc_a');
        $user = $this->createUser('cm3d_acc_user');
        // The range is converted to UTC first, so the edge day is computed the way the function does it.
        $edge = api_get_utc_datetime('2031-03-15', false, true)->format('Y-m-d');
        foreach (['2031-01-01 00:00:00', $edge.' 00:00:00', '2031-06-01 00:00:00'] as $date) {
            $this->conn()->executeStatement(
                'INSERT INTO track_e_course_access (user_id, c_id, login_course_date, counter, session_id, user_ip) VALUES (?, ?, ?, 1, 0, ?)',
                [$user->getId(), $course->getId(), $date, '127.0.0.1']
            );
        }
        $cid = (int) $course->getId();
        $uid = (int) $user->getId();
        $days = fn (array $rows) => array_column($rows, 'login_course_date');

        $this->assertSame([$edge, '2031-06-01'], $days(CourseManager::getAccessCourse($cid, 2, $uid, '2031-03-15')), 'start is inclusive');
        $this->assertSame(['2031-01-01', $edge], $days(CourseManager::getAccessCourse($cid, 2, $uid, null, '2031-03-15')), 'end is inclusive');
        $this->assertSame([$edge], $days(CourseManager::getAccessCourse($cid, 2, $uid, '2031-03-15', '2031-03-15')));
    }

    public function testCountStudentsForUsersPerCourseLimitCountsOnlyStudentsOfBothSources(): void
    {
        $course = $this->createCourse('cm3d_cnt_lim');
        $session = $this->createSession('cm3d_cnt_lim_session');
        $this->sessionCourse($session->getId(), $course);
        foreach (['s1', 's2'] as $k) {
            $this->sub($this->createUser('cm3d_lim_'.$k), $course, STUDENT);
        }
        $this->sub($this->createUser('cm3d_lim_teacher'), $course, COURSEMANAGER);
        foreach (['x1', 'x2'] as $k) {
            $this->sessionCourseUser($session->getId(), $course, $this->createUser('cm3d_lim_'.$k), 0);
        }
        $this->sessionCourseUser($session->getId(), $course, $this->createUser('cm3d_lim_coach'), 2);

        $this->assertSame(4, CourseManager::countStudentsForUsersPerCourseLimit((int) $course->getId()), 'direct students + session students, no teacher, no coach');
    }
}
