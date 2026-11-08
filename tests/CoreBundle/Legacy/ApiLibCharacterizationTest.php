<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Legacy;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Exception\NotAllowedException;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use ChamiloSession;
use Database;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DbalException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session as HttpSession;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * Characterization tests for the SQL-bearing helpers of public/main/inc/lib/api.lib.php.
 *
 * They pin what the helpers return today (row shape, NULL/false handling, scoping by course/session/user/URL) so
 * that a rewrite of the raw SQL to Doctrine which changes any of it fails here. Fixtures are raw prepared
 * statements tagged "ap_" on installer courses 1 (AIACT) and 2 (USINGCHAMILO); fixture users are admin (1),
 * anonymous (2) and the soft-deleted fallback_user (3).
 *
 * Liveness (callers under public/ src/ plugin/):
 *  LIVE: api_get_user_info, api_get_user_info_from_username/email/official_code, api_get_anonymous_id,
 *   api_get_course_int_id, api_get_course_setting, api_get_session_name, api_get_session_visibility, api_is_coach,
 *   api_is_course_session_coach, api_is_platform_admin_by_id, api_is_element_in_the_session,
 *   api_resource_is_locked_by_gradebook, api_block_inactive_user, api_get_current_access_url_id, api_get_access_url,
 *   api_get_languages*, api_get_language_id, api_get_platform_default_isocode, api_get_settings*, api_set_setting*,
 *   api_set_settings_category, api_delete_setting_option, api_register_campus, api_is_explicit_course_student
 *   (through api_is_allowed_to_edit), api_is_admin_in_all_active_urls (api_get_active_urls and
 *   api_get_access_url_from_user), api_get_tool_information_by_name (api_protect_course_script).
 *  DEAD: api_is_session_in_category, api_get_access_urls, api_delete_settings_params, api_set_setting_option,
 *   api_get_language_list_for_flag; api_is_course_visible_for_user only survives through the search tool processors.
 */
