<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Legacy;

use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use Database;
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

    private function conn(): \Doctrine\DBAL\Connection
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

}
