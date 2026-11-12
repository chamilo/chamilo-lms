<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Legacy;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Exception\NotAllowedException;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use Database;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\DBAL\Connection;
use SessionManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session as HttpSession;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * Characterization tests for the tracking / report / admin-list side of the legacy SessionManager
 * (who may see or manage which sessions, session-course ordering, followed sessions of HR managers,
 * coaches and session admins, the jqGrid-bound admin list). Complements
 * SessionManagerCharacterizationTest; everything created here is tagged "s2_".
 *
 * Fixtures are inserted with plain SQL (no resource node, see the brief: creating users/courses through
 * repositories deadlocks when other suites share the database).
 */
class SessionManagerReportsCharacterizationTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    private int $seq = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $httpSession = new HttpSession(new MockArraySessionStorage());
        $httpSession->set('_user', ['id' => $this->getAdmin()->getId()]);
        $httpSession->set('access_url_id', $this->getAccessUrl()->getId());
        $request = new Request();
        $request->setSession($httpSession);
        Container::setSession($httpSession);
        self::getContainer()->get('request_stack')->push($request);
        Container::setContainer(self::getContainer());
        $this->loginAs($this->getAdmin()->getId());
        Database::setManager($this->getEntityManager());
        Database::setConnection($this->getEntityManager()->getConnection());
    }

    // ---------------------------------------------------------------- helpers

    private function conn(): Connection
    {
        return $this->getEntityManager()->getConnection();
    }

    private function fresh(): void
    {
        $this->getEntityManager()->clear();
    }

    private function one(string $sql, array $params = []): mixed
    {
        return $this->conn()->fetchOne($sql, $params);
    }

    private function loginAs(int $userId): void
    {
        $user = $this->getEntityManager()->getRepository(User::class)->find($userId);
        self::getContainer()->get('security.token_storage')->setToken(
            new UsernamePasswordToken($user, 'main', $user->getRoles())
        );
        Container::getSession()->set('_user', ['id' => $userId]);
        $this->fresh();
    }

    private function setSetting(string $name, string $value): void
    {
        self::getContainer()->get(SettingsManager::class)->updateSetting($name, $value);
        $this->fresh();
    }

    private function rawUser(string $suffix, string $role = 'ROLE_STUDENT', int $status = 5): int
    {
        $name = 's2_'.$suffix.'_'.getmypid().'_'.(++$this->seq);
        $this->conn()->executeStatement(
            'INSERT INTO user (username, username_canonical, email, email_canonical, firstname, lastname, locale,
                password, timezone, locked, expired, credentials_expired, salt, roles, status, active, uuid,
                mfa_enabled, created_at, updated_at, official_code)
             VALUES (?, ?, ?, ?, ?, ?, \'en_US\', \'x\', \'UTC\', 0, 0, 0, \'x\', ?, ?, 1,
                UNHEX(REPLACE(UUID(), \'-\', \'\')), 0, NOW(), NOW(), \'\')',
            [$name, $name, $name.'@example.com', $name.'@example.com', 'First'.$suffix, 'Last'.$suffix, json_encode([$role]), $status]
        );
        $id = (int) $this->conn()->lastInsertId();
        $this->conn()->executeStatement('INSERT INTO access_url_rel_user (access_url_id, user_id) VALUES (1, ?)', [$id]);

        return $id;
    }

    private function rawSession(string $title, array $extra = []): int
    {
        $this->conn()->executeStatement(
            'INSERT INTO session (title, nbr_courses, nbr_users, nbr_classes, visibility, status,
                access_start_date, access_end_date, session_category_id, position, description)
             VALUES (?, 0, 0, 0, ?, ?, ?, ?, ?, 0, ?)',
            [
                's2_'.$title,
                $extra['visibility'] ?? 1,
                $extra['status'] ?? 0,
                $extra['start'] ?? null,
                $extra['end'] ?? null,
                $extra['category'] ?? null,
                $extra['description'] ?? '',
            ]
        );
        $id = (int) $this->conn()->lastInsertId();
        if (!($extra['no_url'] ?? false)) {
            $this->conn()->executeStatement('INSERT INTO access_url_rel_session (session_id, access_url_id) VALUES (?, 1)', [$id]);
        }

        return $id;
    }

    private function rawCourse(string $title): int
    {
        $code = strtoupper('s2_'.$title.'_'.getmypid().'_'.(++$this->seq));
        $this->conn()->executeStatement(
            'INSERT INTO course (language_id, title, code, visual_code, directory, visibility, video_url, sticky, creation_date, subscribe, unsubscribe, popularity)
             VALUES ((SELECT MIN(id) FROM language), ?, ?, ?, ?, 2, \'\', 0, NOW(), 1, 0, 0)',
            ['s2_'.$title, $code, $code, $code]
        );

        return (int) $this->conn()->lastInsertId();
    }

    private function rel(int $sessionId, int $userId, int $type): void
    {
        $this->conn()->executeStatement(
            'INSERT INTO session_rel_user (session_id, user_id, relation_type, duration, registered_at) VALUES (?, ?, ?, 0, NOW())',
            [$sessionId, $userId, $type]
        );
    }

    private function sessionCourse(int $sessionId, int $courseId, int $position = 0): void
    {
        $this->conn()->executeStatement(
            'INSERT INTO session_rel_course (session_id, c_id, nbr_users, position) VALUES (?, ?, 0, ?)',
            [$sessionId, $courseId, $position]
        );
    }

    private function courseUser(int $sessionId, int $courseId, int $userId, int $status): void
    {
        $this->conn()->executeStatement(
            'INSERT INTO session_rel_course_rel_user (session_id, c_id, user_id, status, visibility, legal_agreement, progress)
             VALUES (?, ?, ?, ?, 1, 0, 0)',
            [$sessionId, $courseId, $userId, $status]
        );
    }

    // ---------------------------------------------------------------- getSessionsFollowedByUser

    public function testFollowedSessionsOfAnHrManagerAreOnlyThoseHeIsSubscribedTo(): void
    {
        $hrm = $this->rawUser('hrm', 'ROLE_HR', DRH);
        $mine = $this->rawSession('fol_b');
        $mine2 = $this->rawSession('fol_a');
        $other = $this->rawSession('fol_other');
        $this->rel($mine, $hrm, Session::DRH);
        $this->rel($mine2, $hrm, Session::DRH);

        $rows = SessionManager::getSessionsFollowedByUser($hrm, DRH);

        $this->assertSame([$mine2, $mine], array_keys($rows), 'Default order is by title, rows are keyed by session id.');
        $this->assertArrayNotHasKey($other, $rows);
        $this->assertSame('s2_fol_a', $rows[$mine2]['title']);
        $this->assertArrayHasKey('image', $rows[$mine2], 'A default icon url is added to every row.');
        $this->assertSame($rows, SessionManager::get_sessions_followed_by_hrm($hrm));
        $this->assertSame($rows, SessionManager::get_sessions_followed_by_drh($hrm));
    }

    public function testFollowedSessionsCountIdsKeywordLimitAndOrder(): void
    {
        $hrm = $this->rawUser('hrm2', 'ROLE_HR', DRH);
        $a = $this->rawSession('fk_alpha', ['description' => 'zebra topic']);
        $b = $this->rawSession('fk_beta');
        $c = $this->rawSession('fk_gamma');
        foreach ([$a, $b, $c] as $id) {
            $this->rel($id, $hrm, Session::DRH);
        }

        $this->assertSame(3, SessionManager::getSessionsFollowedByUser($hrm, DRH, null, null, true));
        $ids = SessionManager::getSessionsFollowedByUser($hrm, DRH, null, null, false, true);
        $this->assertSame([$a, $b, $c], array_keys($ids));
        $this->assertEqualsCanonicalizing([0, 'id'], array_keys($ids[$a]), 'Only the id column is selected (fetch_array: numeric and named key).');

        $this->assertSame([$b], array_keys(SessionManager::getSessionsFollowedByUser($hrm, DRH, null, null, false, false, false, null, 'fk_bet')));
        // With a description the keyword condition becomes title OR description.
        $this->assertSame(
            [$a, $b],
            array_keys(SessionManager::getSessionsFollowedByUser($hrm, DRH, null, null, false, false, false, null, 'fk_bet', 'zebra'))
        );
        $this->assertSame(
            [$c, $b],
            array_keys(SessionManager::getSessionsFollowedByUser($hrm, DRH, 0, 2, false, false, false, ' ORDER BY s.title DESC '))
        );
        $this->assertSame(
            [$a],
            array_keys(SessionManager::getSessionsFollowedByUser($hrm, DRH, 0, 1))
        );
        $this->assertSame(
            [$b],
            array_keys(SessionManager::getSessionsFollowedByUser($hrm, DRH, 1, 1))
        );

        $sql = SessionManager::getSessionsFollowedByUser($hrm, DRH, null, null, false, true, true);
        $this->assertIsString($sql);
        $this->assertStringContainsString('SELECT DISTINCT(s.id)', $sql);
    }

    public function testFollowedSessionsRoleSwitchStudentCoachAndSessionAdmin(): void
    {
        $u = $this->rawUser('multi');
        $asStudent = $this->rawSession('role_student');
        $asGeneral = $this->rawSession('role_general');
        $asCourseCoach = $this->rawSession('role_ccoach');
        $asAdmin = $this->rawSession('role_admin');
        $asHrm = $this->rawSession('role_hrm');
        $course = $this->rawCourse('role');
        $this->sessionCourse($asCourseCoach, $course);
        $this->rel($asStudent, $u, Session::STUDENT);
        $this->rel($asGeneral, $u, Session::GENERAL_COACH);
        $this->rel($asAdmin, $u, Session::SESSION_ADMIN);
        $this->rel($asHrm, $u, Session::DRH);
        $this->courseUser($asCourseCoach, $course, $u, Session::COURSE_COACH);

        $this->assertSame([$asStudent], array_keys(SessionManager::getSessionsFollowedByUser($u)));
        $this->assertSame([$asStudent], array_keys(SessionManager::getSessionsFollowedByUser($u, STUDENT)));
        $this->assertSame([$asHrm], array_keys(SessionManager::getSessionsFollowedByUser($u, DRH)));
        $this->assertSame([$asAdmin], array_keys(SessionManager::getSessionsFollowedByUser($u, SESSIONADMIN)));
        $this->assertEqualsCanonicalizing(
            [$asGeneral, $asCourseCoach],
            array_keys(SessionManager::getSessionsFollowedByUser($u, COURSEMANAGER)),
            'A teacher follows the sessions he coaches in a course and those where he is general coach.'
        );
    }

    public function testFollowedSessionsAreScopedToTheCurrentAccessUrl(): void
    {
        $hrm = $this->rawUser('hrm3', 'ROLE_HR', DRH);
        $inUrl = $this->rawSession('url_in');
        $outUrl = $this->rawSession('url_out', ['no_url' => true]);
        $elsewhere = $this->rawSession('url_elsewhere', ['no_url' => true]);
        $this->conn()->executeStatement(
            "INSERT INTO access_url (url, description, active, created_by, tms, lft, rgt, lvl, tree_root, limit_courses, limit_active_courses, limit_sessions, limit_users, limit_teachers, limit_disk_space, email, is_login_only)
             VALUES ('http://s2-other-url.example/', '', 1, 1, NOW(), 1, 2, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0)"
        );
        $otherUrl = (int) $this->conn()->lastInsertId();
        $this->conn()->executeStatement('INSERT INTO access_url_rel_session (session_id, access_url_id) VALUES (?, ?)', [$elsewhere, $otherUrl]);
        $this->rel($inUrl, $hrm, Session::DRH);
        $this->rel($outUrl, $hrm, Session::DRH);
        $this->rel($elsewhere, $hrm, Session::DRH);

        $this->assertSame([$inUrl], array_keys(SessionManager::getSessionsFollowedByUser($hrm, DRH)), 'A session of another URL is not listed.');
        $this->assertSame(1, SessionManager::getSessionsFollowedByUser($hrm, DRH, null, null, true));
    }

    public function testFollowedSessionsOfAUserWhoFollowsNothingIsEmpty(): void
    {
        // Security: a user without any followed session must get an empty list, not every session of the URL.
        $nobody = $this->rawUser('nobody', 'ROLE_HR', DRH);
        $someone = $this->rawUser('someone', 'ROLE_HR', DRH);
        $s = $this->rawSession('nofollow');
        $this->rel($s, $someone, Session::DRH);

        $this->assertSame([], SessionManager::getSessionsFollowedByUser($nobody, DRH));
        $this->assertSame(0, SessionManager::getSessionsFollowedByUser($nobody, DRH, null, null, true));
        $this->assertSame([], SessionManager::getSessionsFollowedByUser($nobody));
        $this->assertSame([], SessionManager::getSessionsFollowedByUser($nobody, SESSIONADMIN));
        $this->assertSame([], SessionManager::getSessionsFollowedByUser($nobody, COURSEMANAGER));
    }

    // ---------------------------------------------------------------- getAllCoursesFollowedByUser

    public function testCoursesFollowedByAnHrManager(): void
    {
        $hrm = $this->rawUser('hrmc', 'ROLE_HR', DRH);
        $s1 = $this->rawSession('fc_1');
        $s2 = $this->rawSession('fc_2');
        $other = $this->rawSession('fc_other');
        $c1 = $this->rawCourse('fc_one');
        $c2 = $this->rawCourse('fc_two');
        $c3 = $this->rawCourse('fc_three');
        $this->rel($s1, $hrm, Session::DRH);
        $this->rel($s2, $hrm, Session::DRH);
        $this->sessionCourse($s1, $c1);
        $this->sessionCourse($s2, $c1);
        $this->sessionCourse($s2, $c2);
        $this->sessionCourse($other, $c3);

        $all = SessionManager::getAllCoursesFollowedByUser($hrm, null);
        $this->assertEqualsCanonicalizing([$c1, $c2], array_keys($all), 'Distinct courses of every followed session.');
        $this->assertArrayHasKey('course_language', $all[$c1]);
        $this->assertSame(2, (int) SessionManager::getAllCoursesFollowedByUser($hrm, null, null, null, null, null, true));

        $one = SessionManager::getAllCoursesFollowedByUser($hrm, $s1);
        $this->assertSame([$c1], array_keys($one));
        $this->assertSame([$c2], array_keys(SessionManager::getAllCoursesFollowedByUser($hrm, $s2, null, null, null, null, false, 'fc_two')));
        $this->assertSame(1, (int) SessionManager::getAllCoursesFollowedByUser($hrm, $s2, null, null, null, null, true, 'fc_two'));
        $this->assertCount(1, SessionManager::getAllCoursesFollowedByUser($hrm, null, 0, 1));
    }

    public function testCoursesFollowedByAnHrManagerWithoutSessionsAreEmpty(): void
    {
        // Security: the sub-query of followed sessions must not degrade to "every session" for a manager
        // who follows nothing, otherwise he would see every course that is in a session.
        $nobody = $this->rawUser('hrmnone', 'ROLE_HR', DRH);
        $s = $this->rawSession('fc_none');
        $c = $this->rawCourse('fc_none');
        $this->sessionCourse($s, $c);

        $this->assertSame([], SessionManager::getAllCoursesFollowedByUser($nobody, null));
        $this->assertSame(0, (int) SessionManager::getAllCoursesFollowedByUser($nobody, null, null, null, null, null, true));
    }

    public function testAllCoursesFromAllSessionsOfAnHrManagerAndOfThePlatform(): void
    {
        $hrm = $this->rawUser('hrmall', 'ROLE_HR', DRH);
        $s = $this->rawSession('ca_1');
        $other = $this->rawSession('ca_2');
        $c = $this->rawCourse('ca');
        $this->rel($s, $hrm, Session::DRH);
        $this->sessionCourse($s, $c);
        $this->sessionCourse($other, $c);
        $code = $this->one('SELECT code FROM course WHERE id = ?', [$c]);

        $this->assertSame([$code], SessionManager::getAllCoursesFromAllSessionFromDrh($hrm));
        $this->assertSame([], SessionManager::getAllCoursesFromAllSessionFromDrh($this->rawUser('hrmx', 'ROLE_HR', DRH)));

        $all = SessionManager::getAllCoursesFromAllSessions();
        $this->assertSame('s2_ca (s2_ca_1)', substr($all[$code.':'.$s], strpos($all[$code.':'.$s], 's2_ca')));
        $this->assertArrayHasKey($code.':'.$other, $all);
    }

    // ---------------------------------------------------------------- teachers / users tracking

    public function testTeacherTrackingListsTheTeachersOfTheFollowedSessions(): void
    {
        $hrm = $this->rawUser('hrmt', 'ROLE_HR', DRH);
        $teacher = $this->rawUser('teach', 'ROLE_TEACHER', COURSEMANAGER);
        $inactive = $this->rawUser('teach_off', 'ROLE_TEACHER', COURSEMANAGER);
        $this->conn()->executeStatement('UPDATE user SET active = 0 WHERE id = ?', [$inactive]);
        $outsider = $this->rawUser('teach_out', 'ROLE_TEACHER', COURSEMANAGER);
        $s = $this->rawSession('tt');
        $notMine = $this->rawSession('tt_other');
        $c = $this->rawCourse('tt');
        $this->rel($s, $hrm, Session::DRH);
        $this->sessionCourse($s, $c);
        $this->sessionCourse($notMine, $c);
        // course_rel_user.status = 1 is "teacher of the base course".
        foreach ([$teacher, $inactive] as $id) {
            $this->conn()->executeStatement(
                'INSERT INTO course_rel_user (c_id, user_id, status, relation_type, sort, user_course_cat, is_tutor, progress)
                 VALUES (?, ?, 1, 0, 0, 0, 0, 0)',
                [$c, $id]
            );
        }
        $this->loginAs($hrm);

        $active = SessionManager::getTeacherTracking($hrm, 1);
        $this->assertSame([$teacher], array_map('intval', array_column($active, 'id')));
        $this->assertSame(1, (int) SessionManager::getTeacherTracking($hrm, 1, null, true));
        $this->assertSame([$inactive], array_map('intval', array_column(SessionManager::getTeacherTracking($hrm, 0), 'id')));
        // Explicit session list overrides the followed sessions.
        $this->assertSame(0, SessionManager::getTeacherTracking($hrm, 1, null, false, [$notMine + 1000]));
        $this->assertNotContains($outsider, array_column($active, 'id'));
    }

    public function testTeacherTrackingIsEmptyForAPlainStudent(): void
    {
        $student = $this->rawUser('tstud');
        $this->loginAs($student);

        $this->assertSame(0, SessionManager::getTeacherTracking($student));
    }

    public function testTotalUserTimeInPlatform(): void
    {
        $u = $this->rawUser('time');
        $this->assertSame('00:00:00', SessionManager::getTotalUserTimeInPlatform($u));

        $ins = 'INSERT INTO track_e_login (login_user_id, user_ip, login_date, logout_date) VALUES (?, \'\', ?, ?)';
        $this->conn()->executeStatement($ins, [$u, '2030-01-01 10:00:00', '2030-01-01 10:30:00']);
        $this->conn()->executeStatement($ins, [$u, '2030-01-02 08:00:00', '2030-01-02 09:00:15']);
        $this->conn()->executeStatement($ins, [$u, '2030-02-10 08:00:00', '2030-02-10 08:00:30']);

        $this->assertSame('01:30:45', SessionManager::getTotalUserTimeInPlatform($u));
        $this->assertSame(
            '01:30:15',
            SessionManager::getTotalUserTimeInPlatform($u, '2030-01-01', '2030-01-02'),
            'The until date is inclusive (logout <= until + 1 day).'
        );
        $this->assertSame('00:30:00', SessionManager::getTotalUserTimeInPlatform($u, '2030-01-01', '2030-01-01'));
        $this->assertSame('00:00:30', SessionManager::getTotalUserTimeInPlatform($u, '2030-02-01', '2030-02-28'));
        // Dates are only used when both are given.
        $this->assertSame('01:30:45', SessionManager::getTotalUserTimeInPlatform($u, '2030-02-01'));
    }

    // ---------------------------------------------------------------- course ordering

    private function orderedSession(): array
    {
        $this->setSetting('session.session_course_ordering', 'true');
        $s = $this->rawSession('ord');
        $c = [$this->rawCourse('ord_a'), $this->rawCourse('ord_b'), $this->rawCourse('ord_c')];
        foreach ($c as $i => $id) {
            $this->sessionCourse($s, $id, $i + 1);
        }

        return [$s, $c];
    }

    private function positions(int $sessionId): array
    {
        return $this->conn()->fetchAllKeyValue('SELECT c_id, position FROM session_rel_course WHERE session_id = ?', [$sessionId]);
    }

    public function testCourseOrderingFlagFollowsTheSetting(): void
    {
        $this->setSetting('session.session_course_ordering', 'false');
        $this->assertFalse(SessionManager::orderCourseIsEnabled());
        $this->setSetting('session.session_course_ordering', 'true');
        $this->assertTrue(SessionManager::orderCourseIsEnabled());
    }

    public function testMoveIsANoOpWhenOrderingIsDisabled(): void
    {
        $this->setSetting('session.session_course_ordering', 'false');
        $s = $this->rawSession('ord_off');
        $a = $this->rawCourse('off_a');
        $b = $this->rawCourse('off_b');
        $this->sessionCourse($s, $a, 1);
        $this->sessionCourse($s, $b, 2);

        $this->assertFalse(SessionManager::moveDown($s, $a));
        $this->assertSame([$a => 1, $b => 2], array_map('intval', $this->positions($s)));
    }

    public function testMoveDownAndUpSwapAdjacentCourses(): void
    {
        [$s, [$a, $b, $c]] = $this->orderedSession();

        $this->assertTrue(SessionManager::moveDown($s, $a));
        // The positions are first renumbered 0,1,2 by the current order, then the pair is swapped.
        $this->assertSame([$a => 1, $b => 0, $c => 2], array_map('intval', $this->positions($s)));

        $this->assertTrue(SessionManager::moveUp($s, $c));
        $this->assertSame([$a => 2, $b => 0, $c => 1], array_map('intval', $this->positions($s)));

        $this->assertSame(
            [$b, $c, $a],
            array_keys(SessionManager::get_course_list_by_session_id($s)),
            'With the ordering enabled the course list is sorted by position, not by title.'
        );
    }

    public function testCourseListOrderingFollowsTheSettingAndTheExplicitOrder(): void
    {
        $this->setSetting('session.session_course_ordering', 'false');
        $s = $this->rawSession('ord_list');
        $z = $this->rawCourse('zzz');
        $a = $this->rawCourse('aaa');
        $this->sessionCourse($s, $z, 1);
        $this->sessionCourse($s, $a, 2);

        $this->assertSame([$a, $z], array_keys(SessionManager::get_course_list_by_session_id($s)), 'By title.');
        $this->assertSame([$z, $a], array_keys(SessionManager::get_course_list_by_session_id($s, '', 'position')));
        $this->assertSame([$z, $a], array_keys(SessionManager::get_course_list_by_session_id($s, '', 'title DESC')));
        $this->assertSame(2, SessionManager::get_course_list_by_session_id($s, '', null, true));
        $this->assertSame(1, SessionManager::get_course_list_by_session_id($s, 's2_aaa', null, true));
        $this->assertSame([], SessionManager::get_course_list_by_session_id($s, 'nomatch'));
    }

    // ---------------------------------------------------------------- user in course subscription

    public function testUnSubscribeUserFromCourseSessionKeepsTheCounterInSync(): void
    {
        $s = $this->rawSession('unsub');
        $c = $this->rawCourse('unsub');
        $u1 = $this->rawUser('un1');
        $u2 = $this->rawUser('un2');
        $this->sessionCourse($s, $c);
        $this->courseUser($s, $c, $u1, Session::STUDENT);
        $this->courseUser($s, $c, $u2, Session::STUDENT);
        $this->conn()->executeStatement('UPDATE session_rel_course SET nbr_users = 2 WHERE session_id = ?', [$s]);

        SessionManager::unSubscribeUserFromCourseSession($u1, $c, $s);

        $this->assertSame(
            [$u2],
            array_map('intval', $this->conn()->fetchFirstColumn('SELECT user_id FROM session_rel_course_rel_user WHERE session_id = ?', [$s]))
        );
        $this->assertSame(1, (int) $this->one('SELECT nbr_users FROM session_rel_course WHERE session_id = ? AND c_id = ?', [$s, $c]));

        // Unsubscribing somebody who is not subscribed must not touch the counter.
        SessionManager::unSubscribeUserFromCourseSession($u1, $c, $s);
        $this->assertSame(1, (int) $this->one('SELECT nbr_users FROM session_rel_course WHERE session_id = ? AND c_id = ?', [$s, $c]));
    }

    public function testMovingTheFirstCourseUpOrTheLastDownIsHarmless(): void
    {
        [$s, [$a, $b, $c]] = $this->orderedSession();

        $this->assertFalse(SessionManager::moveUp($s, $a), 'Nothing above the first course.');
        $this->assertFalse(SessionManager::moveDown($s, $c), 'Nothing below the last course.');
        $this->assertFalse(SessionManager::moveDown($s, 99999999), 'A course that is not in the session.');

        $this->assertSame(
            [$a, $b, $c],
            array_keys(SessionManager::get_course_list_by_session_id($s)),
            'Moving past an end keeps the order.'
        );
    }

    // ---------------------------------------------------------------- group admin

    public function testSessionsFollowedForGroupAdminAreTheOnesOfTheStudentsOfHisClasses(): void
    {
        $boss = $this->rawUser('gadm', 'ROLE_STUDENT_BOSS', 9);
        $member = $this->rawUser('gmember');
        $stranger = $this->rawUser('gstranger');
        $mine = $this->rawSession('ga_mine');
        $theirs = $this->rawSession('ga_theirs');
        $coachOnly = $this->rawSession('ga_coach');
        $this->conn()->executeStatement(
            "INSERT INTO usergroup (title, group_type, visibility, allow_members_leave_group, created_at, updated_at)
             VALUES ('s2_class', 1, '1', 0, NOW(), NOW())"
        );
        $group = (int) $this->conn()->lastInsertId();
        foreach ([[$boss, GROUP_USER_PERMISSION_ADMIN], [$member, GROUP_USER_PERMISSION_READER]] as [$u, $rel]) {
            $this->conn()->executeStatement(
                'INSERT INTO usergroup_rel_user (usergroup_id, user_id, relation_type) VALUES (?, ?, ?)',
                [$group, $u, $rel]
            );
        }
        $this->rel($mine, $member, Session::STUDENT);
        $this->rel($theirs, $stranger, Session::STUDENT);
        $this->rel($coachOnly, $member, Session::GENERAL_COACH);

        $rows = SessionManager::getSessionsFollowedForGroupAdmin($boss);

        $this->assertSame([$mine], array_map('intval', array_column($rows, 'id')), 'Only the sessions where a class member is a STUDENT.');
        $this->assertSame([], SessionManager::getSessionsFollowedForGroupAdmin($member), 'A plain member administers no class.');
        $this->assertSame([], SessionManager::getSessionsFollowedForGroupAdmin($this->rawUser('gnone')));
    }

    // ---------------------------------------------------------------- who may manage which sessions

    private function sessionEntity(int $id): Session
    {
        $this->fresh();

        return $this->getEntityManager()->getRepository(Session::class)->find($id);
    }

    /**
     * @return array<string, bool> role => may edit the session
     */
    private function editMatrix(int $sessionId): array
    {
        $roles = [
            'admin' => $this->getAdmin()->getId(),
            'sessionAdminOwner' => $this->users['sessionAdminOwner'],
            'sessionAdminOther' => $this->users['sessionAdminOther'],
            'teacherCoach' => $this->users['teacherCoach'],
            'teacherOther' => $this->users['teacherOther'],
            'student' => $this->users['student'],
            'hrm' => $this->users['hrm'],
        ];
        $out = [];
        foreach ($roles as $name => $id) {
            $this->loginAs($id);
            $out[$name] = SessionManager::cantEditSession($this->sessionEntity($sessionId));
        }
        $this->loginAs($this->getAdmin()->getId());

        return $out;
    }

    private array $users = [];

    private function accessFixture(): int
    {
        $this->users = [
            'sessionAdminOwner' => $this->rawUser('sao', 'ROLE_SESSION_MANAGER', 3),
            'sessionAdminOther' => $this->rawUser('sax', 'ROLE_SESSION_MANAGER', 3),
            'teacherCoach' => $this->rawUser('tc', 'ROLE_TEACHER', 1),
            'teacherOther' => $this->rawUser('to', 'ROLE_TEACHER', 1),
            'student' => $this->rawUser('st'),
            'hrm' => $this->rawUser('hr', 'ROLE_HR', 1),
        ];
        $s = $this->rawSession('acc');
        $this->rel($s, $this->users['sessionAdminOwner'], Session::SESSION_ADMIN);
        $this->rel($s, $this->users['teacherCoach'], Session::GENERAL_COACH);

        return $s;
    }

    public function testWhoMayEditASessionWithTheDefaultSettings(): void
    {
        $this->setSetting('session.allow_session_admins_to_manage_all_sessions', 'false');
        $this->setSetting('session.allow_teachers_to_create_sessions', 'false');
        $s = $this->accessFixture();

        $this->assertSame(
            [
                'admin' => true,
                'sessionAdminOwner' => true,
                'sessionAdminOther' => false,
                'teacherCoach' => false,
                'teacherOther' => false,
                'student' => false,
                'hrm' => false,
            ],
            $this->editMatrix($s),
            'Only platform admins and the session admin of that very session.'
        );
    }

    public function testWhoMayEditASessionWhenSessionAdminsManageAllAndTeachersMayCreate(): void
    {
        $this->setSetting('session.allow_session_admins_to_manage_all_sessions', 'true');
        $this->setSetting('session.allow_teachers_to_create_sessions', 'true');
        $s = $this->accessFixture();

        $this->assertSame(
            [
                'admin' => true,
                'sessionAdminOwner' => true,
                'sessionAdminOther' => true,
                'teacherCoach' => true,
                'teacherOther' => false,
                'student' => false,
                'hrm' => false,
            ],
            $this->editMatrix($s),
            'A teacher only edits the sessions he coaches as general coach.'
        );
    }

    public function testManagementFlagsFollowTheRole(): void
    {
        $this->setSetting('session.allow_teachers_to_create_sessions', 'true');
        $this->accessFixture();
        $flags = [];
        foreach (['sessionAdminOwner', 'teacherCoach', 'student', 'hrm'] as $role) {
            $this->loginAs($this->users[$role]);
            $flags[$role] = [
                SessionManager::allowManageAllSessions(),
                SessionManager::allowToManageSessions(),
                SessionManager::allowOnlyMySessions(),
            ];
        }
        $this->loginAs($this->getAdmin()->getId());

        $this->assertSame(
            [
                'sessionAdminOwner' => [true, true, false],
                'teacherCoach' => [false, true, true],
                'student' => [false, false, false],
                'hrm' => [false, false, false],
            ],
            $flags
        );
        $this->assertSame([true, true, false], [SessionManager::allowManageAllSessions(), SessionManager::allowToManageSessions(), SessionManager::allowOnlyMySessions()]);
    }

    public function testCantEditWithoutSessionCheckOnlyNeedsTheManagementRight(): void
    {
        $this->setSetting('session.allow_teachers_to_create_sessions', 'false');
        $this->accessFixture();
        $out = [];
        foreach (['sessionAdminOther', 'teacherOther', 'student'] as $role) {
            $this->loginAs($this->users[$role]);
            $out[$role] = SessionManager::cantEditSession(null, false);
        }
        $this->loginAs($this->getAdmin()->getId());

        $this->assertSame(['sessionAdminOther' => true, 'teacherOther' => false, 'student' => false], $out);
    }

    // ---------------------------------------------------------------- admin session list scoping

    private function adminListIds(int $userId, string $listType = 'all'): array
    {
        $rows = SessionManager::getSessionsForAdmin($userId, ['order' => 's.title ASC'], false, [], $listType);

        return array_map('intval', array_column($rows, 'id'));
    }

    public function testAdminListIsScopedPerRole(): void
    {
        $this->setSetting('session.allow_session_admins_to_manage_all_sessions', 'false');
        $this->setSetting('session.allow_teachers_to_create_sessions', 'true');
        $s = $this->accessFixture();
        $foreign = $this->rawSession('acc_foreign');
        $this->rel($foreign, $this->users['sessionAdminOther'], Session::SESSION_ADMIN);
        $this->rel($foreign, $this->users['sessionAdminOwner'], Session::GENERAL_COACH);

        $this->loginAs($this->getAdmin()->getId());
        $all = $this->adminListIds($this->getAdmin()->getId());
        $this->assertContains($s, $all);
        $this->assertContains($foreign, $all, 'A platform admin sees every session.');

        $this->loginAs($this->users['sessionAdminOwner']);
        $this->assertSame([$s], $this->adminListIds($this->users['sessionAdminOwner']), 'A session admin only sees the sessions where he is session admin, not those he merely coaches.');
        $this->assertSame(1, (int) SessionManager::getSessionsForAdmin($this->users['sessionAdminOwner'], [], true));

        $this->loginAs($this->users['teacherCoach']);
        $this->assertSame([$s], $this->adminListIds($this->users['teacherCoach']), 'A teacher only sees the sessions he coaches as general coach.');

        $this->setSetting('session.allow_teachers_to_create_sessions', 'false');
        $this->loginAs($this->users['teacherCoach']);
        $this->assertContains($foreign, $this->adminListIds($this->users['teacherCoach']), 'PINNED QUIRK: without the setting the list itself is not restricted (the pages refuse the teacher before).');

        $this->setSetting('session.allow_session_admins_to_manage_all_sessions', 'true');
        $this->loginAs($this->users['sessionAdminOther']);
        $this->assertContains($s, $this->adminListIds($this->users['sessionAdminOther']), 'With manage-all a session admin sees every session.');
        $this->loginAs($this->getAdmin()->getId());
    }

    public function testAdminListTypesActiveCloseAndKeyword(): void
    {
        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $fmt = 'Y-m-d H:i:s';
        $open = $this->rawSession('lt_open', ['start' => $now->modify('-5 days')->format($fmt), 'end' => $now->modify('+5 days')->format($fmt)]);
        $past = $this->rawSession('lt_past', ['start' => $now->modify('-20 days')->format($fmt), 'end' => $now->modify('-10 days')->format($fmt)]);
        $future = $this->rawSession('lt_future', ['start' => $now->modify('+10 days')->format($fmt), 'end' => $now->modify('+20 days')->format($fmt)]);
        $endless = $this->rawSession('lt_endless');
        $admin = $this->getAdmin()->getId();
        $mine = [$open, $past, $future, $endless];

        $active = array_intersect($this->adminListIds($admin, 'active'), $mine);
        $closed = array_intersect($this->adminListIds($admin, 'close'), $mine);

        $this->assertEqualsCanonicalizing([$open, $endless], $active, 'Active: no end date, or running now. A future session is neither active nor closed.');
        $this->assertSame([$past], array_values($closed));
        $this->assertSame(
            [$endless, $open],
            array_map('intval', array_column(SessionManager::getSessionsForAdmin($admin, ['where' => " s.title LIKE 's2_lt_%' AND s.id IN ($open, $endless)", 'order' => 's.title ASC']), 'id')),
            'The where clause of the grid filters is appended to the query.'
        );
        $this->assertSame(2, (int) SessionManager::getSessionsForAdmin($admin, ['where' => " s.id IN ($open, $endless)"], true));
    }

    // ---------------------------------------------------------------- coached sessions and subscription helpers

    public function testSessionsCoachedByUserMergeGeneralAndCourseCoachAndDropInvisible(): void
    {
        $coach = $this->rawUser('coached', 'ROLE_TEACHER', 1);
        $general = $this->rawSession('co_general');
        $course = $this->rawSession('co_course');
        $both = $this->rawSession('co_both');
        $hidden = $this->rawSession('co_hidden', [
            'visibility' => Session::INVISIBLE,
            'start' => '2020-01-01 00:00:00',
            'end' => '2020-02-01 00:00:00',
        ]);
        $notMine = $this->rawSession('co_not_mine');
        $c = $this->rawCourse('co');
        foreach ([$course, $both, $hidden, $notMine] as $id) {
            $this->sessionCourse($id, $c);
        }
        $this->rel($general, $coach, Session::GENERAL_COACH);
        $this->rel($both, $coach, Session::GENERAL_COACH);
        $this->rel($hidden, $coach, Session::GENERAL_COACH);
        $this->courseUser($course, $c, $coach, Session::COURSE_COACH);
        $this->courseUser($both, $c, $coach, Session::COURSE_COACH);
        $this->courseUser($notMine, $c, $this->rawUser('costud'), Session::STUDENT);

        $all = SessionManager::getSessionsCoachedByUser($coach);
        $this->assertEqualsCanonicalizing([$general, $course, $both, $hidden], array_map('intval', array_keys($all)));
        $this->assertSame('s2_co_course', $all[$course]['title']);

        // A finished session whose visibility is INVISIBLE is removed on request, unless the viewer is a
        // platform admin (api_get_session_visibility ignores the visibility for admins).
        $this->loginAs($coach);
        $asCoach = array_map('intval', array_column(SessionManager::getSessionsCoachedByUser($coach, true), 'id'));
        $this->loginAs($this->getAdmin()->getId());
        $asAdmin = array_map('intval', array_column(SessionManager::getSessionsCoachedByUser($coach, true), 'id'));
        $this->assertNotContains($hidden, $asCoach);
        $this->assertContains($hidden, $asAdmin);
        $this->assertContains($general, $asCoach);
    }

    public function testCoursesOfACoachAreMergedPerCourse(): void
    {
        $coach = $this->rawUser('cc', 'ROLE_TEACHER', 1);
        $s1 = $this->rawSession('cc_1');
        $s2 = $this->rawSession('cc_2');
        $c1 = $this->rawCourse('cc_a');
        $c2 = $this->rawCourse('cc_b');
        $this->sessionCourse($s1, $c1);
        $this->sessionCourse($s1, $c2);
        $this->sessionCourse($s2, $c1);
        $this->rel($s1, $coach, Session::GENERAL_COACH);
        $this->courseUser($s2, $c1, $coach, Session::COURSE_COACH);
        $this->loginAs($coach);

        $byCourseCoach = SessionManager::getCoursesForCourseSessionCoach($coach);
        $byMainCoach = SessionManager::getCoursesForMainSessionCoach($coach);
        $merged = SessionManager::getSessionCourseForUser($coach);
        $this->loginAs($this->getAdmin()->getId());

        $this->assertSame([$c1 => [$s2]], $byCourseCoach);
        $this->assertEqualsCanonicalizing([$c1, $c2], array_keys($byMainCoach));
        $this->assertSame([$s1], $byMainCoach[$c1]);
        $this->assertEqualsCanonicalizing([$s2, $s1], $merged[$c1], 'A course in two sessions lists both.');
        $this->assertSame([$s1], $merged[$c2]);
    }

    public function testStudentIdsOfASessionFilterAndSearch(): void
    {
        $s = $this->rawSession('stu');
        $a = $this->rawUser('sa');
        $b = $this->rawUser('sb');
        $coach = $this->rawUser('sc', 'ROLE_TEACHER', 1);
        $outside = $this->rawUser('so');
        $this->rel($s, $a, Session::STUDENT);
        $this->rel($s, $b, Session::STUDENT);
        $this->rel($s, $coach, Session::GENERAL_COACH);

        $this->assertSame([$a => $a, $b => $b], SessionManager::getSessionStudentUserIds($s), 'Students only, keyed by id.');
        $this->assertSame([], SessionManager::getSessionStudentUserIds(0));
        $this->assertSame([], SessionManager::getSessionStudentUserIds(-5));
        $this->assertSame([$b], SessionManager::filterUsersSubscribedToSession($s, [(string) $b, $outside, $coach, 0, $b]));
        $this->assertSame([], SessionManager::filterUsersSubscribedToSession($s, []));
        $this->assertSame([], SessionManager::filterUsersSubscribedToSession(0, [$a]));
    }

    public function testCourseSubscriptionSearchAndCounts(): void
    {
        $s = $this->rawSession('srch');
        $other = $this->rawSession('srch2');
        $c = $this->rawCourse('srch');
        $u = $this->rawUser('srchu');
        $v = $this->rawUser('srchv');
        $this->sessionCourse($s, $c);
        $this->sessionCourse($other, $c);
        $this->courseUser($s, $c, $u, Session::STUDENT);
        $this->courseUser($other, $c, $u, Session::STUDENT);
        $this->courseUser($s, $c, $v, Session::COURSE_COACH);
        foreach ([$u, $v] as $id) {
            $this->rel($s, $id, Session::STUDENT);
        }
        $this->rel($s, $this->rawUser('srchd'), Session::HRM);
        $this->conn()->executeStatement('INSERT INTO access_url_rel_user (access_url_id, user_id) SELECT 1, user_id FROM session_rel_user WHERE session_id = ? AND user_id NOT IN (SELECT user_id FROM access_url_rel_user)', [$s]);

        $this->assertSame([], SessionManager::searchCourseInSessionsFromUser(0, $c));
        $this->assertSame([], SessionManager::searchCourseInSessionsFromUser($u, 0));
        $rows = SessionManager::searchCourseInSessionsFromUser($u, $c);
        $this->assertEqualsCanonicalizing([$s, $other], array_map('intval', array_column($rows, 'session_id')));
        $this->assertCount(1, SessionManager::searchCourseInSessionsFromUser($v, $c));

        $this->fresh();
        $sessionEntity = $this->sessionEntity($s);
        $course = $this->getEntityManager()->getRepository(Course::class)->find($c);
        $this->assertSame(
            2,
            (int) SessionManager::getCountUsersInCourseSession($course, $sessionEntity),
            'Students and coaches of the course who are also subscribed to the session, never the HR managers.'
        );
    }

    public function testAllUserIdsInASessionCoverCoachesStudentsAndGeneralCoaches(): void
    {
        $s = $this->rawSession('allu');
        $c = $this->rawCourse('allu');
        $stud = $this->rawUser('au_s');
        $cc = $this->rawUser('au_c', 'ROLE_TEACHER', 1);
        $gc = $this->rawUser('au_g', 'ROLE_TEACHER', 1);
        $this->sessionCourse($s, $c);
        $this->courseUser($s, $c, $stud, Session::STUDENT);
        $this->courseUser($s, $c, $cc, Session::COURSE_COACH);
        $this->rel($s, $gc, Session::GENERAL_COACH);
        $this->rel($s, $this->rawUser('au_hrm', 'ROLE_HR', 1), Session::HRM);

        $ids = array_map('intval', array_column(SessionManager::getAllUserIdsInSession($s), 'user_id'));

        $this->assertSame([$cc, $stud, $gc], $ids, 'Course coaches, then students, then the general coaches.');
        $this->assertSame([], SessionManager::getAllUserIdsInSession(99999999));
    }

    public function testSessionAdminsAndCoachNamesOfASession(): void
    {
        $s = $this->rawSession('adm');
        $admin = $this->rawUser('adm_a', 'ROLE_SESSION_MANAGER', 3);
        $gc = $this->rawUser('adm_g', 'ROLE_TEACHER', 1);
        $this->rel($s, $admin, Session::SESSION_ADMIN);
        $this->rel($s, $gc, Session::GENERAL_COACH);
        $this->fresh();

        $this->assertTrue(SessionManager::sessionHasSessionAdmin($s, $admin));
        $this->assertFalse(SessionManager::sessionHasSessionAdmin($s, $gc), 'A coach is not a session admin.');
        $this->assertSame([$gc], SessionManager::getGeneralCoachesIdForSession($s));
        $this->assertCount(1, SessionManager::getGeneralCoachesNamesForSession($s));
    }

    public function testCoachesOfACourseSessionAndCountUsersByLanguage(): void
    {
        $s = $this->rawSession('lang');
        $c = $this->rawCourse('lang');
        $coach = $this->rawUser('lang_c', 'ROLE_TEACHER', 1);
        $a = $this->rawUser('lang_a');
        $b = $this->rawUser('lang_b');
        $this->sessionCourse($s, $c);
        $this->courseUser($s, $c, $coach, Session::COURSE_COACH);
        $this->courseUser($s, $c, $a, Session::STUDENT);
        $this->conn()->executeStatement("UPDATE user SET locale = 'fr_FR' WHERE id IN (?, ?)", [$a, $b]);
        $this->conn()->executeStatement("UPDATE user SET locale = 'en_US' WHERE id = ?", [$coach]);
        $this->rel($s, $a, Session::STUDENT);
        $this->rel($s, $b, Session::STUDENT);

        $this->assertSame([$coach], array_map('intval', SessionManager::getCoachesBySession($s)));
        $this->assertSame([$coach], array_map('intval', SessionManager::getCoachesByCourseSession($s, $c)));
        $this->assertSame([], SessionManager::getCoachesByCourseSession($s, 99999999));
        $this->assertStringContainsString('Lastlang_c', SessionManager::getCoachesByCourseSessionToString($s, $c) ?: '');
        $langs = SessionManager::getCountUsersLangBySession($s, 0, Session::STUDENT);
        $this->assertContains('FR_FR 2', $langs);
        $this->assertSame([], SessionManager::getCountUsersLangBySession($s, 0, Session::HRM));
        $all = SessionManager::getCountUsersLangBySession($s);
        $this->assertContains('FR_FR 2', $all);
    }

    public function testClearSessionRefPromotion(): void
    {
        $this->assertFalse(SessionManager::clear_session_ref_promotion(0));
        $this->assertFalse(SessionManager::clear_session_ref_promotion(99999999), 'No row changed means false.');
    }

    // ---------------------------------------------------------------- jqGrid-bound admin list ("complete")

    private function completeFixture(): array
    {
        $coach = $this->rawUser('cmp_coach', 'ROLE_TEACHER', 1);
        $student = $this->rawUser('cmp_stud');
        $full = $this->rawSession('cmp_full');
        $noCourse = $this->rawSession('cmp_nocourse');
        $noCoach = $this->rawSession('cmp_nocoach');
        $c = $this->rawCourse('cmp');
        foreach ([$full, $noCoach] as $id) {
            $this->sessionCourse($id, $c);
            $this->courseUser($id, $c, $student, Session::STUDENT);
        }
        $this->rel($full, $coach, Session::GENERAL_COACH);
        $this->rel($noCourse, $coach, Session::GENERAL_COACH);

        return [$full, $noCourse, $noCoach, $coach, $c];
    }

    public function testCompleteListShowsSessionsWithAGeneralCoachWithOneRowPerSession(): void
    {
        [$full, $noCourse, $noCoach] = $this->completeFixture();

        $rows = SessionManager::get_sessions_admin_complete(['where' => 's.id IN ('.$full.', '.$noCourse.', '.$noCoach.')', 'order' => 's.title ASC']);

        $this->assertSame([$full, $noCourse], array_map('intval', array_keys($rows)), 'A session without general coach is not listed; one without course is.');
        $row = $rows[$full];
        $this->assertStringContainsString('resume_session.php?id_session='.$full, $row['title']);
        $this->assertStringContainsString('s2_cmp_full', $row['title']);
        $this->assertStringContainsString('user_information.php?user_id=', $row['coach_name']);
        $this->assertSame(get_lang('Visible') === $row['visibility'] || get_lang('Read only') === $row['visibility'], true);
        $this->assertSame($row['course_title'], 's2_cmp');
        $this->assertNull($rows[$noCourse]['course_title']);
    }

    public function testCompleteCountCountsTheRowsOfSessionsWithACoachAndCourseUsers(): void
    {
        [$full, $noCourse, $noCoach] = $this->completeFixture();
        $where = ['where' => 's.id IN ('.$full.', '.$noCourse.', '.$noCoach.')'];

        // PINNED QUIRK: the count joins session_rel_course_rel_user (a session needs users in a course) while the
        // list does not, so the two disagree: the list shows 2 sessions, the count 1.
        $this->assertSame(1, (int) SessionManager::get_count_admin_complete($where));
        $this->assertFalse(SessionManager::get_count_admin_complete('not an array'));
        $this->assertFalse(SessionManager::get_sessions_admin_complete('not an array'));
    }

    public function testCompleteListHonoursLimitAndOrder(): void
    {
        [$full, $noCourse] = $this->completeFixture();
        $where = 's.id IN ('.$full.', '.$noCourse.')';

        $desc = SessionManager::get_sessions_admin_complete(['where' => $where, 'order' => 's.title DESC']);
        $this->assertSame([$noCourse, $full], array_map('intval', array_keys($desc)));
        $this->assertCount(1, SessionManager::get_sessions_admin_complete(['where' => $where, 'order' => 's.title ASC', 'limit' => '1 , 1']));
    }

    public function testCompleteListOfASessionAdminFollowsTheManageAllSetting(): void
    {
        [$full, $noCourse] = $this->completeFixture();
        $sa = $this->rawUser('cmp_sa', 'ROLE_SESSION_MANAGER', 3);
        $this->rel($noCourse, $sa, Session::SESSION_ADMIN);
        $where = ['where' => 's.id IN ('.$full.', '.$noCourse.')', 'order' => 's.title ASC'];

        $this->setSetting('session.allow_session_admins_to_manage_all_sessions', 'false');
        $this->loginAs($sa);
        $mine = array_map('intval', array_keys(SessionManager::get_sessions_admin_complete($where)));
        $this->setSetting('session.allow_session_admins_to_manage_all_sessions', 'true');
        $this->loginAs($sa);
        $all = array_map('intval', array_keys(SessionManager::get_sessions_admin_complete($where)));
        $this->loginAs($this->getAdmin()->getId());

        $this->assertSame([$noCourse], $mine, 'A session admin only gets the sessions he administers.');
        $this->assertSame([$full, $noCourse], $all, 'With manage-all he gets those with a general coach, like everybody else.');
    }

    public function testCompleteCountForASessionAdmin(): void
    {
        [$full, $noCourse] = $this->completeFixture();
        $sa = $this->rawUser('cmp_sa2', 'ROLE_SESSION_MANAGER', 3);
        $this->rel($full, $sa, Session::SESSION_ADMIN);
        $this->setSetting('session.allow_session_admins_to_manage_all_sessions', 'false');
        $this->loginAs($sa);

        $count = SessionManager::get_count_admin_complete(['where' => 's.id IN ('.$full.', '.$noCourse.')']);
        $this->loginAs($this->getAdmin()->getId());

        $this->assertSame(1, (int) $count);
    }

    // ---------------------------------------------------------------- formatSessionsAdminForGrid

    public function testGridFormattingOfTheAdminList(): void
    {
        $cat = SessionManager::create_category_session('s2_grid_cat', 2030, 1, 1, 0, 0, 0);
        $a = $this->rawSession('grid_a', ['category' => $cat, 'visibility' => Session::INVISIBLE, 'status' => Session::STATUS_PLANNED]);
        $b = $this->rawSession('grid_b', ['start' => '2020-01-01 00:00:00', 'end' => '2020-02-01 00:00:00', 'visibility' => Session::READ_ONLY]);
        $where = ['where' => 's.id IN ('.$a.', '.$b.')', 'order' => 's.title ASC'];

        $this->assertSame(2, SessionManager::formatSessionsAdminForGrid($where, true));
        $rows = SessionManager::formatSessionsAdminForGrid($where);

        $this->assertCount(2, $rows);
        $this->assertStringContainsString('resume_session.php?id_session='.$a, $rows[0]['title']);
        $this->assertSame('s2_grid_cat', $rows[0]['category_name']);
        $this->assertSame('', $rows[1]['category_name']);
        $this->assertSame(api_ucfirst(get_lang('Invisible')), $rows[0]['visibility']);
        $this->assertSame(get_lang('Read only'), $rows[1]['visibility']);
        $this->assertSame(get_lang('Planned'), $rows[0]['status'], 'The numeric status is replaced by its label.');
    }

    public function testGridUserColumnShowsTheStudentCountAndTheLanguageTooltip(): void
    {
        $s = $this->rawSession('grid_users');
        $u = $this->rawUser('gu1');
        $this->rel($s, $u, Session::STUDENT);
        $this->rel($s, $this->rawUser('gu2', 'ROLE_TEACHER', 1), Session::GENERAL_COACH);
        $this->conn()->executeStatement("UPDATE user SET locale = 'es' WHERE id = ?", [$u]);
        $columns = ['column_model' => [['name' => 'users']]];

        $rows = SessionManager::formatSessionsAdminForGrid(['where' => 's.id = '.$s], false, $columns);

        $this->assertSame(1, (int) strip_tags($rows[0]['users']), 'The coach is not counted, only students.');
        $this->assertStringContainsString('title="ES 1"', $rows[0]['users']);
        $this->assertSame('', $rows[0]['teachers']);
    }

    // ---------------------------------------------------------------- deleting sessions and categories

    private function deleteAs(int $userId, int $sessionId): string
    {
        $this->loginAs($userId);

        try {
            $result = SessionManager::delete($sessionId);
            $outcome = $result ? 'deleted' : 'refused';
        } catch (NotAllowedException) {
            $outcome = 'denied';
        } finally {
            $this->loginAs($this->getAdmin()->getId());
        }

        return $outcome.':'.(int) $this->one('SELECT COUNT(*) FROM session WHERE id = ?', [$sessionId]);
    }

    public function testOnlyThoseWhoManageASessionMayDeleteIt(): void
    {
        // Security: delete() is reached from the session category page, open to session admins.
        $this->setSetting('session.allow_session_admins_to_manage_all_sessions', 'false');
        $this->setSetting('session.allow_teachers_to_create_sessions', 'true');
        $owned = $this->rawSession('del_owned');
        $foreign = $this->rawSession('del_foreign');
        $coached = $this->rawSession('del_coached');
        $stranger = $this->rawSession('del_stranger');
        $owner = $this->rawUser('del_owner', 'ROLE_SESSION_MANAGER', 3);
        $other = $this->rawUser('del_other', 'ROLE_SESSION_MANAGER', 3);
        $teacher = $this->rawUser('del_teacher', 'ROLE_TEACHER', 1);
        $student = $this->rawUser('del_student');
        $this->rel($owned, $owner, Session::SESSION_ADMIN);
        $this->rel($foreign, $other, Session::SESSION_ADMIN);
        $this->rel($coached, $teacher, Session::GENERAL_COACH);

        $this->assertSame('denied:1', $this->deleteAs($owner, $foreign), 'A session admin cannot delete the session of another one.');
        $this->assertSame('denied:1', $this->deleteAs($teacher, $stranger), 'A teacher cannot delete a session he does not coach.');
        $this->assertSame('denied:1', $this->deleteAs($student, $stranger));
        $this->assertSame('deleted:0', $this->deleteAs($owner, $owned), 'The session admin of the session can.');
        $this->assertSame('deleted:0', $this->deleteAs($teacher, $coached), 'The general coach of a session may delete it when teachers may create sessions.');
        $this->assertSame('deleted:0', $this->deleteAs($this->getAdmin()->getId(), $stranger), 'A platform admin always can.');

        $this->setSetting('session.allow_session_admins_to_manage_all_sessions', 'true');
        $this->assertSame('deleted:0', $this->deleteAs($owner, $foreign), 'With manage-all a session admin can delete any session.');
    }

    public function testDeletingACategoryKeepsOrDeletesItsSessions(): void
    {
        $keepCat = SessionManager::create_category_session('s2_cat_keep', 2030, 1, 1, 0, 0, 0);
        $dropCat = SessionManager::create_category_session('s2_cat_drop', 2030, 1, 1, 0, 0, 0);
        $kept = $this->rawSession('cat_kept', ['category' => $keepCat]);
        $dropped = $this->rawSession('cat_dropped', ['category' => $dropCat]);

        $this->assertTrue(SessionManager::delete_session_category($keepCat));
        $this->assertSame(0, (int) $this->one('SELECT COUNT(*) FROM session_category WHERE id = ?', [$keepCat]));
        $this->assertNull($this->one('SELECT session_category_id FROM session WHERE id = ?', [$kept]), 'Without the flag the sessions only lose their category.');

        $this->assertTrue(SessionManager::delete_session_category([$dropCat], true));
        $this->assertSame(0, (int) $this->one('SELECT COUNT(*) FROM session_category WHERE id = ?', [$dropCat]));
        $this->assertSame(0, (int) $this->one('SELECT COUNT(*) FROM session WHERE id = ?', [$dropped]), 'With the flag the sessions are deleted too.');
        $this->assertSame(1, (int) $this->one('SELECT COUNT(*) FROM session WHERE id = ?', [$kept]));
    }

    public function testParentSessionListIsOrderedByTitle(): void
    {
        $b = $this->rawSession('par_b');
        $a = $this->rawSession('par_a');

        $list = SessionManager::getListOfParentSessions();

        $this->assertSame('s2_par_a', $list[$a]);
        $this->assertLessThan(array_search($b, array_keys($list)), array_search($a, array_keys($list)));
    }
}
