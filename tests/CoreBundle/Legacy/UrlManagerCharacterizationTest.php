<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\Tests\ChamiloTestTrait;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session as HttpSession;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use UrlManager;

/**
 * Characterization tests for UrlManager (urlmanager.lib.php): the multi-URL relations between an
 * access URL and users, courses, sessions, user groups and course categories.
 *
 * Multi-URL is the tenant-isolation mechanism, so what is pinned here is mostly WHICH rows a call
 * returns or touches for WHICH URL. The library itself is unscoped by design (it trusts its
 * caller): the scoping of what an admin may pass in lives in the Admin/AccessUrl*Controller classes
 * through AccessUrlScope. Fixture rows live on throw-away URLs (http://um2-a.test/ ...) so rows
 * that belong to the installed URL 1 never enter an assertion.
 *
 * Fixtures: users 1 (admin) and 2 (anonymous), courses 1 and 2; sessions, groups and categories are
 * raw INSERTs (no resource_node rows, see the other Legacy characterization tests).
 */
final class UrlManagerCharacterizationTest extends KernelTestCase
{
    use ChamiloTestTrait;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        Container::setContainer($container);
        Container::setLegacyServices($container);
        $httpSession = new HttpSession(new MockArraySessionStorage());
        Container::setSession($httpSession);
        $request = Request::create('/main/admin/access_urls.php');
        $request->setSession($httpSession);
        Container::setRequest($request);
    }

    // ------------------------------------------------------------------ url rows

    public function testUrlCountGrowsByOnePerUrlRow(): void
    {
        $before = (int) UrlManager::url_count();
        $this->url('a');
        $this->url('b');

        $this->assertSame($before + 2, (int) UrlManager::url_count());
    }

    public function testGetUrlDataFromIdReturnsTheRowOrFalse(): void
    {
        $id = $this->url('a', 'desc a', 0);

        $row = UrlManager::get_url_data_from_id($id);

        $this->assertSame('http://um2-a.test/', $row['url']);
        $this->assertSame('desc a', $row['description']);
        $this->assertSame('0', (string) $row['active']);
        $this->assertSame((string) $id, (string) $row['id']);
        // fetch_array() returns both numeric and named keys.
        $this->assertSame('http://um2-a.test/', $row[1]);
        $this->assertSame(4, \count($row) / 2);
        $this->assertEmpty(UrlManager::get_url_data_from_id(999999));
    }

    public function testGetUrlIdFindsAnExactUrl(): void
    {
        $id = $this->url('a');

        $this->assertSame((string) $id, (string) UrlManager::get_url_id('http://um2-a.test/'));
        $this->assertEmpty(UrlManager::get_url_id('http://um2-nope.test/'));
    }

    public function testUpdateRewritesTheRowAndStampsTheEditor(): void
    {
        $id = $this->url('a');

        UrlManager::update($id, 'http://um2-b.test/', 'new desc', 0, true);

        $row = $this->conn()->fetchAssociative('SELECT url, description, active, is_login_only, created_by FROM access_url WHERE id = ?', [$id]);
        $this->assertSame('http://um2-b.test/', $row['url']);
        $this->assertSame('new desc', $row['description']);
        $this->assertSame(0, (int) $row['active']);
        $this->assertSame(1, (int) $row['is_login_only']);
        // No user in the test request: api_get_user_id() is 0.
        $this->assertSame(0, (int) $row['created_by']);
    }

    public function testSetUrlStatusLockAndUnlock(): void
    {
        $id = $this->url('a', '', 1);

        UrlManager::set_url_status('lock', $id);
        $this->assertSame(0, (int) $this->conn()->fetchOne('SELECT active FROM access_url WHERE id = ?', [$id]));

        UrlManager::set_url_status('unlock', $id);
        $this->assertSame(1, (int) $this->conn()->fetchOne('SELECT active FROM access_url WHERE id = ?', [$id]));

        // A non numeric id is ignored.
        UrlManager::set_url_status('lock', 'x');
        $this->assertSame(1, (int) $this->conn()->fetchOne('SELECT active FROM access_url WHERE id = ?', [$id]));
    }

    public function testDeleteRemovesTheUrlAndItsRelations(): void
    {
        $a = $this->url('a');
        $b = $this->url('b');
        foreach ([$a, $b] as $u) {
            $this->conn()->executeStatement('INSERT INTO access_url_rel_user (user_id, access_url_id) VALUES (1, ?)', [$u]);
            $this->conn()->executeStatement('INSERT INTO access_url_rel_course (c_id, access_url_id) VALUES (1, ?)', [$u]);
            $this->conn()->executeStatement('INSERT INTO access_url_rel_session (session_id, access_url_id) VALUES (?, ?)', [$this->session('s'.$u), $u]);
            $this->conn()->executeStatement('INSERT INTO access_url_rel_usergroup (usergroup_id, access_url_id) VALUES (?, ?)', [$this->group('g'.$u), $u]);
        }

        $this->assertTrue(UrlManager::delete($a));
        $this->fresh();

        $this->assertSame(0, $this->rows('access_url', 'id', $a));
        foreach (['access_url_rel_user', 'access_url_rel_course', 'access_url_rel_session', 'access_url_rel_usergroup'] as $table) {
            $this->assertSame(0, $this->rows($table, 'access_url_id', $a), $table);
            // The sibling URL keeps its rows.
            $this->assertSame(1, $this->rows($table, 'access_url_id', $b), $table);
        }
        $this->assertSame(1, $this->rows('access_url', 'id', $b));
    }

    // ------------------------------------------------------------------ listings

    public function testGetUrlRelUserDataIsScopedToTheUrl(): void
    {
        $a = $this->url('a');
        $b = $this->url('b');
        $this->link('access_url_rel_user', 'user_id', 1, $a);
        $this->link('access_url_rel_user', 'user_id', 2, $b);

        $rows = UrlManager::get_url_rel_user_data($a);

        $this->assertSame([[1, $a]], array_map(
            static fn (array $r): array => [(int) $r['user_id'], (int) $r['access_url_id']],
            $rows
        ));
        $this->assertSame('admin', $rows[0]['username']);
    }

    public function testGetUrlRelUserDataWithAUrlHidesSoftDeletedUsers(): void
    {
        $a = $this->url('a');
        $this->link('access_url_rel_user', 'user_id', 1, $a);
        $this->link('access_url_rel_user', 'user_id', 3, $a);

        $ids = array_map(static fn (array $r): int => (int) $r['user_id'], UrlManager::get_url_rel_user_data($a));

        // User 3 (fallback_user) is soft-deleted: hidden with or without a url id.
        $this->assertSame([1], $ids);
    }

    public function testGetUrlRelUserDataWithoutUrlListsEveryLinkExceptSoftDeletedUsers(): void
    {
        $a = $this->url('a');
        $this->link('access_url_rel_user', 'user_id', 1, $a);
        $this->link('access_url_rel_user', 'user_id', 3, $a);

        $mine = array_values(array_filter(
            UrlManager::get_url_rel_user_data(),
            static fn (array $r): bool => (int) $r['access_url_id'] === $a
        ));

        // User 3 (fallback_user) is soft-deleted: hidden.
        $this->assertSame([1], array_map(static fn (array $r): int => (int) $r['user_id'], $mine));
    }

    public function testGetUrlRelCourseDataIsScopedAndOrderedByTitle(): void
    {
        $a = $this->url('a');
        $b = $this->url('b');
        $this->link('access_url_rel_course', 'c_id', 2, $a);
        $this->link('access_url_rel_course', 'c_id', 1, $a);
        $this->link('access_url_rel_course', 'c_id', 1, $b);

        $rows = UrlManager::get_url_rel_course_data($a);

        // "AI Act" before "Using Chamilo".
        $this->assertSame([1, 2], array_map(static fn (array $r): int => (int) $r['id'], $rows));
        $this->assertSame([$a, $a], array_map(static fn (array $r): int => (int) $r['access_url_id'], $rows));
        $this->assertSame(['AI Act', 'Using Chamilo'], array_column($rows, 'title'));
        $this->assertCount(1, UrlManager::get_url_rel_course_data($b));
    }

    public function testGetUrlRelSessionDataIsScopedAndOrderedByName(): void
    {
        $a = $this->url('a');
        $b = $this->url('b');
        $s2 = $this->session('um2_s_b');
        $s1 = $this->session('um2_s_a');
        $this->link('access_url_rel_session', 'session_id', $s2, $a);
        $this->link('access_url_rel_session', 'session_id', $s1, $a);
        $this->link('access_url_rel_session', 'session_id', $s1, $b);

        $rows = UrlManager::get_url_rel_session_data($a);

        $this->assertSame([$s1, $s2], array_map(static fn (array $r): int => (int) $r['id'], $rows));
        $this->assertSame(['um2_s_a', 'um2_s_b'], array_column($rows, 'name'));
        $this->assertSame([$a, $a], array_map(static fn (array $r): int => (int) $r['access_url_id'], $rows));
        $this->assertCount(1, UrlManager::get_url_rel_session_data($b));
    }

    public function testGetUrlRelUsergroupDataIsScopedAndOrderedByTitle(): void
    {
        $a = $this->url('a');
        $b = $this->url('b');
        $g2 = $this->group('um2_g_b');
        $g1 = $this->group('um2_g_a');
        $this->link('access_url_rel_usergroup', 'usergroup_id', $g2, $a);
        $this->link('access_url_rel_usergroup', 'usergroup_id', $g1, $a);
        $this->link('access_url_rel_usergroup', 'usergroup_id', $g1, $b);

        $rows = UrlManager::get_url_rel_usergroup_data($a);

        $this->assertSame(['um2_g_a', 'um2_g_b'], array_column($rows, 'title'));
        $this->assertCount(1, UrlManager::get_url_rel_usergroup_data($b));
    }

    public function testGetUrlRelCourseCategoryListsOnlyRootCategories(): void
    {
        $a = $this->url('a');
        $root = $this->category('um2_root');
        $child = $this->category('um2_child', $root);
        $this->link('access_url_rel_course_category', 'course_category_id', $root, $a);
        $this->link('access_url_rel_course_category', 'course_category_id', $child, $a);

        $rows = UrlManager::getUrlRelCourseCategory($a);

        // The child is linked but is not listed (parent_id IS NULL filter).
        $this->assertSame([$root], array_map(static fn (array $r): int => (int) $r['id'], $rows));
        $this->assertSame('um2_root', $rows[0]['title']);
    }

    // ------------------------------------------------------------------ existence checks and counts

    public function testRelationExistChecksReturnTheRowCountForThatUrlOnly(): void
    {
        $a = $this->url('a');
        $b = $this->url('b');
        $session = $this->session('um2_s');
        $group = $this->group('um2_g');
        $category = $this->category('um2_c');
        $this->link('access_url_rel_user', 'user_id', 1, $a);
        $this->link('access_url_rel_course', 'c_id', 1, $a);
        $this->link('access_url_rel_session', 'session_id', $session, $a);
        $this->link('access_url_rel_usergroup', 'usergroup_id', $group, $a);
        $this->link('access_url_rel_course_category', 'course_category_id', $category, $a);

        $this->assertSame(1, UrlManager::relation_url_user_exist(1, $a));
        $this->assertSame(0, UrlManager::relation_url_user_exist(1, $b));
        $this->assertSame(1, UrlManager::relation_url_course_exist(1, $a));
        $this->assertSame(0, UrlManager::relation_url_course_exist(2, $a));
        $this->assertSame(1, UrlManager::relation_url_session_exist($session, $a));
        $this->assertSame(0, UrlManager::relation_url_session_exist($session, $b));
        $this->assertSame(1, UrlManager::relationUrlUsergroupExist($group, $a));
        $this->assertSame(0, UrlManager::relationUrlUsergroupExist($group, $b));
        $this->assertSame(1, UrlManager::relationUrlCourseCategoryExist($category, $a));
        $this->assertSame(0, UrlManager::relationUrlCourseCategoryExist($category, $b));
    }

    public function testCountsOfUrlsPerCourse(): void
    {
        $a = $this->url('a');
        $b = $this->url('b');
        $base = UrlManager::getCountUrlRelCourse(2);
        $this->link('access_url_rel_course', 'c_id', 2, $a);
        $this->link('access_url_rel_course', 'c_id', 2, $b);

        $this->assertSame($base + 2, UrlManager::getCountUrlRelCourse(2));
        $this->assertSame($base + 2, UrlManager::getCountAccessUrlFromCourse(2));
        $this->assertSame(0, UrlManager::getCountUrlRelCourse(999999));
        $this->assertSame(0, UrlManager::getCountAccessUrlFromCourse(999999));
    }

    // ------------------------------------------------------------------ "urls of X"

    public function testGetAccessUrlFromUserCourseAndSession(): void
    {
        $a = $this->url('a');
        $b = $this->url('b');
        $session = $this->session('um2_s');
        $this->link('access_url_rel_user', 'user_id', 2, $a);
        $this->link('access_url_rel_user', 'user_id', 2, $b);
        $this->link('access_url_rel_course', 'c_id', 2, $a);
        $this->link('access_url_rel_session', 'session_id', $session, $b);

        $users = array_filter(
            UrlManager::get_access_url_from_user(2),
            static fn (array $r): bool => str_starts_with($r['url'], 'http://um2-')
        );
        $this->assertEqualsCanonicalizing(
            [['http://um2-a.test/', $a], ['http://um2-b.test/', $b]],
            array_map(static fn (array $r): array => [$r['url'], (int) $r['access_url_id']], array_values($users))
        );
        $this->assertArrayNotHasKey(0, $users[array_key_first($users)], 'ASSOC rows only');

        $courses = array_values(array_filter(
            UrlManager::get_access_url_from_course(2),
            static fn (array $r): bool => str_starts_with($r['url'], 'http://um2-')
        ));
        $this->assertSame([['http://um2-a.test/', $a]], array_map(static fn (array $r): array => [$r['url'], (int) $r['access_url_id']], $courses));

        $sessions = UrlManager::get_access_url_from_session($session);
        $this->assertCount(1, $sessions);
        $this->assertSame('http://um2-b.test/', $sessions[0]['url']);
        $this->assertSame([], UrlManager::get_access_url_from_session(999999));
    }

    // ------------------------------------------------------------------ users

    public function testAddUserToUrlIsIdempotentAndDefaultsToUrlOne(): void
    {
        $a = $this->url('a');

        $this->assertTrue((bool) UrlManager::add_user_to_url(2, $a));
        $this->assertTrue((bool) UrlManager::add_user_to_url(2, $a));
        $this->assertSame(1, $this->countPair('access_url_rel_user', 'user_id', 2, $a));

        // Empty url id means URL 1 (and URL 1 already has user 2: nothing added).
        $before = $this->countPair('access_url_rel_user', 'user_id', 2, 1);
        UrlManager::add_user_to_url(2, 0);
        $this->assertSame($before, $this->countPair('access_url_rel_user', 'user_id', 2, 1));
    }

    public function testAddUserToUrlCopiesTheFirstAuthSourceToTheNewUrl(): void
    {
        $a = $this->url('a');
        $this->conn()->executeStatement("INSERT INTO user_auth_source (authentication, url_id, user_id) VALUES ('platform', 1, 2)");

        UrlManager::add_user_to_url(2, $a, true);
        $this->fresh();

        $this->assertSame(1, (int) $this->conn()->fetchOne(
            "SELECT COUNT(*) FROM user_auth_source WHERE user_id = 2 AND url_id = ? AND authentication = 'platform'",
            [$a]
        ));

        // A second call does not duplicate it.
        UrlManager::add_user_to_url(2, $a, true);
        $this->fresh();
        $this->assertSame(1, (int) $this->conn()->fetchOne('SELECT COUNT(*) FROM user_auth_source WHERE user_id = 2 AND url_id = ?', [$a]));
    }

    public function testAddUsersToUrlsIsACrossProduct(): void
    {
        $a = $this->url('a');
        $b = $this->url('b');

        $result = UrlManager::add_users_to_urls([1, 2], [$a, $b]);

        $this->assertSame([$a => [1 => 1, 2 => 1], $b => [1 => 1, 2 => 1]], $result);
        $this->assertSame(1, $this->countPair('access_url_rel_user', 'user_id', 1, $b));
        $this->assertSame([], UrlManager::add_users_to_urls('x', [$a]));
    }

    public function testRemoveUsersFromUrlsTouchesOnlyThePairsRequested(): void
    {
        $a = $this->url('a');
        $b = $this->url('b');
        UrlManager::add_users_to_urls([1, 2], [$a, $b]);

        UrlManager::remove_users_from_urls([1], [$a]);

        $this->assertSame(0, $this->countPair('access_url_rel_user', 'user_id', 1, $a));
        $this->assertSame(1, $this->countPair('access_url_rel_user', 'user_id', 2, $a));
        $this->assertSame(1, $this->countPair('access_url_rel_user', 'user_id', 1, $b));
        // Empty lists are a no-op, never "delete everything".
        UrlManager::remove_users_from_urls([], [$a]);
        UrlManager::remove_users_from_urls([2], []);
        $this->assertSame(1, $this->countPair('access_url_rel_user', 'user_id', 2, $a));
    }

    public function testDeleteUrlRelUserAndDeleteUserFromAllUrls(): void
    {
        $a = $this->url('a');
        $b = $this->url('b');
        UrlManager::add_users_to_urls([2], [$a, $b]);

        UrlManager::delete_url_rel_user(2, $a);
        $this->assertSame(0, $this->countPair('access_url_rel_user', 'user_id', 2, $a));
        $this->assertSame(1, $this->countPair('access_url_rel_user', 'user_id', 2, $b));

        // A missing id is refused (returns true, deletes nothing).
        $this->assertTrue(UrlManager::delete_url_rel_user(0, $b));
        $this->assertTrue(UrlManager::delete_url_rel_user(2, 0));
        $this->assertSame(1, $this->countPair('access_url_rel_user', 'user_id', 2, $b));

        UrlManager::deleteUserFromAllUrls(2);
        $this->assertSame(0, (int) $this->conn()->fetchOne('SELECT COUNT(*) FROM access_url_rel_user WHERE user_id = 2'));
        // Other users are untouched.
        $this->assertGreaterThan(0, (int) $this->conn()->fetchOne('SELECT COUNT(*) FROM access_url_rel_user WHERE user_id = 1'));
    }

    public function testUpdateUrlsRelUserSyncsTheListAndReportsTheDiff(): void
    {
        $a = $this->url('a');
        $b = $this->url('b');
        UrlManager::add_users_to_urls([1], [$a, $b]);

        $result = UrlManager::update_urls_rel_user([2], $a);

        $this->assertSame(['users_added' => [2], 'users_deleted' => [1]], $result);
        $this->assertSame(1, $this->countPair('access_url_rel_user', 'user_id', 2, $a));
        $this->assertSame(0, $this->countPair('access_url_rel_user', 'user_id', 1, $a));
        // The other URL is not touched by the sync.
        $this->assertSame(1, $this->countPair('access_url_rel_user', 'user_id', 1, $b));
        // Nothing to do: false, not an empty diff.
        $this->assertFalse(UrlManager::update_urls_rel_user([2], $a));
    }

    // ------------------------------------------------------------------ courses

    public function testAddCoursesToUrlsOnlyReportsTheNewPairs(): void
    {
        $a = $this->url('a');
        $b = $this->url('b');
        UrlManager::add_course_to_url(1, $a);

        $result = UrlManager::add_courses_to_urls(['AIACT', 'USINGCHAMILO'], [$a, $b]);

        // PINNED QUIRK: a pair that already existed is simply absent from the result array.
        $this->assertSame([$a => ['USINGCHAMILO' => 1], $b => ['AIACT' => 1, 'USINGCHAMILO' => 1]], $result);
        $this->assertSame(1, $this->countPair('access_url_rel_course', 'c_id', 1, $a));
        $this->assertSame(1, $this->countPair('access_url_rel_course', 'c_id', 2, $b));
    }

    public function testAddCourseToUrlIsIdempotentAndDefaultsToUrlOne(): void
    {
        $a = $this->url('a');

        $this->assertTrue(UrlManager::add_course_to_url(2, $a));
        $this->assertTrue(UrlManager::add_course_to_url(2, $a));
        $this->assertSame(1, $this->countPair('access_url_rel_course', 'c_id', 2, $a));

        $before = $this->countPair('access_url_rel_course', 'c_id', 2, 1);
        UrlManager::add_course_to_url(2, 0);
        $this->assertSame($before, $this->countPair('access_url_rel_course', 'c_id', 2, 1));
    }

    public function testRemoveCoursesFromUrlsResolvesCodesAndKeepsOtherPairs(): void
    {
        $a = $this->url('a');
        $b = $this->url('b');
        UrlManager::add_courses_to_urls(['AIACT', 'USINGCHAMILO'], [$a, $b]);

        UrlManager::remove_courses_from_urls(['AIACT'], [$a]);

        $this->assertSame(0, $this->countPair('access_url_rel_course', 'c_id', 1, $a));
        $this->assertSame(1, $this->countPair('access_url_rel_course', 'c_id', 2, $a));
        $this->assertSame(1, $this->countPair('access_url_rel_course', 'c_id', 1, $b));
        UrlManager::remove_courses_from_urls([], [$a]);
        UrlManager::remove_courses_from_urls(['USINGCHAMILO'], []);
        $this->assertSame(1, $this->countPair('access_url_rel_course', 'c_id', 2, $a));
    }

    public function testDeleteUrlRelCourseIsScopedToTheUrl(): void
    {
        $a = $this->url('a');
        $b = $this->url('b');
        UrlManager::add_course_to_url(2, $a);
        UrlManager::add_course_to_url(2, $b);

        UrlManager::delete_url_rel_course(2, $a);

        $this->assertSame(0, $this->countPair('access_url_rel_course', 'c_id', 2, $a));
        $this->assertSame(1, $this->countPair('access_url_rel_course', 'c_id', 2, $b));
    }

    public function testUpdateUrlsRelCourseSyncsTheList(): void
    {
        $a = $this->url('a');
        $b = $this->url('b');
        UrlManager::add_course_to_url(1, $a);
        UrlManager::add_course_to_url(1, $b);

        UrlManager::update_urls_rel_course([2], $a);

        $this->assertSame(0, $this->countPair('access_url_rel_course', 'c_id', 1, $a));
        $this->assertSame(1, $this->countPair('access_url_rel_course', 'c_id', 2, $a));
        $this->assertSame(1, $this->countPair('access_url_rel_course', 'c_id', 1, $b));
    }

    public function testSearchCoursesByTitleOrCodeIsAPrefixMatchLimitedToEleven(): void
    {
        $rows = UrlManager::searchCoursesByTitleOrCode('Using');
        $this->assertSame(['USINGCHAMILO'], array_column($rows, 'code'));

        // Code prefix matches too, and case does not matter (collation).
        $this->assertSame(['AIACT'], array_column(UrlManager::searchCoursesByTitleOrCode('aia'), 'code'));
        // Prefix only: a middle fragment does not match.
        $this->assertSame([], UrlManager::searchCoursesByTitleOrCode('Chamilo'));
    }

    // ------------------------------------------------------------------ sessions

    public function testAddSessionToUrl(): void
    {
        $a = $this->url('a');
        $s = $this->session('um2_s');

        $this->assertTrue(UrlManager::add_session_to_url($s, $a));
        // Already linked: false.
        $this->assertFalse(UrlManager::add_session_to_url($s, $a));
        // No session id: false.
        $this->assertFalse(UrlManager::add_session_to_url(0, $a));
        $this->assertSame(1, $this->countPair('access_url_rel_session', 'session_id', $s, $a));
    }

    public function testAddSessionsToUrlsOnlyReportsTheNewPairs(): void
    {
        $a = $this->url('a');
        $b = $this->url('b');
        $s = $this->session('um2_s');
        UrlManager::add_session_to_url($s, $a);

        $result = UrlManager::add_sessions_to_urls([$s], [$a, $b]);

        $this->assertSame([$b => [$s => 1]], $result);
    }

    public function testDeleteUrlRelSessionIsScopedToTheUrl(): void
    {
        $a = $this->url('a');
        $b = $this->url('b');
        $s = $this->session('um2_s');
        UrlManager::add_sessions_to_urls([$s], [$a, $b]);

        UrlManager::delete_url_rel_session($s, $a);

        $this->assertSame(0, $this->countPair('access_url_rel_session', 'session_id', $s, $a));
        $this->assertSame(1, $this->countPair('access_url_rel_session', 'session_id', $s, $b));
    }

    public function testUpdateUrlsRelSessionSyncsTheList(): void
    {
        $a = $this->url('a');
        $b = $this->url('b');
        $s1 = $this->session('um2_s1');
        $s2 = $this->session('um2_s2');
        UrlManager::add_sessions_to_urls([$s1], [$a, $b]);

        UrlManager::update_urls_rel_session([$s2], $a);

        $this->assertSame(0, $this->countPair('access_url_rel_session', 'session_id', $s1, $a));
        $this->assertSame(1, $this->countPair('access_url_rel_session', 'session_id', $s2, $a));
        $this->assertSame(1, $this->countPair('access_url_rel_session', 'session_id', $s1, $b));
    }

    // ------------------------------------------------------------------ user groups

    public function testUserGroupsAddRemoveAndListFlow(): void
    {
        $a = $this->url('a');
        $b = $this->url('b');
        $g1 = $this->group('um2_g1');
        $g2 = $this->group('um2_g2');

        $id = UrlManager::addUserGroupToUrl($g1, $a);
        $this->assertGreaterThan(0, $id);

        $result = UrlManager::addUserGroupListToUrl([$g1, $g2], [$a, $b]);
        // PINNED QUIRK: pairs that already existed are absent from the result.
        $this->assertSame([$a => [$g2 => 1], $b => [$g1 => 1, $g2 => 1]], $result);

        UrlManager::removeUserGroupListFromUrl([$g1], [$a, $b]);
        $this->assertSame(0, $this->countPair('access_url_rel_usergroup', 'usergroup_id', $g1, $a));
        $this->assertSame(0, $this->countPair('access_url_rel_usergroup', 'usergroup_id', $g1, $b));
        $this->assertSame(1, $this->countPair('access_url_rel_usergroup', 'usergroup_id', $g2, $a));
        UrlManager::removeUserGroupListFromUrl([], [$a]);
        $this->assertSame(1, $this->countPair('access_url_rel_usergroup', 'usergroup_id', $g2, $a));
    }

    public function testDeleteUrlRelUsergroupIsScopedToTheUrl(): void
    {
        $a = $this->url('a');
        $b = $this->url('b');
        $g = $this->group('um2_g');
        UrlManager::addUserGroupListToUrl([$g], [$a, $b]);

        UrlManager::delete_url_rel_usergroup($g, $a);

        $this->assertSame(0, $this->countPair('access_url_rel_usergroup', 'usergroup_id', $g, $a));
        $this->assertSame(1, $this->countPair('access_url_rel_usergroup', 'usergroup_id', $g, $b));
    }

    public function testUpdateUrlsRelUsergroupSyncsTheListButNeverEmptiesIt(): void
    {
        $a = $this->url('a');
        $b = $this->url('b');
        $g1 = $this->group('um2_g1');
        $g2 = $this->group('um2_g2');
        UrlManager::addUserGroupListToUrl([$g1], [$a, $b]);

        UrlManager::update_urls_rel_usergroup([$g2], $a);
        $this->assertSame(0, $this->countPair('access_url_rel_usergroup', 'usergroup_id', $g1, $a));
        $this->assertSame(1, $this->countPair('access_url_rel_usergroup', 'usergroup_id', $g2, $a));
        $this->assertSame(1, $this->countPair('access_url_rel_usergroup', 'usergroup_id', $g1, $b));

        // PINNED QUIRK: an empty list returns early, so the last group of a URL cannot be unassigned
        // through this method (users/courses/sessions sync to an empty list fine).
        UrlManager::update_urls_rel_usergroup([], $a);
        $this->assertSame(1, $this->countPair('access_url_rel_usergroup', 'usergroup_id', $g2, $a));
        // Invalid url id: no-op.
        UrlManager::update_urls_rel_usergroup([$g1], 0);
        $this->assertSame(0, $this->countPair('access_url_rel_usergroup', 'usergroup_id', $g1, 0));
    }

    // ------------------------------------------------------------------ course categories

    public function testAddCourseCategoryToUrlReturnsTheIdOrZeroWhenAlreadyLinked(): void
    {
        $a = $this->url('a');
        $c = $this->category('um2_c');

        $this->assertGreaterThan(0, UrlManager::addCourseCategoryToUrl($c, $a));
        $this->assertSame(0, UrlManager::addCourseCategoryToUrl($c, $a));
        $this->assertSame(1, $this->countPair('access_url_rel_course_category', 'course_category_id', $c, $a));
    }

    public function testAddCourseCategoryListToUrlOnlyReportsTheNewPairs(): void
    {
        $a = $this->url('a');
        $b = $this->url('b');
        $c = $this->category('um2_c');
        UrlManager::addCourseCategoryToUrl($c, $a);

        $this->assertSame([$b => [$c => 1]], UrlManager::addCourseCategoryListToUrl([$c], [$a, $b]));
    }

    public function testDeleteUrlRelCourseCategoryIsScopedToTheUrl(): void
    {
        $a = $this->url('a');
        $b = $this->url('b');
        $c = $this->category('um2_c');
        UrlManager::addCourseCategoryListToUrl([$c], [$a, $b]);

        UrlManager::deleteUrlRelCourseCategory($c, $a);

        $this->assertSame(0, $this->countPair('access_url_rel_course_category', 'course_category_id', $c, $a));
        $this->assertSame(1, $this->countPair('access_url_rel_course_category', 'course_category_id', $c, $b));
    }

    public function testUpdateUrlRelCourseCategoryCascadesToDescendants(): void
    {
        $a = $this->url('a');
        $b = $this->url('b');
        $root = $this->category('um2_root');
        $child = $this->category('um2_child', $root);
        $grandChild = $this->category('um2_grand', $child);
        $other = $this->category('um2_other');
        UrlManager::addCourseCategoryListToUrl([$other], [$a, $b]);

        UrlManager::updateUrlRelCourseCategory([$root], $a);

        $this->assertSame(1, $this->countPair('access_url_rel_course_category', 'course_category_id', $root, $a));
        // Selecting a root links its whole subtree too.
        $this->assertSame(1, $this->countPair('access_url_rel_course_category', 'course_category_id', $child, $a));
        $this->assertSame(1, $this->countPair('access_url_rel_course_category', 'course_category_id', $grandChild, $a));
        $this->assertSame(0, $this->countPair('access_url_rel_course_category', 'course_category_id', $other, $a));
        $this->assertSame(1, $this->countPair('access_url_rel_course_category', 'course_category_id', $other, $b));

        // Dropping the root drops the subtree too.
        UrlManager::updateUrlRelCourseCategory([], $a);
        foreach ([$root, $child, $grandChild] as $id) {
            $this->assertSame(0, $this->countPair('access_url_rel_course_category', 'course_category_id', $id, $a));
        }
        $this->assertSame(1, $this->countPair('access_url_rel_course_category', 'course_category_id', $other, $b));
    }

    // ------------------------------------------------------------------ helpers

    private function conn(): Connection
    {
        return $this->getEntityManager()->getConnection();
    }

    /** Legacy code runs with an empty identity map on every request. */
    private function fresh(): void
    {
        $this->getEntityManager()->clear();
    }

    private function url(string $suffix, string $description = 'um2', int $active = 1): int
    {
        $this->conn()->executeStatement(
            'INSERT INTO access_url (url, description, active, created_by, lft, lvl, rgt, is_login_only)
             VALUES (?, ?, ?, 1, 1, 0, 2, 0)',
            ['http://um2-'.$suffix.'.test/', $description, $active]
        );

        return (int) $this->conn()->lastInsertId();
    }

    private function session(string $name): int
    {
        $this->conn()->executeStatement(
            'INSERT INTO session (title, nbr_courses, nbr_users, nbr_classes, visibility, status, position)
             VALUES (?, 0, 0, 0, 1, 0, 0)',
            [$name]
        );

        return (int) $this->conn()->lastInsertId();
    }

    private function group(string $title): int
    {
        $this->conn()->executeStatement(
            "INSERT INTO usergroup (title, group_type, visibility, allow_members_leave_group, created_at, updated_at)
             VALUES (?, 0, '1', 1, '2030-01-01 00:00:00', '2030-01-01 00:00:00')",
            [$title]
        );

        return (int) $this->conn()->lastInsertId();
    }

    private function category(string $title, ?int $parent = null): int
    {
        $this->conn()->executeStatement(
            'INSERT INTO course_category (parent_id, title, code, tree_pos, children_count) VALUES (?, ?, ?, 0, 0)',
            [$parent, $title, strtoupper($title)]
        );

        return (int) $this->conn()->lastInsertId();
    }

    private function link(string $table, string $column, int $id, int $urlId): void
    {
        $this->conn()->executeStatement("INSERT INTO $table ($column, access_url_id) VALUES (?, ?)", [$id, $urlId]);
    }

    private function rows(string $table, string $column, int $id): int
    {
        return (int) $this->conn()->fetchOne("SELECT COUNT(*) FROM $table WHERE $column = ?", [$id]);
    }

    private function countPair(string $table, string $column, int $id, int $urlId): int
    {
        return (int) $this->conn()->fetchOne("SELECT COUNT(*) FROM $table WHERE $column = ? AND access_url_id = ?", [$id, $urlId]);
    }
}
