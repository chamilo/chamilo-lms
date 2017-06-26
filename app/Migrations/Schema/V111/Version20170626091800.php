<?php
/* For licensing terms, see /license.txt */

namespace Application\Migrations\Schema\V111;

use Application\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Class Version20170626091800
 *
 * Create extrafield to make survey as mandatory
 *
 * @package Application\Migrations\Schema\V111
 */
class Version20170626091800 extends AbstractMigrationChamilo
{

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql("
            INSERT INTO extra_field
            (extra_field_type, field_type, variable, display_text, visible_to_self, changeable, created_at)
            VALUES (12, 13, 'is_mandatory', 'IsMandatory', 1, 1, NOW());
        ");
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql("
            DELETE FROM extra_field
            WHERE extra_field_type = 12 AND field_type = 13 AND variable = 'is_mandatory';
        ");
    }
}
