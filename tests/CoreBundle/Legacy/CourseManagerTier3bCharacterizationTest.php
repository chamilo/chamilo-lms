<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\Tests\ChamiloTestTrait;
use CourseManager;
use Database;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Mime\Email;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

use const E_WARNING;

/**
 * Characterization tests for the functions of CourseManager (public/main/inc/lib/course.lib.php) that
 * CourseManagerCharacterizationTest and CourseManagerTier3CharacterizationTest do not reach (subscribe, delete, tutors, lists). Same conventions: pin today's behaviour, fixtures start with "cm3_".
 */
final class CourseManagerTier3bCharacterizationTest extends KernelTestCase
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

    /**
     * Two tests switch the foreign keys off to insert orphan rows. The session variable outlives the DAMA rollback, so
     * leaving it at 0 silently disables every ON DELETE CASCADE of the tests that run afterwards.
     */
    protected function tearDown(): void
    {
        $this->conn()->executeStatement('SET FOREIGN_KEY_CHECKS = 1');
        parent::tearDown();
    }

    private function conn(): Connection
    {
        return Database::getManager()->getConnection();
    }

    private function sub(User $user, Course $course, int $status = STUDENT, int $relationType = 0, int $sort = 0, int $cat = 0): void
    {
        $this->conn()->executeStatement(
            'INSERT INTO course_rel_user (user_id, c_id, relation_type, status, is_tutor, sort, user_course_cat, legal_agreement, progress)
             VALUES (?, ?, ?, ?, 0, ?, ?, 0, 0)',
            [$user->getId(), $course->getId(), $relationType, $status, $sort, $cat]
        );
    }

    private function sessionCourse(int $sessionId, Course $course): void
    {
        $this->conn()->executeStatement(
            'INSERT INTO session_rel_course (session_id, c_id, position, nbr_users) VALUES (?, ?, 0, 0)',
            [$sessionId, $course->getId()]
        );
    }

    private function sessionCourseUser(int $sessionId, Course $course, User $user, int $status = 0): void
    {
        $this->conn()->executeStatement(
            'INSERT INTO session_rel_course_rel_user (session_id, c_id, user_id, status, visibility, legal_agreement, progress)
             VALUES (?, ?, ?, ?, 1, 0, 0)',
            [$sessionId, $course->getId(), $user->getId(), $status]
        );
    }

    /**
     * Gives the course's resource node a link (the fixture course has none) and returns its id.
     */
    private function resourceLinkOf(Course $course): int
    {
        $node = $course->getResourceNode();
        $this->assertNotNull($node, 'the fixture course has a resource node');
        $this->conn()->executeStatement(
            'INSERT INTO resource_link (resource_node_id, c_id, visibility, display_order, resource_type_group, created_at, updated_at)
             VALUES (?, ?, 2, 0, 0, NOW(), NOW())',
            [$node->getId(), $course->getId()]
        );
        $id = (int) $this->conn()->lastInsertId();
        Database::getManager()->refresh($node);

        return $id;
    }

    private function loginAs(User $user): void
    {
        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
        self::getContainer()->get('security.token_storage')->setToken($token);
    }

    private function rows(string $table, string $where, array $params): int
    {
        return (int) $this->conn()->fetchOne("SELECT COUNT(*) FROM $table WHERE $where", $params);
    }

    // ---------------------------------------------------------------- subscribeUser

    public function testSubscribeUserIgnoresAnHrManagerRelationWhenLookingForADuplicate(): void
    {
        $course = $this->createCourse('cm3b_subhr_a');
        $user = $this->createUser('cm3b_subhr_user');
        $this->sub($user, $course, STUDENT, COURSE_RELATION_TYPE_RRHH);

        $this->assertTrue(
            CourseManager::subscribeUser($user->getId(), $course->getId(), STUDENT, 0, 0, false, ['flash' => false, 'emails' => false]),
            'an HR-manager link is not a registration as a learner'
        );
        $this->assertSame(
            2,
            (int) $this->conn()->fetchOne('SELECT COUNT(*) FROM course_rel_user WHERE user_id = ? AND c_id = ?', [$user->getId(), $course->getId()])
        );
    }

    // ---------------------------------------------------------------- unsubscribe_user

    public function testUnsubscribeUserClearsGroupForumAndMailQueueRowsOfThatCourseOnly(): void
    {
        $course = $this->createCourse('cm3b_unsub_a');
        $other = $this->createCourse('cm3b_unsub_b');
        $user = $this->createUser('cm3b_unsub_user');
        $keep = $this->createUser('cm3b_unsub_keep');
        $this->sub($user, $course);
        $this->sub($keep, $course);
        $uid = (int) $user->getId();
        $kid = (int) $keep->getId();
        $cid = (int) $course->getId();
        $oid = (int) $other->getId();

        // No group row is needed for what is pinned here, only the user/course columns.
        $this->conn()->executeStatement('SET FOREIGN_KEY_CHECKS = 0');
        foreach ([[$uid, $cid], [$uid, $oid], [$kid, $cid]] as [$u, $c]) {
            $this->conn()->executeStatement('INSERT INTO c_group_rel_user (user_id, group_id, c_id, status, role) VALUES (?, 1, ?, 0, \'\')', [$u, $c]);
            $this->conn()->executeStatement('INSERT INTO c_group_rel_tutor (user_id, group_id, c_id) VALUES (?, 1, ?)', [$u, $c]);
            $this->conn()->executeStatement('INSERT INTO c_forum_notification (c_id, user_id, forum_id, thread_id, post_id) VALUES (?, ?, 1, 1, 1)', [$c, $u]);
            $this->conn()->executeStatement('INSERT INTO c_forum_mailcue (c_id, thread_id, user_id, post_id) VALUES (?, 1, ?, 1)', [$c, $u]);
        }

        CourseManager::unsubscribe_user($uid, $course->getCode());

        foreach (['c_group_rel_user', 'c_group_rel_tutor', 'c_forum_notification', 'c_forum_mailcue'] as $table) {
            $this->assertSame(0, $this->rows($table, 'user_id = ? AND c_id = ?', [$uid, $cid]), "$table: the user's rows of that course are gone");
            $this->assertSame(1, $this->rows($table, 'user_id = ? AND c_id = ?', [$uid, $oid]), "$table: same user, other course is kept");
            $this->assertSame(1, $this->rows($table, 'user_id = ? AND c_id = ?', [$kid, $cid]), "$table: other user, same course is kept");
        }
    }

    public function testUnsubscribeUserInASessionRemovesOnlyThatCoursesSessionLink(): void
    {
        $course = $this->createCourse('cm3b_unsubs_a');
        $second = $this->createCourse('cm3b_unsubs_b');
        $session = $this->createSession('cm3b_unsubs_session');
        $user = $this->createUser('cm3b_unsubs_user');
        $sid = (int) $session->getId();
        $uid = (int) $user->getId();
        $this->sessionCourse($sid, $course);
        $this->sessionCourse($sid, $second);
        $this->sessionCourseUser($sid, $course, $user);
        $this->sessionCourseUser($sid, $second, $user);
        $this->conn()->executeStatement('INSERT INTO session_rel_user (session_id, user_id, relation_type, duration, registered_at) VALUES (?, ?, 0, 0, NOW())', [$sid, $uid]);

        CourseManager::unsubscribe_user($uid, $course->getCode(), $sid);
        $this->assertSame(0, $this->rows('session_rel_course_rel_user', 'session_id = ? AND user_id = ? AND c_id = ?', [$sid, $uid, $course->getId()]));
        $this->assertSame(1, $this->rows('session_rel_course_rel_user', 'session_id = ? AND user_id = ? AND c_id = ?', [$sid, $uid, $second->getId()]));
        $this->assertSame(1, $this->rows('session_rel_user', 'session_id = ? AND user_id = ?', [$sid, $uid]), 'session registration kept');

        CourseManager::unsubscribe_user($uid, $second->getCode(), $sid);
        $this->assertSame(0, $this->rows('session_rel_course_rel_user', 'session_id = ? AND user_id = ?', [$sid, $uid]));
        // Once the user has no course left in the session, the session registration goes too
        // (SessionManager::unsubscribe_user_from_session() takes ($session_id, $user_id)).
        $this->assertSame(0, $this->rows('session_rel_user', 'session_id = ? AND user_id = ?', [$sid, $uid]));
    }

    // ---------------------------------------------------------------- setCategoryByCode

    public function testSetCategoryByCodeReplacesTheCategoryRelationOfThatCourseOnly(): void
    {
        // import_csv.php applies the CourseCategory column of its CSV through this relation: the course table has no category column.
        $course = $this->createCourse('cm3b_cat_a');
        $other = $this->createCourse('cm3b_cat_b');
        $cid = (int) $course->getId();
        $oid = (int) $other->getId();
        foreach (['CM3B_OLD', 'CM3B_NEW'] as $code) {
            $this->conn()->executeStatement('INSERT INTO course_category (code, title, tree_pos, children_count) VALUES (?, ?, 0, 0)', [$code, $code]);
        }
        $oldId = (int) $this->conn()->fetchOne('SELECT id FROM course_category WHERE code = ?', ['CM3B_OLD']);
        $newId = (int) $this->conn()->fetchOne('SELECT id FROM course_category WHERE code = ?', ['CM3B_NEW']);
        $this->conn()->executeStatement('INSERT INTO course_rel_category (course_id, course_category_id) VALUES (?, ?)', [$cid, $oldId]);
        $this->conn()->executeStatement('INSERT INTO course_rel_category (course_id, course_category_id) VALUES (?, ?)', [$oid, $oldId]);
        Database::getManager()->clear();

        $this->assertFalse(CourseManager::setCategoryByCode($cid, ''), 'an empty code changes nothing');
        $this->assertFalse(CourseManager::setCategoryByCode($cid, 'CM3B_NO_SUCH'), 'an unknown code changes nothing');
        $this->assertSame([$oldId], $this->categoryIdsOf($cid));

        $this->assertTrue(CourseManager::setCategoryByCode($cid, 'CM3B_NEW'));
        $this->assertSame([$newId], $this->categoryIdsOf($cid), 'the previous category is replaced');
        $this->assertSame([$oldId], $this->categoryIdsOf($oid), 'another course is untouched');
    }

    /**
     * @return list<int>
     */
    private function categoryIdsOf(int $courseId): array
    {
        return array_map('intval', $this->conn()->fetchFirstColumn('SELECT course_category_id FROM course_rel_category WHERE course_id = ? ORDER BY 1', [$courseId]));
    }

    // ---------------------------------------------------------------- delete_course

    public function testDeleteCourseClearsItsRowsInEveryTableItNamesAndLeavesOtherCoursesAlone(): void
    {
        $course = $this->createCourse('cm3b_del_a');
        $other = $this->createCourse('cm3b_del_b');
        $user = $this->createUser('cm3b_del_user');
        $session = $this->createSession('cm3b_del_session');
        $cid = (int) $course->getId();
        $oid = (int) $other->getId();
        $uid = (int) $user->getId();
        $sid = (int) $session->getId();
        $linkId = $this->resourceLinkOf($course);
        $otherLinkId = $this->resourceLinkOf($other);
        $this->assertNotSame(0, $linkId, 'the fixture course has a resource link');

        $db = $this->conn();
        $db->executeStatement('SET FOREIGN_KEY_CHECKS = 0');
        foreach ([$cid, $oid] as $c) {
            $linkOfCourse = $c === $cid ? $linkId : $otherLinkId;
            $this->sub($user, $c === $cid ? $course : $other);
            $db->executeStatement('INSERT INTO session_rel_course (session_id, c_id, position, nbr_users) VALUES (?, ?, 0, 0)', [$sid, $c]);
            $db->executeStatement('INSERT INTO session_rel_course_rel_user (session_id, c_id, user_id, status, visibility, legal_agreement, progress) VALUES (?, ?, ?, 0, 1, 0, 0)', [$sid, $c, $uid]);
            $db->executeStatement("INSERT INTO track_e_hotpotatoes (title, exe_date, c_id, score, max_score) VALUES ('t', NOW(), ?, 1, 1)", [$c]);
            $db->executeStatement("INSERT INTO track_e_access (access_date, c_id, session_id, user_ip) VALUES (NOW(), ?, 0, '127.0.0.1')", [$c]);
            $db->executeStatement('INSERT INTO track_e_lastaccess (access_date, c_id) VALUES (NOW(), ?)', [$c]);
            $db->executeStatement("INSERT INTO track_e_course_access (c_id, login_course_date, counter, session_id, user_ip) VALUES (?, NOW(), 1, 0, '127.0.0.1')", [$c]);
            $db->executeStatement("INSERT INTO track_e_online (login_user_id, login_date, user_ip, c_id, session_id, access_url_id) VALUES (?, NOW(), '127.0.0.1', ?, 0, 1)", [$c === $cid ? $uid : $uid + 1000000, $c]);
            $db->executeStatement('INSERT INTO track_e_downloads (down_date, resource_link_id) VALUES (NOW(), ?)', [$linkOfCourse]);
            $db->executeStatement('INSERT INTO track_e_links (links_date, c_id, links_link_id, session_id) VALUES (NOW(), ?, 1, 0)', [$c]);
            $db->executeStatement('INSERT INTO track_e_uploads (upload_date, upload_work_id, session_id, c_id) VALUES (NOW(), 1, 0, ?)', [$c]);
            $db->executeStatement("INSERT INTO ticket_ticket (code, subject, personal_email, total_messages, sys_insert_user_id, sys_insert_datetime, course_id) VALUES (CONCAT('cm3b', ?), 's', 'a@b.c', 0, ?, NOW(), ?)", [$c, $uid, $c]);
            $db->executeStatement('INSERT INTO usergroup_rel_course (usergroup_id, course_id) VALUES (1, ?)', [$c]);
            $db->executeStatement("INSERT INTO skill_rel_user (user_id, skill_id, acquired_skill_at, validation_status, argumentation, argumentation_author_id, course_id, session_id) VALUES (?, 1, NOW(), 0, 'orig', ?, ?, ?)", [$uid, $uid, $c, $sid]);
        }

        $this->assertTrue(CourseManager::delete_course($course->getCode()));

        $gone = [
            'course_rel_user' => 'c_id', 'session_rel_course' => 'c_id', 'session_rel_course_rel_user' => 'c_id',
            'track_e_hotpotatoes' => 'c_id', 'track_e_access' => 'c_id', 'track_e_lastaccess' => 'c_id',
            'track_e_course_access' => 'c_id', 'track_e_online' => 'c_id', 'track_e_links' => 'c_id',
            'track_e_uploads' => 'c_id', 'usergroup_rel_course' => 'course_id',
        ];
        foreach ($gone as $table => $column) {
            $this->assertSame(0, $this->rows($table, "$column = ?", [$cid]), "$table: rows of the deleted course are gone");
            $this->assertSame(1, $this->rows($table, "$column = ?", [$oid]), "$table: rows of the other course are kept");
        }
        $this->assertSame(0, $this->rows('track_e_downloads', 'resource_link_id = ?', [$linkId]));
        $this->assertSame(1, $this->rows('track_e_downloads', 'resource_link_id = ?', [$otherLinkId]));

        // The ticket survives, detached from the course.
        $this->assertSame(1, $this->rows('ticket_ticket', 'code = ? AND course_id IS NULL', ['cm3b'.$cid]));
        $this->assertSame(1, $this->rows('ticket_ticket', 'code = ? AND course_id = ?', ['cm3b'.$oid, $oid]));

        // The skill stays, detached from course and session, with an explanation.
        $skill = $db->fetchAssociative('SELECT course_id, session_id, argumentation FROM skill_rel_user WHERE user_id = ? AND argumentation <> ?', [$uid, 'orig']);
        $this->assertNotFalse($skill);
        $this->assertNull($skill['course_id']);
        $this->assertNull($skill['session_id']);
        $this->assertStringContainsString($course->getCode(), $skill['argumentation']);
        $this->assertSame(1, $this->rows('skill_rel_user', 'course_id = ? AND argumentation = ?', [$oid, 'orig']));
    }

    // ---------------------------------------------------------------- email_to_tutor

    public function testEmailToTutorMailsTheTeachersOfThatCourseOrTheTutors(): void
    {
        $this->conn()->executeStatement("UPDATE settings SET selected_value = 'noreply@example.org' WHERE variable = 'mailer_from_email'");
        $course = $this->createCourse('cm3b_mail_a');
        $other = $this->createCourse('cm3b_mail_b');
        $teacher = $this->createUser('cm3b_mail_teacher', '', 'cm3b_mail_teacher@example.org');
        $tutor = $this->createUser('cm3b_mail_tutor', '', 'cm3b_mail_tutor@example.org');
        $otherTeacher = $this->createUser('cm3b_mail_otherteacher', '', 'cm3b_mail_otherteacher@example.org');
        $student = $this->createUser('cm3b_mail_student', '', 'cm3b_mail_student@example.org');
        $this->sub($teacher, $course, COURSEMANAGER);
        $this->sub($tutor, $course, STUDENT);
        $this->conn()->executeStatement('UPDATE course_rel_user SET is_tutor = 1 WHERE user_id = ?', [$tutor->getId()]);
        $this->sub($otherTeacher, $other, COURSEMANAGER);
        $this->sub($student, $course, STUDENT);

        CourseManager::email_to_tutor($student->getId(), $course->getId(), false);
        $this->assertEmailCount(1);
        $first = self::getMailerMessage(0);
        $this->assertInstanceOf(Email::class, $first);
        $this->assertSame('cm3b_mail_teacher@example.org', $first->getTo()[0]->getAddress(), 'status 1 (teachers) of that course only');

        CourseManager::email_to_tutor($student->getId(), $course->getId(), true);
        $this->assertEmailCount(2);
        $second = self::getMailerMessage(1);
        $this->assertInstanceOf(Email::class, $second);
        $this->assertSame('cm3b_mail_tutor@example.org', $second->getTo()[0]->getAddress(), 'is_tutor = 1 rows instead');
    }

    // ---------------------------------------------------------------- get_courses_list_by_user_id

    public function testCoursesListByUserIdIsOrderedByTitleAndScopedToTheUser(): void
    {
        $b = $this->createCourse('cm3b_lst_b');
        $a = $this->createCourse('cm3b_lst_a');
        $notMine = $this->createCourse('cm3b_lst_c');
        $user = $this->createUser('cm3b_lst_user');
        $stranger = $this->createUser('cm3b_lst_stranger');
        $this->sub($user, $b);
        $this->sub($user, $a, STUDENT, COURSE_RELATION_TYPE_RRHH);
        $this->sub($stranger, $notMine);

        $list = CourseManager::get_courses_list_by_user_id($user->getId());

        $this->assertSame(['cm3b_lst_a', 'cm3b_lst_b'], array_column($list, 'title'), 'ordered by title, HR-manager link included, strangers\' courses left out');
        $this->assertSame([$a->getCode(), $b->getCode()], array_column($list, 'code'));
        $this->assertSame([(int) $a->getId(), (int) $b->getId()], array_map('intval', array_column($list, 'real_id')));
        $this->assertSame(['code', 'real_id', 'title'], array_keys($list[0]));
        $this->assertSame([], CourseManager::get_courses_list_by_user_id(999999999));
    }

    public function testCoursesListByUserIdSkipsTheGivenCourseIds(): void
    {
        $a = $this->createCourse('cm3b_skip_a');
        $b = $this->createCourse('cm3b_skip_b');
        $user = $this->createUser('cm3b_skip_user');
        $this->sub($user, $a);
        $this->sub($user, $b);

        $list = CourseManager::get_courses_list_by_user_id($user->getId(), false, false, true, [$a->getId()]);

        $this->assertSame(['cm3b_skip_b'], array_column($list, 'title'));
    }

    public function testCoursesListByUserIdSeparatesStickyCoursesAndCanIgnoreThem(): void
    {
        $sticky = $this->createCourse('cm3b_stk_a');
        $normal = $this->createCourse('cm3b_stk_b');
        $subscriber = $this->createUser('cm3b_stk_sub');
        $user = $this->createUser('cm3b_stk_user');
        $this->sub($subscriber, $sticky);
        $this->sub($user, $normal);
        $this->sub($user, $sticky);
        $this->conn()->executeStatement('UPDATE course SET sticky = 1 WHERE id = ?', [$sticky->getId()]);

        $byTitle = [];
        foreach (CourseManager::get_courses_list_by_user_id($user->getId()) as $row) {
            $byTitle[$row['title']] = $row;
        }
        $this->assertSame(1, $byTitle['cm3b_stk_a']['special_course'], 'a sticky course comes first, flagged');
        $this->assertArrayNotHasKey('special_course', $byTitle['cm3b_stk_b']);
        $this->assertSame(1, \count(array_filter(CourseManager::get_courses_list_by_user_id($user->getId()), static fn ($r) => 'cm3b_stk_a' === $r['title'])), 'listed once');

        $this->assertSame(
            ['cm3b_stk_a', 'cm3b_stk_b'],
            array_column(CourseManager::get_courses_list_by_user_id($user->getId(), false, false, false), 'title'),
            'without special-course handling it is a plain subscription'
        );

        // A sticky course is shown to a user even if he is not subscribed, as long as somebody is.
        $this->assertContains('cm3b_stk_a', array_column(CourseManager::get_courses_list_by_user_id($this->createUser('cm3b_stk_nobody')->getId()), 'title'));
    }

    public function testCoursesListByUserIdAdminGetsEveryCourseOfTheUrl(): void
    {
        $course = $this->createCourse('cm3b_adm_a');
        $admin = $this->getUser('admin');

        $without = array_column(CourseManager::get_courses_list_by_user_id($admin->getId()), 'title');
        $with = array_column(CourseManager::get_courses_list_by_user_id($admin->getId(), false, true), 'title');

        $this->assertNotContains('cm3b_adm_a', $without);
        $this->assertContains('cm3b_adm_a', $with);
        $this->assertSame($with, array_values(array_unique($with)));
    }

    public function testCoursesListByUserIdAddsSessionCoursesOnlyWhenAsked(): void
    {
        $own = $this->createCourse('cm3b_ses_own');
        $viaSession = $this->createCourse('cm3b_ses_via');
        $session = $this->createSession('cm3b_ses_session');
        $user = $this->createUser('cm3b_ses_user');
        $this->sub($user, $own);
        $this->sessionCourse((int) $session->getId(), $own);
        $this->sessionCourse((int) $session->getId(), $viaSession);
        $this->sessionCourseUser((int) $session->getId(), $own, $user);
        $this->sessionCourseUser((int) $session->getId(), $viaSession, $user);

        $this->assertSame(['cm3b_ses_own'], array_column(CourseManager::get_courses_list_by_user_id($user->getId()), 'title'));

        $list = CourseManager::get_courses_list_by_user_id($user->getId(), true);
        $this->assertSame([$own->getCode(), $viaSession->getCode()], array_column($list, 'code'), 'a course already listed is not repeated');
        $this->assertSame(['code', 'real_id', 'session_id', 'session_name'], array_keys($list[1]));
        $this->assertSame('cm3b_ses_session', $list[1]['session_name']);

        $different = CourseManager::get_courses_list_by_user_id($user->getId(), true, false, true, [], true, true);
        $this->assertCount(3, $different, 'with the different-key flag the session row of an own course is kept too');
        $this->assertCount(0, CourseManager::get_courses_list_by_user_id($user->getId(), true, false, true, [$own->getId(), $viaSession->getId()]));
    }

    // ---------------------------------------------------------------- return_hot_courses / returnSpecialCourses

    private function courseAccess(Course $course, string $date, int $times = 1): void
    {
        for ($i = 0; $i < $times; ++$i) {
            $this->conn()->executeStatement(
                "INSERT INTO track_e_course_access (c_id, login_course_date, counter, session_id, user_ip, user_id) VALUES (?, ?, 1, 0, '127.0.0.1', ?)",
                [$course->getId(), $date, $this->getUser('admin')->getId()]
            );
        }
    }

    public function testReturnHotCoursesRanksByAccessesInTheWindowAndSkipsClosedAndHiddenCourses(): void
    {
        $user = $this->createUser('cm3b_hot_user');
        $this->loginAs($user);
        $recent = gmdate('Y-m-d H:i:s', time() - 3600);
        $old = gmdate('Y-m-d H:i:s', time() - 40 * 86400);

        $busy = $this->createCourse('cm3b_hot_busy');
        $quiet = $this->createCourse('cm3b_hot_quiet');
        $stale = $this->createCourse('cm3b_hot_stale');
        $edge = $this->createCourse('cm3b_hot_edge');
        $closed = $this->createCourse('cm3b_hot_closed');
        $hidden = $this->createCourse('cm3b_hot_hidden');
        $this->conn()->executeStatement('UPDATE course SET visibility = ? WHERE id = ?', [Course::CLOSED, $closed->getId()]);
        $this->conn()->executeStatement('UPDATE course SET visibility = ? WHERE id = ?', [Course::HIDDEN, $hidden->getId()]);
        $this->courseAccess($busy, $recent, 3);
        $this->courseAccess($quiet, $recent, 1);
        $this->courseAccess($stale, $old, 5);
        $this->courseAccess($edge, gmdate('Y-m-d H:i:s', time() - 30 * 86400 - 43200), 2);
        $this->courseAccess($closed, $recent, 9);
        $this->courseAccess($hidden, $recent, 9);

        $titles = array_column(CourseManager::return_hot_courses(30, 500), 'title');
        $mine = array_values(array_filter($titles, static fn ($t) => str_starts_with($t, 'cm3b_hot_')));
        $this->assertSame(['cm3b_hot_busy', 'cm3b_hot_quiet'], $mine, 'most accessed first; outside the window (half a day beyond 30 days too), closed and hidden left out');

        $wider = array_values(array_filter(array_column(CourseManager::return_hot_courses(60, 500), 'title'), static fn ($t) => str_starts_with($t, 'cm3b_hot_')));
        $this->assertSame(['cm3b_hot_stale', 'cm3b_hot_busy', 'cm3b_hot_edge', 'cm3b_hot_quiet'], $wider, 'a wider window brings the stale course in, ranked by its 5 accesses');

        $this->assertCount(1, CourseManager::return_hot_courses(60, 1), 'the limit applies');
        $this->assertSame('cm3b_hot_stale', CourseManager::return_hot_courses(60, 1)[0]['title'] ?? null, 'only if nothing else in the test database is busier');
    }

    public function testReturnSpecialCoursesListsStickyCoursesOnceAndSkipsHiddenOnes(): void
    {
        $user = $this->createUser('cm3b_spc_user');
        $this->loginAs($user);
        $sticky = $this->createCourse('cm3b_spc_a');
        $hiddenSticky = $this->createCourse('cm3b_spc_hidden');
        $plain = $this->createCourse('cm3b_spc_plain');
        $this->conn()->executeStatement('UPDATE course SET sticky = 1 WHERE id IN (?, ?)', [$sticky->getId(), $hiddenSticky->getId()]);
        // Through the entity: the legacy course info is read from the managed Course, which a raw UPDATE leaves stale.
        $hiddenSticky->setVisibility(Course::HIDDEN);
        Database::getManager()->flush();
        // Two subscribers: the GROUP BY code keeps one row per course.
        $this->sub($user, $sticky, COURSEMANAGER);
        $this->sub($this->createUser('cm3b_spc_other'), $sticky);
        $this->sub($user, $plain);

        // A course without category makes returnSpecialCourses() read undefined "categoryName"/"categoryCode" keys.
        set_error_handler(static fn (int $no, string $str): bool => str_contains($str, 'Undefined array key'), E_WARNING);
        $mine = array_values(array_filter(
            CourseManager::returnSpecialCourses($user->getId()),
            static fn ($row) => str_starts_with((string) $row['title'], 'cm3b_spc_')
        ));

        $this->assertSame(['cm3b_spc_a'], array_column($mine, 'title'));
        $this->assertSame((int) $sticky->getId(), (int) $mine[0]['c_id']);
        $this->assertTrue($mine[0]['is_special_course']);
        $this->assertSame(COURSEMANAGER, (int) $mine[0]['status'], 'status comes from the user\'s own subscription');

        $stranger = $this->createUser('cm3b_spc_stranger');
        $forStranger = array_values(array_filter(
            CourseManager::returnSpecialCourses($stranger->getId()),
            static fn ($row) => 'cm3b_spc_a' === $row['title']
        ));
        restore_error_handler();
        $this->assertSame(STUDENT, (int) $forStranger[0]['status'], 'not subscribed: shown as a student');
    }

    // ---------------------------------------------------------------- updateTeachers

    private function teacherRow(Course $course, User $user): array|false
    {
        return $this->conn()->fetchAssociative(
            'SELECT status, is_tutor, sort, relation_type, user_course_cat, progress FROM course_rel_user WHERE c_id = ? AND user_id = ?',
            [$course->getId(), $user->getId()]
        );
    }

    public function testUpdateTeachersReplacesOnlyTheTeacherRelationsOfThatCourse(): void
    {
        $course = $this->createCourse('cm3b_upt_a');
        $other = $this->createCourse('cm3b_upt_b');
        $keep = $this->createUser('cm3b_upt_keep');
        $drop = $this->createUser('cm3b_upt_drop');
        $hrm = $this->createUser('cm3b_upt_hrm');
        $student = $this->createUser('cm3b_upt_student');
        $fresh = $this->createUser('cm3b_upt_fresh');
        $bystander = $this->createUser('cm3b_upt_bystander');
        $this->sub($keep, $course, COURSEMANAGER);
        $this->sub($drop, $course, COURSEMANAGER);
        $this->sub($hrm, $course, COURSEMANAGER, COURSE_RELATION_TYPE_RRHH);
        $this->sub($student, $course, STUDENT);
        $this->sub($drop, $other, COURSEMANAGER);
        $this->sub($bystander, $course, STUDENT);

        CourseManager::updateTeachers(
            ['real_id' => $course->getId(), 'code' => $course->getCode()],
            [$keep->getId(), $student->getId(), $fresh->getId()],
            true,
            false,
            false,
            [$fresh->getId() => [$course->getCode() => ['user_course_cat' => 7]]]
        );

        $this->assertFalse($this->teacherRow($course, $drop), 'a teacher not in the list is removed');
        $this->assertNotFalse($this->teacherRow($course, $hrm), 'an HR-manager relation is not a teacher relation');
        $this->assertNotFalse($this->teacherRow($other, $drop), 'other courses are untouched');
        $this->assertSame(STUDENT, (int) $this->teacherRow($course, $bystander)['status'], 'a learner who is not listed stays a learner');
        $this->assertSame(COURSEMANAGER, (int) $this->teacherRow($course, $keep)['status']);
        $this->assertSame(COURSEMANAGER, (int) $this->teacherRow($course, $student)['status'], 'a listed learner becomes a teacher');

        $new = $this->teacherRow($course, $fresh);
        $this->assertSame(
            ['status' => 1, 'is_tutor' => 0, 'sort' => 0, 'relation_type' => 0, 'user_course_cat' => 7, 'progress' => 0],
            array_map('intval', $new),
            'a new teacher row; its category is recovered from the backup'
        );
    }

    public function testUpdateTeachersCanKeepTheTeachersNotInTheListAndWithoutBackupUsesCategoryZero(): void
    {
        $course = $this->createCourse('cm3b_upk_a');
        $old = $this->createUser('cm3b_upk_old');
        $fresh = $this->createUser('cm3b_upk_fresh');
        $this->sub($old, $course, COURSEMANAGER);

        CourseManager::updateTeachers(['real_id' => $course->getId(), 'code' => $course->getCode()], [$fresh->getId()], false);

        $this->assertNotFalse($this->teacherRow($course, $old), 'nobody is deleted when asked not to');
        $this->assertSame(0, (int) $this->teacherRow($course, $fresh)['user_course_cat']);

        CourseManager::updateTeachers(['real_id' => $course->getId(), 'code' => $course->getCode()], [], true);
        $this->assertSame(0, $this->rows('course_rel_user', 'c_id = ? AND status = 1 AND relation_type = 0', [$course->getId()]), 'an empty list removes every teacher');
        $this->assertFalse(CourseManager::updateTeachers([], [1]));
    }
}
