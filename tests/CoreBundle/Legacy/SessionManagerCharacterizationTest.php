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
 * Characterization tests for the raw SQL of the legacy SessionManager.
 *
 * They pin what callers observe today (returned rows and keys, ordering, NULL handling, the
 * counters kept in session / session_rel_course, deletion side effects) so that a rewrite of
 * the queries to DQL cannot change behaviour unnoticed. Everything created here is tagged "sm_".
 *
 * Counters are read with raw SQL on purpose: the entity manager's identity map would otherwise
 * hide what the legacy UPDATE statements really wrote.
 */
class SessionManagerCharacterizationTest extends AbstractApiTest
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

    private function newSession(string $title, array $extra = []): int
    {
        $id = SessionManager::create_session(
            $title,
            $extra['start'] ?? '',
            $extra['end'] ?? '',
            '',
            '',
            $extra['coachStart'] ?? '',
            $extra['coachEnd'] ?? '',
            [],
            $extra['category'] ?? 0,
            $extra['visibility'] ?? 1,
            false,
            null,
            '',
        );
        $this->assertIsInt($id, 'create_session returned: '.var_export($id, true));

        return $id;
    }

    /**
     * Every legacy page runs in its own request, with an empty identity map. The raw SQL these
     * methods issue is invisible to entities the test already loaded, so reset it between steps.
     */
    private function fresh(int $returned = 0): int
    {
        $this->getEntityManager()->clear();

        return $returned;
    }

    /**
     * Courses are inserted with plain SQL (no resource node): creating them through the repository
     * locks the shared resource_node tree and deadlocks when other suites run on the same database.
     *
     * @return array{0: int, 1: string} course id and code
     */
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

    /**
     * Calls a mutating SessionManager method, then empties the identity map (see fresh()).
     */
    private function legacy(string $method, mixed ...$args): mixed
    {
        try {
            return SessionManager::$method(...$args);
        } finally {
            $this->getEntityManager()->clear();
        }
    }

    private function one(string $sql, array $params = []): mixed
    {
        return Database::getConnection()->fetchOne($sql, $params);
    }

    private function counters(int $sessionId): array
    {
        return Database::getConnection()->fetchAssociative(
            'SELECT nbr_users, nbr_courses FROM session WHERE id = ?',
            [$sessionId]
        );
    }

    private function courseCounter(int $sessionId, int $courseId): mixed
    {
        return $this->one(
            'SELECT nbr_users FROM session_rel_course WHERE session_id = ? AND c_id = ?',
            [$sessionId, $courseId]
        );
    }

    // ---------------------------------------------------------------- create / fetch / names

    public function testCreateSessionRejectsInvalidInputWithTranslatedMessages(): void
    {
        $this->newSession('sm_dup');

        $this->assertSame(get_lang('A title is required'), SessionManager::create_session('  ', '', '', '', '', '', '', [], 0, 1, false, null, ''));
        $this->assertSame(
            get_lang('Invalid start date was given.'),
            SessionManager::create_session('sm_bad_start', 'not-a-date', '', '', '', '', '', [], 0, 1, false, null, '')
        );
        $this->assertSame(
            get_lang('Invalid end date was given.'),
            SessionManager::create_session('sm_bad_end', '', 'nope', '', '', '', '', [], 0, 1, false, null, '')
        );
        $this->assertSame(
            get_lang('The first date should be before the end date'),
            SessionManager::create_session('sm_rev', '2030-02-01 10:00:00', '2030-01-01 10:00:00', '', '', '', '', [], 0, 1, false, null, '')
        );
        $this->assertSame(
            get_lang('Session title already exists'),
            SessionManager::create_session('sm_dup', '', '', '', '', '', '', [], 0, 1, false, null, '')
        );
        $this->assertSame(0, (int) $this->one("SELECT COUNT(*) FROM session WHERE title IN ('sm_bad_start','sm_bad_end','sm_rev')"));
    }

    public function testCreateSessionFixNameGeneratesSuffixedTitle(): void
    {
        $this->newSession('sm_fix');
        $this->newSession('sm_fix_2'); // makes the LIKE 'sm_fix%' count 2 -> next is _3

        $id = SessionManager::create_session('sm_fix', '', '', '', '', '', '', [], 0, 1, true, null, '');

        $this->assertIsInt($id);
        $this->assertSame('sm_fix_3', $this->one('SELECT title FROM session WHERE id = ?', [$id]));
    }

    public function testCreateSessionStoresDatesVisibilityAndFetchExposesThem(): void
    {
        $id = $this->newSession('sm_dates', [
            'start' => '2030-01-01 10:00:00',
            'end' => '2030-01-31 10:00:00',
            'visibility' => 2,
        ]);

        $row = SessionManager::fetch($id);

        $this->assertSame($id, $row['id']);
        $this->assertSame('sm_dates', $row['title']);
        $this->assertSame('sm_dates', $row['name'], '"name" is kept as a backward-compatible alias of "title".');
        $this->assertSame(2, $row['visibility']);
        $this->assertNull($row['session_category_id']);
        $this->assertSame(0, $row['promotion_id'], 'No promotion maps to 0, not NULL.');
        $this->assertNull($row['display_start_date']);
        $this->assertNull($row['display_start_date_to_local_time']);
        $this->assertNotNull($row['access_start_date_to_local_time']);
        $diff = (new \DateTimeImmutable($row['access_end_date']))->getTimestamp()
            - (new \DateTimeImmutable($row['access_start_date']))->getTimestamp();
        $this->assertSame(30 * 86400, $diff);
        // With the default session_days_before/after_coach_access settings (0) no coach window is derived.
        $this->assertNull($row['coach_access_start_date']);
        $this->assertNull($row['coach_access_end_date']);
        $this->assertSame([], SessionManager::fetch(0));
        $this->assertSame([], SessionManager::fetch(99999999));
    }

    public function testCreatorIsRegisteredAsSessionAdminAndCountedInNbrUsers(): void
    {
        $id = $this->newSession('sm_admin_counted');

        $rows = Database::getConnection()->fetchAllAssociative(
            'SELECT user_id, relation_type FROM session_rel_user WHERE session_id = ?',
            [$id]
        );
        $this->assertSame(
            [['user_id' => $this->getAdmin()->getId(), 'relation_type' => Session::SESSION_ADMIN]],
            $rows
        );
        // Surprising but current: the creating admin is counted in session.nbr_users.
        $this->assertSame(1, (int) $this->counters($id)['nbr_users']);
    }

    public function testNameLookups(): void
    {
        $a = $this->newSession('sm_name_a');
        $b = $this->newSession('sm_name_b');

        $this->assertTrue(SessionManager::sessionNameExists('sm_name_a'));
        $this->assertFalse(SessionManager::sessionNameExists('sm_name_zzz'));

        $row = SessionManager::get_session_by_name(' sm_name_a ');
        $this->assertSame($a, (int) $row['id']);
        $this->assertSame('sm_name_a', $row['title']);
        $this->assertFalse(SessionManager::get_session_by_name('sm_name_zzz'));
        $this->assertFalse(SessionManager::get_session_by_name('   '));

        $this->assertTrue(SessionManager::sessionNameExistBesidesMySession($b, 'sm_name_a'));
        $this->assertFalse(SessionManager::sessionNameExistBesidesMySession($a, 'sm_name_a'), 'A session never conflicts with itself.');
        $this->assertFalse(SessionManager::sessionNameExistBesidesMySession($a, ''));
    }

    public function testGenerateNextSessionName(): void
    {
        $this->newSession('sm_gen');

        $this->assertSame('sm_gen_free', SessionManager::generateNextSessionName('sm_gen_free'));
        $this->assertSame('sm_gen_2', SessionManager::generateNextSessionName('sm_gen'));
    }

    public function testEditSession(): void
    {
        $id = $this->newSession('sm_edit_a');
        $other = $this->newSession('sm_edit_b');

        $this->assertSame($id, $this->legacy('edit_session', 
            $id, 'sm_edit_renamed', '2030-03-01 10:00:00', '2030-03-11 10:00:00', '', '', '', '', [], 0, 2, 'new description'
        ));
        $this->getEntityManager()->clear();

        $row = SessionManager::fetch($id);
        $this->assertSame('sm_edit_renamed', $row['title']);
        $this->assertSame('new description', $row['description']);
        $this->assertSame(2, $row['visibility']);
        $this->assertNotNull($row['access_start_date']);

        // Renaming onto another session's title is refused, and the flash path returns false.
        $this->assertFalse($this->legacy('edit_session', $id, 'sm_edit_b', '', '', '', '', '', '', [], 0, 1));
        $this->assertFalse($this->legacy('edit_session', $id, '', '', '', '', '', '', '', [], 0, 1));
        $this->assertFalse($this->legacy('edit_session', $id, 'sm_edit_x', '2030-05-01 10:00:00', '2030-04-01 10:00:00', '', '', '', '', [], 0, 1));
        $this->assertSame('sm_edit_b', $this->one('SELECT title FROM session WHERE id = ?', [$other]));
    }

    public function testCreateSessionStoresExplicitCoachDatesAndTheCategory(): void
    {
        $cat = SessionManager::create_category_session('sm_cat_ignored', 2030, 1, 1, 0, 0, 0);
        $id = SessionManager::create_session(
            'sm_coach_dates',
            '2030-01-10 10:00:00',
            '2030-01-20 10:00:00',
            '',
            '',
            '2030-01-05 10:00:00',
            '2030-01-25 10:00:00',
            [],
            $cat,
            1,
            false,
            null,
            ''
        );
        $row = SessionManager::fetch($id);

        $this->assertNotNull($row['coach_access_start_date']);
        $before = (new \DateTimeImmutable($row['access_start_date']))->getTimestamp()
            - (new \DateTimeImmutable($row['coach_access_start_date']))->getTimestamp();
        $this->assertSame(5 * 86400, $before);
        // Fixed: create_session() used to ignore $sessionCategoryId; it is now stored.
        $this->assertSame($cat, (int) $row['session_category_id']);
        $this->assertSame($cat, (int) $this->one('SELECT session_category_id FROM session WHERE id = ?', [$id]));
    }

    public function testCreateSessionWithNullDescriptionStoresAnEmptyDescription(): void
    {
        // Fixed: the documented default ($description = null) used to hit Session::setDescription(string).
        $id = SessionManager::create_session('sm_null_desc', '', '', '', '', '', '', [], 0);
        $this->assertIsInt($id);
        $this->assertEmpty($this->one('SELECT description FROM session WHERE id = ?', [$id]));
    }

    // ---------------------------------------------------------------- categories

    public function testSessionCategoryLifecycle(): void
    {
        $withEnd = SessionManager::create_category_session('sm_cat_b', 2030, 1, 5, 2030, 12, 25);
        $noEnd = SessionManager::create_category_session('sm_cat_a', 2030, 2, 3, 0, 0, 0);

        $this->assertIsInt($withEnd);
        $this->assertIsInt($noEnd);

        $row = SessionManager::get_session_category($withEnd);
        $this->assertSame(['id', 'title', 'date_start', 'date_end'], array_keys($row)
            === [0, 1, 2, 3] ? [] : array_values(array_filter(array_keys($row), 'is_string')));
        $this->assertSame('sm_cat_b', $row['title']);
        $this->assertSame('2030-01-05', $row['date_start']);
        $this->assertSame('2030-12-25', $row['date_end']);
        $this->assertNull(SessionManager::get_session_category($noEnd)['date_end'], 'No end date is stored as NULL.');
        $this->assertFalse(SessionManager::get_session_category(99999999));

        // The list is ordered by title ASC and scoped to the current access URL.
        $titles = array_values(array_filter(
            array_column(SessionManager::get_all_session_category(), 'title'),
            static fn ($t) => str_starts_with($t, 'sm_cat_')
        ));
        $this->assertSame(['sm_cat_a', 'sm_cat_b'], $titles);

        $this->assertTrue(SessionManager::edit_category_session($withEnd, 'sm_cat_c', 2030, 1, 6, 0, 0, 0));
        $edited = SessionManager::get_session_category($withEnd);
        $this->assertSame('sm_cat_c', $edited['title']);
        $this->assertSame('2030-01-06', $edited['date_start']);
        $this->assertNull($edited['date_end']);

        $this->assertSame(['sm_cat_a'], array_values(array_filter(
            array_column(SessionManager::get_all_session_category(), 'title'),
            static fn ($t) => 'sm_cat_a' === $t
        )));
        $this->assertSame($noEnd, SessionManager::getSessionCategoryIdByName('sm_cat_a'));
        $this->assertTrue(SessionManager::getSessionCategoryIdByName('sm_cat_unknown')['error']);
        $this->assertTrue(SessionManager::getSessionCategoryIdByName('')['error']);
    }

    public function testSessionCategoryValidationMessages(): void
    {
        $this->assertSame(
            get_lang('Please give a name to the sessions category'),
            SessionManager::create_category_session('', 2030, 1, 1, 0, 0, 0)
        );
        $this->assertSame(
            get_lang('Invalid start date was given.'),
            SessionManager::create_category_session('sm_cat_bad', 2030, 2, 31, 0, 0, 0)
        );
        $this->assertSame(
            get_lang('Invalid end date was given.'),
            SessionManager::create_category_session('sm_cat_bad', 2030, 1, 1, 2030, 2, 31)
        );
        $this->assertSame(
            get_lang('The first date should be before the end date'),
            SessionManager::create_category_session('sm_cat_bad', 2030, 5, 1, 2030, 4, 1)
        );
        $this->assertSame(0, (int) $this->one("SELECT COUNT(*) FROM session_category WHERE title = 'sm_cat_bad'"));
    }

    public function testDeleteCategoryDetachesSessionsOrDeletesThem(): void
    {
        $detachCat = SessionManager::create_category_session('sm_cat_detach', 2030, 1, 1, 0, 0, 0);
        $dropCat = SessionManager::create_category_session('sm_cat_drop', 2030, 1, 1, 0, 0, 0);
        $kept = $this->newSession('sm_in_detached_cat', ['category' => $detachCat]);
        $dropped = $this->newSession('sm_in_dropped_cat', ['category' => $dropCat]);
        $this->assertSame($detachCat, (int) $this->one('SELECT session_category_id FROM session WHERE id = ?', [$kept]));

        $this->assertTrue($this->legacy('delete_session_category', $detachCat));
        $this->assertFalse($this->one('SELECT id FROM session_category WHERE id = ?', [$detachCat]));
        $this->assertNull($this->one('SELECT session_category_id FROM session WHERE id = ?', [$kept]), 'Without deleteSessions the sessions survive with a NULL category.');

        $this->assertTrue($this->legacy('delete_session_category', [$dropCat], true, true));
        $this->assertFalse($this->one('SELECT id FROM session_category WHERE id = ?', [$dropCat]));
        $this->assertFalse($this->one('SELECT id FROM session WHERE id = ?', [$dropped]), 'With deleteSessions the category sessions are deleted.');
    }

    // ---------------------------------------------------------------- courses in sessions

    public function testAddCoursesToSessionKeepsCounterAndRelations(): void
    {
        [$c1] = $this->newCourse('sm_course_one');
        [$c2] = $this->newCourse('sm_course_two');
        $sid = $this->newSession('sm_s_courses');

        $this->assertFalse($this->legacy('add_courses_to_session', 0, [$c1]));
        $this->assertFalse($this->legacy('add_courses_to_session', $sid, []));
        $this->assertFalse(SessionManager::relation_session_course_exist($sid, $c1));

        $this->assertTrue($this->legacy('add_courses_to_session', $sid, [$c1, $c2]));

        $this->assertTrue(SessionManager::relation_session_course_exist($sid, $c1));
        $this->assertSame(2, (int) $this->counters($sid)['nbr_courses']);
        $this->assertSame(0, (int) $this->courseCounter($sid, $c1));

        // Adding the same course again must not duplicate the relation nor inflate the counter.
        $this->assertTrue($this->legacy('add_courses_to_session', $sid, [$c1, $c2], false));
        $this->assertSame(2, (int) $this->one('SELECT COUNT(*) FROM session_rel_course WHERE session_id = ?', [$sid]));
        $this->assertSame(2, (int) $this->counters($sid)['nbr_courses']);

        // With removeExistingCoursesWithUsers (default) courses missing from the list are dropped.
        $this->assertTrue($this->legacy('add_courses_to_session', $sid, [$c2]));
        $this->assertFalse(SessionManager::relation_session_course_exist($sid, $c1));
        $this->assertTrue(SessionManager::relation_session_course_exist($sid, $c2));
        $this->assertSame(1, (int) $this->counters($sid)['nbr_courses']);
    }

    public function testCourseListsOfASession(): void
    {
        [$cb] = $this->newCourse('sm_clist_beta');
        [$ca, $codeA] = $this->newCourse('sm_clist_alpha');
        $sid = $this->newSession('sm_s_clist');
        $empty = $this->newSession('sm_s_clist_empty');
        $this->legacy('add_courses_to_session', $sid, [$cb, $ca]);

        $list = SessionManager::get_course_list_by_session_id($sid);
        // Keyed by real course id, ordered by title when session_course_ordering is off.
        $this->assertSame([$ca, $cb], array_keys($list));
        $this->assertSame($ca, (int) $list[$ca]['real_id']);
        $this->assertSame($codeA, $list[$ca]['course_code']);
        $this->assertSame('sm_clist_alpha', $list[$ca]['title']);

        $this->assertSame([$ca], array_keys(SessionManager::get_course_list_by_session_id($sid, 'alpha')));
        $this->assertSame([], SessionManager::get_course_list_by_session_id($sid, 'no-such-course'));
        $this->assertSame(2, SessionManager::get_course_list_by_session_id($sid, '', null, true));
        $this->assertSame(0, SessionManager::get_course_list_by_session_id($empty, '', null, true) ?: 0);
        $this->assertSame([], SessionManager::get_course_list_by_session_id($empty));

        $this->assertSame([$cb, $ca], array_keys(SessionManager::get_course_list_by_session_id($sid, '', 'title DESC')));

        $like = SessionManager::get_course_list_by_session_id_like($sid, 'BETA');
        $this->assertSame([$cb], array_keys($like));
        $this->assertSame(['id', 'title'], array_keys($like[$cb]));

        $this->assertEquals(2, SessionManager::getCourseCountBySessionId($sid));
        $this->assertEquals(1, SessionManager::getCourseCountBySessionId($sid, 'alpha'));
        $this->assertEquals(0, SessionManager::getCourseCountBySessionId($empty));

        $ids = SessionManager::getCoursesInSession($sid);
        sort($ids);
        $this->assertEquals([$cb, $ca], $ids, 'Course ids in creation order.');
        $this->assertSame([], SessionManager::getCoursesInSession(0));

        $this->assertTrue(SessionManager::sessionHasCourse($sid, $codeA));
        $this->assertFalse(SessionManager::sessionHasCourse($empty, $codeA));
        $this->assertFalse(SessionManager::sessionHasCourse($sid, 'NO_SUCH_CODE'));
    }

    public function testGetSessionByCourseAndUnsubscribeCourse(): void
    {
        [$c1] = $this->newCourse('sm_cbs_one');
        $s1 = $this->newSession('sm_cbs_s1');
        $s2 = $this->newSession('sm_cbs_s2');
        $this->legacy('add_courses_to_session', $s1, [$c1]);
        $this->legacy('add_courses_to_session', $s2, [$c1]);

        $rows = SessionManager::get_session_by_course($c1);
        $this->assertEqualsCanonicalizing([$s1, $s2], array_map('intval', array_column($rows, 'id')));
        $this->assertSame(['title', 'id'], array_values(array_filter(array_keys($rows[0]), 'is_string')));
        $this->assertSame([], SessionManager::get_session_by_course(0));

        $this->assertTrue($this->legacy('unsubscribe_course_from_session', $s1, $c1));
        $this->assertSame(0, (int) $this->counters($s1)['nbr_courses']);
        $this->assertFalse(SessionManager::relation_session_course_exist($s1, $c1));
        $this->assertTrue(SessionManager::relation_session_course_exist($s2, $c1));
        // Second call finds nothing to delete: false and no counter underflow.
        $this->assertFalse($this->legacy('unsubscribe_course_from_session', $s1, $c1));
        $this->assertSame(0, (int) $this->counters($s1)['nbr_courses']);
        $this->assertFalse($this->legacy('unsubscribe_course_from_session', 0, $c1));
    }

    public function testGetSessionsListJoinsCoursesAndSupportsFilterOrderAndLimit(): void
    {
        [$c1] = $this->newCourse('sm_list_course');
        $cat = SessionManager::create_category_session('sm_list_cat', 2030, 1, 1, 0, 0, 0);
        $sb = $this->newSession('sm_list_b', ['category' => $cat]);
        $sa = $this->newSession('sm_list_a');
        $sNoCourse = $this->newSession('sm_list_nocourse');
        $this->legacy('add_courses_to_session', $sb, [$c1]);
        $this->legacy('add_courses_to_session', $sa, [$c1]);
        $only = [$sa, $sb, $sNoCourse];

        $all = SessionManager::get_sessions_list([], ['title', 'asc'], null, null, 0, $only);
        // INNER JOIN on courses: a session without any course is not listed at all.
        $this->assertSame([$sa, $sb], array_keys($all));
        $this->assertSame(['id', 'title', 'nbr_courses', 'access_start_date', 'access_end_date', 'category_name', 'promotion_id'], array_values(array_filter(array_keys($all[$sa]), 'is_string')));
        $this->assertNull($all[$sa]['category_name']);
        $this->assertSame('sm_list_cat', $all[$sb]['category_name']);

        $this->assertSame([$sb, $sa], array_keys(SessionManager::get_sessions_list([], ['title', 'desc'], null, null, 0, $only)));
        $this->assertSame([$sb], array_keys(SessionManager::get_sessions_list([], ['title', 'desc'], 0, 1, 0, $only)));
        $this->assertSame(
            [$sb],
            array_keys(SessionManager::get_sessions_list(['s.title' => ['operator' => 'LIKE', 'value' => '%list_b']], [], null, null, 0, $only))
        );
        $this->assertSame(
            [$sa],
            array_keys(SessionManager::get_sessions_list(['s.id' => ['operator' => '=', 'value' => (string) $sa]], [], null, null, 0, $only))
        );
        // A field outside the allow-list is silently ignored, not an error.
        $this->assertCount(2, SessionManager::get_sessions_list(['s.nbr_users' => ['operator' => '=', 'value' => '999']], [], null, null, 0, $only));
    }

    // ---------------------------------------------------------------- users in sessions

    public function testSubscribeUsersToSessionPropagatesToCoursesAndKeepsCounters(): void
    {
        $alice = $this->createUser('sm_alice');
        $bob = $this->createUser('sm_bob');
        [$c1] = $this->newCourse('sm_usub_course');
        $sid = $this->newSession('sm_s_usub');
        $this->legacy('add_courses_to_session', $sid, [$c1]);

        $this->assertFalse($this->legacy('subscribeUsersToSession', 0, [$alice->getId()]));
        $this->assertFalse($this->legacy('subscribeUsersToSession', $sid, ['x1']), 'Non integer ids are refused.');

        $this->assertTrue($this->legacy('subscribeUsersToSession', $sid, [$alice->getId(), $bob->getId()]));

        $this->assertTrue(SessionManager::isUserSubscribedAsStudent($sid, $alice->getId()));
        $this->assertFalse(SessionManager::isUserSubscribedAsHRM($sid, $alice->getId()));
        // Students + the session admin that create_session registered.
        $this->assertSame(3, (int) $this->counters($sid)['nbr_users']);
        $this->assertSame(
            2,
            (int) $this->one('SELECT COUNT(*) FROM session_rel_course_rel_user WHERE session_id = ? AND c_id = ? AND status = ?', [$sid, $c1, Session::STUDENT])
        );
        $this->assertSame(2, (int) $this->courseCounter($sid, $c1), 'session_rel_course.nbr_users counts the students of the course.');
    }

    public function testSubscribeUsersToSessionReplacesStudentsWhenEmptyUsersIsTrue(): void
    {
        $alice = $this->createUser('sm_alice');
        $bob = $this->createUser('sm_bob');
        [$c1] = $this->newCourse('sm_repl_course');
        $sid = $this->newSession('sm_s_repl');
        $this->legacy('add_courses_to_session', $sid, [$c1]);
        $this->legacy('subscribeUsersToSession', $sid, [$alice->getId()]);

        $this->legacy('subscribeUsersToSession', $sid, [$bob->getId()]);

        $this->assertFalse(SessionManager::isUserSubscribedAsStudent($sid, $alice->getId()));
        $this->assertTrue(SessionManager::isUserSubscribedAsStudent($sid, $bob->getId()));
        $this->assertSame([(string) $bob->getId()], array_map('strval', SessionManager::getUsersByCourseSession($sid, ['real_id' => $c1])));

        // empty_users=false keeps existing students and only adds.
        $this->legacy('subscribeUsersToSession', $sid, [$alice->getId()], SESSION_VISIBLE_READ_ONLY, false);
        $this->assertTrue(SessionManager::isUserSubscribedAsStudent($sid, $alice->getId()));
        $this->assertTrue(SessionManager::isUserSubscribedAsStudent($sid, $bob->getId()));
    }

    public function testUnsubscribeUserFromSessionDecrementsCountersOnlyWhenSubscribed(): void
    {
        $alice = $this->createUser('sm_alice');
        $bob = $this->createUser('sm_bob');
        [$c1] = $this->newCourse('sm_unsub_course');
        $sid = $this->newSession('sm_s_unsub');
        $this->legacy('add_courses_to_session', $sid, [$c1]);
        $this->legacy('subscribeUsersToSession', $sid, [$alice->getId(), $bob->getId()]);
        $before = (int) $this->counters($sid)['nbr_users'];

        $this->assertTrue($this->legacy('unsubscribe_user_from_session', $sid, $alice->getId()));

        $this->assertSame($before - 1, (int) $this->counters($sid)['nbr_users']);
        $this->assertFalse(SessionManager::isUserSubscribedAsStudent($sid, $alice->getId()));
        $this->assertSame(
            0,
            (int) $this->one('SELECT COUNT(*) FROM session_rel_course_rel_user WHERE session_id = ? AND user_id = ?', [$sid, $alice->getId()]),
            'The user is also removed from every course of the session.'
        );

        // A never-subscribed user must not push the counter down.
        $this->assertTrue($this->legacy('unsubscribe_user_from_session', $sid, $this->createUser('sm_carol')->getId()));
        $this->assertSame($before - 1, (int) $this->counters($sid)['nbr_users']);
    }

    public function testGetUsersBySessionOrderingCountAndStatusFilter(): void
    {
        $zed = $this->createUser('sm_zed');
        $amy = $this->createUser('sm_amy');
        $sid = $this->newSession('sm_s_users');
        $this->legacy('subscribeUsersToSession', $sid, [$zed->getId(), $amy->getId()]);

        $all = SessionManager::get_users_by_session($sid);
        $this->assertSame([], SessionManager::get_users_by_session(0));
        // Ordered by relation_type then name: the students come first (0), the admin (4) last.
        $this->assertSame(
            [Session::STUDENT, Session::STUDENT, Session::SESSION_ADMIN],
            array_map(static fn ($r) => (int) $r['relation_type'], $all)
        );
        $this->assertSame([$amy->getId(), $zed->getId(), $this->getAdmin()->getId()], array_map(static fn ($r) => (int) $r['user_id'], $all));
        $this->assertSame(
            ['user_id', 'lastname', 'firstname', 'username', 'relation_type', 'access_url_id', 'moved_to', 'moved_status', 'moved_at', 'registered_at'],
            array_keys($all[0])
        );

        $this->assertSame(3, SessionManager::get_users_by_session($sid, null, true));
        $this->assertSame(2, SessionManager::get_users_by_session($sid, Session::STUDENT, true));
        $this->assertSame(0, SessionManager::get_users_by_session($sid, Session::DRH, true));
        $this->assertCount(1, SessionManager::get_users_by_session($sid, Session::SESSION_ADMIN));
        $this->assertSame([], SessionManager::getDrhUsersInSession($sid));

        // Soft deleted users are hidden.
        $this->getEntityManager()->getConnection()->executeStatement('UPDATE user SET active = ? WHERE id = ?', [-2, $zed->getId()]);
        $this->assertSame(1, SessionManager::get_users_by_session($sid, Session::STUDENT, true));
    }

    public function testGetUserSessionReturnsRelationRowOrEmptyArray(): void
    {
        $alice = $this->createUser('sm_alice');
        $sid = $this->newSession('sm_s_usession');
        $this->legacy('subscribeUsersToSession', $sid, [$alice->getId()]);

        $row = SessionManager::getUserSession($alice->getId(), $sid);
        $this->assertSame($sid, (int) $row['session_id']);
        $this->assertSame($alice->getId(), (int) $row['user_id']);
        $this->assertSame(Session::STUDENT, (int) $row['relation_type']);
        $this->assertNotEmpty($row['registered_at']);
        $this->assertNull($row['access_start_date'], 'A session without dates leaves the per-user window NULL.');

        $this->assertSame([], SessionManager::getUserSession($this->createUser('sm_dave')->getId(), $sid));
        $this->assertFalse(SessionManager::getUserSession(0, $sid));
    }

    public function testSessionUserWindowCopiesTheSessionAccessDates(): void
    {
        $alice = $this->createUser('sm_alice');
        $sid = $this->newSession('sm_s_window', ['start' => '2030-01-01 10:00:00', 'end' => '2030-02-01 10:00:00']);
        $session = SessionManager::fetch($sid);
        $this->legacy('subscribeUsersToSession', $sid, [$alice->getId()]);

        $row = SessionManager::getUserSession($alice->getId(), $sid);
        $this->assertSame($session['access_start_date'], $row['access_start_date']);
        $this->assertSame($session['access_end_date'], $row['access_end_date']);
    }

    public function testCourseSessionUserQueries(): void
    {
        $a = $this->createUser('sm_ua');
        $b = $this->createUser('sm_ub');
        $c = $this->createUser('sm_uc');
        [$c1, $code1] = $this->newCourse('sm_cu_one');
        [$c2] = $this->newCourse('sm_cu_two');
        $sid = $this->newSession('sm_s_cu');
        $this->legacy('add_courses_to_session', $sid, [$c1, $c2]);
        $this->legacy('subscribeUsersToSession', $sid, [$a->getId(), $b->getId(), $c->getId()]);

        $info = ['real_id' => $c1];
        $users = array_map('intval', SessionManager::getUsersByCourseSession($sid, $info));
        $this->assertEqualsCanonicalizing([$a->getId(), $b->getId(), $c->getId()], $users);
        $this->assertSame([], SessionManager::getUsersByCourseSession(0, $info));
        $this->assertSame([], SessionManager::getUsersByCourseSession($sid, ['real_id' => 0]));
        $this->assertSame([], SessionManager::getUsersByCourseSession($sid, $info, Session::COURSE_COACH));
        $this->assertCount(3, SessionManager::getUsersByCourseSession($sid, $info, Session::STUDENT));

        // DISTINCT across the two courses.
        $this->assertCount(3, SessionManager::getUsersByCourseAndSessionList([$sid], [$c1, $c2]));
        $this->assertCount(2, SessionManager::getUsersByCourseAndSessionList([$sid], [$c1, $c2], null, 0, 2));
        $this->assertSame([], SessionManager::getUsersByCourseAndSessionList([], [$c1]));

        $total = SessionManager::getTotalUserCoursesInSession($sid, $c1);
        $this->assertCount(3, $total);
        $this->assertSame(['count', 'id', 'status_in_session', 'user_status'], array_keys($total[0]));
        $this->assertSame([], SessionManager::getTotalUserCoursesInSession(0));
        // GROUP BY u.id collapses the two courses to one row per user, count stays 1.. current behaviour:
        $this->assertCount(3, SessionManager::getTotalUserCoursesInSession($sid));

        $this->assertEquals(Session::STUDENT, SessionManager::get_user_status_in_course_session($a->getId(), $c1, $sid));
        $this->assertFalse(SessionManager::get_user_status_in_course_session($a->getId(), $c1, 99999999));

        // Removing users updates the per-course counter, not the session counter.
        $nbrUsersSession = (int) $this->counters($sid)['nbr_users'];
        $this->legacy('removeUsersFromCourseSession', [$a->getId(), $b->getId()], $sid, $info);
        $this->assertCount(1, SessionManager::getUsersByCourseSession($sid, $info));
        $this->assertSame(1, (int) $this->courseCounter($sid, $c1));
        $this->assertSame($nbrUsersSession, (int) $this->counters($sid)['nbr_users']);
        $this->assertTrue(SessionManager::isUserSubscribedAsStudent($sid, $a->getId()), 'Course-level removal keeps the session subscription.');
        $this->assertFalse($this->legacy('removeUsersFromCourseSession', [], $sid, $info));
    }

    public function testSubscribeUsersToSessionCourseAndRemoveNotInList(): void
    {
        $a = $this->createUser('sm_sa');
        $b = $this->createUser('sm_sb');
        [$c1, $code1] = $this->newCourse('sm_susc_one');
        $sid = $this->newSession('sm_s_susc');
        $this->legacy('add_courses_to_session', $sid, [$c1]);

        $this->assertFalse($this->legacy('subscribe_users_to_session_course', [$a->getId()], 0, $code1));
        $this->assertFalse($this->legacy('subscribe_users_to_session_course', [$a->getId()], $sid, 'NO_SUCH_CODE'));

        $this->assertTrue($this->legacy('subscribe_users_to_session_course', [$a->getId(), $b->getId()], $sid, $code1));
        $this->assertCount(2, SessionManager::getUsersByCourseSession($sid, ['real_id' => $c1]));
        // The session itself gets the students too (updateSession=true path).
        $this->assertTrue(SessionManager::isUserSubscribedAsStudent($sid, $a->getId()));

        // A scalar id is accepted; removeUsersNotInList drops everybody else (course status 0 only).
        $c = $this->createUser('sm_sc');
        $this->assertTrue($this->legacy('subscribe_users_to_session_course', $c->getId(), $sid, $code1, SESSION_VISIBLE_READ_ONLY, true));
        $remaining = array_map('intval', SessionManager::getUsersByCourseSession($sid, ['real_id' => $c1]));
        $this->assertSame([$c->getId()], $remaining);
        $this->assertSame(1, (int) $this->courseCounter($sid, $c1), 'Removal recomputes the per-course counter.');
    }

    public function testSubscribingAnAlreadySubscribedUserToASessionCourseFatals(): void
    {
        $a = $this->createUser('sm_dupsub');
        [$c1, $code1] = $this->newCourse('sm_dupsub_course');
        $sid = $this->newSession('sm_s_dupsub');
        $this->legacy('add_courses_to_session', $sid, [$c1]);
        $this->legacy('subscribe_users_to_session_course', [$a->getId()], $sid, $code1);

        // Current behaviour, almost certainly a bug: insertUsersInCourse() chains ->setVisibility()
        // on Session::addUserInCourse(), which returns null for an existing subscription.
        $this->expectException(\Error::class);
        $this->expectExceptionMessage('setVisibility() on null');
        $this->legacy('subscribe_users_to_session_course', [$a->getId()], $sid, $code1);
    }

    // ---------------------------------------------------------------- coaches

    public function testCoachAssignmentOnCourseSession(): void
    {
        // status 1 is not enough: set_coach_to_course_session() checks the ROLE_TEACHER role.
        $coach = $this->createUser('sm_coach', '', '', 'ROLE_TEACHER');
        $coach2 = $this->createUser('sm_coach2', '', '', 'ROLE_TEACHER');
        [$c1, $code1] = $this->newCourse('sm_coach_course');
        $sid = $this->newSession('sm_s_coach');
        $this->legacy('add_courses_to_session', $sid, [$c1]);

        $this->assertFalse($this->legacy('set_coach_to_course_session', $coach->getId(), 0, 0));
        $this->assertTrue($this->legacy('set_coach_to_course_session', $coach->getId(), $sid, $c1));

        $this->assertSame([(string) $coach->getId()], array_map('strval', SessionManager::getCoachesByCourseSession($sid, $c1)));
        $this->assertSame([(string) $coach->getId()], array_map('strval', SessionManager::getCoachesBySession($sid)));
        $this->assertSame(
            1,
            (int) $this->one('SELECT COUNT(*) FROM session_rel_user WHERE session_id = ? AND user_id = ? AND relation_type = ?', [$sid, $coach->getId(), Session::COURSE_COACH]),
            'A course coach is mirrored as a session_rel_user row.'
        );

        // Promoting an existing student of the course only flips the status.
        $this->legacy('subscribe_users_to_session_course', [$coach2->getId()], $sid, $code1);
        $this->assertTrue($this->legacy('set_coach_to_course_session', $coach2->getId(), $sid, $c1));
        $this->assertEqualsCanonicalizing(
            [$coach->getId(), $coach2->getId()],
            array_map('intval', SessionManager::getCoachesByCourseSession($sid, $c1))
        );

        // updateCoaches with deleteCoachesNotInList keeps only the listed ones.
        $this->legacy('updateCoaches', $sid, $c1, [$coach2->getId()], true);
        $this->assertSame([$coach2->getId()], array_map('intval', SessionManager::getCoachesByCourseSession($sid, $c1)));
        $this->assertSame(
            0,
            (int) $this->one('SELECT COUNT(*) FROM session_rel_user WHERE session_id = ? AND user_id = ? AND relation_type = ?', [$sid, $coach->getId(), Session::COURSE_COACH]),
            'Dropping the last coaching of a user also drops the session-level row.'
        );
        $this->assertTrue($this->legacy('set_coach_to_course_session', $coach2->getId(), $sid, $c1, true));
        $this->assertSame([], SessionManager::getCoachesByCourseSession($sid, $c1));
    }

    public function testNonTeacherCannotBeMadeCoach(): void
    {
        // createUser() sets status 1 (teacher status) but no ROLE_TEACHER: coaching is role-based.
        $student = $this->createUser('sm_notteacher');
        [$c1] = $this->newCourse('sm_nt_course');
        $sid = $this->newSession('sm_s_nt');
        $this->legacy('add_courses_to_session', $sid, [$c1]);

        $this->assertFalse($this->legacy('set_coach_to_course_session', $student->getId(), $sid, $c1));
        $this->assertSame([], SessionManager::getCoachesByCourseSession($sid, $c1));
    }

    public function testGeneralCoachListing(): void
    {
        $coach = $this->createUser('sm_gcoach');
        $mine = $this->newSession('sm_gc_b');
        $mine2 = $this->newSession('sm_gc_a');
        $this->newSession('sm_gc_other');
        foreach ([$mine, $mine2] as $id) {
            $this->getEntityManager()->getConnection()->executeStatement(
                'INSERT INTO session_rel_user (session_id, user_id, relation_type, duration, registered_at) VALUES (?, ?, ?, 0, NOW())',
                [$id, $coach->getId(), Session::GENERAL_COACH]
            );
        }

        $rows = SessionManager::get_sessions_by_general_coach($coach->getId());
        $this->assertSame(['sm_gc_a', 'sm_gc_b'], array_column($rows, 'title'), 'Ordered by title.');
        $this->assertSame([], SessionManager::get_sessions_by_general_coach($this->createUser('sm_gc_none')->getId()));

        $all = array_column(SessionManager::get_sessions_by_general_coach(0, true), 'title');
        $this->assertContains('sm_gc_other', $all, 'Platform admin mode returns every session.');
    }

    // ---------------------------------------------------------------- misc updates and counts

    public function testPromotionAndStatusUpdates(): void
    {
        $s1 = $this->newSession('sm_promo_1');
        $s2 = $this->newSession('sm_promo_2');
        $promotionId = 987654;
        $conn = Database::getConnection();
        $conn->executeStatement('SET FOREIGN_KEY_CHECKS = 0');
        try {
            $this->legacy('subscribe_sessions_to_promotion', $promotionId, [$s1, $s2]);
            $rows = SessionManager::get_all_sessions_by_promotion($promotionId);
            $this->assertEqualsCanonicalizing([$s1, $s2], array_map('intval', array_column($rows, 'id')));

            $this->legacy('subscribe_sessions_to_promotion', $promotionId, [$s2]);
            $this->assertSame([$s2], array_map('intval', array_column(SessionManager::get_all_sessions_by_promotion($promotionId), 'id')));
            $this->assertSame(0, (int) $this->one('SELECT promotion_id FROM session WHERE id = ?', [$s1]), 'Dropped sessions are reset to 0, not NULL.');
        } finally {
            $conn->executeStatement('UPDATE session SET promotion_id = NULL WHERE promotion_id IN (?, 0) AND id IN (?, ?)', [$promotionId, $s1, $s2]);
            $conn->executeStatement('SET FOREIGN_KEY_CHECKS = 1');
        }
    }

    public function testSetSessionStatusWritesTheVisibilityColumn(): void
    {
        $id = $this->newSession('sm_status', ['visibility' => 1]);

        $this->legacy('set_session_status', $id, 3);

        // Current (odd) behaviour: the "status" argument lands in session.visibility.
        $this->assertSame(3, (int) $this->one('SELECT visibility FROM session WHERE id = ?', [$id]));
        $this->assertSame(0, (int) $this->one('SELECT status FROM session WHERE id = ?', [$id]));
    }

    public function testDescriptionAndSearchAndParentList(): void
    {
        $id = $this->newSession('sm_search_one');
        // Sessions are all created before the first legacy call: SessionListener caches the AccessUrl
        // entity, which becomes detached once fresh() empties the identity map.
        $other = $this->newSession('sm_search_two');
        $this->legacy('edit_session', $id, 'sm_search_one', '', '', '', '', '', '', [], 0, 1, 'sm_needle in the description');

        $this->assertSame('sm_needle in the description', SessionManager::getDescriptionFromSessionId($id));
        $this->assertSame('', SessionManager::getDescriptionFromSessionId(0));
        $this->assertSame('', SessionManager::getDescriptionFromSessionId(99999999));

        $byTitle = SessionManager::searchSession('sm_search_t');
        $this->assertSame([$other], array_map('intval', array_keys($byTitle)));
        $byDescription = SessionManager::searchSession('sm_needle');
        $this->assertSame([$id], array_map('intval', array_keys($byDescription)));

        $parents = SessionManager::getListOfParentSessions();
        $this->assertSame('sm_search_one', $parents[$id]);
        $order = array_keys($parents);
        $this->assertLessThan(array_search($other, $order, true), array_search($id, $order, true), 'Ordered by title.');
    }

    public function testGetCountAdminCountsSessionsAndSkipsSoftDeletedGeneralCoaches(): void
    {
        // Fixed: the count used to die with an SQL syntax error (" AND u.active <> -2" appended after the
        // derived table, and a join on the removed s.id_coach column). Sessions' general coaches now come
        // from session_rel_user; a session whose coach is soft-deleted is still left out, as intended.
        $before = (int) SessionManager::get_count_admin();

        $this->newSession('sm_count_nocoach');
        $this->assertSame($before + 1, (int) SessionManager::get_count_admin());

        $coach = $this->createUser('sm_count_coach');
        $withCoach = SessionManager::create_session('sm_count_coached', '', '', '', '', '', '', [$coach->getId()], 0, 1, false, null, '');
        $this->assertIsInt($withCoach);
        $this->assertSame($before + 2, (int) SessionManager::get_count_admin());

        Database::getConnection()->executeStatement('UPDATE user SET active = -2 WHERE id = ?', [$coach->getId()]);
        $this->assertSame($before + 1, (int) SessionManager::get_count_admin());

        // The listing of the same sessions agrees with the count when filtered the same way.
        $this->assertSame(
            1,
            (int) SessionManager::get_count_admin(" AND s.title = 'sm_count_nocoach'")
        );
    }

    public function testSessionsForAdminCountAndListScopedToTheSessionsCreated(): void
    {
        $a = $this->newSession('sm_adm_b');
        $b = $this->newSession('sm_adm_a');
        $admin = $this->getAdmin()->getId();

        $count = SessionManager::getSessionsForAdmin($admin, ['where' => [], 'order' => 'title ASC'], true);
        $this->assertGreaterThanOrEqual(2, (int) $count);

        $rows = SessionManager::getSessionsForAdmin($admin, ['order' => 'title ASC', 'limit' => '0,1000']);
        $ids = array_map('intval', array_column($rows, 'id'));
        $this->assertContains($a, $ids);
        $this->assertContains($b, $ids);
        $this->assertLessThan(array_search($a, $ids, true), array_search($b, $ids, true), 'Ordered by title ASC.');
    }

    public function testCountSessionsByAccessUrl(): void
    {
        $url = $this->getAccessUrl()->getId();
        $before = (int) SessionManager::count_sessions($url);
        $beforeAll = (int) SessionManager::count_sessions();

        $this->newSession('sm_count_1');
        $this->newSession('sm_count_2');

        $this->assertSame($before + 2, (int) SessionManager::count_sessions($url));
        $this->assertSame($beforeAll + 2, (int) SessionManager::count_sessions());
    }

    public function testDrhFollowsSessions(): void
    {
        $drh = $this->createUser('sm_drh');
        $s1 = $this->newSession('sm_drh_1');
        $s2 = $this->newSession('sm_drh_2');
        [$c1] = $this->newCourse('sm_drh_course');
        $this->legacy('add_courses_to_session', $s1, [$c1]);
        $info = api_get_user_info($drh->getId());

        $this->assertSame(0, $this->legacy('subscribeSessionsToDrh', [], [$s1]));
        $this->assertSame(2, SessionManager::subscribeSessionsToDrh($info, [$s1, $s2]));
        // Fixed: the follow relations used to be only queued on the entities (nothing reached the database
        // until a caller flushed); they are now persisted by the method itself, like its raw DELETE.
        $this->assertSame(2, (int) $this->one('SELECT COUNT(*) FROM session_rel_user WHERE user_id = ? AND relation_type = ?', [$drh->getId(), Session::DRH]));
        foreach ([$s1, $s2] as $sid) {
            $this->assertSame(
                (int) $this->one('SELECT COUNT(*) FROM session_rel_user WHERE session_id = ?', [$sid]),
                (int) $this->counters($sid)['nbr_users'],
                'nbr_users keeps counting every session_rel_user row.'
            );
        }
        $this->getEntityManager()->clear();

        $this->assertTrue(SessionManager::isSessionFollowedByDrh($s1, $drh->getId()));
        $this->assertFalse(SessionManager::isSessionFollowedByDrh($s1, $this->createUser('sm_notdrh')->getId()));
        $this->assertTrue(SessionManager::isUserSubscribedAsHRM($s1, $drh->getId()));
        $this->assertSame([(int) $drh->getId()], array_map(static fn ($r) => (int) $r['user_id'], SessionManager::getDrhUsersInSession($s1)));

        $followed = SessionManager::getSessionFollowedByDrh($drh->getId(), $s1);
        // SELECT * over session + session_rel_user: the shared "id" column holds the relation id, not the
        // session id (quirk pinned), so the session is identified through session_id.
        $this->assertSame($s1, (int) $followed['session_id']);
        $this->assertNotSame($s1, (int) $followed['id']);
        $this->assertSame([$c1], array_keys($followed['course_list']));
        $this->assertSame([], SessionManager::getSessionFollowedByDrh($drh->getId(), 99999999));

        $ids = SessionManager::get_sessions_followed_by_hrm($drh->getId(), null, null, false, true);
        $this->assertEqualsCanonicalizing([$s1, $s2], array_map('intval', is_array(current($ids)) ? array_column($ids, 'id') : $ids));
        $this->assertSame(2, (int) SessionManager::get_sessions_followed_by_hrm($drh->getId(), null, null, true));

        // Re-subscribing with a shorter list replaces the follow list: the old rows are deleted and the
        // session kept in the list is re-inserted at once (it counts as 1 new relation), no flush needed.
        $this->assertSame(1, SessionManager::subscribeSessionsToDrh($info, [$s1]));
        $this->assertTrue(SessionManager::isSessionFollowedByDrh($s1, $drh->getId()));
        $this->assertSame(1, (int) $this->counters($s1)['nbr_users'] - (int) $this->one('SELECT COUNT(*) FROM session_rel_user WHERE session_id = ? AND relation_type <> ?', [$s1, Session::DRH]));
        $this->assertSame((int) $this->one('SELECT COUNT(*) FROM session_rel_user WHERE session_id = ?', [$s2]), (int) $this->counters($s2)['nbr_users']);
        $this->assertFalse(SessionManager::isSessionFollowedByDrh($s2, $drh->getId()), 'The session left out of the list is no longer followed.');
    }

    // ---------------------------------------------------------------- delete

    public function testDeleteRemovesSessionAndAllRelations(): void
    {
        $alice = $this->createUser('sm_alice');
        [$c1] = $this->newCourse('sm_del_course');
        $sid = $this->newSession('sm_s_del');
        $keep = $this->newSession('sm_s_del_keep');
        $this->legacy('add_courses_to_session', $sid, [$c1]);
        $this->legacy('add_courses_to_session', $keep, [$c1]);
        $this->legacy('subscribeUsersToSession', $sid, [$alice->getId()]);
        $this->legacy('subscribeUsersToSession', $keep, [$alice->getId()]);

        $this->assertFalse($this->legacy('delete', 0));
        $this->assertTrue($this->legacy('delete', $sid, true));

        foreach (['session' => 'id', 'session_rel_course' => 'session_id', 'session_rel_user' => 'session_id', 'session_rel_course_rel_user' => 'session_id', 'access_url_rel_session' => 'session_id'] as $table => $col) {
            $this->assertSame(0, (int) $this->one("SELECT COUNT(*) FROM $table WHERE $col = ?", [$sid]), "$table still has rows of the deleted session");
        }
        $this->assertSame(1, (int) $this->one('SELECT COUNT(*) FROM session_rel_course WHERE session_id = ?', [$keep]), 'Other sessions are untouched.');
        $this->assertSame(1, (int) $this->one('SELECT COUNT(*) FROM session_rel_course_rel_user WHERE session_id = ?', [$keep]));
    }

    public function testDeleteAcceptsAListOfIds(): void
    {
        $a = $this->newSession('sm_del_list_a');
        $b = $this->newSession('sm_del_list_b');

        $this->legacy('delete', [$a, $b], true);

        $this->assertSame(0, (int) $this->one('SELECT COUNT(*) FROM session WHERE id IN (?, ?)', [$a, $b]));
    }
}
