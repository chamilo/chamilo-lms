<?php
/* For licensing terms, see /license.txt */

namespace Application\Migrations\Schema\V110;

use Application\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * GradebookCategory changes
 */
class Version20150706135000 extends AbstractMigrationChamilo
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $gradebookCategory = $schema->getTable('gradebook_category');

        $isRequirement = $gradebookCategory->addColumn(
            'is_requirement',
            \Doctrine\DBAL\Types\Type::BOOLEAN
        );
        $isRequirement->setNotnull(true)->setDefault(false);
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $gradebookCategory = $schema->getTable('gradebook_category');
        $gradebookCategory->dropColumn('is_requirement');
    }
}
