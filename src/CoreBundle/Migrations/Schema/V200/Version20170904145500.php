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

        if (!$table->hasForeignKey('FK_499A73F31E27F6BF')) {
            $this->addSql('ALTER TABLE c_quiz_question_option ADD CONSTRAINT FK_499A73F31E27F6BF FOREIGN KEY (question_id) REFERENCES c_quiz_question (iid) ON DELETE CASCADE');
            $this->addSql('CREATE INDEX IDX_499A73F31E27F6BF ON c_quiz_question_option (question_id);');
        }

        $table = $schema->getTable('c_quiz_rel_category');

        $this->addSql('UPDATE c_quiz_rel_category SET category_id = NULL WHERE category_id = 0');
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

        $table = $schema->getTable('c_quiz_rel_question');
        if ($table->hasColumn('exercice_id')) {
            $this->addSql('DELETE FROM c_quiz_rel_question WHERE exercice_id = -1 ');
        }

        if ($table->hasIndex('exercise')) {
            $this->addSql('ALTER TABLE c_quiz_rel_question DROP KEY exercise');
        }

        if ($table->hasIndex('course')) {
            $this->addSql('DROP INDEX course ON c_quiz_rel_question');
        }

        $this->addSql('ALTER TABLE c_quiz_rel_question CHANGE question_id question_id INT DEFAULT NULL');

        if ($table->hasColumn('exercice_id')) {
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

        $this->abortIfLegacyQuizIdsAreAmbiguous($schema);

        $createdIndexes = [];
        $indexCandidates = [
            ['c_quiz', 'idx_legacy_migration_quiz_course_local_id', ['c_id', 'id']],
            ['c_quiz_question', 'idx_legacy_migration_question_course_local_id', ['c_id', 'id']],
            ['c_quiz_answer', 'idx_legacy_migration_answer_course_question_local_id', ['c_id', 'question_id', 'id']],
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
            $this->normalizeTrackAttemptReferences($schema);
            $this->normalizeMatchingAnswerReferences($schema);
            $this->normalizeSimpleLegacyQuizReferences($schema);
            $this->normalizeQuizQuestionRelations($schema);
            $this->normalizeQuizAnswers($schema);
        } finally {
            foreach (array_reverse($createdIndexes) as $index) {
                if (true === ($index[3] ?? false)) {
                    $this->dropMigrationIndex($index[0], $index[1]);
                }
            }
        }
    }

    private function abortIfLegacyQuizIdsAreAmbiguous(Schema $schema): void
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

        if (!$schema->hasTable('c_quiz_answer')) {
            return;
        }

        $answerTable = $schema->getTable('c_quiz_answer');
        if (!$answerTable->hasColumn('id') || !$answerTable->hasColumn('question_id')) {
            return;
        }

        $duplicateAnswerIds = (int) $this->connection->fetchOne(
            'SELECT COUNT(*)
             FROM (
                 SELECT c_id, question_id, id
                 FROM c_quiz_answer
                 WHERE id IS NOT NULL
                 GROUP BY c_id, question_id, id
                 HAVING COUNT(*) > 1
             ) duplicate_answers'
        );
        $this->abortIf(
            $duplicateAnswerIds > 0,
            \sprintf('Cannot safely normalize %d duplicated legacy answer keys.', $duplicateAnswerIds)
        );
    }

    private function normalizeTrackExerciseQuestionLists(Schema $schema): void
    {
        if (!$schema->hasTable('track_e_exercises')) {
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
        $courseIds = $this->connection->fetchFirstColumn(
            'SELECT DISTINCT c_id FROM track_e_exercises ORDER BY c_id'
        );

        $updatedRows = 0;
        $unresolvedTokens = 0;

        foreach ($courseIds as $courseIdValue) {
            $courseId = (int) $courseIdValue;
            $questionRows = $this->connection->fetchAllAssociative(
                'SELECT id, iid
                 FROM c_quiz_question
                 WHERE c_id = :courseId
                   AND id IS NOT NULL',
                ['courseId' => $courseId]
            );

            $questionMap = [];
            foreach ($questionRows as $questionRow) {
                $questionMap[(string) (int) $questionRow['id']] = (int) $questionRow['iid'];
            }

            $lastExerciseId = 0;

            while (true) {
                $select = 'SELECT exe_id, data_tracking';
                if ($hasQuestionsToCheck) {
                    $select .= ', questions_to_check';
                }

                $rows = $this->connection->fetchAllAssociative(
                    $select.'
                     FROM track_e_exercises
                     WHERE c_id = :courseId
                       AND exe_id > :lastExerciseId
                     ORDER BY exe_id
                     LIMIT '.self::TRACKING_BATCH_SIZE,
                    [
                        'courseId' => $courseId,
                        'lastExerciseId' => $lastExerciseId,
                    ]
                );

                if ([] === $rows) {
                    break;
                }

                $updates = [];
                foreach ($rows as $row) {
                    $exerciseId = (int) $row['exe_id'];
                    $lastExerciseId = $exerciseId;

                    $dataTracking = (string) ($row['data_tracking'] ?? '');
                    $newDataTracking = $this->remapQuestionIdList(
                        $dataTracking,
                        $questionMap,
                        $unresolvedTokens
                    );

                    $questionsToCheck = $hasQuestionsToCheck
                        ? (string) ($row['questions_to_check'] ?? '')
                        : '';
                    $newQuestionsToCheck = $hasQuestionsToCheck
                        ? $this->remapQuestionIdList($questionsToCheck, $questionMap, $unresolvedTokens)
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

                if ([] === $updates) {
                    continue;
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
                        $parameters = [
                            'dataTracking' => $update['data_tracking'],
                            'exerciseId' => $update['exe_id'],
                        ];
                        if ($hasQuestionsToCheck) {
                            $parameters['questionsToCheck'] = $update['questions_to_check'];
                        }

                        $statement->bindValue('dataTracking', $parameters['dataTracking']);
                        $statement->bindValue('exerciseId', $parameters['exerciseId']);
                        if ($hasQuestionsToCheck) {
                            $statement->bindValue('questionsToCheck', $parameters['questionsToCheck']);
                        }

                        $statement->executeStatement();
                        ++$updatedRows;
                    }

                    $this->connection->commit();
                } catch (Throwable $exception) {
                    if ($this->connection->isTransactionActive()) {
                        $this->connection->rollBack();
                    }

                    throw $exception;
                }
            }
        }

        $this->getLogger()->info('Normalized legacy exercise question lists.', [
            'updated_tracking_rows' => $updatedRows,
            'unresolved_question_tokens_preserved' => $unresolvedTokens,
        ]);
    }

    /**
     * @param array<string, int> $questionMap
     */
    private function remapQuestionIdList(string $value, array $questionMap, int &$unresolvedTokens): string
    {
        if ('' === trim($value)) {
            return $value;
        }

        $tokens = explode(',', $value);

        foreach ($tokens as &$token) {
            $questionId = trim($token);
            if ('' === $questionId || !ctype_digit($questionId)) {
                continue;
            }

            if (isset($questionMap[$questionId])) {
                $token = (string) $questionMap[$questionId];
                continue;
            }

            ++$unresolvedTokens;
        }
        unset($token);

        return implode(',', $tokens);
    }

    private function normalizeTrackAttemptReferences(Schema $schema): void
    {
        if (!$schema->hasTable('track_e_attempt')) {
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
             LEFT JOIN c_quiz_question question
                ON question.c_id = attempt.c_id
               AND question.id = attempt.question_id
             WHERE question.iid IS NULL'
        );
        $this->abortIf(
            $unresolvedQuestionReferences > 0,
            \sprintf(
                'Cannot safely normalize %d track_e_attempt rows with unresolved legacy question references.',
                $unresolvedQuestionReferences
            )
        );

        $lastAttemptId = 0;
        $updatedRows = 0;
        $batchNumber = 0;
        $batchSize = 50000;

        while (true) {
            $upperAttemptId = $this->connection->fetchOne(
                'SELECT id
                 FROM track_e_attempt
                 WHERE id > :lastAttemptId
                 ORDER BY id
                 LIMIT '.($batchSize - 1).', 1',
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
                break;
            }

            $upperAttemptId = (int) $upperAttemptId;
            $updatedRows += $this->connection->executeStatement(
                'UPDATE track_e_attempt attempt
                 INNER JOIN c_quiz_question question
                    ON question.c_id = attempt.c_id
                   AND question.id = attempt.question_id
                 LEFT JOIN c_quiz_answer answer
                    ON answer.c_id = attempt.c_id
                   AND answer.question_id = attempt.question_id
                   AND CAST(answer.id AS CHAR) = attempt.answer
                 SET attempt.answer = CASE
                         WHEN answer.iid IS NOT NULL THEN CAST(answer.iid AS CHAR)
                         ELSE attempt.answer
                     END,
                     attempt.question_id = question.iid
                 WHERE attempt.id > :lastAttemptId
                   AND attempt.id <= :upperAttemptId
                   AND (
                       attempt.question_id <> question.iid
                       OR (answer.iid IS NOT NULL AND attempt.answer <> CAST(answer.iid AS CHAR))
                   )',
                [
                    'lastAttemptId' => $lastAttemptId,
                    'upperAttemptId' => $upperAttemptId,
                ]
            );

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
        if (!$schema->hasTable('c_quiz_answer')) {
            return;
        }

        $answerTable = $schema->getTable('c_quiz_answer');
        foreach (['id', 'c_id', 'question_id', 'correct'] as $requiredColumn) {
            if (!$answerTable->hasColumn($requiredColumn)) {
                return;
            }
        }

        $unresolvedMatchingReferences = (int) $this->connection->fetchOne(
            'SELECT COUNT(*)
             FROM c_quiz_answer source_answer
             INNER JOIN c_quiz_question question
                ON question.c_id = source_answer.c_id
               AND question.id = source_answer.question_id
             LEFT JOIN c_quiz_answer target_answer
                ON target_answer.c_id = source_answer.c_id
               AND target_answer.question_id = source_answer.question_id
               AND target_answer.id = source_answer.correct
             WHERE question.type = 4
               AND source_answer.correct > 0
               AND target_answer.iid IS NULL'
        );
        $this->abortIf(
            $unresolvedMatchingReferences > 0,
            \sprintf(
                'Cannot safely normalize %d matching-answer references in c_quiz_answer.correct.',
                $unresolvedMatchingReferences
            )
        );

        $updatedRows = $this->connection->executeStatement(
            'UPDATE c_quiz_answer source_answer
             INNER JOIN c_quiz_question question
                ON question.c_id = source_answer.c_id
               AND question.id = source_answer.question_id
               AND question.type = 4
             INNER JOIN c_quiz_answer target_answer
                ON target_answer.c_id = source_answer.c_id
               AND target_answer.question_id = source_answer.question_id
               AND target_answer.id = source_answer.correct
             SET source_answer.correct = target_answer.iid
             WHERE source_answer.correct > 0
               AND source_answer.correct <> target_answer.iid'
        );

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

                $this->getLogger()->info('Normalized legacy quiz item-property references.', [
                    'quiz_rows' => $quizProperties,
                    'question_rows' => $questionProperties,
                ]);
            }
        }

        if ($schema->hasTable('track_e_exercises')) {
            $trackingTable = $schema->getTable('track_e_exercises');
            if ($trackingTable->hasColumn('c_id') && $trackingTable->hasColumn('exe_exo_id')) {
                $updatedRows = $this->connection->executeStatement(
                    'UPDATE track_e_exercises tracking
                     INNER JOIN c_quiz quiz
                        ON quiz.c_id = tracking.c_id
                       AND quiz.id = tracking.exe_exo_id
                     SET tracking.exe_exo_id = quiz.iid
                     WHERE tracking.exe_exo_id <> quiz.iid'
                );

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
                $updatedRows = $this->connection->executeStatement(
                    "UPDATE c_lp_item item
                     INNER JOIN c_quiz quiz
                        ON quiz.c_id = item.c_id
                       AND item.path = CAST(quiz.id AS CHAR)
                     SET item.path = CAST(quiz.iid AS CHAR)
                     WHERE item.item_type = 'quiz'
                       AND item.path <> CAST(quiz.iid AS CHAR)"
                );

                $this->getLogger()->info('Normalized legacy learning-path quiz references.', [
                    'updated_rows' => $updatedRows,
                ]);
            }
        }

        if ($schema->hasTable('gradebook_link')) {
            $gradebookLink = $schema->getTable('gradebook_link');
            if ($gradebookLink->hasColumn('ref_id') && $gradebookLink->hasColumn('type')) {
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
                } else {
                    $updatedRows = 0;
                }

                $this->getLogger()->info('Normalized legacy gradebook exercise references.', [
                    'updated_rows' => $updatedRows,
                ]);
            }
        }

        if ($schema->hasTable('c_quiz_question_option')) {
            $table = $schema->getTable('c_quiz_question_option');
            if ($table->hasColumn('c_id') && $table->hasColumn('question_id')) {
                $this->connection->executeStatement(
                    'UPDATE c_quiz_question_option question_option
                     INNER JOIN c_quiz_question question
                        ON question.c_id = question_option.c_id
                       AND question.id = question_option.question_id
                     SET question_option.question_id = question.iid
                     WHERE question_option.question_id <> question.iid'
                );
            }
        }

        if ($schema->hasTable('c_quiz_question_rel_category')) {
            $table = $schema->getTable('c_quiz_question_rel_category');
            if ($table->hasColumn('c_id') && $table->hasColumn('question_id')) {
                $this->connection->executeStatement(
                    'UPDATE c_quiz_question_rel_category relation
                     INNER JOIN c_quiz_question question
                        ON question.c_id = relation.c_id
                       AND question.id = relation.question_id
                     SET relation.question_id = question.iid
                     WHERE relation.question_id <> question.iid'
                );
            }
        }

        if ($schema->hasTable('c_quiz_rel_category')) {
            $table = $schema->getTable('c_quiz_rel_category');
            if ($table->hasColumn('c_id') && $table->hasColumn('exercise_id')) {
                $this->connection->executeStatement(
                    'UPDATE c_quiz_rel_category relation
                     INNER JOIN c_quiz quiz
                        ON quiz.c_id = relation.c_id
                       AND quiz.id = relation.exercise_id
                     SET relation.exercise_id = quiz.iid
                     WHERE relation.exercise_id IS NOT NULL
                       AND relation.exercise_id <> quiz.iid'
                );
            }
        }
    }

    private function normalizeQuizQuestionRelations(Schema $schema): void
    {
        if (!$schema->hasTable('c_quiz_rel_question')) {
            return;
        }

        $relationTable = $schema->getTable('c_quiz_rel_question');
        if (!$relationTable->hasColumn('c_id')
            || !$relationTable->hasColumn('question_id')
            || !$relationTable->hasColumn('exercice_id')
        ) {
            return;
        }

        $orphanRows = $this->connection->fetchAllAssociative(
            'SELECT relation.iid,
                    quiz.iid AS quiz_iid,
                    question.iid AS question_iid
             FROM c_quiz_rel_question relation
             LEFT JOIN c_quiz quiz
                ON quiz.c_id = relation.c_id
               AND quiz.id = relation.exercice_id
             LEFT JOIN c_quiz_question question
                ON question.c_id = relation.c_id
               AND question.id = relation.question_id
             WHERE relation.exercice_id <> -1
               AND (quiz.iid IS NULL OR question.iid IS NULL)'
        );
        $orphanRowCount = \count($orphanRows);

        if ($orphanRowCount > 0) {
            $orphanQuizRelationIds = [];
            $orphanQuestionRelationIds = [];

            foreach ($orphanRows as $orphanRow) {
                $relationId = (int) $orphanRow['iid'];
                if (null === $orphanRow['quiz_iid']) {
                    $orphanQuizRelationIds[] = $relationId;
                }
                if (null === $orphanRow['question_iid']) {
                    $orphanQuestionRelationIds[] = $relationId;
                }
            }

            $this->getLogger()->warning('Preserving orphan legacy quiz-question relations with null references before adding foreign keys.', [
                'orphan_rows' => $orphanRowCount,
                'missing_quiz_rows' => \count($orphanQuizRelationIds),
                'missing_question_rows' => \count($orphanQuestionRelationIds),
            ]);

            $this->addSql(
                'ALTER TABLE c_quiz_rel_question
                 CHANGE exercice_id exercice_id INT DEFAULT NULL,
                 CHANGE question_id question_id INT DEFAULT NULL'
            );

            if ([] !== $orphanQuizRelationIds) {
                $this->addSql(
                    'UPDATE c_quiz_rel_question
                     SET exercice_id = NULL
                     WHERE iid IN ('.implode(', ', $orphanQuizRelationIds).')'
                );
            }

            if ([] !== $orphanQuestionRelationIds) {
                $this->addSql(
                    'UPDATE c_quiz_rel_question
                     SET question_id = NULL
                     WHERE iid IN ('.implode(', ', $orphanQuestionRelationIds).')'
                );
            }
        }

        $updatedRows = $this->connection->executeStatement(
            'UPDATE c_quiz_rel_question relation
             LEFT JOIN c_quiz quiz
                ON quiz.c_id = relation.c_id
               AND quiz.id = relation.exercice_id
             LEFT JOIN c_quiz_question question
                ON question.c_id = relation.c_id
               AND question.id = relation.question_id
             SET relation.exercice_id = COALESCE(quiz.iid, relation.exercice_id),
                 relation.question_id = COALESCE(question.iid, relation.question_id)
             WHERE relation.exercice_id <> -1
               AND (
                   (quiz.iid IS NOT NULL AND relation.exercice_id <> quiz.iid)
                   OR (question.iid IS NOT NULL AND relation.question_id <> question.iid)
               )'
        );

        $this->getLogger()->info('Normalized legacy quiz-question relations.', [
            'updated_rows' => $updatedRows,
            'orphan_rows_preserved_with_null_reference' => $orphanRowCount,
        ]);
    }

    private function normalizeQuizAnswers(Schema $schema): void
    {
        if (!$schema->hasTable('c_quiz_answer')) {
            return;
        }

        $answerTable = $schema->getTable('c_quiz_answer');
        if (!$answerTable->hasColumn('c_id') || !$answerTable->hasColumn('question_id')) {
            return;
        }

        $orphanAnswerIds = array_map(
            'intval',
            $this->connection->fetchFirstColumn(
                'SELECT answer_row.iid
                 FROM c_quiz_answer answer_row
                 LEFT JOIN c_quiz_question question
                    ON question.c_id = answer_row.c_id
                   AND question.id = answer_row.question_id
                 WHERE question.iid IS NULL'
            )
        );
        $orphanRows = \count($orphanAnswerIds);

        if ($orphanRows > 0) {
            $this->getLogger()->warning('Preserving orphan legacy quiz answers with a null question reference before adding the foreign key.', [
                'orphan_rows' => $orphanRows,
            ]);

            $this->addSql(
                'ALTER TABLE c_quiz_answer CHANGE question_id question_id INT DEFAULT NULL'
            );
            $this->addSql(
                'UPDATE c_quiz_answer
                 SET question_id = NULL
                 WHERE iid IN ('.implode(', ', $orphanAnswerIds).')'
            );
        }

        $updatedRows = $this->connection->executeStatement(
            'UPDATE c_quiz_answer answer_row
             INNER JOIN c_quiz_question question
                ON question.c_id = answer_row.c_id
               AND question.id = answer_row.question_id
             SET answer_row.question_id = question.iid
             WHERE answer_row.question_id <> question.iid'
        );

        $this->getLogger()->info('Normalized legacy quiz-answer question references.', [
            'updated_rows' => $updatedRows,
            'orphan_rows_preserved_with_null_reference' => $orphanRows,
        ]);
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

                if ($expectedColumns === array_slice($existingColumns, 0, \count($expectedColumns))) {
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
