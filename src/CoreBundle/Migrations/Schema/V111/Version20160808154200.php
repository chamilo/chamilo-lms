<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V111;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Class Version20160808154200
 * Set ponderation and destination for questions and answers.
 */
class Version20160808154200 extends AbstractMigrationChamilo
{
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

    public function down(Schema $schema)
    {
        $answer = $schema->getTable('c_quiz_answer');
        $answer
            ->getColumn('destination')
            ->setNotnull(true)
            ->setDefault(0);
    }
}
