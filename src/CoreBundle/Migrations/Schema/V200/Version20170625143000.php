<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * c_thematic.
 */
class Version20170625143000 extends AbstractMigrationChamilo
{
    public function up(Schema $schema): void
    {
        $table = $schema->getTable('c_thematic');
        if (false === $table->hasColumn('resource_node_id')) {
            $this->addSql('ALTER TABLE c_thematic ADD resource_node_id INT DEFAULT NULL');
            $this->addSql(
                'ALTER TABLE c_thematic ADD CONSTRAINT FK_6D8F59B91BAD783F FOREIGN KEY (resource_node_id) REFERENCES resource_node (id) ON DELETE CASCADE'
            );
            $this->addSql('CREATE UNIQUE INDEX UNIQ_6D8F59B91BAD783F ON c_thematic (resource_node_id)');
        }

        if ($table->hasIndex('course')) {
            $this->addSql('DROP INDEX course ON c_thematic');
        }
        if ($table->hasIndex('active')) {
            $this->addSql('DROP INDEX active ON c_thematic');
        }

        if ($table->hasColumn('c_id')) {
            //$this->addSql('ALTER TABLE c_thematic DROP c_id');
        }

        if ($table->hasColumn('session_id')) {
            //$this->addSql('ALTER TABLE c_thematic DROP session_id');
        }

        if ($table->hasIndex('active')) {
            $this->addSql('CREATE INDEX active ON c_thematic (active);');
        }

        $table = $schema->getTable('c_thematic_advance');
        if ($table->hasIndex('course')) {
            $this->addSql('DROP INDEX course ON c_thematic_advance');
        }

        if ($table->hasColumn('c_id')) {
            //$this->addSql('ALTER TABLE c_thematic_advance DROP c_id;');
        }

        if ($table->hasIndex('thematic_id')) {
            $this->addSql('DROP INDEX thematic_id ON c_thematic_advance');
        }

        if (false === $table->hasIndex('IDX_62798E972395FCED')) {
            $this->addSql('CREATE INDEX IDX_62798E972395FCED ON c_thematic_advance (thematic_id)');
        }

        $this->addSql(
            'ALTER TABLE c_thematic_advance CHANGE thematic_id thematic_id INT DEFAULT NULL, CHANGE attendance_id attendance_id INT DEFAULT NULL'
        );

        if (false === $table->hasForeignKey('FK_62798E972395FCED')) {
            $this->addSql(
                'ALTER TABLE c_thematic_advance ADD CONSTRAINT FK_62798E972395FCED FOREIGN KEY (thematic_id) REFERENCES c_thematic (iid)'
            );
        }
        if (false === $table->hasForeignKey('FK_62798E97163DDA15')) {
            $this->addSql(
                'ALTER TABLE c_thematic_advance ADD CONSTRAINT FK_62798E97163DDA15 FOREIGN KEY (attendance_id) REFERENCES c_attendance (iid)'
            );
        }
        if (false === $table->hasIndex('IDX_62798E97163DDA15')) {
            $this->addSql('CREATE INDEX IDX_62798E97163DDA15 ON c_thematic_advance (attendance_id);');
        }

        /*
        if (false === $table->hasColumn('resource_node_id')) {
            $this->addSql('ALTER TABLE c_thematic_advance ADD resource_node_id INT DEFAULT NULL');

            //$this->addSql('ALTER TABLE c_thematic_advance ADD CONSTRAINT FK_62798E971BAD783F FOREIGN KEY (resource_node_id) REFERENCES resource_node (id) ON DELETE CASCADE');
            $this->addSql('CREATE UNIQUE INDEX UNIQ_62798E971BAD783F ON c_thematic_advance (resource_node_id)');
        }*/

        $table = $schema->getTable('c_thematic_plan');
        $this->addSql('ALTER TABLE c_thematic_plan CHANGE thematic_id thematic_id INT DEFAULT NULL');

        if ($table->hasIndex('course')) {
            $this->addSql('DROP INDEX course ON c_thematic_plan');
        }

        if ($table->hasColumn('c_id')) {
            //$this->addSql('ALTER TABLE c_thematic_plan DROP c_id;');
        }
        if (false === $table->hasForeignKey('FK_1197487C2395FCED')) {
            $this->addSql(
                'ALTER TABLE c_thematic_plan ADD CONSTRAINT FK_1197487C2395FCED FOREIGN KEY (thematic_id) REFERENCES c_thematic (iid)'
            );
        }

        if (false === $table->hasIndex('IDX_1197487C2395FCED')) {
            $this->addSql('CREATE INDEX IDX_1197487C2395FCED ON c_thematic_plan (thematic_id)');
        }

        /*
        if (false === $table->hasColumn('resource_node_id')) {
            $this->addSql('ALTER TABLE c_thematic_plan ADD resource_node_id INT DEFAULT NULL');

            $this->addSql('ALTER TABLE c_thematic_plan ADD CONSTRAINT FK_1197487C1BAD783F FOREIGN KEY (resource_node_id) REFERENCES resource_node (id) ON DELETE CASCADE');
            $this->addSql('CREATE UNIQUE INDEX UNIQ_1197487C1BAD783F ON c_thematic_plan (resource_node_id)');
        }*/

        // CLink
        $table = $schema->getTable('c_link');
        if (false === $table->hasColumn('resource_node_id')) {
            $this->addSql('ALTER TABLE c_link ADD resource_node_id INT DEFAULT NULL;');
            $this->addSql(
                'ALTER TABLE c_link ADD CONSTRAINT FK_9209C2A01BAD783F FOREIGN KEY (resource_node_id) REFERENCES resource_node (id) ON DELETE CASCADE;'
            );
            $this->addSql('CREATE UNIQUE INDEX UNIQ_9209C2A01BAD783F ON c_link (resource_node_id);');
        }

        if ($table->hasIndex('session_id')) {
            $this->addSql('DROP INDEX session_id ON c_link');
        }
        if ($table->hasIndex('course')) {
            $this->addSql('DROP INDEX course ON c_link');
        }

        $this->addSql('UPDATE c_link SET category_id = NULL WHERE category_id = 0');
        if (false === $table->hasForeignKey('FK_9209C2A012469DE2')) {
            $this->addSql(
                'ALTER TABLE c_link ADD CONSTRAINT FK_9209C2A012469DE2 FOREIGN KEY (category_id) REFERENCES c_link_category (iid)'
            );
            $this->addSql('CREATE INDEX IDX_9209C2A012469DE2 ON c_link (category_id)');
        }

        $table = $schema->getTable('c_link_category');
        if (false === $table->hasColumn('resource_node_id')) {
            $this->addSql('ALTER TABLE c_link_category ADD resource_node_id INT DEFAULT NULL');
            $this->addSql(
                'ALTER TABLE c_link_category ADD CONSTRAINT FK_319D6C9C1BAD783F FOREIGN KEY (resource_node_id) REFERENCES resource_node (id) ON DELETE CASCADE'
            );
            $this->addSql('CREATE UNIQUE INDEX UNIQ_319D6C9C1BAD783F ON c_link_category (resource_node_id)');
        }
        if ($table->hasIndex('session_id')) {
            $this->addSql('DROP INDEX session_id ON c_link_category');
        }
        if ($table->hasIndex('course')) {
            $this->addSql('DROP INDEX course ON c_link_category');
        }

        $table = $schema->getTable('c_glossary');

        if ($table->hasIndex('course')) {
            $this->addSql('DROP INDEX course ON c_glossary');
        }

        if ($table->hasIndex('session_id')) {
            $this->addSql('DROP INDEX session_id ON c_glossary');
        }

        if (false === $table->hasColumn('resource_node_id')) {
            $this->addSql('ALTER TABLE c_glossary ADD resource_node_id INT DEFAULT NULL');
            $this->addSql(
                'ALTER TABLE c_glossary ADD CONSTRAINT FK_A1168D881BAD783F FOREIGN KEY (resource_node_id) REFERENCES resource_node (id) ON DELETE CASCADE'
            );
            $this->addSql('CREATE UNIQUE INDEX UNIQ_A1168D881BAD783F ON c_glossary (resource_node_id)');
        }

        $table = $schema->getTable('c_student_publication');

        $this->addSql('UPDATE c_student_publication SET user_id = NULL WHERE user_id = 0');
        $this->addSql('ALTER TABLE c_student_publication CHANGE user_id user_id INT DEFAULT NULL');

        if (false === $table->hasForeignKey('FK_5246F746A76ED395')) {
            $this->addSql('ALTER TABLE c_student_publication ADD CONSTRAINT FK_5246F746A76ED395 FOREIGN KEY (user_id) REFERENCES user (id);');
        }

        if ($table->hasIndex('idx_csp_u')) {
            $this->addSql('DROP INDEX idx_csp_u ON c_student_publication');
        }

        if (false === $table->hasIndex('IDX_5246F746A76ED395')) {
            $this->addSql('CREATE INDEX IDX_5246F746A76ED395 ON c_student_publication (user_id);');
        }

        $this->addSql('UPDATE c_student_publication SET parent_id = NULL WHERE parent_id = 0');
        $this->addSql('ALTER TABLE c_student_publication CHANGE parent_id parent_id INT DEFAULT NULL');

        if (false === $table->hasForeignKey('FK_5246F746727ACA70')) {
            $this->addSql('ALTER TABLE c_student_publication ADD CONSTRAINT FK_5246F746727ACA70 FOREIGN KEY (parent_id) REFERENCES c_student_publication (iid);');
        }

        if (false === $table->hasIndex('IDX_5246F746727ACA70')) {
            $this->addSql('CREATE INDEX IDX_5246F746727ACA70 ON c_student_publication (parent_id)');
        }

        if (false === $table->hasColumn('filesize')) {
            $this->addSql('ALTER TABLE c_student_publication ADD filesize INT DEFAULT NULL');
        }

        if ($table->hasForeignKey('FK_5246F746613FECDF')) {
            $this->addSql('ALTER TABLE c_student_publication DROP FOREIGN KEY FK_5246F746613FECDF;');
        }

        if ($table->hasIndex('course')) {
            $this->addSql('DROP INDEX course ON c_student_publication;');
        }

        $this->addSql('ALTER TABLE c_student_publication CHANGE url url VARCHAR(500) DEFAULT NULL');
        $this->addSql(
            'ALTER TABLE c_student_publication CHANGE url_correction url_correction VARCHAR(500) DEFAULT NULL'
        );
        $this->addSql('ALTER TABLE c_student_publication CHANGE active active INT DEFAULT NULL');

        if (false === $table->hasColumn('resource_node_id')) {
            $this->addSql('ALTER TABLE c_student_publication ADD resource_node_id INT DEFAULT NULL');
            $this->addSql(
                'ALTER TABLE c_student_publication ADD CONSTRAINT FK_5246F7461BAD783F FOREIGN KEY (resource_node_id) REFERENCES resource_node (id) ON DELETE CASCADE'
            );
            $this->addSql('CREATE UNIQUE INDEX UNIQ_5246F7461BAD783F ON c_student_publication (resource_node_id)');
        }

        $table = $schema->getTable('c_student_publication_assignment');
        /*
        if (false === $table->hasColumn('resource_node_id')) {
            $this->addSql('ALTER TABLE c_student_publication_assignment ADD resource_node_id INT DEFAULT NULL');
            $this->addSql(
                'ALTER TABLE c_student_publication_assignment ADD CONSTRAINT FK_25687EB81BAD783F FOREIGN KEY (resource_node_id) REFERENCES resource_node (id) ON DELETE CASCADE'
            );
            $this->addSql(
                'CREATE UNIQUE INDEX UNIQ_25687EB81BAD783F ON c_student_publication_assignment (resource_node_id)'
            );
        }
        */

        $this->addSql('UPDATE c_student_publication_assignment SET publication_id = NULL WHERE publication_id = 0');
        $this->addSql('ALTER TABLE c_student_publication_assignment CHANGE publication_id publication_id INT DEFAULT NULL');

        if (false === $table->hasForeignKey('FK_25687EB838B217A7')) {
            $this->addSql('ALTER TABLE c_student_publication_assignment ADD CONSTRAINT FK_25687EB838B217A7 FOREIGN KEY (publication_id) REFERENCES c_student_publication (iid) ON DELETE CASCADE;');
        }

        if (false === $table->hasIndex('UNIQ_25687EB838B217A7')) {
            $this->addSql('ALTER TABLE c_student_publication_assignment ADD UNIQUE INDEX UNIQ_25687EB838B217A7 (publication_id)');
        }

        if (false === $schema->hasTable('c_student_publication_correction')) {
            $this->addSql(
                'CREATE TABLE c_student_publication_correction (id INT AUTO_INCREMENT NOT NULL, resource_node_id INT DEFAULT NULL, title VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_B7309BBA1BAD783F (resource_node_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC'
            );
            $this->addSql(
                'ALTER TABLE c_student_publication_correction ADD CONSTRAINT FK_B7309BBA1BAD783F FOREIGN KEY (resource_node_id) REFERENCES resource_node (id) ON DELETE CASCADE;'
            );
        }

        $table = $schema->getTable('c_student_publication_comment');

        if (false === $table->hasColumn('resource_node_id')) {
            $this->addSql('ALTER TABLE c_student_publication_comment ADD resource_node_id INT DEFAULT NULL');
            $this->addSql(
                'ALTER TABLE c_student_publication_comment ADD CONSTRAINT FK_35C509F61BAD783F FOREIGN KEY (resource_node_id) REFERENCES resource_node (id) ON DELETE CASCADE'
            );
            $this->addSql(
                'CREATE UNIQUE INDEX UNIQ_35C509F61BAD783F ON c_student_publication_comment (resource_node_id)'
            );
        }

        if ($table->hasIndex('course')) {
            $this->addSql('DROP INDEX course ON c_student_publication_comment');
        }

        $this->addSql('UPDATE c_student_publication_comment SET work_id = NULL WHERE work_id = 0');
        $this->addSql('UPDATE c_student_publication_comment SET user_id = NULL WHERE user_id = 0');

        $this->addSql('ALTER TABLE c_student_publication_comment CHANGE work_id work_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE c_student_publication_comment CHANGE user_id user_id INT DEFAULT NULL;');

        if (!$table->hasForeignKey('FK_35C509F6BB3453DB')) {
            $this->addSql('ALTER TABLE c_student_publication_comment ADD CONSTRAINT FK_35C509F6BB3453DB FOREIGN KEY (work_id) REFERENCES c_student_publication (iid) ON DELETE CASCADE;');
        }

        if (!$table->hasForeignKey('FK_35C509F6A76ED395')) {
            $this->addSql('ALTER TABLE c_student_publication_comment ADD CONSTRAINT FK_35C509F6A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE;');
        }

        if ($table->hasIndex('work')) {
            $this->addSql('DROP INDEX work ON c_student_publication_comment;');
        }

        if ($table->hasIndex('user')) {
            $this->addSql('DROP INDEX user ON c_student_publication_comment;');
        }

        if (!$table->hasIndex('IDX_35C509F6BB3453DB')) {
            $this->addSql('CREATE INDEX IDX_35C509F6BB3453DB ON c_student_publication_comment (work_id);');
        }

        if (!$table->hasIndex('IDX_35C509F6A76ED395')) {
            $this->addSql('CREATE INDEX IDX_35C509F6A76ED395 ON c_student_publication_comment (user_id);');
        }

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
