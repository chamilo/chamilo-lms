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

/**
 * Characterization of online_logout() (online.inc.php), the part that reads/closes the user's last track_e_login row.
 * Callers: main/auth/unsubscribe_account.php and UserPortal::logout().
 *
 * It closes the MOST RECENT login row of the user (login_date DESC) by setting logout_date to the current UTC time,
 * except when the session says the user is being impersonated ($_SESSION['login_as']).
 */
final class OnlineLogoutTier3CharacterizationTest extends KernelTestCase
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
        $request = Request::create('/main/auth/unsubscribe_account.php');
        $request->setSession($httpSession);
        Container::setRequest($request);
        ChamiloSession::erase('_user');
        unset($_SESSION['login_as']);
    }

    protected function tearDown(): void
    {
        unset($_SESSION['login_as']);
        parent::tearDown();
    }

    public function testLogoutClosesOnlyTheMostRecentLoginOfTheGivenUser(): void
    {
        $oldest = $this->login(1, '2030-01-01 10:00:00');
        $latest = $this->login(1, '2030-01-03 10:00:00');
        $middle = $this->login(1, '2030-01-02 10:00:00');
        $otherUser = $this->login(2, '2030-01-04 10:00:00');

        $before = gmdate('Y-m-d H:i:s');
        $this->logout(1);
        $after = gmdate('Y-m-d H:i:s');

        $closed = $this->logoutDate($latest);
        $this->assertNotNull($closed);
        $this->assertGreaterThanOrEqual($before, $closed);
        $this->assertLessThanOrEqual($after, $closed);
        $this->assertNull($this->logoutDate($oldest));
        $this->assertNull($this->logoutDate($middle));
        $this->assertNull($this->logoutDate($otherUser));
    }

    public function testLogoutWhileImpersonatingLeavesTheLoginOpen(): void
    {
        $id = $this->login(1, '2030-01-01 10:00:00');
        $_SESSION['login_as'] = 1;

        $this->logout(1);

        $this->assertNull($this->logoutDate($id));
    }

    public function testLogoutOfAUserWithoutLoginRowsChangesNothing(): void
    {
        $id = $this->login(2, '2030-01-01 10:00:00');

        $this->logout(1);

        $this->assertNull($this->logoutDate($id));
    }

    public function testLogoutReadsTheUserFromTheUidQueryParameterWhenNoIdIsGiven(): void
    {
        $id = $this->login(1, '2030-01-01 10:00:00');
        $_GET['uid'] = '1';

        try {
            $this->logout(null);
        } finally {
            unset($_GET['uid']);
        }

        $this->assertNotNull($this->logoutDate($id));
    }

    private function logout(?int $userId): void
    {
        // The function ends by regenerating/destroying the PHP session, which CLI cannot do: those warnings are expected.
        set_error_handler(static fn (): bool => true, E_WARNING | E_NOTICE);

        try {
            online_logout($userId, false);
        } finally {
            restore_error_handler();
        }
    }

    private function conn(): Connection
    {
        return $this->getEntityManager()->getConnection();
    }

    private function login(int $userId, string $date): int
    {
        $this->conn()->executeStatement(
            "INSERT INTO track_e_login (login_user_id, login_date, user_ip, logout_date) VALUES (?, ?, '127.0.0.1', NULL)",
            [$userId, $date]
        );

        return (int) $this->conn()->lastInsertId();
    }

    private function logoutDate(int $loginId): ?string
    {
        $v = $this->conn()->fetchOne('SELECT logout_date FROM track_e_login WHERE login_id = ?', [$loginId]);

        return false === $v ? null : $v;
    }
}
