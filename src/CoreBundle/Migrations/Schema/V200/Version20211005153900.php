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

        $this->addSql('UPDATE ticket_ticket SET session_id = NULL WHERE session_id = 0');
        $this->addSql('UPDATE ticket_ticket SET course_id = NULL WHERE course_id = 0');

        $this->addSql('DELETE FROM ticket_ticket WHERE session_id IS NOT NULL AND session_id NOT IN (SELECT id FROM session)');
        $this->addSql('DELETE FROM ticket_ticket WHERE course_id IS NOT NULL AND course_id NOT IN (SELECT id FROM course)');

        if (!$table->hasForeignKey('FK_EDE2C7686219A7B7')) {
            $this->addSql(
                'ALTER TABLE ticket_ticket ADD CONSTRAINT FK_EDE2C7686219A7B7 FOREIGN KEY (assigned_last_user) REFERENCES user (id) ON DELETE CASCADE'
            );
        }

        if (!$table->hasForeignKey('FK_EDE2C768591CC992')) {
            $this->addSql('ALTER TABLE ticket_ticket ADD CONSTRAINT FK_EDE2C768591CC992 FOREIGN KEY (course_id) REFERENCES course (id) ON DELETE CASCADE;');
        }

        if (!$table->hasForeignKey('FK_EDE2C768613FECDF')) {
            $this->addSql('ALTER TABLE ticket_ticket ADD CONSTRAINT FK_EDE2C768613FECDF FOREIGN KEY (session_id) REFERENCES session (id) ON DELETE CASCADE;');
        }

        if (false === $table->hasIndex('IDX_EDE2C7686219A7B7')) {
            $this->addSql('CREATE INDEX IDX_EDE2C7686219A7B7 ON ticket_ticket (assigned_last_user)');
        }

        $table = $schema->getTable('ticket_assigned_log');
        if (!$table->hasForeignKey('FK_54B65868700047D2')) {
            $this->addSql('ALTER TABLE ticket_assigned_log ADD CONSTRAINT FK_54B65868700047D2 FOREIGN KEY (ticket_id) REFERENCES ticket_ticket (id) ON DELETE CASCADE;');
        }

        $table = $schema->getTable('ticket_message');
        if (!$table->hasForeignKey('FK_BA71692D700047D2')) {
            $this->addSql('ALTER TABLE ticket_message ADD CONSTRAINT FK_BA71692D700047D2 FOREIGN KEY (ticket_id) REFERENCES ticket_ticket (id) ON DELETE CASCADE;');
        }

        // Attachments
        $table = $schema->getTable('ticket_message_attachments');

        if (!$table->hasForeignKey('FK_70BF9E26537A1329')) {
            $this->addSql('ALTER TABLE ticket_message_attachments ADD CONSTRAINT FK_70BF9E26537A1329 FOREIGN KEY (message_id) REFERENCES ticket_message (id) ON DELETE CASCADE;');
        }

        if (!$table->hasForeignKey('FK_70BF9E26700047D2')) {
            $this->addSql('ALTER TABLE ticket_message_attachments ADD CONSTRAINT FK_70BF9E26700047D2 FOREIGN KEY (ticket_id) REFERENCES ticket_ticket (id) ON DELETE CASCADE;');
        }

        if (!$table->hasColumn('resource_node_id')) {
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
