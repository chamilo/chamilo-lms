<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\RecycleBin;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use RuntimeException;

/**
 * Removes expired archive copies only. Never deletes active learner tracking.
 * Pending learning path references keep linked exercise archives protected.
 */
final readonly class RecycleBinRetentionService
{
    private const array TABLES = [
        'learning_path_tracking_recycle_bin',
        'exercise_attempt_recycle_bin',
    ];

    public function __construct(
        private EntityManagerInterface $entityManager,
        private RecycleBinRetentionPolicy $policy,
    ) {}

    /**
     * @return array<string, array{scanned: int, eligible: int, protected: int, purged: int, last_id: int}>
     */
    public function run(
        int $hours,
        int $scanLimit = 250,
        bool $execute = false,
        int $afterLpId = 0,
        int $afterExerciseId = 0,
    ): array {
        if ($scanLimit < 1 || $scanLimit > 1000 || $afterLpId < 0 || $afterExerciseId < 0) {
            throw new InvalidArgumentException('Invalid scan limit or resume identifier.');
        }

        $cutoff = RecycleBinRetentionPolicy::cutoff($hours);
        $connection = $this->entityManager->getConnection();
        if (!$connection->createSchemaManager()->tablesExist(self::TABLES)) {
            throw new RuntimeException('Both recycle bin migrations must be applied before running retention.');
        }

        $stats = [];
        foreach (self::TABLES as $table) {
            $afterId = 'learning_path_tracking_recycle_bin' === $table ? $afterLpId : $afterExerciseId;
            $rows = $connection->fetchAllAssociative(
                'SELECT id, course_id, user_id, session_id FROM '.$table
                .' WHERE id > :after AND COALESCE(restored_at, deleted_at) <= :cutoff'
                .' ORDER BY id ASC LIMIT :limit',
                ['after' => $afterId, 'cutoff' => $cutoff, 'limit' => $scanLimit],
                ['after' => ParameterType::INTEGER, 'cutoff' => ParameterType::STRING, 'limit' => ParameterType::INTEGER],
            );

            $stats[$table] = [
                'scanned' => 0,
                'eligible' => 0,
                'protected' => 0,
                'purged' => 0,
                'last_id' => $afterId,
            ];

            foreach ($rows as $row) {
                $id = (int) $row['id'];
                ++$stats[$table]['scanned'];
                $stats[$table]['last_id'] = $id;

                if ('exercise_attempt_recycle_bin' === $table
                    && null !== $this->findPendingLearningPathReference($connection, $row)
                ) {
                    ++$stats[$table]['protected'];

                    continue;
                }

                ++$stats[$table]['eligible'];
                if (!$execute) {
                    continue;
                }

                $purged = $connection->transactional(function (Connection $connection) use ($table, $id, $cutoff): bool {
                    $record = $connection->fetchAssociative(
                        'SELECT id, course_id, user_id, session_id FROM '.$table
                        .' WHERE id = :id AND COALESCE(restored_at, deleted_at) <= :cutoff FOR UPDATE',
                        ['id' => $id, 'cutoff' => $cutoff],
                    );
                    if (false === $record) {
                        return false;
                    }

                    if ('exercise_attempt_recycle_bin' === $table
                        && null !== $this->findPendingLearningPathReference($connection, $record)
                    ) {
                        return false;
                    }

                    return 1 === $connection->executeStatement(
                        'DELETE FROM '.$table.' WHERE id = :id AND COALESCE(restored_at, deleted_at) <= :cutoff',
                        ['id' => $id, 'cutoff' => $cutoff],
                    );
                });

                if ($purged) {
                    ++$stats[$table]['purged'];
                } else {
                    ++$stats[$table]['protected'];
                    --$stats[$table]['eligible'];
                }
            }
        }

        return $stats;
    }

    /**
     * An individual exercise restore must not consume an attempt that still belongs to
     * a pending LP restore. The matching LP restore may consume it atomically.
     *
     * @param array<string, mixed> $exerciseArchive
     */
    public function canRestoreExerciseArchive(Connection $connection, array $exerciseArchive, ?int $restoringLpArchiveId = null): bool
    {
        $owner = $this->findPendingLearningPathReference($connection, $exerciseArchive);

        return null === $owner || $owner === $restoringLpArchiveId;
    }

    /**
     * @param array<string, mixed> $exerciseArchive
     */
    public function findPendingLearningPathReference(Connection $connection, array $exerciseArchive): ?int
    {
        $params = [
            'course' => (int) $exerciseArchive['course_id'],
            'user' => (int) $exerciseArchive['user_id'],
        ];
        $sessionClause = 'session_id IS NULL';
        if (null !== $exerciseArchive['session_id']) {
            $sessionClause = 'session_id = :session';
            $params['session'] = (int) $exerciseArchive['session_id'];
        }

        $afterId = 0;
        while (true) {
            $rows = $connection->fetchAllAssociative(
                'SELECT id, snapshot, snapshot_sha256 FROM learning_path_tracking_recycle_bin'
                .' WHERE id > :after AND course_id = :course AND user_id = :user'
                .' AND '.$sessionClause.' AND restored_at IS NULL ORDER BY id ASC LIMIT 100',
                [...$params, 'after' => $afterId],
            );
            if ([] === $rows) {
                return null;
            }

            foreach ($rows as $row) {
                $afterId = (int) $row['id'];
                if (\in_array(
                    (int) $exerciseArchive['id'],
                    $this->policy->referencedExerciseArchiveIds((string) $row['snapshot'], (string) $row['snapshot_sha256']),
                    true,
                )) {
                    return (int) $row['id'];
                }
            }
        }
    }
}
