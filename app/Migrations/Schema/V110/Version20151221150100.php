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
        $this->addSql("ALTER TABLE c_quiz_answer ENGINE=InnoDB");
        $this->addSql("ALTER TABLE c_quiz_question ENGINE=InnoDB");
        $this->addSql("ALTER TABLE c_quiz_answer ADD INDEX idx_qa_question_id_temp (question_id)");
        $this->addSql("ALTER TABLE c_quiz_answer ADD INDEX idx_qa_id_temp (id)");
        $this->addSql("ALTER TABLE c_quiz_answer ADD INDEX idx_qa_correct_temp (correct)");
        $this->addSql("ALTER TABLE track_e_attempt CHANGE tms tms datetime default null");
        $this->addSql("UPDATE track_e_attempt SET tms = NULL WHERE tms = '0000-00-00 00:00:00'");
        $this->addSql("ALTER TABLE track_e_attempt ADD INDEX idx_tea_answer_temp (answer(6))");
        $this->addSql("ALTER TABLE c_quiz_question ADD INDEX idx_qq_id_temp (id)");
        $this->addSql("ALTER TABLE c_quiz_question ADD INDEX idx_qq_type_temp (type)");
        $this->addSql("
            UPDATE track_e_attempt a
            INNER JOIN c_quiz_answer qa
            ON (a.question_id = qa.question_id AND a.c_id = qa.c_id)
            INNER JOIN c_quiz_question q
            ON (qa.question_id = q.id AND qa.c_id = q.c_id)
            SET a.answer = qa.id_auto
            WHERE
                a.answer = qa.id AND
                q.c_id = a.c_id AND
                q.type IN (" . MATCHING . ", " . DRAGGABLE . ", " . MATCHING_DRAGGABLE . ")
        ");

        $this->addSql("
            UPDATE c_quiz_answer a
            INNER JOIN c_quiz_answer b
            ON (a.question_id = b.question_id AND b.c_id = a.c_id)
            INNER JOIN c_quiz_question q
            ON (b.question_id = q.id AND b.c_id = q.c_id)
            SET a.correct = b.id_auto
            WHERE
                a.correct = b.id AND
                q.c_id = a.c_id AND
                q.type IN (" . MATCHING . ", " . DRAGGABLE . ", " . MATCHING_DRAGGABLE . ")
        ");
        $this->addSql("ALTER TABLE c_quiz_answer DROP INDEX idx_qa_question_id_temp");
        $this->addSql("ALTER TABLE c_quiz_answer DROP INDEX idx_qa_id_temp");
        $this->addSql("ALTER TABLE c_quiz_answer DROP INDEX idx_qa_correct_temp");
        $this->addSql("ALTER TABLE track_e_attempt DROP INDEX idx_tea_answer_temp");
        $this->addSql("ALTER TABLE c_quiz_question DROP INDEX idx_qq_id_temp");
        $this->addSql("ALTER TABLE c_quiz_question DROP INDEX idx_qq_type_temp");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
    }
}
