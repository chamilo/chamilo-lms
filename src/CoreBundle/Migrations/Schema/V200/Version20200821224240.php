<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20200821224240 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Delete duplicated messages with msg_status = 4 and their attachments before restructuring message table';
    }

    public function up(Schema $schema): void
    {
        // Select the IDs of duplicated messages with msg_status = 4
        $sqlSelectMessages = '
            SELECT DISTINCT(m1.id) AS message_id
            FROM message m1
            JOIN message m2
                ON m1.user_sender_id = m2.user_sender_id
                AND m1.user_receiver_id = m2.user_receiver_id
                AND m1.send_date = m2.send_date
                AND m1.title = m2.title
                AND m1.content = m2.content
                AND m1.msg_status = 4
                AND m1.id != m2.id
        ';

        // Delete attachments related to the duplicated messages
        $sqlDeleteAttachments = "
            DELETE FROM message_attachment
            WHERE message_id IN ($sqlSelectMessages)
        ";

        // Delete duplicated messages with msg_status = 4
        $sqlDeleteMessages = "
            DELETE FROM message
            WHERE id IN ($sqlSelectMessages)
        ";

        // Execute delete queries
        $this->addSql($sqlDeleteAttachments);
        $this->addSql($sqlDeleteMessages);
    }

    public function down(Schema $schema): void
    {
        // No need to implement down method since this migration is non-reversible
    }
}
