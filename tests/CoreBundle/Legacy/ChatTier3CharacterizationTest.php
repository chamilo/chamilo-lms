<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use ArgumentCountError;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\Tests\ChamiloTestTrait;
use ChamiloSession;
use Chat;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session as HttpSession;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * Characterization tests for the Chat class (chat.lib.php) readers and writers of the `chat` table, used by the docked
 * chat (ChatController) and by chat.ajax.php: getMessages(), heartbeat(), ackReadUpTo(), getUnreadByPeer(),
 * heartbeatMin(), heartbeatTiny(), getIncomingSince().
 *
 * recd: 0 not delivered, 1 delivered, 2 read.
 *
 * Fixtures: two fresh users (me, peer) and a third (other); chat rows are inserted raw, DAMA rolls them back.
 */
final class ChatTier3CharacterizationTest extends KernelTestCase
{
    use ChamiloTestTrait;

    private int $me;
    private int $peer;
    private int $other;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        Container::setContainer($container);
        Container::setLegacyServices($container);
        $httpSession = new HttpSession(new MockArraySessionStorage());
        Container::setSession($httpSession);
        $request = Request::create('/main/chat/chat.php');
        $request->setSession($httpSession);
        Container::setRequest($request);
        ChamiloSession::erase('_user');

        $this->me = (int) $this->createUser('ct3_me')->getId();
        $this->peer = (int) $this->createUser('ct3_peer')->getId();
        $this->other = (int) $this->createUser('ct3_other')->getId();
    }

    protected function tearDown(): void
    {
        ChamiloSession::erase('_user');
        ChamiloSession::erase('chatHistory');
        unset($_SESSION['openChatBoxes'], $_SESSION['tsChatBoxes']);
        parent::tearDown();
    }

    // ------------------------------------------------------------------ getMessages

    public function testGetMessagesReturnsTheConversationInBothDirectionsOldestFirstKeyedById(): void
    {
        $a = $this->chat($this->me, $this->peer, 'one', 0);
        $b = $this->chat($this->peer, $this->me, 'two', 1);
        $c = $this->chat($this->me, $this->peer, 'three', 2);
        $this->chat($this->me, $this->other, 'elsewhere', 0);
        $this->chat($this->other, $this->peer, 'not mine', 0);

        $items = (new Chat())->getMessages($this->me, $this->peer, 0, 10);

        // Rows are fetched id ASC, then reversed: newest first.
        $this->assertSame([$c, $b, $a], array_keys($items));
        $this->assertSame($c, $items[$c]['id']);
        $this->assertSame('three', $items[$c]['message']);
        $this->assertSame(2, $items[$c]['recd']);
        $this->assertSame(0, $items[$a]['recd']);
        $this->assertSame(api_strtotime('2030-01-02 03:04:05', 'UTC'), $items[$a]['date']);
        $this->assertSame($this->me, (int) $items[$a]['from_user_info']['id']);
        $this->assertSame($this->peer, (int) $items[$a]['to_user_info']['id']);
        $this->assertSame(api_strtotime('2030-01-02 03:04:05', 'UTC'), $_SESSION['openChatBoxes'][$this->me]);
    }

    public function testGetMessagesFiltersMarkupFromTheMessage(): void
    {
        $id = $this->chat($this->me, $this->peer, 'hi <script>alert(1)</script>there', 0);

        $items = (new Chat())->getMessages($this->me, $this->peer, 0, 10);

        $this->assertStringNotContainsString('<script', $items[$id]['message']);
        $this->assertStringContainsString('there', $items[$id]['message']);
    }

    public function testGetMessagesAppliesOffsetAndCountLikeLimitStartEnd(): void
    {
        $ids = [];
        foreach (range(1, 5) as $i) {
            $ids[$i] = $this->chat($this->me, $this->peer, 'm'.$i, 0);
        }
        $chat = new Chat();

        $this->assertSame([$ids[3], $ids[2]], array_keys($chat->getMessages($this->me, $this->peer, 1, 2)));
        $this->assertSame([$ids[5], $ids[4], $ids[3]], array_keys($chat->getMessages($this->me, $this->peer, 2, 10)));
        $this->assertSame([], $chat->getMessages($this->me, $this->peer, 0, 0));
        $this->assertSame([], $chat->getMessages($this->me, $this->peer, 9, 3));
    }

    public function testGetMessagesOrderByDescendingOnlyWhenAsked(): void
    {
        $ids = [];
        foreach (range(1, 3) as $i) {
            $ids[$i] = $this->chat($this->me, $this->peer, 'm'.$i, 0);
        }
        $chat = new Chat();

        // DESC fetch, reversed: oldest first.
        $this->assertSame([$ids[1], $ids[2], $ids[3]], array_keys($chat->getMessages($this->me, $this->peer, 0, 10, 'ORDER BY id DESC')));
        $this->assertSame([$ids[3], $ids[2], $ids[1]], array_keys($chat->getMessages($this->me, $this->peer, 0, 10, 'ORDER BY id ASC')));
        $this->assertSame([$ids[3], $ids[2], $ids[1]], array_keys($chat->getMessages($this->me, $this->peer, 0, 10, '')));
    }

    public function testGetMessagesOrderByFreeTextIsNotHonouredAnyMore(): void
    {
        // BEHAVIOUR CHANGE of the DQL migration (written after it, no caller passes an order): the clause used to be spliced
        // into the SQL (a stacked statement such as 'ORDER BY id; ...' reached the database); now only 'ORDER BY id ASC|DESC'
        // is understood and anything else is the default ascending fetch.
        $ids = [];
        foreach (range(1, 3) as $i) {
            $ids[$i] = $this->chat($this->me, $this->peer, 'm'.$i, 0);
        }

        $items = (new Chat())->getMessages($this->me, $this->peer, 0, 10, 'ORDER BY id; DROP TABLE chat');

        $this->assertSame([$ids[3], $ids[2], $ids[1]], array_keys($items));
        $this->assertSame(3, (int) $this->conn()->fetchOne('SELECT COUNT(*) FROM chat WHERE from_user = ?', [$this->me]));
    }

    public function testGetMessagesWithoutBothUsersIsEmpty(): void
    {
        $this->chat($this->me, $this->peer, 'x', 0);
        $chat = new Chat();

        $this->assertSame([], $chat->getMessages(0, $this->peer, 0, 10));
        $this->assertSame([], $chat->getMessages($this->me, 0, 0, 10));
    }

    // ------------------------------------------------------------------ heartbeat

    public function testHeartbeatPublishesTheUndeliveredMessagesPerPeerAndMarksThemDelivered(): void
    {
        ChamiloSession::write('_user', ['id' => $this->me]);
        $in1 = $this->chat($this->peer, $this->me, 'in1', 0);
        $in2 = $this->chat($this->peer, $this->me, 'in2', 0);
        $this->chat($this->other, $this->me, 'already delivered', 1);
        $this->chat($this->me, $this->peer, 'outgoing', 0);

        ob_start();
        (new Chat())->heartbeat();
        $payload = json_decode((string) ob_get_clean(), true);

        // Only the peer with undelivered messages gets a window.
        $this->assertSame([(string) $this->peer], array_map('strval', array_keys($payload['items'])));
        $window = $payload['items'][$this->peer];
        $this->assertSame(3, $window['total_messages'], 'in1, in2 and the outgoing one.');
        $this->assertSame($this->peer, (int) $window['window_user_info']['id']);
        $this->assertCount(3, $window['items']);

        $this->assertSame([1, 1], $this->recdOf([$in1, $in2]));
        $this->assertSame(1, (int) $this->conn()->fetchOne(
            'SELECT recd FROM chat WHERE from_user = ? AND to_user = ?',
            [$this->other, $this->me]
        ));
        $this->assertSame(0, (int) $this->conn()->fetchOne(
            'SELECT recd FROM chat WHERE from_user = ? AND to_user = ?',
            [$this->me, $this->peer]
        ), 'My outgoing message is not touched.');
        $this->assertSame(api_strtotime('2030-01-02 03:04:05', 'UTC'), $_SESSION['openChatBoxes'][$this->peer]);
    }

    public function testHeartbeatWithNothingNewKeepsAnEmptyHistory(): void
    {
        ChamiloSession::write('_user', ['id' => $this->me]);

        ob_start();
        (new Chat())->heartbeat();
        $payload = json_decode((string) ob_get_clean(), true);

        $this->assertEmpty($payload['items']);
    }

    // ------------------------------------------------------------------ ackReadUpTo

    public function testAckReadUpToUpdatesTheRowsThenThrowsBecauseAffectedRowsIsCalledWithoutItsArgument(): void
    {
        // BROKEN (ChatController::ack reaches it): Database::affected_rows() requires the Result and is called with no
        // argument, so the call throws ArgumentCountError AFTER the UPDATE already ran. Intended: return the number of rows
        // marked read (the peer's messages to me with id <= lastSeen that were not read yet).
        $a = $this->chat($this->peer, $this->me, 'a', 0);
        $b = $this->chat($this->peer, $this->me, 'b', 1);
        $c = $this->chat($this->peer, $this->me, 'c', 1);
        $alreadyRead = $this->chat($this->peer, $this->me, 'd', 2);
        $mine = $this->chat($this->me, $this->peer, 'mine', 0);
        $elsewhere = $this->chat($this->other, $this->me, 'e', 0);

        try {
            (new Chat())->ackReadUpTo($this->peer, $this->me, $b);
            $this->fail('Expected ArgumentCountError.');
        } catch (ArgumentCountError) {
        }

        $this->assertSame([2, 2, 1, 2, 0, 0], $this->recdOf([$a, $b, $c, $alreadyRead, $mine, $elsewhere]));
    }

    public function testAckReadUpToRejectsNonPositiveArguments(): void
    {
        $a = $this->chat($this->peer, $this->me, 'a', 0);
        $chat = new Chat();

        $this->assertSame(0, $chat->ackReadUpTo(0, $this->me, $a));
        $this->assertSame(0, $chat->ackReadUpTo($this->peer, 0, $a));
        $this->assertSame(0, $chat->ackReadUpTo($this->peer, $this->me, 0));
        $this->assertSame([0], $this->recdOf([$a]));
    }

    // ------------------------------------------------------------------ unread counters

    public function testGetUnreadByPeerCountsMessagesNotYetReadGroupedBySender(): void
    {
        $this->chat($this->peer, $this->me, 'a', 0);
        $this->chat($this->peer, $this->me, 'b', 1);
        $this->chat($this->peer, $this->me, 'c', 2);
        $this->chat($this->other, $this->me, 'd', 0);
        $this->chat($this->me, $this->peer, 'outgoing', 0);

        $map = (new Chat())->getUnreadByPeer($this->me);

        ksort($map);
        $this->assertSame([$this->peer => 2, $this->other => 1], $map);
        $this->assertSame([], (new Chat())->getUnreadByPeer(0));
        $this->assertSame([], (new Chat())->getUnreadByPeer($this->peer + 100000));
    }

    public function testHeartbeatMinReportsLastIncomingIdAfterSinceIdAndUnreadTotals(): void
    {
        $a = $this->chat($this->peer, $this->me, 'a', 2);
        $b = $this->chat($this->other, $this->me, 'b', 0);
        $c = $this->chat($this->peer, $this->me, 'c', 1);
        $this->chat($this->me, $this->peer, 'outgoing', 0);
        $chat = new Chat();

        $this->assertSame(
            [
                'has_new' => true,
                'last_id' => $c,
                'unread' => 2,
                'unread_by_peer' => [$this->peer => 1, $this->other => 1],
                'since_id' => 0,
            ],
            $this->sortedPeers($chat->heartbeatMin($this->me))
        );

        $fromB = $chat->heartbeatMin($this->me, $b);
        $this->assertTrue($fromB['has_new']);
        $this->assertSame($c, $fromB['last_id']);
        $this->assertSame(2, $fromB['unread'], 'The unread total does not depend on sinceId.');

        $none = $chat->heartbeatMin($this->me, $c);
        $this->assertFalse($none['has_new']);
        $this->assertSame(0, $none['last_id']);
        $this->assertSame($c, $none['since_id']);

        $this->assertSame(0, $chat->heartbeatMin($this->me, -5)['since_id'], 'Negative sinceId is clamped to 0.');
        $this->assertGreaterThan($a, $c);
    }

    public function testHeartbeatTinyReportsTheLastIdAndUnreadOfOnePeer(): void
    {
        $this->chat($this->peer, $this->me, 'a', 2);
        $b = $this->chat($this->peer, $this->me, 'b', 0);
        $this->chat($this->other, $this->me, 'other', 0);
        $this->chat($this->me, $this->peer, 'outgoing', 0);
        $chat = new Chat();

        $this->assertSame(
            ['has_new' => true, 'last_id' => $b, 'peer_id' => $this->peer, 'since_id' => 0, 'unread_peer' => 1],
            $chat->heartbeatTiny($this->me, $this->peer)
        );
        $this->assertFalse($chat->heartbeatTiny($this->me, $this->peer, $b)['has_new']);
        $this->assertSame(
            ['has_new' => false, 'last_id' => 0, 'peer_id' => $this->peer + 100000, 'since_id' => 0, 'unread_peer' => 0],
            $chat->heartbeatTiny($this->me, $this->peer + 100000)
        );
    }

    // ------------------------------------------------------------------ getIncomingSince

    public function testGetIncomingSinceReturnsOnlyNewMessagesFromThePeerAndMarksThemDelivered(): void
    {
        $old = $this->chat($this->peer, $this->me, 'old', 0);
        $new1 = $this->chat($this->peer, $this->me, 'new1', 0);
        $new2 = $this->chat($this->peer, $this->me, 'new2', 2);
        $mine = $this->chat($this->me, $this->peer, 'mine', 0);
        $elsewhere = $this->chat($this->other, $this->me, 'else', 0);

        $items = (new Chat())->getIncomingSince($this->peer, $this->me, $old);

        $this->assertSame([$new1, $new2], array_column($items, 'id'), 'id ASC, list (not keyed).');
        $this->assertSame(['id', 'message', 'date', 'recd', 'from_user_info', 'to_user_info'], array_keys($items[0]));
        $this->assertSame('new1', $items[0]['message']);
        $this->assertSame(0, $items[0]['recd'], 'The recd shown is the one before the update.');
        $this->assertSame(2, $items[1]['recd']);
        $this->assertSame($this->peer, (int) $items[0]['from_user_info']['id']);
        $this->assertSame($this->me, (int) $items[0]['to_user_info']['id']);

        $this->assertSame([0, 1, 2, 0, 0], $this->recdOf([$old, $new1, $new2, $mine, $elsewhere]), 'Delivered never lowers a read message.');
    }

    public function testGetIncomingSinceWithNothingNewOrBadIdsIsEmpty(): void
    {
        $a = $this->chat($this->peer, $this->me, 'a', 0);
        $chat = new Chat();

        $this->assertSame([], $chat->getIncomingSince($this->peer, $this->me, $a));
        $this->assertSame([], $chat->getIncomingSince(0, $this->me));
        $this->assertSame([], $chat->getIncomingSince($this->peer, 0));
        $this->assertSame([$a], array_column($chat->getIncomingSince($this->peer, $this->me, -3), 'id'), 'Negative sinceId is clamped to 0.');
    }

    public function testGetIncomingSinceReturnsAtMost200Messages(): void
    {
        $this->conn()->executeStatement(
            "INSERT INTO chat (from_user, to_user, message, sent, recd)
             SELECT ?, ?, 'bulk', '2030-01-02 03:04:05', 0 FROM (
                SELECT 1 FROM information_schema.columns LIMIT 210
             ) t",
            [$this->peer, $this->me]
        );

        $this->assertCount(200, (new Chat())->getIncomingSince($this->peer, $this->me));
        $this->assertSame(
            10,
            (int) $this->conn()->fetchOne('SELECT COUNT(*) FROM chat WHERE from_user = ? AND to_user = ? AND recd = 0', [$this->peer, $this->me])
        );
    }

    // ------------------------------------------------------------------ helpers

    private function conn(): Connection
    {
        return $this->getEntityManager()->getConnection();
    }

    private function chat(int $from, int $to, string $message, int $recd): int
    {
        $this->conn()->executeStatement(
            "INSERT INTO chat (from_user, to_user, message, sent, recd) VALUES (?, ?, ?, '2030-01-02 03:04:05', ?)",
            [$from, $to, $message, $recd]
        );

        return (int) $this->conn()->lastInsertId();
    }

    /**
     * @param int[] $ids
     *
     * @return int[]
     */
    private function recdOf(array $ids): array
    {
        $out = [];
        foreach ($ids as $id) {
            $out[] = (int) $this->conn()->fetchOne('SELECT recd FROM chat WHERE id = ?', [$id]);
        }

        return $out;
    }

    private function sortedPeers(array $result): array
    {
        ksort($result['unread_by_peer']);

        return $result;
    }
}
