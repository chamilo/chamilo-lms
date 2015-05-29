<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V110;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Session date changes
 */
class Version20150528103216 extends AbstractMigrationChamilo
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql('UPDATE session SET access_start_date = date_start');
        $this->addSql("UPDATE session SET access_end_date = CONVERT(CONCAT(date_end, ' 23:59:59'), DATETIME)");

        $this->addSql('UPDATE session SET coach_access_start_date = CONVERT(DATE_SUB(date_start, INTERVAL nb_days_access_before_beginning DAY), DATETIME) ');
        $this->addSql('UPDATE session SET coach_access_start_date = NULL WHERE nb_days_access_before_beginning = 0');

        $this->addSql('UPDATE session SET coach_access_end_date = CONVERT(DATE_ADD(date_end, INTERVAL nb_days_access_after_end DAY), DATETIME) ');
        $this->addSql('UPDATE session SET coach_access_end_date = NULL WHERE nb_days_access_after_end = 0');

        $this->addSql('UPDATE session SET display_start_date = access_start_date');
        $this->addSql('UPDATE session SET display_end_date = access_end_date');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql('ALTER TABLE session DROP access_start_date');
        $this->addSql('ALTER TABLE session DROP access_end_date');
        $this->addSql('ALTER TABLE session DROP coach_access_start_date');
        $this->addSql('ALTER TABLE session DROP coach_access_end_date');
        $this->addSql('ALTER TABLE session DROP display_start_date');
        $this->addSql('ALTER TABLE session DROP display_end_date');
    }
}
