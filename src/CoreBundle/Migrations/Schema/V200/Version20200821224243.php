<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20200821224243 extends AbstractMigrationChamilo
{
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
    }

    public function down(Schema $schema): void
    {
    }
}
