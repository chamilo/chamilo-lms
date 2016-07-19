<?php
/* For licensing terms, see /license.txt */

namespace Application\Migrations\Schema\V110;

use Application\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Class Version20150819095300
 *
 * @package Application\Migrations\Schema\V11010
 */
class Version20150819095300 extends AbstractMigrationChamilo
{

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $skillTable = $schema->getTable('skill');

        $skillTable->addColumn(
            'status',
            \Doctrine\DBAL\Types\Type::INTEGER,
            ['default' => 1]
        );
        $skillTable->addColumn(
            'updated_at',
            \Doctrine\DBAL\Types\Type::DATETIME
        );
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $skillTable = $schema->getTable('skill');
        $skillTable->dropColumn('status');
        $skillTable->dropColumn('updated_at');
    }

}
