<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Legacy;

use Chamilo\CoreBundle\Exception\NotAllowedException;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\Tests\ChamiloTestTrait;
use Database;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Uid\UuidV4;
use Tracking;

/**
 * Second characterization file for the legacy Tracking library: the coach and group reporting, the averages that
 * mix entity queries with raw SQL, the Doctrine count family, the platform-setting branches and the state moves.
 *
 * Why it exists: the first file (TrackingCharacterizationTest) skipped these because they need sessions, coaches,
 * groups and resource-linked entities. They are about to be rewritten around Doctrine, so every assertion pins
 * what the CURRENT code returns for rows seeded with fixed values (exact numbers on rows created here, or deltas),
 * so that a rewrite changing a scope (who a coach sees), an aggregate, a boundary or the NULL/zero handling fails
 * here instead of silently changing a report. Quirks are flagged "PINNED QUIRK": not endorsed, only recorded.
 *
 * Fixtures: only prepared statements. Users, sessions and resource nodes are raw rows (the repositories insert
 * resource nodes and deadlock against concurrent suites on the shared database); courses 1 and 2 are the
 * installer fixtures. Everything is tagged "tc_" and rolled back with the DAMA transaction.
 */
final class TrackingCoachCharacterizationTest extends KernelTestCase
{
    use ChamiloTestTrait;

    private const COURSE = 1;
    private const COURSE_TWO = 2;

    // Session::STUDENT / GENERAL_COACH relation types and COURSE_COACH course status.
    private const REL_STUDENT = 0;
    private const REL_GENERAL_COACH = 3;
    private const STATUS_STUDENT = 0;
    private const STATUS_COURSE_COACH = 2;

    private Connection $conn;
    private int $seq = 0;
    private int $creator = 0;
    /** @var array<string, string> */
    private array $originalSettings = [];

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        Container::setContainer($container);
        Container::setLegacyServices($container);
        $httpSession = new Session(new MockArraySessionStorage());
        Container::setSession($httpSession);
        $request = Request::create('/main/mySpace/index.php');
        $request->setSession($httpSession);
        Container::setRequest($request);

