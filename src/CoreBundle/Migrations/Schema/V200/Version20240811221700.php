<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Doctrine\DBAL\Schema\Schema;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;

final class Version20240811221700 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Migration to create LTI-related tables and establish foreign key relationships, including lti_token, lti_lineitem, lti_platform, and their relations.';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('lti_external_tool')) {
            $this->addSql('
                CREATE TABLE lti_external_tool (
                    id INT AUTO_INCREMENT NOT NULL,
                    resource_node_id INT DEFAULT NULL,
                    c_id INT DEFAULT NULL,
                    gradebook_eval_id INT DEFAULT NULL,
                    parent_id INT DEFAULT NULL,
                    title VARCHAR(255) NOT NULL,
                    description LONGTEXT DEFAULT NULL,
                    launch_url VARCHAR(255) NOT NULL,
                    consumer_key VARCHAR(255) DEFAULT NULL,
                    shared_secret VARCHAR(255) DEFAULT NULL,
                    custom_params LONGTEXT DEFAULT NULL,
                    active_deep_linking TINYINT(1) DEFAULT 0 NOT NULL,
                    privacy LONGTEXT DEFAULT NULL,
                    client_id VARCHAR(255) DEFAULT NULL,
                    login_url VARCHAR(255) DEFAULT NULL,
                    redirect_url VARCHAR(255) DEFAULT NULL,
                    advantage_services LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\',
                    version VARCHAR(255) DEFAULT \'lti1p1\' NOT NULL,
                    launch_presentation LONGTEXT NOT NULL COMMENT \'(DC2Type:json)\',
                    replacement_params LONGTEXT NOT NULL COMMENT \'(DC2Type:json)\',
                    UNIQUE INDEX UNIQ_DB0E04E41BAD783F (resource_node_id),
                    INDEX IDX_DB0E04E491D79BD3 (c_id),
                    INDEX IDX_DB0E04E482F80D8B (gradebook_eval_id),
                    INDEX IDX_DB0E04E4727ACA70 (parent_id),
                    PRIMARY KEY(id)
                ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC;
            ');
        }

        $this->addSql('
            CREATE TABLE IF NOT EXISTS lti_token (
                id INT AUTO_INCREMENT NOT NULL,
                tool_id INT DEFAULT NULL,
                scope LONGTEXT NOT NULL COMMENT \'(DC2Type:json)\',
                hash VARCHAR(255) NOT NULL,
                created_at INT NOT NULL,
                expires_at INT NOT NULL,
                INDEX IDX_EA71C468F7B22CC (tool_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC;
        ');

        $this->addSql('
            CREATE TABLE IF NOT EXISTS lti_lineitem (
                id INT AUTO_INCREMENT NOT NULL,
                tool_id INT NOT NULL,
                evaluation INT NOT NULL,
                resource_id VARCHAR(255) DEFAULT NULL,
                tag VARCHAR(255) DEFAULT NULL,
                start_date DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime)\',
                end_date DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime)\',
                INDEX IDX_5C76B75D8F7B22CC (tool_id),
                UNIQUE INDEX UNIQ_5C76B75D1323A575 (evaluation),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC;
        ');

        $this->addSql('
            CREATE TABLE IF NOT EXISTS lti_platform (
                id INT AUTO_INCREMENT NOT NULL,
                public_key LONGTEXT NOT NULL,
                kid VARCHAR(255) NOT NULL,
                private_key LONGTEXT NOT NULL,
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC;
        ');

        if ($schema->hasTable('lti_external_tool')) {

            if (!$schema->getTable('lti_external_tool')->hasForeignKey('FK_DB0E04E41BAD783F')) {
                $this->addSql('
                ALTER TABLE lti_external_tool
                ADD CONSTRAINT FK_DB0E04E41BAD783F FOREIGN KEY (resource_node_id)
                REFERENCES resource_node (id) ON DELETE CASCADE;
            ');

                $this->addSql('
                CREATE UNIQUE INDEX UNIQ_DB0E04E41BAD783F ON lti_external_tool (resource_node_id);
            ');
            }

            if (!$schema->getTable('lti_external_tool')->hasForeignKey('FK_DB0E04E491D79BD3')) {
                $this->addSql('
                ALTER TABLE lti_external_tool
                ADD CONSTRAINT FK_DB0E04E491D79BD3 FOREIGN KEY (c_id)
                REFERENCES course (id);
            ');
            }

            if (!$schema->getTable('lti_external_tool')->hasForeignKey('FK_DB0E04E482F80D8B')) {
                $this->addSql('
                ALTER TABLE lti_external_tool
                ADD CONSTRAINT FK_DB0E04E482F80D8B FOREIGN KEY (gradebook_eval_id)
                REFERENCES gradebook_evaluation (id) ON DELETE SET NULL;
            ');
            }

            if (!$schema->getTable('lti_external_tool')->hasForeignKey('FK_DB0E04E4727ACA70')) {
                $this->addSql('
                ALTER TABLE lti_external_tool
                ADD CONSTRAINT FK_DB0E04E4727ACA70 FOREIGN KEY (parent_id)
                REFERENCES lti_external_tool (id);
            ');
            }

        }

        if ($schema->hasTable('lti_token') && !$schema->getTable('lti_token')->hasForeignKey('FK_EA71C468F7B22CC')) {
            $this->addSql('
                ALTER TABLE lti_token
                ADD CONSTRAINT FK_EA71C468F7B22CC FOREIGN KEY (tool_id)
                REFERENCES lti_external_tool (id) ON DELETE CASCADE;
            ');
        }

        if ($schema->hasTable('lti_lineitem') && !$schema->getTable('lti_lineitem')->hasForeignKey('FK_5C76B75D8F7B22CC')) {
            $this->addSql('
                ALTER TABLE lti_lineitem
                ADD CONSTRAINT FK_5C76B75D8F7B22CC FOREIGN KEY (tool_id)
                REFERENCES lti_external_tool (id) ON DELETE CASCADE;
            ');
        }

        if ($schema->hasTable('lti_lineitem') && !$schema->getTable('lti_lineitem')->hasForeignKey('FK_5C76B75D1323A575')) {
            $this->addSql('
                ALTER TABLE lti_lineitem
                ADD CONSTRAINT FK_5C76B75D1323A575 FOREIGN KEY (evaluation)
                REFERENCES gradebook_evaluation (id) ON DELETE CASCADE;
            ');
        }

        $this->addSql('SET FOREIGN_KEY_CHECKS = 1;');
    }

    public function down(Schema $schema): void
    {
        $columnsToAdd = [
            'resource_node_id' => 'INT DEFAULT NULL',
            'c_id' => 'INT DEFAULT NULL',
            'gradebook_eval_id' => 'INT DEFAULT NULL',
            'parent_id' => 'INT DEFAULT NULL',
            'client_id' => 'VARCHAR(255) DEFAULT NULL',
            'login_url' => 'VARCHAR(255) DEFAULT NULL',
            'redirect_url' => 'VARCHAR(255) DEFAULT NULL',
            'advantage_services' => 'LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\'',
            'version' => 'VARCHAR(255) DEFAULT \'lti1p1\' NOT NULL',
            'launch_presentation' => 'LONGTEXT NOT NULL COMMENT \'(DC2Type:json)\'',
            'replacement_params' => 'LONGTEXT NOT NULL COMMENT \'(DC2Type:json)\'',
        ];

        $this->addSql('ALTER TABLE lti_token DROP FOREIGN KEY IF EXISTS FK_EA71C468F7B22CC;');
        $this->addSql('ALTER TABLE lti_lineitem DROP FOREIGN KEY IF EXISTS FK_5C76B75D8F7B22CC;');
        $this->addSql('ALTER TABLE lti_lineitem DROP FOREIGN KEY IF EXISTS FK_5C76B75D1323A575;');
        $this->addSql('ALTER TABLE lti_external_tool DROP FOREIGN KEY IF EXISTS FK_DB0E04E41BAD783F;');
        $this->addSql('ALTER TABLE lti_external_tool DROP FOREIGN KEY IF EXISTS FK_DB0E04E491D79BD3;');
        $this->addSql('ALTER TABLE lti_external_tool DROP FOREIGN KEY IF EXISTS FK_DB0E04E482F80D8B;');
        $this->addSql('ALTER TABLE lti_external_tool DROP FOREIGN KEY IF EXISTS FK_DB0E04E4727ACA70;');

        foreach (array_keys($columnsToAdd) as $column) {
            $this->addSql(sprintf('ALTER TABLE lti_external_tool DROP COLUMN IF EXISTS %s;', $column));
        }
    }
}
