<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\Message;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20200821224243 extends AbstractMigrationChamilo
{
    private const OLD_MESSAGE_STATUS_NEW = 0;
    private const OLD_MESSAGE_STATUS_UNREAD = 1;
    private const OLD_MESSAGE_STATUS_DELETED = 3;
    private const OLD_MESSAGE_STATUS_OUTBOX = 4;
    private const OLD_MESSAGE_STATUS_INVITATION_PENDING = 5;
    private const OLD_MESSAGE_STATUS_INVITATION_ACCEPTED = 6;
    private const OLD_MESSAGE_STATUS_INVITATION_DENIED = 7;
    private const OLD_MESSAGE_STATUS_WALL = 8;
    private const OLD_MESSAGE_STATUS_WALL_DELETE = 9;
    private const OLD_MESSAGE_STATUS_WALL_POST = 10;
    private const OLD_MESSAGE_STATUS_CONVERSATION = 11;
    private const OLD_MESSAGE_STATUS_FORUM = 12;
    private const OLD_MESSAGE_STATUS_PROMOTED = 13;

    public function getDescription(): string
    {
        return 'Post Message update';
    }

    public function up(Schema $schema): void
    {
        $connection = $this->getEntityManager()->getConnection();
        $result = $connection->executeQuery('SELECT * FROM message WHERE user_receiver_id IS NOT NULL');
        $messages = $result->fetchAllAssociative();

        if ($messages) {
            foreach ($messages as $message) {
                $messageId = (int) $message['id'];
                $receiverId = (int) $message['user_receiver_id'];

                $result = $connection->executeQuery(" SELECT * FROM message_rel_user WHERE message_id = $messageId AND user_id = $receiverId");
                $exists = $result->fetchAllAssociative();

                if (empty($exists)) {
                    $sql = "INSERT INTO message_rel_user (message_id, user_id, msg_read, starred, receiver_type) VALUES('$messageId', '$receiverId', 1, 0, 1) ";
                    $this->addSql($sql);
                }
                //$this->addSql("UPDATE message SET user_receiver_id = NULL WHERE id = $messageId");
            }
        }

        $newTypeQueries = [];

        $newTypeQueries[] = sprintf(
            'UPDATE message SET status = %d WHERE msg_type = %d',
            Message::MESSAGE_STATUS_DELETED,
            self::OLD_MESSAGE_STATUS_DELETED
        );
        $newTypeQueries[] = sprintf(
            'UPDATE message SET msg_type = %d WHERE msg_type = %d',
            Message::MESSAGE_TYPE_INBOX, self::OLD_MESSAGE_STATUS_OUTBOX
        );

        $newTypeQueries[] = sprintf(
            'UPDATE message SET status = %d WHERE msg_type = %d',
            Message::MESSAGE_STATUS_INVITATION_PENDING,
            self::OLD_MESSAGE_STATUS_INVITATION_PENDING
        );
        $newTypeQueries[] = sprintf(
            'UPDATE message SET status = %d WHERE msg_type = %d',
            Message::MESSAGE_STATUS_INVITATION_ACCEPTED,
            self::OLD_MESSAGE_STATUS_INVITATION_ACCEPTED
        );
        $newTypeQueries[] = sprintf(
            'UPDATE message SET status = %d WHERE msg_type = %d',
            Message::MESSAGE_STATUS_INVITATION_DENIED,
            self::OLD_MESSAGE_STATUS_INVITATION_DENIED
        );
        $newTypeQueries[] = sprintf(
            'UPDATE message SET msg_type = %d WHERE status IN (%d, %d, %d)',
            Message::MESSAGE_TYPE_INVITATION,
            Message::MESSAGE_STATUS_INVITATION_PENDING,
            Message::MESSAGE_STATUS_INVITATION_ACCEPTED,
            Message::MESSAGE_STATUS_INVITATION_DENIED
        );

        $newTypeQueries[] = sprintf(
            'UPDATE message SET msg_type = %d WHERE msg_type = %d',
            Message::MESSAGE_TYPE_INBOX,
            self::OLD_MESSAGE_STATUS_NEW
        );

        $newTypeQueries[] = sprintf(
            'UPDATE message SET msg_type = %d WHERE group_id IS NOT NULL',
            Message::MESSAGE_TYPE_GROUP
        );

        foreach ($newTypeQueries as $sql) {
            $this->addSql($sql);
        }
    }

    public function down(Schema $schema): void
    {
    }
}
