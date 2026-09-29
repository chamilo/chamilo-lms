<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;
use Throwable;

class Version20170904145500 extends AbstractMigrationChamilo
{
    private const int TRACKING_BATCH_SIZE = 1000;
    private const int ATTEMPT_BATCH_SIZE = 50000;
    private const string REFERENCE_STATE_TABLE = 'migration_v20170904145500_reference_state';

    public function getDescription(): string
    {
        return 'c_quiz changes';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        $this->normalizeLegacyQuizReferences($schema);

        if (false === $schema->hasTable('c_exercise_category')) {
            $this->addSql(
                'CREATE TABLE c_exercise_category (id INT AUTO_INCREMENT NOT NULL, c_id INT NOT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, position INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB'
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

        $this->addSql('UPDATE c_quiz SET active = 0 WHERE active IS NULL');
        $this->addSql('ALTER TABLE c_quiz CHANGE active active INT NOT NULL');

        if ($table->hasColumn('exercise_category_id')) {
            $this->addSql('ALTER TABLE c_quiz CHANGE exercise_category_id exercise_category_id INT DEFAULT NULL;');
            if (false === $table->hasForeignKey('FK_B7A1C35FB48D66')) {
                $this->addSql(
                    'ALTER TABLE c_quiz ADD CONSTRAINT FK_B7A1C35FB48D66 FOREIGN KEY (exercise_category_id) REFERENCES c_exercise_category (id) ON DELETE SET NULL'
                );
            }
        } else {
            $this->addSql('ALTER TABLE c_quiz ADD COLUMN exercise_category_id INT DEFAULT NULL;');
            $this->addSql(
                'ALTER TABLE c_quiz ADD CONSTRAINT FK_B7A1C35FB48D66 FOREIGN KEY (exercise_category_id) REFERENCES c_exercise_category (id) ON DELETE SET NULL'
            );
        }

        if (!$table->hasColumn('hide_question_number')) {
            $this->addSql('ALTER TABLE c_quiz ADD hide_question_number INT DEFAULT 0 NOT NULL;');
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
                'UPDATE c_quiz SET page_result_configuration = "[]" WHERE page_result_configuration IS NULL OR page_result_configuration = "" '
            );
            $this->addSql(
                "ALTER TABLE c_quiz CHANGE page_result_configuration page_result_configuration LONGTEXT NOT NULL COMMENT '(DC2Type:json)';"
            );
        } else {
            $this->addSql(
                "ALTER TABLE c_quiz ADD COLUMN page_result_configuration LONGTEXT NOT NULL COMMENT '(DC2Type:json)';"
            );
            $this->addSql(
                'UPDATE c_quiz SET page_result_configuration = "[]"'
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

        $liveAnswerTable = $this->connection
            ->createSchemaManager()
            ->introspectTable('c_quiz_answer')
        ;

        if ($liveAnswerTable->getColumn('question_id')->getNotnull()) {
            $this->connection->executeStatement(
                'ALTER TABLE c_quiz_answer CHANGE question_id question_id INT DEFAULT NULL'
            );
            $liveAnswerTable = $this->connection
                ->createSchemaManager()
                ->introspectTable('c_quiz_answer')
            ;
        }

        if (!$liveAnswerTable->hasForeignKey('FK_AEBC3EFF1E27F6BF')) {
            $this->connection->executeStatement(
                'ALTER TABLE c_quiz_answer
                 ADD CONSTRAINT FK_AEBC3EFF1E27F6BF
                 FOREIGN KEY (question_id)
                 REFERENCES c_quiz_question (iid)
                 ON DELETE CASCADE'
            );
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

        $this->addSql('ALTER TABLE c_quiz_question CHANGE type type INT NOT NULL;');

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

        $liveQuestionOptionTable = $this->connection
            ->createSchemaManager()
            ->introspectTable('c_quiz_question_option')
        ;

        if ($liveQuestionOptionTable->getColumn('question_id')->getNotnull()) {
            $this->connection->executeStatement(
                'ALTER TABLE c_quiz_question_option CHANGE question_id question_id INT DEFAULT NULL'
            );
            $liveQuestionOptionTable = $this->connection
                ->createSchemaManager()
                ->introspectTable('c_quiz_question_option')
            ;
        }

        if (!$liveQuestionOptionTable->hasForeignKey('FK_499A73F31E27F6BF')) {
            $this->connection->executeStatement(
                'ALTER TABLE c_quiz_question_option
                 ADD CONSTRAINT FK_499A73F31E27F6BF
                 FOREIGN KEY (question_id)
                 REFERENCES c_quiz_question (iid)
                 ON DELETE CASCADE'
            );

            $liveQuestionOptionTable = $this->connection
                ->createSchemaManager()
                ->introspectTable('c_quiz_question_option')
            ;
        }

        if (!$liveQuestionOptionTable->hasIndex('IDX_499A73F31E27F6BF')) {
            $this->connection->executeStatement(
                'CREATE INDEX IDX_499A73F31E27F6BF
                 ON c_quiz_question_option (question_id)'
            );
        }

        $table = $schema->getTable('c_quiz_rel_category');

        $this->addSql('UPDATE c_quiz_rel_category SET category_id = NULL WHERE category_id = 0');
        $this->addSql('UPDATE c_quiz_rel_category SET category_id = NULL WHERE category_id NOT IN (SELECT iid FROM c_quiz_question_category)');
        $this->addSql('UPDATE c_quiz_rel_category SET count_questions = 0 WHERE count_questions IS NULL');
        $this->addSql('ALTER TABLE c_quiz_rel_category CHANGE count_questions count_questions INT NOT NULL');
        $this->addSql('UPDATE c_quiz_rel_category SET exercise_id = NULL WHERE exercise_id NOT IN (SELECT iid FROM c_quiz)');
        $this->addSql('ALTER TABLE c_quiz_rel_category CHANGE exercise_id exercise_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE c_quiz_rel_category CHANGE category_id category_id INT DEFAULT NULL');

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

        // c_quiz_rel_question is normalized with direct SQL above. Finalize its schema
        // immediately from a fresh database introspection instead of queueing the legacy
        // column rename through addSql(). This migration is non-transactional and direct
        // normalization changes the live table before Doctrine executes planned SQL, so a
        // stale Schema snapshot can otherwise queue a second rename of exercice_id.
        $this->finalizeQuizQuestionRelationSchema();

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

        if (!$table->hasForeignKey('FK_A468585C12469DE2')) {
            if (null !== $table->getPrimaryKey()) {
                $this->addSql('ALTER TABLE c_quiz_question_rel_category DROP PRIMARY KEY');
                $this->addSql('ALTER TABLE c_quiz_question_rel_category ADD PRIMARY KEY (category_id, question_id)');
            }

            /*$this->addSql(
                'ALTER TABLE c_quiz_question_rel_category ADD CONSTRAINT FK_A468585C12469DE2 FOREIGN KEY (category_id) REFERENCES c_quiz_question (iid)'
            );*/
        }

        /*if (false === $table->hasForeignKey('FK_A468585C1E27F6BF')) {
            $this->addSql(
                'ALTER TABLE c_quiz_question_rel_category ADD CONSTRAINT FK_A468585C1E27F6BF FOREIGN KEY (question_id) REFERENCES c_quiz_question_category (iid)'
            );
        }*/

        if (false === $table->hasIndex('IDX_A468585C12469DE2')) {
            $this->addSql('CREATE INDEX IDX_A468585C12469DE2 ON c_quiz_question_rel_category (category_id)');
        }

        // $this->addSql('ALTER TABLE c_quiz_question_rel_category ADD PRIMARY KEY (category_id, question_id)');
        /*if ($table->hasIndex('idx_qqrc_qid')) {
            $this->addSql('DROP INDEX idx_qqrc_qid ON c_quiz_question_rel_category');
        }*/
        if (false === $table->hasIndex('IDX_A468585C1E27F6BF')) {
            $this->addSql('CREATE INDEX IDX_A468585C1E27F6BF ON c_quiz_question_rel_category (question_id)');
        }
    }

    private function finalizeQuizAnswerSchema(): void
    {
        $schemaManager = $this->connection->createSchemaManager();

        if (!$schemaManager->tablesExist(['c_quiz_answer', 'c_quiz_question'])) {
            return;
        }

        $table = $schemaManager->introspectTable('c_quiz_answer');

        if (!$table->hasColumn('question_id')) {
            return;
        }

        if ($table->getColumn('question_id')->getNotnull()) {
            $this->connection->executeStatement(
                'ALTER TABLE c_quiz_answer
                 CHANGE question_id question_id INT DEFAULT NULL'
            );
        }

        $table = $schemaManager->introspectTable('c_quiz_answer');

        if (!$table->hasForeignKey('FK_AEBC3EFF1E27F6BF')) {
            $this->connection->executeStatement(
                'ALTER TABLE c_quiz_answer
                 ADD CONSTRAINT FK_AEBC3EFF1E27F6BF
                 FOREIGN KEY (question_id)
                 REFERENCES c_quiz_question (iid)
                 ON DELETE CASCADE'
            );
        }
    }

    private function finalizeQuizQuestionOptionSchema(): void
    {
        $schemaManager = $this->connection->createSchemaManager();

        if (!$schemaManager->tablesExist([
            'c_quiz_question_option',
            'c_quiz_question',
        ])) {
            return;
        }

        $table = $schemaManager->introspectTable(
            'c_quiz_question_option'
        );

        if (!$table->hasColumn('question_id')) {
            return;
        }

        if ($table->getColumn('question_id')->getNotnull()) {
            $this->connection->executeStatement(
                'ALTER TABLE c_quiz_question_option
                 CHANGE question_id question_id INT DEFAULT NULL'
            );
        }

        $table = $schemaManager->introspectTable(
            'c_quiz_question_option'
        );

        if (!$table->hasForeignKey('FK_499A73F31E27F6BF')) {
            $this->connection->executeStatement(
                'ALTER TABLE c_quiz_question_option
                 ADD CONSTRAINT FK_499A73F31E27F6BF
                 FOREIGN KEY (question_id)
                 REFERENCES c_quiz_question (iid)
                 ON DELETE CASCADE'
            );
        }

        $table = $schemaManager->introspectTable(
            'c_quiz_question_option'
        );

        if (!$table->hasIndex('IDX_499A73F31E27F6BF')) {
            $this->connection->executeStatement(
                'CREATE INDEX IDX_499A73F31E27F6BF
                 ON c_quiz_question_option (question_id)'
            );
        }
    }

    private function finalizeQuizQuestionRelationSchema(): void
    {
        $schemaManager = $this->connection->createSchemaManager();
        if (!$schemaManager->tablesExist(['c_quiz_rel_question'])) {
            return;
        }

        $table = $schemaManager->introspectTable('c_quiz_rel_question');

        if ($table->hasColumn('exercice_id')) {
            $this->connection->executeStatement(
                'DELETE FROM c_quiz_rel_question WHERE exercice_id = -1'
            );

            if ($table->hasIndex('course')) {
                $this->connection->executeStatement('DROP INDEX course ON c_quiz_rel_question');
            }

            $this->connection->executeStatement(
                'ALTER TABLE c_quiz_rel_question
                 CHANGE question_id question_id INT DEFAULT NULL,
                 CHANGE exercice_id quiz_id INT DEFAULT NULL'
            );
        } elseif ($table->hasColumn('quiz_id')) {
            if ($table->hasIndex('course')) {
                $this->connection->executeStatement('DROP INDEX course ON c_quiz_rel_question');
            }

            if ($table->getColumn('question_id')->getNotnull()) {
                $this->connection->executeStatement(
                    'ALTER TABLE c_quiz_rel_question CHANGE question_id question_id INT DEFAULT NULL'
                );
            }
        } else {
            $this->abortIf(
                true,
                'Cannot finalize c_quiz_rel_question: neither legacy exercice_id nor normalized quiz_id exists.'
            );
        }

        // Refresh metadata after the possible column rename. This also makes the method
        // safe to resume after a non-transactional partial migration.
        $table = $schemaManager->introspectTable('c_quiz_rel_question');

        if (!$table->hasColumn('quiz_id')) {
            $this->abortIf(true, 'Cannot finalize c_quiz_rel_question: quiz_id was not created.');
        }

        if (!$table->hasForeignKey('FK_485736AC853CD175')) {
            $this->connection->executeStatement(
                'ALTER TABLE c_quiz_rel_question
                 ADD CONSTRAINT FK_485736AC853CD175
                 FOREIGN KEY (quiz_id) REFERENCES c_quiz (iid) ON DELETE CASCADE'
            );
        }

        if (!$table->hasForeignKey('FK_485736AC1E27F6BF')) {
            $this->connection->executeStatement(
                'ALTER TABLE c_quiz_rel_question
                 ADD CONSTRAINT FK_485736AC1E27F6BF
                 FOREIGN KEY (question_id) REFERENCES c_quiz_question (iid) ON DELETE CASCADE'
            );
        }

        // Keep the historical index name used by the migration, but do not recreate it if
        // the legacy index survived the column rename and already targets quiz_id.
        $table = $schemaManager->introspectTable('c_quiz_rel_question');
        if (!$table->hasIndex('exercise')) {
            $this->connection->executeStatement(
                'CREATE INDEX exercise ON c_quiz_rel_question (quiz_id)'
            );
        }
    }

    public function postUp(Schema $schema): void
    {
        // Finalize against the live database after all queued addSql() statements.
        // This migration is non-transactional, so the initial Schema snapshot may no
        // longer represent the actual table state after a partial or fresh upgrade.
        $this->finalizeQuizAnswerSchema();
        $this->finalizeQuizQuestionOptionSchema();
        $this->finalizeQuizQuestionRelationSchema();

        // Keep the resumable normalization state until every planned schema statement has succeeded.
        // Doctrine executes addSql() statements after up(), so cleanup must happen in postUp().
        // If the non-transactional migration fails during planned SQL execution, the state table
        // remains available for a safe retry instead of applying id -> iid conversions twice.
        $schemaManager = $this->connection->createSchemaManager();
        if ($schemaManager->tablesExist([self::REFERENCE_STATE_TABLE])) {
            $this->connection->executeStatement('DROP TABLE '.self::REFERENCE_STATE_TABLE);
        }
    }

    private function normalizeLegacyQuizReferences(Schema $schema): void
    {
        if (!$schema->hasTable('c_quiz') || !$schema->hasTable('c_quiz_question')) {
            return;
        }

        $quizTable = $schema->getTable('c_quiz');
        $questionTable = $schema->getTable('c_quiz_question');

        if (!$quizTable->hasColumn('id') || !$questionTable->hasColumn('id')) {
            return;
        }

        $this->ensureReferenceStateTable();
        if ($this->isReferencePhaseCompleted('normalization_complete')) {
            $this->getLogger()->info('Legacy quiz-reference normalization was already completed in a previous migration attempt.');

            return;
        }

        $this->abortIfLegacyQuizKeysAreDuplicated();

        $createdIndexes = [];
        $indexCandidates = [
            ['c_quiz', 'idx_legacy_migration_quiz_course_local_id', ['c_id', 'id']],
            ['c_quiz_question', 'idx_legacy_migration_question_course_local_id', ['c_id', 'id']],
            ['c_quiz_answer', 'idx_legacy_migration_answer_course_question_local_id', ['c_id', 'question_id', 'id']],
            ['c_quiz_answer', 'idx_legacy_migration_answer_course_auto_id', ['c_id', 'id_auto']],
        ];

        foreach ($indexCandidates as [$tableName, $indexName, $columns]) {
            if (!$schema->hasTable($tableName)) {
                continue;
            }

            $table = $schema->getTable($tableName);
            foreach ($columns as $column) {
                if (!$table->hasColumn($column)) {
                    continue 2;
                }
            }

            $createdIndexes[] = [
                $tableName,
                $indexName,
                $columns,
                $this->ensureMigrationIndex($tableName, $indexName, $columns),
            ];
        }

        try {
            $this->normalizeTrackExerciseQuestionLists($schema);
            $this->normalizeQuizAnswers($schema);
            $this->normalizeTrackAttemptReferences($schema);
            $this->normalizeMatchingAnswerReferences($schema);
            $this->normalizeSimpleLegacyQuizReferences($schema);
            $this->normalizeQuizQuestionRelations($schema);
            $this->completeReferencePhase('normalization_complete');
        } finally {
            foreach (array_reverse($createdIndexes) as $index) {
                if (true === ($index[3] ?? false)) {
                    $this->dropMigrationIndex($index[0], $index[1]);
                }
            }
        }
    }

    private function abortIfLegacyQuizKeysAreDuplicated(): void
    {
        $duplicateQuizIds = (int) $this->connection->fetchOne(
            'SELECT COUNT(*)
             FROM (
                 SELECT c_id, id
                 FROM c_quiz
                 WHERE id IS NOT NULL
                 GROUP BY c_id, id
                 HAVING COUNT(*) > 1
             ) duplicate_quizzes'
        );
        $this->abortIf(
            $duplicateQuizIds > 0,
            \sprintf('Cannot safely normalize %d duplicated c_quiz(c_id, id) keys.', $duplicateQuizIds)
        );

        $duplicateQuestionIds = (int) $this->connection->fetchOne(
            'SELECT COUNT(*)
             FROM (
                 SELECT c_id, id
                 FROM c_quiz_question
                 WHERE id IS NOT NULL
                 GROUP BY c_id, id
                 HAVING COUNT(*) > 1
             ) duplicate_questions'
        );
        $this->abortIf(
            $duplicateQuestionIds > 0,
            \sprintf('Cannot safely normalize %d duplicated c_quiz_question(c_id, id) keys.', $duplicateQuestionIds)
        );

        // A legacy local id may legitimately have the same numeric value as another row's iid.
        // They are different identifier namespaces, so this is diagnostic information, not an
        // ambiguity by itself. Reference columns are normalized according to their legacy schema
        // semantics and the phase state prevents a committed conversion from running twice.
        $quizNumericCollisions = (int) $this->connection->fetchOne(
            'SELECT COUNT(*)
             FROM c_quiz legacy_quiz
             INNER JOIN c_quiz normalized_quiz
                ON normalized_quiz.c_id = legacy_quiz.c_id
               AND normalized_quiz.iid = legacy_quiz.id
             WHERE legacy_quiz.iid <> normalized_quiz.iid'
        );
        $questionNumericCollisions = (int) $this->connection->fetchOne(
            'SELECT COUNT(*)
             FROM c_quiz_question legacy_question
             INNER JOIN c_quiz_question normalized_question
                ON normalized_question.c_id = legacy_question.c_id
               AND normalized_question.iid = legacy_question.id
             WHERE legacy_question.iid <> normalized_question.iid'
        );

        $this->getLogger()->info('Checked legacy quiz identifier namespaces.', [
            'quiz_numeric_id_iid_collisions' => $quizNumericCollisions,
            'question_numeric_id_iid_collisions' => $questionNumericCollisions,
        ]);
    }

    private function normalizeTrackExerciseQuestionLists(Schema $schema): void
    {
        if (!$schema->hasTable('track_e_exercises') || $this->isReferencePhaseCompleted('track_question_lists')) {
            return;
        }

        $trackingTable = $schema->getTable('track_e_exercises');
        if (!$trackingTable->hasColumn('exe_id')
            || !$trackingTable->hasColumn('c_id')
            || !$trackingTable->hasColumn('data_tracking')
        ) {
            return;
        }

        $hasQuestionsToCheck = $trackingTable->hasColumn('questions_to_check');
        $lastExerciseId = $this->getReferencePhaseCursor('track_question_lists');
        $updatedRows = 0;
        $unresolvedTokens = 0;

        while (true) {
            $select = 'SELECT exe_id, c_id, data_tracking';
            if ($hasQuestionsToCheck) {
                $select .= ', questions_to_check';
            }

            $rows = $this->connection->fetchAllAssociative(
                $select.'
                 FROM track_e_exercises
                 WHERE exe_id > :lastExerciseId
                 ORDER BY exe_id
                 LIMIT '.self::TRACKING_BATCH_SIZE,
                ['lastExerciseId' => $lastExerciseId]
            );

            if ([] === $rows) {
                $this->completeReferencePhase('track_question_lists', $lastExerciseId);

                break;
            }

            $questionMaps = [];
            $normalizedQuestionIds = [];
            foreach ($rows as $row) {
                $courseId = (int) $row['c_id'];
                if ($courseId <= 0 || isset($questionMaps[$courseId])) {
                    continue;
                }

                $questionRows = $this->connection->fetchAllAssociative(
                    'SELECT id, iid
                     FROM c_quiz_question
                     WHERE c_id = :courseId',
                    ['courseId' => $courseId]
                );

                $questionMaps[$courseId] = [];
                $normalizedQuestionIds[$courseId] = [];
                foreach ($questionRows as $questionRow) {
                    $iid = (int) $questionRow['iid'];
                    $normalizedQuestionIds[$courseId][(string) $iid] = true;

                    if (null !== $questionRow['id']) {
                        $questionMaps[$courseId][(string) (int) $questionRow['id']] = $iid;
                    }
                }
            }

            $updates = [];
            foreach ($rows as $row) {
                $exerciseId = (int) $row['exe_id'];
                $courseId = (int) $row['c_id'];
                $lastExerciseId = $exerciseId;
                $questionMap = $questionMaps[$courseId] ?? [];
                $normalizedIds = $normalizedQuestionIds[$courseId] ?? [];

                $dataTracking = (string) ($row['data_tracking'] ?? '');
                $newDataTracking = $this->remapQuestionIdList(
                    $dataTracking,
                    $questionMap,
                    $normalizedIds,
                    $unresolvedTokens
                );

                $questionsToCheck = $hasQuestionsToCheck
                    ? (string) ($row['questions_to_check'] ?? '')
                    : '';
                $newQuestionsToCheck = $hasQuestionsToCheck
                    ? $this->remapQuestionIdList(
                        $questionsToCheck,
                        $questionMap,
                        $normalizedIds,
                        $unresolvedTokens
                    )
                    : '';

                if ($newDataTracking === $dataTracking
                    && (!$hasQuestionsToCheck || $newQuestionsToCheck === $questionsToCheck)
                ) {
                    continue;
                }

                $updates[] = [
                    'exe_id' => $exerciseId,
                    'data_tracking' => $newDataTracking,
                    'questions_to_check' => $newQuestionsToCheck,
                ];
            }

            $sql = 'UPDATE track_e_exercises SET data_tracking = :dataTracking';
            if ($hasQuestionsToCheck) {
                $sql .= ', questions_to_check = :questionsToCheck';
            }
            $sql .= ' WHERE exe_id = :exerciseId';
            $statement = $this->connection->prepare($sql);

            $this->connection->beginTransaction();

            try {
                foreach ($updates as $update) {
                    $statement->bindValue('dataTracking', $update['data_tracking']);
                    $statement->bindValue('exerciseId', $update['exe_id']);
                    if ($hasQuestionsToCheck) {
                        $statement->bindValue('questionsToCheck', $update['questions_to_check']);
                    }

                    $statement->executeStatement();
                    ++$updatedRows;
                }

                $this->storeReferencePhase('track_question_lists', $lastExerciseId, false);
                $this->connection->commit();
            } catch (Throwable $exception) {
                if ($this->connection->isTransactionActive()) {
                    $this->connection->rollBack();
                }

                throw $exception;
            }
        }

        $this->getLogger()->info('Normalized legacy exercise question lists.', [
            'updated_tracking_rows' => $updatedRows,
            'unresolved_question_tokens_preserved' => $unresolvedTokens,
        ]);
    }

    /**
     * @param array<string, int>  $questionMap
     * @param array<string, bool> $normalizedQuestionIds
     */
    private function remapQuestionIdList(
        string $value,
        array $questionMap,
        array $normalizedQuestionIds,
        int &$unresolvedTokens
    ): string {
        if ('' === trim($value)) {
            return $value;
        }

        $tokens = explode(',', $value);

        foreach ($tokens as &$token) {
            $questionId = trim($token);
            if ('' === $questionId || !ctype_digit($questionId)) {
                continue;
            }

            // These tracking columns are legacy question references. Prefer the explicit
            // (course, legacy id) map even when the same number also exists as another iid.
            if (isset($questionMap[$questionId])) {
                $token = (string) $questionMap[$questionId];

                continue;
            }

            // Keep already-normalized values when there is no legacy mapping for the token.
            if (isset($normalizedQuestionIds[$questionId])) {
                continue;
            }

            ++$unresolvedTokens;
        }
        unset($token);

        return implode(',', $tokens);
    }

    private function normalizeTrackAttemptReferences(Schema $schema): void
    {
        if (!$schema->hasTable('track_e_attempt') || $this->isReferencePhaseCompleted('track_attempt')) {
            return;
        }

        $attemptTable = $schema->getTable('track_e_attempt');
        foreach (['id', 'c_id', 'question_id', 'answer'] as $requiredColumn) {
            if (!$attemptTable->hasColumn($requiredColumn)) {
                return;
            }
        }

        $unresolvedQuestionReferences = (int) $this->connection->fetchOne(
            'SELECT COUNT(*)
             FROM track_e_attempt attempt
             LEFT JOIN c_quiz_question legacy_question
                ON legacy_question.c_id = attempt.c_id
               AND legacy_question.id = attempt.question_id
             LEFT JOIN c_quiz_question normalized_question
                ON normalized_question.c_id = attempt.c_id
               AND normalized_question.iid = attempt.question_id
             WHERE legacy_question.iid IS NULL
               AND normalized_question.iid IS NULL'
        );
        $this->abortIf(
            $unresolvedQuestionReferences > 0,
            \sprintf(
                'Cannot safely normalize %d track_e_attempt rows with unresolved question references.',
                $unresolvedQuestionReferences
            )
        );

        $ambiguousAnswerReferences = (int) $this->connection->fetchOne(
            "SELECT COUNT(*)
             FROM track_e_attempt attempt
             INNER JOIN c_quiz_question legacy_question
                ON legacy_question.c_id = attempt.c_id
               AND legacy_question.id = attempt.question_id
             INNER JOIN (
                 SELECT c_id, question_id, id
                 FROM c_quiz_answer
                 WHERE id IS NOT NULL
                   AND question_id IS NOT NULL
                 GROUP BY c_id, question_id, id
                 HAVING COUNT(*) > 1
             ) duplicate_answer
                ON duplicate_answer.c_id = attempt.c_id
               AND duplicate_answer.question_id = legacy_question.iid
               AND attempt.answer REGEXP '^[0-9]+$'
               AND CAST(attempt.answer AS UNSIGNED) = duplicate_answer.id"
        );
        $this->abortIf(
            $ambiguousAnswerReferences > 0,
            \sprintf(
                'Cannot safely normalize %d track_e_attempt rows that reference a duplicated legacy answer id.',
                $ambiguousAnswerReferences
            )
        );

        $lastAttemptId = $this->getReferencePhaseCursor('track_attempt');
        $updatedRows = 0;
        $batchNumber = 0;

        while (true) {
            $upperAttemptId = $this->connection->fetchOne(
                'SELECT id
                 FROM track_e_attempt
                 WHERE id > :lastAttemptId
                 ORDER BY id
                 LIMIT '.(self::ATTEMPT_BATCH_SIZE - 1).', 1',
                ['lastAttemptId' => $lastAttemptId]
            );

            if (false === $upperAttemptId || null === $upperAttemptId) {
                $upperAttemptId = $this->connection->fetchOne(
                    'SELECT MAX(id)
                     FROM track_e_attempt
                     WHERE id > :lastAttemptId',
                    ['lastAttemptId' => $lastAttemptId]
                );
            }

            if (false === $upperAttemptId || null === $upperAttemptId) {
                $this->completeReferencePhase('track_attempt', $lastAttemptId);

                break;
            }

            $upperAttemptId = (int) $upperAttemptId;
            $this->connection->beginTransaction();

            try {
                $updatedRows += $this->connection->executeStatement(
                    "UPDATE track_e_attempt attempt
                     LEFT JOIN c_quiz_question legacy_question
                        ON legacy_question.c_id = attempt.c_id
                       AND legacy_question.id = attempt.question_id
                     LEFT JOIN c_quiz_question normalized_question
                        ON normalized_question.c_id = attempt.c_id
                       AND normalized_question.iid = attempt.question_id
                     LEFT JOIN c_quiz_answer legacy_answer
                        ON legacy_answer.c_id = attempt.c_id
                       AND legacy_answer.question_id = COALESCE(legacy_question.iid, normalized_question.iid)
                       AND attempt.answer REGEXP '^[0-9]+$'
                       AND CAST(attempt.answer AS UNSIGNED) = legacy_answer.id
                     SET attempt.answer = CASE
                             WHEN legacy_answer.iid IS NOT NULL THEN CAST(legacy_answer.iid AS CHAR)
                             ELSE attempt.answer
                         END,
                         attempt.question_id = COALESCE(legacy_question.iid, normalized_question.iid)
                     WHERE attempt.id > :lastAttemptId
                       AND attempt.id <= :upperAttemptId
                       AND COALESCE(legacy_question.iid, normalized_question.iid) IS NOT NULL
                       AND (
                           attempt.question_id <> COALESCE(legacy_question.iid, normalized_question.iid)
                           OR (
                               legacy_answer.iid IS NOT NULL
                               AND attempt.answer <> CAST(legacy_answer.iid AS CHAR)
                           )
                       )",
                    [
                        'lastAttemptId' => $lastAttemptId,
                        'upperAttemptId' => $upperAttemptId,
                    ]
                );

                $this->storeReferencePhase('track_attempt', $upperAttemptId, false);
                $this->connection->commit();
            } catch (Throwable $exception) {
                if ($this->connection->isTransactionActive()) {
                    $this->connection->rollBack();
                }

                throw $exception;
            }

            $lastAttemptId = $upperAttemptId;
            ++$batchNumber;

            if (0 === $batchNumber % 10) {
                $this->getLogger()->info('Legacy exercise attempt reference normalization progress.', [
                    'last_attempt_id' => $lastAttemptId,
                    'updated_rows' => $updatedRows,
                    'batches' => $batchNumber,
                ]);
            }
        }

        $this->getLogger()->info('Normalized legacy exercise attempt references.', [
            'updated_rows' => $updatedRows,
            'batches' => $batchNumber,
        ]);
    }

    private function normalizeMatchingAnswerReferences(Schema $schema): void
    {
        if (!$schema->hasTable('c_quiz_answer') || $this->isReferencePhaseCompleted('matching_answers')) {
            return;
        }

        $answerTable = $schema->getTable('c_quiz_answer');
        foreach (['c_id', 'question_id', 'correct'] as $requiredColumn) {
            if (!$answerTable->hasColumn($requiredColumn)) {
                return;
            }
        }

        $hasLegacyId = $answerTable->hasColumn('id');
        $hasLegacyAutoId = $answerTable->hasColumn('id_auto');
        if (!$hasLegacyId && !$hasLegacyAutoId) {
            return;
        }

        // Chamilo 1.11.x stores matching-answer targets in id_auto. Older data sets can
        // still carry the per-question legacy id, so use that only when no id_auto match
        // exists. The choice comes from the legacy schema, never from numeric offsets.
        if ($hasLegacyAutoId) {
            $ambiguousAutoReferences = (int) $this->connection->fetchOne(
                'SELECT COUNT(*)
                 FROM c_quiz_answer source_answer
                 INNER JOIN c_quiz_question question
                    ON question.c_id = source_answer.c_id
                   AND question.iid = source_answer.question_id
                 INNER JOIN (
                     SELECT c_id, id_auto
                     FROM c_quiz_answer
                     WHERE id_auto IS NOT NULL
                       AND id_auto > 0
                     GROUP BY c_id, id_auto
                     HAVING COUNT(*) > 1
                 ) duplicate_auto
                    ON duplicate_auto.c_id = source_answer.c_id
                   AND duplicate_auto.id_auto = source_answer.correct
                 WHERE question.type = 4
                   AND source_answer.correct > 0'
            );
            $this->abortIf(
                $ambiguousAutoReferences > 0,
                \sprintf(
                    'Cannot safely normalize %d matching-answer rows that reference a duplicated legacy id_auto.',
                    $ambiguousAutoReferences
                )
            );
        }

        if ($hasLegacyId) {
            $autoNotExistsCondition = $hasLegacyAutoId
                ? 'AND NOT EXISTS (
                       SELECT 1
                       FROM c_quiz_answer auto_target
                       WHERE auto_target.c_id = source_answer.c_id
                         AND auto_target.id_auto = source_answer.correct
                   )'
                : '';

            $ambiguousLocalIdReferences = (int) $this->connection->fetchOne(
                'SELECT COUNT(*)
                 FROM c_quiz_answer source_answer
                 INNER JOIN c_quiz_question question
                    ON question.c_id = source_answer.c_id
                   AND question.iid = source_answer.question_id
                 INNER JOIN (
                     SELECT c_id, question_id, id
                     FROM c_quiz_answer
                     WHERE id IS NOT NULL
                     GROUP BY c_id, question_id, id
                     HAVING COUNT(*) > 1
                 ) duplicate_local
                    ON duplicate_local.c_id = source_answer.c_id
                   AND duplicate_local.question_id = source_answer.question_id
                   AND duplicate_local.id = source_answer.correct
                 WHERE question.type = 4
                   AND source_answer.correct > 0
                   '.$autoNotExistsCondition
            );
            $this->abortIf(
                $ambiguousLocalIdReferences > 0,
                \sprintf(
                    'Cannot safely normalize %d matching-answer rows that reference a duplicated per-question legacy answer id.',
                    $ambiguousLocalIdReferences
                )
            );
        }

        $resolutionChecks = [];
        if ($hasLegacyAutoId) {
            $resolutionChecks[] = 'EXISTS (
                SELECT 1
                FROM c_quiz_answer auto_target
                WHERE auto_target.c_id = source_answer.c_id
                  AND auto_target.id_auto = source_answer.correct
            )';
        }
        if ($hasLegacyId) {
            $resolutionChecks[] = 'EXISTS (
                SELECT 1
                FROM c_quiz_answer local_target
                WHERE local_target.c_id = source_answer.c_id
                  AND local_target.question_id = source_answer.question_id
                  AND local_target.id = source_answer.correct
            )';
        }
        $resolutionChecks[] = 'EXISTS (
            SELECT 1
            FROM c_quiz_answer normalized_target
            WHERE normalized_target.c_id = source_answer.c_id
              AND normalized_target.question_id = source_answer.question_id
              AND normalized_target.iid = source_answer.correct
        )';

        $unresolvedMatchingReferences = (int) $this->connection->fetchOne(
            'SELECT COUNT(*)
             FROM c_quiz_answer source_answer
             INNER JOIN c_quiz_question question
                ON question.c_id = source_answer.c_id
               AND question.iid = source_answer.question_id
             WHERE question.type = 4
               AND source_answer.correct > 0
               AND NOT ('.implode(' OR ', $resolutionChecks).')'
        );
        $this->abortIf(
            $unresolvedMatchingReferences > 0,
            \sprintf(
                'Cannot safely normalize %d matching-answer references in c_quiz_answer.correct.',
                $unresolvedMatchingReferences
            )
        );

        $joins = [];
        $targets = [];
        if ($hasLegacyAutoId) {
            $joins[] = 'LEFT JOIN c_quiz_answer auto_target
                ON auto_target.c_id = source_answer.c_id
               AND auto_target.id_auto = source_answer.correct';
            $targets[] = 'auto_target.iid';
        }
        if ($hasLegacyId) {
            $autoMissing = $hasLegacyAutoId ? 'auto_target.iid IS NULL AND ' : '';
            $joins[] = 'LEFT JOIN c_quiz_answer local_target
                ON '.$autoMissing.'local_target.c_id = source_answer.c_id
               AND local_target.question_id = source_answer.question_id
               AND local_target.id = source_answer.correct';
            $targets[] = 'local_target.iid';
        }
        $joins[] = 'LEFT JOIN c_quiz_answer normalized_target
            ON normalized_target.c_id = source_answer.c_id
           AND normalized_target.question_id = source_answer.question_id
           AND normalized_target.iid = source_answer.correct';
        $targets[] = 'normalized_target.iid';
        $targetExpression = 'COALESCE('.implode(', ', $targets).')';

        $updatedRows = 0;
        $this->runReferencePhase('matching_answers', function () use (&$updatedRows, $joins, $targetExpression): void {
            $updatedRows = $this->connection->executeStatement(
                'UPDATE c_quiz_answer source_answer
                 INNER JOIN c_quiz_question question
                    ON question.c_id = source_answer.c_id
                   AND question.iid = source_answer.question_id
                   AND question.type = 4
                 '.implode("\n                 ", $joins).'
                 SET source_answer.correct = '.$targetExpression.'
                 WHERE source_answer.correct > 0
                   AND '.$targetExpression.' IS NOT NULL
                   AND source_answer.correct <> '.$targetExpression
            );
        });

        $this->getLogger()->info('Normalized legacy matching-answer references.', [
            'updated_rows' => $updatedRows,
        ]);
    }

