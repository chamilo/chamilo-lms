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
 * An id taken from a request must be checked against what the CURRENT user may manage, not only against the
 * session the page was protected for: the legacy session pages post lists of session and category ids next to
 * their own id. These tests pin the guard helpers of SessionManager and the page-level pins that use them.
 * Fixtures are raw prepared inserts, tagged "sa2_".
 */
final class SessionAccessControlTest extends AbstractApiTest
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
        $name = 'sa2_'.$suffix.'_'.getmypid().'_'.(++$this->seq);
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
                'sa2_'.$title,
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
        $code = strtoupper('sa2_'.$title.'_'.getmypid().'_'.(++$this->seq));
        $this->conn()->executeStatement(
            'INSERT INTO course (language_id, title, code, visual_code, directory, visibility, video_url, sticky, creation_date, subscribe, unsubscribe, popularity)
             VALUES ((SELECT MIN(id) FROM language), ?, ?, ?, ?, 2, \'\', 0, NOW(), 1, 0, 0)',
            ['sa2_'.$title, $code, $code, $code]
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


    private array $users = [];

    private function fixture(): array
    {
        $this->setSetting('session.allow_session_admins_to_manage_all_sessions', 'false');
        $this->setSetting('session.allow_teachers_to_create_sessions', 'false');
        $this->users = [
            'owner' => $this->rawUser('own', 'ROLE_SESSION_MANAGER', 3),
            'other' => $this->rawUser('oth', 'ROLE_SESSION_MANAGER', 3),
            'student' => $this->rawUser('stu'),
        ];
        $mine = $this->rawSession('mine');
        $theirs = $this->rawSession('theirs');
        $this->rel($mine, $this->users['owner'], Session::SESSION_ADMIN);
        $this->rel($theirs, $this->users['other'], Session::SESSION_ADMIN);

        return [$mine, $theirs];
    }

    private function actAs(string $role): void
    {
        $this->loginAs('admin' === $role ? $this->getAdmin()->getId() : $this->users[$role]);
    }

    public function testCanManageSessionIdFollowsTheSessionAdminOfThatSession(): void
    {
        [$mine, $theirs] = $this->fixture();
        $out = [];
        foreach (['admin', 'owner', 'other', 'student'] as $role) {
            $this->actAs($role);
            $out[$role] = [
                SessionManager::canManageSessionId($mine),
                SessionManager::canManageSessionId($theirs),
            ];
        }
        $this->actAs('admin');

        $this->assertSame(
            [
                'admin' => [true, true],
                'owner' => [true, false],
                'other' => [false, true],
                'student' => [false, false],
            ],
            $out
        );
        $this->assertFalse(SessionManager::canManageSessionId(0));
        $this->assertFalse(SessionManager::canManageSessionId(999999999), 'An unknown session is nobody\'s.');
    }

    public function testManageAllSettingOpensEverySessionToSessionAdmins(): void
    {
        [$mine, $theirs] = $this->fixture();
        $this->setSetting('session.allow_session_admins_to_manage_all_sessions', 'true');
        $this->actAs('owner');

        $this->assertTrue(SessionManager::canManageSessionId($theirs));
        $this->actAs('student');
        $this->assertFalse(SessionManager::canManageSessionId($theirs), 'A learner is never a manager.');
        $this->actAs('admin');
    }

    public function testFilterManageableSessionIdsDropsForeignAndGarbageIds(): void
    {
        [$mine, $theirs] = $this->fixture();
        $this->actAs('owner');

        $this->assertSame(
            [$mine],
            SessionManager::filterManageableSessionIds([(string) $mine, $theirs, 0, 'x', $mine, 999999999])
        );
        $this->assertSame([], SessionManager::filterManageableSessionIds($mine), 'Not a list: nothing.');
        $this->assertSame([], SessionManager::filterManageableSessionIds(null));
        $this->actAs('admin');
        $this->assertSame([$mine, $theirs], SessionManager::filterManageableSessionIds([$mine, $theirs]));
    }

    private function category(string $title, ?int $urlId = 1): int
    {
        $this->conn()->executeStatement(
            'INSERT INTO session_category (access_url_id, title, date_start) VALUES (?, ?, \'2030-01-01\')',
            [$urlId, 'sa2_'.$title.'_'.(++$this->seq)]
        );

        return (int) $this->conn()->lastInsertId();
    }

    public function testCategoryDeletionNeedsEverySessionInItToBeManageable(): void
    {
        [$mine, $theirs] = $this->fixture();
        $empty = $this->category('empty');
        $mixed = $this->category('mixed');
        $ownOnly = $this->category('own');
        $mine2 = $this->rawSession('mine2');
        $this->rel($mine2, $this->users['owner'], Session::SESSION_ADMIN);
        $this->conn()->executeStatement('UPDATE session SET session_category_id = ? WHERE id IN (?, ?)', [$mixed, $mine, $theirs]);
        $this->conn()->executeStatement('UPDATE session SET session_category_id = ? WHERE id = ?', [$ownOnly, $mine2]);

        $out = [];
        foreach (['admin', 'owner', 'student'] as $role) {
            $this->actAs($role);
            $out[$role] = [
                SessionManager::canDeleteSessionCategory($empty),
                SessionManager::canDeleteSessionCategory($mixed),
                SessionManager::canDeleteSessionCategory($ownOnly),
            ];
        }
        $this->actAs('admin');

        $this->assertSame(
            [
                'admin' => [true, true, true],
                'owner' => [true, false, true],
                'student' => [false, false, false],
            ],
            $out,
            'A session admin may only drop a category whose sessions he manages (the sessions are deleted or unlinked).'
        );
    }

    public function testCategoryOfAnotherAccessUrlCannotBeDeleted(): void
    {
        $this->fixture();
        $foreign = $this->category('foreign', null);
        $this->actAs('admin');

        $this->assertFalse(SessionManager::canDeleteSessionCategory($foreign), 'Even for a platform admin.');
        $this->assertFalse(SessionManager::canDeleteSessionCategory(0));
    }

    private function page(string $relative): string
    {
        return (string) file_get_contents(__DIR__.'/../../../public/main/'.$relative);
    }

    /**
     * Source-level pin: the category list used to delete categories (and, with delete_on_session, sessions) on a
     * plain GET without any token, for any posted category id.
     */
    public function testCategoryListChecksTokenAndScopeBeforeDeleting(): void
    {
        $source = $this->page('session/session_category_list.php');

        $this->assertStringContainsString("Security::check_token('request')", $source);
        $this->assertStringContainsString('SessionManager::canDeleteSessionCategory(', $source);
        $this->assertLessThan(
            strpos($source, 'SessionManager::delete_session_category('),
            strpos($source, "Security::check_token('request')"),
            'The token is checked before anything is deleted.'
        );
        $this->assertStringContainsString('Security::get_existing_token()', $source);
        $this->assertStringNotContainsString('Security::get_token()', $source, 'A new token on each request breaks the links of the previous page.');
        $this->assertStringContainsString('sec_token=', $source, 'The delete links carry the token.');
    }

    /**
     * Source-level pin: these pages are protected for ONE session id (or only for the role) but act on lists of
     * session / course ids from the body. A session admin of one session could copy the students of any session,
     * enrol the coaches of any session as TEACHERS of any course, or move any session into a category.
     */
    public function testPagesActingOnPostedIdListsScopeThemToWhatTheUserManages(): void
    {
        $students = $this->page('session/add_students_to_session.php');
        $this->assertStringContainsString("filterManageableSessionIds(\$_POST['sessions'] ?? [])", $students);
        $this->assertStringContainsString("filterManageableSessionIds(\$_POST['sessions_destination'] ?? [])", $students);
        $this->assertStringNotContainsString("= \$_POST['sessions'] ?? [];", $students);

        $teachers = $this->page('session/add_teachers_to_session.php');
        $this->assertStringContainsString("filterManageableSessionIds(\$_POST['sessions'])", $teachers);
        $this->assertStringContainsString('array_intersect(', $teachers, 'Teacher courses of a non-admin are limited to this session.');

        $category = $this->page('session/add_many_session_to_category.php');
        $this->assertStringContainsString("Security::check_token('post')", $category);
        $this->assertStringContainsString('SessionManager::filterManageableSessionIds(', $category);
        $this->assertStringContainsString('SessionManager::get_all_session_category()', $category, 'The category must be one of the current URL.');
        $this->assertStringNotContainsString("'>'.\$category['title'].'</option>'", $category, 'Category titles are escaped.');
        $this->assertStringNotContainsString("<?php echo \$enreg['title']; ?>", $category, 'Session titles are escaped.');
    }

    /**
     * Source-level pin: the overview ran its delete / move / add_user_to_url actions on a plain GET without a
     * token, and add_user_to_url (giving ANY user id access to the URL) only needed the right to manage this
     * session, which a general coach has.
     */
    public function testSessionOverviewActionsNeedTheTokenAndUrlAccessNeedsAPlatformAdmin(): void
    {
        $source = $this->page('session/resume_session.php');

        $this->assertStringContainsString("!Security::check_token('get')", $source);
        $this->assertLessThan(
            strpos($source, "switch (\$action)"),
            strpos($source, "!Security::check_token('get')"),
            'The token is checked before any action runs.'
        );
        $this->assertSame(4, substr_count($source, 'sec_token='), 'move up, move down, delete course, delete user.');
        $this->assertStringContainsString('Security::get_existing_token()', $source);
        $this->assertMatchesRegularExpression(
            "/case 'add_user_to_url':\s*\/\/[^\n]*\n\s*if \(!api_is_platform_admin\(\)\) \{\s*api_not_allowed\(true\);/",
            $source
        );
    }

    /**
     * The "update existing sessions" import matched sessions BY NAME: a session admin without manage-all
     * overwrote (dates, users, courses) the sessions of other admins.
     */
    public function testCsvImportUpdateSkipsSessionsTheUserDoesNotManage(): void
    {
        [$mine, $theirs] = $this->fixture();
        $this->conn()->executeStatement('UPDATE session SET description = ?, show_description = 0 WHERE id IN (?, ?)', ['orig', $mine, $theirs]);
        $csv = tempnam(sys_get_temp_dir(), 'sa2');
        file_put_contents(
            $csv,
            "SessionName,DateStart,DateEnd,SessionDescription\n".
            "sa2_mine,2031-01-01,2031-02-01,changed\n".
            "sa2_theirs,2031-01-01,2031-02-01,changed\n"
        );

        $this->actAs('owner');
        try {
            $result = SessionManager::importCSV($csv, true, $this->users['owner']);
        } finally {
            unlink($csv);
        }
        $this->fresh();
        $this->actAs('admin');

        $this->assertSame('changed', $this->one('SELECT description FROM session WHERE id = ?', [$mine]));
        $this->assertSame('orig', $this->one('SELECT description FROM session WHERE id = ?', [$theirs]), 'Not his session.');
        $this->assertNotEmpty($result['error_message'], 'The skipped session is reported.');
        $this->assertStringContainsString('sa2_theirs', (string) $result['error_message']);
    }

    public function testSessionEditOnlyAcceptsAManagedParentSession(): void
    {
        $source = $this->page('session/session_edit.php');
        $this->assertStringContainsString('SessionManager::canManageSessionId($parentId)', $source);
        $this->assertStringContainsString('$parentId === (int) $id', $source);
    }

    public function testXmlImportUpdateGuardIsInPlace(): void
    {
        $source = (string) file_get_contents(__DIR__.'/../../../public/main/inc/lib/sessionmanager.lib.php');
        $xml = substr($source, (int) strpos($source, 'public static function importXML'));
        $this->assertMatchesRegularExpression(
            '/if \(\$updateSession && \$mySession\) \{\s*\$sessionId = \$mySession\[\'id\'\];\s*if \(!self::canImportOverwriteSession/',
            $xml
        );
    }

    /**
     * Source-level pin: user/resume_session.php (tutors assigning students) kept the old behaviour: delete and
     * add_user_to_url on a GET without token, and add_user_to_url open to the general coach.
     */
    public function testTutorSessionOverviewActionsNeedTheTokenAndUrlAccessNeedsAPlatformAdmin(): void
    {
        $source = $this->page('user/resume_session.php');

        $this->assertLessThan(
            strpos($source, 'switch ($action)'),
            strpos($source, "!Security::check_token('get')"),
            'The token is checked before any action runs.'
        );
        $this->assertStringContainsString('Security::get_existing_token()', $source);
        $this->assertSame(2, substr_count($source, "&sec_token='.\$token"), 'delete user and add_user_to_url links.');
        $this->assertMatchesRegularExpression(
            "/case 'add_user_to_url':\s*\/\/[^\n]*\n\s*if \(!api_is_platform_admin\(\)\) \{\s*api_not_allowed\(true\);/",
            $source
        );
    }

    /**
     * Source-level pin: the scheduled announcements page is open to every session admin and edited / deleted any
     * announcement id (of any session) on a GET without token; the hidden session_id of the form decided which
     * session a new announcement was saved into.
     */
    public function testScheduledAnnouncementsAreBoundToTheManagedSession(): void
    {
        $source = $this->page('session/scheduled_announcement.php');

        $this->assertStringContainsString('SessionManager::canManageSessionId($sessionId)', $source);
        $this->assertStringContainsString("(int) \$item['session_id'] !== \$sessionId", $source, 'The announcement is one of this session.');
        $this->assertStringContainsString("'delete' === \$action && !Security::check_token('get')", $source);
        $this->assertSame(2, substr_count($source, "\$values['session_id'] = \$sessionId;"), 'add and edit ignore the posted session id.');
        $this->assertStringContainsString("&sec_token='.\$token", $source, 'The delete link carries the token.');
        $this->assertStringContainsString('Security::get_existing_token()', $source);
    }

    /**
     * Source-level pin: the session reports of model.ajax.php and the session export were open to every session
     * admin for any session id, whether or not he manages it.
     */
    public function testSessionReportsAndExportAreLimitedToManagedSessions(): void
    {
        $ajax = $this->page('inc/ajax/model.ajax.php');
        foreach (['get_session_lp_progress', 'get_session_progress', 'get_exercise_progress', 'get_session_access_overview', 'get_survey_overview'] as $report) {
            $this->assertMatchesRegularExpression(
                "/\\\$action, \\[[^\\]]*'".$report."'[^\\]]*\\], true\\)\\s*&& !api_is_platform_admin\\(\\)/",
                $ajax,
                $report.' is gated by the managed-session check.'
            );
        }
        $this->assertStringContainsString('SessionManager::canManageSessionId($reportSessionId)', $ajax);

        $export = $this->page('session/session_export.php');
        $this->assertLessThan(
            strpos($export, "\$row['title'] = str_replace"),
            strpos($export, "SessionManager::canManageSessionId((int) \$row['id'])"),
            'Rows of foreign sessions are skipped before anything is read about them.'
        );
    }

    /**
     * getSessionsFollowedByUser() rows come straight from the session table, whose name column is now title:
     * the anonymisation page read and wrote the removed column (an SQL error on "Anonymize user's sessions").
     */
    public function testFollowedSessionRowsCarryTheTitleTheAnonymisationPageReads(): void
    {
        $this->fixture();
        $boss = $this->rawUser('anonstu');
        $session = $this->rawSession('anon');
        $this->rel($session, $boss, Session::STUDENT);

        $rows = SessionManager::getSessionsFollowedByUser($boss);

        $this->assertCount(1, $rows);
        $this->assertSame('sa2_anon', $rows[array_key_first($rows)]['title']);
        $this->assertArrayNotHasKey('name', $rows[array_key_first($rows)], 'The page must not read name.');
    }

    /**
     * Source-level pin: the page admits session admins, anonymises irreversibly and had no token; a session
     * admin could wipe a platform admin account.
     */
    public function testAnonymisationImportIsProtectedAndUsesTheTitleColumn(): void
    {
        $source = $this->page('admin/user_anonymize_import.php');

        $this->assertStringContainsString('$step2Form->protect();', $source);
        $this->assertStringContainsString('!api_is_platform_admin() && UserManager::is_admin($userId)', $source);
        $this->assertStringContainsString("SET title = '", $source);
        $this->assertStringNotContainsString("SET name = '", $source);
        $this->assertStringNotContainsString("\$session['name']", $source);
        $this->assertStringContainsString('empty($errors)', $source);
    }

    /**
     * Source-level pin: unsubscribing courses or users from a session was a GET link (or a form) without a
     * token, and the user subscription page accepted a course id that is not in the session.
     */
    public function testSessionCourseListsCheckTheTokenAndTheCourseBelongsToTheSession(): void
    {
        foreach (['session/session_course_list.php', 'session/session_course_user_list.php'] as $page) {
            $source = $this->page($page);
            $this->assertLessThan(
                strpos($source, "'delete' === \$action) {\n    if"),
                strpos($source, 'Security::get_existing_token()'),
                $page
            );
            $this->assertStringContainsString("if ('delete' === \$action) {\n    if (!Security::check_token('request')) {", $source, $page);
            $this->assertSame(1, substr_count($source, "name=\"sec_token\""), $page.': the bulk form carries the token.');
            $this->assertSame(1, substr_count($source, "&sec_token='.\$token"), $page.': the row link carries the token.');
        }

        $source = $this->page('session/add_users_to_session_course.php');
        $this->assertStringContainsString('$sessionHasCourse', $source);
        $this->assertLessThan(
            strpos($source, '$xajax->processRequests();'),
            strpos($source, '$sessionHasCourse = false;'),
            'The course is checked before the AJAX search runs too.'
        );
    }

    /**
     * Source-level pin: get_description (public when the catalogue is) echoed the session title unescaped.
     */
    public function testSessionDescriptionAnswerEscapesTheTitle(): void
    {
        $source = $this->page('inc/ajax/session.ajax.php');

        $this->assertStringContainsString("'<h2>'.api_htmlentities(\$sessionInfo['title']).'</h2>'", $source);
        $this->assertStringNotContainsString("'<h2>'.\$sessionInfo['name']", $source);
    }
}
