<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

class Version20240117134009 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Remove table track_e_attempt_recording';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS track_e_attempt_recording');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TABLE track_e_attempt_recording (id INT AUTO_INCREMENT NOT NULL, exe_id INT NOT NULL, question_id INT NOT NULL, marks DOUBLE PRECISION NOT NULL, insert_date DATETIME NOT NULL, author INT NOT NULL, teacher_comment LONGTEXT NOT NULL, session_id INT NOT NULL, answer LONGTEXT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE INDEX exe_id ON track_e_attempt_recording (exe_id)');
        $this->addSql('CREATE INDEX question_id ON track_e_attempt_recording (question_id)');
        $this->addSql('CREATE INDEX session_id ON track_e_attempt_recording (session_id)');
    }
}
