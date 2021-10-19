<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

class Version20170625143000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'c_thematic changes';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('c_thematic');
        if (false === $table->hasColumn('resource_node_id')) {
            $this->addSql('ALTER TABLE c_thematic ADD resource_node_id BIGINT DEFAULT NULL');
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
            $this->addSql('ALTER TABLE c_thematic_advance ADD resource_node_id BIGINT DEFAULT NULL');

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
            $this->addSql('ALTER TABLE c_thematic_plan ADD resource_node_id BIGINT DEFAULT NULL');

            $this->addSql('ALTER TABLE c_thematic_plan ADD CONSTRAINT FK_1197487C1BAD783F FOREIGN KEY (resource_node_id) REFERENCES resource_node (id) ON DELETE CASCADE');
            $this->addSql('CREATE UNIQUE INDEX UNIQ_1197487C1BAD783F ON c_thematic_plan (resource_node_id)');
        }*/

        // CLink
        $table = $schema->getTable('c_link');
        if (false === $table->hasColumn('resource_node_id')) {
            $this->addSql('ALTER TABLE c_link ADD resource_node_id BIGINT DEFAULT NULL;');
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

        $this->addSql('ALTER TABLE c_link CHANGE on_homepage on_homepage VARCHAR(10) DEFAULT NULL');
        $this->addSql('UPDATE c_link SET category_id = NULL WHERE category_id = 0');

        if (false === $table->hasForeignKey('FK_9209C2A012469DE2')) {
            $this->addSql(
                'ALTER TABLE c_link ADD CONSTRAINT FK_9209C2A012469DE2 FOREIGN KEY (category_id) REFERENCES c_link_category (iid) ON DELETE SET NULL'
            );
            $this->addSql('CREATE INDEX IDX_9209C2A012469DE2 ON c_link (category_id)');
        }

        $table = $schema->getTable('c_link_category');
        if (false === $table->hasColumn('resource_node_id')) {
            $this->addSql('ALTER TABLE c_link_category ADD resource_node_id BIGINT DEFAULT NULL');
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
            $this->addSql('ALTER TABLE c_glossary ADD resource_node_id BIGINT DEFAULT NULL');
            $this->addSql(
                'ALTER TABLE c_glossary ADD CONSTRAINT FK_A1168D881BAD783F FOREIGN KEY (resource_node_id) REFERENCES resource_node (id) ON DELETE CASCADE'
            );
            $this->addSql('CREATE UNIQUE INDEX UNIQ_A1168D881BAD783F ON c_glossary (resource_node_id)');
        }
    }

    public function down(Schema $schema): void
    {
    }
}
