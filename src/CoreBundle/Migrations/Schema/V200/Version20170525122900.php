<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

class Version20170525122900 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Resources changes';
    }

    public function up(Schema $schema): void
    {
        if (false === $schema->hasTable('resource_file')) {
            $this->addSql(
                'CREATE TABLE resource_file (id BIGINT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, original_name LONGTEXT DEFAULT NULL, size INT NOT NULL, dimensions LONGTEXT DEFAULT NULL COMMENT "(DC2Type:simple_array)",crop VARCHAR(255) DEFAULT NULL, mime_type LONGTEXT DEFAULT NULL, description longtext DEFAULT NULL, metadata LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\',  created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB ROW_FORMAT = DYNAMIC'
            );
        }

        if (false === $schema->hasTable('resource_node')) {
            $this->addSql(
                'CREATE TABLE IF NOT EXISTS resource_node (id BIGINT AUTO_INCREMENT NOT NULL, resource_type_id INT NOT NULL, resource_file_id BIGINT DEFAULT NULL, creator_id INT NOT NULL, parent_id BIGINT DEFAULT NULL, name VARCHAR(255) NOT NULL, level INT DEFAULT NULL, path VARCHAR(3000) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_8A5F48FF98EC6B7B (resource_type_id), UNIQUE INDEX UNIQ_8A5F48FFCE6B9E84 (resource_file_id), INDEX IDX_8A5F48FF61220EA6 (creator_id), INDEX IDX_8A5F48FF727ACA70 (parent_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB ROW_FORMAT = DYNAMIC;'
            );
            $this->addSql(
                'ALTER TABLE resource_node ADD slug VARCHAR(255) NOT NULL, ADD uuid BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', CHANGE creator_id creator_id INT DEFAULT NULL, CHANGE path path LONGTEXT DEFAULT NULL, CHANGE name title VARCHAR(255) NOT NULL'
            );
            $this->addSql('CREATE UNIQUE INDEX UNIQ_8A5F48FFD17F50A6 ON resource_node (uuid)');
            $this->addSql('ALTER TABLE resource_node ADD public TINYINT(1) NOT NULL');
        }

        if (!$schema->hasTable('resource_link')) {
            $this->addSql(
                "CREATE TABLE resource_link (id BIGINT AUTO_INCREMENT NOT NULL, resource_node_id BIGINT DEFAULT NULL, session_id INT DEFAULT NULL, user_id INT DEFAULT NULL, c_id INT DEFAULT NULL, group_id INT DEFAULT NULL, usergroup_id INT DEFAULT NULL, visibility INT NOT NULL, start_visibility_at DATETIME DEFAULT NULL, end_visibility_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime)', updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime)', INDEX IDX_398C394B1BAD783F (resource_node_id), INDEX IDX_398C394B613FECDF (session_id), INDEX IDX_398C394BA76ED395 (user_id), INDEX IDX_398C394B91D79BD3 (c_id), INDEX IDX_398C394BFE54D947 (group_id), INDEX IDX_398C394BD2112630 (usergroup_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB ROW_FORMAT = DYNAMIC;"
            );
        }

        if (false === $schema->hasTable('resource_comment')) {
            $this->addSql(
                'CREATE TABLE resource_comment (id BIGINT AUTO_INCREMENT NOT NULL, resource_node_id BIGINT DEFAULT NULL, author_id INT DEFAULT NULL, parent_id BIGINT DEFAULT NULL, content VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, root INT DEFAULT NULL, lvl INT NOT NULL, lft INT NOT NULL, rgt INT NOT NULL, INDEX IDX_C9D4B5841BAD783F (resource_node_id), INDEX IDX_C9D4B584F675F31B (author_id), INDEX IDX_C9D4B584727ACA70 (parent_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC;'
            );
            $this->addSql(
                'ALTER TABLE resource_comment ADD CONSTRAINT FK_C9D4B5841BAD783F FOREIGN KEY (resource_node_id) REFERENCES resource_node (id) ON DELETE SET NULL'
            );
            $this->addSql(
                'ALTER TABLE resource_comment ADD CONSTRAINT FK_C9D4B584F675F31B FOREIGN KEY (author_id) REFERENCES user (id) ON DELETE SET NULL'
            );
            $this->addSql(
                'ALTER TABLE resource_comment ADD CONSTRAINT FK_C9D4B584727ACA70 FOREIGN KEY (parent_id) REFERENCES resource_comment (id) ON DELETE CASCADE'
            );
        }

        if (false === $schema->hasTable('resource_tag')) {
            $this->addSql(
                'CREATE TABLE IF NOT EXISTS resource_tag (id BIGINT AUTO_INCREMENT NOT NULL, author_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_23D039CAF675F31B (author_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC;'
            );
            $this->addSql(
                'ALTER TABLE resource_tag ADD CONSTRAINT FK_23D039CAF675F31B FOREIGN KEY (author_id) REFERENCES user (id) ON DELETE SET NULL;'
            );
        }

        if (false === $schema->hasTable('resource_user_tag')) {
            $this->addSql(
                'CREATE TABLE IF NOT EXISTS resource_user_tag (id BIGINT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, tag_id BIGINT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_46131CA5A76ED395 (user_id), INDEX IDX_46131CA5BAD26311 (tag_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC;'
            );
            $this->addSql(
                'ALTER TABLE resource_user_tag ADD CONSTRAINT FK_46131CA5A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE SET NULL;'
            );
            $this->addSql(
                'ALTER TABLE resource_user_tag ADD CONSTRAINT FK_46131CA5BAD26311 FOREIGN KEY (tag_id) REFERENCES resource_tag (id) ON DELETE SET NULL;'
            );
        }

        if (false === $schema->hasTable('personal_file')) {
            $this->addSql(
                'CREATE TABLE IF NOT EXISTS personal_file (id INT AUTO_INCREMENT NOT NULL, resource_node_id BIGINT DEFAULT NULL, title VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_BD95312D1BAD783F (resource_node_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC;'
            );
            $this->addSql(
                'ALTER TABLE personal_file ADD CONSTRAINT FK_BD95312D1BAD783F FOREIGN KEY (resource_node_id) REFERENCES resource_node (id) ON DELETE CASCADE;'
            );
        }

        if (false === $schema->hasTable('resource_link')) {
            $this->addSql(
                'ALTER TABLE resource_link ADD CONSTRAINT FK_398C394B1BAD783F FOREIGN KEY (resource_node_id) REFERENCES resource_node (id) ON DELETE CASCADE;'
            );

            $this->addSql(
                'ALTER TABLE resource_link ADD CONSTRAINT FK_398C394BFE54D947 FOREIGN KEY (group_id) REFERENCES c_group_info (iid) ON DELETE CASCADE'
            );
            $this->addSql(
                'ALTER TABLE resource_link ADD CONSTRAINT FK_398C394B613FECDF FOREIGN KEY (session_id) REFERENCES session (id) ON DELETE CASCADE'
            );
            $this->addSql(
                'ALTER TABLE resource_link ADD CONSTRAINT FK_398C394BA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE SET NULL;'
            );
            $this->addSql(
                'ALTER TABLE resource_link ADD CONSTRAINT FK_398C394B91D79BD3 FOREIGN KEY (c_id) REFERENCES course (id) ON DELETE CASCADE'
            );
            $this->addSql(
                'ALTER TABLE resource_link ADD CONSTRAINT FK_398C394BD2112630 FOREIGN KEY (usergroup_id) REFERENCES usergroup (id) ON DELETE CASCADE;'
            );
        }

        if (false === $schema->hasTable('tool_resource_right')) {
            $this->addSql(
                'CREATE TABLE IF NOT EXISTS tool_resource_right (id INT AUTO_INCREMENT NOT NULL, tool_id INT DEFAULT NULL, role VARCHAR(255) NOT NULL, mask INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;'
            );
            $this->addSql(
                'ALTER TABLE tool_resource_right ADD CONSTRAINT FK_E5C562598F7B22CC FOREIGN KEY (tool_id) REFERENCES tool (id);'
            );
            $this->addSql('CREATE INDEX IDX_E5C562598F7B22CC ON tool_resource_right (tool_id)');
        }

        if (false === $schema->hasTable('resource_right')) {
            $this->addSql(
                'CREATE TABLE IF NOT EXISTS resource_right (id BIGINT AUTO_INCREMENT NOT NULL, resource_link_id BIGINT DEFAULT NULL, role VARCHAR(255) NOT NULL, mask INT NOT NULL, INDEX IDX_9F710F26F004E599 (resource_link_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB ROW_FORMAT = DYNAMIC;'
            );
            $this->addSql(
                'ALTER TABLE resource_right ADD CONSTRAINT FK_9F710F26F004E599 FOREIGN KEY (resource_link_id) REFERENCES resource_link (id) ON DELETE CASCADE'
            );
        }

        if (false === $schema->hasTable('resource_type')) {
            $this->addSql(
                'CREATE TABLE IF NOT EXISTS resource_type (id INT AUTO_INCREMENT NOT NULL, tool_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_83FEF7938F7B22CC (tool_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB ROW_FORMAT = DYNAMIC;'
            );
            $this->addSql(
                'ALTER TABLE resource_type ADD CONSTRAINT FK_83FEF7938F7B22CC FOREIGN KEY (tool_id) REFERENCES tool (id);'
            );
        }

        if (false === $schema->hasTable('resource_node')) {
            $this->addSql(
                'ALTER TABLE resource_node ADD CONSTRAINT FK_8A5F48FF98EC6B7B FOREIGN KEY (resource_type_id) REFERENCES resource_type (id);'
            );

            $this->addSql(
                'ALTER TABLE resource_node ADD CONSTRAINT FK_8A5F48FFCE6B9E84 FOREIGN KEY (resource_file_id) REFERENCES resource_file (id) ON DELETE CASCADE'
            );

            $this->addSql(
                'ALTER TABLE resource_node ADD CONSTRAINT FK_8A5F48FF61220EA6 FOREIGN KEY (creator_id) REFERENCES user (id) ON DELETE CASCADE;'
            );

            $this->addSql(
                'ALTER TABLE resource_node ADD CONSTRAINT FK_8A5F48FF727ACA70 FOREIGN KEY (parent_id) REFERENCES resource_node (id) ON DELETE CASCADE;'
            );
        }

        if (false === $schema->hasTable('illustration')) {
            $this->addSql(
                "CREATE TABLE illustration (id BINARY(16) NOT NULL COMMENT '(DC2Type:uuid)', resource_node_id BIGINT DEFAULT NULL, name VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_D67B9A421BAD783F (resource_node_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB ROW_FORMAT = DYNAMIC;"
            );
            $this->addSql(
                'ALTER TABLE illustration ADD CONSTRAINT FK_D67B9A421BAD783F FOREIGN KEY (resource_node_id) REFERENCES resource_node (id) ON DELETE CASCADE;'
            );
        }

        if (false === $schema->hasTable('c_shortcut')) {
            $this->addSql(
                'CREATE TABLE IF NOT EXISTS c_shortcut (id INT AUTO_INCREMENT NOT NULL, shortcut_node_id BIGINT DEFAULT NULL, resource_node_id BIGINT DEFAULT NULL, name VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_3F6BB957937100BE (shortcut_node_id), UNIQUE INDEX UNIQ_3F6BB9571BAD783F (resource_node_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC;'
            );
            $this->addSql(
                'ALTER TABLE c_shortcut ADD CONSTRAINT FK_3F6BB957937100BE FOREIGN KEY (shortcut_node_id) REFERENCES resource_node (id);'
            );
            $this->addSql(
                'ALTER TABLE c_shortcut ADD CONSTRAINT FK_3F6BB9571BAD783F FOREIGN KEY (resource_node_id) REFERENCES resource_node (id) ON DELETE CASCADE;'
            );
        }
    }

    public function down(Schema $schema): void
    {
    }
}
