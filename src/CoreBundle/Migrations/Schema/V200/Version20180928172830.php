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

        $this->addSql('UPDATE c_tool SET title = "blog" WHERE title = "blog_management" ');
        $this->addSql('UPDATE c_tool SET title = "agenda" WHERE title = "calendar_event" ');
        $this->addSql('UPDATE c_tool SET title = "member" WHERE link = "user/user.php" ');
        $this->addSql('UPDATE c_tool SET title = "course_description/index.php" WHERE link = "course_description/" ');

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

        if ($table->hasColumn('link')) {
            $lpTools = $this->connection
                ->prepare("SELECT c_id, session_id, link FROM c_tool WHERE link LIKE '%lp_controller.php%'")
                ->executeQuery()
                ->fetchAllAssociative()
            ;

            $this->writeFile('tool_links', serialize($lpTools));

            $this->addSql('ALTER TABLE c_tool DROP link');
        }

        if ($table->hasColumn('category')) {
            $this->addSql('ALTER TABLE c_tool DROP category');
        }

        if ($table->hasColumn('admin')) {
            $this->addSql('ALTER TABLE c_tool DROP admin');
        }

        if (!$table->hasColumn('tool_id')) {
            $this->addSql('ALTER TABLE c_tool ADD tool_id INT NOT NULL');
        }

        if (!$table->hasColumn('position')) {
            $this->addSql('ALTER TABLE c_tool ADD position INT NOT NULL');
        }

        $this->addSql('DELETE FROM c_tool WHERE c_id NOT IN (SELECT id FROM course)');
        $this->addSql('ALTER TABLE c_tool CHANGE c_id c_id INT NOT NULL');

        if (!$table->hasForeignKey('FK_8456658091D79BD3')) {
            $this->addSql(
                'ALTER TABLE c_tool ADD CONSTRAINT FK_8456658091D79BD3 FOREIGN KEY (c_id) REFERENCES course (id)'
            );
        }

        $this->addSql('UPDATE c_tool SET session_id = NULL WHERE session_id = 0');
        $this->addSql('DELETE FROM c_tool WHERE session_id IS NOT NULL AND session_id NOT IN (SELECT id FROM session)');

        if (!$table->hasForeignKey('FK_84566580613FECDF')) {
            $this->addSql(
                'ALTER TABLE c_tool ADD CONSTRAINT FK_84566580613FECDF FOREIGN KEY (session_id) REFERENCES session (id) ON DELETE CASCADE'
            );
        }

        // Delete c_tool not registered in tool. @todo migrate BBB/LP/mobidico plugins
        $this->addSql('DELETE FROM c_tool WHERE title NOT IN (SELECT title FROM tool)');
        $this->addSql('UPDATE c_tool SET tool_id = (SELECT id FROM tool WHERE title = c_tool.title) WHERE tool_id IS NOT NULL');

        // @todo remove/move LP/Link shortcuts.
        $this->addSql('DELETE FROM c_tool WHERE tool_id = 0 OR tool_id IS NULL');

        if (!$table->hasForeignKey('FK_845665808F7B22CC')) {
            $this->addSql('ALTER TABLE c_tool ADD CONSTRAINT FK_845665808F7B22CC FOREIGN KEY (tool_id) REFERENCES tool (id)');
        }

        if (!$table->hasIndex('IDX_845665808F7B22CC')) {
            $this->addSql('CREATE INDEX IDX_845665808F7B22CC ON c_tool (tool_id)');
        }

        if (!$table->hasColumn('resource_node_id')) {
            $this->addSql('ALTER TABLE c_tool ADD resource_node_id INT DEFAULT NULL');
        }

        if (!$table->hasForeignKey('FK_845665801BAD783F')) {
            $this->addSql(
                'ALTER TABLE c_tool ADD CONSTRAINT FK_845665801BAD783F FOREIGN KEY (resource_node_id) REFERENCES resource_node (id) ON DELETE CASCADE'
            );
        }

        if (!$table->hasIndex('UNIQ_845665801BAD783F')) {
            $this->addSql('CREATE UNIQUE INDEX UNIQ_845665801BAD783F ON c_tool (resource_node_id)');
        }
    }

    public function down(Schema $schema): void {}
}
