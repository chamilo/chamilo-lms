<?php
/* For licensing terms, see /license.txt */

namespace Application\Migrations\Schema\V110;

use Application\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Add indexes
 */
class Version20151214164000 extends AbstractMigrationChamilo
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $extraFieldValueTable = $schema->getTable('extra_field_values');
        $extraFieldValueTable->addIndex(['field_id', 'item_id']);

        $extraFieldTable = $schema->getTable('extra_field');
        $extraFieldTable->addIndex(['extra_field_type']);
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
    }
}
