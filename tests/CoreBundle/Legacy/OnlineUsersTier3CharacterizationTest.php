<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\Tests\ChamiloTestTrait;
use ChamiloSession;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session as HttpSession;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Uid\UuidV4;

/**
 * Complements OnlineUsersCharacterizationTest for the DQL migration of online.inc.php: return types,
 * multi-URL friends branches, the ORDER BY column allowlist and the unknown-course listing.
 *
 * LIVE (called from OnlineController, ajax/online.ajax.php, banner.lib.php, api.lib.php):
 * who_is_online, who_is_online_count, who_is_online_in_this_course, who_is_online_in_this_course_count,
 * whoIsOnlineInThisSessionCount, user_is_online.
 *
 * What is pinned is WHICH users are listed: the time window, soft-deleted (status -2) and anonymous
 * accounts hidden, the course/session scoping and, in multi-URL mode, the URL scoping. Presence rows
 * are written with fixed offsets from "now" (the library reads the clock), the users are raw INSERTs
 * (prefix ou_) plus the fixture accounts: 1 admin, 2 anonymous, 3 soft-deleted.
 */
final class OnlineUsersTier3CharacterizationTest extends KernelTestCase
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
        ChamiloSession::erase('_user');
        // A clean slate for the presence table: DAMA rolls this back.
        $this->conn->executeStatement('DELETE FROM track_e_online');
    }

    protected function tearDown(): void
    {
        ChamiloSession::erase('_user');
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

    /**
     * Presence row seen $minutesAgo minutes ago (UTC, as the library compares).
     */
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

    /**
     * A second access URL switches the platform to multi-URL mode (AccessUrlHelper::isMultiple()).
     */
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
        ChamiloSession::write('_user', ['id' => $userId]);
    }

    /**
     * @return int[]
     */
    private function ids(array|false $rows): array
    {
        $this->assertIsArray($rows);

        return array_map('intval', $rows);
    }

    // ---------------------------------------------------------------- tier 3 additions

    public function testReturnTypes(): void
    {
        $a = $this->user('a');
        $this->online($a, 1, 1, 7);

        $this->assertSame([$a], who_is_online(0, 50));
        $this->assertSame([$a], who_is_online_in_this_course(0, 50, 1, 30, 'AIACT'));
        $this->assertTrue(user_is_online($a));
        $this->assertSame(1, who_is_online_count());
        $this->assertSame(1, who_is_online_in_this_course_count(1, 30, 'AIACT'));
        $this->assertSame(1, whoIsOnlineInThisSessionCount(30, 7));
    }

    public function testOrderByEveryColumnCallersMayPass(): void
    {
        $a = $this->user('a', 5, 1, 'b.png');
        $b = $this->user('b', 5, 1, 'a.png');
        $this->online($a, 1);
        $this->online($b, 2);

        $this->assertSame([$a, $b], who_is_online(0, 50, 'login_user_id', 'asc'));
        $this->assertSame([$b, $a], who_is_online(0, 50, 'login_user_id', 'desc'));
        $this->assertSame([$b, $a], who_is_online(0, 50, 'picture_uri', 'asc'));
        $this->assertSame([$a, $b], who_is_online(0, 50, 'picture_uri', 'desc'));
        $this->assertSame([$b, $a], who_is_online(0, 50, 'login_date', 'asc'));
    }

    public function testFriendsModeOrdersByLoginDateByDefault(): void
    {
        $me = $this->user('me');
        $f1 = $this->user('f1');
        $f2 = $this->user('f2');
        $this->friends($me, $f1);
        $this->friends($me, $f2);
        $this->online($f1, 5);
        $this->online($f2, 1);
        $this->actAs($me);

        $this->assertSame([$f2, $f1], who_is_online(0, 50, null, null, null, true));
        $this->assertSame([$f1, $f2], who_is_online(0, 50, null, 'asc', null, true));
    }

    public function testFriendsModeInMultiUrlModeIsScopedToTheUrlButNotToTheCurrentUser(): void
    {
        $other = $this->secondUrl();
        $current = api_get_current_access_url_id();
        $me = $this->user('me');
        $mine = $this->user('mine');
        $theirs = $this->user('theirs');
        $otherUrl = $this->user('otherurl');
        $this->friends($me, $mine);
        $this->friends($theirs, $mine);
        $this->friends($me, $otherUrl);
        $this->online($mine, 1, 0, 0, $current);
        $this->online($otherUrl, 1, 0, 0, $other);
        $this->actAs($me);

        // PINNED QUIRK: the multi-URL friends branch drops the "user_id = me" condition, so a friendship
        // of anybody counts: $mine is joined once per relation row (DISTINCT lists it once, but the
        // count of the same join is 2).
        $this->assertSame([$mine], who_is_online(0, 50, null, null, null, true));
        $this->assertSame(2, who_is_online_count(null, true));
    }

    public function testWhoIsOnlineCountFriendsModeCountsPresenceRowsTimesRelations(): void
    {
        $me = $this->user('me');
        $f = $this->user('f');
        $this->friends($me, $f);
        $this->online($f, 1);
        $this->online($f, 2, 1, 0, 424242);
        $this->actAs($me);

        // single-URL mode: no URL filter, two presence rows of the same friend.
        $this->assertSame(2, who_is_online_count(null, true));
    }

    public function testUrlScopedCountsAreNotSymmetric(): void
    {
        // Two users on the current URL against one on the other: a flipped URL condition gives 1, not 2.
        $other = $this->secondUrl();
        $current = api_get_current_access_url_id();
        $a = $this->user('a');
        $b = $this->user('b');
        $c = $this->user('c');
        $this->online($a, 1, 1, 7, $current);
        $this->online($b, 1, 1, 7, $current);
        $this->online($c, 1, 1, 7, $other);
        $this->assertSame(2, who_is_online_count());

        foreach ([$a, $b] as $id) {
            $this->conn->executeStatement('INSERT INTO access_url_rel_user (user_id, access_url_id) VALUES (?, ?)', [$id, $current]);
        }
        $this->conn->executeStatement('INSERT INTO access_url_rel_user (user_id, access_url_id) VALUES (?, ?)', [$c, $other]);
        $this->assertSame(2, who_is_online_in_this_course_count(1, 30, 'AIACT'));
        $this->assertSame(2, whoIsOnlineInThisSessionCount(30, 7));
    }

    public function testSingleUrlModeListsAUserWithTwoPresenceRowsOnce(): void
    {
        $a = $this->user('a');
        $me = $this->user('me');
        // DISTINCT applies to (user id, login date): both rows carry the same date.
        $date = gmdate('Y-m-d H:i:s', time() - 60);
        foreach ([1, 424242] as $urlId) {
            $this->conn->executeStatement(
                "INSERT INTO track_e_online (login_user_id, login_date, user_ip, c_id, session_id, access_url_id)
                 VALUES (?, ?, '127.0.0.1', 0, 0, ?)",
                [$a, $date, $urlId]
            );
        }
        $this->friends($me, $a);
        $this->actAs($me);

        $this->assertSame([$a], who_is_online(0, 50));
        $this->assertSame([$a], who_is_online(0, 50, null, null, null, true));
    }
}
