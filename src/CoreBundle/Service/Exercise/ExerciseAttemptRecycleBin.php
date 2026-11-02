<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Exercise;

use Chamilo\CoreBundle\Entity\TrackEExercise;
use Chamilo\CoreBundle\Service\RecycleBin\RecycleBinRetentionService;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * A transactional, lossless archive for deleted exercise attempts and their DB rows.
 * The original attempt disappears from existing reports exactly as it did before.
 */
final readonly class ExerciseAttemptRecycleBin
{
    /**
     * Each table below is explicitly reviewed. Unexpected FK dependents abort the delete.
     * The root must be inserted first, followed by answers and answer attachments.
     */
    private const array TABLES = [
        'track_e_exercises' => ['exe_id', 'root'],
        'track_e_attempt' => ['exe_id', 'root'],
        'track_e_attempt_qualify' => ['exe_id', 'root'],
        'track_e_attempt_log' => ['exe_id', 'root'],
        'c_quiz_question_attempt_feedback' => ['attempt_id', 'root'],
        'c_quiz_destination_result' => ['exe_id', 'root'],
        'track_e_exercise_confirmation' => ['attempt_id', 'root'],
        'track_e_attempt_recording' => ['exe_id', 'root'],
        'track_e_attempt_coeff' => ['attempt_id', 'answers'],
        'attempt_file' => ['attempt_id', 'answers'],
        'attempt_feedback' => ['attempt_id', 'answers'],
    ];

    public function __construct(
        private EntityManagerInterface $entityManager,
        private ExerciseAttemptRecycleSnapshotCodec $codec,
        private RecycleBinRetentionService $retentionService,
    ) {}

    /**
     * @param list<TrackEExercise> $attempts
     */
    public function archiveAndDelete(array $attempts, int $actorId): int
    {
        if ($actorId <= 0) {
            throw new RuntimeException('An authenticated teacher is required.');
        }
        if ([] === $attempts) {
            return 0;
        }

        $connection = $this->entityManager->getConnection();
        $this->requireArchiveTable($connection);
        $columns = $this->getTableColumns($connection);
        $this->assertSupportedCascadeDependencies($connection);

        return $connection->transactional(function (Connection $connection) use ($attempts, $actorId, $columns): int {
            $count = 0;
            foreach ($attempts as $attempt) {
                $attemptId = (int) $attempt->getExeId();
                $root = $connection->fetchAssociative(
                    'SELECT exe_id, exe_user_id, c_id, session_id, exe_exo_id FROM track_e_exercises WHERE exe_id = :id FOR UPDATE',
                    ['id' => $attemptId],
                    ['id' => Types::INTEGER],
                );
                if (false === $root || null === $root['exe_exo_id']) {
                    throw new RuntimeException('The requested exercise attempt is no longer available.');
                }
                $snapshot = $this->snapshot($connection, $attemptId, $columns);
                $payload = $this->codec->encode($snapshot);
                $connection->insert('exercise_attempt_recycle_bin', [
                    'attempt_id' => $attemptId,
                    'exercise_id' => (int) $root['exe_exo_id'],
                    'user_id' => (int) $root['exe_user_id'],
                    'course_id' => (int) $root['c_id'],
                    'session_id' => null === $root['session_id'] ? null : (int) $root['session_id'],
                    'deleted_by' => $actorId,
                    'deleted_at' => date('Y-m-d H:i:s'),
                    'snapshot_sha256' => hash('sha256', $payload),
                    'snapshot' => $payload,
                ]);
                // These legacy relations have no reliable cascading foreign key.
                foreach (['track_e_exercise_confirmation' => 'attempt_id', 'track_e_attempt_recording' => 'exe_id'] as $table => $column) {
                    if (isset($columns[$table]) && \in_array($column, $columns[$table], true)) {
                        $connection->executeStatement('DELETE FROM '.$table.' WHERE '.$column.' = :id', ['id' => $attemptId], ['id' => Types::INTEGER]);
                    }
                }
                if (isset($columns['track_e_attempt_coeff']) && \in_array('attempt_id', $columns['track_e_attempt_coeff'], true)) {
                    $answerIds = array_map(static fn (array $record): int => (int) $record['id'], $snapshot['track_e_attempt'] ?? []);
                    if ([] !== $answerIds) {
                        $connection->executeStatement('DELETE FROM track_e_attempt_coeff WHERE attempt_id IN (:ids)', ['ids' => $answerIds], ['ids' => ArrayParameterType::INTEGER]);
                    }
                }
                $deleted = $connection->executeStatement('DELETE FROM track_e_exercises WHERE exe_id = :id', ['id' => $attemptId], ['id' => Types::INTEGER]);
                if (1 !== $deleted) {
                    throw new RuntimeException('The exercise attempt could not be removed after archiving.');
                }
                ++$count;
            }

            return $count;
        });
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listForExercise(int $courseId, ?int $sessionId, int $exerciseId): array
    {
        $connection = $this->entityManager->getConnection();
        $this->requireArchiveTable($connection);

        return $connection->fetchAllAssociative(
            'SELECT id, attempt_id, user_id, deleted_by, deleted_at '
            .'FROM exercise_attempt_recycle_bin '
            .'WHERE course_id = :course AND exercise_id = :exercise '
            .'AND '.(null === $sessionId ? 'session_id IS NULL ' : 'session_id = :session ')
            .'AND restored_at IS NULL ORDER BY deleted_at DESC, id DESC LIMIT 200',
            null === $sessionId ? ['course' => $courseId, 'exercise' => $exerciseId] : ['course' => $courseId, 'exercise' => $exerciseId, 'session' => $sessionId],
        );
    }

    public function restore(int $archiveId, int $courseId, ?int $sessionId, int $exerciseId, int $actorId, ?int $learningPathArchiveId = null): void
    {
        if ($archiveId <= 0 || $actorId <= 0) {
            throw new NotFoundHttpException('Recycle archive not found.');
        }

        $connection = $this->entityManager->getConnection();
        $this->requireArchiveTable($connection);
        $columns = $this->getTableColumns($connection);
        $this->assertSupportedCascadeDependencies($connection);

        $connection->transactional(function (Connection $connection) use ($archiveId, $courseId, $sessionId, $exerciseId, $actorId, $columns, $learningPathArchiveId): void {
            $row = $connection->fetchAssociative(
                'SELECT * FROM exercise_attempt_recycle_bin WHERE id = :id AND course_id = :course AND exercise_id = :exercise FOR UPDATE',
                ['id' => $archiveId, 'course' => $courseId, 'exercise' => $exerciseId],
            );
            if (false === $row || (null === $sessionId ? null !== $row['session_id'] : $sessionId !== (int) $row['session_id'])) {
                throw new NotFoundHttpException('Recycle archive not found in this course and session.');
            }
            if (null !== $row['restored_at']) {
                throw new ConflictHttpException('The exercise attempt has already been restored.');
            }

            // A pending LP snapshot owns its linked exercise archives until the whole LP is restored.
            // Restoring an exercise separately would make that LP restoration fail midway.
            if ($connection->createSchemaManager()->tablesExist(['learning_path_tracking_recycle_bin'])
                && !$this->retentionService->canRestoreExerciseArchive($connection, $row, $learningPathArchiveId)
            ) {
                throw new ConflictHttpException('Restore this exercise attempt from the learning path recycle bin first.');
            }
            if (!hash_equals((string) $row['snapshot_sha256'], hash('sha256', (string) $row['snapshot']))) {
                throw new ConflictHttpException('The recycle archive failed its integrity check.');
            }

            $tables = $this->codec->decode((string) $row['snapshot']);
            if (1 !== \count($tables['track_e_exercises'] ?? []) || (int) ($tables['track_e_exercises'][0]['exe_id'] ?? 0) !== (int) $row['attempt_id']) {
                throw new ConflictHttpException('The recycle archive does not match its attempt.');
            }
            $root = $tables['track_e_exercises'][0];
            if ((int) ($root['c_id'] ?? 0) !== $courseId
                || (int) ($root['exe_exo_id'] ?? 0) !== $exerciseId
                || (int) ($root['exe_user_id'] ?? 0) !== (int) $row['user_id']
                || (null === $sessionId ? null !== $root['session_id'] : (int) ($root['session_id'] ?? 0) !== $sessionId)
            ) {
                throw new ConflictHttpException('The recycle archive context does not match the original attempt.');
            }
            foreach ($tables as $table => $records) {
                if (!isset(self::TABLES[$table]) || !isset($columns[$table])) {
                    throw new ConflictHttpException('The recycle archive references an unknown table.');
                }
                foreach ($records as $record) {
                    if ([] !== array_diff(array_keys($record), $columns[$table])) {
                        throw new ConflictHttpException('The recycle archive schema no longer matches the database.');
                    }
                    $connection->insert($table, $record);
                }
            }

            $connection->update('exercise_attempt_recycle_bin', [
                'restored_at' => date('Y-m-d H:i:s'),
                'restored_by' => $actorId,
            ], ['id' => $archiveId]);
        });
    }

    /**
     * @param array<string, list<string>> $columns
     *
     * @return array<string, list<array<string, mixed>>>
     */
    private function snapshot(Connection $connection, int $attemptId, array $columns): array
    {
        $data = [];
        $answerIds = [];
        foreach (self::TABLES as $table => [$column, $scope]) {
            if (!isset($columns[$table]) || !\in_array($column, $columns[$table], true)) {
                continue;
            }
            $ids = 'answers' === $scope ? $answerIds : [$attemptId];
            $records = [] === $ids ? [] : $connection->executeQuery(
                'SELECT * FROM '.$table.' WHERE '.$column.' IN (:ids)',
                ['ids' => $ids],
                ['ids' => ArrayParameterType::INTEGER],
            )->fetchAllAssociative();
            $data[$table] = $records;
            if ('track_e_attempt' === $table) {
                $answerIds = array_map(static fn (array $record): int => (int) $record['id'], $records);
            }
        }
        if (1 !== \count($data['track_e_exercises'] ?? [])) {
            throw new RuntimeException('The attempt could not be archived safely.');
        }

        return $data;
    }

    /**
     * @return array<string, list<string>>
     */
    private function getTableColumns(Connection $connection): array
    {
        $columns = [];
        foreach ($connection->fetchAllAssociative(
            'SELECT TABLE_NAME, COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN ('
            .implode(',', array_fill(0, \count(self::TABLES), '?')).')',
            array_keys(self::TABLES),
        ) as $record) {
            $columns[$record['TABLE_NAME']][] = $record['COLUMN_NAME'];
        }
        foreach (self::TABLES as $table => [$column]) {
            if (isset($columns[$table]) && !\in_array($column, $columns[$table], true)) {
                throw new RuntimeException('Unsupported recycle source column for table: '.$table);
            }
        }
        foreach (['track_e_exercises', 'track_e_attempt'] as $mandatory) {
            if (!isset($columns[$mandatory])) {
                throw new RuntimeException('Required exercise tracking table is missing: '.$mandatory);
            }
        }

        return $columns;
    }

    private function assertSupportedCascadeDependencies(Connection $connection): void
    {
        $supported = array_keys(self::TABLES);
        foreach ($connection->fetchAllAssociative(
            "SELECT TABLE_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND REFERENCED_TABLE_NAME IN ('track_e_exercises', 'track_e_attempt')",
        ) as $row) {
            if (!\in_array($row['TABLE_NAME'], $supported, true)) {
                throw new RuntimeException('An unhandled dependent table prevents safe recycling: '.$row['TABLE_NAME']);
            }
        }
    }

    private function requireArchiveTable(Connection $connection): void
    {
        if (!$connection->createSchemaManager()->tablesExist(['exercise_attempt_recycle_bin'])) {
            throw new RuntimeException('Apply the exercise recycle bin migration before deleting attempts.');
        }
    }
}
