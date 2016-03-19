<?php
/* For licensing terms, see /license.txt */

namespace Application\Migrations\Schema\V111;

use Application\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Class Version20160315155700
 * Change type of the course_creation_use_template setting
 */
class Version20160315155700 extends AbstractMigrationChamilo
{
    /**
     * @param Schema $schema
     *
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function up(Schema $schema)
    {
        $this->addSql("UPDATE settings_current SET type = 'select_course' WHERE variable = 'course_creation_use_template'");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        
    }
}
