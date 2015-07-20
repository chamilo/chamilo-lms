<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V110;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Class Version20150713132630
 *
 * @package Chamilo\CoreBundle\Migrations\Schema\V110
 */
class Version20150713132630 extends AbstractMigrationChamilo
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql('ALTER TABLE c_student_publication ADD url_correction VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE c_student_publication ADD title_correction VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE c_student_publication ADD document_id INT NOT NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql('ALTER TABLE c_student_publication DROP url_correction');
        $this->addSql('ALTER TABLE c_student_publication DROP title_correction');
        $this->addSql('ALTER TABLE c_student_publication DROP document_id');
    }
}
