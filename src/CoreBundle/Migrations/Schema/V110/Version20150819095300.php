<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V110;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Class Version20150819095300.
 */
class Version20150819095300 extends AbstractMigrationChamilo
{
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

    public function down(Schema $schema)
    {
        $skillTable = $schema->getTable('skill');
        $skillTable->dropColumn('status');
        $skillTable->dropColumn('updated_at');
    }
}
