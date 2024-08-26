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
        return 'Migration to create LTI-related tables: lti_token, lti_lineitem, and lti_platform.';
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
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS lti_platform;');
        $this->addSql('DROP TABLE IF EXISTS lti_lineitem;');
        $this->addSql('DROP TABLE IF EXISTS lti_token;');
    }
}
