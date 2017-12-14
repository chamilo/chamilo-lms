<?php
/* For licensing terms, see /license.txt */

namespace Application\Migrations\Schema\V111;

use Application\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Class Version20170522120000
 * Remove track_e_attempt.course_code which is deleted between 1.9 and 1.10
 * but somehow still existed in the 1.10 entity (and it is not deleted from
 * 1.10 to 1.11)
 * @package Application\Migrations\Schema\V111
 */
class Version20170522120000 extends AbstractMigrationChamilo
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        error_log('Version20170522120000');
        $trackEAttempt = $schema->getTable('track_e_attempt');
        if ($trackEAttempt->hasColumn('course_code')) {
            $this->addSql("ALTER TABLE track_e_attempt DROP COLUMN course_code");
        }
    }

    /**
     * Down does not do anything in this case because the field shouldn't
     * have been there in the first place
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
    }
}
