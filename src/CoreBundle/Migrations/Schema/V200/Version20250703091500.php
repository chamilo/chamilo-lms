<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

class Version20250703091500 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Create table to save last synchronization state for Azure commands';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE IF NOT EXISTS azure_sync_state (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, value LONGTEXT NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime)', updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime)', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC");
    }
}
