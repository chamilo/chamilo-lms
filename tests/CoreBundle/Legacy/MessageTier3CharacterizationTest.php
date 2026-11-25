<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\Tests\ChamiloTestTrait;
use ChamiloSession;
use Doctrine\DBAL\Connection;
use MessageManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session as HttpSession;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * Characterization tests for MessageManager (message.lib.php), the helpers that read the `message` table directly.
 *
 * LIVE: messageWasAlreadySent() (announcement e-mails), get_messages_by_group() / get_messages_by_group_by_message()
 * (social group forum), get_message_by_id() (group topic page).
 *
 * REWRITTEN (they read message.user_receiver_id, which is gone; recipients live in message_rel_user, so they always threw):
 * getUsersThatHadConversationWithUser() (my_space/myStudents.php) and getAllMessagesBetweenStudents() (tracking/messages.php).
 * getMessagesByParent() and isUserOwner() (no callers) were removed.
 *
 * Fixtures: ids from 900000 up (mt3_), removed in tearDown.
 */
final class MessageTier3CharacterizationTest extends KernelTestCase
{
    use ChamiloTestTrait;

    private const BASE = 900000;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        Container::setContainer($container);
        Container::setLegacyServices($container);
        $httpSession = new HttpSession(new MockArraySessionStorage());
        Container::setSession($httpSession);
        $request = Request::create('/main/social/groups.php');
        $request->setSession($httpSession);
        Container::setRequest($request);
        ChamiloSession::erase('_user');
    }

    protected function tearDown(): void
    {
        ChamiloSession::erase('_user');
        parent::tearDown();
    }

    // ------------------------------------------------------------------ messageWasAlreadySent

    public function testMessageWasAlreadySentMatchesSenderReceiverTitleAndContentOfAnInboxMessage(): void
    {
        $this->msg(self::BASE + 1, 1, null, null, 1, 'Subject A', 'Body A');
        $this->recipient(self::BASE + 1, 2);

        $this->assertTrue(MessageManager::messageWasAlreadySent(1, 2, 'Subject A', 'Body A'));
        $this->assertTrue(MessageManager::messageWasAlreadySent('1', '2', 'Subject A', 'Body A'), 'Ids are cast.');
        $this->assertFalse(MessageManager::messageWasAlreadySent(1, 2, 'Subject B', 'Body A'), 'Other title.');
        $this->assertFalse(MessageManager::messageWasAlreadySent(1, 2, 'Subject A', 'Body B'), 'Other content.');
        $this->assertFalse(MessageManager::messageWasAlreadySent(2, 2, 'Subject A', 'Body A'), 'Other sender.');
        $this->assertFalse(MessageManager::messageWasAlreadySent(1, 3, 'Subject A', 'Body A'), 'Other receiver.');
    }

    public function testMessageWasAlreadySentIgnoresMessagesThatAreNotInbox(): void
    {
        $this->msg(self::BASE + 2, 1, null, null, 4, 'Subject C', 'Body C');
        $this->recipient(self::BASE + 2, 2);

        $this->assertFalse(MessageManager::messageWasAlreadySent(1, 2, 'Subject C', 'Body C'));
    }

    public function testMessageWasAlreadySentCountsAReceiverWhoSoftDeletedTheMessage(): void
    {
        $this->msg(self::BASE + 4, 1, null, null, 1, 'Subject D', 'Body D');
        $this->recipient(self::BASE + 4, 2);
        $this->conn()->executeStatement(
            "UPDATE message_rel_user SET deleted_at = '2030-01-01 00:00:00' WHERE message_id = ?",
            [self::BASE + 4]
        );

        $this->assertTrue(
            MessageManager::messageWasAlreadySent(1, 2, 'Subject D', 'Body D'),
            'deleted_at is not looked at: the announcement e-mail is not sent twice because a user deleted it.'
        );
        $this->assertTrue($this->getEntityManager()->getFilters()->isEnabled('softdeleteable'), 'The filter is restored.');
    }

    public function testMessageWasAlreadySentTreatsQuotesAsData(): void
    {
        $title = "O'Brien \" -- ;";
        $this->msg(self::BASE + 3, 1, null, null, 1, $title, "it's");
        $this->recipient(self::BASE + 3, 2);

        $this->assertTrue(MessageManager::messageWasAlreadySent(1, 2, $title, "it's"));
        $this->assertFalse(MessageManager::messageWasAlreadySent(1, 2, "' OR '1'='1", "' OR '1'='1"));
    }

    // ------------------------------------------------------------------ group messages

    public function testGetMessagesByGroupListsOnlyGroupTypeMessagesOfTheGroupByIdAscending(): void
    {
        $g = $this->group();
        $this->msg(self::BASE + 12, 1, $g, null, 5, 'second', 'x');
        $this->msg(self::BASE + 11, 1, $g, null, 5, 'first', 'x');
        $this->msg(self::BASE + 13, 1, $g, null, 1, 'inbox in group', 'x');
        $this->msg(self::BASE + 14, 1, null, null, 5, 'other group', 'x');

        $rows = MessageManager::get_messages_by_group($g);

        $this->assertSame([self::BASE + 11, self::BASE + 12], array_column($rows, 'id'));
        $this->assertSame(
            [
                'id' => self::BASE + 11,
                'user_sender_id' => 1,
                'group_id' => $g,
                'parent_id' => null,
                'msg_type' => 5,
                'status' => 0,
                'send_date' => '2030-01-02 03:04:05',
                'title' => 'first',
                'content' => 'x',
                'mail_answer' => 0,
                'mail_inbound_id' => null,
                'update_date' => null,
                'votes' => null,
            ],
            $rows[0]
        );
    }

    public function testGetMessagesByGroupWithNoGroupIsFalseAndWithoutMessagesEmpty(): void
    {
        $this->assertFalse(MessageManager::get_messages_by_group(0));
        $this->assertFalse(MessageManager::get_messages_by_group('abc'));
        $this->assertSame([], MessageManager::get_messages_by_group($this->group()));
    }

    public function testGetMessagesByGroupByMessageReturnsTheTreeBelowTheTopicInIdOrder(): void
    {
        $g = $this->group();
        $this->msg(self::BASE + 21, 1, $g, null, 5, 'topic', 'x');
        $this->msg(self::BASE + 22, 1, $g, self::BASE + 21, 5, 'reply', 'x');
        $this->msg(self::BASE + 23, 1, $g, self::BASE + 22, 5, 'reply of reply', 'x');
        $this->msg(self::BASE + 24, 1, $g, null, 5, 'other topic', 'x');
        $this->msg(self::BASE + 25, 1, $g, self::BASE + 24, 5, 'reply to other topic', 'x');
        $this->msg(self::BASE + 26, 1, $g, self::BASE + 21, 1, 'inbox type is skipped', 'x');

        $rows = MessageManager::get_messages_by_group_by_message($g, self::BASE + 21);

        $this->assertSame([self::BASE + 22, self::BASE + 23], array_column($rows, 'id'));
        $this->assertSame(self::BASE + 21, $rows[0]['parent_id']);
        $this->assertSame([self::BASE + 25], array_column(MessageManager::get_messages_by_group_by_message($g, self::BASE + 24), 'id'));
        $this->assertSame([], MessageManager::get_messages_by_group_by_message($g, 1));
    }

    public function testGetMessagesByGroupByMessageWithNoGroupIsFalse(): void
    {
        $this->assertFalse(MessageManager::get_messages_by_group_by_message(0, 1));
    }

    // ------------------------------------------------------------------ get_message_by_id

    public function testGetMessageByIdReturnsTheRowExceptForType3AndPrintsNothing(): void
    {
        // It excludes msg_type 3 (a type no constant names any more). It used to echo its SQL text (debug leftover that
        // polluted the group topic page); the DQL version prints nothing.
        $g = $this->group();
        $this->msg(self::BASE + 31, 1, $g, null, 1, 'inbox', 'x');
        $this->msg(self::BASE + 32, 1, $g, null, 3, 'type 3', 'x');

        ob_start();
        $row = MessageManager::get_message_by_id(self::BASE + 31);
        $echoed = ob_get_clean();

        $this->assertSame(self::BASE + 31, $row['id']);
        $this->assertSame('inbox', $row['title']);
        $this->assertSame(13, \count($row));
        $this->assertSame('', $echoed);

        ob_start();
        $none = MessageManager::get_message_by_id(self::BASE + 32);
        ob_end_clean();
        $this->assertSame([], $none);

        ob_start();
        $missing = MessageManager::get_message_by_id(self::BASE + 99);
        ob_end_clean();
        $this->assertSame([], $missing);
    }

    // ------------------------------------------------------------------ conversations (rewritten: the old SQL read the
    // message.user_receiver_id column, which is gone; recipients live in message_rel_user)

    public function testUsersThatHadConversationWithUserAreTheSendersOfMessagesThatReachedHim(): void
    {
        $five = (int) $this->createUser('mt3_five')->getId();
        $six = (int) $this->createUser('mt3_six')->getId();
        $this->msg(self::BASE + 41, 1, null, null, 1, 'from admin', 'x');
        $this->recipient(self::BASE + 41, $five);
        $this->msg(self::BASE + 42, 1, null, null, 1, 'again from admin', 'x');
        $this->recipient(self::BASE + 42, $five);
        $this->msg(self::BASE + 43, 2, null, null, 1, 'from anon', 'x');
        $this->recipient(self::BASE + 43, $five);
        $this->msg(self::BASE + 44, 3, null, null, 1, 'to someone else', 'x');
        $this->recipient(self::BASE + 44, $six);
        $this->msg(self::BASE + 45, $five, null, null, 1, 'sent by him', 'x');
        $this->recipient(self::BASE + 45, $six);

        $users = MessageManager::getUsersThatHadConversationWithUser($five);

        $this->assertSame([1, 2], array_keys($users), 'One entry per sender, keyed by sender id; unknown senders skipped.');
        $this->assertSame(1, (int) $users[1]['user_id']);
        $this->assertSame([], MessageManager::getUsersThatHadConversationWithUser(99999999));
    }

    public function testGetAllMessagesBetweenStudentsListsBothDirectionsNewestFirst(): void
    {
        $this->msg(self::BASE + 51, 1, null, null, 1, 'oldest', 'x', '2030-01-01 10:00:00');
        $this->recipient(self::BASE + 51, 2);
        $this->msg(self::BASE + 52, 2, null, null, 1, 'reply', 'x', '2030-01-03 10:00:00');
        $this->recipient(self::BASE + 52, 1);
        $this->msg(self::BASE + 53, 1, null, null, 1, 'newer', 'x', '2030-01-02 10:00:00');
        $this->recipient(self::BASE + 53, 2);
        $this->msg(self::BASE + 54, 1, null, null, 1, 'to a third user', 'x', '2030-01-04 10:00:00');
        $this->recipient(self::BASE + 54, 3);
        $this->msg(self::BASE + 55, 3, null, null, 1, 'from a third user', 'x', '2030-01-05 10:00:00');
        $this->recipient(self::BASE + 55, 2);

        $rows = MessageManager::getAllMessagesBetweenStudents(1, 2);

        $this->assertSame([self::BASE + 52, self::BASE + 53, self::BASE + 51], array_column($rows, 'id'));
        $this->assertSame('reply', $rows[0]['title']);
        $this->assertSame('2030-01-03 10:00:00', $rows[0]['send_date']);
        $this->assertSame(2, $rows[0]['user_sender_id']);
        $this->assertSame(
            [self::BASE + 52, self::BASE + 53, self::BASE + 51],
            array_column(MessageManager::getAllMessagesBetweenStudents(2, 1), 'id'),
            'Symmetric.'
        );
        $this->assertSame([], MessageManager::getAllMessagesBetweenStudents(1, 99999999));
    }

    public function testGetAllMessagesBetweenStudentsRejectsEmptyIdsBeforeTheQuery(): void
    {
        $this->assertSame([], MessageManager::getAllMessagesBetweenStudents(0, 2));
        $this->assertSame([], MessageManager::getAllMessagesBetweenStudents(1, 0));
    }

    // ------------------------------------------------------------------ helpers

    private function conn(): Connection
    {
        return $this->getEntityManager()->getConnection();
    }

    private function group(): int
    {
        static $next = 0;
        ++$next;
        $this->conn()->executeStatement(
            "INSERT INTO usergroup (title, description, group_type, picture, url, allow_members_leave_group, created_at, updated_at, visibility, author_id)
             VALUES (?, '', 1, '', '', 1, '2030-01-01 00:00:00', '2030-01-01 00:00:00', 1, 1)",
            ['mt3_group_'.$next.'_'.uniqid()]
        );

        return (int) $this->conn()->lastInsertId();
    }

    private function msg(int $id, int $sender, ?int $group, ?int $parent, int $type, string $title, string $content, string $date = '2030-01-02 03:04:05'): void
    {
        $this->conn()->executeStatement(
            'INSERT INTO message (id, user_sender_id, group_id, parent_id, msg_type, status, send_date, title, content, mail_answer)
             VALUES (?, ?, ?, ?, ?, 0, ?, ?, ?, 0)',
            [$id, $sender, $group, $parent, $type, $date, $title, $content]
        );
    }

    private function recipient(int $messageId, int $userId): void
    {
        $this->conn()->executeStatement(
            'INSERT INTO message_rel_user (message_id, user_id, msg_read, receiver_type, starred) VALUES (?, ?, 0, 1, 0)',
            [$messageId, $userId]
        );
    }
}
