<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

class Version20180928172830 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Migrate c_tool';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('c_tool');
        if (false === $table->hasForeignKey('FK_8456658091D79BD3')) {
            $this->addSql(
                'ALTER TABLE c_tool ADD CONSTRAINT FK_8456658091D79BD3 FOREIGN KEY (c_id) REFERENCES course (id)'
            );
        }
        $this->addSql('ALTER TABLE c_tool CHANGE link link VARCHAR(255) DEFAULT NULL;');
        $this->addSql('UPDATE c_tool SET name = "blog" WHERE name = "blog_management" ');
        $this->addSql('UPDATE c_tool SET name = "agenda" WHERE name = "calendar_event" ');
        $this->addSql('UPDATE c_tool SET name = "member" WHERE link = "user/user.php" ');
        $this->addSql('UPDATE c_tool SET name = "course_description/index.php" WHERE link = "course_description/" ');

        //$this->addSql('UPDATE c_tool SET name = "maintenance" WHERE name = "course_maintenance" ');
        //$this->addSql('UPDATE c_tool SET name = "assignment" WHERE name = "student_publication" ');
        //$this->addSql('UPDATE c_tool SET name = "settings" WHERE name = "course_setting" ');

        if (!$table->hasColumn('tool_id')) {
            $this->addSql('ALTER TABLE c_tool ADD tool_id INT NOT NULL');
        }
        if (!$table->hasColumn('position')) {
            $this->addSql('ALTER TABLE c_tool ADD position INT NOT NULL');
        }

        if ($table->hasColumn('id')) {
            $this->addSql('ALTER TABLE c_tool DROP id');
        }
        if ($table->hasColumn('image')) {
            $this->addSql('ALTER TABLE c_tool DROP image');
        }
        if ($table->hasColumn('address')) {
            $this->addSql('ALTER TABLE c_tool DROP address');
        }
        if ($table->hasColumn('added_tool')) {
            $this->addSql('ALTER TABLE c_tool DROP added_tool');
        }
        if ($table->hasColumn('target')) {
            $this->addSql('ALTER TABLE c_tool DROP target');
        }
        if ($table->hasColumn('description')) {
            $this->addSql('ALTER TABLE c_tool DROP description');
        }
        if ($table->hasColumn('custom_icon')) {
            $this->addSql('ALTER TABLE c_tool DROP custom_icon');
        }

        $this->addSql('UPDATE c_tool SET session_id = NULL WHERE session_id = 0');

        // Delete c_tool not registered in tool. @todo migrate BBB/LP/mobidico plugins
        $this->addSql('DELETE FROM c_tool WHERE name NOT IN (SELECT name FROM tool)');
        $this->addSql('UPDATE c_tool SET tool_id = (SELECT id FROM tool WHERE name = c_tool.name) WHERE tool_id IS NOT NULL');

        if (!$table->hasColumn('resource_node_id')) {
            $this->addSql('ALTER TABLE c_tool ADD resource_node_id BIGINT DEFAULT NULL');
        }

        // @todo remove/move LP/Link shortcuts.
        $this->addSql('DELETE FROM c_tool WHERE tool_id = 0 OR tool_id IS NULL');
        $this->addSql('DELETE FROM c_tool WHERE session_id IS NOT NULL AND session_id NOT IN (SELECT id FROM session)');

        if (!$table->hasForeignKey('FK_84566580613FECDF')) {
            $this->addSql(
                'ALTER TABLE c_tool ADD CONSTRAINT FK_84566580613FECDF FOREIGN KEY (session_id) REFERENCES session (id)'
            );
        }

        if (!$table->hasForeignKey('FK_845665808F7B22CC')) {
            $this->addSql('ALTER TABLE c_tool ADD CONSTRAINT FK_845665808F7B22CC FOREIGN KEY (tool_id) REFERENCES tool (id)');
        }

        if (!$table->hasForeignKey('FK_845665801BAD783F')) {
            $this->addSql(
                'ALTER TABLE c_tool ADD CONSTRAINT FK_845665801BAD783F FOREIGN KEY (resource_node_id) REFERENCES resource_node (id) ON DELETE CASCADE'
            );
        }

        if (!$table->hasIndex('IDX_845665808F7B22CC')) {
            $this->addSql('CREATE INDEX IDX_845665808F7B22CC ON c_tool (tool_id)');
        }

        if (!$table->hasIndex('UNIQ_845665801BAD783F')) {
            $this->addSql('CREATE UNIQUE INDEX UNIQ_845665801BAD783F ON c_tool (resource_node_id)');
        }
    }

    public function down(Schema $schema): void
    {
    }
}