    private function normalizeSimpleLegacyQuizReferences(Schema $schema): void
    {
        if ($schema->hasTable('c_item_property')) {
            $itemProperty = $schema->getTable('c_item_property');
            if ($itemProperty->hasColumn('c_id')
                && $itemProperty->hasColumn('ref')
                && $itemProperty->hasColumn('tool')
                && $itemProperty->hasColumn('lastedit_type')
            ) {
                $quizProperties = 0;
                $questionProperties = 0;
                $this->runReferencePhase('item_property', function () use (&$quizProperties, &$questionProperties): void {
                    $quizProperties = $this->connection->executeStatement(
                        "UPDATE c_item_property item
                         INNER JOIN c_quiz quiz
                            ON quiz.c_id = item.c_id
                           AND quiz.id = item.ref
                         SET item.ref = quiz.iid
                         WHERE item.tool = 'quiz'
                           AND item.lastedit_type IN ('QuizDeleted', 'QuizUpdated')
                           AND item.ref <> quiz.iid"
                    );
                    $questionProperties = $this->connection->executeStatement(
                        "UPDATE c_item_property item
                         INNER JOIN c_quiz_question question
                            ON question.c_id = item.c_id
                           AND question.id = item.ref
                         SET item.ref = question.iid
                         WHERE item.tool = 'quiz'
                           AND item.lastedit_type IN ('QuizQuestionUpdated', 'QuizQuestionDeleted')
                           AND item.ref <> question.iid"
                    );
                });

                $this->getLogger()->info('Normalized legacy quiz item-property references.', [
                    'quiz_rows' => $quizProperties,
                    'question_rows' => $questionProperties,
                ]);
            }
        }

        if ($schema->hasTable('track_e_exercises')) {
            $trackingTable = $schema->getTable('track_e_exercises');
            if ($trackingTable->hasColumn('c_id') && $trackingTable->hasColumn('exe_exo_id')) {
                $updatedRows = 0;
                $this->runReferencePhase('track_exercise_quiz', function () use (&$updatedRows): void {
                    $updatedRows = $this->connection->executeStatement(
                        'UPDATE track_e_exercises tracking
                         INNER JOIN c_quiz quiz
                            ON quiz.c_id = tracking.c_id
                           AND quiz.id = tracking.exe_exo_id
                         SET tracking.exe_exo_id = quiz.iid
                         WHERE tracking.exe_exo_id <> quiz.iid'
                    );
                });

                $unresolvedRows = (int) $this->connection->fetchOne(
                    'SELECT COUNT(*)
                     FROM track_e_exercises tracking
                     LEFT JOIN c_quiz quiz
                        ON quiz.c_id = tracking.c_id
                       AND quiz.iid = tracking.exe_exo_id
                     WHERE quiz.iid IS NULL'
                );

                $this->getLogger()->info('Normalized legacy exercise tracking quiz references.', [
                    'updated_rows' => $updatedRows,
                    'unresolved_tracking_rows_preserved' => $unresolvedRows,
                ]);
            }
        }

        if ($schema->hasTable('c_lp_item')) {
            $lpItem = $schema->getTable('c_lp_item');
            if ($lpItem->hasColumn('c_id') && $lpItem->hasColumn('item_type') && $lpItem->hasColumn('path')) {
                $updatedRows = 0;
                $this->runReferencePhase('lp_quiz', function () use (&$updatedRows): void {
                    $updatedRows = $this->connection->executeStatement(
                        "UPDATE c_lp_item item
                         INNER JOIN c_quiz quiz
                            ON quiz.c_id = item.c_id
                           AND item.path = CAST(quiz.id AS CHAR)
                         SET item.path = CAST(quiz.iid AS CHAR)
                         WHERE item.item_type = 'quiz'
                           AND item.path <> CAST(quiz.iid AS CHAR)"
                    );
                });

                $this->getLogger()->info('Normalized legacy learning-path quiz references.', [
                    'updated_rows' => $updatedRows,
                ]);
            }
        }

        if ($schema->hasTable('gradebook_link')) {
            $gradebookLink = $schema->getTable('gradebook_link');
            if ($gradebookLink->hasColumn('ref_id') && $gradebookLink->hasColumn('type')) {
                $updatedRows = 0;
                $this->runReferencePhase('gradebook_quiz', function () use ($gradebookLink, &$updatedRows): void {
                    if ($gradebookLink->hasColumn('course_code')) {
                        $updatedRows = $this->connection->executeStatement(
                            'UPDATE gradebook_link grade_link
                             INNER JOIN course course_row
                                ON course_row.code = grade_link.course_code
                             INNER JOIN c_quiz quiz
                                ON quiz.c_id = course_row.id
                               AND quiz.id = grade_link.ref_id
                             SET grade_link.ref_id = quiz.iid
                             WHERE grade_link.type = 1
                               AND grade_link.ref_id <> quiz.iid'
                        );
                    } elseif ($gradebookLink->hasColumn('c_id')) {
                        $updatedRows = $this->connection->executeStatement(
                            'UPDATE gradebook_link grade_link
                             INNER JOIN c_quiz quiz
                                ON quiz.c_id = grade_link.c_id
                               AND quiz.id = grade_link.ref_id
                             SET grade_link.ref_id = quiz.iid
                             WHERE grade_link.type = 1
                               AND grade_link.ref_id <> quiz.iid'
                        );
                    }
                });

                $this->getLogger()->info('Normalized legacy gradebook exercise references.', [
                    'updated_rows' => $updatedRows,
                ]);
            }
        }

        if ($schema->hasTable('c_quiz_question_option')) {
            $table = $schema->getTable('c_quiz_question_option');
            if ($table->hasColumn('c_id') && $table->hasColumn('question_id')) {
                $this->runReferencePhase('question_option', function (): void {
                    $this->connection->executeStatement(
                        'UPDATE c_quiz_question_option question_option
                         INNER JOIN c_quiz_question question
                            ON question.c_id = question_option.c_id
                           AND question.id = question_option.question_id
                         SET question_option.question_id = question.iid
                         WHERE question_option.question_id <> question.iid'
                    );
                });
            }
        }

        if ($schema->hasTable('c_quiz_question_rel_category')) {
            $table = $schema->getTable('c_quiz_question_rel_category');
            if ($table->hasColumn('c_id') && $table->hasColumn('question_id')) {
                $this->runReferencePhase('question_rel_category', function (): void {
                    $this->connection->executeStatement(
                        'UPDATE c_quiz_question_rel_category relation
                         INNER JOIN c_quiz_question question
                            ON question.c_id = relation.c_id
                           AND question.id = relation.question_id
                         SET relation.question_id = question.iid
                         WHERE relation.question_id <> question.iid'
                    );
                });
            }
        }

        if ($schema->hasTable('c_quiz_rel_category')) {
            $table = $schema->getTable('c_quiz_rel_category');
            if ($table->hasColumn('c_id') && $table->hasColumn('exercise_id')) {
                $this->runReferencePhase('quiz_rel_category', function (): void {
                    $this->connection->executeStatement(
                        'UPDATE c_quiz_rel_category relation
                         INNER JOIN c_quiz quiz
                            ON quiz.c_id = relation.c_id
                           AND quiz.id = relation.exercise_id
                         SET relation.exercise_id = quiz.iid
                         WHERE relation.exercise_id IS NOT NULL
                           AND relation.exercise_id <> quiz.iid'
                    );
                });
            }
        }
    }

