<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V310;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20260929131500 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Add learner question issue reports for exercises.';
    }

    public function up(Schema $schema): void
    {
        if ($schema->hasTable('c_quiz') && !$schema->getTable('c_quiz')->hasColumn('allow_question_feedback_reports')) {
            $this->addSql('ALTER TABLE c_quiz ADD allow_question_feedback_reports TINYINT(1) DEFAULT 0 NOT NULL');
        }

        if (!$schema->hasTable('c_quiz_question_attempt_feedback')) {
            $this->addSql(
                'CREATE TABLE c_quiz_question_attempt_feedback (
                    iid INT AUTO_INCREMENT NOT NULL,
                    attempt_id INT NOT NULL,
                    question_id INT NOT NULL,
                    feedback LONGTEXT NOT NULL,
                    feedback_time DATETIME NOT NULL COMMENT \'(DC2Type:datetime)\',
                    INDEX IDX_QQAF_ATTEMPT (attempt_id),
                    INDEX IDX_QQAF_QUESTION (question_id),
                    PRIMARY KEY(iid)
                ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC'
            );
            $this->addSql(
                'ALTER TABLE c_quiz_question_attempt_feedback
                 ADD CONSTRAINT FK_QQAF_ATTEMPT
                 FOREIGN KEY (attempt_id) REFERENCES track_e_exercises (exe_id) ON DELETE CASCADE'
            );
            $this->addSql(
                'ALTER TABLE c_quiz_question_attempt_feedback
                 ADD CONSTRAINT FK_QQAF_QUESTION
                 FOREIGN KEY (question_id) REFERENCES c_quiz_question (iid) ON DELETE CASCADE'
            );
        }
    }

    public function down(Schema $schema): void
    {
        if ($schema->hasTable('c_quiz_question_attempt_feedback')) {
            $this->addSql('DROP TABLE c_quiz_question_attempt_feedback');
        }

        if ($schema->hasTable('c_quiz') && $schema->getTable('c_quiz')->hasColumn('allow_question_feedback_reports')) {
            $this->addSql('ALTER TABLE c_quiz DROP allow_question_feedback_reports');
        }
    }
}
