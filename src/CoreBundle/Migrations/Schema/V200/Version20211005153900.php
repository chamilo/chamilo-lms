<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

class Version20211005153900 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'ticket changes';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('ticket_message_attachments');

        if (false === $table->hasColumn('resource_node_id')) {
            $this->addSql('ALTER TABLE ticket_message_attachments ADD resource_node_id BIGINT DEFAULT NULL;');
            $this->addSql(
                'ALTER TABLE ticket_message_attachments ADD CONSTRAINT FK_70BF9E261BAD783F FOREIGN KEY (resource_node_id) REFERENCES resource_node (id) ON DELETE CASCADE;'
            );
            $this->addSql(
                'CREATE UNIQUE INDEX UNIQ_70BF9E261BAD783F ON ticket_message_attachments (resource_node_id);'
            );
        }
    }
}
