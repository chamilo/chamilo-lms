<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Legacy;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Repository\Node\AccessUrlRepository;
use Chamilo\CoreBundle\Repository\TrackELoginRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use ChamiloSession;
use Database;
use SessionManager;
use Statistics;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session as HttpSession;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Tracking;

/**
 * A user shared between two access URLs logs in on both. On a multi-URL platform, the reports of
 * one URL must not show the connections made on the other one, but must keep the connections
 * recorded before track_e_login had access_url_id (NULL): they cannot be attributed, and dropping
 * them would erase the history of every user.
 */
class TrackELoginAccessUrlReportTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    private int $userId;

    protected function setUp(): void
    {
        parent::setUp();

        // Created before the request below: the resource listener reads a pushed request's body.
        $currentUrl = $this->getAccessUrl();
        $otherUrl = (new AccessUrl())
            ->setUrl('https://login-scope-'.uniqid().'.example.org/')
            ->setActive(1)
            ->setCreator($this->getAdmin())
            ->setSuperior($currentUrl)
        ;
        self::getContainer()->get(AccessUrlRepository::class)->create($otherUrl);

        $httpSession = new HttpSession(new MockArraySessionStorage());
        $request = new Request();
        $request->setSession($httpSession);
        self::getContainer()->get('request_stack')->push($request);
        Container::setContainer(self::getContainer());
        Container::setSession($httpSession);
        Container::setRequest($request);
        Database::setManager($this->getEntityManager());
        Database::setConnection($this->getEntityManager()->getConnection());
        ChamiloSession::write('system_timezone', 'UTC');

        $this->userId = (int) $this->createUser('tel_url_report_'.uniqid())->getId();
        $connection = $this->getEntityManager()->getConnection();
        $connection->executeStatement(
            'INSERT INTO access_url_rel_user (access_url_id, user_id) VALUES (?, ?)',
            [$otherUrl->getId(), $this->userId]
        );

        // 5 min before the column existed, 10 min on this URL, 30 min on the other one (the latest).
        $this->insertLogin('2026-01-05 08:00:00', '2026-01-05 08:05:00', null);
        $this->insertLogin('2026-01-10 08:00:00', '2026-01-10 08:10:00', (int) $currentUrl->getId());
        $this->insertLogin('2026-01-20 08:00:00', '2026-01-20 08:30:00', (int) $otherUrl->getId());
    }

    protected function tearDown(): void
    {
        ChamiloSession::erase('system_timezone');
        parent::tearDown();
    }

    public function testThePlatformIsMultiUrl(): void
    {
        $this->assertTrue(Container::getAccessUrlUtil()->isMultiple());
    }

    public function testTimeOnThePlatformCountsThisUrlAndUnattributedConnections(): void
    {
        $this->assertSame(
            900,
            (int) Tracking::get_time_spent_on_the_platform($this->userId, 'custom', '2026-01-01 00:00:00', '2026-01-31 23:59:59')
        );
        $this->assertSame(900, self::getContainer()->get(TrackELoginRepository::class)->getTotalConnectionTime($this->userId));
        $this->assertSame('00:15:00', SessionManager::getTotalUserTimeInPlatform($this->userId, '2026-01-01', '2026-01-31'));
    }

    public function testConnectionDatesIgnoreTheOtherUrl(): void
    {
        $this->assertSame(
            strtotime('2026-01-10 08:00:00 UTC'),
            Tracking::get_last_connection_date($this->userId, false, true)
        );
        $this->assertNotFalse(Tracking::get_first_connection_date($this->userId), 'The unattributed connection still counts.');
    }

    public function testPlatformStatisticsCountThisUrlAndUnattributedConnections(): void
    {
        $rows = array_column(Statistics::getLoginsByDate('2026-01-01', '2026-01-31'), 'time_count', 'id');

        $this->assertSame(900, (int) ($rows[$this->userId] ?? 0));
    }

    private function insertLogin(string $login, string $logout, ?int $urlId): void
    {
        $this->getEntityManager()->getConnection()->executeStatement(
            'INSERT INTO track_e_login (login_user_id, login_date, logout_date, user_ip, access_url_id) VALUES (?, ?, ?, ?, ?)',
            [$this->userId, $login, $logout, '127.0.0.1', $urlId]
        );
    }
}
