<?php
/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

class Version20240321142500 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Remove personal agenda related tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS personal_agenda');
        $this->addSql('DROP TABLE IF EXISTS personal_agenda_repeat');
        $this->addSql('DROP TABLE IF EXISTS personal_agenda_repeat_not');
    }

    public function down(Schema $schema): void
    {
        if (!$schema->hasTable('personal_agenda')) {
            $this->addSql('CREATE TABLE personal_agenda (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, title TEXT DEFAULT NULL, text TEXT DEFAULT NULL, date DATETIME DEFAULT NULL, enddate DATETIME DEFAULT NULL, parent_event_id INT DEFAULT NULL, all_day INT NOT NULL, color VARCHAR(20) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        }

        if (!$schema->hasTable('personal_agenda_repeat')) {
            $this->addSql('CREATE TABLE personal_agenda_repeat (cal_id INT AUTO_INCREMENT NOT NULL, cal_type VARCHAR(20) DEFAULT NULL, cal_end INT DEFAULT NULL, cal_frequency INT DEFAULT NULL, cal_days VARCHAR(7) DEFAULT NULL, PRIMARY KEY(cal_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        }

        if (!$schema->hasTable('personal_agenda_repeat_not')) {
            $this->addSql('CREATE TABLE personal_agenda_repeat_not (cal_id INT NOT NULL, cal_date INT NOT NULL, PRIMARY KEY(cal_id, cal_date)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        }
    }
}
