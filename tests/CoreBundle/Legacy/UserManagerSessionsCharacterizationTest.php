<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Legacy;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use Database;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session as HttpSession;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use UserManager;

/**
 * Characterization tests for the session / course / career / tracking queries of the legacy UserManager
 * that UserManagerCharacterizationTest skipped.
 *
 * They pin what the methods return today (row shape, ordering, NULL handling, DB side effects) so that a rewrite
 * to Doctrine which changes any of it fails here. Fixtures are raw prepared statements (the ORM/resource-node
 * path deadlocks when sibling suites run concurrently) and users/sessions are tagged "us_". Course fixtures are
 * the installer courses 1 (AIACT) and 2 (USINGCHAMILO).
 */
class UserManagerSessionsCharacterizationTest extends AbstractApiTest
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
        $this->setSetting('session.show_all_sessions_on_my_course_page', 'false');
        $this->setSetting('session.session_list_order', 'false');
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

    /** Legacy code runs with an empty identity map on every request. */
    private function fresh(): void
    {
        $this->getEntityManager()->clear();
    }

    private function loginAs(int $userId): void
    {
        $user = $this->getEntityManager()->getRepository(User::class)->find($userId);
        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
        self::getContainer()->get('security.token_storage')->setToken($token);
        \ChamiloSession::write('_user', ['id' => $userId]);
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

    private function rawCategory(string $title): int
    {
        $this->conn()->executeStatement(
            'INSERT INTO session_category (access_url_id, title, date_start, date_end) VALUES (1, ?, \'2030-01-01\', NULL)',
            ['us_'.$title]
        );

        return (int) $this->conn()->lastInsertId();
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

    private function setSetting(string $name, string $value): void
    {
        self::getContainer()->get(SettingsManager::class)->updateSetting($name, $value);
        $this->fresh();
    }

    private function fixedTimes(): array
    {
        return [
            'past_start' => '2001-01-01 00:00:00',
            'past_end' => '2002-01-01 00:00:00',
            'future_start' => '2090-01-01 00:00:00',
            'future_end' => '2091-01-01 00:00:00',
        ];
    }

    // ------------------------------------------------- get_courses_list_by_session

    public function testCoursesListBySessionOrdersByPositionAndShapesRows(): void
    {
        $u = $this->rawUser('cl1');
        $s = $this->rawSession('cl1');
        $this->sessionCourse($s, 1, 2);
        $this->sessionCourse($s, 2, 1);
        $this->sessionCourseUser($s, 1, $u);
        $this->sessionCourseUser($s, 2, $u);

        $list = UserManager::get_courses_list_by_session($u, $s);

        // ksort() on the position key: position 1 (course 2) before position 2 (course 1).
        $this->assertSame([1, 2], array_keys($list));
        $this->assertSame(['USINGCHAMILO', 'AIACT'], array_column($list, 'course_code'));
        $first = $list[1];
        $this->assertEqualsCanonicalizing(
            ['title', 'visibility', 'real_id', 'course_code', 'position', 'unsubscribe', 'status'],
            array_keys($first)
        );
        $this->assertSame(5, $first['status'], 'Rows coming from session_rel_course_rel_user are hard-coded as status 5.');
        $this->assertSame('2', (string) $first['real_id']);
    }

    public function testCoursesListBySessionWithoutPositionsFallsBackToCourseCodeOrder(): void
    {
        $u = $this->rawUser('cl2');
        $s = $this->rawSession('cl2');
        $this->sessionCourse($s, 1, 0);
        $this->sessionCourse($s, 2, 0);
        // Subscribed in reverse order on purpose.
        $this->sessionCourseUser($s, 2, $u);
        $this->sessionCourseUser($s, 1, $u);

        $list = UserManager::get_courses_list_by_session($u, $s);

        $this->assertSame(['AIACT', 'USINGCHAMILO'], array_column($list, 'course_code'));
        // The two rows share position 0: the first takes key 0, the second is appended (key 1).
        $this->assertSame([0, 1], array_keys($list));
    }

    public function testCoursesListBySessionIgnoresOtherUsersAndCoursesNotInSession(): void
    {
        $u = $this->rawUser('cl3');
        $other = $this->rawUser('cl3b');
        $s = $this->rawSession('cl3');
        $this->sessionCourse($s, 1, 1);
        $this->sessionCourseUser($s, 1, $other);
        // Subscription to a course that the session does not contain: inner join on session_rel_course drops it.
        $this->sessionCourseUser($s, 2, $u);

        $this->assertSame([], UserManager::get_courses_list_by_session($u, $s));
    }

    public function testCoursesListBySessionIsEmptyForUnknownUserOrSession(): void
    {
        $s = $this->rawSession('cl6');
        $this->sessionCourse($s, 1, 1);

        $this->assertSame([], UserManager::get_courses_list_by_session(0, $s));
        $this->assertSame([], UserManager::get_courses_list_by_session($this->rawUser('cl6'), 0));
    }

    public function testCoursesListBySessionGivesGeneralCoachEveryCourseOfTheSession(): void
    {
        $coach = $this->rawUser('cl4', 1);
        $s = $this->rawSession('cl4');
        $this->sessionCourse($s, 1, 0);
        $this->sessionCourse($s, 2, 0);
        $this->sessionUser($s, $coach, 3);
        $this->fresh();

        $list = UserManager::get_courses_list_by_session($coach, $s);

        $this->assertSame(['AIACT', 'USINGCHAMILO'], array_column($list, 'course_code'));
        // Rows added through the general-coach branch come from SessionManager::get_course_list_by_session_id():
        // full course rows without the hard-coded "status" key of the subscription branch.
        $this->assertArrayNotHasKey('status', reset($list));
        $this->assertArrayHasKey('real_id', reset($list));
    }

    public function testCoursesListBySessionMultiUrlRequiresTheSessionOnTheCurrentUrl(): void
    {
        $u = $this->rawUser('cl5');
        $withUrl = $this->rawSession('cl5a');
        $noUrl = $this->rawSession('cl5b', null, null, ['no_url' => true]);
        foreach ([$withUrl, $noUrl] as $s) {
            $this->sessionCourse($s, 1, 1);
            $this->sessionCourseUser($s, 1, $u);
        }

        // Single URL: the url condition is not applied at all.
        $this->assertCount(1, UserManager::get_courses_list_by_session($u, $noUrl));

        $this->enableMultiUrl();
        $this->assertCount(1, UserManager::get_courses_list_by_session($u, $withUrl));
        $this->assertSame([], UserManager::get_courses_list_by_session($u, $noUrl), 'Session not linked to the URL.');
    }

    // --------------------------------------------------------- get_sessions_by_category

    public function testSessionsByCategoryIsEmptyWithoutUserOrSessions(): void
    {
        $this->assertSame([], UserManager::get_sessions_by_category(0));
        $this->assertSame([], UserManager::get_sessions_by_category($this->rawUser('sc0')));
        $this->assertSame(0, UserManager::get_sessions_by_category($this->rawUser('sc0b'), true, false, false, true));
    }

    public function testSessionsByCategoryGroupsByCategoryAndShapesRows(): void
    {
        $t = $this->fixedTimes();
        $u = $this->rawUser('sc1');
        $cat = $this->rawCategory('cat');
        $s1 = $this->rawSession('sc1b', $t['past_start'], $t['future_end'], ['category' => $cat]);
        $s2 = $this->rawSession('sc1a', $t['past_start'], null, ['category' => $cat]);
        $s3 = $this->rawSession('sc1c', $t['past_start'], null);
        foreach ([$s1, $s2, $s3] as $s) {
            $this->sessionCourse($s, 1, 1);
            $this->sessionCourseUser($s, 1, $u);
        }
        $this->fresh();

        $res = UserManager::get_sessions_by_category($u, false);

        // Rows without category are keyed by the empty string (null array key).
        $this->assertEqualsCanonicalizing([$cat, ''], array_keys($res));
        $this->assertSame(
            ['id' => $cat, 'name' => 'us_cat', 'date_start' => '2030-01-01', 'date_end' => ''],
            [
                'id' => (int) $res[$cat]['session_category']['id'],
                'name' => $res[$cat]['session_category']['name'],
                'date_start' => $res[$cat]['session_category']['date_start'],
                'date_end' => $res[$cat]['session_category']['date_end'],
            ]
        );
        // Default order is "category title, session title".
        $this->assertSame(['us_sc1a', 'us_sc1b'], array_column($res[$cat]['sessions'], 'session_name'));

        $row = $res[$cat]['sessions'][1];
        $this->assertSame($s1, (int) $row['session_id']);
        $this->assertSame('2001-01-01 00:00:00', $row['access_start_date']);
        $this->assertSame('2091-01-01 00:00:00', $row['access_end_date']);
        $this->assertNull($row['coach_access_start_date'], 'NULL dates stay NULL (not an empty string).');
        $this->assertNull($res[$cat]['sessions'][0]['access_end_date']);
        $this->assertSame(['AIACT'], array_column($row['courses'], 'course_code'));
        $this->assertSame('', $row['collapsed']);
    }

    public function testSessionsByCategorySplitsCurrentAndHistoryByEndDate(): void
    {
        $t = $this->fixedTimes();
        $u = $this->rawUser('sc2');
        $open = $this->rawSession('sc2open', $t['past_start'], $t['future_end']);
        $endless = $this->rawSession('sc2endless', $t['past_start'], null);
        $ended = $this->rawSession('sc2ended', $t['past_start'], $t['past_end']);
        foreach ([$open, $endless, $ended] as $s) {
            $this->sessionCourse($s, 1, 1);
            $this->sessionCourseUser($s, 1, $u);
        }
        $this->fresh();

        $ids = static function (array $res): array {
            $out = [];
            foreach ($res as $cat) {
                foreach ($cat['sessions'] as $s) {
                    $out[] = (int) $s['session_id'];
                }
            }
            sort($out);

            return $out;
        };

        $this->assertSame([$open, $endless], $ids(UserManager::get_sessions_by_category($u, false)), 'Current portal.');
        $this->assertSame([$ended], $ids(UserManager::get_sessions_by_category($u, true)), 'History: only sessions with a past end date.');
        $this->assertSame([$open, $endless, $ended], $ids(UserManager::get_sessions_by_category($u, true, false, true)), 'ignoreTimeLimit.');
    }

    public function testSessionsByCategoryHidesSessionsThatAreNotYetOpenOrHaveNoCourse(): void
    {
        $t = $this->fixedTimes();
        $u = $this->rawUser('sc3');
        $future = $this->rawSession('sc3future', $t['future_start'], $t['future_end']);
        $empty = $this->rawSession('sc3empty', $t['past_start'], $t['past_end']);
        $this->sessionCourse($future, 1, 1);
        $this->sessionCourseUser($future, 1, $u);
        // Student subscribed to a session whose end date passed but which contains no course at all.
        $this->sessionUser($empty, $u, 0);
        $this->sessionCourseUser($empty, 1, $u);
        $this->fresh();

        // Not open yet: access_start_date in the future makes the visibility SESSION_INVISIBLE, even in "all" mode.
        // PINNED QUIRK: the category entry is created before the visibility check, so a hidden session leaves a
        // category stub without a 'sessions' key behind.
        $hidden = UserManager::get_sessions_by_category($u, false, false, true);
        $this->assertSame([''], array_keys($hidden));
        $this->assertSame(['session_category'], array_keys($hidden['']));
        // ... unless the caller says to ignore visibility (admins).
        $res = UserManager::get_sessions_by_category($u, false, true, true);
        $this->assertSame([$empty, $future], array_map(static fn ($r) => (int) $r['session_id'], $res['']['sessions']));

        // Session without any course: not shown in history either (all of its zero courses are "blocked").
        $this->assertArrayNotHasKey('sessions', UserManager::get_sessions_by_category($u, true)['']);
    }

    public function testSessionsByCategoryCoachSessionsComeThroughSessionRelUser(): void
    {
        $t = $this->fixedTimes();
        $coach = $this->rawUser('sc4', 1);
        $stud = $this->rawSession('sc4stud', $t['past_start'], null);
        $coached = $this->rawSession('sc4coach', $t['past_start'], null);
        $this->sessionCourse($stud, 1, 1);
        $this->sessionCourse($coached, 1, 1);
        $this->sessionCourseUser($stud, 1, $coach);
        $this->sessionUser($coached, $coach, 3);
        $this->fresh();

        $res = UserManager::get_sessions_by_category($coach, false);

        $this->assertSame(
            ['us_sc4stud', 'us_sc4coach'],
            array_column($res['']['sessions'], 'session_name'),
            'Student rows are collected first, then coach rows are appended (merged by session id): the SQL order is not global.'
        );
        $this->assertSame(2, UserManager::get_sessions_by_category($coach, false, false, false, true));
    }

    public function testSessionsByCategoryCountAddsStudentAndCoachCounts(): void
    {
        $t = $this->fixedTimes();
        $u = $this->rawUser('sc5', 1);
        $s = $this->rawSession('sc5', $t['past_start'], null);
        $this->sessionCourse($s, 1, 1);
        $this->sessionCourseUser($s, 1, $u);
        $this->sessionUser($s, $u, 3);
        $this->fresh();

        $res = UserManager::get_sessions_by_category($u, false);
        $this->assertCount(1, $res[''] ['sessions'], 'The list merges the two rows into one session.');
        // PINNED QUIRK: the count variant sums the two sub-queries without de-duplicating the session id.
        $this->assertSame(2, UserManager::get_sessions_by_category($u, false, false, false, true));
    }

    public function testSessionsByCategoryIgnoresSessionsOfAnotherUrl(): void
    {
        $t = $this->fixedTimes();
        $u = $this->rawUser('sc6');
        $s = $this->rawSession('sc6', $t['past_start'], null, ['no_url' => true]);
        $this->sessionCourse($s, 1, 1);
        $this->sessionCourseUser($s, 1, $u);
        $this->fresh();

        $this->assertSame([], UserManager::get_sessions_by_category($u, false));
        $this->assertSame(0, UserManager::get_sessions_by_category($u, false, false, false, true));
    }

    public function testSessionsByCategoryHonoursTheSessionOrderSetting(): void
    {
        $t = $this->fixedTimes();
        $u = $this->rawUser('sc7');
        $a = $this->rawSession('sc7a', $t['past_start'], null, ['position' => 2]);
        $b = $this->rawSession('sc7b', $t['past_start'], null, ['position' => 1]);
        foreach ([$a, $b] as $s) {
            $this->sessionCourse($s, 1, 1);
            $this->sessionCourseUser($s, 1, $u);
        }
        $this->fresh();

        $names = static fn (array $res): array => array_column($res['']['sessions'], 'session_name');
        $this->assertSame(['us_sc7a', 'us_sc7b'], $names(UserManager::get_sessions_by_category($u, false)));

        $this->setSetting('session.session_list_order', 'true');
        $this->assertSame(['us_sc7b', 'us_sc7a'], $names(UserManager::get_sessions_by_category($u, false)), 'ORDER BY position.');
    }

    public function testSessionsByCategoryOrdersByAccessStartDateWhenAllSessionsAreShown(): void
    {
        $t = $this->fixedTimes();
        $u = $this->rawUser('sc8');
        $late = $this->rawSession('sc8a', '2005-01-01 00:00:00', null);
        $early = $this->rawSession('sc8b', $t['past_start'], null);
        foreach ([$late, $early] as $s) {
            $this->sessionCourse($s, 1, 1);
            $this->sessionCourseUser($s, 1, $u);
        }
        $this->fresh();
        $this->setSetting('session.show_all_sessions_on_my_course_page', 'true');

        $res = UserManager::get_sessions_by_category($u, false);

        // Title order would give sc8a first; the setting switches to "ORDER BY access start date".
        $this->assertSame(['us_sc8b', 'us_sc8a'], array_column($res['']['sessions'], 'session_name'));
    }

    // ----------------------------------------------- get_personal_session_course_list

    public function testPersonalSessionCourseListIsEmptyForNoUser(): void
    {
        $this->assertSame([], UserManager::get_personal_session_course_list(0));
        $this->assertSame([], UserManager::get_personal_session_course_list($this->rawUser('pl0')));
    }

    public function testPersonalSessionCourseListReturnsDirectCoursesBySortThenTitleWithoutHrmLinks(): void
    {
        $u = $this->rawUser('pl1');
        $this->courseUser($u, 1, 5, 0, 7);
        $this->courseUser($u, 2, 1, 0, 3);
        $hrm = $this->rawUser('pl1h', 1);
        $this->courseUser($hrm, 1, 1, 1, 0); // HR manager relation: excluded.
        $this->fresh();

        $list = UserManager::get_personal_session_course_list($u);

        $this->assertSame([0, 1], array_keys($list));
        $this->assertSame(['USINGCHAMILO', 'AIACT'], array_column($list, 'code'), 'ORDER BY sort ASC.');
        $this->assertSame(['1', '5'], [(string) $list[0]['course_rel_status'], (string) $list[1]['course_rel_status']]);
        $this->assertSame(['3', '7'], [(string) $list[0]['sort'], (string) $list[1]['sort']]);
        $this->assertEqualsCanonicalizing(
            ['code', 'course_rel_status', 'sort', 'user_course_cat', 'course_info'],
            array_keys($list[0])
        );
        $this->assertSame('USINGCHAMILO', $list[0]['course_info']['code']);

        $this->assertSame([], UserManager::get_personal_session_course_list($hrm), 'RRHH relation is not a personal course.');
    }

    public function testPersonalSessionCourseListAddsSessionCoursesKeyedBySessionAndCode(): void
    {
        $t = $this->fixedTimes();
        $u = $this->rawUser('pl2');
        $s = $this->rawSession('pl2', $t['past_start'], $t['future_end']);
        $this->sessionCourse($s, 1, 1);
        $this->sessionUser($s, $u, 0);
        $this->sessionCourseUser($s, 1, $u, 0);
        $this->fresh();

        $list = UserManager::get_personal_session_course_list($u);

        $this->assertSame(["$s - AIACT"], array_keys($list));
        $row = $list["$s - AIACT"];
        $this->assertSame('AIACT', $row['code']);
        $this->assertSame('us_pl2', $row['session_name']);
        $this->assertSame(1, (int) $row['sort']);
        $this->assertStringContainsString('Lastpl2 Firstpl2', $row['t']);
        $this->assertStringEndsWith('@example.com', $row['email']);
        $this->assertSame($t['past_start'], $row['access_start_date']);
        $this->assertSame($t['future_end'], $row['access_end_date']);
        $this->assertSame(
            ['code', 'i', 't', 'email', 'l', 'sort', 'access_start_date', 'access_end_date', 'session_id', 'session_name'],
            array_slice(array_keys($row), 0, 10)
        );
        $this->assertSame('5', (string) $row[array_keys($row)[10]], 'Unnamed IF(...) status column: "5" for a student.');
        $this->assertSame('course_info', array_keys($row)[11]);
    }

    public function testPersonalSessionCourseListMarksCourseCoachWithStatusTwoColumn(): void
    {
        $t = $this->fixedTimes();
        $u = $this->rawUser('pl3', 1);
        $s = $this->rawSession('pl3', $t['past_start'], $t['future_end']);
        $this->sessionCourse($s, 2, 1);
        $this->sessionCourseUser($s, 2, $u, 2);
        $this->fresh();

        // Not a student of the session (no session_rel_user row) and no coach relation: the session is not listed.
        $this->assertSame([], UserManager::get_personal_session_course_list($u));

        $this->sessionUser($s, $u, 0);
        $this->fresh();
        $row = UserManager::get_personal_session_course_list($u)["$s - USINGCHAMILO"];
        $this->assertSame('2', (string) $row[array_keys($row)[10]], 'Course coach status column.');
    }

    public function testPersonalSessionCourseListSkipsInvisibleSessionsAndHonoursLimit(): void
    {
        $t = $this->fixedTimes();
        $u = $this->rawUser('pl4');
        $notOpen = $this->rawSession('pl4future', $t['future_start'], $t['future_end']);
        $a = $this->rawSession('pl4a', $t['past_start'], $t['future_end']);
        $b = $this->rawSession('pl4b', $t['past_start'], $t['future_end']);
        foreach ([$notOpen, $a, $b] as $s) {
            $this->sessionCourse($s, 1, 1);
            $this->sessionUser($s, $u, 0);
            $this->sessionCourseUser($s, 1, $u);
        }
        $this->fresh();

        $keys = array_keys(UserManager::get_personal_session_course_list($u));
        $this->assertEqualsCanonicalizing(["$a - AIACT", "$b - AIACT"], $keys, 'Not-yet-open session is skipped.');

        // The limit applies to the "student" session query ordered by start date, end date, title: the future one
        // sorts last, so limit 1 keeps only $a.
        $limited = array_keys(UserManager::get_personal_session_course_list($u, 1));
        $this->assertSame(["$a - AIACT"], $limited);
    }

    public function testPersonalSessionCourseListMultiUrlFiltersCoursesByUrl(): void
    {
        $u = $this->rawUser('pl5');
        $this->courseUser($u, 1, 5, 0, 1);
        $this->courseUser($u, 2, 5, 0, 2);
        $inUrl = (int) $this->conn()->fetchOne('SELECT COUNT(*) FROM access_url_rel_course WHERE c_id = 1 AND access_url_id = 1');
        $this->assertSame(1, $inUrl, 'Installer course 1 belongs to URL 1.');
        $this->conn()->executeStatement('DELETE FROM access_url_rel_course WHERE c_id = 2 AND access_url_id = 1');
        $this->fresh();

        // Single URL: the url table is ignored.
        $this->assertSame(['AIACT', 'USINGCHAMILO'], array_column(UserManager::get_personal_session_course_list($u), 'code'));

        $this->enableMultiUrl();
        $this->assertSame(['AIACT'], array_column(UserManager::get_personal_session_course_list($u), 'code'));
    }

    public function testPersonalSessionCourseListAsAdminAddsCoachedCoursesOfSessions(): void
    {
        $t = $this->fixedTimes();
        $coach = $this->rawUser('pl6', 1);
        $s = $this->rawSession('pl6', $t['past_start'], $t['future_end']);
        $this->sessionCourse($s, 1, 1);
        $this->sessionUser($s, $coach, 3);
        $this->fresh();

        // Without being allowed to create courses (anonymous caller) a general coach with no student rows sees nothing
        // from the student query, but the general-coach SQL still lists the session; courses need a subscription.
        $this->assertSame([], UserManager::get_personal_session_course_list($coach));

        $this->loginAs(1);
        $list = UserManager::get_personal_session_course_list($coach);
        $this->assertSame(["$s - AIACT"], array_keys($list));
        $row = $list["$s - AIACT"];
        $this->assertSame(['AIACT', 'us_pl6'], [$row['code'], $row['session_name']]);
        $this->assertSame(1, $row['sort']);
        $this->assertStringEndsWith('@example.com', $row['email']);
    }

    // ------------------------------------------------------ getSubscribedSessionsByYear

    public function testSubscribedSessionsByYearMatchesYearsInsideTheAccessRange(): void
    {
        $u = $this->rawUser('yr1');
        $both = $this->rawSession('yr1both', '2031-02-10 10:00:00', '2033-03-10 10:00:00');
        $startOnly = $this->rawSession('yr1start', '2032-05-01 10:00:00', null);
        $endOnly = $this->rawSession('yr1end', null, '2032-06-01 10:00:00');
        foreach ([$both, $startOnly, $endOnly] as $s) {
            $this->sessionCourse($s, 1, 1);
            $this->sessionCourseUser($s, 1, $u);
        }
        $this->fresh();
        $info = ['id' => $u, 'status' => 5];

        $names = static fn (array $r): array => array_map(static fn ($e) => $e['name'], $r);

        $y2031 = UserManager::getSubscribedSessionsByYear($info, 2031);
        $this->assertSame([$both => 'us_yr1both'], $names($y2031));
        $y2032 = UserManager::getSubscribedSessionsByYear($info, 2032);
        $this->assertEqualsCanonicalizing(
            [$both => 'us_yr1both', $startOnly => 'us_yr1start', $endOnly => 'us_yr1end'],
            $names($y2032)
        );
        $this->assertSame([], UserManager::getSubscribedSessionsByYear($info, 2034), 'No end date: only its own year; range ended.');
        $this->assertSame([$both => 'us_yr1both'], $names(UserManager::getSubscribedSessionsByYear($info, 2033)));
        $this->assertNull($y2032[$endOnly]['access_start_date']);
        $this->assertNull($y2032[$startOnly]['access_end_date']);
    }

    public function testSubscribedSessionsByYearUsesCourseFirstAccessForDurationSessions(): void
    {
        $u = $this->rawUser('yr2');
        $s = $this->rawSession('yr2', null, null, ['duration' => 400]);
        $this->sessionCourse($s, 1, 1);
        $this->sessionCourseUser($s, 1, $u);
        $this->fresh();
        $info = ['id' => $u, 'status' => 5];

        $this->assertSame([], UserManager::getSubscribedSessionsByYear($info, 2031), 'Never accessed: no calendar entry.');

        $this->conn()->executeStatement(
            'INSERT INTO track_e_course_access (user_id, c_id, login_course_date, logout_course_date, counter, session_id, user_ip)
             VALUES (?, 1, \'2031-06-15 12:00:00\', \'2031-06-15 13:00:00\', 1, ?, \'127.0.0.1\')',
            [$u, $s]
        );
        $res = UserManager::getSubscribedSessionsByYear($info, 2031);
        $this->assertSame([$s], array_keys($res));
        $this->assertSame('us_yr2', $res[$s]['name']);
        // 400 days after the first access spills into the next year as well.
        $this->assertSame([$s], array_keys(UserManager::getSubscribedSessionsByYear($info, 2032)));
        $this->assertSame([], UserManager::getSubscribedSessionsByYear($info, 2033));
        $this->assertSame([], UserManager::getSubscribedSessionsByYear($info, 2030));
    }

    public function testSubscribedSessionsByYearFormatsTimesOnA24HourClock(): void
    {
        $u = $this->rawUser('yr3');
        $s = $this->rawSession('yr3', '2031-02-10 15:30:00', '2031-03-10 20:00:00');
        $this->sessionCourse($s, 1, 1);
        $this->sessionCourseUser($s, 1, $u);
        $this->fresh();

        $res = UserManager::getSubscribedSessionsByYear(['id' => $u, 'status' => 5], 2031);

        $tz = new \DateTimeZone(api_get_timezone());
        $expected = static fn (string $utc): string => (new \DateTime(api_get_local_time($utc), $tz))->format('H:i:s');
        $this->assertSame(
            [$expected('2031-02-10 15:30:00'), $expected('2031-03-10 20:00:00')],
            [substr($res[$s]['access_start_date'], 11), substr($res[$s]['access_end_date'], 11)]
        );
        $this->assertGreaterThan(12, (int) substr($res[$s]['access_end_date'], 11, 2), 'A 24-hour clock.');
    }

    // --------------------------------------------------------- getSessionsCalendarByYear

    public function testSessionsCalendarByYearComputesWeeksClampsAndSortsByStart(): void
    {
        $list = [
            11 => ['name' => 'later', 'access_start_date' => '2031-06-11 12:00:00', 'access_end_date' => '2031-06-25 12:00:00'],
            12 => ['name' => 'carry', 'access_start_date' => '2030-11-12 12:00:00', 'access_end_date' => '2032-02-11 12:00:00'],
            13 => ['name' => 'open', 'access_start_date' => null, 'access_end_date' => null],
            14 => ['name' => 'short', 'access_start_date' => '2031-03-12 12:00:00', 'access_end_date' => '2031-03-12 13:00:00'],
        ];

        $cal = UserManager::getSessionsCalendarByYear($list, 2031);

        $this->assertSame([12, 13, 14, 11], array_column($cal, 'id'), 'usort on "start" ascending, stable on ties (12 and 13 both start at 0).');
        $by = array_column($cal, null, 'id');

        $this->assertEquals(
            ['start' => 0, 'duration' => 1, 'no_start' => true, 'no_end' => true, 'start_in_last_year' => true, 'end_in_next_year' => false],
            array_intersect_key($by[13], array_flip(['start', 'duration', 'no_start', 'no_end', 'start_in_last_year', 'end_in_next_year']))
        );
        $this->assertEquals(
            ['start' => 0, 'duration' => 52, 'start_in_last_year' => true, 'end_in_next_year' => true],
            array_intersect_key($by[12], array_flip(['start', 'duration', 'start_in_last_year', 'end_in_next_year']))
        );
        $this->assertSame(10, $by[14]['start'], '2031-03-12 is ISO week 11 -> 0-based 10.');
        $this->assertSame(1, $by[14]['duration'], 'A duration below one week is clamped to 1.');
        $this->assertSame(23, $by[11]['start']);
        $this->assertSame(3, $by[11]['duration']);
        $this->assertIsString($by[11]['human_date']);
        $this->assertSame([], UserManager::getSessionsCalendarByYear([], 2031));
    }

    // -------------------------------------------------------- getTimeSpentInCourses

    private function access(int $uid, int $cid, int $sid, string $in, string $out): void
    {
        $this->conn()->executeStatement(
            'INSERT INTO track_e_course_access (user_id, c_id, login_course_date, logout_course_date, counter, session_id, user_ip)
             VALUES (?, ?, ?, ?, 1, ?, \'127.0.0.1\')',
            [$uid, $cid, $in, $out, $sid]
        );
    }

    public function testTimeSpentInCoursesSumsSecondsPerUserCourseAndSession(): void
    {
        $u = $this->rawUser('ts1');
        $this->access($u, 1, 0, '2031-01-01 10:00:00', '2031-01-01 10:10:00');
        $this->access($u, 1, 0, '2031-02-01 10:00:00', '2031-02-01 10:00:30');
        $this->access($u, 2, 0, '2031-01-01 10:00:00', '2031-01-01 11:00:00');
        $this->access($u, 1, 5, '2031-01-01 10:00:00', '2031-01-01 10:01:00');

        $this->assertEquals(630, UserManager::getTimeSpentInCourses($u, 1));
        $this->assertEquals(3600, UserManager::getTimeSpentInCourses($u, 2));
        $this->assertEquals(60, UserManager::getTimeSpentInCourses($u, 1, 5), 'Session 5 is tracked separately.');
        $this->assertSame(0, UserManager::getTimeSpentInCourses($u, 1, 9), 'No rows: SUM is NULL and is returned as int 0.');
        $this->assertSame(0, UserManager::getTimeSpentInCourses($this->rawUser('ts1b'), 1));
    }

    public function testTimeSpentInCoursesDateRangeIsInclusiveOfTheUntilDay(): void
    {
        $u = $this->rawUser('ts2');
        $this->access($u, 1, 0, '2031-01-01 10:00:00', '2031-01-01 10:10:00'); // 600
        $this->access($u, 1, 0, '2031-02-01 10:00:00', '2031-02-01 10:00:30'); // 30
        $this->access($u, 1, 0, '2031-03-01 10:00:00', '2031-03-01 10:01:00'); // 60

        $this->assertEquals(630, UserManager::getTimeSpentInCourses($u, 1, 0, '2031-01-01', '2031-02-01'));
        $this->assertEquals(30, UserManager::getTimeSpentInCourses($u, 1, 0, '2031-02-01', '2031-02-01'), 'Until is extended by one day.');
        $this->assertEquals(690, UserManager::getTimeSpentInCourses($u, 1, 0, '', '2031-02-01'), 'Both bounds are needed, otherwise ignored.');
    }

    // ------------------------------------------------- countUsersWhoFinishedCourses*

    private function gradebookFor(int $cid, ?int $sid = null): int
    {
        $existing = $this->conn()->fetchOne(
            'SELECT id FROM gradebook_category WHERE c_id = ? AND '.(null === $sid ? '(session_id IS NULL OR session_id = 0)' : 'session_id = '.$sid).' ORDER BY id LIMIT 1',
            [$cid]
        );
        if (false !== $existing) {
            return (int) $existing;
        }
        $this->conn()->executeStatement(
            'INSERT INTO gradebook_category (c_id, session_id, title, weight, visible, locked, generate_certificates, is_requirement, calculation_mode)
             VALUES (?, ?, \'us_gb\', 1, 1, 0, 1, 0, \'weighted_average\')',
            [$cid, $sid]
        );

        return (int) $this->conn()->lastInsertId();
    }

    private function certificate(int $catId, int $uid, ?string $path = 'us/cert.html', float $score = 80.0): void
    {
        $this->conn()->executeStatement(
            'INSERT INTO gradebook_certificate (cat_id, user_id, score_certificate, created_at, path_certificate, publish)
             VALUES (?, ?, ?, \'2031-01-01 00:00:00\', ?, 0)',
            [$catId, $uid, $score, $path]
        );
    }

    public function testCountUsersWhoFinishedCoursesCountsSubscribedAndCertifiedPerCourseCode(): void
    {
        $u1 = $this->rawUser('fc1');
        $u2 = $this->rawUser('fc2');
        $this->courseUser($u1, 1);
        $this->courseUser($u2, 1);
        $cat = $this->gradebookFor(1);
        $this->fresh();

        $before = UserManager::countUsersWhoFinishedCourses();
        $this->certificate($cat, $u1);
        $this->fresh();
        $after = UserManager::countUsersWhoFinishedCourses();

        $this->assertSame($before['AIACT']['subscribed'], $after['AIACT']['subscribed']);
        $this->assertSame($before['AIACT']['finished'] + 1, $after['AIACT']['finished']);
        $this->assertGreaterThanOrEqual(2, $after['AIACT']['subscribed']);
        $this->assertSame(['subscribed', 'finished'], array_keys($after['AIACT']));
        $codes = array_keys($after);
        $sorted = $codes;
        sort($sorted);
        $this->assertSame($sorted, $codes, 'Rows are ordered by course code.');
    }

    public function testCountUsersWhoFinishedCoursesOnlyCountsUsersOfTheCurrentUrl(): void
    {
        $u = $this->rawUser('fc3');
        $this->courseUser($u, 2);
        $before = UserManager::countUsersWhoFinishedCourses();
        $this->conn()->executeStatement('DELETE FROM access_url_rel_user WHERE user_id = ?', [$u]);
        $this->fresh();
        $after = UserManager::countUsersWhoFinishedCourses();

        $this->assertSame($before['USINGCHAMILO']['subscribed'] - 1, $after['USINGCHAMILO']['subscribed']);
    }

    public function testCountUsersWhoFinishedCoursesInSessionsKeysByCodeAndSessionTitle(): void
    {
        $t = $this->fixedTimes();
        $u1 = $this->rawUser('fs1');
        $u2 = $this->rawUser('fs2');
        $s = $this->rawSession('fs', $t['past_start'], null);
        $this->sessionCourse($s, 1, 1);
        $this->sessionCourseUser($s, 1, $u1);
        $this->sessionCourseUser($s, 1, $u2);
        $cat = $this->gradebookFor(1, $s);
        $this->certificate($cat, $u2);
        $this->fresh();

        $res = UserManager::countUsersWhoFinishedCoursesInSessions();

        $this->assertSame(['subscribed' => 2, 'finished' => 1], $res['AIACT (us_fs)']);
    }

    public function testCountUsersWhoFinishedCoursesInSessionsWithoutGradebookCountsOnlySubscriptions(): void
    {
        $t = $this->fixedTimes();
        $u = $this->rawUser('fs3');
        $s = $this->rawSession('fs3', $t['past_start'], null);
        $this->sessionCourse($s, 2, 1);
        $this->sessionCourseUser($s, 2, $u);
        $this->fresh();

        $res = UserManager::countUsersWhoFinishedCoursesInSessions();

        $this->assertSame(['subscribed' => 1, 'finished' => 0], $res['USINGCHAMILO (us_fs3)']);
    }

    // ----------------------------------------------------------------------- careers

    private function rawCareer(string $title): int
    {
        $this->conn()->executeStatement(
            'INSERT INTO career (title, description, status, created_at, updated_at) VALUES (?, \'\', 1, NOW(), NOW())',
            ['us_'.$title]
        );

        return (int) $this->conn()->lastInsertId();
    }

    public function testAddUserCareerIsRefusedWhenTheSettingIsOff(): void
    {
        $u = $this->rawUser('ca1');
        $c = $this->rawCareer('ca1');
        $this->setSetting('session.allow_career_users', 'false');

        $this->assertFalse(UserManager::addUserCareer($u, $c));
        $this->assertFalse(UserManager::userHasCareer($u, $c));
        $this->assertFalse(UserManager::updateUserCareer(1, 'x'));
    }

    public function testAddUserCareerInsertsOnceAndUpdateWritesExtraData(): void
    {
        $u = $this->rawUser('ca2');
        $c = $this->rawCareer('ca2');
        $this->setSetting('session.allow_career_users', 'true');

        $this->assertTrue(UserManager::addUserCareer($u, $c));
        $this->assertTrue(UserManager::addUserCareer($u, $c), 'Adding twice is a no-op that still returns true.');
        $this->assertSame(1, (int) $this->conn()->fetchOne('SELECT COUNT(*) FROM user_career WHERE user_id = ? AND career_id = ?', [$u, $c]));
        $this->assertTrue(UserManager::userHasCareer($u, $c));
        $this->assertFalse(UserManager::userHasCareer($u, $this->rawCareer('ca2other')), 'The career id is part of the lookup.');

        $row = UserManager::getUserCareer($u, $c);
        $this->assertSame([$u, $c], [(int) $row['user_id'], (int) $row['career_id']]);
        $this->assertNull($row['extra_data']);
        $this->assertNotNull($row['created_at']);

        $this->assertTrue(UserManager::updateUserCareer($row['id'], 'a:1:{s:1:"k";s:1:"v";}'));
        $this->assertSame('a:1:{s:1:"k";s:1:"v";}', UserManager::getUserCareer($u, $c)['extra_data']);
        $this->assertSame([['id' => (string) $c, 'title' => 'us_ca2']], array_map(
            static fn ($r) => ['id' => (string) $r['id'], 'title' => $r['title']],
            UserManager::getUserCareers($u)
        ));
    }

    // -------------------------------------------------------- certificates

    public function testIsUserCertifiedNeedsANonEmptyCertificatePath(): void
    {
        $u = $this->rawUser('ce1');
        $cat = $this->gradebookFor(1);

        $this->assertFalse(UserManager::is_user_certified($cat, $u));
        $this->certificate($cat, $u, null);
        $this->assertFalse(UserManager::is_user_certified($cat, $u), 'A certificate row without a path is not a certificate.');
        $this->conn()->executeStatement('UPDATE gradebook_certificate SET path_certificate = \'\' WHERE user_id = ?', [$u]);
        $this->assertFalse(UserManager::is_user_certified($cat, $u));
        $this->conn()->executeStatement('UPDATE gradebook_certificate SET path_certificate = \'p.html\' WHERE user_id = ?', [$u]);
        $this->assertTrue(UserManager::is_user_certified($cat, $u));
        $this->assertFalse(UserManager::is_user_certified(0, $u));
    }

    public function testGetInfoGradebookCertificateReturnsFalseWithoutCertificate(): void
    {
        $u = $this->rawUser('ce2');
        $this->gradebookFor(1);
        $sid = $this->rawSession('ce2');
        $this->gradebookFor(1, $sid);

        $this->assertFalse(UserManager::get_info_gradebook_certificate(['real_id' => 1], 0, $u));
        $this->assertFalse(UserManager::get_info_gradebook_certificate(['real_id' => 1], $sid, $u));
    }

    // ------------------------------------------------- teacher / student relations

    public function testTeacherOfStudentIsDecidedByCommonCourses(): void
    {
        $teacher = $this->rawUser('te1', 1);
        $student = $this->rawUser('te1s');
        $other = $this->rawUser('te1o');
        $this->courseUser($teacher, 1, 1);
        $this->courseUser($teacher, 2, 5); // Only a student there: not a teacher course.
        $this->courseUser($student, 1, 5);
        $this->courseUser($student, 2, 5);
        $this->courseUser($other, 2, 5);
        $this->fresh();

        $this->assertSame(['AIACT'], array_values(UserManager::getCommonCoursesBetweenTeacherAndStudent($teacher, $student)));
        $this->assertTrue(UserManager::isTeacherOfStudent($teacher, $student));
        $this->assertSame([], UserManager::getCommonCoursesBetweenTeacherAndStudent($teacher, $other));
        $this->assertFalse(UserManager::isTeacherOfStudent($teacher, $other));
        // False (not []) when either side has no courses at all.
        $this->assertFalse(UserManager::getCommonCoursesBetweenTeacherAndStudent($student, $teacher) ? true : false);
        $this->assertFalse(UserManager::getCommonCoursesBetweenTeacherAndStudent($this->rawUser('te1x'), $student));
    }

    public function testGetUsersFollowedByStudentBossIsTheBossVariantOfGetUsersFollowedByUser(): void
    {
        $boss = $this->rawUser('bo1');
        $stud = $this->rawUser('bo1s');
        $this->conn()->executeStatement(
            'INSERT INTO user_rel_user (user_id, friend_user_id, relation_type, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())',
            [$stud, $boss, \Chamilo\CoreBundle\Entity\UserRelUser::USER_RELATION_TYPE_BOSS]
        );

        $this->assertSame([$stud], array_keys(UserManager::getUsersFollowedByStudentBoss($boss)));
        $this->assertSame([], UserManager::getUsersFollowedByStudentBoss($stud));
        $this->assertSame(
            array_keys(UserManager::getUsersFollowedByUser($boss, 0, false, false, false, null, null, null, null, null, null, STUDENT_BOSS)),
            array_keys(UserManager::getUsersFollowedByStudentBoss($boss))
        );
    }

    // --------------------------------------------------------------- tag search

    public function testGetAllUserTagsMatchesByTagOrNameAndScopesToCurrentUrlActiveNonAnonymous(): void
    {
        $match = $this->rawUser('tg1');
        $inactive = $this->rawUser('tg1i');
        $anon = $this->rawUser('tg1a', 6);
        $otherUrl = $this->rawUser('tg1u');
        $this->conn()->executeStatement('UPDATE user SET active = 0 WHERE id = ?', [$inactive]);
        $secondUrl = $this->enableMultiUrl();
        $this->conn()->executeStatement('UPDATE access_url_rel_user SET access_url_id = ? WHERE user_id = ?', [$secondUrl, $otherUrl]);

        $fieldId = (int) $this->conn()->fetchOne('SELECT id FROM extra_field WHERE item_type = 1 LIMIT 1');
        if (!$fieldId) {
            $this->conn()->executeStatement(
                'INSERT INTO extra_field (item_type, value_type, variable, display_text, field_order, visible_to_self,
                    visible_to_others, changeable, filter, created_at, auto_remove)
                 VALUES (1, 1, \'us_tagfield\', \'us_tagfield\', 1, 1, 1, 1, 0, NOW(), 0)'
            );
            $fieldId = (int) $this->conn()->lastInsertId();
        }
        $this->conn()->executeStatement('INSERT INTO tag (tag, field_id, count) VALUES (\'us_zzuniquetag\', ?, 4)', [$fieldId]);
        $tagId = (int) $this->conn()->lastInsertId();
        foreach ([$match, $inactive, $anon, $otherUrl] as $uid) {
            $this->conn()->executeStatement('INSERT INTO user_rel_tag (user_id, tag_id) VALUES (?, ?)', [$uid, $tagId]);
        }

        $res = UserManager::get_all_user_tags('us_zzuniquetag');

        $this->assertArrayHasKey($match, $res);
        $this->assertSame('us_zzuniquetag', $res[$match]['tag']);
        $this->assertSame(1, UserManager::get_all_user_tags('us_zzuniquetag', 0, 0, 10, true) ? 1 : 0);
        // The url/active/anonymous filters apply to the tag match as well as to the name match.
        $this->assertArrayNotHasKey($inactive, $res, 'Inactive users are excluded.');
        $this->assertArrayNotHasKey($anon, $res, 'Anonymous users are excluded.');
        $this->assertArrayNotHasKey($otherUrl, $res, 'Users of another URL are excluded.');
        // The same users matched by name only are filtered.
        $byName = UserManager::get_all_user_tags($this->conn()->fetchOne('SELECT username FROM user WHERE id = ?', [$inactive]));
        $this->assertArrayNotHasKey($inactive, $byName);
    }

    public function testGetUserSubscriptionTabIsEmptyForNonAdminsWithoutTheCourseAdminSetting(): void
    {
        $this->assertSame('', UserManager::getUserSubscriptionTab());
    }
}
