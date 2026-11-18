<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Controller;

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\Tests\ChamiloTestTrait;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use TicketManager;

use const PHP_URL_PATH;
use const PHP_URL_QUERY;

/**
 * The ticket unsubscribe link mailed to a follower must only ever unsubscribe that follower:
 * whoever holds a link must not be able to point it at another user of the same ticket.
 */
class ValidationTokenControllerTest extends WebTestCase
{
    use ChamiloTestTrait;

    private KernelBrowser $client;
    private Connection $conn;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $container = self::getContainer();
        Container::setContainer($container);
        Container::setLegacyServices($container);
        $session = new Session(new MockArraySessionStorage());
        Container::setSession($session);
        $request = Request::create('/main/ticket/tickets.php');
        $request->setSession($session);
        Container::setRequest($request);

        $this->conn = $container->get('doctrine')->getConnection();
    }

    public function testUnsubscribeLinkOnlyUnsubscribesTheUserItWasIssuedTo(): void
    {
        $holder = (int) $this->createUser('vt_holder')->getId();
        $victim = (int) $this->createUser('vt_victim')->getId();
        $ticketId = $this->createTicket();
        TicketManager::subscribeUserToTicket($ticketId, $holder);
        TicketManager::subscribeUserToTicket($ticketId, $victim);

        [$path, $query] = $this->splitLink(TicketManager::generateUnsubscribeLink($ticketId, $holder));
        $query['user_id'] = (string) $victim;

        $this->client->request('GET', $path, $query);

        $this->assertTrue($this->isSubscribed($ticketId, $victim), 'another user must not be unsubscribed');
        $this->assertTrue($this->isSubscribed($ticketId, $holder));
        self::assertResponseStatusCodeSame(404);
    }

    public function testUnsubscribeLinkWithoutUserIsRejected(): void
    {
        $holder = (int) $this->createUser('vt_nouser')->getId();
        $ticketId = $this->createTicket();
        TicketManager::subscribeUserToTicket($ticketId, $holder);

        [$path] = $this->splitLink(TicketManager::generateUnsubscribeLink($ticketId, $holder));

        $this->client->request('GET', $path);

        self::assertResponseStatusCodeSame(404);
        $this->assertTrue($this->isSubscribed($ticketId, $holder));
    }

    public function testUnsubscribeLinkUnsubscribesItsOwnUserOnce(): void
    {
        $holder = (int) $this->createUser('vt_owner')->getId();
        $ticketId = $this->createTicket();
        TicketManager::subscribeUserToTicket($ticketId, $holder);

        [$path, $query] = $this->splitLink(TicketManager::generateUnsubscribeLink($ticketId, $holder));

        $this->client->request('GET', $path, $query);

        self::assertResponseRedirects('/tickets/'.$ticketId);
        $this->assertFalse($this->isSubscribed($ticketId, $holder));

        // Single use: the same link cannot be replayed.
        TicketManager::subscribeUserToTicket($ticketId, $holder);
        $this->client->request('GET', $path, $query);

        self::assertResponseStatusCodeSame(404);
        $this->assertTrue($this->isSubscribed($ticketId, $holder));
    }

    /**
     * @return array{0: string, 1: array<string, string>}
     */
    private function splitLink(string $link): array
    {
        parse_str((string) parse_url($link, PHP_URL_QUERY), $query);

        return [(string) parse_url($link, PHP_URL_PATH), $query];
    }

    private function isSubscribed(int $ticketId, int $userId): bool
    {
        return 1 === (int) $this->conn->fetchOne(
            'SELECT COUNT(*) FROM ticket_rel_user WHERE ticket_id = ? AND user_id = ?',
            [$ticketId, $userId]
        );
    }

    private function createTicket(): int
    {
        $this->conn->insert('ticket_project', [
            'access_url_id' => 1,
            'title' => 'vt_project',
            'sys_insert_user_id' => 1,
            'sys_insert_datetime' => '2020-01-01 00:00:00',
        ]);
        $projectId = (int) $this->conn->lastInsertId();

        $this->conn->insert('ticket_category', [
            'project_id' => $projectId,
            'title' => 'vt_category',
            'total_tickets' => 0,
            'course_required' => 0,
            'sys_insert_user_id' => 1,
            'sys_insert_datetime' => '2020-01-01 00:00:00',
        ]);
        $categoryId = (int) $this->conn->lastInsertId();

        $this->conn->insert('ticket_ticket', [
            'project_id' => $projectId,
            'category_id' => $categoryId,
            'priority_id' => 1,
            'status_id' => 1,
            'access_url_id' => 1,
            'code' => 'vt_code_'.uniqid(),
            'subject' => 'vt_subject',
            'message' => 'vt_body',
            'personal_email' => '',
            'total_messages' => 0,
            'start_date' => '2021-03-15 10:00:00',
            'sys_insert_user_id' => 1,
            'sys_insert_datetime' => '2021-03-15 10:00:00',
            'sys_lastedit_user_id' => 1,
            'sys_lastedit_datetime' => '2021-03-15 10:00:00',
        ]);

        return (int) $this->conn->lastInsertId();
    }
}
