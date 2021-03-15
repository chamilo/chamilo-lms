<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Quiz changes.
 */
class Version20170904145500 extends AbstractMigrationChamilo
{
    public function up(Schema $schema): void
    {
        if (false === $schema->hasTable('c_exercise_category')) {
            $this->addSql(
                'CREATE TABLE c_exercise_category (id BIGINT AUTO_INCREMENT NOT NULL, c_id INT NOT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, position INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB'
            );
            $this->addSql('ALTER TABLE c_exercise_category ADD resource_node_id INT DEFAULT NULL');
            $this->addSql(
                'ALTER TABLE c_exercise_category ADD CONSTRAINT FK_B94C157E91D79BD3 FOREIGN KEY (c_id) REFERENCES course (id) ON DELETE CASCADE'
            );
            $this->addSql(
                'ALTER TABLE c_exercise_category ADD CONSTRAINT FK_B94C157E1BAD783F FOREIGN KEY (resource_node_id) REFERENCES resource_node (id) ON DELETE CASCADE'
            );
            $this->addSql('CREATE INDEX IDX_B94C157E91D79BD3 ON c_exercise_category (c_id)');
            $this->addSql('CREATE UNIQUE INDEX UNIQ_B94C157E1BAD783F ON c_exercise_category (resource_node_id)');
        }

        // c_quiz.
        $table = $schema->getTable('c_quiz');
        if ($table->hasColumn('exercise_category_id')) {
            $this->addSql('ALTER TABLE c_quiz CHANGE exercise_category_id exercise_category_id BIGINT DEFAULT NULL;');
            if (false === $table->hasForeignKey('FK_B7A1C35FB48D66')) {
                $this->addSql(
                    'ALTER TABLE c_quiz ADD CONSTRAINT FK_B7A1C35FB48D66 FOREIGN KEY (exercise_category_id) REFERENCES c_exercise_category (id) ON DELETE SET NULL'
                );
            }
        } else {
            $this->addSql('ALTER TABLE c_quiz ADD COLUMN exercise_category_id BIGINT DEFAULT NULL;');
            $this->addSql(
                'ALTER TABLE c_quiz ADD CONSTRAINT FK_B7A1C35FB48D66 FOREIGN KEY (exercise_category_id) REFERENCES c_exercise_category (id) ON DELETE SET NULL'
            );
        }

        if (!$table->hasColumn('autolaunch')) {
            $this->addSql('ALTER TABLE c_quiz ADD autolaunch TINYINT(1) DEFAULT 0');
        }
        if (false === $table->hasIndex('IDX_B7A1C35FB48D66')) {
            $this->addSql('CREATE INDEX IDX_B7A1C35FB48D66 ON c_quiz (exercise_category_id);');
        }

        if ($table->hasIndex('course')) {
            $this->addSql('DROP INDEX course ON c_quiz');
        }

        if ($table->hasIndex('session')) {
            $this->addSql('DROP INDEX session ON c_quiz');
        }

        if (false === $table->hasColumn('show_previous_button')) {
            $this->addSql(
                'ALTER TABLE c_quiz ADD COLUMN show_previous_button TINYINT(1) DEFAULT 1 NOT NULL'
            );
        } else {
            $this->addSql('UPDATE c_quiz SET show_previous_button = 1 WHERE show_previous_button IS NULL');
            $this->addSql('ALTER TABLE c_quiz CHANGE show_previous_button show_previous_button TINYINT(1) DEFAULT 1 NOT NULL');
        }

        if (false === $table->hasColumn('notifications')) {
            $this->addSql(
                'ALTER TABLE c_quiz ADD COLUMN notifications VARCHAR(255) NULL DEFAULT NULL;'
            );
        }

        if ($table->hasColumn('page_result_configuration')) {
            $this->addSql(
                'UPDATE c_quiz SET page_result_configuration = "a:0:{}" WHERE page_result_configuration IS NULL ORpage_result_configuration = "" '
            );
            $this->addSql(
                "ALTER TABLE c_quiz CHANGE page_result_configuration page_result_configuration LONGTEXT NOT NULL COMMENT '(DC2Type:array)';"
            );
        } else {
            $this->addSql(
                "ALTER TABLE c_quiz ADD COLUMN page_result_configuration LONGTEXT NOT NULL COMMENT '(DC2Type:array)';"
            );
        }

        $this->addSql('ALTER TABLE c_quiz MODIFY COLUMN save_correct_answers INT NULL DEFAULT NULL');
        if ($table->hasForeignKey('FK_B7A1C35FB48D66')) {
            $this->addSql('ALTER TABLE c_quiz DROP FOREIGN KEY FK_B7A1C35FB48D66');
        }

        $this->addSql('ALTER TABLE c_quiz CHANGE type type INT NOT NULL');

        if (false === $table->hasColumn('resource_node_id')) {
            $this->addSql('ALTER TABLE c_quiz ADD COLUMN resource_node_id INT DEFAULT NULL');
            $this->addSql(
                'ALTER TABLE c_quiz ADD CONSTRAINT FK_B7A1C31BAD783F FOREIGN KEY (resource_node_id) REFERENCES resource_node (id) ON DELETE CASCADE'
            );
            $this->addSql('CREATE UNIQUE INDEX UNIQ_B7A1C31BAD783F ON c_quiz (resource_node_id)');
        }

        if (false === $table->hasColumn('prevent_backwards')) {
            $this->addSql(
                'ALTER TABLE c_quiz ADD prevent_backwards INT DEFAULT 0 NOT NULL'
            );
        }
        $this->addSql('ALTER TABLE c_quiz CHANGE type type INT NOT NULL');

        if ($table->hasForeignKey('FK_B7A1C35FB48D66')) {
            $this->addSql(
                'ALTER TABLE c_quiz ADD CONSTRAINT FK_B7A1C35FB48D66 FOREIGN KEY (exercise_category_id) REFERENCES c_exercise_category (id) ON DELETE SET NULL'
            );
        }

        // answer
        $table = $schema->getTable('c_quiz_answer');
        if ($table->hasColumn('id_auto')) {
            $this->addSql('ALTER TABLE c_quiz_answer DROP id_auto');
        }
        if ($table->hasColumn('id')) {
            $this->addSql('ALTER TABLE c_quiz_answer DROP id');
        }

        $this->addSql('ALTER TABLE c_quiz_answer CHANGE question_id question_id INT DEFAULT NULL');
        if (false === $table->hasForeignKey('FK_AEBC3EFF1E27F6BF')) {
            $this->addSql('ALTER TABLE c_quiz_answer ADD CONSTRAINT FK_AEBC3EFF1E27F6BF FOREIGN KEY (question_id) REFERENCES c_quiz_question (iid) ON DELETE CASCADE');
        }

        // c_quiz_question.
        $table = $schema->getTable('c_quiz_question');
        if (false === $table->hasColumn('resource_node_id')) {
            $this->addSql('ALTER TABLE c_quiz_question ADD resource_node_id INT DEFAULT NULL;');
            $this->addSql(
                'ALTER TABLE c_quiz_question ADD CONSTRAINT FK_9A48A59F1BAD783F FOREIGN KEY (resource_node_id) REFERENCES resource_node (id) ON DELETE CASCADE;'
            );
            $this->addSql('CREATE UNIQUE INDEX UNIQ_9A48A59F1BAD783F ON c_quiz_question (resource_node_id);');
        }

        if (false === $table->hasColumn('mandatory')) {
            $this->addSql('ALTER TABLE c_quiz_question ADD mandatory INT NOT NULL');
        }

        if ($table->hasColumn('id')) {
            $this->addSql('ALTER TABLE c_quiz_question DROP id');
        }

        if (false === $table->hasColumn('feedback')) {
            $this->addSql('ALTER TABLE c_quiz_question ADD feedback LONGTEXT DEFAULT NULL;');
        }

        if ($table->hasIndex('course')) {
            $this->addSql('DROP INDEX course ON c_quiz_question');
        }

        // c_quiz_question_category.
        $table = $schema->getTable('c_quiz_question_category');
        if (false === $table->hasColumn('session_id')) {
            /*$this->addSql('ALTER TABLE c_quiz_question_category ADD session_id INT DEFAULT NULL');
            if (false === $table->hasIndex('IDX_1414369D613FECDF')) {
                $this->addSql('CREATE INDEX IDX_1414369D613FECDF ON c_quiz_question_category (session_id)');
            }
            if (false === $table->hasForeignKey('FK_1414369D613FECDF')) {
                $this->addSql(
                    'ALTER TABLE c_quiz_question_category ADD CONSTRAINT FK_1414369D613FECDF FOREIGN KEY (session_id) REFERENCES session (id)'
                );
            }*/
        }
        $this->addSql('ALTER TABLE c_quiz_question_category CHANGE description description LONGTEXT DEFAULT NULL;');

        if ($table->hasIndex('IDX_1414369D613FECDF')) {
            $this->addSql('DROP INDEX IDX_1414369D613FECDF ON c_quiz_question_category');
        }
        if ($table->hasIndex('course')) {
            $this->addSql('DROP INDEX course ON c_quiz_question_category');
        }

        /*if (false === $table->hasForeignKey('FK_1414369D91D79BD3')) {
            $this->addSql(
                'ALTER TABLE c_quiz_question_category ADD CONSTRAINT FK_1414369D91D79BD3 FOREIGN KEY (c_id) REFERENCES course (id) ON DELETE CASCADE;'
            );
        }*/

        $table = $schema->getTable('c_quiz_question_option');
        if ($table->hasColumn('id')) {
            $this->addSql('ALTER TABLE c_quiz_question_option DROP id');
        }

        if (!$table->hasForeignKey('FK_499A73F31E27F6BF')) {
            $this->addSql('ALTER TABLE c_quiz_question_option ADD CONSTRAINT FK_499A73F31E27F6BF FOREIGN KEY (question_id) REFERENCES c_quiz_question (iid) ON DELETE CASCADE');
            $this->addSql('CREATE INDEX IDX_499A73F31E27F6BF ON c_quiz_question_option (question_id);');
        }

        $table = $schema->getTable('c_quiz_rel_question');

        $this->addSql('UPDATE c_quiz_rel_category SET count_questions = 0 WHERE count_questions IS NULL');
        $this->addSql('ALTER TABLE c_quiz_rel_category CHANGE count_questions count_questions INT NOT NULL');

        $this->addSql('ALTER TABLE c_quiz_rel_category CHANGE exercise_id exercise_id INT DEFAULT NULL');

        if (!$table->hasForeignKey('FK_F8EC662312469DE2')) {
            $this->addSql('ALTER TABLE c_quiz_rel_category ADD CONSTRAINT FK_F8EC662312469DE2 FOREIGN KEY (category_id) REFERENCES c_quiz_question_category (iid) ON DELETE CASCADE;');
        }

        if (!$table->hasIndex('IDX_F8EC662312469DE2')) {
            $this->addSql('CREATE INDEX IDX_F8EC662312469DE2 ON c_quiz_rel_category (category_id)');
        }

        if (!$table->hasIndex('IDX_F8EC6623E934951A')) {
            $this->addSql('CREATE INDEX IDX_F8EC6623E934951A ON c_quiz_rel_category (exercise_id)');
        }

        if (!$table->hasForeignKey('FK_F8EC6623E934951A')) {
            $this->addSql('ALTER TABLE c_quiz_rel_category ADD CONSTRAINT FK_F8EC6623E934951A FOREIGN KEY (exercise_id) REFERENCES c_quiz (iid) ON DELETE CASCADE');
        }

        if ($table->hasIndex('exercise')) {
            $this->addSql('ALTER TABLE c_quiz_rel_question DROP KEY exercise');
        }

        if ($table->hasIndex('course')) {
            $this->addSql('DROP INDEX course ON c_quiz_rel_question');
        }

        $this->addSql('ALTER TABLE c_quiz_rel_question CHANGE question_id question_id INT DEFAULT NULL');

        if ($table->hasColumn('question_id')) {
            $this->addSql(' ALTER TABLE c_quiz_rel_question CHANGE exercice_id quiz_id INT DEFAULT NULL;');
            $this->addSql(
                'ALTER TABLE c_quiz_rel_question ADD CONSTRAINT FK_485736AC853CD175 FOREIGN KEY (quiz_id) REFERENCES c_quiz (iid) ON DELETE CASCADE;'
            );
            $this->addSql('CREATE INDEX exercise ON c_quiz_rel_question (quiz_id);');
        }

        if (false === $table->hasForeignKey('FK_485736AC1E27F6BF')) {
            $this->addSql(
                'ALTER TABLE c_quiz_rel_question ADD CONSTRAINT FK_485736AC1E27F6BF FOREIGN KEY (question_id) REFERENCES c_quiz_question (iid) ON DELETE CASCADE;'
            );
        }

        /*if (false === $table->hasForeignKey('FK_485736AC89D40298')) {
            $this->addSql(
                'ALTER TABLE c_quiz_rel_question ADD CONSTRAINT FK_485736AC89D40298 FOREIGN KEY (quiz_id) REFERENCES c_quiz (iid)'
            );
        }*/

        $table = $schema->getTable('c_quiz_question_category');
        if (false === $table->hasColumn('resource_node_id')) {
            $this->addSql('ALTER TABLE c_quiz_question_category ADD resource_node_id INT DEFAULT NULL');
            $this->addSql(
                'ALTER TABLE c_quiz_question_category ADD CONSTRAINT FK_1414369D1BAD783F FOREIGN KEY (resource_node_id) REFERENCES resource_node (id) ON DELETE CASCADE'
            );
            $this->addSql('CREATE UNIQUE INDEX UNIQ_1414369D1BAD783F ON c_quiz_question_category (resource_node_id)');
        }

        $table = $schema->getTable('c_quiz_question_rel_category');
        $this->addSql('ALTER TABLE c_quiz_question_rel_category MODIFY iid INT NOT NULL');
        if ($table->hasIndex('course')) {
            $this->addSql('DROP INDEX course ON c_quiz_question_rel_category');
        }

        if ($table->hasPrimaryKey()) {
            $this->addSql('ALTER TABLE c_quiz_question_rel_category DROP PRIMARY KEY');
            $this->addSql('ALTER TABLE c_quiz_question_rel_category ADD PRIMARY KEY (category_id, question_id)');
        }
        if (false === $table->hasForeignKey('FK_A468585C12469DE2')) {
            $this->addSql(
                'ALTER TABLE c_quiz_question_rel_category ADD CONSTRAINT FK_A468585C12469DE2 FOREIGN KEY (category_id) REFERENCES c_quiz_question (iid)'
            );
        }

        if (false === $table->hasForeignKey('FK_A468585C1E27F6BF')) {
            $this->addSql(
                'ALTER TABLE c_quiz_question_rel_category ADD CONSTRAINT FK_A468585C1E27F6BF FOREIGN KEY (question_id) REFERENCES c_quiz_question_category (iid)'
            );
        }

        if (false === $table->hasIndex('IDX_A468585C12469DE2')) {
            $this->addSql('CREATE INDEX IDX_A468585C12469DE2 ON c_quiz_question_rel_category (category_id)');
        }

        //$this->addSql('ALTER TABLE c_quiz_question_rel_category ADD PRIMARY KEY (category_id, question_id)');
        /*if ($table->hasIndex('idx_qqrc_qid')) {
            $this->addSql('DROP INDEX idx_qqrc_qid ON c_quiz_question_rel_category');
        }*/
        if (false === $table->hasIndex('IDX_A468585C1E27F6BF')) {
            $this->addSql('CREATE INDEX IDX_A468585C1E27F6BF ON c_quiz_question_rel_category (question_id)');
        }
    }

    public function down(Schema $schema): void
    {
    }
}
