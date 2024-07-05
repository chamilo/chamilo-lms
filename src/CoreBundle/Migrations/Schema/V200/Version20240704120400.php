<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20240704120400 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Add duration field to multiple tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE course ADD duration INT DEFAULT NULL');
        $this->addSql('ALTER TABLE c_survey ADD duration INT DEFAULT NULL');
        $this->addSql('ALTER TABLE c_quiz ADD duration INT DEFAULT NULL');
        $this->addSql('ALTER TABLE c_quiz_question ADD duration INT DEFAULT NULL');
        $this->addSql('ALTER TABLE c_lp ADD duration INT DEFAULT NULL');
        $this->addSql('ALTER TABLE c_lp_item ADD duration INT DEFAULT NULL');
        $this->addSql('ALTER TABLE c_student_publication ADD duration INT DEFAULT NULL');
        $this->addSql('ALTER TABLE c_attendance_calendar ADD duration INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE course DROP duration');
        $this->addSql('ALTER TABLE c_survey DROP duration');
        $this->addSql('ALTER TABLE c_quiz DROP duration');
        $this->addSql('ALTER TABLE c_quiz_question DROP duration');
        $this->addSql('ALTER TABLE c_lp DROP duration');
        $this->addSql('ALTER TABLE c_lp_item DROP duration');
        $this->addSql('ALTER TABLE c_student_publication DROP duration');
        $this->addSql('ALTER TABLE c_attendance_calendar DROP duration');
    }
}
