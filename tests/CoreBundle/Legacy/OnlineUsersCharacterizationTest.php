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
use Symfony\Component\Uid\UuidV4;

/**
 * Characterization of the "who is online" library (public/main/inc/lib/online.inc.php).
 *
 * LIVE (called from OnlineController, ajax/online.ajax.php, banner.lib.php, api.lib.php):
 * who_is_online, who_is_online_count, who_is_online_in_this_course, who_is_online_in_this_course_count,
 * whoIsOnlineInThisSessionCount, user_is_online. DEAD (no caller anywhere, marked @deprecated in the
 * source): LoginCheck, preventMultipleLogin.
 *
 * What is pinned is WHICH users are listed: the time window, soft-deleted (status -2) and anonymous
 * accounts hidden, the course/session scoping and, in multi-URL mode, the URL scoping. Presence rows
 * are written with fixed offsets from "now" (the library reads the clock), the users are raw INSERTs
 * (prefix ou_) plus the fixture accounts: 1 admin, 2 anonymous, 3 soft-deleted.
 */
final class OnlineUsersCharacterizationTest extends KernelTestCase
{
    use ChamiloTestTrait;

    private Connection $conn;
    private int $seq = 0;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        Container::setContainer($container);
        Container::setLegacyServices($container);
        $httpSession = new HttpSession(new MockArraySessionStorage());
        Container::setSession($httpSession);
        $request = Request::create('/main/inc/ajax/online.ajax.php');
        $request->setSession($httpSession);
        Container::setRequest($request);
        $this->conn = $container->get('doctrine')->getConnection();
        \ChamiloSession::erase('_user');
        // A clean slate for the presence table: DAMA rolls this back.
        $this->conn->executeStatement('DELETE FROM track_e_online');
    }

    protected function tearDown(): void
    {
        \ChamiloSession::erase('_user');
        self::getContainer()->get('security.token_storage')->setToken(null);
        parent::tearDown();
    }

    // ---------------------------------------------------------------- fixtures

    private function user(string $name, int $status = 5, int $active = 1, string $picture = ''): int
    {
        $name = 'ou_'.$name.'_'.(++$this->seq).'_'.substr(md5((string) microtime(true)), 0, 6);
        $this->conn->executeStatement(
            "INSERT INTO user (username, username_canonical, locale, password, timezone, email, email_canonical,
                locked, expired, credentials_expired, salt, roles, status, active, uuid, created_at, updated_at,
                firstname, lastname, picture_uri)
             VALUES (?, ?, 'en_US', 'x', 'UTC', ?, ?, 0, 0, 0, 'x', '[]', ?, ?, ?,
                '2001-01-01 00:00:00', '2001-01-01 00:00:00', ?, 'ou', ?)",
            [$name, $name, $name.'@example.com', $name.'@example.com', $status, $active, UuidV4::v4()->toBinary(), $name, $picture]
        );

        return (int) $this->conn->lastInsertId();
    }

    /** Presence row seen $minutesAgo minutes ago (UTC, as the library compares). */
    private function online(int $userId, int $minutesAgo, int $courseId = 0, int $sessionId = 0, ?int $urlId = null): void
    {
        $this->conn->executeStatement(
            'INSERT INTO track_e_online (login_user_id, login_date, user_ip, c_id, session_id, access_url_id)
             VALUES (?, ?, ?, ?, ?, ?)',
            [
                $userId,
                gmdate('Y-m-d H:i:s', time() - $minutesAgo * 60),
                '127.0.0.1',
                $courseId,
                $sessionId,
                $urlId ?? 1,
            ]
        );
    }

    /** A second access URL switches the platform to multi-URL mode (AccessUrlHelper::isMultiple()). */
    private function secondUrl(): int
    {
        $this->conn->executeStatement(
            "INSERT INTO access_url (url, description, active, created_by, lft, lvl, rgt, is_login_only)
             VALUES ('http://ou-other.test/', 'ou', 1, 1, 1, 0, 2, 0)"
        );

        return (int) $this->conn->lastInsertId();
    }

    private function friends(int $userId, int $friendId): void
    {
        $this->conn->executeStatement(
            'INSERT INTO user_rel_user (user_id, friend_user_id, relation_type, created_at, updated_at)
             VALUES (?, ?, 3, ?, ?)',
            [$userId, $friendId, '2001-01-01 00:00:00', '2001-01-01 00:00:00']
        );
    }

    private function actAs(int $userId): void
    {
        \ChamiloSession::write('_user', ['id' => $userId]);
    }

    /** @return int[] */
    private function ids(array|false $rows): array
    {
        $this->assertIsArray($rows);

        return array_map('intval', $rows);
    }

    // ---------------------------------------------------------------- who_is_online

    public function testWhoIsOnlineListsOnlyUsersSeenInsideTheTimeWindow(): void
    {
        $recent = $this->user('recent');
        $old = $this->user('old');
        $this->online($recent, 5);
        $this->online($old, 300);

        $this->assertSame([$recent], $this->ids(who_is_online(0, 50)));
    }

    public function testWhoIsOnlineHidesSoftDeletedAndAnonymousAccounts(): void
    {
        $normal = $this->user('normal');
        $softDeleted = $this->user('gone', 5, -2);
        $anonymous = $this->user('anon', 6);
        $inactive = $this->user('inactive', 5, 0);
        foreach ([$normal, $softDeleted, $anonymous, $inactive] as $id) {
            $this->online($id, 1);
        }

        $listed = $this->ids(who_is_online(0, 50));

        // PINNED: an INACTIVE (active = 0) account is still listed, only -2 is hidden.
        $this->assertEqualsCanonicalizing([$normal, $inactive], $listed);
        $this->assertNotContains($softDeleted, $listed);
        $this->assertNotContains($anonymous, $listed);
    }

    public function testWhoIsOnlineHidesTheFixtureAnonymousAndSoftDeletedAccounts(): void
    {
        $this->online(1, 2);
        $this->online(2, 2);
        $this->online(3, 2);

        $this->assertSame([1], $this->ids(who_is_online(0, 50)));
    }

    public function testWhoIsOnlineReturnsAnEmptyArrayNotFalseWhenNobodyIsOnline(): void
    {
        $this->assertSame([], who_is_online(0, 50));
    }

    public function testWhoIsOnlineHonoursAnExplicitTimeLimitInMinutes(): void
    {
        $a = $this->user('a');
        $b = $this->user('b');
        $this->online($a, 5);
        $this->online($b, 20);

        $this->assertSame([$a], $this->ids(who_is_online(0, 50, 'login_user_id', 'asc', 10)));
        $this->assertEqualsCanonicalizing([$a, $b], $this->ids(who_is_online(0, 50, 'login_user_id', 'asc', 25)));
    }

    public function testWhoIsOnlineOrdersByTheRequestedColumnAndDirection(): void
    {
        $a = $this->user('a');
        $b = $this->user('b');
        $c = $this->user('c');
        $this->online($a, 3);
        $this->online($b, 1);
        $this->online($c, 2);

        $this->assertSame([$b, $c, $a], $this->ids(who_is_online(0, 50, 'login_date', 'desc')));
        $this->assertSame([$a, $c, $b], $this->ids(who_is_online(0, 50, 'login_date', 'asc')));
        // PINNED: an unknown direction falls back to DESC (the value never reaches the SQL).
        $this->assertSame([$b, $c, $a], $this->ids(who_is_online(0, 50, 'login_date', 'sideways')));
        // Direction is case-insensitive.
        $this->assertSame([$a, $c, $b], $this->ids(who_is_online(0, 50, 'login_date', 'ASC')));
    }

    public function testWhoIsOnlineDefaultOrderIsTheUserPictureDescending(): void
    {
        $a = $this->user('a', 5, 1, 'a.png');
        $b = $this->user('b', 5, 1, 'c.png');
        $c = $this->user('c', 5, 1, 'b.png');
        foreach ([$a, $b, $c] as $id) {
            $this->online($id, 1);
        }

        $this->assertSame([$b, $c, $a], $this->ids(who_is_online(0, 50)));
    }

    public function testWhoIsOnlinePaginatesWithFromAndNumberOfItems(): void
    {
        $ids = [];
        foreach (['a', 'b', 'c', 'd'] as $i => $n) {
            $ids[$n] = $this->user($n);
            $this->online($ids[$n], 4 - $i);
        }

        // login_date ascending: a (oldest), b, c, d.
        $this->assertSame([$ids['b'], $ids['c']], $this->ids(who_is_online(1, 2, 'login_date', 'asc')));
        $this->assertSame([$ids['d']], $this->ids(who_is_online(3, 5, 'login_date', 'asc')));
        $this->assertSame([], who_is_online(10, 5, 'login_date', 'asc'));
    }

    public function testWhoIsOnlineFriendsModeListsOnlyTheFriendsOfTheCurrentUser(): void
    {
        $me = $this->user('me');
        $friend = $this->user('friend');
        $stranger = $this->user('stranger');
        $reverseOnly = $this->user('reverse');
        $this->friends($me, $friend);
        $this->friends($reverseOnly, $me);
        foreach ([$me, $friend, $stranger, $reverseOnly] as $id) {
            $this->online($id, 1);
        }
        $this->actAs($me);

        // The relation is directional: only user_id = me counts.
        $this->assertSame([$friend], $this->ids(who_is_online(0, 50, null, null, null, true)));
    }

    public function testWhoIsOnlineFriendsModeDoesNotFilterSoftDeletedFriends(): void
    {
        $me = $this->user('me');
        $gone = $this->user('gone', 5, -2);
        $this->friends($me, $gone);
        $this->online($gone, 1);
        $this->actAs($me);

        // PINNED QUIRK: the friends branch has no u.active <> -2 / status filter (no join on user at all),
        // unlike the "all users" branch. A soft-deleted friend with a fresh presence row is listed.
        $this->assertSame([$gone], $this->ids(who_is_online(0, 50, null, null, null, true)));
    }

    public function testWhoIsOnlineInMultiUrlModeIsScopedToTheCurrentUrl(): void
    {
        $other = $this->secondUrl();
        $current = api_get_current_access_url_id();
        $here = $this->user('here');
        $there = $this->user('there');
        $this->online($here, 1, 0, 0, $current);
        $this->online($there, 1, 0, 0, $other);

        $this->assertSame([$here], $this->ids(who_is_online(0, 50)));
    }

    public function testWhoIsOnlineInSingleUrlModeIgnoresTheUrlColumn(): void
    {
        $a = $this->user('a');
        $this->online($a, 1, 0, 0, 424242);

        // PINNED: without multi-URL, the access_url_id of the presence row is not looked at.
        $this->assertSame([$a], $this->ids(who_is_online(0, 50)));
    }

    public function testWhoIsOnlineInMultiUrlModeStillHidesSoftDeletedAndAnonymous(): void
    {
        $this->secondUrl();
        $current = api_get_current_access_url_id();
        $this->online(1, 1, 0, 0, $current);
        $this->online(2, 1, 0, 0, $current);
        $this->online(3, 1, 0, 0, $current);

        $this->assertSame([1], $this->ids(who_is_online(0, 50)));
    }

    // ---------------------------------------------------------------- who_is_online_count

    public function testWhoIsOnlineCountMatchesTheListingFilters(): void
    {
        $a = $this->user('a');
        $old = $this->user('old');
        $gone = $this->user('gone', 5, -2);
        $anon = $this->user('anon', 6);
        $this->online($a, 2);
        $this->online($old, 200);
        $this->online($gone, 2);
        $this->online($anon, 2);

        $this->assertSame(1, (int) who_is_online_count());
    }

    public function testWhoIsOnlineCountHonoursTheTimeLimitArgument(): void
    {
        $this->online($this->user('a'), 5);
        $this->online($this->user('b'), 20);

        $this->assertSame(1, (int) who_is_online_count('10'));
        $this->assertSame(2, (int) who_is_online_count('25'));
    }

    public function testWhoIsOnlineCountIsZeroWhenNobodyIsOnline(): void
    {
        $this->assertSame(0, (int) who_is_online_count());
    }

    public function testWhoIsOnlineCountFriendsModeCountsOnlyFriendsOfTheCurrentUser(): void
    {
        $me = $this->user('me');
        $f1 = $this->user('f1');
        $f2 = $this->user('f2');
        $stranger = $this->user('stranger');
        $this->friends($me, $f1);
        $this->friends($me, $f2);
        foreach ([$f1, $f2, $stranger] as $id) {
            $this->online($id, 1);
        }
        $this->actAs($me);

        $this->assertSame(2, (int) who_is_online_count(null, true));
    }

    public function testWhoIsOnlineCountInMultiUrlModeIsScopedToTheCurrentUrl(): void
    {
        $other = $this->secondUrl();
        $current = api_get_current_access_url_id();
        $this->online($this->user('here'), 1, 0, 0, $current);
        $this->online($this->user('there'), 1, 0, 0, $other);
        $this->online(3, 1, 0, 0, $current);

        $this->assertSame(1, (int) who_is_online_count());
    }

    // ---------------------------------------------------------------- user_is_online

    public function testUserIsOnlineIsTrueOnlyInsideTheTimeWindow(): void
    {
        $recent = $this->user('recent');
        $old = $this->user('old');
        $never = $this->user('never');
        $this->online($recent, 10);
        $this->online($old, 300);

        $this->assertTrue(user_is_online($recent));
        $this->assertFalse(user_is_online($old));
        $this->assertFalse(user_is_online($never));
    }

    public function testUserIsOnlineDoesNotFilterAnonymousOrSoftDeletedUsers(): void
    {
        $this->online(2, 1);
        $this->online(3, 1);

        // PINNED: unlike the listings, this predicate has no status filter: the anonymous account and the
        // soft-deleted one are "online" when their presence row is fresh.
        $this->assertTrue(user_is_online(2));
        $this->assertTrue(user_is_online(3));
    }

    public function testUserIsOnlineChecksThePresenceRowOfTheCurrentUrl(): void
    {
        $a = $this->user('a');
        $this->online($a, 1, 0, 0, 424242);

        // Even in single-URL mode (current url = 1) the presence row must carry that URL.
        $this->assertFalse(user_is_online($a));
    }

    // ---------------------------------------------------------------- course scoping

    public function testWhoIsOnlineInThisCourseIsScopedToTheCourseOfThePresenceRow(): void
    {
        $inCourse = $this->user('in');
        $elsewhere = $this->user('else');
        $nowhere = $this->user('none');
        $this->online($inCourse, 1, 1);
        $this->online($elsewhere, 1, 2);
        $this->online($nowhere, 1, 0);

        $this->assertSame([$inCourse], $this->ids(who_is_online_in_this_course(0, 50, 1, 30, 'AIACT')));
        $this->assertSame([$elsewhere], $this->ids(who_is_online_in_this_course(0, 50, 1, 30, 'USINGCHAMILO')));
    }

    public function testWhoIsOnlineInThisCourseAppliesTheTimeWindowAndTheStatusFilters(): void
    {
        $fresh = $this->user('fresh');
        $stale = $this->user('stale');
        $gone = $this->user('gone', 5, -2);
        $anon = $this->user('anon', 6);
        $this->online($fresh, 1, 1);
        $this->online($stale, 100, 1);
        $this->online($gone, 1, 1);
        $this->online($anon, 1, 1);

        $this->assertSame([$fresh], $this->ids(who_is_online_in_this_course(0, 50, 1, 30, 'AIACT')));
        $this->assertEqualsCanonicalizing(
            [$fresh, $stale],
            $this->ids(who_is_online_in_this_course(0, 50, 1, 200, 'AIACT'))
        );
    }

    public function testWhoIsOnlineInThisCourseWithoutACourseCodeIsFalse(): void
    {
        $this->assertFalse(who_is_online_in_this_course(0, 50, 1, 30, ''));
        $this->assertFalse(who_is_online_in_this_course(0, 50, 1, 30, null));
    }

    public function testWhoIsOnlineInThisCourseFallsBackToThePlatformTimeLimitWhenNoneIsGiven(): void
    {
        $fresh = $this->user('fresh');
        $stale = $this->user('stale');
        $this->online($fresh, 5, 1);
        $this->online($stale, 100, 1);

        // time_limit_whosonline defaults to 30 minutes.
        $this->assertSame([$fresh], $this->ids(who_is_online_in_this_course(0, 50, 1, 0, 'AIACT')));
    }

    public function testWhoIsOnlineInThisCoursePaginates(): void
    {
        $users = [];
        for ($i = 0; $i < 3; ++$i) {
            $users[] = $this->user('p'.$i);
            $this->online($users[$i], 1, 1);
        }

        $this->assertCount(2, who_is_online_in_this_course(0, 2, 1, 30, 'AIACT'));
        $this->assertCount(1, who_is_online_in_this_course(2, 2, 1, 30, 'AIACT'));
    }

    public function testWhoIsOnlineInThisCourseInMultiUrlModeFollowsTheUserUrlRelationNotThePresenceUrl(): void
    {
        $other = $this->secondUrl();
        $current = api_get_current_access_url_id();
        $mine = $this->user('mine');
        $theirs = $this->user('theirs');
        // The presence rows both say URL 1; what scopes the listing is access_url_rel_user.
        $this->online($mine, 1, 1, 0, 1);
        $this->online($theirs, 1, 1, 0, 1);
        $this->conn->executeStatement('INSERT INTO access_url_rel_user (user_id, access_url_id) VALUES (?, ?)', [$mine, $current]);
        $this->conn->executeStatement('INSERT INTO access_url_rel_user (user_id, access_url_id) VALUES (?, ?)', [$theirs, $other]);

        $this->assertSame([$mine], $this->ids(who_is_online_in_this_course(0, 50, 1, 30, 'AIACT')));
        $this->assertSame(1, (int) who_is_online_in_this_course_count(1, 30, 'AIACT'));
    }

    public function testWhoIsOnlineInThisCourseCountCountsTheSameUsersAsTheListing(): void
    {
        $this->online($this->user('a'), 1, 1);
        $this->online($this->user('b'), 2, 1);
        $this->online($this->user('stale'), 100, 1);
        $this->online($this->user('gone', 5, -2), 1, 1);
        $this->online($this->user('anon', 6), 1, 1);
        $this->online($this->user('other'), 1, 2);

        $this->assertSame(2, (int) who_is_online_in_this_course_count(1, 30, 'AIACT'));
        $this->assertSame(3, (int) who_is_online_in_this_course_count(1, 200, 'AIACT'));
        $this->assertSame(1, (int) who_is_online_in_this_course_count(1, 30, 'USINGCHAMILO'));
    }

    public function testWhoIsOnlineInThisCourseCountIsFalseForAnEmptyOrUnknownCourseCode(): void
    {
        $this->assertFalse(who_is_online_in_this_course_count(1, 30, null));
        $this->assertFalse(who_is_online_in_this_course_count(1, 30, ''));
        $this->assertFalse(who_is_online_in_this_course_count(1, 30, 'OU_NO_SUCH_COURSE'));
    }

    public function testWhoIsOnlineInThisCourseCountDoesNotAcceptANumericCourseId(): void
    {
        $this->online($this->user('a'), 1, 1);

        // PINNED QUIRK: the third argument is a course CODE. banner.lib.php::getOnlineUsersInCourseCount()
        // passes $courseInfo['id'] (the numeric id), which is looked up as a code and never matches.
        $this->assertFalse(who_is_online_in_this_course_count(1, 30, 1));
    }

    // ---------------------------------------------------------------- session scoping

    public function testWhoIsOnlineInThisSessionCountIsScopedToTheSession(): void
    {
        $this->online($this->user('a'), 1, 1, 7);
        $this->online($this->user('b'), 2, 1, 7);
        $this->online($this->user('other'), 1, 1, 8);
        $this->online($this->user('stale'), 100, 1, 7);
        $this->online($this->user('gone', 5, -2), 1, 1, 7);
        $this->online($this->user('anon', 6), 1, 1, 7);

        $this->assertSame(2, (int) whoIsOnlineInThisSessionCount(30, 7));
        $this->assertSame(3, (int) whoIsOnlineInThisSessionCount(200, 7));
        $this->assertSame(1, (int) whoIsOnlineInThisSessionCount(30, 8));
        $this->assertSame(0, (int) whoIsOnlineInThisSessionCount(30, 9));
    }

    public function testWhoIsOnlineInThisSessionCountWithoutASessionIsZero(): void
    {
        $this->online($this->user('a'), 1, 1, 0);

        $this->assertSame(0, whoIsOnlineInThisSessionCount(30, 0));
        $this->assertSame(0, whoIsOnlineInThisSessionCount(30, null));
    }

    public function testWhoIsOnlineInThisSessionCountInMultiUrlModeFollowsTheUserUrlRelation(): void
    {
        $other = $this->secondUrl();
        $current = api_get_current_access_url_id();
        $mine = $this->user('mine');
        $theirs = $this->user('theirs');
        $this->online($mine, 1, 1, 7);
        $this->online($theirs, 1, 1, 7);
        $this->conn->executeStatement('INSERT INTO access_url_rel_user (user_id, access_url_id) VALUES (?, ?)', [$mine, $current]);
        $this->conn->executeStatement('INSERT INTO access_url_rel_user (user_id, access_url_id) VALUES (?, ?)', [$theirs, $other]);

        $this->assertSame(1, (int) whoIsOnlineInThisSessionCount(30, 7));
    }
}
