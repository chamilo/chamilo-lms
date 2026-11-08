<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Repository\Node;

use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\Tests\ChamiloTestTrait;
use Chamilo\Tests\CoreBundle\Service\RawReportingFixturesTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * UserRepository::deleteUserMessagesAndAttachments(), the raw-DBAL step of the hard deletion of a user
 * (deleteUser($user, true), reached after "unify users" and from the admin hard delete).
 * Users are raw "rp_" rows, everything runs inside the DAMA transaction.
 */
final class UserRepositoryMessagesTest extends KernelTestCase
{
    use ChamiloTestTrait;
    use RawReportingFixturesTrait;

    private UserRepository $repo;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->repo = self::getContainer()->get(UserRepository::class);
    }

    private function message(?int $senderId, string $content): int
    {
        $this->conn()->executeStatement(
            "INSERT INTO message (user_sender_id, msg_type, status, send_date, title, content, mail_answer) VALUES (?, 1, 0, '2020-01-01 00:00:00', 'sv3', ?, 0)",
            [$senderId, $content]
        );

        return (int) $this->conn()->lastInsertId();
    }

    private function attachment(int $messageId, string $filename): void
    {
        $this->conn()->executeStatement('INSERT INTO message_attachment (message_id, path, size, filename) VALUES (?, ?, 1, ?)', [$messageId, 'sv3/'.$filename, $filename]);
    }

    private function filenames(int $messageId): array
    {
        return $this->conn()->fetchFirstColumn('SELECT filename FROM message_attachment WHERE message_id = ? ORDER BY filename', [$messageId]);
    }

    public function testMessagesOfTheUserAreBlankedDetachedAndTheirAttachmentsDeleted(): void
    {
        $user = $this->rawUser('sender');
        $m1 = $this->message($user, 'secret one');
        $m2 = $this->message($user, 'secret two');
        $this->attachment($m1, 'a.txt');
        $this->attachment($m1, 'b.txt');
        $this->attachment($m2, 'c.txt');

        $this->repo->deleteUserMessagesAndAttachments($this->entityUser($user));

        foreach ([$m1, $m2] as $id) {
            $row = $this->conn()->fetchAssociative('SELECT user_sender_id, content FROM message WHERE id = ?', [$id]);
            $this->assertNull($row['user_sender_id']);
            $this->assertStringNotContainsString('secret', $row['content']);
            $this->assertNotSame('', $row['content']);
            $this->assertSame([], $this->filenames($id));
        }
    }

    public function testMessagesAndAttachmentsOfOtherSendersAreUntouched(): void
    {
        $user = $this->rawUser('sender');
        $other = $this->rawUser('other');
        $mine = $this->message($user, 'mine');
        $theirs = $this->message($other, 'theirs');
        $this->attachment($mine, 'mine.txt');
        $this->attachment($theirs, 'theirs.txt');

        $this->repo->deleteUserMessagesAndAttachments($this->entityUser($user));

        $this->assertSame($other, (int) $this->conn()->fetchOne('SELECT user_sender_id FROM message WHERE id = ?', [$theirs]));
        $this->assertSame('theirs', $this->conn()->fetchOne('SELECT content FROM message WHERE id = ?', [$theirs]));
        $this->assertSame(['theirs.txt'], $this->filenames($theirs));
    }

    public function testAttachmentsOfMessagesWithoutSenderAreNotDeletedByAnotherUsersRemoval(): void
    {
        // A message that already has no sender (earlier removal, system message) keeps its attachments when an
        // unrelated user is removed. The statement used to delete every attachment of every sender-less message.
        $user = $this->rawUser('sender');
        $orphan = $this->message(null, 'orphan');
        $this->attachment($orphan, 'orphan.txt');
        $this->attachment($this->message($user, 'mine'), 'mine.txt');

        $this->repo->deleteUserMessagesAndAttachments($this->entityUser($user));

        $this->assertSame(['orphan.txt'], $this->filenames($orphan));
    }
}
