<?php
/* For licensing terms, see /license.txt */

namespace Application\Migrations\Schema\V110;

use Application\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Fix c_quiz_answer's correct field for id_auto
 */
class Version20151221150100 extends AbstractMigrationChamilo
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql("
            UPDATE track_e_attempt a
            INNER JOIN c_quiz_answer qa
            ON (a.question_id = qa.question_id AND a.c_id = qa.c_id)
            SET a.answer = qa.id_auto
            WHERE a.answer = qa.id
        ");

        $this->addSql("
            UPDATE c_quiz_answer a
            INNER JOIN c_quiz_answer b
            ON (a.question_id = b.question_id AND a.c_id = b.c_id)
            SET a.correct = b.id_auto
            WHERE a.correct = b.id
        ");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
    }
}
