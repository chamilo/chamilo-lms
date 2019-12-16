<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V110;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Class Version20150713132630.
 */
class Version20150713132630 extends AbstractMigrationChamilo
{
    public function up(Schema $schema)
    {
        if ($schema->hasTable('c_student_publication')) {
            $this->addSql('ALTER TABLE c_student_publication ADD url_correction VARCHAR(255) DEFAULT NULL');
            $this->addSql('ALTER TABLE c_student_publication ADD title_correction VARCHAR(255) DEFAULT NULL');
        }
    }

    public function down(Schema $schema)
    {
        $this->addSql('ALTER TABLE c_student_publication DROP url_correction');
        $this->addSql('ALTER TABLE c_student_publication DROP title_correction');
    }
}
