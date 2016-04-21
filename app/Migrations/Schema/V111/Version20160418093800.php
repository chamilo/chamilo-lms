<?php
/* For licensing terms, see /license.txt */

namespace Application\Migrations\Schema\V111;

use Application\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;

/**
 * Class Version20160418093800
 * Add save_correct_answers column to c_quiz table
 * @package Application\Migrations\Schema\V111
 */
class Version20160418093800 extends AbstractMigrationChamilo
{
    /**
     * @param Schema $schema
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function up(Schema $schema)
    {
        $cQuizTable = $schema->getTable('c_quiz');
        $cQuizTable->addColumn('save_correct_answers', Type::BOOLEAN);
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {

    }
}