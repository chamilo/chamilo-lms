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
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

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
        // Today's behaviour, which looks like a bug: unsubscribe_user() calls
        // SessionManager::unsubscribe_user_from_session($uid, $session_id) with the arguments swapped
        // (its signature is ($session_id, $user_id)), so the session registration is never removed.
        $this->assertSame(1, $this->rows('session_rel_user', 'session_id = ? AND user_id = ?', [$sid, $uid]));
    }
}
