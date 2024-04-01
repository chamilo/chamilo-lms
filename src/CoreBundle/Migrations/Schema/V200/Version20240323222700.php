<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20240323222700 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Remove the sys_calendar table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS sys_calendar');
    }

    public function down(Schema $schema): void
    {
        if (!$schema->hasTable('sys_calendar')) {
            $this->addSql('CREATE TABLE sys_calendar (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, content LONGTEXT DEFAULT NULL, start_date DATETIME DEFAULT NULL, end_date DATETIME DEFAULT NULL, access_url_id INT DEFAULT NULL, all_day INT NOT NULL, color VARCHAR(20) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        }
    }
}
