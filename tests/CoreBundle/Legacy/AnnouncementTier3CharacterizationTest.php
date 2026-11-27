<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use AnnouncementManager;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\Tests\ChamiloTestTrait;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Characterization tests for AnnouncementManager::get_attachment() (AnnouncementManager.php).
 *
 * The other raw-SQL helpers of the class are dead or broken: the remaining Database::query() calls live in
 * commented-out blocks. get_attachment() is read by the announcements listing to
 * show the attachment icon, and by nothing else.
 */
final class AnnouncementTier3CharacterizationTest extends KernelTestCase
{
    use ChamiloTestTrait;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        Container::setContainer($container);
        Container::setLegacyServices($container);
    }

    public function testGetAttachmentReturnsTheRawColumnsOfTheAttachmentOfAnAnnouncement(): void
    {
        $announcementId = $this->announcement();
        $attachmentId = $this->attachment($announcementId, 'p-one', 'one.txt', 'first comment');

        $row = AnnouncementManager::get_attachment($announcementId);

        $this->assertSame(
            [
                'iid' => $attachmentId,
                'path' => 'p-one',
                'filename' => 'one.txt',
                'comment' => 'first comment',
            ],
            $row
        );
        $this->assertSame(['iid', 'path', 'filename', 'comment'], array_keys($row));
    }

    public function testGetAttachmentKeepsANullCommentAsNullAndAcceptsANumericString(): void
    {
        $announcementId = $this->announcement();
        $this->attachment($announcementId, 'p-null', 'null.txt', null);

        $row = AnnouncementManager::get_attachment((string) $announcementId);

        $this->assertNull($row['comment']);
        $this->assertSame('null.txt', $row['filename']);
    }

    public function testGetAttachmentIsEmptyWhenTheAnnouncementHasNoAttachmentOrDoesNotExist(): void
    {
        $announcementId = $this->announcement();
        $other = $this->announcement();
        $this->attachment($other, 'p-other', 'other.txt', 'x');

        $this->assertSame([], AnnouncementManager::get_attachment($announcementId));
        $this->assertSame([], AnnouncementManager::get_attachment(2147483000));
    }

    public function testGetAttachmentOnlyReadsTheAttachmentOfTheRequestedAnnouncement(): void
    {
        $a = $this->announcement();
        $b = $this->announcement();
        $this->attachment($a, 'p-a', 'a.txt', 'ca');
        $this->attachment($b, 'p-b', 'b.txt', 'cb');

        $this->assertSame('a.txt', AnnouncementManager::get_attachment($a)['filename']);
        $this->assertSame('b.txt', AnnouncementManager::get_attachment($b)['filename']);
    }

    public function testGetAttachmentReturnsOnlyTheFirstAttachmentWhenThereAreSeveral(): void
    {
        $announcementId = $this->announcement();
        $this->attachment($announcementId, 'p-first', 'first.txt', 'c1');
        $this->attachment($announcementId, 'p-second', 'second.txt', 'c2');

        $this->assertSame('first.txt', AnnouncementManager::get_attachment($announcementId)['filename']);
    }

    private function conn(): Connection
    {
        return $this->getEntityManager()->getConnection();
    }

    private function announcement(): int
    {
        $this->conn()->executeStatement(
            "INSERT INTO c_announcement (title, content) VALUES ('an_t3', 'content')"
        );

        return (int) $this->conn()->lastInsertId();
    }

    private function attachment(int $announcementId, string $path, string $filename, ?string $comment): int
    {
        $this->conn()->executeStatement(
            'INSERT INTO c_announcement_attachment (announcement_id, path, comment, size, filename) VALUES (?, ?, ?, 1, ?)',
            [$announcementId, $path, $comment, $filename]
        );

        return (int) $this->conn()->lastInsertId();
    }
}
