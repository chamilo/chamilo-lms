<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Legacy;

use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use Database;
use SessionManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session as HttpSession;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * Characterization tests (tier 3) for SessionManager functions the first test file did not reach.
 *
 * They pin what callers observe today (returned rows and keys, ordering, NULL handling, the
 * counters kept in session / session_rel_course, deletion side effects) so that a rewrite of
 * the queries to DQL cannot change behaviour unnoticed. Everything created here is tagged "sm_".
 *
 * Counters are read with raw SQL on purpose: the entity manager's identity map would otherwise
 * hide what the legacy UPDATE statements really wrote.
 */
class SessionManagerTier3CharacterizationTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    protected function setUp(): void
    {
        parent::setUp();

        // Legacy code reads the current user from the HTTP session and queries through the
        // Database wrapper; both are normally prepared by LegacyListener.
        $httpSession = new HttpSession(new MockArraySessionStorage());
        $httpSession->set('_user', ['id' => $this->getAdmin()->getId()]);
        $httpSession->set('access_url_id', $this->getAccessUrl()->getId());
        $request = new Request();
        $request->setSession($httpSession);
        // Container::getSession() prefers this static over the request stack, and an earlier test in
        // the same process leaves its own session there, so point it at this test's session.
        Container::setSession($httpSession);
        self::getContainer()->get('request_stack')->push($request);
        Container::setContainer(self::getContainer());
        $admin = $this->getAdmin();
        self::getContainer()->get('security.token_storage')->setToken(
            new UsernamePasswordToken($admin, 'main', $admin->getRoles())
        );
        Database::setManager($this->getEntityManager());
        Database::setConnection($this->getEntityManager()->getConnection());
    }

    private function newSession(string $title): int
    {
        $id = SessionManager::create_session($title, '', '', '', '', '', '', [], 0, 1, false, null, '');
        $this->assertIsInt($id);

        return $id;
    }

    private function newCourse(string $title): array
    {
        $conn = Database::getConnection();
        $code = strtoupper($title);
        $conn->executeStatement(
            'INSERT INTO course (language_id, title, code, visual_code, directory, visibility, video_url, sticky, creation_date, subscribe, unsubscribe, popularity)
             VALUES ((SELECT MIN(id) FROM language), ?, ?, ?, ?, 2, \'\', 0, NOW(), 1, 0, 0)',
            [$title, $code, $code, $code]
        );

        return [(int) $conn->lastInsertId(), $code];
    }

    public function testUsersByCourseAndSessionListIsDistinctFilteredByStatusAndPaged(): void
    {
        $users = [];
        foreach (['sm3_u1', 'sm3_u2', 'sm3_u3'] as $name) {
            $users[] = $this->createUser($name)->getId();
        }
        [$c1, $code1] = $this->newCourse('sm3_ul_course1');
        [$c2, $code2] = $this->newCourse('sm3_ul_course2');
        $sid = $this->newSession('sm3_s_ul');
        SessionManager::add_courses_to_session($sid, [$c1, $c2]);
        $this->getEntityManager()->clear();
        // u1 and u2 in both courses (DISTINCT must collapse them), u3 only in course 2.
        SessionManager::subscribe_users_to_session_course([$users[0], $users[1]], $sid, $code1);
        SessionManager::subscribe_users_to_session_course($users, $sid, $code2);
        $this->getEntityManager()->clear();

        $all = SessionManager::getUsersByCourseAndSessionList([$sid], [$c1, $c2]);
        sort($all);
        $this->assertEquals($users, $all);
        $this->assertEquals([$users[2]], array_values(array_diff($all, SessionManager::getUsersByCourseSession($sid, ['real_id' => $c1]))));
        $this->assertCount(3, SessionManager::getUsersByCourseSession($sid, ['real_id' => $c2]));
        $this->assertCount(2, SessionManager::getUsersByCourseSession($sid, ['real_id' => $c1], Session::STUDENT));
        $this->assertSame([], SessionManager::getUsersByCourseSession(0, ['real_id' => $c1]));
        $this->assertSame([], SessionManager::getUsersByCourseAndSessionList([], [$c1]));
        $this->assertSame([], SessionManager::getUsersByCourseAndSessionList([$sid], [$c1], Session::COURSE_COACH));
        $this->assertCount(2, SessionManager::getUsersByCourseAndSessionList([$sid], [$c1], Session::STUDENT));

        // LIMIT start, count
        $this->assertCount(3, SessionManager::getUsersByCourseAndSessionList([$sid], [$c1, $c2], null, 0, 3));
        $this->assertCount(1, SessionManager::getUsersByCourseAndSessionList([$sid], [$c1, $c2], null, 2, 5));
        $this->assertCount(2, SessionManager::getUsersByCourseAndSessionList([$sid], [$c1, $c2], null, 0, 2));
    }

    public function testUserStatusInCourseSessionIsPerUser(): void
    {
        $a = $this->createUser('sm3_st_a', '', '', 'ROLE_TEACHER');
        $b = $this->createUser('sm3_st_b');
        [$c1, $code1] = $this->newCourse('sm3_st_course');
        $sid = $this->newSession('sm3_s_st');
        SessionManager::add_courses_to_session($sid, [$c1]);
        $this->getEntityManager()->clear();
        SessionManager::subscribe_users_to_session_course([$b->getId()], $sid, $code1);
        $this->getEntityManager()->clear();
        SessionManager::set_coach_to_course_session($a->getId(), $sid, $c1);
        $this->getEntityManager()->clear();

        $this->assertEquals(Session::COURSE_COACH, SessionManager::get_user_status_in_course_session($a->getId(), $c1, $sid));
        $this->assertEquals(Session::STUDENT, SessionManager::get_user_status_in_course_session($b->getId(), $c1, $sid));
        $this->assertFalse(SessionManager::get_user_status_in_course_session($this->getAdmin()->getId(), $c1, $sid));
    }

    public function testTotalUserCoursesInSessionFiltersByCourseAndCoachesByStatus(): void
    {
        $a = $this->createUser('sm3_tc_a');
        $b = $this->createUser('sm3_tc_b');
        [$c1, $code1] = $this->newCourse('sm3_tc_course1');
        [$c2, $code2] = $this->newCourse('sm3_tc_course2');
        $sid = $this->newSession('sm3_s_tc');
        SessionManager::add_courses_to_session($sid, [$c1, $c2]);
        $this->getEntityManager()->clear();
        SessionManager::subscribe_users_to_session_course([$a->getId()], $sid, $code1);
        SessionManager::subscribe_users_to_session_course([$a->getId(), $b->getId()], $sid, $code2);
        $this->getEntityManager()->clear();

        $this->assertSame([], SessionManager::getTotalUserCoursesInSession(0));
        $this->assertCount(2, SessionManager::getTotalUserCoursesInSession($sid));
        $one = SessionManager::getTotalUserCoursesInSession($sid, $c1);
        $this->assertCount(1, $one);
        $this->assertEquals($a->getId(), $one[0]['id']);
        $this->assertSame(['count', 'id', 'status_in_session', 'user_status'], array_keys($one[0]));

        $this->assertSame([], SessionManager::getCoachesBySession($sid));
        $coach = $this->createUser('sm3_tc_coach', '', '', 'ROLE_TEACHER');
        SessionManager::set_coach_to_course_session($coach->getId(), $sid, $c1);
        $this->getEntityManager()->clear();
        $this->assertEquals([$coach->getId()], SessionManager::getCoachesBySession($sid));
    }

    public function testSessionsListOrderWithoutDirectionIsAscending(): void
    {
        $b = $this->newSession('sm3_ord_b');
        $a = $this->newSession('sm3_ord_a');

        $this->assertSame([$a, $b], array_keys(SessionManager::get_sessions_list([], ['title'], null, null, 0, [$a, $b])));
        $this->assertSame([$b, $a], array_keys(SessionManager::get_sessions_list([], ['title', 'DESC'], null, null, 0, [$a, $b])));
    }

    public function testUnsubscribingACourseRemovesOnlyItsOwnUserRows(): void
    {
        $a = $this->createUser('sm3_uc_a');
        [$c1, $code1] = $this->newCourse('sm3_uc_course1');
        [$c2, $code2] = $this->newCourse('sm3_uc_course2');
        $sid = $this->newSession('sm3_s_uc');
        SessionManager::add_courses_to_session($sid, [$c1, $c2]);
        $this->getEntityManager()->clear();
        SessionManager::subscribe_users_to_session_course([$a->getId()], $sid, $code1);
        SessionManager::subscribe_users_to_session_course([$a->getId()], $sid, $code2);
        $this->getEntityManager()->clear();

        $this->assertTrue(SessionManager::unsubscribe_course_from_session($sid, $c1));
        $this->getEntityManager()->clear();

        $conn = Database::getConnection();
        $this->assertSame(0, (int) $conn->fetchOne('SELECT COUNT(*) FROM session_rel_course_rel_user WHERE session_id = ? AND c_id = ?', [$sid, $c1]));
        $this->assertSame(1, (int) $conn->fetchOne('SELECT COUNT(*) FROM session_rel_course_rel_user WHERE session_id = ? AND c_id = ?', [$sid, $c2]));
        $this->assertSame(1, (int) $conn->fetchOne('SELECT COUNT(*) FROM session_rel_course WHERE session_id = ?', [$sid]));
        $this->assertSame(1, (int) $conn->fetchOne('SELECT nbr_courses FROM session WHERE id = ?', [$sid]));
    }
}