    private function normalizeQuizQuestionRelations(Schema $schema): void
    {
        if (!$schema->hasTable('c_quiz_rel_question') || $this->isReferencePhaseCompleted('quiz_question_relations')) {
            return;
        }

        $relationTable = $schema->getTable('c_quiz_rel_question');
        if (!$relationTable->hasColumn('c_id')
            || !$relationTable->hasColumn('question_id')
            || !$relationTable->hasColumn('exercice_id')
        ) {
            return;
        }

        $orphanQuizRows = (int) $this->connection->fetchOne(
            'SELECT COUNT(*)
             FROM c_quiz_rel_question relation
             LEFT JOIN c_quiz legacy_quiz
                ON legacy_quiz.c_id = relation.c_id
               AND legacy_quiz.id = relation.exercice_id
             LEFT JOIN c_quiz normalized_quiz
                ON normalized_quiz.c_id = relation.c_id
               AND normalized_quiz.iid = relation.exercice_id
             WHERE relation.exercice_id <> -1
               AND legacy_quiz.iid IS NULL
               AND normalized_quiz.iid IS NULL'
        );
        $orphanQuestionRows = (int) $this->connection->fetchOne(
            'SELECT COUNT(*)
             FROM c_quiz_rel_question relation
             LEFT JOIN c_quiz_question legacy_question
                ON legacy_question.c_id = relation.c_id
               AND legacy_question.id = relation.question_id
             LEFT JOIN c_quiz_question normalized_question
                ON normalized_question.c_id = relation.c_id
               AND normalized_question.iid = relation.question_id
             WHERE relation.exercice_id <> -1
               AND legacy_question.iid IS NULL
               AND normalized_question.iid IS NULL'
        );

        if ($orphanQuizRows > 0 || $orphanQuestionRows > 0) {
            $this->getLogger()->warning('Preserving orphan legacy quiz-question relations with null references before adding foreign keys.', [
                'missing_quiz_rows' => $orphanQuizRows,
                'missing_question_rows' => $orphanQuestionRows,
            ]);

            // Re-running this CHANGE is harmless and keeps the data update below resumable.
            $this->connection->executeStatement(
                'ALTER TABLE c_quiz_rel_question
                 CHANGE exercice_id exercice_id INT DEFAULT NULL,
                 CHANGE question_id question_id INT DEFAULT NULL'
            );
        }

        $updatedRows = 0;
        $this->runReferencePhase('quiz_question_relations', function () use (&$updatedRows): void {
            $updatedRows = $this->connection->executeStatement(
                'UPDATE c_quiz_rel_question relation
                 LEFT JOIN c_quiz legacy_quiz
                    ON legacy_quiz.c_id = relation.c_id
                   AND legacy_quiz.id = relation.exercice_id
                 LEFT JOIN c_quiz normalized_quiz
                    ON normalized_quiz.c_id = relation.c_id
                   AND normalized_quiz.iid = relation.exercice_id
                 LEFT JOIN c_quiz_question legacy_question
                    ON legacy_question.c_id = relation.c_id
                   AND legacy_question.id = relation.question_id
                 LEFT JOIN c_quiz_question normalized_question
                    ON normalized_question.c_id = relation.c_id
                   AND normalized_question.iid = relation.question_id
                 SET relation.exercice_id = COALESCE(legacy_quiz.iid, normalized_quiz.iid),
                     relation.question_id = COALESCE(legacy_question.iid, normalized_question.iid)
                 WHERE relation.exercice_id <> -1
                   AND (
                       relation.exercice_id <> COALESCE(legacy_quiz.iid, normalized_quiz.iid)
                       OR relation.question_id <> COALESCE(legacy_question.iid, normalized_question.iid)
                       OR COALESCE(legacy_quiz.iid, normalized_quiz.iid) IS NULL
                       OR COALESCE(legacy_question.iid, normalized_question.iid) IS NULL
                   )'
            );
        });

        $this->getLogger()->info('Normalized legacy quiz-question relations.', [
            'updated_rows' => $updatedRows,
            'orphan_rows_preserved_with_null_reference' => $orphanQuizRows + $orphanQuestionRows,
        ]);
    }

