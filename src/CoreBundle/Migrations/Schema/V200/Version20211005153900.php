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
        // Assigned users
        $table = $schema->getTable('ticket_ticket');
        $this->addSql(
            'UPDATE ticket_ticket SET assigned_last_user = NULL WHERE assigned_last_user NOT IN (SELECT id FROM user)'
        );

        if (false === $table->hasForeignKey('FK_EDE2C7686219A7B7')) {
            $this->addSql(
                'ALTER TABLE ticket_ticket ADD CONSTRAINT FK_EDE2C7686219A7B7 FOREIGN KEY (assigned_last_user) REFERENCES user (id)'
            );
        }

        if (false === $table->hasIndex('IDX_EDE2C7686219A7B7')) {
            $this->addSql('CREATE INDEX IDX_EDE2C7686219A7B7 ON ticket_ticket (assigned_last_user)');
        }

        // Attachments
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
