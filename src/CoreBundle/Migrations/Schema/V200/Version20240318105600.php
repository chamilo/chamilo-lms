<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

class Version20240318105600 extends AbstractMigrationChamilo
{
    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE color_theme (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, variables LONGTEXT NOT NULL COMMENT '(DC2Type:json)', slug VARCHAR(255) NOT NULL, active TINYINT(1) NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime)', updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime)', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC");
    }
}