    private function normalizeQuizAnswers(Schema $schema): void
    {
        if (!$schema->hasTable('c_quiz_answer') || $this->isReferencePhaseCompleted('quiz_answers')) {
            return;
        }

        $answerTable = $schema->getTable('c_quiz_answer');
        if (!$answerTable->hasColumn('c_id') || !$answerTable->hasColumn('question_id')) {
            return;
        }

        $orphanRows = (int) $this->connection->fetchOne(
            'SELECT COUNT(*)
             FROM c_quiz_answer answer_row
             LEFT JOIN c_quiz_question legacy_question
                ON legacy_question.c_id = answer_row.c_id
               AND legacy_question.id = answer_row.question_id
             LEFT JOIN c_quiz_question normalized_question
                ON normalized_question.c_id = answer_row.c_id
               AND normalized_question.iid = answer_row.question_id
             WHERE legacy_question.iid IS NULL
               AND normalized_question.iid IS NULL'
        );

        if ($orphanRows > 0) {
            $this->getLogger()->warning('Preserving orphan legacy quiz answers with a null question reference before adding the foreign key.', [
                'orphan_rows' => $orphanRows,
            ]);

            // Make the column nullable before the resumable data phase. Repeating this DDL after
            // an interrupted run is safe and avoids collecting potentially large iid lists in PHP.
            $this->connection->executeStatement(
                'ALTER TABLE c_quiz_answer CHANGE question_id question_id INT DEFAULT NULL'
            );
        }

        $updatedRows = 0;
        $this->runReferencePhase('quiz_answers', function () use ($orphanRows, &$updatedRows): void {
            if ($orphanRows > 0) {
                $this->connection->executeStatement(
                    'UPDATE c_quiz_answer answer_row
                     LEFT JOIN c_quiz_question legacy_question
                        ON legacy_question.c_id = answer_row.c_id
                       AND legacy_question.id = answer_row.question_id
                     LEFT JOIN c_quiz_question normalized_question
                        ON normalized_question.c_id = answer_row.c_id
                       AND normalized_question.iid = answer_row.question_id
                     SET answer_row.question_id = NULL
                     WHERE legacy_question.iid IS NULL
                       AND normalized_question.iid IS NULL'
                );
            }

            $updatedRows = $this->connection->executeStatement(
                'UPDATE c_quiz_answer answer_row
                 INNER JOIN c_quiz_question legacy_question
                    ON legacy_question.c_id = answer_row.c_id
                   AND legacy_question.id = answer_row.question_id
                 SET answer_row.question_id = legacy_question.iid
                 WHERE answer_row.question_id <> legacy_question.iid'
            );
        });

        $this->getLogger()->info('Normalized legacy quiz-answer question references.', [
            'updated_rows' => $updatedRows,
            'orphan_rows_preserved_with_null_reference' => $orphanRows,
        ]);
    }

