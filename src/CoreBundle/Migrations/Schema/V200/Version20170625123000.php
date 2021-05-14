<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

class Version20170625123000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'c_attendance changes';
    }

    public function up(Schema $schema): void
    {
        $em = $this->getEntityManager();
        $container = $this->getContainer();

        // CAttendance
        $table = $schema->getTable('c_attendance');
        if ($table->hasIndex('course')) {
            $this->addSql('DROP INDEX course ON c_attendance');
        }
        if ($table->hasIndex('session_id')) {
            $this->addSql('DROP INDEX session_id ON c_attendance');
        }

        $this->addSql('ALTER TABLE c_attendance CHANGE active active INT NOT NULL');

        if (false === $table->hasColumn('resource_node_id')) {
            $this->addSql('ALTER TABLE c_attendance ADD resource_node_id INT DEFAULT NULL');
            $this->addSql('ALTER TABLE c_attendance ADD CONSTRAINT FK_413634921BAD783F FOREIGN KEY (resource_node_id) REFERENCES resource_node (id) ON DELETE CASCADE');
            $this->addSql('CREATE UNIQUE INDEX UNIQ_413634921BAD783F ON c_attendance (resource_node_id)');
        }

        $table = $schema->getTable('c_attendance_calendar');
        if ($table->hasIndex('course')) {
            $this->addSql('DROP INDEX course ON c_attendance_calendar');
        }

        if ($table->hasIndex('attendance_id')) {
            $this->addSql('DROP INDEX attendance_id ON c_attendance_calendar');
        }

        $this->addSql('ALTER TABLE c_attendance_calendar CHANGE attendance_id attendance_id INT DEFAULT NULL;');

        if (false === $table->hasForeignKey('FK_AA3A9AB8163DDA15')) {
            $this->addSql('ALTER TABLE c_attendance_calendar ADD CONSTRAINT FK_AA3A9AB8163DDA15 FOREIGN KEY (attendance_id) REFERENCES c_attendance (iid);');
        }

        if (false === $table->hasIndex('IDX_AA3A9AB8163DDA15')) {
            $this->addSql('CREATE INDEX IDX_AA3A9AB8163DDA15 ON c_attendance_calendar (attendance_id);');
        }

        $table = $schema->getTable('c_attendance_sheet');

        if ($table->hasIndex('course')) {
            $this->addSql('DROP INDEX course ON c_attendance_sheet;');
        }

        if ($table->hasIndex('user')) {
            $this->addSql('DROP INDEX user ON c_attendance_sheet;');
        }

        $this->addSql('UPDATE c_attendance_sheet SET attendance_calendar_id = NULL WHERE attendance_calendar_id = 0;');
        $this->addSql('UPDATE c_attendance_sheet SET attendance_calendar_id = NULL WHERE attendance_calendar_id = 0;');
        $this->addSql('ALTER TABLE c_attendance_sheet CHANGE user_id user_id INT DEFAULT NULL, CHANGE attendance_calendar_id attendance_calendar_id INT DEFAULT NULL');

        //ALTER TABLE c_attendance_sheet DROP c_id

        $this->addSql('DELETE FROM c_attendance_sheet WHERE user_id NOT IN (SELECT id FROM user)');
        if (false === $table->hasForeignKey('FK_AD1394FAA76ED395')) {
            $this->addSql('ALTER TABLE c_attendance_sheet ADD CONSTRAINT FK_AD1394FAA76ED395 FOREIGN KEY (user_id) REFERENCES user (id);');
        }

        if (false === $table->hasForeignKey('FK_AD1394FA19EA43C3')) {
            $this->addSql('ALTER TABLE c_attendance_sheet ADD CONSTRAINT FK_AD1394FA19EA43C3 FOREIGN KEY (attendance_calendar_id) REFERENCES c_attendance_calendar (iid);');
        }

        if (false === $table->hasIndex('IDX_AD1394FA19EA43C3')) {
            $this->addSql('CREATE INDEX IDX_AD1394FA19EA43C3 ON c_attendance_sheet (attendance_calendar_id);');
        }

        if (false === $table->hasIndex('IDX_AD1394FAA76ED395')) {
            $this->addSql('CREATE INDEX IDX_AD1394FAA76ED395 ON c_attendance_sheet (user_id);');
        }

        $table = $schema->getTable('c_attendance_sheet_log');

        if ($table->hasIndex('course')) {
            $this->addSql('DROP INDEX course ON c_attendance_sheet_log;');
        }

        if ($table->hasIndex('user')) {
            $this->addSql('DROP INDEX user ON c_attendance_sheet;');
        }

        $this->addSql('DELETE FROM c_attendance_sheet_log WHERE attendance_id = 0');
        $this->addSql('DELETE FROM c_attendance_sheet_log WHERE lastedit_user_id = 0');

        $this->addSql('ALTER TABLE c_attendance_sheet_log CHANGE attendance_id attendance_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE c_attendance_sheet_log CHANGE lastedit_user_id lastedit_user_id INT DEFAULT NULL');

        if (!$table->hasForeignKey('FK_181D0917163DDA15')) {
            $this->addSql(
                'ALTER TABLE c_attendance_sheet_log ADD CONSTRAINT FK_181D0917163DDA15 FOREIGN KEY (attendance_id) REFERENCES c_attendance (iid) ON DELETE CASCADE'
            );
        }
        if (!$table->hasForeignKey('FK_181D091731BA5DD')) {
            $this->addSql(
                'ALTER TABLE c_attendance_sheet_log ADD CONSTRAINT FK_181D091731BA5DD FOREIGN KEY (lastedit_user_id) REFERENCES user (id) ON DELETE CASCADE'
            );
        }
        if (!$table->hasIndex('IDX_181D0917163DDA15')) {
            $this->addSql('CREATE INDEX IDX_181D0917163DDA15 ON c_attendance_sheet_log (attendance_id)');
        }
        if (!$table->hasIndex('IDX_181D091731BA5DD')) {
            $this->addSql('CREATE INDEX IDX_181D091731BA5DD ON c_attendance_sheet_log (lastedit_user_id)');
        }

        $table = $schema->getTable('c_attendance_result');

        if ($table->hasIndex('course')) {
            $this->addSql('DROP INDEX course ON c_attendance_result');
        }

        if ($table->hasIndex('user_id')) {
            $this->addSql('DROP INDEX user_id ON c_attendance_result');
        }

        if ($table->hasIndex('attendance_id')) {
            $this->addSql('DROP INDEX attendance_id ON c_attendance_result;');
        }

        $this->addSql('UPDATE c_attendance_result SET attendance_id = NULL WHERE attendance_id = 0');
        $this->addSql('UPDATE c_attendance_result SET user_id = NULL WHERE user_id = 0');

        $this->addSql('DELETE FROM c_attendance_result WHERE user_id NOT IN (SELECT id FROM user)');

        //ALTER TABLE c_attendance_result DROP c_id, ;
        $this->addSql('ALTER TABLE c_attendance_result CHANGE user_id user_id INT DEFAULT NULL, CHANGE attendance_id attendance_id INT DEFAULT NULL ');

        //ALTER TABLE c_attendance_sheet DROP c_id

        if (false === $table->hasForeignKey('FK_2C7640A76ED395')) {
            $this->addSql('ALTER TABLE c_attendance_result ADD CONSTRAINT FK_2C7640A76ED395 FOREIGN KEY (user_id) REFERENCES user (id);');
        }
        if (false === $table->hasForeignKey('FK_2C7640163DDA15')) {
            $this->addSql('ALTER TABLE c_attendance_result ADD CONSTRAINT FK_2C7640163DDA15 FOREIGN KEY (attendance_id) REFERENCES c_attendance (iid);');
        }
        if (false === $table->hasIndex('IDX_2C7640A76ED395')) {
            $this->addSql('CREATE INDEX IDX_2C7640A76ED395 ON c_attendance_result (user_id);');
        }

        if (false === $table->hasIndex('IDX_2C7640163DDA15')) {
            $this->addSql('CREATE INDEX IDX_2C7640163DDA15 ON c_attendance_result (attendance_id);');
        }

        $table = $schema->getTable('c_attendance_calendar_rel_group');

        if ($table->hasIndex('course')) {
            $this->addSql('DROP INDEX course ON c_attendance_calendar_rel_group');
        }

        //ALTER TABLE c_attendance_calendar_rel_group DROP c_id,
        $this->addSql('UPDATE c_attendance_calendar_rel_group SET group_id = NULL WHERE group_id = 0');
        $this->addSql('UPDATE c_attendance_calendar_rel_group SET calendar_id = NULL WHERE calendar_id = 0');
        $this->addSql('ALTER TABLE c_attendance_calendar_rel_group CHANGE group_id group_id INT DEFAULT NULL, CHANGE calendar_id calendar_id INT DEFAULT NULL;');

        if (false === $table->hasForeignKey('FK_C2AB1FACFE54D947')) {
            if ($table->hasIndex('group')) {
                $this->addSql('DROP INDEX `group` ON c_attendance_calendar_rel_group');
            }
            $this->addSql('ALTER TABLE c_attendance_calendar_rel_group ADD CONSTRAINT FK_C2AB1FACFE54D947 FOREIGN KEY (group_id) REFERENCES c_group_info (iid);');
        }

        if (false === $table->hasForeignKey('FK_C2AB1FACA40A2C8')) {
            $this->addSql('ALTER TABLE c_attendance_calendar_rel_group ADD CONSTRAINT FK_C2AB1FACA40A2C8 FOREIGN KEY (calendar_id) REFERENCES c_attendance_calendar (iid);');
        }

        if (false === $table->hasIndex('IDX_C2AB1FACA40A2C8')) {
            $this->addSql('CREATE INDEX IDX_C2AB1FACA40A2C8 ON c_attendance_calendar_rel_group (calendar_id);');
        }

        if (false === $table->hasIndex('IDX_C2AB1FACFE54D947')) {
            $this->addSql('CREATE INDEX IDX_C2AB1FACFE54D947 ON c_attendance_calendar_rel_group (group_id);');
        }
    }

    public function down(Schema $schema): void
    {
    }
}