        $this->conn = $container->get('doctrine')->getConnection();
        Database::setManager($container->get('doctrine')->getManager());
        \ChamiloSession::erase('system_timezone');
        $this->creator = $this->user('tc_creator');
        $this->originalSettings = [
            'platform.timezone' => (string) Container::getSettingsManager()->getSetting('platform.timezone'),
            'lp.lp_minimum_time' => (string) Container::getSettingsManager()->getSetting('lp.lp_minimum_time'),
        ];
    }

    protected function tearDown(): void
    {
        // The settings manager keeps its values in a cache that outlives the DAMA rollback: put them back so
        // that the timezone or minimum-time tests below cannot leak into the next test or the next suite.
        foreach ($this->originalSettings as $name => $value) {
            Container::getSettingsManager()->updateSetting($name, $value);
        }
        parent::tearDown();
    }

    // ---------------------------------------------------------------- fixtures

    private function user(string $name): int
    {
        $name .= '_'.(++$this->seq).'_'.substr(md5((string) microtime(true)), 0, 6);
        $this->conn->executeStatement(
            "INSERT INTO user (username, username_canonical, locale, password, timezone, email, email_canonical,
                locked, expired, credentials_expired, salt, roles, status, active, uuid, created_at, updated_at,
                firstname, lastname)
             VALUES (?, ?, 'en_US', 'x', 'UTC', ?, ?, 0, 0, 0, 'x', '[]', 5, 1, ?,
                '2001-01-01 00:00:00', '2001-01-01 00:00:00', ?, 'tc')",
            [$name, $name, $name.'@example.com', $name.'@example.com', UuidV4::v4()->toBinary(), $name]
        );

        return (int) $this->conn->lastInsertId();
    }

    private function session(string $title = 'tc_session', ?string $start = null, ?string $end = null): int
    {
        $this->conn->executeStatement(
            'INSERT INTO session (title, nbr_courses, nbr_users, nbr_classes, visibility, status,
                access_start_date, access_end_date)
             VALUES (?, 0, 0, 0, 1, 0, ?, ?)',
            [$title.'_'.(++$this->seq), $start, $end]
        );
        $id = (int) $this->conn->lastInsertId();
        $this->conn->executeStatement(
            'INSERT INTO access_url_rel_session (access_url_id, session_id) VALUES (?, ?)',
            [api_get_current_access_url_id(), $id]
        );

        return $id;
    }

    private function sessionCourse(int $sessionId, int $courseId): void
    {
        $this->conn->executeStatement(
            'INSERT INTO session_rel_course (session_id, c_id, position, nbr_users) VALUES (?, ?, 0, 0)',
            [$sessionId, $courseId]
        );
    }

    private function sessionUser(int $sessionId, int $userId, int $relationType): void
    {
        $this->conn->executeStatement(
            "INSERT INTO session_rel_user (session_id, user_id, relation_type, duration, registered_at)
             VALUES (?, ?, ?, 0, '2001-01-01 00:00:00')",
            [$sessionId, $userId, $relationType]
        );
    }

    private function sessionCourseUser(int $sessionId, int $courseId, int $userId, int $status): void
    {
        $this->conn->executeStatement(
            'INSERT INTO session_rel_course_rel_user (session_id, c_id, user_id, status, visibility, legal_agreement, progress)
             VALUES (?, ?, ?, ?, 1, 0, 0)',
            [$sessionId, $courseId, $userId, $status]
        );
    }

    private function node(int $resourceTypeId, string $title): int
    {
        static $n = 0;
        $this->conn->executeStatement(
            "INSERT INTO resource_node (resource_type_id, creator_id, title, slug, level, path, created_at, updated_at, public, uuid)
             VALUES (?, ?, ?, ?, 0, ?, '2001-01-01 00:00:00', '2001-01-01 00:00:00', 0, ?)",
            [$resourceTypeId, $this->creator, $title, 'tc-'.(++$n).'-'.(++$this->seq), 'tc-'.$this->seq.'-'.$n.'/', UuidV4::v4()->toBinary()]
        );

        return (int) $this->conn->lastInsertId();
    }

    private function link(int $nodeId, int $courseId, ?int $sessionId = null, ?int $groupId = null, int $visibility = 2): int
    {
        $this->conn->executeStatement(
            "INSERT INTO resource_link (resource_node_id, c_id, session_id, group_id, visibility, display_order,
                resource_type_group, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, 0, 0, '2001-01-01 00:00:00', '2001-01-01 00:00:00')",
            [$nodeId, $courseId, $sessionId, $groupId, $visibility]
        );

        return (int) $this->conn->lastInsertId();
    }

    private function dump(mixed $v): void
    {
        fwrite(STDERR, "\nDUMP ".var_export($v, true)."\n");
    }

    private function multiUrl(): void
    {
        // Multi-URL mode is "more than one access_url row"; CLI resolves the current URL to the first one (id 1).
        $this->conn->executeStatement(
            "INSERT INTO access_url (url, description, active, created_by, lft, lvl, rgt, tree_root, is_login_only)
             VALUES ('http://tc-second.example/', '', 1, 1, 1, 0, 2, NULL, 0)"
        );
    }

    /**
     * Two sessions: A (course 1 + course 2) and B (course 1). coach is the COURSE coach of course 1 in A, gcoach is
     * the GENERAL coach of A. s1 is a student of A/course 1, s2 and s3 students of B/course 1, s4 only registered in A
     * (session_rel_user) without a course subscription.
     *
     * @return array<string, int>
     */
    private function coachScene(): array
    {
        $x = [
            'coach' => $this->user('tc_coach'),
            'gcoach' => $this->user('tc_gcoach'),
            's1' => $this->user('tc_s1'),
            's2' => $this->user('tc_s2'),
            's3' => $this->user('tc_s3'),
            's4' => $this->user('tc_s4'),
        ];
        $x['a'] = $this->session('tc_a', '2001-01-01 00:00:00', '2001-12-31 00:00:00');
        $x['b'] = $this->session('tc_b');
        $this->sessionCourse($x['a'], self::COURSE);
        $this->sessionCourse($x['a'], self::COURSE_TWO);
        $this->sessionCourse($x['b'], self::COURSE);
        $this->sessionUser($x['a'], $x['gcoach'], self::REL_GENERAL_COACH);
        $this->sessionUser($x['a'], $x['s1'], self::REL_STUDENT);
        $this->sessionUser($x['a'], $x['s4'], self::REL_STUDENT);
        $this->sessionUser($x['b'], $x['s2'], self::REL_STUDENT);
        $this->sessionUser($x['b'], $x['s3'], self::REL_STUDENT);
        $this->sessionCourseUser($x['a'], self::COURSE, $x['coach'], self::STATUS_COURSE_COACH);
        $this->sessionCourseUser($x['a'], self::COURSE, $x['s1'], self::STATUS_STUDENT);
        $this->sessionCourseUser($x['b'], self::COURSE, $x['s2'], self::STATUS_STUDENT);
        $this->sessionCourseUser($x['b'], self::COURSE, $x['s3'], self::STATUS_STUDENT);

        return $x;
    }

    private function ids(array $map): array
    {
        $ids = array_keys($map);
        sort($ids);

        return $ids;
    }

    // ------------------------------------------------------------------
    // Coach reporting: who a coach sees
    // ------------------------------------------------------------------

    public function testStudentsFollowedByACourseCoachAreThoseOfHisSessionAndCourseOnly(): void
    {
        $x = $this->coachScene();

        // coach coaches course 1 in session A only: s1 yes; s2/s3 (session B) and s4 (no course subscription) no.
        $this->assertSame([$x['s1']], array_values(Tracking::get_student_followed_by_coach($x['coach'])));
        // The result is keyed by the user id (de-duplicated).
        $this->assertSame([$x['s1'] => $x['s1']], Tracking::get_student_followed_by_coach($x['coach']));
        // Somebody who coaches nothing follows nobody.
        $this->assertSame([], Tracking::get_student_followed_by_coach($x['s2']));
    }

    public function testStudentsFollowedByAGeneralCoachAreTheCourseStudentsOfTheSession(): void
    {
        $x = $this->coachScene();

        // A general coach sees the students subscribed to a course of his session (not session B, not s4).
        $this->assertSame([$x['s1']], array_values(Tracking::get_student_followed_by_coach($x['gcoach'])));

        // The same person coaching a course of session B too: both sets are merged, still without duplicates.
        $this->sessionCourseUser($x['b'], self::COURSE, $x['gcoach'], self::STATUS_COURSE_COACH);
        $this->assertSame(
            $this->ids([$x['s1'] => 1, $x['s2'] => 1, $x['s3'] => 1]),
            $this->ids(Tracking::get_student_followed_by_coach($x['gcoach']))
        );
    }

    public function testStudentsFollowedByACoachAreScopedToTheCurrentUrlWhenMultiUrl(): void
    {
        $x = $this->coachScene();
        $this->multiUrl();

        // Session A is registered on URL 1 (the current one): nothing changes.
        $this->assertSame([$x['s1']], array_values(Tracking::get_student_followed_by_coach($x['coach'])));
        $this->assertSame([$x['s1']], array_values(Tracking::get_student_followed_by_coach($x['gcoach'])));

        // Take session A off URL 1: its students disappear for both kinds of coach.
        $this->conn->executeStatement(
            'DELETE FROM access_url_rel_session WHERE session_id = ? AND access_url_id = ?',
            [$x['a'], api_get_current_access_url_id()]
        );
        $this->assertSame([], Tracking::get_student_followed_by_coach($x['coach']));
        $this->assertSame([], Tracking::get_student_followed_by_coach($x['gcoach']));
    }

    public function testIsAllowedToCoachStudentForAGeneralCoachOnlyCoversHisOwnSessions(): void
    {
        $x = $this->coachScene();

        $this->assertTrue(Tracking::is_allowed_to_coach_student($x['gcoach'], $x['s1']));
        $this->assertFalse(Tracking::is_allowed_to_coach_student($x['gcoach'], $x['s2']));
        $this->assertFalse(Tracking::is_allowed_to_coach_student($x['s1'], $x['s1']));
    }

    public function testIsAllowedToCoachStudentForACourseCoachOnlyCoversTheStudentsOfHisCourseAndSession(): void
    {
        $x = $this->coachScene();

        $this->assertTrue(Tracking::is_allowed_to_coach_student($x['coach'], $x['s1']));
        // Fixed: the course coach branch never looked at the student, so a course coach was "allowed" for every
        // student of the platform (and myStudents.php / lp_tracking.php use this as their access check).
        // s2/s3 are in session B, s4 has no course subscription, and a stranger has nothing in common.
        $this->assertFalse(Tracking::is_allowed_to_coach_student($x['coach'], $x['s2']));
        $this->assertFalse(Tracking::is_allowed_to_coach_student($x['coach'], $x['s4']));
        $this->assertFalse(Tracking::is_allowed_to_coach_student($x['coach'], $x['gcoach']));
        // The same coach also coaching the course in session B now covers s2 there, but still not s4.
        $this->sessionCourseUser($x['b'], self::COURSE, $x['coach'], self::STATUS_COURSE_COACH);
        $this->assertTrue(Tracking::is_allowed_to_coach_student($x['coach'], $x['s2']));
        $this->assertFalse(Tracking::is_allowed_to_coach_student($x['coach'], $x['s4']));
        // Coach and student agree with the list of students the coach follows.
        $this->assertSame(
            $this->ids(Tracking::get_student_followed_by_coach($x['coach'])),
            array_values(array_filter(
                [$x['s1'], $x['s2'], $x['s3'], $x['s4']],
                fn (int $id): bool => Tracking::is_allowed_to_coach_student($x['coach'], $id)
            ))
        );
    }

    public function testCoursesFollowedByACoachListCodesOfCourseCoachAndGeneralCoachRoles(): void
    {
        $x = $this->coachScene();
        $codeOne = api_get_course_info_by_id(self::COURSE)['code'];
        $codeTwo = api_get_course_info_by_id(self::COURSE_TWO)['code'];

        // Course coach: only the course he coaches. Keyed by course code.
        $this->assertSame([$codeOne => $codeOne], Tracking::get_courses_followed_by_coach($x['coach']));
        // General coach: every course of the session.
        $this->assertSame(
            [$codeOne => $codeOne, $codeTwo => $codeTwo],
            Tracking::get_courses_followed_by_coach($x['gcoach'])
        );
        $this->assertSame([], Tracking::get_courses_followed_by_coach($x['s1']));
    }

    public function testCoursesFollowedByACoachCanBeLimitedToOneSession(): void
    {
        $x = $this->coachScene();
        $codeOne = api_get_course_info_by_id(self::COURSE)['code'];
        $codeTwo = api_get_course_info_by_id(self::COURSE_TWO)['code'];

        $this->assertSame([$codeOne => $codeOne], Tracking::get_courses_followed_by_coach($x['coach'], $x['a']));
        // Session B: the course coach does not coach there and the general coach is not its coach.
        $this->assertSame([], Tracking::get_courses_followed_by_coach($x['coach'], $x['b']));
        $this->assertSame([], Tracking::get_courses_followed_by_coach($x['gcoach'], $x['b']));
        $this->assertSame(
            [$codeOne => $codeOne, $codeTwo => $codeTwo],
            Tracking::get_courses_followed_by_coach($x['gcoach'], $x['a'])
        );
    }

    public function testCoursesFollowedByACoachInMultiUrlMode(): void
    {
        $x = $this->coachScene();
        $this->multiUrl();
        $codeOne = api_get_course_info_by_id(self::COURSE)['code'];
        $codeTwo = api_get_course_info_by_id(self::COURSE_TWO)['code'];

        // The general coach path keeps working (both courses are registered on URL 1).
        $this->assertSame(
            [$codeOne => $codeOne, $codeTwo => $codeTwo],
            Tracking::get_courses_followed_by_coach($x['gcoach'])
        );
        $this->assertSame([$codeOne => $codeOne, $codeTwo => $codeTwo], Tracking::get_courses_followed_by_coach($x['gcoach'], $x['a']));
        $this->assertSame([], Tracking::get_courses_followed_by_coach($x['gcoach'], $x['b']));

        // Fixed: the course coach branch compared the course CODE with session_rel_course_rel_user.c_id (an
        // integer), so in multi-URL mode a course coach followed no course at all.
        $this->assertSame([$codeOne => $codeOne], Tracking::get_courses_followed_by_coach($x['coach']));
        $this->assertSame([$codeOne => $codeOne], Tracking::get_courses_followed_by_coach($x['coach'], $x['a']));
        $this->assertSame([], Tracking::get_courses_followed_by_coach($x['coach'], $x['b']));

        // A course that is not registered on the current URL is not listed.
        $this->conn->executeStatement(
            'DELETE FROM access_url_rel_course WHERE c_id = ? AND access_url_id = ?',
            [self::COURSE, api_get_current_access_url_id()]
        );
        $this->assertSame([], Tracking::get_courses_followed_by_coach($x['coach']));
    }

    public function testSessionsCoachedByUserMergesGeneralAndCourseCoachSessionsWithAStatus(): void
    {
        $x = $this->coachScene();
        $future = $this->session('tc_future', '2999-01-01 00:00:00', '2999-12-31 00:00:00');
        $open = $this->session('tc_open');
        $this->sessionUser($future, $x['coach'], self::REL_GENERAL_COACH);
        $this->sessionCourseUser($open, self::COURSE, $x['coach'], self::STATUS_COURSE_COACH);

        $sessions = Tracking::get_sessions_coached_by_user($x['coach']);

        // One entry per session, keyed by id: A and `open` as course coach, `future` as general coach.
        $this->assertSame($this->ids([$x['a'] => 1, $future => 1, $open => 1]), $this->ids($sessions));
        // Status is computed from the dates: ended, not begun yet, and "no start date" counts as active.
        $this->assertSame('Past', $sessions[$x['a']]['status']);
        $this->assertSame('Not yet begun', $sessions[$future]['status']);
        $this->assertSame('active', $sessions[$open]['status']);
        $this->assertSame('2001-01-01 00:00:00', $sessions[$x['a']]['access_start_date']);
        $this->assertSame(3, Tracking::get_sessions_coached_by_user($x['coach'], 0, 0, true));
        $this->assertSame([], Tracking::get_sessions_coached_by_user($x['s1']));
        $this->assertSame(0, Tracking::get_sessions_coached_by_user($x['s1'], 0, 0, true));
    }

    public function testSessionsCoachedByUserKeywordOrderAndLimit(): void
    {
        $coach = $this->user('tc_coach');
        $one = $this->session('tc_zeta', '2001-02-01 00:00:00', '2001-12-31 00:00:00');
        $two = $this->session('tc_alpha', '2001-01-01 00:00:00', '2001-12-31 00:00:00');
        $three = $this->session('tc_mid', '2001-03-01 00:00:00', '2001-12-31 00:00:00');
        foreach ([$one, $two, $three] as $id) {
            $this->sessionUser($id, $coach, self::REL_GENERAL_COACH);
        }

        $byTitle = Tracking::get_sessions_coached_by_user($coach, 0, 0, false, '', '', 'title', 'asc');
        $this->assertSame([$two, $three, $one], array_keys($byTitle));
        $byTitleDesc = Tracking::get_sessions_coached_by_user($coach, 0, 0, false, '', '', 'title', 'desc');
        $this->assertSame([$one, $three, $two], array_keys($byTitleDesc));
        $byStart = Tracking::get_sessions_coached_by_user($coach, 0, 0, false, '', '', 'access_start_date', 'asc');
        $this->assertSame([$two, $one, $three], array_keys($byStart));
        // Only an allowlisted sort column is honoured: anything else leaves the natural order.
        $this->assertCount(3, Tracking::get_sessions_coached_by_user($coach, 0, 0, false, '', '', 'id; DROP', 'asc'));

        // Keyword matches the title only (the description argument adds an OR on description).
        $found = Tracking::get_sessions_coached_by_user($coach, 0, 0, false, 'tc_mid');
        $this->assertSame([$three], array_keys($found));
        $this->assertSame(1, Tracking::get_sessions_coached_by_user($coach, 0, 0, true, 'tc_mid'));

        // PINNED QUIRK: LIMIT is only applied when BOTH start and limit are non-zero, so the first page
        // (start = 0) is never limited.
        $this->assertCount(3, Tracking::get_sessions_coached_by_user($coach, 0, 2));
        $this->assertCount(2, Tracking::get_sessions_coached_by_user($coach, 1, 2));
        // Fixed: the query put LIMIT before ORDER BY (a syntax error), so sorting a page failed with the generic
        // "unknown error". The page is now cut from the sorted list.
        $this->assertSame([$three, $one], array_keys(Tracking::get_sessions_coached_by_user($coach, 1, 2, false, '', '', 'title', 'asc')));
        $this->assertSame([$three, $two], array_keys(Tracking::get_sessions_coached_by_user($coach, 1, 2, false, '', '', 'title', 'desc')));
    }

    public function testSessionsCoachedByUserIgnoresSessionsOfOtherUrls(): void
    {
        $coach = $this->user('tc_coach');
        $mine = $this->session('tc_mine');
        $other = $this->session('tc_other');
        $this->sessionUser($mine, $coach, self::REL_GENERAL_COACH);
        $this->sessionUser($other, $coach, self::REL_GENERAL_COACH);
        $this->conn->executeStatement(
            'DELETE FROM access_url_rel_session WHERE session_id = ?',
            [$other]
        );

        $this->assertSame([$mine], array_keys(Tracking::get_sessions_coached_by_user($coach)));
    }

    public function testCoursesListFromSessionIsKeyedByCode(): void
    {
        $x = $this->coachScene();

        $list = Tracking::get_courses_list_from_session($x['a']);
        $this->assertSame(
            [self::COURSE, self::COURSE_TWO],
            array_values(array_map(static fn (array $r): int => (int) $r['c_id'], $list))
        );
        $this->assertSame(array_keys($list), array_column($list, 'code'));
        $this->assertSame([], Tracking::get_courses_list_from_session(0));
    }

    // ------------------------------------------------------------------
    // Resource-linked fixtures for the group reporting and the count family
    // ------------------------------------------------------------------

    private function group(string $title, int $courseId, ?int $sessionId = null): int
    {
        $this->conn->executeStatement(
            'INSERT INTO c_group_info (title, status, max_student, doc_state, calendar_state, work_state,
                announcements_state, forum_state, wiki_state, chat_state, self_registration_allowed,
                self_unregistration_allowed, document_access, resource_node_id)
             VALUES (?, 1, 0, 1, 1, 1, 1, 1, 1, 1, 0, 0, 0, ?)',
            [$title, $node = $this->node(38, $title)]
        );
        $id = (int) $this->conn->lastInsertId();
        $this->link($node, $courseId, $sessionId);

        return $id;
    }

    private function groupUser(int $groupId, int $userId, int $courseId): void
    {
        $this->conn->executeStatement(
            "INSERT INTO c_group_rel_user (user_id, group_id, c_id, status, role) VALUES (?, ?, ?, 0, '')",
            [$userId, $groupId, $courseId]
        );
    }

    private function forum(int $courseId, ?int $sessionId = null, ?int $groupId = null): int
    {
        $this->conn->executeStatement(
            "INSERT INTO c_forum_forum (title, locked, forum_image, resource_node_id) VALUES ('tc_forum', 0, '', ?)",
            [$node = $this->node(25, 'tc_forum')]
        );
        $id = (int) $this->conn->lastInsertId();
        $this->link($node, $courseId, $sessionId, $groupId);

        return $id;
    }

    private function thread(int $forumId, int $courseId, ?int $sessionId = null, ?int $groupId = null): int
    {
        $this->conn->executeStatement(
            "INSERT INTO c_forum_thread (forum_id, thread_poster_id, title, thread_date, thread_replies, thread_views,
                thread_sticky, locked, thread_qualify_max, thread_weight, thread_peer_qualify, resource_node_id)
             VALUES (?, ?, 'tc_thread', '2001-01-01 00:00:00', 0, 0, 0, 0, 0, 0, 0, ?)",
            [$forumId, $this->creator, $node = $this->node(29, 'tc_thread')]
        );
        $id = (int) $this->conn->lastInsertId();
        $this->link($node, $courseId, $sessionId, $groupId);

        return $id;
    }

    private function post(
        int $threadId,
        int $forumId,
        int $posterId,
        int $courseId,
        ?int $sessionId = null,
        int $visible = 1,
        ?int $groupId = null
    ): int {
        $this->conn->executeStatement(
            "INSERT INTO c_forum_post (thread_id, forum_id, poster_id, title, post_text, post_date, post_notification,
                visible, resource_node_id)
             VALUES (?, ?, ?, 'tc_post', 'x', '2001-01-01 00:00:00', 0, ?, ?)",
            [$threadId, $forumId, $posterId, $visible, $node = $this->node(28, 'tc_post')]
        );
        $id = (int) $this->conn->lastInsertId();
        $this->link($node, $courseId, $sessionId, $groupId);

        return $id;
    }

    /**
     * A student publication; $parentId null makes it an assignment (folder), otherwise it is a submission.
     */
    private function work(int $userId, int $courseId, ?int $sessionId = null, ?int $parentId = null, int $active = 1, bool $linked = true): int
    {
        $this->conn->executeStatement(
            "INSERT INTO c_student_publication (parent_id, user_id, title, active, post_group_id, filetype, has_properties,
                qualification, qualificator_id, weight, allow_text_assignment, contains_file, document_id, resource_node_id)
             VALUES (?, ?, 'tc_work', ?, 0, 'file', 0, 0, 0, 0, 0, 0, 0, ?)",
            [$parentId, $userId, $active, $node = $this->node(6, 'tc_work')]
        );
        $id = (int) $this->conn->lastInsertId();
        if ($linked) {
            $this->link($node, $courseId, $sessionId);
        }

        return $id;
    }

    /**
     * @return array{0: int, 1: int} [document id, resource link id]
     */
    private function document(int $courseId, ?int $sessionId = null, string $title = 'tc_doc'): array
    {
        $this->conn->executeStatement(
            "INSERT INTO c_document (title, filetype, readonly, template, resource_node_id) VALUES (?, 'file', 0, 0, ?)",
            [$title, $node = $this->node(17, $title)]
        );
        $id = (int) $this->conn->lastInsertId();

        return [$id, $this->link($node, $courseId, $sessionId)];
    }

    private function download(int $userId, int $linkId): void
    {
        $this->conn->executeStatement(
            "INSERT INTO track_e_downloads (resource_link_id, down_user_id, down_date, down_doc_path)
             VALUES (?, ?, '2001-01-01 00:00:00', 'x')",
            [$linkId, $userId]
        );
    }

    private function courseAccess(int $userId, int $courseId, string $in, string $out, int $sessionId = 0): void
    {
        $this->conn->executeStatement(
            "INSERT INTO track_e_course_access (user_id, c_id, login_course_date, logout_course_date, counter, session_id, user_ip)
             VALUES (?, ?, ?, ?, 1, ?, '127.0.0.1')",
            [$userId, $courseId, $in, $out, $sessionId]
        );
    }

    /**
     * A learning path linked (published) to the course, optionally to a session.
     *
     * @return array{0: int, 1: int} [lp id, resource node id]
     */
    private function lp(int $courseId, ?int $sessionId = null, int $useMaxScore = 1): array
    {
        $this->conn->executeStatement(
            "INSERT INTO c_lp (lp_type, title, path, force_commit, content_maker, content_license, js_lib, debug, theme,
                author, prerequisite, hide_toc_frame, seriousgame_mode, autolaunch, max_attempts, subscribe_users,
                created_on, modified_on, use_max_score, resource_node_id)
             VALUES (1, 'tc_lp', '', 0, '', '', '', 0, '', '', 0, 0, 0, 0, 0, 0,
                '2001-01-01 00:00:00', '2001-01-01 00:00:00', ?, ?)",
            [$useMaxScore, $node = $this->node(40, 'tc_lp')]
        );
        $id = (int) $this->conn->lastInsertId();
        $this->link($node, $courseId, $sessionId);

        return [$id, $node];
    }

    /** @var array<int, int> lp id => id of its root item */
    private array $lpRoots = [];

    private function lpItem(int $lpId, string $type, float $maxScore, int $order, string $path = ''): int
    {
        // learnpath::get_flat_ordered_items_list() walks the tree down from the root item.
        if (!isset($this->lpRoots[$lpId])) {
            $this->conn->executeStatement(
                "INSERT INTO c_lp_item (lp_id, title, item_type, ref, path, min_score, max_score, display_order, launch_data, lvl)
                 VALUES (?, 'root', 'root', '', 'root', 0, 100, 0, '', 0)",
                [$lpId]
            );
            $this->lpRoots[$lpId] = (int) $this->conn->lastInsertId();
        }
        $this->conn->executeStatement(
            "INSERT INTO c_lp_item (lp_id, title, item_type, ref, path, min_score, max_score, display_order, launch_data, lvl, parent_item_id)
             VALUES (?, ?, ?, '', ?, 0, ?, ?, '', 1, ?)",
            [$lpId, 'tc_item_'.$order, $type, $path, $maxScore, $order, $this->lpRoots[$lpId]]
        );

        return (int) $this->conn->lastInsertId();
    }

    private function lpView(int $userId, int $lpId, int $courseId, ?int $progress, ?int $sessionId = null, int $viewCount = 1): int
    {
        $this->conn->executeStatement(
            'INSERT INTO c_lp_view (user_id, lp_id, c_id, session_id, view_count, last_item, progress)
             VALUES (?, ?, ?, ?, ?, 0, ?)',
            [$userId, $lpId, $courseId, $sessionId, $viewCount, $progress]
        );

        return (int) $this->conn->lastInsertId();
    }

    private function lpItemView(int $itemId, int $viewId, int $totalTime, float $score, ?float $maxScore = 0): int
    {
        $this->conn->executeStatement(
            "INSERT INTO c_lp_item_view (lp_item_id, lp_view_id, view_count, start_time, total_time, score, max_score, status)
             VALUES (?, ?, 1, 1000000, ?, ?, ?, 'completed')",
            [$itemId, $viewId, $totalTime, $score, $maxScore]
        );

        return (int) $this->conn->lastInsertId();
    }

    // ------------------------------------------------------------------
    // Group reporting
    // ------------------------------------------------------------------

    public function testGroupReportingNeedsACourse(): void
    {
        $this->assertNull(Tracking::get_group_reporting(0));
        $this->assertNull(Tracking::get_group_reporting(0, 0, 0, 'count'));
    }

    public function testGroupReportingCountsAndListsTheGroupsOfTheCourseAndSession(): void
    {
        $a = $this->session('tc_a');
        $g1 = $this->group('tc_g1', self::COURSE);
        $g2 = $this->group('tc_g2', self::COURSE, $a);
        $this->group('tc_g3', self::COURSE_TWO);

        $before = Tracking::get_group_reporting(self::COURSE, 0, 0, 'count');
        $this->assertSame(1, $before, 'base course: g1 only (g2 is linked to a session, g3 to another course)');
        $this->assertSame(1, Tracking::get_group_reporting(self::COURSE, $a, 0, 'count'));

        $base = Tracking::get_group_reporting(self::COURSE);
        $this->assertSame([$g1], array_column($base, 'id'));
        $this->assertSame('tc_g1', $base[0]['title']);
        $inSession = Tracking::get_group_reporting(self::COURSE, $a);
        $this->assertSame([$g2], array_column($inSession, 'id'));
        // An empty group reports zeros: "00:00:00" time and works/messages 0.
        $this->assertSame(
            ['id' => $g1, 'title' => 'tc_g1', 'time' => '00:00:00', 'progress' => 0, 'score' => 0, 'works' => 0, 'messages' => 0],
            $base[0]
        );
    }

    public function testGroupReportingSumsTheTimeOfTheMembers(): void
    {
        $s1 = $this->user('tc_s1');
        $s2 = $this->user('tc_s2');
        $outsider = $this->user('tc_out');
        $g = $this->group('tc_g', self::COURSE);
        $this->groupUser($g, $s1, self::COURSE);
        $this->groupUser($g, $s2, self::COURSE);
        $this->courseAccess($s1, self::COURSE, '2001-03-01 10:00:00', '2001-03-01 11:00:00');
        $this->courseAccess($s2, self::COURSE, '2001-03-01 10:00:00', '2001-03-01 10:01:30');
        $this->courseAccess($outsider, self::COURSE, '2001-03-01 10:00:00', '2001-03-01 12:00:00');
        // Another course does not count.
        $this->courseAccess($s1, self::COURSE_TWO, '2001-03-01 10:00:00', '2001-03-01 10:10:00');

        $row = Tracking::get_group_reporting(self::COURSE)[0];

        // 3600 + 90 seconds of the two members of the group.
        $this->assertSame('01:01:30', $row['time']);
    }

    public function testGroupReportingProgressAndScoreAreAveragedOverTheMembers(): void
    {
        $s1 = $this->user('tc_s1');
        $s2 = $this->user('tc_s2');
        $g = $this->group('tc_g', self::COURSE);
        $this->groupUser($g, $s1, self::COURSE);
        $this->groupUser($g, $s2, self::COURSE);
        $course = api_get_course_entity(self::COURSE);
        $existing = count(Container::getLpRepository()->findAllByCourse($course)->getQuery()->getResult());
        [$lp] = $this->lp(self::COURSE);
        $this->lpView($s1, $lp, self::COURSE, 40);
        $this->lpView($s2, $lp, self::COURSE, 20);

        $row = Tracking::get_group_reporting(self::COURSE)[0];

        // Each member's progress is the sum of the progresses of the course LPs they see divided by the NUMBER
        // OF LPs of the course (the LPs they never opened count as 0): with $existing other LPs that is
        // 40 / n and 20 / n, averaged over the 2 members. A float because members exist (an empty group gives
        // the integer 0).
        $n = $existing + 1;
        $this->assertSame(round((round(40 / $n, 1) + round(20 / $n, 1)) / 2, 2), $row['progress']);
        // No scorable item: get_avg_student_score answers '-' (not numeric), so the score stays 0.
        $this->assertSame(0.0, $row['score']);
    }

    public function testGroupReportingWorksAndMessagesCountEveryLinkedResourceOfTheCourse(): void
    {
        $s1 = $this->user('tc_s1');
        $s2 = $this->user('tc_s2');
        $g = $this->group('tc_g', self::COURSE);
        $this->groupUser($g, $s1, self::COURSE);
        $this->groupUser($g, $s2, self::COURSE);
        $forum = $this->forum(self::COURSE);
        $thread = $this->thread($forum, self::COURSE);
        $this->post($thread, $forum, $s1, self::COURSE);
        $this->post($thread, $forum, $s1, self::COURSE);
        $this->post($thread, $forum, $s2, self::COURSE);
        $assignment = $this->work($this->creator, self::COURSE);
        $this->work($s1, self::COURSE, null, $assignment);

        $row = Tracking::get_group_reporting(self::COURSE)[0];

        // PINNED QUIRK: countUserForumPosts()/countUserPublications() go through
        // getResourcesByCourseLinkedToUser(), whose "links.user IS NULL" branch matches every resource linked
        // to the course without a user. So each member is credited with ALL 3 posts: 2 members x 3 = 6
        // (the real figure is 3), and the one submission is credited to both members (2).
        $this->assertSame(6, $row['messages']);
        $this->assertSame(2, $row['works']);
    }

    // ------------------------------------------------------------------
    // Counts of forum content and publications
    // ------------------------------------------------------------------

    public function testForumCountsFollowTheCourseSessionAndGroupLinks(): void
    {
        $a = $this->session('tc_a');
        $s1 = $this->user('tc_s1');
        $g = $this->group('tc_g', self::COURSE);
        $count = fn (?int $session = null, ?int $group = null): array => [
            Tracking::count_number_of_forums_by_course(self::COURSE, $session, $group ?? 0),
            Tracking::count_number_of_threads_by_course(self::COURSE, $session, $group ?? 0),
            Tracking::count_number_of_posts_by_course(self::COURSE, $session, $group ?? 0),
            Tracking::countStudentMessages(self::COURSE, $session, $group),
        ];
        $delta = static fn (array $after, array $before): array => array_map(static fn ($x, $y): int => (int) $x - (int) $y, $after, $before);
        $baseCtx = $count();
        $sessionCtx = $count($a);
        $groupCtx = $count(null, $g);

        $f1 = $this->forum(self::COURSE);
        $t1 = $this->thread($f1, self::COURSE);
        $this->post($t1, $f1, $s1, self::COURSE);
        $this->post($t1, $f1, $s1, self::COURSE, null, 0);
        // A forum of the session, a forum of the group, a forum of another course.
        $f2 = $this->forum(self::COURSE, $a);
        $t2 = $this->thread($f2, self::COURSE, $a);
        $this->post($t2, $f2, $s1, self::COURSE, $a);
        $this->forum(self::COURSE, null, $g);
        $this->forum(self::COURSE_TWO);

        // Base course: forum f1, thread t1 and its two posts. The hidden post (visible = 0) is still counted:
        // only the link's visibility matters. Columns: forums, threads, posts, countStudentMessages.
        $this->assertSame([1, 1, 2, 2], $delta($count(), $baseCtx));
        // Session context: forums and threads also list the base-course content, posts do not (only forums
        // and threads implement "show course resources in session"): forums 2, threads 2, posts only p3.
        $this->assertSame([2, 2, 1, 1], $delta($count($a), $sessionCtx));
        // Group context: only what is linked to the group, no base content.
        $this->assertSame([1, 0, 0, 0], $delta($count(null, $g), $groupCtx));
    }

    public function testStudentPublicationCountsOnlyCountPublishedLinkedItems(): void
    {
        $a = $this->session('tc_a');
        $s1 = $this->user('tc_s1');
        $base = Tracking::countStudentPublications(self::COURSE);
        $baseSession = Tracking::countStudentPublications(self::COURSE, $a);

        $assignment = $this->work($this->creator, self::COURSE);
        $this->work($s1, self::COURSE, null, $assignment);
        $this->work($s1, self::COURSE, $a, $assignment);
        // No resource link: invisible to the course listing.
        $this->work($s1, self::COURSE, null, $assignment, 1, false);

        // The assignment and the base submission count (parent or not): the unlinked one does not.
        $this->assertSame(2, Tracking::countStudentPublications(self::COURSE) - $base);
        // Session context: only the publication linked to the session (publications do not show base content).
        $this->assertSame(1, Tracking::countStudentPublications(self::COURSE, $a) - $baseSession);
    }

    public function testForumPostsForAStudentListCountsVisiblePostsOfThoseUsersEverywhere(): void
    {
        $s1 = $this->user('tc_s1');
        $s2 = $this->user('tc_s2');
        $s3 = $this->user('tc_s3');
        $f = $this->forum(self::COURSE);
        $t = $this->thread($f, self::COURSE);
        $this->post($t, $f, $s1, self::COURSE);
        $this->post($t, $f, $s1, self::COURSE, null, 0);
        $this->post($t, $f, $s2, self::COURSE_TWO);
        $this->post($t, $f, $s3, self::COURSE);

        // Visible posts only, any course, no link needed: s1 1 + s2 1.
        $this->assertSame(2, Tracking::countForumPostsForStudentList([$s1, $s2]));
        $this->assertSame(1, Tracking::countForumPostsForStudentList([$s1]));
        $this->assertSame(3, Tracking::countForumPostsForStudentList([$s1, $s2, $s3]));
        // Ids are cleaned: strings, duplicates and junk are dropped or coerced.
        $this->assertSame(1, Tracking::countForumPostsForStudentList([(string) $s1, $s1, 0, -4]));
        $this->assertSame(0, Tracking::countForumPostsForStudentList([]));
    }

    public function testAssignmentsForAStudentListCountSubmissionsNotAssignments(): void
    {
        $s1 = $this->user('tc_s1');
        $s2 = $this->user('tc_s2');
        $assignment = $this->work($s1, self::COURSE);
        $this->work($s1, self::COURSE, null, $assignment);
        $this->work($s1, self::COURSE, null, $assignment, 0);
        // active = 2 means deleted.
        $this->work($s1, self::COURSE, null, $assignment, 2);
        $this->work($s2, self::COURSE_TWO, null, $assignment);

        // parent_id must be set (the assignment itself is excluded) and active must be 0 or 1.
        $this->assertSame(2, Tracking::countAssignmentsForStudentList([$s1]));
        $this->assertSame(3, Tracking::countAssignmentsForStudentList([$s1, $s2]));
        $this->assertSame(0, Tracking::countAssignmentsForStudentList([]));
    }

    // ------------------------------------------------------------------
    // Documents
    // ------------------------------------------------------------------

    public function testUploadedDocumentsOfAStudentAreCountedForTheWholeCourse(): void
    {
        $s1 = $this->user('tc_s1');
        $s2 = $this->user('tc_s2');
        $code = api_get_course_info_by_id(self::COURSE)['code'];
        $base = (int) Tracking::count_student_uploaded_documents($s1, $code);

        $this->document(self::COURSE);
        $this->document(self::COURSE_TWO);

        // PINNED QUIRK: the count goes through getResourcesByCourseLinkedToUser(), whose "links.user IS NULL"
        // branch matches every document linked to the course with no user. The count ignores who uploaded
        // it: the same 1 for the student who uploaded nothing and for any other user.
        $this->assertSame(1, (int) Tracking::count_student_uploaded_documents($s1, $code) - $base);
        $this->assertSame(
            (int) Tracking::count_student_uploaded_documents($s1, $code),
            (int) Tracking::count_student_uploaded_documents($s2, $code)
        );
    }

    public function testDownloadedDocumentsCountsOnlyThatStudentInThatCourseAndSession(): void
    {
        $s1 = $this->user('tc_s1');
        $s2 = $this->user('tc_s2');
        $a = $this->session('tc_a');
        [, $linkBase] = $this->document(self::COURSE);
        [, $linkSession] = $this->document(self::COURSE, $a);
        [, $linkOther] = $this->document(self::COURSE_TWO);
        $this->download($s1, $linkBase);
        $this->download($s1, $linkBase);
        $this->download($s1, $linkSession);
        $this->download($s1, $linkOther);
        $this->download($s2, $linkBase);

        $this->assertSame(3, Tracking::countStudentDownloadedDocuments($s1, self::COURSE));
        $this->assertSame(1, Tracking::countStudentDownloadedDocuments($s1, self::COURSE, $a));
        $this->assertSame(1, Tracking::countStudentDownloadedDocuments($s2, self::COURSE));
        $this->assertSame(1, Tracking::countStudentDownloadedDocuments($s1, self::COURSE_TWO));
        $this->assertSame(0, Tracking::countStudentDownloadedDocuments($this->user('tc_none'), self::COURSE));
    }

    public function testMostDownloadedDocumentsAreRankedByDownloadCount(): void
    {
        $s1 = $this->user('tc_s1');
        $s2 = $this->user('tc_s2');
        $a = $this->session('tc_a');
        [, $popular] = $this->document(self::COURSE, null, 'tc_popular');
        [, $rare] = $this->document(self::COURSE, null, 'tc_rare');
        [, $inSession] = $this->document(self::COURSE, $a, 'tc_insession');
        $this->download($s1, $popular);
        $this->download($s2, $popular);
        $this->download($s1, $popular);
        $this->download($s1, $rare);
        $this->download($s1, $inSession);
        $code = api_get_course_info_by_id(self::COURSE)['code'];

        $top = Tracking::get_documents_most_downloaded_by_course($code, 0, 10);
        $this->assertSame(['tc_popular', 'tc_rare', 'tc_insession'], array_column($top, 'ntitle'));
        $this->assertSame([3, 1, 1], array_map('intval', array_column($top, 'count_down')));
        $this->assertSame($popular, (int) $top[0]['lid']);
        // Session filter is exact on the link's session.
        $this->assertSame(['tc_insession'], array_column(Tracking::get_documents_most_downloaded_by_course($code, $a, 10), 'ntitle'));
        $this->assertCount(1, Tracking::get_documents_most_downloaded_by_course($code, 0, 1));
        // Fixed: limit 0 (the default, documented as "without limit") was sent as "LIMIT 0, 0" and returned nothing.
        $this->assertSame(
            ['tc_popular', 'tc_rare', 'tc_insession'],
            array_column(Tracking::get_documents_most_downloaded_by_course($code), 'ntitle')
        );
    }

    // ------------------------------------------------------------------
    // Quiz, learning path and tracking fixtures for the averages
    // ------------------------------------------------------------------

    private function quiz(int $courseId, ?int $sessionId = null, string $title = 'tc_quiz'): int
    {
        $this->conn->executeStatement(
            "INSERT INTO c_quiz (title, type, random, random_answers, results_disabled, max_attempt, feedback_type,
                expired_time, propagate_neg, review_answers, random_by_category, display_category_name,
                hide_question_title, page_result_configuration, resource_node_id)
             VALUES (?, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, '{}', ?)",
            [$title, $node = $this->node(19, $title)]
        );
        $id = (int) $this->conn->lastInsertId();
        $this->link($node, $courseId, $sessionId);

        return $id;
    }

    private function exercise(
        int $userId,
        int $courseId,
        ?int $sessionId,
        int $quizId,
        string $date,
        float $score,
        float $maxScore,
        string $status = '',
        int $lpId = 0,
        int $lpItemId = 0,
        int $lpItemViewId = 0,
        int $duration = 0
    ): int {
        $this->conn->executeStatement(
            "INSERT INTO track_e_exercises (exe_user_id, c_id, session_id, exe_exo_id, exe_date, score, max_score, user_ip,
                status, data_tracking, start_date, steps_counter, orig_lp_id, orig_lp_item_id, exe_duration,
                orig_lp_item_view_id, questions_to_check)
             VALUES (?, ?, ?, ?, ?, ?, ?, '127.0.0.1', ?, '', ?, 0, ?, ?, ?, ?, '')",
            [$userId, $courseId, $sessionId, $quizId, $date, $score, $maxScore, $status, $date, $lpId, $lpItemId, $duration, $lpItemViewId]
        );

        return (int) $this->conn->lastInsertId();
    }

    private function courseEntity(int $courseId = self::COURSE): \Chamilo\CoreBundle\Entity\Course
    {
        return api_get_course_entity($courseId);
    }

    private function sessionEntity(int $sessionId): \Chamilo\CoreBundle\Entity\Session
    {
        return api_get_session_entity($sessionId);
    }

    private function lpCount(int $courseId = self::COURSE, ?int $sessionId = null): int
    {
        $session = null === $sessionId ? null : $this->sessionEntity($sessionId);

        return count(Container::getLpRepository()->findAllByCourse($this->courseEntity($courseId), $session)->getQuery()->getResult());
    }

    // ------------------------------------------------------------------
    // Average progress
    // ------------------------------------------------------------------

    public function testAvgStudentProgressGuards(): void
    {
        $s1 = $this->user('tc_s1');
        $course = $this->courseEntity();

        $this->assertFalse(Tracking::get_avg_student_progress(0, $course));
        $this->assertFalse(Tracking::get_avg_student_progress($s1, null));
        // A course without any learning path is "false", not 0 (course 2 has none in the installer fixtures).
        if (0 === $this->lpCount(self::COURSE_TWO)) {
            $this->assertFalse(Tracking::get_avg_student_progress($s1, $this->courseEntity(self::COURSE_TWO)));
        }
        // A session shows the base-course learning paths too: no LP of its own is not "false".
        $a = $this->session('tc_a');
        $this->assertIsFloat(Tracking::get_avg_student_progress($s1, $course, [], $this->sessionEntity($a)));
    }

    public function testAvgStudentProgressAveragesOverTheRequestedLearningPaths(): void
    {
        $s1 = $this->user('tc_s1');
        $course = $this->courseEntity();
        [$lp1] = $this->lp(self::COURSE);
        [$lp2] = $this->lp(self::COURSE);
        [$lp3] = $this->lp(self::COURSE);
        $this->lpView($s1, $lp1, self::COURSE, 40);
        $this->lpView($s1, $lp2, self::COURSE, 20);
        // A view of lp3 stored with another c_id.
        $this->lpView($s1, $lp3, self::COURSE_TWO, 90);

        // Explicit list: the sum of progresses over the listed LPs that have a view, divided by the list size.
        $this->assertSame(30.0, Tracking::get_avg_student_progress($s1, $course, [$lp1, $lp2]));
        // PINNED QUIRK: the c_id condition of the query is commented out, so a view counts whatever its c_id
        // says: only lp_id (and the session) decide. lp3's 90 is added: [40 + 20 + 90, 3 LPs].
        $this->assertSame([150.0, 3.0], array_map('floatval', Tracking::get_avg_student_progress($s1, $course, [$lp1, $lp2, $lp3], null, true)));
        $this->assertSame(90.0, Tracking::get_avg_student_progress($s1, $course, [$lp3]));
        // An LP of the list the course does not own is simply not found in the views: 0.
        $this->assertSame(0.0, Tracking::get_avg_student_progress($s1, $course, [999999999]));
    }

    public function testAvgStudentProgressFollowsTheSessionOfTheView(): void
    {
        $s1 = $this->user('tc_s1');
        $a = $this->session('tc_a');
        $course = $this->courseEntity();
        [$lp] = $this->lp(self::COURSE);
        [$lpSession] = $this->lp(self::COURSE, $a);
        $this->lpView($s1, $lp, self::COURSE, 50);
        $this->lpView($s1, $lpSession, self::COURSE, 80, $a);

        // Base course: session_id NULL or 0 views only.
        $this->assertSame(50.0, Tracking::get_avg_student_progress($s1, $course, [$lp]));
        // In the session only the views of that session count (and only LPs the session lists).
        $this->assertSame(80.0, Tracking::get_avg_student_progress($s1, $course, [$lpSession], $this->sessionEntity($a)));
        $this->assertSame(0.0, Tracking::get_avg_student_progress($s1, $course, [$lp], $this->sessionEntity($a)));
    }

    public function testAvgStudentProgressOfSeveralStudentsMixesTheirViewsPerLearningPath(): void
    {
        $s1 = $this->user('tc_s1');
        $s2 = $this->user('tc_s2');
        $course = $this->courseEntity();
        [$lp] = $this->lp(self::COURSE);
        $this->lpView($s1, $lp, self::COURSE, 40);
        $this->lpView($s2, $lp, self::COURSE, 60);

        // PINNED QUIRK: with a list of students the query is "GROUP BY lp_id", so ONE view per LP survives
        // (the first stored, here s1's 40), not the mean of the group: the result depends on row order.
        $this->assertSame(40.0, Tracking::get_avg_student_progress([$s1, $s2], $course, [$lp]));
    }

    public function testAvgStudentProgressKeepsTheFirstViewOfAnLpNotTheMostViewed(): void
    {
        $s1 = $this->user('tc_s1');
        $course = $this->courseEntity();
        [$lp] = $this->lp(self::COURSE);
        $this->lpView($s1, $lp, self::COURSE, 30, null, 1);
        $this->lpView($s1, $lp, self::COURSE, 90, null, 2);

        // PINNED QUIRK: the code orders by view_count DESC and keeps the first row per LP to get the latest
        // attempt, but GROUP BY lp_id collapses the rows before the ORDER BY: the latest attempt (90) is not
        // the one reported, the oldest (30) is.
        $this->assertSame(30.0, Tracking::get_avg_student_progress($s1, $course, [$lp]));
    }

    public function testAvgStudentProgressWithoutAListUsesTheLearningPathsTheStudentSees(): void
    {
        $s1 = $this->user('tc_s1');
        $course = $this->courseEntity();
        $before = Tracking::get_avg_student_progress($s1, $course, [], null, true);
        [$lp] = $this->lp(self::COURSE);
        $this->lpView($s1, $lp, self::COURSE, 40);

        $after = Tracking::get_avg_student_progress($s1, $course, [], null, true);

        // [sum of progresses, number of LPs]: the new LP adds its 40 and one to the divisor.
        $this->assertSame(0, (int) $before[0]);
        $this->assertSame(40, (int) $after[0]);
        $this->assertSame((int) $before[1] + 1, (int) $after[1]);
    }

    // ------------------------------------------------------------------
    // Average score in learning paths
    // ------------------------------------------------------------------

    public function testAvgStudentScoreGuards(): void
    {
        $s1 = $this->user('tc_s1');
        $course = $this->courseEntity();
        [$lp] = $this->lp(self::COURSE);

        $this->assertNull(Tracking::get_avg_student_score(0, $course));
        // Unknown learning path ids leave nothing to average.
        $this->assertNull(Tracking::get_avg_student_score($s1, $course, [999999999]));
        // PINNED QUIRK: a learning path without any sco/quiz item answers the string '-' (not a number).
        $this->assertSame('-', Tracking::get_avg_student_score($s1, $course, [$lp]));
    }

    public function testAvgStudentScoreOfAScormItemUsesItsMaxScoreOrFallsBack(): void
    {
        $s1 = $this->user('tc_s1');
        $course = $this->courseEntity();
        [$lp] = $this->lp(self::COURSE, null, 1);
        $item = $this->lpItem($lp, 'sco', 10, 1);
        $view = $this->lpView($s1, $lp, self::COURSE, 100);
        $this->lpItemView($item, $view, 60, 5);

        $this->assertSame(50.0, Tracking::get_avg_student_score($s1, $course, [$lp]));
        $this->assertSame([50.0, 1], Tracking::get_avg_student_score($s1, $course, [$lp], null, true));

        // No max score on the item: with use_max_score the platform assumes 100 (SCORM 1.2) ...
        [$lpAssumed] = $this->lp(self::COURSE, null, 1);
        $itemAssumed = $this->lpItem($lpAssumed, 'sco', 0, 1);
        $viewAssumed = $this->lpView($s1, $lpAssumed, self::COURSE, 100);
        $this->lpItemView($itemAssumed, $viewAssumed, 60, 50, 20);
        $this->assertSame(50.0, Tracking::get_avg_student_score($s1, $course, [$lpAssumed]));
        // ... without it, the max score stored in the item view is used (50 / 20 = 250%).
        [$lpOwn] = $this->lp(self::COURSE, null, 0);
        $itemOwn = $this->lpItem($lpOwn, 'sco', 0, 1);
        $viewOwn = $this->lpView($s1, $lpOwn, self::COURSE, 100);
        $this->lpItemView($itemOwn, $viewOwn, 60, 50, 20);
        $this->assertSame(250.0, Tracking::get_avg_student_score($s1, $course, [$lpOwn]));
    }

    public function testAvgStudentScoreOfAQuizItemUsesTheLastAttemptAndItsQuestionsMaximum(): void
    {
        $s1 = $this->user('tc_s1');
        $course = $this->courseEntity();
        $quiz = $this->quiz(self::COURSE);
        [$lp] = $this->lp(self::COURSE);
        $item = $this->lpItem($lp, 'quiz', 20, 1, (string) $quiz);
        $view = $this->lpView($s1, $lp, self::COURSE, 100);
        $itemView = $this->lpItemView($item, $view, 60, 5, 0);
        // Two attempts in the LP: the latest by date (8) is the one that counts, not the best (9).
        $this->exercise($s1, self::COURSE, null, $quiz, '2001-02-01 10:00:00', 9, 20, '', $lp, $item, $itemView);
        $last = $this->exercise($s1, self::COURSE, null, $quiz, '2001-02-02 10:00:00', 8, 20, '', $lp, $item, $itemView);
        // An attempt flagged "incomplete" and one outside the LP are ignored.
        $this->exercise($s1, self::COURSE, null, $quiz, '2001-02-03 10:00:00', 20, 20, 'incomplete', $lp, $item, $itemView);
        $this->exercise($s1, self::COURSE, null, $quiz, '2001-02-04 10:00:00', 20, 20, '', 0, 0, 0);

        // Without recorded questions the max score is the one of the LP item (20): 5 (item view score) / 20.
        $this->assertSame(25.0, Tracking::get_avg_student_score($s1, $course, [$lp]));

        // With the questions of the last attempt (ponderation 5 + 5 = 10) the maximum comes from them.
        foreach ([1, 2] as $n) {
            $this->conn->executeStatement(
                "INSERT INTO c_quiz_question (question, position, type, level, mandatory, ponderation) VALUES ('tc_q', ?, 1, 1, 0, 5)",
                [$n]
            );
            $questionId = (int) $this->conn->lastInsertId();
            $this->conn->executeStatement(
                "INSERT INTO track_e_attempt (exe_id, user_id, question_id, answer, teacher_comment, marks, tms, seconds_spent)
                 VALUES (?, ?, ?, '', '', 4, '2001-02-02 10:00:00', 0)",
                [$last, $s1, $questionId]
            );
        }
        $this->assertSame(50.0, Tracking::get_avg_student_score($s1, $course, [$lp]));
    }

    public function testAvgStudentScoreLatestAndBestAttemptModes(): void
    {
        $s1 = $this->user('tc_s1');
        $course = $this->courseEntity();
        [$lp] = $this->lp(self::COURSE, null, 1);
        $item = $this->lpItem($lp, 'sco', 10, 1);
        $view = $this->lpView($s1, $lp, self::COURSE, 100);
        // Two tries of the same item inside the one LP view: view_count 1 scored 9, view_count 2 scored 4.
        $this->conn->executeStatement(
            "INSERT INTO c_lp_item_view (lp_item_id, lp_view_id, view_count, start_time, total_time, score, max_score, status)
             VALUES (?, ?, 1, 1, 10, 9, 0, 'completed'), (?, ?, 2, 1, 10, 4, 0, 'completed')",
            [$item, $view, $item, $view]
        );

        // Default: every try is a row of its own, and each also counts as an item: (0.9 + 0.4) / 2 = 65%.
        $this->assertSame(65.0, Tracking::get_avg_student_score($s1, $course, [$lp]));
        // Latest only: the try with the highest view_count (4 of 10).
        $this->assertSame(40.0, Tracking::get_avg_student_score($s1, $course, [$lp], null, false, true));
        // Best only: the try with the highest score (9 of 10).
        $this->assertSame(90.0, Tracking::get_avg_student_score($s1, $course, [$lp], null, false, false, true));
    }

    public function testAvgStudentScoreOfSeveralStudentsIsNotDividedByTheirNumber(): void
    {
        $s1 = $this->user('tc_s1');
        $s2 = $this->user('tc_s2');
        $course = $this->courseEntity();
        [$lp] = $this->lp(self::COURSE, null, 1);
        $item = $this->lpItem($lp, 'sco', 10, 1);
        foreach ([[$s1, 5], [$s2, 10]] as [$user, $score]) {
            $view = $this->lpView($user, $lp, self::COURSE, 100);
            $this->lpItemView($item, $view, 60, $score);
        }

        // 50% and 100%: each (user, LP) pair is added to the total, which is divided by the number of LPs with a
        // score (1), never by the number of students: PINNED QUIRK, the "average" of two students is 150%.
        $this->assertSame(150.0, Tracking::get_avg_student_score([$s1, $s2], $course, [$lp]));
        $this->assertSame([150.0, 1], Tracking::get_avg_student_score([$s1, $s2], $course, [$lp], null, true));
        $this->assertSame(100.0, Tracking::get_avg_student_score($s2, $course, [$lp]));
    }

    public function testAvgStudentScoreFollowsTheSessionOfTheView(): void
    {
        $s1 = $this->user('tc_s1');
        $a = $this->session('tc_a');
        $course = $this->courseEntity();
        [$lp] = $this->lp(self::COURSE, null, 1);
        $item = $this->lpItem($lp, 'sco', 10, 1);
        $view = $this->lpView($s1, $lp, self::COURSE, 100, $a);
        $this->lpItemView($item, $view, 60, 8);

        // The only view belongs to session A: invisible from the base course, visible in the session.
        $this->assertSame(0.0, Tracking::get_avg_student_score($s1, $course, [$lp]));
        $this->assertSame(80.0, Tracking::get_avg_student_score($s1, $course, [$lp], $this->sessionEntity($a)));
    }

    // ------------------------------------------------------------------
    // Average exercise score
    // ------------------------------------------------------------------

    public function testAvgStudentExerciseScoreForAnUnknownCourseOrStudentIsNull(): void
    {
        $s1 = $this->user('tc_s1');

        $this->assertNull(Tracking::get_avg_student_exercise_score($s1, 'TC_NO_SUCH_COURSE'));
        $this->assertNull(Tracking::get_avg_student_exercise_score(0, api_get_course_info_by_id(self::COURSE)['code']));
    }

    public function testAvgStudentExerciseScoreOfOneQuiz(): void
    {
        $s1 = $this->user('tc_s1');
        $quiz = $this->quiz(self::COURSE);
        $code = api_get_course_info_by_id(self::COURSE)['code'];
        $this->exercise($s1, self::COURSE, null, $quiz, '2001-02-01 10:00:00', 5, 10);
        $this->exercise($s1, self::COURSE, null, $quiz, '2001-02-02 10:00:00', 10, 10);
        // Incomplete attempts and attempts made inside a learning path are not part of the plain average.
        $this->exercise($s1, self::COURSE, null, $quiz, '2001-02-03 10:00:00', 0, 10, 'incomplete');
        $this->exercise($s1, self::COURSE, null, $quiz, '2001-02-04 10:00:00', 0, 10, '', 3, 4);

        // (50% + 100%) / 2 attempts.
        $this->assertSame(75.0, Tracking::get_avg_student_exercise_score($s1, $code, $quiz));
    }

    public function testAvgStudentExerciseScoreWithoutAQuizIdListsTheQuizzesOfTheCourse(): void
    {
        $s1 = $this->user('tc_s1');
        $quiz = $this->quiz(self::COURSE);
        $code = api_get_course_info_by_id(self::COURSE)['code'];
        $this->exercise($s1, self::COURSE, null, $quiz, '2001-02-01 10:00:00', 5, 10);

        // PINNED QUIRK: with no quiz id the quiz list is read through c_quiz.c_id and c_quiz.active, columns that
        // no longer exist (quizzes reach their course through resource_link): the query fails, which the
        // legacy database layer turns into the generic "unknown error".
        $this->expectException(NotAllowedException::class);
        Tracking::get_avg_student_exercise_score($s1, $code);
    }

    // ------------------------------------------------------------------
    // Time in learning paths
    // ------------------------------------------------------------------

    public function testTimeSpentInLpSumsTheItemViewsOfTheStudentOnly(): void
    {
        $s1 = $this->user('tc_s1');
        $s2 = $this->user('tc_s2');
        $course = $this->courseEntity();
        [$lp] = $this->lp(self::COURSE);
        $item1 = $this->lpItem($lp, 'document', 0, 1);
        $item2 = $this->lpItem($lp, 'document', 0, 2);
        $view = $this->lpView($s1, $lp, self::COURSE, 10);
        $this->lpItemView($item1, $view, 100, 0);
        $this->lpItemView($item2, $view, 50, 0);
        $other = $this->lpView($s2, $lp, self::COURSE, 10);
        $this->lpItemView($item1, $other, 7, 0);

        $this->assertSame(150, Tracking::get_time_spent_in_lp($s1, $course, [$lp]));
        $this->assertSame(7, Tracking::get_time_spent_in_lp($s2, $course, [$lp]));
        // Accepts a list of arrays with an iid (or id) key too.
        $this->assertSame(150, Tracking::get_time_spent_in_lp($s1, $course, [['iid' => $lp]]));
        $this->assertSame(0, Tracking::get_time_spent_in_lp($this->user('tc_none'), $course, [$lp]));
    }

    public function testTimeSpentInLpReplacesAQuizItemTimeWithTheExerciseDuration(): void
    {
        $s1 = $this->user('tc_s1');
        $course = $this->courseEntity();
        $quiz = $this->quiz(self::COURSE);
        [$lp] = $this->lp(self::COURSE);
        $item = $this->lpItem($lp, 'quiz', 10, 1, (string) $quiz);
        $view = $this->lpView($s1, $lp, self::COURSE, 100);
        $itemView = $this->lpItemView($item, $view, 100, 5);
        // Two completed attempts (durations 200 + 50) and one incomplete (900, ignored), all inside the LP item.
        $this->exercise($s1, self::COURSE, null, $quiz, '2001-02-01 10:00:00', 5, 10, '', $lp, $item, $itemView, 200);
        $this->exercise($s1, self::COURSE, null, $quiz, '2001-02-02 10:00:00', 5, 10, '', $lp, $item, $itemView, 50);
        $this->exercise($s1, self::COURSE, null, $quiz, '2001-02-03 10:00:00', 5, 10, 'incomplete', $lp, $item, $itemView, 900);

        $total = Tracking::get_time_spent_in_lp($s1, $course, [$lp]);

        // The item view total_time (100) was overwritten with the SUM of the exercise durations (250), and that
        // is what the sum reports. A read method that writes: the stored value changes.
        $this->assertSame(250, $total);
        $this->assertSame(250, (int) $this->conn->fetchOne('SELECT total_time FROM c_lp_item_view WHERE iid = ?', [$itemView]));
    }

    public function testTimeSpentInLpLeavesTheItemTimeAloneWhenThereIsNoExerciseDuration(): void
    {
        $s1 = $this->user('tc_s1');
        $course = $this->courseEntity();
        $quiz = $this->quiz(self::COURSE);
        [$lp] = $this->lp(self::COURSE);
        $item = $this->lpItem($lp, 'quiz', 10, 1, (string) $quiz);
        $view = $this->lpView($s1, $lp, self::COURSE, 100);
        $itemView = $this->lpItemView($item, $view, 100, 5);
        $this->exercise($s1, self::COURSE, null, $quiz, '2001-02-01 10:00:00', 5, 10, '', $lp, $item, $itemView, 0);

        $this->assertSame(100, Tracking::get_time_spent_in_lp($s1, $course, [$lp]));
        $this->assertSame(100, (int) $this->conn->fetchOne('SELECT total_time FROM c_lp_item_view WHERE iid = ?', [$itemView]));
    }

    public function testTimeSpentInLpFiltersTheViewsBySession(): void
    {
        $s1 = $this->user('tc_s1');
        $a = $this->session('tc_a');
        $course = $this->courseEntity();
        [$lp] = $this->lp(self::COURSE);
        $item = $this->lpItem($lp, 'document', 0, 1);
        $base = $this->lpView($s1, $lp, self::COURSE, 10);
        $this->lpItemView($item, $base, 100, 0);
        $inSession = $this->lpView($s1, $lp, self::COURSE, 10, $a);
        $this->lpItemView($item, $inSession, 30, 0);

        $this->assertSame(100, Tracking::get_time_spent_in_lp($s1, $course, [$lp]));
        $this->assertSame(30, Tracking::get_time_spent_in_lp($s1, $course, [$lp], $a));
    }

    // ------------------------------------------------------------------
    // Platform-setting branches: lp.lp_minimum_time and multi-URL
    // ------------------------------------------------------------------

    private function enableMinimumTime(int $courseId): void
    {
        $this->setMinimumTimeSetting('true');
        $fieldId = (int) $this->conn->fetchOne("SELECT id FROM extra_field WHERE item_type = 2 AND variable = 'new_tracking_system'");
        $this->assertGreaterThan(0, $fieldId, 'the installer creates the course extra field new_tracking_system');
        $this->conn->executeStatement(
            "INSERT INTO extra_field_values (field_id, item_id, field_value, created_at, updated_at)
             VALUES (?, ?, '1', '2001-01-01 00:00:00', '2001-01-01 00:00:00')",
            [$fieldId, $courseId]
        );
    }

    private function setTimezone(string $timezone): void
    {
        Container::getSettingsManager()->updateSetting('platform.timezone', $timezone);
        // api_get_timezone() memoizes its answer under this key.
        \ChamiloSession::erase('system_timezone');
    }

    private function setMinimumTimeSetting(string $value): void
    {
        Container::getSettingsManager()->updateSetting('lp.lp_minimum_time', $value);
    }

    private function completeAccess(int $userId, int $courseId, string $date, int $currentId, string $tool = 'course-main', int $sessionId = 0): void
    {
        $this->conn->executeStatement(
            "INSERT INTO track_e_access_complete (user_id, date_reg, tool, tool_id, tool_id_detail, action, action_details,
                current_id, ip_user, user_agent, session_id, c_id, ch_sid, login_as, info, url)
             VALUES (?, ?, ?, 0, 0, 'view', '', ?, '', '', ?, ?, '', 0, '', '')",
            [$userId, $date, $tool, $currentId, $sessionId, $courseId]
        );
    }

    public function testMinimumTimeIsOffUnlessTheSettingAndTheExtraFieldAreBothSet(): void
    {
        // Setting off (the default): never available, whatever the extra field says.
        $this->setMinimumTimeSetting('false');
        $this->assertFalse(Tracking::minimumTimeAvailable(0, self::COURSE));

        // Setting on but no extra field value on the course.
        $this->setMinimumTimeSetting('true');
        $this->assertFalse(Tracking::minimumTimeAvailable(0, self::COURSE));
        $this->assertFalse(Tracking::minimumTimeAvailable(0, 0));

        $this->enableMinimumTime(self::COURSE);
        $this->assertTrue(Tracking::minimumTimeAvailable(0, self::COURSE));
        $this->assertFalse(Tracking::minimumTimeAvailable(0, self::COURSE_TWO));
        // With a session the SESSION extra field decides, not the course's: none set on this session.
        $a = $this->session('tc_a');
        $this->assertFalse(Tracking::minimumTimeAvailable($a, self::COURSE));

        $this->setMinimumTimeSetting('false');
        $this->assertFalse(Tracking::minimumTimeAvailable(0, self::COURSE));
    }

    public function testCourseTimeComesFromTheAccessCompleteLogWhenMinimumTimeIsOn(): void
    {
        $s1 = $this->user('tc_s1');
        $this->courseAccess($s1, self::COURSE, '2001-03-01 10:00:00', '2001-03-01 11:00:00');
        // Classic tracking: the 3600 seconds of the course access rows.
        $this->assertSame(3600, Tracking::get_time_spent_on_the_course($s1, self::COURSE, 0));

        $this->enableMinimumTime(self::COURSE);
        // New tracking system with no track_e_access_complete rows for the user: no time at all (the classic rows
        // are not consulted any more).
        $this->assertSame(0, Tracking::get_time_spent_on_the_course($s1, self::COURSE, 0));

        // Two page views of the same login 100 seconds apart: the span between them is the time.
        $this->completeAccess($s1, self::COURSE, '2001-03-01 10:00:00', 7);
        $this->completeAccess($s1, self::COURSE, '2001-03-01 10:01:40', 7);
        $this->assertSame(100, Tracking::get_time_spent_on_the_course($s1, self::COURSE, 0));
        // Another course is still classic.
        $this->assertSame(0, Tracking::get_time_spent_on_the_course($s1, self::COURSE_TWO, 0));
    }

    public function testLastConnectionDateOnTheCourseStopsAtTheSessionEndWhenMinimumTimeIsOn(): void
    {
        $s1 = $this->user('tc_s1');
        $a = $this->session('tc_a', '2001-01-01 00:00:00', '2001-03-01 00:00:00');
        $info = api_get_course_info_by_id(self::COURSE);
        $this->courseAccess($s1, self::COURSE, '2001-02-01 10:00:00', '2001-02-01 11:00:00', $a);
        $this->courseAccess($s1, self::COURSE, '2001-04-01 10:00:00', '2001-04-01 11:00:00', $a);

        // Classic: the latest logout, unformatted when asked.
        $this->assertSame('2001-04-01 11:00:00', Tracking::get_last_connection_date_on_the_course($s1, $info, $a, false));

        // New tracking, session variant: the SESSION extra field is what enables it.
        $this->setMinimumTimeSetting('true');
        $fieldId = (int) $this->conn->fetchOne("SELECT id FROM extra_field WHERE item_type = 3 AND variable = 'new_tracking_system'");
        if (0 === $fieldId) {
            $this->conn->executeStatement(
                "INSERT INTO extra_field (item_type, value_type, variable, display_text, visible_to_self, visible_to_others,
                    changeable, filter, created_at, field_order)
                 VALUES (3, 13, 'new_tracking_system', 'tc', 0, 0, 0, 0, '2001-01-01 00:00:00', 0)"
            );
            $fieldId = (int) $this->conn->lastInsertId();
        }
        $this->conn->executeStatement(
            "INSERT INTO extra_field_values (field_id, item_id, field_value, created_at, updated_at)
             VALUES (?, ?, '1', '2001-01-01 00:00:00', '2001-01-01 00:00:00')",
            [$fieldId, $a]
        );
        $this->assertTrue(Tracking::minimumTimeAvailable($a, self::COURSE));
        // A student only sees connections that ended before the session closed: the April visit is dropped.
        $this->assertSame('2001-02-01 11:00:00', Tracking::get_last_connection_date_on_the_course($s1, $info, $a, false));
    }

    public function testMultiUrlRestrictsPlatformTimeToTheUsersOfTheCurrentUrl(): void
    {
        $inUrl = $this->user('tc_in');
        $outUrl = $this->user('tc_out');
        foreach ([$inUrl, $outUrl] as $u) {
            $this->conn->executeStatement(
                "INSERT INTO track_e_login (login_user_id, login_date, user_ip, logout_date) VALUES (?, '2001-03-01 10:00:00', '1', '2001-03-01 10:01:00')",
                [$u]
            );
        }
        $from = '2001-03-01 00:00:00';
        $to = '2001-03-31 23:59:59';
        $before = Tracking::getTotalTimeSpentOnThePlatform($from, $to);

        $this->multiUrl();
        // In multi-URL mode only users registered on the current URL (access_url_rel_user) count: neither of
        // these two is, so their 120 seconds disappear from the total.
        // (the multi-URL branch returns the SUM as a numeric string, hence assertEquals)
        $this->assertEquals($before - 120, Tracking::getTotalTimeSpentOnThePlatform($from, $to));
        $this->conn->executeStatement(
            'INSERT INTO access_url_rel_user (user_id, access_url_id) VALUES (?, ?)',
            [$inUrl, api_get_current_access_url_id()]
        );
        $this->assertEquals($before - 60, Tracking::getTotalTimeSpentOnThePlatform($from, $to));
    }

    // ------------------------------------------------------------------
    // Dates rendered with the locale machinery
    // ------------------------------------------------------------------

    public function testFirstAndLastConnectionDatesAreRenderedInThePlatformTimezone(): void
    {
        $u = $this->user('tc_u');
        $this->setTimezone('Asia/Tokyo');
        foreach (['2001-03-01 20:00:00', '2001-03-05 20:00:00'] as $date) {
            $this->conn->executeStatement(
                "INSERT INTO track_e_login (login_user_id, login_date, user_ip, logout_date) VALUES (?, ?, '1', NULL)",
                [$u, $date]
            );
        }

        // Why the first test file saw '' / false: these helpers read the language and the timezone through the
        // legacy session bridge, which only exists once Container::setSession() was called (setUp does).
        // 20:00 UTC is already the next day in Tokyo (UTC+9): both dates are rendered in the platform timezone.
        $this->assertSame('March 2, 2001', Tracking::get_first_connection_date($u));
        $this->assertSame('March 6, 2001', Tracking::get_last_connection_date($u));
        // The timestamp form returns the LOCAL wall clock read as UTC: 05 Mar 20:00 UTC + 9h.
        $this->assertSame(gmmktime(5, 0, 0, 3, 6, 2001), Tracking::get_last_connection_date($u, false, true));
        // More than 7 days ago: the warning variant wraps the date in a red <em>.
        $this->assertSame('<em style="color: #F00;">March 6, 2001</em>', Tracking::get_last_connection_date($u, true));
    }

    public function testChatLastConnectionFormatsTheLatestChatAccessOfTheCourseAndSession(): void
    {
        $u = $this->user('tc_u');
        $a = $this->session('tc_a');
        $this->setTimezone('UTC');
        foreach ([
            ['2001-03-01 10:00:00', 'chat', self::COURSE, 0],
            ['2001-03-03 15:30:00', 'chat', self::COURSE, 0],
            ['2001-03-09 09:00:00', 'chat', self::COURSE, $a],
            ['2001-03-08 09:00:00', 'chat', self::COURSE_TWO, 0],
            ['2001-03-07 09:00:00', 'forum', self::COURSE, 0],
        ] as [$date, $tool, $course, $session]) {
            $this->conn->executeStatement(
                'INSERT INTO track_e_lastaccess (access_user_id, access_date, c_id, access_tool, session_id) VALUES (?, ?, ?, ?, ?)',
                [$u, $date, $course, $tool, $session]
            );
        }

        // Newest chat row of that course and session only, rendered long with the day name and time.
        $this->assertSame("Saturday, March 3, 2001 at 3:30\u{202F}PM", Tracking::chat_last_connection($u, self::COURSE));
        $this->assertSame("Friday, March 9, 2001 at 9:00\u{202F}AM", Tracking::chat_last_connection($u, self::COURSE, $a));
        // No chat access (other user, other course): empty string, not false.
        $this->assertSame('', Tracking::chat_last_connection($this->user('tc_none'), self::COURSE));
        $this->assertSame('', Tracking::chat_last_connection($u, 99999999));
    }

    // ------------------------------------------------------------------
    // getStats
    // ------------------------------------------------------------------

    public function testGetStatsOfAUserWhoFollowsNobody(): void
    {
        $u = $this->user('tc_u');

        $stats = Tracking::getStats($u);

        $this->assertSame(
            ['drh', 'teachers', 'student_list', 'student_bosses', 'courses', 'sessions', 'assigned_courses'],
            array_keys($stats)
        );
        foreach ($stats as $list) {
            $this->assertSame([], $list);
        }
    }

    public function testGetStatsCountVariantReturnsCountsAndTheStudentList(): void
    {
        $u = $this->user('tc_u');

        $stats = Tracking::getStats($u, true);

        $this->assertSame(
            ['drh', 'teachers', 'student_count', 'student_list', 'student_bosses', 'courses', 'session_count', 'session_list', 'assigned_courses'],
            array_keys($stats)
        );
        $this->assertSame(0, $stats['student_count']);
        $this->assertSame([], $stats['student_list']);
        $this->assertSame(0, $stats['session_count']);
        foreach (['drh', 'teachers', 'student_bosses', 'courses', 'assigned_courses'] as $key) {
            $this->assertEquals(0, $stats[$key], $key);
        }
    }

    // ------------------------------------------------------------------
    // Moving a user's tracking data from a session to another
    // ------------------------------------------------------------------

    /**
     * Seeds the tracking of $user in course 1 inside session $from.
     *
     * @return array<string, int>
     */
    private function trackingInSession(int $user, int $from, bool $withLpView = false): array
    {
        $quiz = $this->quiz(self::COURSE);
        $ids = [];
        $ids['exercise'] = $this->exercise($user, self::COURSE, $from, $quiz, '2001-02-01 10:00:00', 5, 10);
        $ids['exerciseOther'] = $this->exercise($user, self::COURSE_TWO, $from, $quiz, '2001-02-01 10:00:00', 5, 10);
        $this->conn->executeStatement(
            "INSERT INTO track_e_attempt_qualify (exe_id, question_id, marks, insert_date, author, teacher_comment, session_id, answer, final)
             VALUES (?, 1, 1, '2001-02-01 10:00:00', 1, '', ?, '', 0)",
            [$ids['exercise'], $from]
        );
        $ids['qualify'] = (int) $this->conn->lastInsertId();
        $this->courseAccess($user, self::COURSE, '2001-03-01 10:00:00', '2001-03-01 11:00:00', $from);
        $ids['access'] = (int) $this->conn->lastInsertId();
        $this->conn->executeStatement(
            "INSERT INTO track_e_lastaccess (access_user_id, access_date, c_id, access_tool, session_id) VALUES (?, '2001-03-01 10:00:00', ?, 'learnpath', ?)",
            [$user, self::COURSE, $from]
        );
        $ids['last'] = (int) $this->conn->lastInsertId();
        if ($withLpView) {
            [$lp] = $this->lp(self::COURSE);
            $ids['lpView'] = $this->lpView($user, $lp, self::COURSE, 40, $from);
        }

        return $ids;
    }

    private function moveData(int $user, int $from, int $to, bool $update): string
    {
        ob_start();
        try {
            Tracking::processUserDataMove($user, api_get_course_info_by_id(self::COURSE), $from, $to, $update);
        } finally {
            $out = (string) ob_get_clean();
        }

        return $out;
    }

    public function testProcessUserDataMoveUpdatesTheTrackingRowsOfThatUserAndCourse(): void
    {
        $s1 = $this->user('tc_s1');
        $s2 = $this->user('tc_s2');
        $from = $this->session('tc_from');
        $to = $this->session('tc_to');
        $ids = $this->trackingInSession($s1, $from);
        $other = $this->trackingInSession($s2, $from);

        $out = $this->moveData($s1, $from, $to, true);

        $session = fn (string $table, string $pk, int $id): int => (int) $this->conn->fetchOne("SELECT session_id FROM $table WHERE $pk = ?", [$id]);
        $this->assertSame($to, $session('track_e_exercises', 'exe_id', $ids['exercise']));
        $this->assertSame($to, $session('track_e_attempt_qualify', 'id', $ids['qualify']));
        $this->assertSame($to, $session('track_e_course_access', 'course_access_id', $ids['access']));
        $this->assertSame($to, $session('track_e_lastaccess', 'access_id', $ids['last']));
        // Another course of the same user and another user stay in the origin session.
        $this->assertSame($from, $session('track_e_exercises', 'exe_id', $ids['exerciseOther']));
        $this->assertSame($from, $session('track_e_exercises', 'exe_id', $other['exercise']));
        $this->assertSame($from, $session('track_e_course_access', 'course_access_id', $other['access']));
        $this->assertSame($from, $session('track_e_lastaccess', 'access_id', $other['last']));
        // The report printed for the admin names each updated table with its row count.
        $this->assertStringContainsString('Stats moved.', $out);
        $this->assertStringContainsString('track_e_exercises - 1 records updated', $out);
        $this->assertStringContainsString('track_e_course_access - 1 records updated', $out);
        $this->assertStringContainsString('track_e_lastaccess - 1 records updated', $out);
    }

    public function testProcessUserDataMovePreviewChangesNothing(): void
    {
        $s1 = $this->user('tc_s1');
        $from = $this->session('tc_from');
        $to = $this->session('tc_to');
        $ids = $this->trackingInSession($s1, $from);

        $out = $this->moveData($s1, $from, $to, false);

        $this->assertSame($from, (int) $this->conn->fetchOne('SELECT session_id FROM track_e_exercises WHERE exe_id = ?', [$ids['exercise']]));
        $this->assertSame($from, (int) $this->conn->fetchOne('SELECT session_id FROM track_e_course_access WHERE course_access_id = ?', [$ids['access']]));
        $this->assertStringContainsString('Original session #'.$from, $out);
        $this->assertStringContainsString('Destination session #'.$to, $out);
        $this->assertStringNotContainsString('Stats moved.', $out);
    }

    public function testProcessUserDataMoveRelinksTheStudentPublicationsOfTheUser(): void
    {
        $s1 = $this->user('tc_s1');
        $from = $this->session('tc_from');
        $to = $this->session('tc_to');
        $assignment = $this->work($this->creator, self::COURSE);
        $work = $this->work($s1, self::COURSE, $from, $assignment);
        $untouched = $this->work($s1, self::COURSE, null, $assignment);
        $linkOf = fn (int $workId): array => $this->conn->fetchAssociative(
            'SELECT rl.id, rl.session_id FROM resource_link rl INNER JOIN c_student_publication w ON w.resource_node_id = rl.resource_node_id WHERE w.iid = ?',
            [$workId]
        );
        $untouchedBefore = $linkOf($untouched);

        $this->moveData($s1, $from, $to, true);

        $this->assertSame($to, (int) $linkOf($work)['session_id']);
        // The base-course submission keeps its NULL session.
        $this->assertSame($untouchedBefore, $linkOf($untouched));
    }

    public function testProcessUserDataMoveToTheBaseCourseFailsOnTheSessionForeignKey(): void
    {
        $s1 = $this->user('tc_s1');
        $from = $this->session('tc_from');
        $this->trackingInSession($s1, $from);

        // PINNED QUIRK: "no session" is written as session_id = 0 in the tracking tables, but session_id is a
        // foreign key to session.id, so moving a user's data back to the base course fails with the generic
        // "unknown error" (and, outside a test, half-way through the moves).
        $this->expectException(NotAllowedException::class);
        $this->moveData($s1, $from, 0, true);
    }

    public function testProcessUserDataMoveCannotReadTheLearningPathViews(): void
    {
        $s1 = $this->user('tc_s1');
        $from = $this->session('tc_from');
        $to = $this->session('tc_to');
        $ids = $this->trackingInSession($s1, $from, true);

        // PINNED QUIRK: the loop keys the c_lp_view rows by $row['id'], a column that does not exist (it is
        // 'iid'): PHP raises "Undefined array key" as soon as the user has a learning path view in the
        // origin session, so none of the views can be moved (the exercises were already updated above it).
        try {
            $this->moveData($s1, $from, $to, true);
            $this->fail('expected the undefined array key warning');
        } catch (\Throwable $e) {
            $this->assertStringContainsString('Undefined array key "id"', $e->getMessage());
        }
        $this->assertSame($from, (int) $this->conn->fetchOne('SELECT session_id FROM c_lp_view WHERE iid = ?', [$ids['lpView']]));
    }

    // ------------------------------------------------------------------
    // Other small queries
    // ------------------------------------------------------------------

    public function testIpFromUserEventIsTheLastLoginIpBeforeTheEvent(): void
    {
        $u = $this->user('tc_u');
        foreach ([['2001-03-01 10:00:00', '10.0.0.1'], ['2001-03-02 10:00:00', '10.0.0.2'], ['2001-03-04 10:00:00', '10.0.0.4']] as [$date, $ip]) {
            $this->conn->executeStatement(
                'INSERT INTO track_e_login (login_user_id, login_date, user_ip, logout_date) VALUES (?, ?, ?, NULL)',
                [$u, $date, $ip]
            );
        }

        // Strictly before the event: a login at the very same second is not the one.
        $this->assertSame('10.0.0.2', Tracking::get_ip_from_user_event($u, '2001-03-03 00:00:00'));
        $this->assertSame('10.0.0.1', Tracking::get_ip_from_user_event($u, '2001-03-02 10:00:00'));
        $this->assertSame('', Tracking::get_ip_from_user_event($u, '2001-03-01 10:00:00'));
        $this->assertFalse(Tracking::get_ip_from_user_event(0, '2001-03-03 00:00:00'));
        $this->assertFalse(Tracking::get_ip_from_user_event($u, ''));
        $link = Tracking::get_ip_from_user_event($u, '2001-03-03 00:00:00', true, 'trace');
        $this->assertStringContainsString('ip=10.0.0.2', $link);
        $this->assertStringContainsString('>trace<', $link);
    }
}