    private function ensureReferenceStateTable(): void
    {
        $this->connection->executeStatement(
            'CREATE TABLE IF NOT EXISTS '.self::REFERENCE_STATE_TABLE.' (
                phase VARCHAR(100) NOT NULL,
                cursor_value BIGINT NOT NULL DEFAULT 0,
                completed TINYINT(1) NOT NULL DEFAULT 0,
                PRIMARY KEY (phase)
            ) ENGINE=InnoDB'
        );
    }

    private function isReferencePhaseCompleted(string $phase): bool
    {
        return 1 === (int) $this->connection->fetchOne(
            'SELECT COALESCE(MAX(completed), 0) FROM '.self::REFERENCE_STATE_TABLE.' WHERE phase = :phase',
            ['phase' => $phase]
        );
    }

    private function getReferencePhaseCursor(string $phase): int
    {
        $cursor = $this->connection->fetchOne(
            'SELECT cursor_value FROM '.self::REFERENCE_STATE_TABLE.' WHERE phase = :phase',
            ['phase' => $phase]
        );

        return false === $cursor || null === $cursor ? 0 : (int) $cursor;
    }

    private function storeReferencePhase(string $phase, int $cursor, bool $completed): void
    {
        $this->connection->executeStatement(
            'INSERT INTO '.self::REFERENCE_STATE_TABLE.' (phase, cursor_value, completed)
             VALUES (:phase, :cursor, :completed)
             ON DUPLICATE KEY UPDATE
                cursor_value = VALUES(cursor_value),
                completed = VALUES(completed)',
            [
                'phase' => $phase,
                'cursor' => $cursor,
                'completed' => $completed ? 1 : 0,
            ]
        );
    }

    private function completeReferencePhase(string $phase, int $cursor = 0): void
    {
        $this->storeReferencePhase($phase, $cursor, true);
    }

    private function runReferencePhase(string $phase, callable $callback): void
    {
        if ($this->isReferencePhaseCompleted($phase)) {
            return;
        }

        $this->connection->beginTransaction();

        try {
            $callback();
            $this->completeReferencePhase($phase);
            $this->connection->commit();
        } catch (Throwable $exception) {
            if ($this->connection->isTransactionActive()) {
                $this->connection->rollBack();
            }

            throw $exception;
        }
    }

    /**
     * @param string[] $columns
     */
    private function ensureMigrationIndex(string $tableName, string $indexName, array $columns): bool
    {
        try {
            $schemaManager = $this->connection->createSchemaManager();
            foreach ($schemaManager->listTableIndexes($tableName) as $index) {
                $existingColumns = array_map('strtolower', $index->getColumns());
                $expectedColumns = array_map('strtolower', $columns);

                if ($expectedColumns === \array_slice($existingColumns, 0, \count($expectedColumns))) {
                    return false;
                }
            }

            $this->connection->executeStatement(
                \sprintf(
                    'CREATE INDEX %s ON %s (%s)',
                    $indexName,
                    $tableName,
                    implode(', ', $columns)
                )
            );

            return true;
        } catch (Throwable $exception) {
            $this->getLogger()->warning('Could not create a temporary legacy quiz migration index.', [
                'table' => $tableName,
                'index' => $indexName,
                'error' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    private function dropMigrationIndex(string $tableName, string $indexName): void
    {
        try {
            $schemaManager = $this->connection->createSchemaManager();
            $indexExists = false;
            foreach ($schemaManager->listTableIndexes($tableName) as $index) {
                if (strtolower($index->getName()) === strtolower($indexName)) {
                    $indexExists = true;

                    break;
                }
            }

            if (!$indexExists) {
                return;
            }

            $this->connection->executeStatement(
                \sprintf('DROP INDEX %s ON %s', $indexName, $tableName)
            );
        } catch (Throwable $exception) {
            $this->getLogger()->warning('Could not drop a temporary legacy quiz migration index.', [
                'table' => $tableName,
                'index' => $indexName,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    public function down(Schema $schema): void {}
}