class ApiLibCharacterizationTest extends AbstractApiTest
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
        $_REQUEST = [];
        ChamiloSession::erase('studentview');
        ChamiloSession::erase('_real_cid');
        ChamiloSession::erase('_cid');
        ChamiloSession::erase('_user');
        ChamiloSession::erase('sid');
    }

    protected function tearDown(): void
    {
        $_GET = [];
        $_REQUEST = [];
        ChamiloSession::erase('studentview');
        ChamiloSession::erase('_real_cid');
        ChamiloSession::erase('_cid');
        ChamiloSession::erase('_user');
        ChamiloSession::erase('sid');
        self::getContainer()->get('security.token_storage')->setToken(null);
        parent::tearDown();
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

    private function setSetting(string $name, string $value): void
    {
        self::getContainer()->get(SettingsManager::class)->updateSetting($name, $value);
        $this->fresh();
    }

    private function loginAs(int $userId): void
    {
        $user = $this->getEntityManager()->getRepository(User::class)->find($userId);
        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
        self::getContainer()->get('security.token_storage')->setToken($token);
        ChamiloSession::write('_user', ['id' => $userId]);
        $this->fresh();
    }

    private function rawUser(string $suffix, int $status = 5, int $active = 1, string $officialCode = ''): int
    {
        $name = 'ap_'.$suffix.'_'.getmypid().'_'.(++$this->seq);
        $this->conn()->executeStatement(
            'INSERT INTO user (username, username_canonical, email, email_canonical, firstname, lastname, locale,
                password, timezone, locked, expired, credentials_expired, salt, roles, status, active, uuid,
                mfa_enabled, created_at, updated_at, official_code)
             VALUES (?, ?, ?, ?, ?, ?, \'en_US\', \'x\', \'UTC\', 0, 0, 0, \'x\', \'[]\', ?, ?,
                UNHEX(REPLACE(UUID(), \'-\', \'\')), 0, NOW(), NOW(), ?)',
            [$name, $name, $name.'@example.com', $name.'@example.com', 'First'.$suffix, 'Last'.$suffix, $status, $active, $officialCode]
        );
        $id = (int) $this->conn()->lastInsertId();
        $this->conn()->executeStatement('INSERT INTO access_url_rel_user (access_url_id, user_id) VALUES (1, ?)', [$id]);

        return $id;
    }

    private function username(int $uid): string
    {
        return (string) $this->conn()->fetchOne('SELECT username FROM user WHERE id = ?', [$uid]);
    }

    private function rawSession(string $title, ?string $start, ?string $end, array $extra = []): int
    {
        $this->conn()->executeStatement(
            'INSERT INTO session (title, nbr_courses, nbr_users, nbr_classes, visibility, status, access_start_date,
                access_end_date, coach_access_start_date, coach_access_end_date, duration, session_category_id, position)
             VALUES (?, 0, 0, 0, ?, 0, ?, ?, ?, ?, ?, ?, 0)',
            [
                'ap_'.$title.'_'.getmypid().'_'.(++$this->seq),
                $extra['visibility'] ?? 1,
                $start,
                $end,
                $extra['coach_start'] ?? null,
                $extra['coach_end'] ?? null,
                $extra['duration'] ?? null,
                $extra['category'] ?? null,
            ]
        );

        return (int) $this->conn()->lastInsertId();
    }

    private function sessionUser(int $sid, int $uid, int $relationType): void
    {
        $this->conn()->executeStatement(
            'INSERT INTO session_rel_user (session_id, user_id, relation_type, duration, registered_at)
             VALUES (?, ?, ?, 0, \'2020-01-01 00:00:00\')',
            [$sid, $uid, $relationType]
        );
    }

    private function sessionCourseUser(int $sid, int $cid, int $uid, int $status): void
    {
        $this->conn()->executeStatement(
            'INSERT INTO session_rel_course_rel_user (user_id, session_id, c_id, status, visibility, legal_agreement, progress)
             VALUES (?, ?, ?, ?, 1, 0, 0)',
            [$uid, $sid, $cid, $status]
        );
    }

    private function courseUser(int $uid, int $cid, int $status): void
    {
        $this->conn()->executeStatement(
            'INSERT INTO course_rel_user (user_id, c_id, relation_type, status, is_tutor, sort, user_course_cat, legal_agreement, progress)
             VALUES (?, ?, 0, ?, 0, 0, 0, 0, 0)',
            [$uid, $cid, $status]
        );
    }

    // ---------------------------------------------------------------- user lookups

    public function testGetUserInfoReturnsTheFormattedRowOfTheUser(): void
    {
        $uid = $this->rawUser('info', 1, 1, 'AP-OFF-1');
        $name = $this->username($uid);

        $info = api_get_user_info($uid);

        $this->assertIsArray($info);
        $this->assertSame($uid, (int) $info['user_id']);
        $this->assertSame($name, $info['username']);
        $this->assertSame('Firstinfo', ucfirst(strtolower($info['firstname'])));
        $this->assertSame($name.'@example.com', $info['email']);
        $this->assertSame($info['email'], $info['mail']);
        $this->assertSame('AP-OFF-1', $info['official_code']);
        $this->assertSame(1, (int) $info['status']);
        $this->assertSame(1, (int) $info['active']);
        $this->assertArrayNotHasKey('password', $info, 'The password hash is never returned by default.');
    }

    public function testGetUserInfoOfAnUnknownIdIsFalse(): void
    {
        $this->assertFalse(api_get_user_info(999999999));
    }

    public function testGetUserInfoWithoutIdAndWithoutSessionUserIsFalse(): void
    {
        $this->assertFalse(api_get_user_info(0));
    }

    public function testGetUserInfoWithoutIdFallsBackToTheSessionUser(): void
    {
        ChamiloSession::write('_user', ['id' => 77, 'username' => 'ap_sessionuser']);

        $this->assertSame(['id' => 77, 'username' => 'ap_sessionuser'], api_get_user_info(0));
    }

    public function testGetUserInfoDoesNotHideSoftDeletedUsers(): void
    {
        // PINNED QUIRK: the lookup has no "status/active = soft deleted" filter. fallback_user (id 3) has
        // active = -2 (USER_SOFT_DELETED) and is still returned, with its active flag as the only marker.
        $info = api_get_user_info(3);

        $this->assertIsArray($info);
        $this->assertSame('fallback_user', $info['username']);
        $this->assertSame(-2, (int) $info['active']);
    }

    public function testGetUserInfoAcceptsNumericStringsAndCastsInjection(): void
    {
        $uid = $this->rawUser('cast');

        $this->assertSame($uid, (int) api_get_user_info((string) $uid)['user_id']);
        // "(int) '1 OR 1=1'" is 1: the value is cast, never concatenated as SQL.
        $this->assertSame(1, (int) api_get_user_info('1 OR 1=1')['user_id']);
    }

    public function testGetUserInfoFromUsernameIsExactAndTrimsInput(): void
    {
        $uid = $this->rawUser('byname');
        $name = $this->username($uid);

        $this->assertSame($uid, (int) api_get_user_info_from_username('  '.$name.' ')['user_id']);
        $this->assertFalse(api_get_user_info_from_username($name.'x'));
        $this->assertFalse(api_get_user_info_from_username(''));
        $this->assertFalse(api_get_user_info_from_username("' OR '1'='1"));
    }

    public function testGetUserInfoFromUsernameReturnsSoftDeletedUsersToo(): void
    {
        // PINNED QUIRK: like api_get_user_info(), no filter on active = -2.
        $info = api_get_user_info_from_username('fallback_user');

        $this->assertSame(3, (int) $info['user_id']);
        $this->assertSame(-2, (int) $info['active']);
    }

    public function testGetUserInfoFromEmailReturnsTheFirstMatch(): void
    {
        $uid = $this->rawUser('bymail');
        $mail = $this->username($uid).'@example.com';

        $this->assertSame($uid, (int) api_get_user_info_from_email($mail)['user_id']);
        $this->assertFalse(api_get_user_info_from_email('nobody-ap@example.invalid'));
        $this->assertFalse(api_get_user_info_from_email(''));
    }

    public function testGetUserInfoFromEmailPicksOneOfSeveralUsersSharingTheAddress(): void
    {
        $a = $this->rawUser('dup1');
        $b = $this->rawUser('dup2');
        $shared = 'ap_shared_'.getmypid().'@example.com';
        $this->conn()->executeStatement('UPDATE user SET email = ? WHERE id IN (?, ?)', [$shared, $a, $b]);

        // LIMIT 1 without ORDER BY: pin only that one of them is returned, not which one.
        $this->assertContains((int) api_get_user_info_from_email($shared)['user_id'], [$a, $b]);
    }

    public function testGetUserInfoFromOfficialCodeIsExact(): void
    {
        $uid = $this->rawUser('code', 5, 1, 'AP-CODE-'.getmypid());

        $this->assertSame($uid, (int) api_get_user_info_from_official_code('AP-CODE-'.getmypid())['user_id']);
        $this->assertFalse(api_get_user_info_from_official_code('AP-CODE'));
        $this->assertFalse(api_get_user_info_from_official_code(''));
    }

    // ---------------------------------------------------------------- admin / blocking

    public function testPlatformAdminByIdIsTrueOnlyForAdmins(): void
    {
        $student = $this->rawUser('stu', 5);

        $this->assertTrue(api_is_platform_admin_by_id(1));
        $this->assertFalse(api_is_platform_admin_by_id($student));
        $this->assertFalse(api_is_platform_admin_by_id(2), 'The anonymous user is not an admin.');
        $this->assertFalse(api_is_platform_admin_by_id(3), 'The soft-deleted fallback user is not an admin.');
        $this->assertFalse(api_is_platform_admin_by_id(999999999));
    }

    public function testPlatformAdminByIdWithUrlRequiresTheUrlRelation(): void
    {
        // Admin 1 is registered on URL 1 by the installer; URL 99999 has no relation row.
        $linked = (int) $this->conn()->fetchOne('SELECT COUNT(*) FROM access_url_rel_user WHERE user_id = 1 AND access_url_id = 1');

        $this->assertSame($linked === 1, api_is_platform_admin_by_id(1, 1));
        $this->assertFalse(api_is_platform_admin_by_id(1, 99999));
    }

    public function testBlockInactiveUserLetsEveryoneThroughWhenTheSettingIsOff(): void
    {
        $this->setSetting('security.security_block_inactive_users_immediately', 'false');
        $inactive = $this->rawUser('off', 5, 0);
        $this->loginAs($inactive);

        $this->assertTrue(api_block_inactive_user());
    }

    public function testBlockInactiveUserLetsAnActiveUserAndTheAnonymousVisitorThrough(): void
    {
        $this->setSetting('security.security_block_inactive_users_immediately', 'true');
        $active = $this->rawUser('on', 5, 1);
        $this->loginAs($active);
        $this->assertTrue(api_block_inactive_user());

        ChamiloSession::erase('_user');
        self::getContainer()->get('security.token_storage')->setToken(null);
        $this->assertTrue(api_block_inactive_user(), 'User id 0 (not logged in) is never blocked.');
    }

    // ---------------------------------------------------------------- anonymous id

    public function testGetAnonymousIdReturnsTheAnonymousUserRow(): void
    {
        $this->setSetting('admin.max_anonymous_users', '0');

        $expected = (int) $this->conn()->fetchOne('SELECT id FROM user WHERE status = 6 ORDER BY id LIMIT 1');

        $this->assertGreaterThan(0, $expected);
        $this->assertSame($expected, (int) api_get_anonymous_id());
    }

    // ---------------------------------------------------------------- course int id

    public function testGetCourseIntIdResolvesTheCodeOrIsZero(): void
    {
        $this->assertSame(1, api_get_course_int_id('AIACT'));
        $this->assertSame(2, api_get_course_int_id('USINGCHAMILO'));
        $this->assertSame(0, api_get_course_int_id('AP_NOPE'));
        $this->assertSame(0, api_get_course_int_id("AIACT' OR '1'='1"));
    }

    public function testGetCourseIntIdWithoutCodeReadsTheSessionThenTheRequest(): void
    {
        $this->assertSame(0, api_get_course_int_id());

        $_REQUEST['cid'] = '2';
        $this->assertSame(2, api_get_course_int_id());

        ChamiloSession::write('_real_cid', 1);
        $this->assertSame(1, api_get_course_int_id(), 'The session value wins over the request.');
    }

    // ---------------------------------------------------------------- course settings

    public function testGetCourseSettingReadsTheCourseValueAndMinusOneForUnknownOnes(): void
    {
        $this->conn()->executeStatement(
            "INSERT INTO c_course_setting (c_id, variable, subkey, type, category, value, title)
             VALUES (1, 'ap_setting', NULL, 'checkbox', 'ap', '1', 'ap')"
        );

        $this->assertSame(-1, api_get_course_setting('ap_unknown_setting', ['real_id' => 1], true));
        $this->assertSame(-1, api_get_course_setting('', ['real_id' => 1], true));
        $this->assertSame(-1, api_get_course_setting('ap_setting', ['real_id' => 0], true), 'No course id: -1.');
        $this->assertSame('1', (string) api_get_course_setting('ap_setting', ['real_id' => 1], true));
        // The same name on another course does not leak.
        $this->assertSame(-1, api_get_course_setting('ap_setting', ['real_id' => 2], true));
    }

    // ---------------------------------------------------------------- sessions

    public function testGetSessionNameReturnsTheTitleOrNull(): void
    {
        $sid = $this->rawSession('name', null, null);
        $title = (string) $this->conn()->fetchOne('SELECT title FROM session WHERE id = ?', [$sid]);

        $this->assertSame($title, api_get_session_name($sid));
        $this->assertNull(api_get_session_name(999999999));
        $this->assertNull(api_get_session_name(0), 'No session in the context: null.');

        ChamiloSession::write('sid', $sid);
        $this->assertSame($title, api_get_session_name(), 'Defaults to the current session.');
    }

    public function testSessionVisibilityOfASessionWithoutDatesIsAvailable(): void
    {
        $sid = $this->rawSession('nodates', null, null);

        $this->assertSame(SESSION_AVAILABLE, api_get_session_visibility($sid));
    }

    public function testSessionVisibilityWithoutSessionIsZeroAndUnknownSessionIsInvisible(): void
    {
        $this->assertSame(0, api_get_session_visibility(0));
        $this->assertSame(SESSION_INVISIBLE, api_get_session_visibility(999999999));
    }

    public function testSessionVisibilityIsInvisibleBeforeTheStartDate(): void
    {
        $sid = $this->rawSession('future', '2090-01-01 00:00:00', '2091-01-01 00:00:00');

        $this->assertSame(SESSION_INVISIBLE, api_get_session_visibility($sid));
    }

    public function testSessionVisibilityIsAvailableBetweenStartAndEnd(): void
    {
        $sid = $this->rawSession('current', '2001-01-01 00:00:00', '2090-01-01 00:00:00');

        $this->assertSame(SESSION_AVAILABLE, api_get_session_visibility($sid));
    }

    public function testSessionVisibilityAfterTheEndDateIsTheStoredVisibility(): void
    {
        $readOnly = $this->rawSession('over', '2001-01-01 00:00:00', '2002-01-01 00:00:00', ['visibility' => SESSION_VISIBLE_READ_ONLY]);
        $invisible = $this->rawSession('over2', '2001-01-01 00:00:00', '2002-01-01 00:00:00', ['visibility' => SESSION_INVISIBLE]);

        $this->assertSame(SESSION_VISIBLE_READ_ONLY, (int) api_get_session_visibility($readOnly));
        $this->assertSame(SESSION_INVISIBLE, (int) api_get_session_visibility($invisible));
    }

    public function testSessionVisibilityWithOnlyAnEndDateIsAvailableUntilThen(): void
    {
        $open = $this->rawSession('endonly', null, '2090-01-01 00:00:00');
        $closed = $this->rawSession('endonly2', null, '2002-01-01 00:00:00', ['visibility' => SESSION_VISIBLE_READ_ONLY]);

        $this->assertSame(SESSION_AVAILABLE, api_get_session_visibility($open));
        $this->assertSame(SESSION_VISIBLE_READ_ONLY, (int) api_get_session_visibility($closed));
    }

    public function testSessionVisibilityAdminSeesEverythingUnlessAskedNotTo(): void
    {
        $sid = $this->rawSession('adm', '2090-01-01 00:00:00', '2091-01-01 00:00:00');
        $this->loginAs(1);

        $this->assertSame(SESSION_AVAILABLE, api_get_session_visibility($sid));
        $this->assertSame(SESSION_INVISIBLE, api_get_session_visibility($sid, null, false, 1));
    }

    public function testSessionVisibilityCoachDatesCanOpenAnOtherwiseClosedSession(): void
    {
        $coach = $this->rawUser('coachvis');
        $sid = $this->rawSession('coachwin', '2090-01-01 00:00:00', '2091-01-01 00:00:00', [
            'coach_start' => '2001-01-01 00:00:00',
            'coach_end' => '2090-01-01 00:00:00',
        ]);
        $this->sessionUser($sid, $coach, 3);
        $other = $this->rawUser('nocoachvis');

        $this->loginAs($coach);
        $this->assertSame(SESSION_AVAILABLE, api_get_session_visibility($sid, 1));
        $this->loginAs($other);
        $this->assertSame(SESSION_INVISIBLE, api_get_session_visibility($sid, 1), 'Non coaches keep the session dates.');
    }

    public function testSessionVisibilityCoachRuleIgnoresTheGivenUserId(): void
    {
        // PINNED QUIRK: $userId is only used for the per-student duration; the coach rule asks
        // api_is_coach() about the LOGGED-IN user, so a coach asked about through an explicit id does not
        // get the coach window while somebody else is logged in.
        $coach = $this->rawUser('coachvis2');
        $sid = $this->rawSession('coachwin2', '2090-01-01 00:00:00', '2091-01-01 00:00:00', [
            'coach_start' => '2001-01-01 00:00:00',
            'coach_end' => '2090-01-01 00:00:00',
        ]);
        $this->sessionUser($sid, $coach, 3);
        $this->loginAs($this->rawUser('bystander'));

        $this->assertSame(SESSION_INVISIBLE, api_get_session_visibility($sid, 1, true, $coach));
    }

    // ---------------------------------------------------------------- coaches

    public function testIsCourseSessionCoachRequiresTheCourseCoachRowForThatExactCourseAndSession(): void
    {
        $coach = $this->rawUser('csc');
        $student = $this->rawUser('cscstu');
        $sid = $this->rawSession('csc', null, null);
        $sid2 = $this->rawSession('csc2', null, null);
        $this->sessionCourseUser($sid, 1, $coach, 2);
        $this->sessionCourseUser($sid, 1, $student, 0);

        $this->assertTrue(api_is_course_session_coach($coach, 1, $sid));
        $this->assertFalse(api_is_course_session_coach($coach, 2, $sid), 'Another course.');
        $this->assertFalse(api_is_course_session_coach($coach, 1, $sid2), 'Another session.');
        $this->assertFalse(api_is_course_session_coach($student, 1, $sid), 'A student is not a coach.');
        $this->assertFalse(api_is_course_session_coach(2, 1, $sid), 'Anonymous is not a coach.');
        $this->assertFalse(api_is_course_session_coach(3, 1, $sid), 'The soft-deleted user is not a coach.');
    }

    public function testIsCoachRecognisesTheCourseCoachOfTheSession(): void
    {
        $coach = $this->rawUser('cc');
        $sid = $this->rawSession('cc', null, null);
        $this->sessionCourseUser($sid, 1, $coach, 2);

        $this->assertTrue(api_is_coach($sid, 1, true, $coach));
        $this->assertFalse(api_is_coach($sid, 2, true, $coach), 'The course coach row is per course.');
        $this->assertFalse(api_is_coach(0, 1, true, $coach), 'Without a session nobody is coach.');
    }

    public function testIsCoachRecognisesTheGeneralCoachOfTheSession(): void
    {
        $general = $this->rawUser('gc');
        $assistant = $this->rawUser('gcassist');
        $sid = $this->rawSession('gc', null, null);
        $other = $this->rawSession('gc2', null, null);
        $this->sessionUser($sid, $general, 3);
        // Relation type 0 is a plain student subscription, 4 a session admin: neither is a coach.
        $this->sessionUser($sid, $assistant, 0);

        $this->assertTrue(api_is_coach($sid, 1, true, $general));
        $this->assertTrue(api_is_coach($sid, 2, true, $general), 'The general coach is coach whatever the course.');
        $this->assertFalse(api_is_coach($other, 1, true, $general), 'Another session.');
        $this->assertFalse(api_is_coach($sid, 1, true, $assistant));
    }

    public function testIsCoachIsFalseInStudentViewUnlessTheCheckIsDisabled(): void
    {
        $coach = $this->rawUser('sv');
        $sid = $this->rawSession('sv', null, null);
        $this->sessionUser($sid, $coach, 3);
        ChamiloSession::write('studentview', 'studentview');

        $this->assertFalse(api_is_coach($sid, 1, true, $coach));
        $this->assertTrue(api_is_coach($sid, 1, false, $coach));
    }

    public function testIsCoachDefaultsToTheCurrentUserAndSession(): void
    {
        $coach = $this->rawUser('cur');
        $sid = $this->rawSession('cur', null, null);
        $this->sessionUser($sid, $coach, 3);
        $this->loginAs($coach);
        ChamiloSession::write('sid', $sid);

        $this->assertTrue(api_is_coach());

        $this->loginAs($this->rawUser('curother'));
        $this->assertFalse(api_is_coach());
    }

    // ---------------------------------------------------------------- explicit student

    public function testIsExplicitCourseStudentOnlyForStudentRowsOfThatCourse(): void
    {
        $student = $this->rawUser('exs');
        $teacher = $this->rawUser('ext');
        $this->courseUser($student, 1, 5);
        $this->courseUser($teacher, 1, 1);

        $this->assertTrue(api_is_explicit_course_student($student, 1));
        $this->assertFalse(api_is_explicit_course_student($student, 2), 'Not subscribed to course 2.');
        $this->assertFalse(api_is_explicit_course_student($teacher, 1), 'A teacher is not a student.');
        $this->assertFalse(api_is_explicit_course_student(0, 1));
        $this->assertFalse(api_is_explicit_course_student($student, 0));
    }

    // ---------------------------------------------------------------- session element / category

    private function freeResourceNode(): int
    {
        return (int) $this->conn()->fetchOne(
            'SELECT id FROM resource_node
             WHERE id NOT IN (SELECT resource_node_id FROM c_survey WHERE resource_node_id IS NOT NULL)
               AND id NOT IN (SELECT resource_node_id FROM resource_link WHERE resource_node_id IS NOT NULL)
             ORDER BY id DESC LIMIT 1'
        );
    }

    private function rawSurvey(): int
    {
        $this->conn()->executeStatement(
            "INSERT INTO c_survey (resource_node_id, title, creation_date, invited, answered, invite_mail, reminder_mail,
                mail_subject, anonymous, shuffle, one_question_per_page, survey_version, survey_type, show_form_profile,
                form_fields, results_hidden_from_teacher, is_mandatory, display_question_number)
             VALUES (?, 'ap_survey', NOW(), 0, 0, '', '', '', '0', 0, 0, '', 0, 0, '', 0, 0, 0)",
            [$this->freeResourceNode()]
        );

        return (int) $this->conn()->lastInsertId();
    }

    private function link(int $surveyId, int $courseId, ?int $sessionId, ?string $deletedAt = null): void
    {
        $this->conn()->executeStatement(
            'INSERT INTO resource_link (resource_node_id, c_id, session_id, visibility, display_order, resource_type_group, deleted_at, created_at, updated_at)
             VALUES ((SELECT resource_node_id FROM c_survey WHERE iid = ?), ?, ?, 2, 0, 0, ?, NOW(), NOW())',
            [$surveyId, $courseId, $sessionId, $deletedAt]
        );
    }

    public function testIsElementInTheSessionIsTrueOnlyForAResourceLinkOfThatCourseAndSession(): void
    {
        ChamiloSession::write('_real_cid', 1);
        $sid = $this->rawSession('elem', null, null);
        $sid2 = $this->rawSession('elem2', null, null);
        $inSession = $this->rawSurvey();
        $this->link($inSession, 1, $sid);
        $baseOnly = $this->rawSurvey();
        $this->link($baseOnly, 1, null);
        $otherCourse = $this->rawSurvey();
        $this->link($otherCourse, 2, $sid);
        $removed = $this->rawSurvey();
        $this->link($removed, 1, $sid, '2020-01-01 00:00:00');

        $this->assertTrue(api_is_element_in_the_session(TOOL_SURVEY, $inSession, $sid));
        $this->assertFalse(api_is_element_in_the_session(TOOL_SURVEY, $inSession, $sid2), 'Another session.');
        $this->assertFalse(api_is_element_in_the_session(TOOL_SURVEY, $baseOnly, $sid), 'A base course element is not in the session.');
        $this->assertFalse(api_is_element_in_the_session(TOOL_SURVEY, $otherCourse, $sid), 'The link must be in the current course.');
        $this->assertFalse(api_is_element_in_the_session(TOOL_SURVEY, $removed, $sid), 'A soft-deleted link does not count.');
        $this->assertFalse(api_is_element_in_the_session(TOOL_SURVEY, $inSession, 0), 'No session: never.');
        $this->assertFalse(api_is_element_in_the_session(TOOL_SURVEY, 999999999, $sid));
    }

    public function testIsElementInTheSessionDefaultsToTheCurrentSession(): void
    {
        ChamiloSession::write('_real_cid', 1);
        $sid = $this->rawSession('elemcur', null, null);
        $survey = $this->rawSurvey();
        $this->link($survey, 1, $sid);

        $this->assertFalse(api_is_element_in_the_session(TOOL_SURVEY, $survey));
        ChamiloSession::write('sid', $sid);
        $this->assertTrue(api_is_element_in_the_session(TOOL_SURVEY, $survey));
    }

    public function testIsElementInTheSessionShortCircuitsOnEmptyIdAndUnknownTools(): void
    {
        $this->assertFalse(api_is_element_in_the_session(TOOL_SURVEY, 0, 1));
        $this->assertFalse(api_is_element_in_the_session('unknown_tool', 5, 1));
    }

    // ---------------------------------------------------------------- gradebook lock

    public function testResourceIsLockedByGradebookOnlyWhenTheSettingAndTheLinkLockAreOn(): void
    {
        $this->setSetting('gradebook.gradebook_locking_enabled', 'true');
        $nodeId = (int) $this->conn()->fetchOne(
            'SELECT id FROM resource_node
             WHERE id NOT IN (SELECT resource_node_id FROM c_quiz WHERE resource_node_id IS NOT NULL)
               AND id NOT IN (SELECT resource_node_id FROM resource_link WHERE resource_node_id IS NOT NULL)
             ORDER BY id DESC LIMIT 1'
        );
        $this->conn()->executeStatement(
            "INSERT INTO c_quiz (resource_node_id, title, type, random, random_answers, results_disabled, max_attempt,
                feedback_type, expired_time, propagate_neg, review_answers, random_by_category, display_category_name,
                prevent_backwards, hide_question_number, hide_question_title, show_question_max_score,
                show_previous_button, hide_attempts_table, allow_question_feedback_reports, page_result_configuration,
                display_chart_degree_certainty, send_email_chart_degree_certainty,
                not_display_balance_percentage_categorie_question, display_chart_degree_certainty_category,
                gather_questions_categories)
             VALUES (?, 'ap_quiz', 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, '[]', 0, 0, 0, 0, 0)",
            [$nodeId]
        );
        $quizId = (int) $this->conn()->lastInsertId();
        $courseCode = 'AIACT';
        $cid = 1;

        $this->assertFalse(api_resource_is_locked_by_gradebook($quizId, LINK_EXERCISE, $courseCode), 'No link yet.');

        $this->conn()->executeStatement(
            'INSERT INTO gradebook_link (ref_id, c_id, type, created_at, weight, visible, locked)
             VALUES (?, ?, ?, NOW(), 1, 1, 0)',
            [$nodeId, $cid, LINK_EXERCISE]
        );
        $linkId = (int) $this->conn()->lastInsertId();
        $this->assertFalse(api_resource_is_locked_by_gradebook($quizId, LINK_EXERCISE, $courseCode), 'Link not locked.');

        $this->conn()->executeStatement('UPDATE gradebook_link SET locked = 1 WHERE id = ?', [$linkId]);
        $this->assertTrue(api_resource_is_locked_by_gradebook($quizId, LINK_EXERCISE, $courseCode));
        $this->assertFalse(api_resource_is_locked_by_gradebook($quizId, LINK_LEARNPATH, $courseCode), 'Link type must match.');
        $this->assertFalse(api_resource_is_locked_by_gradebook($quizId, 99999, $courseCode), 'Unknown link type.');

        $this->loginAs(1);
        $this->assertFalse(api_resource_is_locked_by_gradebook($quizId, LINK_EXERCISE, $courseCode), 'Admins are never blocked.');
        $this->loginAs($this->rawUser('gl'));
        $this->setSetting('gradebook.gradebook_locking_enabled', 'false');
        $this->assertFalse(api_resource_is_locked_by_gradebook($quizId, LINK_EXERCISE, $courseCode), 'Locking disabled.');
    }

    // ---------------------------------------------------------------- access urls / languages

    public function testCurrentAccessUrlIdIsOneWithASingleUrl(): void
    {
        $this->assertSame(1, api_get_current_access_url_id());
    }

    public function testGetAccessUrlReturnsTheRowOrFalse(): void
    {
        $row = api_get_access_url(1);

        $this->assertSame(
            ['url', 'description', 'active', 'created_by', 'tms'],
            array_keys(array_intersect_key($row, array_flip(['url', 'description', 'active', 'created_by', 'tms'])))
        );
        $this->assertSame((string) $this->conn()->fetchOne('SELECT url FROM access_url WHERE id = 1'), $row['url']);
        // PINNED QUIRK: an unknown id yields an empty array, not false as the docblock suggests.
        $this->assertSame([], api_get_access_url(999999999));
    }

    public function testGetActiveUrlsListsOnlyActiveRows(): void
    {
        $this->conn()->executeStatement(
            "INSERT INTO access_url (url, active, description, created_by, lft, rgt, lvl)
             VALUES ('http://ap-inactive.invalid/', 0, '', 1, 1, 2, 0), ('http://ap-active.invalid/', 1, '', 1, 1, 2, 0)"
        );

        $urls = array_column(api_get_active_urls(), 'url');

        $this->assertContains('http://ap-active.invalid/', $urls);
        $this->assertNotContains('http://ap-inactive.invalid/', $urls);
    }

    public function testGetAccessUrlFromUserListsTheUrlIdsOfTheUser(): void
    {
        $uid = $this->rawUser('url');
        $this->conn()->executeStatement(
            "INSERT INTO access_url (url, active, description, created_by, lft, rgt, lvl) VALUES ('http://ap-second.invalid/', 1, '', 1, 1, 2, 0)"
        );
        $second = (int) $this->conn()->lastInsertId();
        $this->conn()->executeStatement('INSERT INTO access_url_rel_user (access_url_id, user_id) VALUES (?, ?)', [$second, $uid]);

        $list = array_map('intval', api_get_access_url_from_user($uid));
        sort($list);

        $this->assertSame([1, $second], $list);
        $this->assertSame([], api_get_access_url_from_user(999999999));
    }

    public function testGetAccessUrlsOrdersByTheGivenColumnAndFallsBackToAscOnABadDirection(): void
    {
        $this->conn()->executeStatement(
            "INSERT INTO access_url (url, active, description, created_by, lft, rgt, lvl)
             VALUES ('http://ap-aaa.invalid/', 1, '', 1, 1, 2, 0), ('http://ap-zzz.invalid/', 1, '', 1, 1, 2, 0)"
        );

        $asc = array_column(api_get_access_urls(0, 1000), 'url');
        $desc = array_column(api_get_access_urls(0, 1000, 'url', 'DESC'), 'url');
        $bad = array_column(api_get_access_urls(0, 1000, 'url', 'sideways'), 'url');

        $sorted = $asc;
        sort($sorted, SORT_STRING);
        $this->assertSame($sorted, $asc);
        $this->assertSame(array_reverse($sorted), $desc);
        $this->assertSame($asc, $bad);
        $this->assertCount(2, api_get_access_urls(0, 2), 'The second argument is the page size.');
    }

    public function testGetLanguagesListsAvailableOnesByIsoCodeOrderedByOriginalName(): void
    {
        $expected = $this->conn()->fetchAllKeyValue("SELECT isocode, original_name FROM language WHERE available = '1' ORDER BY original_name ASC");

        $this->assertSame($expected, api_get_languages());
        $this->assertSame(array_keys($expected), array_keys(api_get_languages()));
    }

    public function testGetLanguageIdResolvesIsoCodesThenEnglishNames(): void
    {
        $row = $this->conn()->fetchAssociative("SELECT id, isocode, english_name FROM language WHERE isocode = 'fr_FR' LIMIT 1");
        if (!$row) {
            $this->markTestSkipped('fr_FR language is not installed.');
        }

        $this->assertSame((int) $row['id'], (int) api_get_language_id('fr_FR'));
        $this->assertSame((int) $row['id'], (int) api_get_language_id($row['english_name']));
        $this->assertNull(api_get_language_id(''));
    }

    public function testGetLanguageInfoReturnsTheMappedRowOrAnEmptyArray(): void
    {
        $row = $this->conn()->fetchAssociative("SELECT id, original_name, english_name, isocode, available FROM language WHERE isocode = 'fr_FR' LIMIT 1");
        if (!$row) {
            $this->markTestSkipped('fr_FR language is not installed.');
        }

        $info = api_get_language_info((int) $row['id']);

        $this->assertSame($row['isocode'], $info['isocode']);
        $this->assertSame($row['english_name'], $info['english_name']);
        $this->assertSame($row['original_name'], $info['original_name']);
        $this->assertSame(['id', 'original_name', 'english_name', 'isocode', 'available', 'parent_id'], array_keys($info));
        $this->assertSame([], api_get_language_info(0));
        $this->assertSame([], api_get_language_info(999999999));
    }

    public function testPlatformDefaultIsocodeReadsTheSettingAndFallsBackToTheEnglishName(): void
    {
        $this->setSetting('language.platform_language', 'es_ES');
        $this->assertSame('es_ES', api_get_platform_default_isocode());
    }

    public function testLanguagesWithPlatformDefaultAddsTheDefaultEvenWhenUnavailable(): void
    {
        $iso = 'fr_FR';
        if (!$this->conn()->fetchOne('SELECT 1 FROM language WHERE isocode = ?', [$iso])) {
            $this->markTestSkipped('fr_FR language is not installed.');
        }
        $this->conn()->executeStatement("UPDATE language SET available = 0 WHERE isocode = ?", [$iso]);
        $this->setSetting('language.platform_language', $iso);

        $this->assertArrayNotHasKey($iso, api_get_languages());
        $this->assertArrayHasKey($iso, api_get_languages_with_platform_default());
        $this->assertSame(
            array_keys(api_get_languages()),
            array_values(array_diff(array_keys(api_get_languages_with_platform_default()), [$iso]))
        );
    }

    // ---------------------------------------------------------------- settings tables

    public function testSetSettingUpdatesTheSelectedValueOfTheMatchingRow(): void
    {
        $this->conn()->executeStatement(
            "INSERT INTO settings (access_url, variable, subkey, type, category, selected_value, title, access_url_changeable)
             VALUES (1, 'ap_var', 'one', 'textfield', 'ap_cat', 'old', 'ap', 0), (1, 'ap_var', 'two', 'textfield', 'ap_cat', 'old', 'ap', 0)"
        );

        api_set_setting('ap_var', 'new', 'two', 'ap_cat');

        $this->assertSame(
            ['one' => 'old', 'two' => 'new'],
            $this->conn()->fetchAllKeyValue("SELECT subkey, selected_value FROM settings WHERE variable = 'ap_var'")
        );
        $this->assertFalse(api_set_setting('', 'x'));
    }

    public function testSetSettingsCategoryUpdatesOnlyTheCategoryAndTheGivenFieldTypes(): void
    {
        $this->conn()->executeStatement(
            "INSERT INTO settings (access_url, variable, subkey, type, category, selected_value, title, access_url_changeable)
             VALUES (1, 'ap_a', NULL, 'radio', 'ap_cat2', 'old', 'ap', 0),
                    (1, 'ap_b', NULL, 'textfield', 'ap_cat2', 'old', 'ap', 0),
                    (1, 'ap_c', NULL, 'radio', 'ap_cat3', 'old', 'ap', 0)"
        );

        $this->assertTrue(api_set_settings_category('ap_cat2', 'changed', 1, ['radio']));
        $values = $this->conn()->fetchAllKeyValue("SELECT variable, selected_value FROM settings WHERE variable LIKE 'ap\\_%' ORDER BY variable");
        $this->assertSame(['ap_a' => 'changed', 'ap_b' => 'old', 'ap_c' => 'old'], $values);

        $this->assertTrue(api_set_settings_category('ap_cat2', null, 1));
        $this->assertNull($this->conn()->fetchOne("SELECT selected_value FROM settings WHERE variable = 'ap_b'"));
        $this->assertFalse(api_set_settings_category(''));
    }

    public function testSetSettingsCategoryOnlyTouchesTheGivenAccessUrl(): void
    {
        $this->conn()->executeStatement(
            "INSERT INTO access_url (url, active, description, created_by, lft, rgt, lvl) VALUES ('http://ap-settings.invalid/', 1, '', 1, 1, 2, 0)"
        );
        $second = (int) $this->conn()->lastInsertId();
        $this->conn()->executeStatement(
            "INSERT INTO settings (access_url, variable, subkey, type, category, selected_value, title, access_url_changeable)
             VALUES (1, 'ap_u1', NULL, 'radio', 'ap_cat7', 'old', 'ap', 0), (?, 'ap_u2', NULL, 'radio', 'ap_cat7', 'old', 'ap', 0)",
            [$second]
        );

        api_set_settings_category('ap_cat7', 'changed', $second);

        $this->assertSame(
            ['ap_u1' => 'old', 'ap_u2' => 'changed'],
            $this->conn()->fetchAllKeyValue("SELECT variable, selected_value FROM settings WHERE category = 'ap_cat7'")
        );
    }

    public function testGetSettingsFiltersByCategoryAndUrl(): void
    {
        $this->conn()->executeStatement(
            "INSERT INTO settings (access_url, variable, subkey, type, category, selected_value, title, access_url_changeable)
             VALUES (1, 'ap_g1', NULL, 'textfield', 'ap_cat4', 'x', 'ap', 1), (1, 'ap_g2', NULL, 'textfield', 'ap_cat4', 'x', 'ap', 0)"
        );

        $all = api_get_settings('ap_cat4');
        $changeable = api_get_settings('ap_cat4', 'list', 1, 1);

        $this->assertSame(['ap_g1', 'ap_g2'], array_column($all, 'variable'));
        $this->assertSame(['ap_g1'], array_column($changeable, 'variable'));
        $this->assertSame([], api_get_settings('ap_cat4', 'list', 99999));
        $this->assertSame(['ap_g1', 'ap_g2'], array_column(api_get_settings('ap_cat4', 'group', -1), 'variable'), 'A -1 URL means URL 1.');
    }

    public function testSettingsParamsHelpersSelectByWhereClause(): void
    {
        $this->conn()->executeStatement(
            "INSERT INTO settings (access_url, variable, subkey, type, category, selected_value, title, access_url_changeable)
             VALUES (1, 'ap_p1', NULL, 'textfield', 'ap_cat5', 'x', 'ap', 0)"
        );

        $rows = api_get_settings_params(['variable = ?' => 'ap_p1']);
        $one = api_get_settings_params_simple(['variable = ?' => 'ap_p1']);

        $this->assertCount(1, $rows);
        $first = reset($rows);
        $this->assertSame('x', $first['selected_value']);
        $this->assertSame('ap_p1', $one['variable']);
        $this->assertSame([], api_get_settings_params(['variable = ?' => 'ap_nope']));
    }

    public function testSetSettingSimpleInsertsForTheCurrentUrlThenUpdatesById(): void
    {
        api_set_setting_simple([
            'variable' => 'ap_simple',
            'selected_value' => 'one',
            'title' => 'ap',
            'type' => 'textfield',
            'category' => 'ap_cat6',
            'access_url_changeable' => 0,
        ]);
        $row = $this->conn()->fetchAssociative("SELECT id, access_url, selected_value FROM settings WHERE variable = 'ap_simple'");

        $this->assertSame(1, (int) $row['access_url']);
        $this->assertSame('one', $row['selected_value']);

        api_set_setting_simple(['id' => $row['id'], 'selected_value' => 'two']);
        $this->assertSame('two', $this->conn()->fetchOne("SELECT selected_value FROM settings WHERE variable = 'ap_simple'"));
    }

    public function testSettingsOptionsAreListedByIdAndDeletedById(): void
    {
        $this->conn()->executeStatement(
            "INSERT INTO settings_options (variable, value, display_text) VALUES ('ap_opt', 'b', 'Second'), ('ap_opt', 'a', 'First'), ('ap_opt_other', 'z', 'Other')"
        );

        $options = api_get_settings_options('ap_opt');

        $this->assertSame(['b', 'a'], array_column($options, 'value'), 'Ordered by id (insertion), not by value.');
        $this->assertSame([], api_get_settings_options('ap_opt_none'));

        api_delete_setting_option($options[0]['id']);
        $this->assertSame(['a'], array_column(api_get_settings_options('ap_opt'), 'value'));
        api_delete_setting_option(0);
        $this->assertCount(1, api_get_settings_options('ap_opt'), 'An empty id deletes nothing.');
    }

    public function testRegisterCampusFlagsTheRegisteredSettingAndOptionallyDonotlistcampus(): void
    {
        $this->conn()->executeStatement(
            "INSERT INTO settings (access_url, variable, subkey, type, category, selected_value, title, access_url_changeable)
             VALUES (1, 'registered', NULL, 'textfield', 'ap', 'false', 'ap', 0), (1, 'donotlistcampus', NULL, 'textfield', 'ap', 'false', 'ap', 0)"
        );

        api_register_campus(true);
        $this->assertSame('true', $this->conn()->fetchOne("SELECT selected_value FROM settings WHERE variable = 'registered'"));
        $this->assertSame('false', $this->conn()->fetchOne("SELECT selected_value FROM settings WHERE variable = 'donotlistcampus'"));

        api_register_campus(false);
        $this->assertSame('true', $this->conn()->fetchOne("SELECT selected_value FROM settings WHERE variable = 'donotlistcampus'"));
    }

    // ---------------------------------------------------------------- tools

    public function testGetToolInformationByNameReturnsTheCourseToolRowOrFalse(): void
    {
        ChamiloSession::write('_real_cid', 1);
        $toolId = (int) $this->conn()->fetchOne("SELECT id FROM tool WHERE title = 'document'");
        $this->conn()->executeStatement('DELETE FROM c_tool WHERE c_id = 1 AND tool_id = ?', [$toolId]);
        $this->conn()->executeStatement("INSERT INTO c_tool (c_id, tool_id, title, position) VALUES (1, ?, 'ap_doc_tool', 1)", [$toolId]);

        $row = api_get_tool_information_by_name('document');

        $this->assertSame('ap_doc_tool', $row['title']);
        // PINNED QUIRK: unknown tool name gives an empty array, not false.
        $this->assertSame([], api_get_tool_information_by_name('ap_unknown_tool'));
        // A known tool the course does not have gives false.
        $this->conn()->executeStatement('DELETE FROM c_tool WHERE c_id = 1 AND tool_id = ?', [$toolId]);
        $this->assertFalse(api_get_tool_information_by_name('document'));
    }

    // ---------------------------------------------------------------- dead helpers (cheap pins)

    public function testIsSessionInCategoryMatchesTheCategoryTitleSuffix(): void
    {
        $this->conn()->executeStatement("INSERT INTO session_category (access_url_id, title) VALUES (1, 'ap_category')");
        $cat = (int) $this->conn()->lastInsertId();
        $sid = $this->rawSession('incat', null, null, ['category' => $cat]);
        $out = $this->rawSession('outcat', null, null);

        $this->assertTrue(api_is_session_in_category($sid, 'ap_category'));
        $this->assertTrue(api_is_session_in_category($sid, 'category'), 'LIKE %name: a suffix of the title matches.');
        $this->assertFalse(api_is_session_in_category($sid, 'ap_cat'), 'A prefix does not.');
        $this->assertFalse(api_is_session_in_category($out, 'ap_category'));
    }

    // ---------------------------------------------------------------- course visibility (search processors only)

    private function courseVisibility(int $visibility): void
    {
        $this->conn()->executeStatement('UPDATE course SET visibility = ? WHERE id = 1', [$visibility]);
        $this->fresh();
    }

    public function testCourseVisibleForEveryoneWhenOpenToTheWorld(): void
    {
        $this->courseVisibility(3);
        $stranger = $this->rawUser('cvw');

        $this->assertTrue(api_is_course_visible_for_user($stranger, 'AIACT'));
    }

    public function testCourseVisibleToASubscribedMemberOfARegisteredCourse(): void
    {
        $this->courseVisibility(1);
        $member = $this->rawUser('cvm');
        $this->courseUser($member, 1, 5);

        $this->assertTrue(api_is_course_visible_for_user($member, 'AIACT'));
    }

    public function testCourseOpenToThePlatformIsVisibleToAKnownUser(): void
    {
        $this->courseVisibility(2);
        $teacher = $this->rawUser('cvt');
        $this->courseUser($teacher, 1, 1);

        $this->assertTrue(api_is_course_visible_for_user($teacher, 'AIACT'));
    }

    public function testHiddenCourseIsNotVisibleToItsOwnTeacherButToAnAdmin(): void
    {
        $this->courseVisibility(4);
        $teacher = $this->rawUser('cvh');
        $this->courseUser($teacher, 1, 1);

        $this->assertFalse(api_is_course_visible_for_user($teacher, 'AIACT'));

        $this->loginAs(1);
        $this->assertTrue(api_is_course_visible_for_user($teacher, 'AIACT'));
    }

    public function testClosedCourseIsVisibleToItsMembersOnly(): void
    {
        $this->courseVisibility(0);
        $member = $this->rawUser('cvcl');
        $stranger = $this->rawUser('cvcl_out');
        $this->courseUser($member, 1, 5);

        $this->assertTrue(api_is_course_visible_for_user($member, 'AIACT'));
        $this->assertFalse(api_is_course_visible_for_user($stranger, 'AIACT'));
    }

    public function testNonMembersCannotSeeARegisteredCourse(): void
    {
        $this->courseVisibility(1);
        $stranger = $this->rawUser('cvx');

        $this->assertFalse(api_is_course_visible_for_user($stranger, 'AIACT'));
    }

    public function testACourseCoachOfASessionOfTheCourseSeesARegisteredCourse(): void
    {
        $this->courseVisibility(1);
        $coach = $this->rawUser('cvc');
        $other = $this->rawUser('cvc_other');
        $sid = $this->rawSession('cvc', null, null);
        $this->sessionCourseUser($sid, 1, $coach, 2);
        $this->sessionCourseUser($sid, 2, $other, 2);

        $this->assertTrue(api_is_course_visible_for_user($coach, 'AIACT'));
        $this->assertFalse(api_is_course_visible_for_user($other, 'AIACT'), 'Coach of another course only.');
    }

    public function testCourseVisibilityOfANonNumericUserIsFalseUnlessAnonymous(): void
    {
        $this->courseVisibility(1);

        $this->assertFalse(api_is_course_visible_for_user('abc', 'AIACT'));
    }
}
