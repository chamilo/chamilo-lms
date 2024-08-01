<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\MessageRelUser;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240731120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add entries in message_rel_user for the sender during migration and update existing messages.';
    }

    public function up(Schema $schema): void
    {
        $senderType = MessageRelUser::TYPE_SENDER;

        // Add entries for the sender in message_rel_user
        $this->addSql("
            INSERT INTO message_rel_user (message_id, user_id, receiver_type, msg_read, starred)
            SELECT m.id, m.user_sender_id, $senderType, false, false
            FROM message m
            LEFT JOIN message_rel_user mru
            ON m.id = mru.message_id
            AND m.user_sender_id = mru.user_id
            WHERE mru.id IS NULL
        ");

        // Update message status based on message_rel_user entries
        $this->addSql("
            UPDATE message m
            LEFT JOIN (
                SELECT message_id, COUNT(*) AS rel_count
                FROM message_rel_user
                WHERE receiver_type = 1
                GROUP BY message_id
            ) AS mru ON m.id = mru.message_id
            SET m.status = CASE
                WHEN mru.rel_count IS NULL THEN 3 -- Message::MESSAGE_STATUS_DELETED
                ELSE 0 -- Set to 0 or whatever the default status should be
            END
        ");
    }

    public function down(Schema $schema): void
    {
        $senderType = MessageRelUser::TYPE_SENDER;

        // Remove the entries added during the migration
        $this->addSql("
            DELETE FROM message_rel_user
            WHERE receiver_type = $senderType
        ");
    }
}
