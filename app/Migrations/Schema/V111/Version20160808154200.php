<?php
/* For licensing terms, see /license.txt */

namespace Application\Migrations\Schema\V111;

use Application\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Class Version20160808154200
 * Set ponderation and destination for questions and answers
 * @package Application\Migrations\Schema\V111
 */
class Version20160808154200 extends AbstractMigrationChamilo
{

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $question = $schema->getTable('c_quiz_question');
        $question
            ->getColumn('ponderation')
            ->setDefault(0);

        $answer = $schema->getTable('c_quiz_answer');
        $answer
            ->getColumn('ponderation')
            ->setDefault(0);
        $answer
            ->getColumn('destination')
            ->setNotnull(false)
            ->setDefault(null);
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $answer = $schema->getTable('c_quiz_answer');
        $answer
            ->getColumn('destination')
            ->setNotnull(true)
            ->setDefault(0);
    }
}