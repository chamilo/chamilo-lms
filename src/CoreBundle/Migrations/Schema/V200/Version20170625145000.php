<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * c_calendar_event.
 */
class Version20170625145000 extends AbstractMigrationChamilo
{
    public function up(Schema $schema): void
    {
        $table = $schema->getTable('c_calendar_event');
        if (false === $table->hasColumn('resource_node_id')) {
            $this->addSql('ALTER TABLE c_calendar_event ADD resource_node_id INT DEFAULT NULL');
            $this->addSql(
                'ALTER TABLE c_calendar_event ADD CONSTRAINT FK_A0622581EE3A445A FOREIGN KEY (parent_event_id) REFERENCES c_calendar_event (iid)'
            );
            $this->addSql(
                'ALTER TABLE c_calendar_event ADD CONSTRAINT FK_A06225811BAD783F FOREIGN KEY (resource_node_id) REFERENCES resource_node (id) ON DELETE CASCADE'
            );
            $this->addSql('CREATE INDEX IDX_A0622581EE3A445A ON c_calendar_event (parent_event_id)');
            $this->addSql('CREATE UNIQUE INDEX UNIQ_A06225811BAD783F ON c_calendar_event (resource_node_id)');
        }

        if ($table->hasIndex('course')) {
            $this->addSql('DROP INDEX course ON c_calendar_event');
        }

        if ($table->hasIndex('session_id')) {
            $this->addSql('DROP INDEX session_id ON c_calendar_event');
        }

        $table = $schema->getTable('c_calendar_event_attachment');
        if ($table->hasIndex('course')) {
            $this->addSql('DROP INDEX course ON c_calendar_event_attachment');
        }

        $this->addSql('ALTER TABLE c_calendar_event_attachment CHANGE agenda_id agenda_id INT DEFAULT NULL');

        if (false === $table->hasColumn('resource_node_id')) {
            $this->addSql('ALTER TABLE c_calendar_event_attachment ADD resource_node_id INT DEFAULT NULL');
            $this->addSql(
                'ALTER TABLE c_calendar_event_attachment ADD CONSTRAINT FK_DDD745A6EA67784A FOREIGN KEY (agenda_id) REFERENCES c_calendar_event (iid) ON DELETE CASCADE'
            );
            $this->addSql(
                'ALTER TABLE c_calendar_event_attachment ADD CONSTRAINT FK_DDD745A61BAD783F FOREIGN KEY (resource_node_id) REFERENCES resource_node (id) ON DELETE CASCADE'
            );
            $this->addSql('CREATE INDEX IDX_DDD745A6EA67784A ON c_calendar_event_attachment (agenda_id)');
            $this->addSql(
                'CREATE UNIQUE INDEX UNIQ_DDD745A61BAD783F ON c_calendar_event_attachment (resource_node_id)'
            );
        }

        $table = $schema->getTable('c_calendar_event_repeat');
        $this->addSql('ALTER TABLE c_calendar_event_repeat CHANGE cal_id cal_id INT DEFAULT NULL');
        if (false === $table->hasForeignKey('FK_86FD1CA87300D633')) {
            $this->addSql(
                'ALTER TABLE c_calendar_event_repeat ADD CONSTRAINT FK_86FD1CA87300D633 FOREIGN KEY (cal_id) REFERENCES c_calendar_event (iid)'
            );
        }
        if (false === $table->hasIndex('IDX_86FD1CA87300D633')) {
            $this->addSql('CREATE INDEX IDX_86FD1CA87300D633 ON c_calendar_event_repeat (cal_id)');
        }

        if ($table->hasIndex('course')) {
            $this->addSql('DROP INDEX course ON c_calendar_event_repeat');
        }

        $table = $schema->getTable('c_calendar_event_repeat_not');

        if ($table->hasIndex('course')) {
            $this->addSql('DROP INDEX course ON c_calendar_event_repeat_not');
        }

        if (false === $table->hasForeignKey('FK_7D4436947300D633')) {
            $this->addSql(
                'ALTER TABLE c_calendar_event_repeat_not ADD CONSTRAINT FK_7D4436947300D633 FOREIGN KEY (cal_id) REFERENCES c_calendar_event (iid)'
            );
        }

        $this->addSql('ALTER TABLE c_calendar_event_repeat_not CHANGE cal_id cal_id INT DEFAULT NULL');

        if (false === $table->hasIndex('IDX_7D4436947300D633')) {
            $this->addSql('CREATE INDEX IDX_7D4436947300D633 ON c_calendar_event_repeat_not (cal_id)');
        }
    }

    public function down(Schema $schema): void
    {
    }
}
