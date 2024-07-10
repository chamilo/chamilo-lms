<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20240709222600 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Create permissions and permission_rel_roles tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE IF NOT EXISTS permissions (
                id INT AUTO_INCREMENT NOT NULL,
                title VARCHAR(255) NOT NULL,
                slug VARCHAR(255) NOT NULL,
                description LONGTEXT DEFAULT NULL,
                UNIQUE INDEX UNIQ_2DEDCC6F989D9B62 (slug),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC
        ');

        $this->addSql('
            CREATE TABLE IF NOT EXISTS permission_rel_roles (
                id INT AUTO_INCREMENT NOT NULL,
                permission_id INT NOT NULL,
                role_code VARCHAR(50) NOT NULL,
                changeable TINYINT(1) NOT NULL,
                updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime)\',
                INDEX IDX_43723A27FED90CCA (permission_id),
                PRIMARY KEY(id),
                CONSTRAINT FK_43723A27FED90CCA FOREIGN KEY (permission_id) REFERENCES permissions (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS permission_rel_roles');
        $this->addSql('DROP TABLE IF EXISTS permissions');
    }
}
