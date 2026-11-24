<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Legacy;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\UserRelUser;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use ChamiloSession;
use Database;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session as HttpSession;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use UserManager;

/**
 * Characterization tests for the UserManager methods whose SQL no other test executed: canDeleteUser,
 * loginDelete, the session branch of get_user_id_of_course_admin_or_session_admin and the multi-URL filter of
 * the coached session courses in get_personal_session_course_list. Users and sessions are tagged "us_".
 */
class UserManagerTier2CharacterizationTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    private int $seq = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $httpSession = new HttpSession(new MockArraySessionStorage());
        $request = new Request();
        $request->setSession($httpSession);
        self::getContainer()->get('request_stack')->push($request);
        Container::setContainer(self::getContainer());
        Container::setSession($httpSession);
        Container::setRequest($request);
        Database::setManager($this->getEntityManager());
        Database::setConnection($this->getEntityManager()->getConnection());
        $_GET = [];
        // The test database may carry other values; the default listing order is the one under test.
    }

    protected function tearDown(): void
    {
        $_GET = [];
        parent::tearDown();
    }

    // ---------------------------------------------------------------- helpers

    private function conn(): Connection
    {
        return $this->getEntityManager()->getConnection();
    }

    /**
     * Legacy code runs with an empty identity map on every request.
     */
    private function fresh(): void
    {
        $this->getEntityManager()->clear();
    }

    private function loginAs(int $userId): void
    {
        $user = $this->getEntityManager()->getRepository(User::class)->find($userId);
        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
        self::getContainer()->get('security.token_storage')->setToken($token);
        ChamiloSession::write('_user', ['id' => $userId]);
        $this->fresh();
    }

    private function rawUser(string $suffix, int $status = 5): int
    {
        $name = 'us_'.$suffix.'_'.getmypid().'_'.(++$this->seq);
        $this->conn()->executeStatement(
            'INSERT INTO user (username, username_canonical, email, email_canonical, firstname, lastname, locale,
                password, timezone, locked, expired, credentials_expired, salt, roles, status, active, uuid,
                mfa_enabled, created_at, updated_at, official_code)
             VALUES (?, ?, ?, ?, ?, ?, \'en_US\', \'x\', \'UTC\', 0, 0, 0, \'x\', \'[]\', ?, 1,
                UNHEX(REPLACE(UUID(), \'-\', \'\')), 0, NOW(), NOW(), \'\')',
            [$name, $name, $name.'@example.com', $name.'@example.com', 'First'.$suffix, 'Last'.$suffix, $status]
        );
        $id = (int) $this->conn()->lastInsertId();
        $this->conn()->executeStatement('INSERT INTO access_url_rel_user (access_url_id, user_id) VALUES (1, ?)', [$id]);

        return $id;
    }

    private function rawSession(string $title, ?string $start = null, ?string $end = null, array $extra = []): int
    {
        $this->conn()->executeStatement(
            'INSERT INTO session (title, nbr_courses, nbr_users, nbr_classes, visibility, status,
                access_start_date, access_end_date, duration, session_category_id, position)
             VALUES (?, 0, 0, 0, 1, 0, ?, ?, ?, ?, ?)',
            [
                'us_'.$title,
                $start,
                $end,
                $extra['duration'] ?? null,
                $extra['category'] ?? null,
                $extra['position'] ?? 0,
            ]
        );
        $id = (int) $this->conn()->lastInsertId();
        if (!($extra['no_url'] ?? false)) {
            $this->conn()->executeStatement('INSERT INTO access_url_rel_session (session_id, access_url_id) VALUES (?, 1)', [$id]);
        }

        return $id;
    }

    private function sessionCourse(int $sid, int $cid, int $position = 0): void
    {
        $this->conn()->executeStatement(
            'INSERT INTO session_rel_course (session_id, c_id, position, nbr_users) VALUES (?, ?, ?, 0)',
            [$sid, $cid, $position]
        );
    }

    private function sessionUser(int $sid, int $uid, int $relationType): void
    {
        $this->conn()->executeStatement(
            'INSERT INTO session_rel_user (session_id, user_id, relation_type, duration, registered_at)
             VALUES (?, ?, ?, 0, \'2020-01-01 00:00:00\')',
            [$sid, $uid, $relationType]
        );
    }

    private function sessionCourseUser(int $sid, int $cid, int $uid, int $status = 0): void
    {
        $this->conn()->executeStatement(
            'INSERT INTO session_rel_course_rel_user (user_id, session_id, c_id, status, visibility, legal_agreement, progress)
             VALUES (?, ?, ?, ?, 1, 0, 0)',
            [$uid, $sid, $cid, $status]
        );
    }

    private function courseUser(int $uid, int $cid, int $status = 5, int $relationType = 0, int $sort = 0): void
    {
        $this->conn()->executeStatement(
            'INSERT INTO course_rel_user (user_id, c_id, relation_type, status, is_tutor, sort, user_course_cat, legal_agreement, progress)
             VALUES (?, ?, ?, ?, 0, ?, 0, 0, 0)',
            [$uid, $cid, $relationType, $status, $sort]
        );
    }

    private function enableMultiUrl(): int
    {
        // AccessUrlHelper::isMultiple() is "more than one access_url row".
        $this->conn()->executeStatement(
            'INSERT INTO access_url (url, active, description, created_by, lft, rgt, lvl)
             VALUES (?, 1, \'\', 1, 1, 2, 0)',
            ['http://us-second-url.invalid/']
        );
        $this->fresh();

        return (int) $this->conn()->lastInsertId();
    }

    private function fixedTimes(): array
    {
        return [
            'past_start' => '2001-01-01 00:00:00',
            'future_end' => '2091-01-01 00:00:00',
        ];
    }

    // ---------------------------------------------------------------- tests

    public function testCanDeleteUserRefusesTheOnlyTeacherOfACourse(): void
    {
        $teacher = $this->rawUser('cd1', 1);
        $other = $this->rawUser('cd2', 1);
        $student = $this->rawUser('cd3');
        $cid = 1;
        $this->conn()->executeStatement('DELETE FROM course_rel_user WHERE c_id = ? AND status = 1', [$cid]);
        $this->courseUser($teacher, $cid, 1);
        $this->courseUser($student, $cid, 5);
        $this->fresh();

        $this->assertFalse(UserManager::canDeleteUser($teacher), 'Sole teacher of a course cannot be deleted.');
        $this->assertTrue(UserManager::canDeleteUser($student), 'A student can.');
        $this->assertTrue(UserManager::canDeleteUser($other), 'A user without courses can.');

        $this->courseUser($other, $cid, 1);
        $this->fresh();
        $this->assertTrue(UserManager::canDeleteUser($teacher), 'With a second teacher the first one can go.');

        $this->assertFalse(UserManager::canDeleteUser(0));
        $this->assertFalse(UserManager::canDeleteUser('abc'));
    }

    public function testLoginDeleteRemovesOnlyTheOnlineRowsOfThatUser(): void
    {
        $a = $this->rawUser('ld1');
        $b = $this->rawUser('ld2');
        foreach ([$a, $b] as $uid) {
            $this->conn()->executeStatement(
                'INSERT INTO track_e_online (login_user_id, login_date, user_ip, c_id, session_id, access_url_id)
                 VALUES (?, NOW(), \'127.0.0.1\', 0, 0, 1)',
                [$uid]
            );
        }
        $count = fn (int $uid): int => (int) $this->conn()->fetchOne('SELECT COUNT(*) FROM track_e_online WHERE login_user_id = ?', [$uid]);
        $this->assertSame(1, $count($a));

        UserManager::loginDelete($a);

        $this->assertSame(0, $count($a));
        $this->assertSame(1, $count($b), 'Other users stay online.');
        UserManager::loginDelete($b);
        $this->assertSame(0, $count($b));
    }

    public function testCourseAdminInASessionIsTheLowestCourseCoachUserId(): void
    {
        $t = $this->fixedTimes();
        $low = $this->rawUser('sa1', 1);
        $high = $this->rawUser('sa2', 1);
        $student = $this->rawUser('sa3');
        $s = $this->rawSession('sa', $t['past_start'], $t['future_end']);
        $this->sessionCourse($s, 1, 1);
        // The student has the lowest id of the three rows below but is not a coach.
        $this->sessionCourseUser($s, 1, $student, 0);
        $this->sessionCourseUser($s, 1, $high, 2);
        $this->sessionCourseUser($s, 1, $low, 2);
        $this->assertGreaterThan($low, $high);
        $info = ['real_id' => 1, 'id' => 1];

        ChamiloSession::write('sid', $s);
        $this->assertSame($low, UserManager::get_user_id_of_course_admin_or_session_admin($info));

        // The query filters on the course only, never on the session id: a session without coach of its own still
        // gets the lowest course coach of any session (pinned as is, not endorsed).
        $s2 = $this->rawSession('sa_empty', $t['past_start'], $t['future_end']);
        ChamiloSession::write('sid', $s2);
        $this->assertSame($low, UserManager::get_user_id_of_course_admin_or_session_admin($info));

        // A course nobody coaches in any session: 0.
        $this->assertSame(0, UserManager::get_user_id_of_course_admin_or_session_admin(['real_id' => 2, 'id' => 2]));

        ChamiloSession::write('sid', 0);
        $this->assertSame(0, UserManager::get_user_id_of_course_admin_or_session_admin([]));
    }

    public function testPersonalSessionCourseListMultiUrlDropsCoachedCoursesOutsideTheUrl(): void
    {
        $t = $this->fixedTimes();
        $coach = $this->rawUser('pm1', 1);
        $s = $this->rawSession('pm1', $t['past_start'], $t['future_end']);
        $this->sessionCourse($s, 1, 1);
        $this->sessionCourse($s, 2, 2);
        // General coach only: the courses can only come from the coached-sessions branch, not from a subscription.
        $this->sessionUser($s, $coach, 3);
        $this->conn()->executeStatement('DELETE FROM access_url_rel_course WHERE c_id = 2 AND access_url_id = 1');
        $this->loginAs(1);

        $codes = array_column(UserManager::get_personal_session_course_list($coach), 'code');
        sort($codes);
        $this->assertSame(['AIACT', 'USINGCHAMILO'], $codes, 'Single URL: the URL table is ignored.');

        $this->enableMultiUrl();
        $this->loginAs(1);
        $list = UserManager::get_personal_session_course_list($coach);
        $this->assertSame(["$s - AIACT"], array_keys($list), 'Multi URL: only the courses of the current URL.');
    }

    public function testPersonalSessionCourseListMultiUrlFiltersSubscribedSessionCoursesAndOrdersThemByTitle(): void
    {
        $t = $this->fixedTimes();
        $u = $this->rawUser('pm2');
        $s = $this->rawSession('pm2', $t['past_start'], $t['future_end']);
        $this->sessionCourse($s, 1, 1);
        $this->sessionCourse($s, 2, 2);
        $this->sessionUser($s, $u, 0);
        $this->sessionCourseUser($s, 1, $u, 0);
        $this->sessionCourseUser($s, 2, $u, 0);
        $titles = $this->conn()->fetchAllKeyValue('SELECT code, title FROM course WHERE id IN (1, 2)');
        asort($titles);
        $expected = array_map(fn (string $code): string => "$s - $code", array_keys($titles));

        $this->assertSame($expected, array_keys(UserManager::get_personal_session_course_list($u)), 'ORDER BY course title.');

        $this->conn()->executeStatement('DELETE FROM access_url_rel_course WHERE c_id = 2 AND access_url_id = 1');
        $this->enableMultiUrl();
        $this->fresh();
        $this->assertSame(["$s - AIACT"], array_keys(UserManager::get_personal_session_course_list($u)));
    }

    public function testPersonalSessionCourseListListsTheSessionsWhereTheUserIsCourseCoach(): void
    {
        $t = $this->fixedTimes();
        $coach = $this->rawUser('pm3', 1);
        $student = $this->rawUser('pm3s');
        $s = $this->rawSession('pm3', $t['past_start'], $t['future_end']);
        $this->sessionCourse($s, 1, 1);
        // The session is reached through somebody else's subscription row: the course-coach list adds it for $coach.
        $this->sessionUser($s, $student, 0);
        $this->sessionCourseUser($s, 1, $coach, 2);
        $other = $this->rawSession('pm3_other', $t['past_start'], $t['future_end']);
        $this->sessionCourse($other, 2, 1);
        $this->sessionUser($other, $student, 0);
        $this->sessionCourseUser($other, 2, $coach, 0);
        $this->loginAs(1);

        $keys = array_keys(UserManager::get_personal_session_course_list($coach));
        $this->assertContains("$s - AIACT", $keys);
        $this->assertNotContains("$other - USINGCHAMILO", $keys, 'Only sessions where the user is course coach.');
    }

    public function testGetExtraFieldsSortsFiltersAndListsOptionsInOrder(): void
    {
        $insertField = function (string $variable, int $order, int $visible = 1, int $filter = 0): int {
            $this->conn()->executeStatement(
                'INSERT INTO extra_field (item_type, value_type, variable, display_text, default_value, field_order,
                    visible_to_self, visible_to_others, changeable, filter, created_at, auto_remove)
                 VALUES (1, 4, ?, ?, NULL, ?, ?, 1, 1, ?, NOW(), 0)',
                [$variable, $variable, $order, $visible, $filter]
            );

            return (int) $this->conn()->lastInsertId();
        };
        // x is created first (lower id) but sorts last by variable and by field order: y is the other way round.
        $x = $insertField('zz_um_ef_b', 900002, 1, 1);
        $y = $insertField('zz_um_ef_a', 900001, 0, 0);
        foreach ([[$x, 'second', 2], [$x, 'first', 1], [$x, 'third', 3]] as [$field, $value, $order]) {
            $this->conn()->executeStatement(
                'INSERT INTO extra_field_options (field_id, option_value, display_text, option_order) VALUES (?, ?, ?, ?)',
                [$field, $value, $value, $order]
            );
        }
        $position = fn (array $ids, int $id): false|int => array_search($id, $ids);

        // Column 0 is the id, 1 the variable, 5 the field order.
        $ids = array_keys(UserManager::get_extra_fields(0, 0, 1, 'ASC'));
        $this->assertLessThan($position($ids, $x), $position($ids, $y), 'Variable ASC: a before b.');
        $ids = array_keys(UserManager::get_extra_fields(0, 0, 1, 'DESC'));
        $this->assertLessThan($position($ids, $y), $position($ids, $x), 'Variable DESC: b before a.');
        $ids = array_keys(UserManager::get_extra_fields(0, 0, 5, 'ASC'));
        $this->assertLessThan($position($ids, $x), $position($ids, $y), 'Field order ASC.');
        $ids = array_keys(UserManager::get_extra_fields(0, 0, 5, 'DESC'));
        $this->assertLessThan($position($ids, $y), $position($ids, $x), 'Field order DESC.');
        $ids = array_keys(UserManager::get_extra_fields(0, 0, 0, 'DESC'));
        $this->assertLessThan($position($ids, $x), $position($ids, $y), 'Id DESC: the later field first.');

        $fields = UserManager::get_extra_fields(0, 0, 5, 'ASC');
        $this->assertSame(['first', 'second', 'third'], array_column(array_values($fields[$x][9]), 1), 'Options by option_order.');
        $this->assertSame([], $fields[$y][9]);
        $this->assertSame([1, 0], [$fields[$x][6], $fields[$y][6]], 'visible_to_self comes back as the stored integer.');
        $this->assertSame([1, 0], [$fields[$x][8], $fields[$y][8]], 'filter likewise.');

        $visibleOnly = UserManager::get_extra_fields(0, 0, 5, 'ASC', false);
        $this->assertArrayHasKey($x, $visibleOnly);
        $this->assertArrayNotHasKey($y, $visibleOnly, 'Invisible fields are dropped when not all visibility is wanted.');

        $filtered = UserManager::get_extra_fields(0, 0, 5, 'ASC', true, 1);
        $this->assertArrayHasKey($x, $filtered);
        $this->assertArrayNotHasKey($y, $filtered);
        $unfiltered = UserManager::get_extra_fields(0, 0, 5, 'ASC', true, 0);
        $this->assertArrayHasKey($y, $unfiltered);
        $this->assertArrayNotHasKey($x, $unfiltered);
    }

    public function testUserListsAndCountsAreRestrictedToTheAccessUrlWhenThereSeveralUrls(): void
    {
        $inUrl = $this->rawUser('mu1', 1);
        $elsewhere = $this->rawUser('mu2', 1);
        $secondUrl = $this->enableMultiUrl();
        $this->conn()->executeStatement('UPDATE access_url_rel_user SET access_url_id = ? WHERE user_id = ?', [$secondUrl, $elsewhere]);
        $this->conn()->executeStatement('UPDATE user SET roles = ?, official_code = ? WHERE id IN (?, ?)', ['["ROLE_ADMIN"]', 'us_mu_code', $inUrl, $elsewhere]);
        $this->fresh();

        // Current URL (1): only the user attached to it.
        $ids = fn (array $rows): array => array_map('intval', array_column($rows, 'id'));
        $this->assertSame([$inUrl], $ids(UserManager::getUserListLike(['official_code' => 'us_mu_code'])));
        $this->assertSame([$inUrl], $ids(UserManager::getUserListExtraConditions(['official_code' => 'us_mu_code'])));
        $this->assertSame([$elsewhere], $ids(UserManager::getUserListExtraConditions(['official_code' => 'us_mu_code'], [], false, false, $secondUrl)));
        $this->assertSame([$inUrl], $ids(UserManager::get_user_list(['official_code' => 'us_mu_code'])));
        $this->assertSame([$elsewhere], $ids(UserManager::get_user_list(['official_code' => 'us_mu_code'], [], false, false, $secondUrl)));
        $this->assertEquals(1, UserManager::getUserListExtraConditions(['official_code' => 'us_mu_code'], [], false, false, null, '', true));

        $inFirstUrl = UserManager::get_number_of_users(0, 1);
        $inSecondUrl = UserManager::get_number_of_users(0, $secondUrl);
        $this->assertSame(1, $inSecondUrl, 'Only the moved user belongs to the second URL.');
        $this->assertGreaterThan($inSecondUrl, $inFirstUrl);

        $admins = UserManager::get_all_administrators();
        $this->assertArrayHasKey($inUrl, $admins);
        $this->assertArrayNotHasKey($elsewhere, $admins, 'Administrators of other URLs are not listed.');
    }

    public function testGetUsersByNameMatchesPrefixesOnly(): void
    {
        $a = $this->rawUser('nm1');
        $b = $this->rawUser('nm2');
        $this->conn()->executeStatement('UPDATE user SET firstname = ?, lastname = ? WHERE id = ?', ['Zebulon', 'Quark', $a]);
        $this->conn()->executeStatement('UPDATE user SET firstname = ?, lastname = ? WHERE id = ?', ['AZebulon', 'Quark', $b]);

        $ids = array_map(static fn (object $user): int => (int) $user->id, UserManager::getUsersByName('Zeb', 'Qua'));
        $this->assertSame([$a], $ids, 'The name must START with the given text: "AZebulon" does not match "Zeb".');
        $ids = array_map(static fn (object $user): int => (int) $user->id, UserManager::getUsersByName('Zeb', 'ark'));
        $this->assertSame([], $ids, 'Neither does a last name that merely contains the text.');
    }

    public function testCountUsersWhoFinishedCoursesInSessionsListsSessionsOfACourseByTitle(): void
    {
        $t = $this->fixedTimes();
        $u = $this->rawUser('fs1');
        $zz = $this->rawSession('fs_zz', $t['past_start'], $t['future_end']);
        $aa = $this->rawSession('fs_aa', $t['past_start'], $t['future_end']);
        foreach ([$zz, $aa] as $sid) {
            $this->sessionCourse($sid, 1, 1);
            $this->sessionCourseUser($sid, 1, $u);
        }
        $this->fresh();

        $code = (string) $this->conn()->fetchOne('SELECT code FROM course WHERE id = 1');
        $keys = array_values(array_filter(
            array_keys(UserManager::countUsersWhoFinishedCoursesInSessions()),
            static fn (string $key): bool => str_contains($key, 'us_fs_')
        ));
        $this->assertSame(["$code (us_fs_aa)", "$code (us_fs_zz)"], $keys, 'ORDER BY course code, then session title.');
    }

    public function testGetHrmListFromUserIsOrderedByTheNameConventionOfTheLanguage(): void
    {
        $student = $this->rawUser('hl0');
        $h1 = $this->rawUser('hl1', 1);
        $h2 = $this->rawUser('hl2', 1);
        // First names and last names sort in opposite directions: h1 = Aaa Zzz, h2 = Bbb Yyy.
        $this->conn()->executeStatement('UPDATE user SET firstname = ?, lastname = ? WHERE id = ?', ['Aaa', 'Zzz', $h1]);
        $this->conn()->executeStatement('UPDATE user SET firstname = ?, lastname = ? WHERE id = ?', ['Bbb', 'Yyy', $h2]);
        foreach ([$h1, $h2] as $hrm) {
            $this->conn()->executeStatement(
                'INSERT INTO user_rel_user (user_id, friend_user_id, relation_type, created_at, updated_at)
                 VALUES (?, ?, ?, NOW(), NOW())',
                [$student, $hrm, UserRelUser::USER_RELATION_TYPE_RRHH]
            );
        }

        $expected = api_is_western_name_order() ? [$h1, $h2] : [$h2, $h1];
        $rows = UserManager::getHrmListFromUser($student);
        $this->assertSame($expected, array_map('intval', array_column($rows, 'id')));
        $this->assertSame(['id', 'username', 'firstname', 'lastname'], array_keys($rows[0]));
    }
}
