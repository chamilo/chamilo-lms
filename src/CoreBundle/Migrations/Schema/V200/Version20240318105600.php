<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20240318105600 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Color theme migration';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE color_theme (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, variables LONGTEXT NOT NULL COMMENT '(DC2Type:json)', slug VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime)', updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime)', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC");
        $this->addSql("CREATE TABLE access_url_rel_color_theme (id INT AUTO_INCREMENT NOT NULL, url_id INT NOT NULL, color_theme_id INT NOT NULL, active TINYINT(1) NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime)', updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime)', INDEX IDX_D2A2E1C981CFDAE7 (url_id), INDEX IDX_D2A2E1C98587EFC5 (color_theme_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC");
        $this->addSql("ALTER TABLE access_url_rel_color_theme ADD CONSTRAINT FK_D2A2E1C981CFDAE7 FOREIGN KEY (url_id) REFERENCES access_url (id)");
        $this->addSql("ALTER TABLE access_url_rel_color_theme ADD CONSTRAINT FK_D2A2E1C98587EFC5 FOREIGN KEY (color_theme_id) REFERENCES color_theme (id)");
    }
}
