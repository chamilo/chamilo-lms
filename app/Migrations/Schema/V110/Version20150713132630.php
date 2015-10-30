<?php
/* For licensing terms, see /license.txt */

namespace Application\Migrations\Schema\V110;

use Application\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Class Version20150713132630
 *
 * @package Application\Migrations\Schema\V11010
 */
class Version20150713132630 extends AbstractMigrationChamilo
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        if ($schema->hasTable('c_student_publication')) {
            $this->addSql('ALTER TABLE c_student_publication ADD url_correction VARCHAR(255) DEFAULT NULL');
            $this->addSql('ALTER TABLE c_student_publication ADD title_correction VARCHAR(255) DEFAULT NULL');
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql('ALTER TABLE c_student_publication DROP url_correction');
        $this->addSql('ALTER TABLE c_student_publication DROP title_correction');
    }
}
