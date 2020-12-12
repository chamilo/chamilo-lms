<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * c_forum.
 */
class Version20170625153000 extends AbstractMigrationChamilo
{
    public function up(Schema $schema): void
    {
        $table = $schema->getTable('c_forum_attachment');
        $this->addSql('ALTER TABLE c_forum_attachment CHANGE post_id post_id INT DEFAULT NULL');

        if (false === $table->hasColumn('resource_node_id')) {
            $this->addSql('ALTER TABLE c_forum_attachment ADD resource_node_id INT DEFAULT NULL');
            $this->addSql(
                'ALTER TABLE c_forum_attachment ADD CONSTRAINT FK_F1113A884B89032C FOREIGN KEY (post_id) REFERENCES c_forum_post (iid) ON DELETE CASCADE'
            );
            $this->addSql(
                'ALTER TABLE c_forum_attachment ADD CONSTRAINT FK_F1113A881BAD783F FOREIGN KEY (resource_node_id) REFERENCES resource_node (id) ON DELETE CASCADE'
            );
            $this->addSql('CREATE UNIQUE INDEX UNIQ_F1113A881BAD783F ON c_forum_attachment (resource_node_id)');

            $this->addSql('CREATE INDEX IDX_F1113A884B89032C ON c_forum_attachment (post_id)');
        }
        $table = $schema->getTable('c_forum_category');

        if (false === $table->hasColumn('resource_node_id')) {
            $this->addSql('ALTER TABLE c_forum_category ADD resource_node_id INT DEFAULT NULL');
            $this->addSql(
                'ALTER TABLE c_forum_category ADD CONSTRAINT FK_D627B86E1BAD783F FOREIGN KEY (resource_node_id) REFERENCES resource_node (id) ON DELETE CASCADE'
            );
            $this->addSql('CREATE UNIQUE INDEX UNIQ_D627B86E1BAD783F ON c_forum_category (resource_node_id)');
        }

        $table = $schema->getTable('c_forum_forum');
        if (false === $table->hasColumn('resource_node_id')) {
            $this->addSql('ALTER TABLE c_forum_forum ADD resource_node_id INT DEFAULT NULL, DROP forum_id');
            $this->addSql(
                'ALTER TABLE c_forum_forum ADD CONSTRAINT FK_47A9C991BAD783F FOREIGN KEY (resource_node_id) REFERENCES resource_node (id) ON DELETE CASCADE'
            );
            $this->addSql(
                'ALTER TABLE c_forum_forum ADD CONSTRAINT FK_47A9C9921BF9426 FOREIGN KEY (forum_category) REFERENCES c_forum_category (iid) ON DELETE SET NULL'
            );
            $this->addSql('CREATE UNIQUE INDEX UNIQ_47A9C991BAD783F ON c_forum_forum (resource_node_id)');
        }

        $this->addSql('ALTER TABLE c_forum_forum DROP FOREIGN KEY FK_47A9C9921BF9426');

        $table = $schema->getTable('c_forum_thread');
        if ($table->hasForeignKey('FK_5DA7884C29CCBAD0')) {
            $this->addSql('ALTER TABLE c_forum_thread DROP FOREIGN KEY FK_5DA7884C29CCBAD0');
        }

        if (false === $table->hasColumn('resource_node_id')) {
            $this->addSql('ALTER TABLE c_forum_thread ADD resource_node_id INT DEFAULT NULL, DROP thread_id');
            $this->addSql(
                'ALTER TABLE c_forum_thread ADD CONSTRAINT FK_5DA7884C1BAD783F FOREIGN KEY (resource_node_id) REFERENCES resource_node (id) ON DELETE CASCADE'
            );
            $this->addSql(
                'ALTER TABLE c_forum_thread ADD CONSTRAINT FK_5DA7884C29CCBAD0 FOREIGN KEY (forum_id) REFERENCES c_forum_forum (iid) ON DELETE SET NULL'
            );
            $this->addSql('CREATE UNIQUE INDEX UNIQ_5DA7884C1BAD783F ON c_forum_thread (resource_node_id)');
        }
        //$this->addSql('ALTER TABLE c_forum_thread_qualify DROP id');
        //$this->addSql('ALTER TABLE c_forum_thread_qualify_log DROP id');

        $table = $schema->getTable('c_forum_post');
        if ($table->hasForeignKey('FK_B5BEF559E2904019')) {
            $this->addSql('ALTER TABLE c_forum_post DROP FOREIGN KEY FK_B5BEF559E2904019');
        }

        if (false === $table->hasColumn('resource_node_id')) {
            $this->addSql('ALTER TABLE c_forum_post ADD resource_node_id INT DEFAULT NULL');
            $this->addSql(
                'ALTER TABLE c_forum_post ADD CONSTRAINT FK_B5BEF55929CCBAD0 FOREIGN KEY (forum_id) REFERENCES c_forum_forum (iid) ON DELETE SET NULL'
            );
            $this->addSql(
                'ALTER TABLE c_forum_post ADD CONSTRAINT FK_B5BEF5591BAD783F FOREIGN KEY (resource_node_id) REFERENCES resource_node (id) ON DELETE CASCADE'
            );
            $this->addSql(
                'ALTER TABLE c_forum_post ADD CONSTRAINT FK_B5BEF559E2904019 FOREIGN KEY (thread_id) REFERENCES c_forum_thread (iid) ON DELETE SET NULL'
            );
            $this->addSql('CREATE UNIQUE INDEX UNIQ_B5BEF5591BAD783F ON c_forum_post (resource_node_id)');
        }

        $table = $schema->getTable('c_course_description');
        if (false === $table->hasColumn('resource_node_id')) {
            $this->addSql('ALTER TABLE c_course_description ADD resource_node_id INT DEFAULT NULL');
            $this->addSql('ALTER TABLE c_course_description ADD CONSTRAINT FK_EC3CD8091BAD783F FOREIGN KEY (resource_node_id) REFERENCES resource_node (id) ON DELETE CASCADE');
            $this->addSql('CREATE UNIQUE INDEX UNIQ_EC3CD8091BAD783F ON c_course_description (resource_node_id)');
        }

        $table = $schema->getTable('c_notebook');

        if ($table->hasIndex('course')) {
            $this->addSql('DROP INDEX course ON c_notebook');
        }

        if (false === $table->hasColumn('resource_node_id')) {
            $this->addSql('ALTER TABLE c_notebook ADD resource_node_id INT DEFAULT NULL, DROP notebook_id');
            $this->addSql('ALTER TABLE c_notebook ADD CONSTRAINT FK_E7EE1CE01BAD783F FOREIGN KEY (resource_node_id) REFERENCES resource_node (id) ON DELETE CASCADE');
            $this->addSql('CREATE UNIQUE INDEX UNIQ_E7EE1CE01BAD783F ON c_notebook (resource_node_id)');
        }
    }
}
