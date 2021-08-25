<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

class Version20170625153000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Migrate c_forum tables';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('c_forum_attachment');
        $this->addSql('ALTER TABLE c_forum_attachment CHANGE post_id post_id INT DEFAULT NULL');

        if (false === $table->hasColumn('resource_node_id')) {
            $this->addSql('ALTER TABLE c_forum_attachment ADD resource_node_id BIGINT DEFAULT NULL');
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
            $this->addSql('ALTER TABLE c_forum_category ADD resource_node_id BIGINT DEFAULT NULL');
            $this->addSql(
                'ALTER TABLE c_forum_category ADD CONSTRAINT FK_D627B86E1BAD783F FOREIGN KEY (resource_node_id) REFERENCES resource_node (id) ON DELETE CASCADE'
            );
            $this->addSql('CREATE UNIQUE INDEX UNIQ_D627B86E1BAD783F ON c_forum_category (resource_node_id)');
        }

        if ($table->hasIndex('session_id')) {
            $this->addSql('DROP INDEX session_id ON c_forum_category;');
        }

        if ($table->hasIndex('course')) {
            $this->addSql('DROP INDEX course ON c_forum_category;');
        }

        $table = $schema->getTable('c_forum_forum');
        if (!$table->hasColumn('resource_node_id')) {
            $this->addSql('ALTER TABLE c_forum_forum ADD resource_node_id BIGINT DEFAULT NULL, DROP forum_id');
            $this->addSql(
                'ALTER TABLE c_forum_forum ADD CONSTRAINT FK_47A9C991BAD783F FOREIGN KEY (resource_node_id) REFERENCES resource_node (id) ON DELETE CASCADE'
            );
            /*$this->addSql(
                'ALTER TABLE c_forum_forum ADD CONSTRAINT FK_47A9C9921BF9426 FOREIGN KEY (forum_category) REFERENCES c_forum_category (iid) ON DELETE SET NULL'
            );*/
            $this->addSql('CREATE UNIQUE INDEX UNIQ_47A9C991BAD783F ON c_forum_forum (resource_node_id)');
        }

        if ($table->hasIndex('course')) {
            $this->addSql('DROP INDEX course ON c_forum_forum;');
        }

        if ($table->hasIndex('FK_47A9C9968DFD1EF')) {
            $this->addSql('ALTER TABLE c_forum_forum DROP INDEX FK_47A9C9968DFD1EF');
        }

        $this->addSql('ALTER TABLE c_forum_forum CHANGE lp_id lp_id INT DEFAULT NULL');
        $this->addSql('UPDATE c_forum_forum SET lp_id = NULL WHERE lp_id = 0');

        /*if (false === $table->hasIndex('UNIQ_47A9C9968DFD1EF')) {
            $this->addSql('ALTER TABLE c_forum_forum ADD UNIQUE INDEX UNIQ_47A9C9968DFD1EF (lp_id)');
        }*/

        /*if ($table->hasForeignKey('FK_47A9C9921BF9426')) {
            $this->addSql('ALTER TABLE c_forum_forum DROP FOREIGN KEY FK_47A9C9921BF9426');
        }*/

        $table = $schema->getTable('c_forum_thread');
        if ($table->hasForeignKey('FK_5DA7884C29CCBAD0')) {
            $this->addSql('ALTER TABLE c_forum_thread DROP FOREIGN KEY FK_5DA7884C29CCBAD0');
        }

        $this->addSql('ALTER TABLE c_forum_thread CHANGE lp_item_id lp_item_id INT DEFAULT NULL');
        if ($table->hasForeignKey('FK_5DA7884CDBF72317')) {
            $this->addSql(
                'ALTER TABLE c_forum_thread ADD CONSTRAINT FK_5DA7884CDBF72317 FOREIGN KEY (lp_item_id) REFERENCES c_lp_item (iid) ON DELETE CASCADE'
            );
        }

        if (!$table->hasIndex('IDX_5DA7884CDBF72317')) {
            $this->addSql('CREATE INDEX IDX_5DA7884CDBF72317 ON c_forum_thread (lp_item_id)');
        }

        $this->addSql('UPDATE c_forum_thread SET thread_date = NOW() WHERE thread_date is NULL OR thread_date = 0');
        $this->addSql('ALTER TABLE c_forum_thread CHANGE thread_date thread_date DATETIME NOT NULL');

        if (false === $table->hasColumn('resource_node_id')) {
            $this->addSql('ALTER TABLE c_forum_thread ADD resource_node_id BIGINT DEFAULT NULL, DROP thread_id');
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
        $table = $schema->getTable('c_forum_thread_qualify');

        $this->addSql('DELETE FROM c_forum_thread_qualify WHERE user_id = 0');
        $this->addSql('DELETE FROM c_forum_thread_qualify WHERE thread_id = 0');

        $this->addSql('ALTER TABLE c_forum_thread_qualify CHANGE user_id user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE c_forum_thread_qualify CHANGE thread_id thread_id INT DEFAULT NULL');

        if (!$table->hasForeignKey('FK_715FC3A5A76ED395')) {
            $this->addSql('ALTER TABLE c_forum_thread_qualify ADD CONSTRAINT FK_715FC3A5A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        }
        if (!$table->hasForeignKey('FK_715FC3A5E2904019')) {
            $this->addSql(
                'ALTER TABLE c_forum_thread_qualify ADD CONSTRAINT FK_715FC3A5E2904019 FOREIGN KEY (thread_id) REFERENCES c_forum_thread (iid) ON DELETE CASCADE'
            );
        }
        if (!$table->hasForeignKey('FK_715FC3A5E5E1B95C')) {
            $this->addSql(
                'ALTER TABLE c_forum_thread_qualify ADD CONSTRAINT FK_715FC3A5E5E1B95C FOREIGN KEY (qualify_user_id) REFERENCES user (id) ON DELETE CASCADE'
            );
        }

        if (!$table->hasIndex('IDX_715FC3A5A76ED395')) {
            $this->addSql('CREATE INDEX IDX_715FC3A5A76ED395 ON c_forum_thread_qualify (user_id)');
        }
        if (!$table->hasIndex('IDX_715FC3A5E2904019')) {
            $this->addSql('CREATE INDEX IDX_715FC3A5E2904019 ON c_forum_thread_qualify (thread_id)');
        }
        if (!$table->hasIndex('IDX_715FC3A5E5E1B95C')) {
            $this->addSql('CREATE INDEX IDX_715FC3A5E5E1B95C ON c_forum_thread_qualify (qualify_user_id)');
        }

        $table = $schema->getTable('c_forum_post');
        if ($table->hasForeignKey('FK_B5BEF559E2904019')) {
            $this->addSql('ALTER TABLE c_forum_post DROP FOREIGN KEY FK_B5BEF559E2904019');
        }

        $this->addSql('UPDATE c_forum_post SET post_parent_id = NULL WHERE post_parent_id = 0');

        if (!$table->hasForeignKey('FK_B5BEF559D314B487')) {
            $this->addSql('ALTER TABLE c_forum_post ADD CONSTRAINT FK_B5BEF559D314B487 FOREIGN KEY (post_parent_id) REFERENCES c_forum_post (iid) ON DELETE SET NULL');
        }

        if (!$table->hasIndex('IDX_B5BEF559D314B487')) {
            $this->addSql('CREATE INDEX IDX_B5BEF559D314B487 ON c_forum_post (post_parent_id)');
        }

        if ($table->hasIndex('c_id_visible_post_date')) {
            $this->addSql('DROP INDEX c_id_visible_post_date ON c_forum_post');
        }

        if ($table->hasIndex('course')) {
            $this->addSql('DROP INDEX course ON c_forum_post');
        }

        if (false === $table->hasColumn('resource_node_id')) {
            $this->addSql('ALTER TABLE c_forum_post ADD resource_node_id BIGINT DEFAULT NULL');
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

        $this->addSql('UPDATE c_forum_post SET post_date = NOW() WHERE post_date is NULL OR post_date = 0');
        $this->addSql('ALTER TABLE c_forum_post CHANGE post_date post_date DATETIME NOT NULL');
    }
}
