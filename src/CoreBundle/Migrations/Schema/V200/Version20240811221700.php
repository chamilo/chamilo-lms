<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Doctrine\DBAL\Schema\Schema;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;

final class Version20240811221700 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Migration to create LTI-related tables and add necessary foreign keys and unique constraints.';
    }

    public function up(Schema $schema): void
    {
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

        $this->addSql('
            CREATE TABLE IF NOT EXISTS lti_external_tool (
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

        $this->addSql('
            ALTER TABLE lti_token
            ADD CONSTRAINT FK_EA71C468F7B22CC FOREIGN KEY (tool_id)
            REFERENCES lti_external_tool (id) ON DELETE CASCADE;
        ');

        $this->addSql('
            ALTER TABLE lti_external_tool
            ADD CONSTRAINT FK_DB0E04E41BAD783F FOREIGN KEY (resource_node_id)
            REFERENCES resource_node (id) ON DELETE CASCADE;
        ');

        $this->addSql('
            ALTER TABLE lti_external_tool
            ADD CONSTRAINT FK_DB0E04E491D79BD3 FOREIGN KEY (c_id)
            REFERENCES course (id);
        ');

        $this->addSql('
            ALTER TABLE lti_external_tool
            ADD CONSTRAINT FK_DB0E04E482F80D8B FOREIGN KEY (gradebook_eval_id)
            REFERENCES gradebook_evaluation (id) ON DELETE SET NULL;
        ');

        $this->addSql('
            ALTER TABLE lti_external_tool
            ADD CONSTRAINT FK_DB0E04E4727ACA70 FOREIGN KEY (parent_id)
            REFERENCES lti_external_tool (id);
        ');

        $this->addSql('
            ALTER TABLE lti_lineitem
            ADD CONSTRAINT FK_5C76B75D8F7B22CC FOREIGN KEY (tool_id)
            REFERENCES lti_external_tool (id) ON DELETE CASCADE;
        ');

        $this->addSql('
            ALTER TABLE lti_lineitem
            ADD CONSTRAINT FK_5C76B75D1323A575 FOREIGN KEY (evaluation)
            REFERENCES gradebook_evaluation (id) ON DELETE CASCADE;
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE lti_lineitem DROP FOREIGN KEY FK_5C76B75D8F7B22CC;');
        $this->addSql('ALTER TABLE lti_lineitem DROP FOREIGN KEY FK_5C76B75D1323A575;');
        $this->addSql('ALTER TABLE lti_token DROP FOREIGN KEY FK_EA71C468F7B22CC;');
        $this->addSql('ALTER TABLE lti_external_tool DROP FOREIGN KEY FK_DB0E04E41BAD783F;');
        $this->addSql('ALTER TABLE lti_external_tool DROP FOREIGN KEY FK_DB0E04E491D79BD3;');
        $this->addSql('ALTER TABLE lti_external_tool DROP FOREIGN KEY FK_DB0E04E482F80D8B;');
        $this->addSql('ALTER TABLE lti_external_tool DROP FOREIGN KEY FK_DB0E04E4727ACA70;');

        $this->addSql('DROP TABLE IF EXISTS lti_platform;');
        $this->addSql('DROP TABLE IF EXISTS lti_lineitem;');
        $this->addSql('DROP TABLE IF EXISTS lti_token;');
        $this->addSql('DROP TABLE IF EXISTS lti_external_tool;');
    }
}
