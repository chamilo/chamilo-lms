<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V111;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;

/**
 * Class Version20160418093800
 * Add save_correct_answers column to c_quiz table.
 */
class Version20160418093800 extends AbstractMigrationChamilo
{
    /**
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function up(Schema $schema)
    {
        $cQuizTable = $schema->getTable('c_quiz');
        $cQuizTable->addColumn('save_correct_answers', Type::BOOLEAN);
    }

    public function down(Schema $schema)
    {
    }
}
