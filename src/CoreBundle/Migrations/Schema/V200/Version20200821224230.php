<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\Schema;

final class Version20200821224230 extends AbstractMigrationChamilo
{
    public const INBOX_TAGS_FILE = 'inbox_message_tags';

    public function getDescription(): string
    {
        return 'Prepare data to migrate message tags from extra fields.';
    }

    public function up(Schema $schema): void
    {
        $this->prepareTagsFromInboxMessages();
    }

    /**
     * @throws Exception
     */
    private function prepareTagsFromInboxMessages(): void
    {
        // Tags from inbox message
        $resMessageTag = $this->connection->executeQuery(
            "SELECT m.id AS m_id, m.user_receiver_id AS m_receiver_id, t.id AS t_id, t.tag AS t_tag
            FROM message m
            INNER JOIN extra_field_rel_tag efrt ON m.id = efrt.item_id
            INNER JOIN extra_field ef ON efrt.field_id = ef.id
            INNER JOIN tag t ON (efrt.tag_id = t.id AND ef.id = t.field_id)
            WHERE m.msg_status = 0
            AND ef.item_type = ".ExtraField::MESSAGE_TYPE." AND ef.variable = 'tags'"
        );
        $oldMessageTagInfo = $resMessageTag->fetchAllAssociative();

        $this->writeFile(self::INBOX_TAGS_FILE, serialize($oldMessageTagInfo));
    }
}