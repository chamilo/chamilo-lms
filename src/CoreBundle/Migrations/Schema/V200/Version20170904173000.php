<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

class Version20170904173000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'c_group_info changes';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('c_group_info');

        $this->addSql('ALTER TABLE c_group_info CHANGE doc_state doc_state INT NOT NULL, CHANGE calendar_state calendar_state INT NOT NULL, CHANGE work_state work_state INT NOT NULL');
        $this->addSql('ALTER TABLE c_group_info CHANGE announcements_state announcements_state INT NOT NULL, CHANGE forum_state forum_state INT NOT NULL, CHANGE wiki_state wiki_state INT NOT NULL, CHANGE chat_state chat_state INT NOT NULL;');

        if ($table->hasIndex('session_id')) {
            $this->addSql('DROP INDEX session_id ON c_group_info');
        }
        if ($table->hasIndex('course')) {
            $this->addSql('DROP INDEX course ON c_group_info');
        }
        if (!$table->hasColumn('document_access')) {
            $this->addSql('ALTER TABLE c_group_info ADD document_access INT DEFAULT 0 NOT NULL;');
        }

        if (false === $table->hasColumn('resource_node_id')) {
            $this->addSql('ALTER TABLE c_group_info ADD resource_node_id BIGINT DEFAULT NULL');
            $this->addSql('ALTER TABLE c_group_info ADD CONSTRAINT FK_CE0653241BAD783F FOREIGN KEY (resource_node_id) REFERENCES resource_node (id) ON DELETE CASCADE');
            $this->addSql('CREATE UNIQUE INDEX UNIQ_CE0653241BAD783F ON c_group_info (resource_node_id)');
        }

        if (false === $table->hasIndex('IDX_CE06532412469DE2')) {
            $this->addSql('CREATE INDEX IDX_CE06532412469DE2 ON c_group_info (category_id)');
        }

        $this->addSql('ALTER TABLE c_group_info CHANGE category_id category_id INT DEFAULT NULL');

        $table = $schema->getTable('c_group_rel_user');

        $this->addSql('DELETE FROM c_group_rel_user WHERE user_id NOT IN (SELECT id FROM user)');
        $this->addSql('DELETE FROM c_group_rel_user WHERE group_id NOT IN (SELECT iid FROM c_group_info)');
        if (false === $table->hasForeignKey('FK_C5D3D49FA76ED395')) {
            $this->addSql(
                'ALTER TABLE c_group_rel_user ADD CONSTRAINT FK_C5D3D49FA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)'
            );
        }

        if (false === $table->hasForeignKey('FK_C5D3D49FFE54D947')) {
            $this->addSql(
                'ALTER TABLE c_group_rel_user ADD CONSTRAINT FK_C5D3D49FFE54D947 FOREIGN KEY (group_id) REFERENCES c_group_info (iid) ON DELETE CASCADE;'
            );
        }
        if (false === $table->hasIndex('IDX_C5D3D49FA76ED395')) {
            $this->addSql('CREATE INDEX IDX_C5D3D49FA76ED395 ON c_group_rel_user (user_id)');
        }
        if (false === $table->hasIndex('IDX_C5D3D49FFE54D947')) {
            $this->addSql('CREATE INDEX IDX_C5D3D49FFE54D947 ON c_group_rel_user (group_id)');
        }

        $this->addSql('DELETE FROM c_group_rel_tutor WHERE user_id NOT IN (SELECT id FROM user)');
        $this->addSql('DELETE FROM c_group_rel_tutor WHERE group_id NOT IN (SELECT iid FROM c_group_info)');

        $table = $schema->getTable('c_group_rel_tutor');
        if (false === $table->hasForeignKey('FK_F6FF71ABA76ED395')) {
            $this->addSql(
                'ALTER TABLE c_group_rel_tutor ADD CONSTRAINT FK_F6FF71ABA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)'
            );
        }
        if (false === $table->hasForeignKey('FK_F6FF71ABFE54D947')) {
            $this->addSql(
                'ALTER TABLE c_group_rel_tutor ADD CONSTRAINT FK_F6FF71ABFE54D947 FOREIGN KEY (group_id) REFERENCES c_group_info (iid)'
            );
        }
        if (false === $table->hasIndex('IDX_F6FF71ABA76ED395')) {
            $this->addSql('CREATE INDEX IDX_F6FF71ABA76ED395 ON c_group_rel_tutor (user_id)');
        }
        if (false === $table->hasIndex('IDX_F6FF71ABFE54D947')) {
            $this->addSql('CREATE INDEX IDX_F6FF71ABFE54D947 ON c_group_rel_tutor (group_id)');
        }

        $table = $schema->getTable('c_group_category');
        if ($table->hasIndex('course')) {
            $this->addSql('DROP INDEX course ON c_group_category');
        }

        $this->addSql('ALTER TABLE c_group_category CHANGE display_order display_order INT DEFAULT NULL');

        if (!$table->hasColumn('document_access')) {
            $this->addSql('ALTER TABLE c_group_category ADD document_access INT DEFAULT 0 NOT NULL;');
        }

        if (false === $table->hasColumn('resource_node_id')) {
            $this->addSql('ALTER TABLE c_group_category ADD resource_node_id BIGINT DEFAULT NULL');
            $this->addSql('ALTER TABLE c_group_category ADD CONSTRAINT FK_F8E479F61BAD783F FOREIGN KEY (resource_node_id) REFERENCES resource_node (id) ON DELETE CASCADE');
            $this->addSql('CREATE UNIQUE INDEX UNIQ_F8E479F61BAD783F ON c_group_category (resource_node_id)');
        }

        $this->addSql('UPDATE c_group_info SET category_id = NULL WHERE category_id NOT IN (select iid FROM c_group_category)');
        if (false === $schema->getTable('c_group_info')->hasForeignKey('FK_CE06532412469DE2')) {
            $this->addSql('ALTER TABLE c_group_info ADD CONSTRAINT FK_CE06532412469DE2 FOREIGN KEY (category_id) REFERENCES c_group_category (iid) ON DELETE CASCADE');
        }
    }

    public function down(Schema $schema): void
    {
    }
}
