<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use ChamiloSession;
use Database;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session as HttpSession;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * Second characterization pass over public/main/inc/lib/api.lib.php: the branches the first pass
 * (ApiLibCharacterizationTest) never reached - api_get_anonymous_id() with several anonymous users allowed, and the
 * other-access-url branches of api_set_setting().
 */
final class ApiLibTier2CharacterizationTest extends AbstractApiTest
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

    private function anonymousIds(): array
    {
        return array_map('intval', $this->conn()->fetchFirstColumn('SELECT id FROM user WHERE status = 6 ORDER BY id'));
    }

    public function testAnonymousIdWithSeveralAnonymousUsersReusesTheOneAlreadyConnectedFromThisIp(): void
    {
        $this->setSetting('admin.max_anonymous_users', '5');
        $anon = $this->rawUser('anon_ip', 6);
        $otherIpAnon = $this->rawUser('anon_other_ip', 6);
        $student = $this->rawUser('student_ip', 5);
        $ip = api_get_real_ip();
        $this->conn()->executeStatement(
            "INSERT INTO track_e_login (login_user_id, user_ip, login_date, logout_date) VALUES (?, ?, '2020-01-01 00:00:00', '2020-01-01 00:00:00')",
            [$otherIpAnon, '203.0.113.99']
        );
        $this->conn()->executeStatement(
            "INSERT INTO track_e_login (login_user_id, user_ip, login_date, logout_date) VALUES (?, ?, '2020-01-01 00:00:00', '2020-01-01 00:00:00')",
            [$student, $ip]
        );
        $before = $this->anonymousIds();

        // A login from this IP exists only for a non-anonymous user, and one from another IP for an anonymous one:
        // no anonymous user is connected from here, so the function tries to create a new one. (UserManager
        // refuses the address it passes - it starts with a space - so nothing comes back and nothing is created.)
        $this->assertFalse(api_get_anonymous_id());
        $this->assertSame($before, $this->anonymousIds());

        $this->conn()->executeStatement(
            "INSERT INTO track_e_login (login_user_id, user_ip, login_date, logout_date) VALUES (?, ?, '2020-01-01 00:00:00', '2020-01-01 00:00:00')",
            [$anon, $ip]
        );
        $this->assertSame($anon, (int) api_get_anonymous_id());
        $this->assertSame($before, $this->anonymousIds());
    }

    public function testAnonymousIdIgnoresTheBuiltInAnonymousUserNumberTwoWhenSeveralAreAllowed(): void
    {
        $this->setSetting('admin.max_anonymous_users', '5');
        $ip = api_get_real_ip();
        $this->conn()->executeStatement(
            "INSERT INTO track_e_login (login_user_id, user_ip, login_date, logout_date) VALUES (2, ?, '2020-01-01 00:00:00', '2020-01-01 00:00:00')",
            [$ip]
        );
        $before = $this->anonymousIds();

        // The only login from this IP is the built-in anonymous user 2, which is excluded: nobody is connected.
        $this->assertFalse(api_get_anonymous_id());
        $this->assertSame($before, $this->anonymousIds());
    }

    public function testAnonymousIdIsZeroWhenNoAnonymousUserExistsAndASingleOneIsAllowed(): void
    {
        $this->setSetting('admin.max_anonymous_users', '0');
        $this->conn()->executeStatement('UPDATE user SET status = 5 WHERE status = 6');

        $this->assertSame(0, api_get_anonymous_id());
    }

    private function secondUrl(): int
    {
        $this->conn()->executeStatement(
            "INSERT INTO access_url (url, active, description, created_by, lft, rgt, lvl) VALUES ('http://ap-tier2.invalid/', 1, '', 1, 1, 2, 0)"
        );

        return (int) $this->conn()->lastInsertId();
    }

    private function settingRows(string $variable): array
    {
        return $this->conn()->fetchAllAssociative(
            'SELECT access_url, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable
             FROM settings WHERE variable = ? ORDER BY access_url, id',
            [$variable]
        );
    }

    public function testSetSettingStoresTheValueVerbatimIncludingQuotes(): void
    {
        $this->conn()->executeStatement(
            "INSERT INTO settings (access_url, variable, subkey, type, category, selected_value, title, access_url_changeable)
             VALUES (1, 'ap_q', NULL, 'textfield', 'ap_catq', 'old', 'ap', 0)"
        );

        api_set_setting('ap_q', 'it\'s "quoted" \\ back', null, 'ap_catq');

        $this->assertSame('it\'s "quoted" \\ back', $this->conn()->fetchOne("SELECT selected_value FROM settings WHERE variable = 'ap_q'"));
    }

    public function testSetSettingFiltersOnSubkeyAndCategoryAndUsesAccessUrlOneByDefault(): void
    {
        $second = $this->secondUrl();
        $this->conn()->executeStatement(
            "INSERT INTO settings (access_url, variable, subkey, type, category, selected_value, title, access_url_changeable) VALUES
             (1, 'ap_f', 'k', 'textfield', 'ap_c1', 'old', 'ap', 1),
             (1, 'ap_f', 'j', 'textfield', 'ap_c1', 'old', 'ap', 1),
             (1, 'ap_f', NULL, 'textfield', 'ap_c2', 'old', 'ap', 1)"
        );
        $values = fn (): array => array_column($this->settingRows('ap_f'), 'selected_value', 'subkey');

        // Right subkey, wrong category: nothing matches.
        api_set_setting('ap_f', 'new', 'k', 'ap_c2');
        $this->assertSame('old', $values()['k']);
        $this->assertSame('old', $values()['j']);

        api_set_setting('ap_f', 'new', 'k', 'ap_c1');
        $this->assertSame('new', $values()['k']);
        $this->assertSame('old', $values()['j']);

        // Without subkey the first row of that variable and category is the one updated.
        api_set_setting('ap_f', 'cat only', null, 'ap_c2');
        $this->assertSame('cat only', $this->conn()->fetchOne("SELECT selected_value FROM settings WHERE variable = 'ap_f' AND category = 'ap_c2'"));

        // Access url 0 (or empty) means the main url, never the second one.
        api_set_setting('ap_f', 'zero', 'j', 'ap_c1', 0);
        $this->assertSame('zero', $values()['j']);
        $this->assertSame(0, (int) $this->conn()->fetchOne('SELECT COUNT(*) FROM settings WHERE variable = ? AND access_url = ?', ['ap_f', $second]));
    }

    public function testSetSettingWithANegativeAccessUrlUpdatesTheMainUrlRow(): void
    {
        $this->conn()->executeStatement(
            "INSERT INTO settings (access_url, variable, subkey, type, category, selected_value, title, access_url_changeable)
             VALUES (1, 'ap_neg', NULL, 'textfield', 'ap_cneg', 'old', 'ap', 0)"
        );

        api_set_setting('ap_neg', 'new', null, 'ap_cneg', -5);

        $this->assertSame('new', $this->conn()->fetchOne("SELECT selected_value FROM settings WHERE variable = 'ap_neg'"));
    }

    public function testSetSettingOnAnotherAccessUrlUpdatesItsOwnRowOnly(): void
    {
        $second = $this->secondUrl();
        $this->conn()->executeStatement(
            "INSERT INTO settings (access_url, variable, subkey, type, category, selected_value, title, access_url_changeable) VALUES
             (1, 'ap_own', NULL, 'textfield', 'ap_co', 'main', 'ap', 1),
             (?, 'ap_own', NULL, 'textfield', 'ap_co', 'second', 'ap', 1)",
            [$second]
        );

        api_set_setting('ap_own', 'changed', null, 'ap_co', $second);

        $this->assertSame(['main', 'changed'], array_column($this->settingRows('ap_own'), 'selected_value'));
    }

    public function testSetSettingOnAnotherAccessUrlCopiesTheMainRowWhenItIsChangeable(): void
    {
        $second = $this->secondUrl();
        $this->conn()->executeStatement(
            "INSERT INTO settings (access_url, variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable)
             VALUES (1, 'ap_copy', 'sk', 'radio', 'ap_cc', 'main', 'Copy title', 'a comment', 'sc', 'sk text', 1)"
        );

        api_set_setting('ap_copy', 'for second', 'sk', 'ap_cc', $second);

        $rows = $this->settingRows('ap_copy');
        $this->assertCount(2, $rows);
        $this->assertSame('main', $rows[0]['selected_value']);
        $this->assertSame(
            [
                'access_url' => $second,
                'subkey' => 'sk',
                'type' => 'radio',
                'category' => 'ap_cc',
                'selected_value' => 'for second',
                'title' => 'Copy title',
                'comment' => 'a comment',
                'scope' => 'sc',
                'subkeytext' => 'sk text',
                'access_url_changeable' => 1,
            ],
            array_map(static fn ($v) => is_numeric($v) && !is_string($v) ? (int) $v : $v, $rows[1])
        );
    }

    public function testSetSettingOnAnotherAccessUrlCopiesEmptyOptionalColumnsAsNull(): void
    {
        $second = $this->secondUrl();
        $this->conn()->executeStatement(
            "INSERT INTO settings (access_url, variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable)
             VALUES (1, 'ap_nul', '', 'textfield', 'ap_cn', 'main', 'T', '', '', '', 1)"
        );

        api_set_setting('ap_nul', 'v', null, 'ap_cn', $second);

        $copy = $this->settingRows('ap_nul')[1];
        $this->assertNull($copy['subkey']);
        $this->assertNull($copy['comment']);
        $this->assertNull($copy['scope']);
        $this->assertNull($copy['subkeytext']);
        $this->assertSame('v', $copy['selected_value']);
    }

    public function testSetSettingOnAnotherAccessUrlDoesNotCopyAnUnchangeableOrMissingSetting(): void
    {
        $second = $this->secondUrl();
        $this->conn()->executeStatement(
            "INSERT INTO settings (access_url, variable, subkey, type, category, selected_value, title, access_url_changeable)
             VALUES (1, 'ap_fixed', NULL, 'textfield', 'ap_cf', 'main', 'T', 0)"
        );

        api_set_setting('ap_fixed', 'x', null, 'ap_cf', $second);
        api_set_setting('ap_missing', 'x', null, 'ap_cf', $second);
        api_set_setting('ap_missing', 'x', null, 'ap_cf', 1);

        $this->assertCount(1, $this->settingRows('ap_fixed'));
        $this->assertSame([], $this->settingRows('ap_missing'));
    }

    // ---------------------------------------------------------------- pinned by the mutation pass

    private function newNode(): int
    {
        $this->conn()->executeStatement(
            "INSERT INTO resource_node (resource_type_id, title, slug, created_at, updated_at, public, uuid)
             VALUES (32, ?, ?, NOW(), NOW(), 0, UNHEX(REPLACE(UUID(), '-', '')))",
            ['ap2_node_'.(++$this->seq), 'ap2-node-'.getmypid().'-'.$this->seq]
        );

        return (int) $this->conn()->lastInsertId();
    }

    private function nodeLink(int $nodeId, int $courseId, ?int $sessionId): void
    {
        $this->conn()->executeStatement(
            'INSERT INTO resource_link (resource_node_id, c_id, session_id, visibility, display_order, resource_type_group, created_at, updated_at)
             VALUES (?, ?, ?, 2, 0, 0, NOW(), NOW())',
            [$nodeId, $courseId, $sessionId]
        );
    }

    /**
     * One row of a course resource table, hanging off a new resource node. Returns [iid, node id].
     *
     * @return array{0: int, 1: int}
     */
    private function resourceRow(string $kind): array
    {
        $node = $this->newNode();
        $title = 'ap2_'.$kind.'_'.(++$this->seq);
        switch ($kind) {
            case 'announcement':
                $this->conn()->executeStatement('INSERT INTO c_announcement (resource_node_id, title) VALUES (?, ?)', [$node, $title]);
                break;
            case 'agenda':
                $this->conn()->executeStatement(
                    'INSERT INTO c_calendar_event (resource_node_id, title, all_day, collective, subscription_visibility, max_attendees) VALUES (?, ?, 0, 0, 0, 0)',
                    [$node, $title]
                );
                break;
            case 'group':
                $this->conn()->executeStatement(
                    'INSERT INTO c_group_info (resource_node_id, title, status, max_student, doc_state, calendar_state, work_state,
                        announcements_state, forum_state, wiki_state, chat_state, self_registration_allowed, self_unregistration_allowed)
                     VALUES (?, ?, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0)',
                    [$node, $title]
                );
                break;
            case 'attendance':
                $this->conn()->executeStatement(
                    'INSERT INTO c_attendance (resource_node_id, title, active, attendance_qualify_max, attendance_weight, locked) VALUES (?, ?, 1, 0, 0, 0)',
                    [$node, $title]
                );
                break;
            case 'lp':
                $this->conn()->executeStatement(
                    "INSERT INTO c_lp (resource_node_id, lp_type, title, path, force_commit, content_maker, content_license, js_lib, debug, theme,
                        author, prerequisite, hide_toc_frame, seriousgame_mode, autolaunch, max_attempts, subscribe_users, created_on, modified_on)
                     VALUES (?, 1, ?, '', 0, '', '', '', 0, '', '', 0, 0, 0, 0, 0, 0, NOW(), NOW())",
                    [$node, $title]
                );
                break;
            case 'work':
                $this->conn()->executeStatement(
                    "INSERT INTO c_student_publication (resource_node_id, title, post_group_id, filetype, has_properties, qualification,
                        qualificator_id, weight, allow_text_assignment, contains_file, document_id)
                     VALUES (?, ?, 0, 'folder', 0, 0, 0, 0, 0, 0, 0)",
                    [$node, $title]
                );
                break;
            case 'thread':
                $this->conn()->executeStatement(
                    'INSERT INTO c_forum_thread (resource_node_id, title, thread_date, thread_sticky, locked, thread_qualify_max, thread_weight, thread_peer_qualify)
                     VALUES (?, ?, NOW(), 0, 0, 0, 0, 0)',
                    [$node, $title]
                );
                break;
            case 'survey':
                $this->conn()->executeStatement(
                    "INSERT INTO c_survey (resource_node_id, title, creation_date, invited, answered, invite_mail, reminder_mail, mail_subject,
                        anonymous, shuffle, one_question_per_page, survey_version, survey_type, show_form_profile, form_fields)
                     VALUES (?, ?, NOW(), 0, 0, '', '', '', '0', 0, 0, '', 0, 0, '')",
                    [$node, $title]
                );
                break;
        }

        return [(int) $this->conn()->lastInsertId(), $node];
    }

    public function testElementInTheSessionLooksInTheTableOfEachSupportedTool(): void
    {
        ChamiloSession::write('_real_cid', 1);
        $sid = $this->rawSession('tools', null, null);
        foreach (['announcement' => TOOL_ANNOUNCEMENT, 'agenda' => TOOL_AGENDA, 'group' => TOOL_GROUP, 'survey' => TOOL_SURVEY] as $kind => $tool) {
            [$iid, $node] = $this->resourceRow($kind);
            $this->assertFalse(api_is_element_in_the_session($tool, $iid, $sid), $kind.' without link');
            $this->nodeLink($node, 1, $sid);
            $this->assertTrue(api_is_element_in_the_session($tool, $iid, $sid), $kind);
        }
    }

    public function testResourceLockedByGradebookResolvesTheResourceOfEachLinkType(): void
    {
        $this->setSetting('gradebook.gradebook_locking_enabled', 'true');
        $types = [
            'attendance' => [LINK_ATTENDANCE],
            'lp' => [LINK_LEARNPATH],
            'work' => [LINK_STUDENTPUBLICATION],
            'thread' => [LINK_FORUM_THREAD, LINK_FORUM_PARTICIPATION],
            'survey' => [LINK_SURVEY],
        ];
        foreach ($types as $kind => $linkTypes) {
            foreach ($linkTypes as $linkType) {
                [$iid, $node] = $this->resourceRow($kind);
                $this->assertFalse(api_resource_is_locked_by_gradebook($iid, $linkType, 'AIACT'), $kind.' not linked');
                $this->conn()->executeStatement(
                    'INSERT INTO gradebook_link (ref_id, c_id, type, created_at, weight, visible, locked) VALUES (?, 1, ?, NOW(), 1, 1, 1)',
                    [$node, $linkType]
                );
                $this->assertTrue(api_resource_is_locked_by_gradebook($iid, $linkType, 'AIACT'), $kind.' link type '.$linkType);
                $this->assertFalse(api_resource_is_locked_by_gradebook($iid, $linkType, 'USINGCHAMILO'), $kind.' link of another course');
            }
        }
    }

    public function testPlatformAdminOfAnAccessUrlNeedsExactlyOneLinkRow(): void
    {
        $this->conn()->executeStatement('DELETE FROM access_url_rel_user WHERE user_id = 1 AND access_url_id = 1');
        $this->assertFalse(api_is_platform_admin_by_id(1, 1));
        $this->conn()->executeStatement('INSERT INTO access_url_rel_user (access_url_id, user_id) VALUES (1, 1)');
        $this->assertTrue(api_is_platform_admin_by_id(1, 1));
        $this->conn()->executeStatement('INSERT INTO access_url_rel_user (access_url_id, user_id) VALUES (1, 1)');
        $this->assertFalse(api_is_platform_admin_by_id(1, 1), 'Two link rows are not "exactly one".');
    }

    public function testSessionVisibilityWithADurationCountsFromTheFirstCourseAccess(): void
    {
        $user = $this->rawUser('dur', 5);
        $sid = $this->rawSession('dur', null, null, ['duration' => 2]);
        $this->assertSame(SESSION_AVAILABLE, api_get_session_visibility($sid, null, false, $user), 'No access yet: still available.');

        $this->conn()->executeStatement(
            "INSERT INTO track_e_course_access (c_id, user_id, session_id, login_course_date, logout_course_date, counter, user_ip)
             VALUES (1, ?, ?, ?, ?, 1, '10.0.0.1')",
            [$user, $sid, gmdate('Y-m-d H:i:s', time() - 10 * 86400), gmdate('Y-m-d H:i:s', time() - 10 * 86400)]
        );
        $this->assertSame(SESSION_VISIBLE_READ_ONLY, (int) api_get_session_visibility($sid, null, false, $user), 'Two days from ten days ago is over.');

        $long = $this->rawSession('dur_long', null, null, ['duration' => 30]);
        $this->conn()->executeStatement(
            "INSERT INTO track_e_course_access (c_id, user_id, session_id, login_course_date, logout_course_date, counter, user_ip)
             VALUES (1, ?, ?, ?, ?, 1, '10.0.0.1')",
            [$user, $long, gmdate('Y-m-d H:i:s', time() - 10 * 86400), gmdate('Y-m-d H:i:s', time() - 10 * 86400)]
        );
        $this->assertSame(SESSION_AVAILABLE, api_get_session_visibility($long, null, false, $user), 'Thirty days from ten days ago is not.');
    }

    public function testCourseVisibleForTheCoachOfASessionOfThatCourseOnly(): void
    {
        $this->conn()->executeStatement('UPDATE course SET visibility = 1 WHERE id IN (1, 2)');
        $coach = $this->rawUser('vis_coach', 5);
        $sessionAdmin = $this->rawUser('vis_admin', 5);
        $stranger = $this->rawUser('vis_stranger', 5);
        $sid = $this->rawSession('vis', null, null);
        $this->sessionUser($sid, $coach, \Chamilo\CoreBundle\Entity\Session::GENERAL_COACH);
        $this->sessionUser($sid, $sessionAdmin, \Chamilo\CoreBundle\Entity\Session::SESSION_ADMIN);
        $this->conn()->executeStatement('INSERT INTO session_rel_course (session_id, c_id, position, nbr_users) VALUES (?, 1, 0, 0)', [$sid]);
        $code1 = (string) $this->conn()->fetchOne('SELECT code FROM course WHERE id = 1');
        $code2 = (string) $this->conn()->fetchOne('SELECT code FROM course WHERE id = 2');

        $this->assertTrue(api_is_course_visible_for_user($coach, $code1), 'The coach of a session of the course sees it.');
        $this->assertFalse(api_is_course_visible_for_user($coach, $code2), 'The session does not contain that course.');
        $this->assertFalse(api_is_course_visible_for_user($sessionAdmin, $code1), 'A session administrator is not a member.');
        $this->assertFalse(api_is_course_visible_for_user($stranger, $code1));
    }

    public function testCourseVisibleForTheCourseCoachOfASession(): void
    {
        $this->conn()->executeStatement('UPDATE course SET visibility = 1 WHERE id IN (1, 2)');
        $courseCoach = $this->rawUser('vis_cc', 5);
        $sid = $this->rawSession('viscc', null, null);
        $this->sessionCourseUser($sid, 1, $courseCoach, \Chamilo\CoreBundle\Entity\Session::COURSE_COACH);
        $code1 = (string) $this->conn()->fetchOne('SELECT code FROM course WHERE id = 1');
        $code2 = (string) $this->conn()->fetchOne('SELECT code FROM course WHERE id = 2');

        $this->assertTrue(api_is_course_visible_for_user($courseCoach, $code1));
        $this->assertFalse(api_is_course_visible_for_user($courseCoach, $code2));
    }

    private function extraUrls(int $count): array
    {
        $ids = [];
        for ($i = 0; $i < $count; ++$i) {
            $this->conn()->executeStatement(
                "INSERT INTO access_url (url, active, description, created_by, lft, rgt, lvl) VALUES (?, 1, 'ap2', 1, 1, 2, 0)",
                ['http://ap-tier2-'.getmypid().'-'.(++$this->seq).'.invalid/']
            );
            $ids[] = (int) $this->conn()->lastInsertId();
        }

        return $ids;
    }

    public function testAccessUrlsListIsPagedAndSortedOnTheGivenColumn(): void
    {
        [$a, $b] = $this->extraUrls(2);
        $all = array_map('intval', $this->conn()->fetchFirstColumn('SELECT id FROM access_url ORDER BY id'));

        $page = api_get_access_urls(1, 2, 'id', 'ASC');
        $this->assertSame(array_slice($all, 1, 2), array_map(static fn (array $r): int => $r['id'], $page));
        $this->assertSame(['id', 'url', 'description', 'active', 'created_by', 'tms'], array_keys($page[0]));
        $this->assertSame('ap2', $page[count($page) - 1]['description'] ?? $page[0]['description']);
        $this->assertIsInt($page[0]['active']);
        $this->assertIsInt($page[0]['created_by']);

        $desc = api_get_access_urls(0, 100, 'id', 'DESC');
        $this->assertSame(array_reverse($all), array_map(static fn (array $r): int => $r['id'], $desc));
        $this->assertSame([$b, $a], array_slice(array_map(static fn (array $r): int => $r['id'], $desc), 0, 2));
        $this->assertSame($all, array_map(static fn (array $r): int => $r['id'], api_get_access_urls(0, 100, 'id', 'bogus')), 'Unknown direction means ascending.');
        $byUrl = api_get_access_urls(0, 100);
        $urls = array_column($byUrl, 'url');
        $sorted = $urls;
        sort($sorted, SORT_STRING);
        $this->assertSame($sorted, $urls, 'Default order is by url.');
    }

    public function testGetAccessUrlRowAlsoCarriesTheNumericKeys(): void
    {
        $row = api_get_access_url(1);

        $this->assertSame(['url', 'description', 'active', 'created_by', 'tms', 0, 1, 2, 3, 4], array_keys($row));
        $this->assertSame($row['url'], $row[0]);
        $this->assertSame($row['tms'], $row[4]);
        $this->assertSame([], api_get_access_url(999999999));
    }

    public function testActiveUrlsCarryEveryColumnOfTheRowWithTheRawTypes(): void
    {
        [$id] = $this->extraUrls(1);
        $this->conn()->executeStatement('UPDATE access_url SET active = 0 WHERE id <> ? AND id <> 1', [$id]);

        $urls = api_get_active_urls();
        $row = $urls[array_search($id, array_column($urls, 'id'), true)];

        $this->assertSame(
            [
                'id', 'resource_node_id', 'parent_id', 'tree_root', 'lft', 'lvl', 'rgt', 'url', 'description', 'active',
                'created_by', 'tms', 'url_type', 'limit_courses', 'limit_active_courses', 'limit_sessions', 'limit_users',
                'limit_teachers', 'limit_disk_space', 'email', 'is_login_only',
            ],
            array_keys($row)
        );
        $this->assertSame(1, $row['active']);
        $this->assertSame(0, $row['is_login_only']);
        $this->assertSame('ap2', $row['description']);
        $this->conn()->executeStatement('UPDATE access_url SET active = 0 WHERE id = ?', [$id]);
        $this->assertNotContains($id, array_column(api_get_active_urls(), 'id'));
    }

    public function testToolInformationKeepsTheFirstToolRowWhenSeveralMatch(): void
    {
        ChamiloSession::write('_real_cid', 1);
        $toolId = (int) $this->conn()->fetchOne("SELECT id FROM tool WHERE title = 'document'");
        $this->conn()->executeStatement('DELETE FROM c_tool WHERE c_id = 1 AND tool_id = ?', [$toolId]);
        $this->conn()->executeStatement("INSERT INTO c_tool (c_id, tool_id, title, position, resource_node_id) VALUES (1, ?, 'ap2_first', 1, ?)", [$toolId, $this->newNode()]);
        $first = (int) $this->conn()->lastInsertId();
        $this->conn()->executeStatement("INSERT INTO c_tool (c_id, tool_id, title, position, resource_node_id) VALUES (1, ?, 'ap2_second', 2, ?)", [$toolId, $this->newNode()]);

        $row = api_get_tool_information_by_name('document');

        $this->assertSame($first, $row['iid']);
        $this->assertSame('ap2_first', $row['title']);
        $this->assertSame(['iid', 'resource_node_id', 'c_id', 'session_id', 'tool_id', 'title', 'position', 'visibility'], array_keys($row));
        $this->assertNull($row['visibility']);
        $this->assertNull($row['session_id']);
        $this->assertSame(1, $row['c_id']);
        $this->assertSame($toolId, $row['tool_id']);
    }
}
