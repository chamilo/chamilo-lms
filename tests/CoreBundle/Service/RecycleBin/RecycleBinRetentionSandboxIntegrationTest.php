<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service\RecycleBin;

use Chamilo\CoreBundle\Service\Exercise\ExerciseAttemptRecycleSnapshotCodec;
use Chamilo\CoreBundle\Service\RecycleBin\RecycleBinRetentionPolicy;
use Chamilo\CoreBundle\Service\RecycleBin\RecycleBinRetentionService;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Real MySQL retention smoke tests against a disposable, isolated database.
 * Never point this suite to a Chamilo installation database.
 */
final class RecycleBinRetentionSandboxIntegrationTest extends TestCase
{
    private Connection $connection;
    private RecycleBinRetentionService $service;
    private ExerciseAttemptRecycleSnapshotCodec $codec;

    protected function setUp(): void
    {
        $database = getenv('GH2872_SANDBOX_DB');
        $host = getenv('GH2872_SANDBOX_HOST');
        if (!\is_string($database) || !preg_match('/^gh2872_recycle_sandbox_[0-9]+_[0-9]+$/D', $database)
            || !\in_array($host, ['localhost', '127.0.0.1'], true)
        ) {
            throw new RuntimeException('A local, uniquely named GH2872 sandbox database is mandatory.');
        }
        $this->connection = DriverManager::getConnection([
            'driver' => 'pdo_mysql',
            'host' => $host,
            'port' => 3306,
            'user' => getenv('GH2872_SANDBOX_USER') ?: 'root',
            'password' => getenv('GH2872_SANDBOX_PASS') ?: '',
            'dbname' => $database,
            'charset' => 'utf8mb4',
        ]);
        if ($this->connection->createSchemaManager()->tablesExist([
            'exercise_attempt_recycle_bin', 'learning_path_tracking_recycle_bin',
        ])) {
            throw new RuntimeException('The sandbox must not contain pre-existing recycle tables.');
        }
        $this->connection->executeStatement(<<<'SQL'
            CREATE TABLE exercise_attempt_recycle_bin (
                id INT AUTO_INCREMENT PRIMARY KEY,
                attempt_id INT NOT NULL,
                exercise_id INT NOT NULL,
                user_id INT NOT NULL,
                course_id INT NOT NULL,
                session_id INT DEFAULT NULL,
                deleted_by INT NOT NULL,
                deleted_at DATETIME NOT NULL,
                snapshot_sha256 CHAR(64) NOT NULL,
                snapshot LONGTEXT NOT NULL,
                restored_by INT DEFAULT NULL,
                restored_at DATETIME DEFAULT NULL
            ) ENGINE=InnoDB
            SQL);
        $this->connection->executeStatement(<<<'SQL'
            CREATE TABLE learning_path_tracking_recycle_bin (
                id INT AUTO_INCREMENT PRIMARY KEY,
                lp_id INT NOT NULL,
                course_id INT NOT NULL,
                session_id INT DEFAULT NULL,
                user_id INT NOT NULL,
                deleted_by INT NOT NULL,
                deleted_at DATETIME NOT NULL,
                snapshot_sha256 CHAR(64) NOT NULL,
                snapshot LONGTEXT NOT NULL,
                restored_by INT DEFAULT NULL,
                restored_at DATETIME DEFAULT NULL
            ) ENGINE=InnoDB
            SQL);
        $this->codec = new ExerciseAttemptRecycleSnapshotCodec();
        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->method('getConnection')->willReturn($this->connection);
        $this->service = new RecycleBinRetentionService($manager, new RecycleBinRetentionPolicy($this->codec));
    }

    protected function tearDown(): void
    {
        if (isset($this->connection)) {
            $this->connection->executeStatement('DROP TABLE IF EXISTS learning_path_tracking_recycle_bin');
            $this->connection->executeStatement('DROP TABLE IF EXISTS exercise_attempt_recycle_bin');
            $this->connection->close();
        }
    }

    public function testExpiredStandaloneArchiveIsPurgedButLinkedArchiveRemainsProtected(): void
    {
        $linked = $this->exerciseArchive(93, '2000-01-01 00:00:00');
        $standalone = $this->exerciseArchive(94, '2000-01-01 00:00:00');
        $this->lpArchive([$linked], date('Y-m-d H:i:s'));

        $preview = $this->service->run(1);
        self::assertSame(2, $preview['exercise_attempt_recycle_bin']['scanned']);
        self::assertSame(1, $preview['exercise_attempt_recycle_bin']['protected']);
        self::assertSame(0, $preview['exercise_attempt_recycle_bin']['purged']);
        self::assertSame(2, (int) $this->connection->fetchOne('SELECT COUNT(*) FROM exercise_attempt_recycle_bin'));

        $result = $this->service->run(1, 250, true);
        self::assertSame(1, $result['exercise_attempt_recycle_bin']['purged']);
        self::assertSame(1, $result['exercise_attempt_recycle_bin']['protected']);
        self::assertSame(1, (int) $this->connection->fetchOne('SELECT COUNT(*) FROM exercise_attempt_recycle_bin WHERE id = ?', [$linked]));
        self::assertSame(0, (int) $this->connection->fetchOne('SELECT COUNT(*) FROM exercise_attempt_recycle_bin WHERE id = ?', [$standalone]));
    }

    public function testExpiredLearningPathAndLinkedExerciseArePurgedTogether(): void
    {
        $linked = $this->exerciseArchive(93, '2000-01-01 00:00:00');
        $recent = $this->exerciseArchive(95, date('Y-m-d H:i:s'));
        $lp = $this->lpArchive([$linked], '2000-01-01 00:00:00');

        $summary = $this->service->run(1, 250, true);
        self::assertSame(1, $summary['learning_path_tracking_recycle_bin']['purged']);
        self::assertSame(1, $summary['exercise_attempt_recycle_bin']['purged']);
        self::assertSame(0, (int) $this->connection->fetchOne('SELECT COUNT(*) FROM learning_path_tracking_recycle_bin WHERE id = ?', [$lp]));
        self::assertSame(0, (int) $this->connection->fetchOne('SELECT COUNT(*) FROM exercise_attempt_recycle_bin WHERE id = ?', [$linked]));
        self::assertSame(1, (int) $this->connection->fetchOne('SELECT COUNT(*) FROM exercise_attempt_recycle_bin WHERE id = ?', [$recent]));
    }

    public function testInvalidPendingLearningPathChecksumBlocksLinkedPurge(): void
    {
        $linked = $this->exerciseArchive(93, '2000-01-01 00:00:00');
        $lp = $this->lpArchive([$linked], date('Y-m-d H:i:s'));
        $this->connection->update('learning_path_tracking_recycle_bin', ['snapshot_sha256' => str_repeat('0', 64)], ['id' => $lp]);

        try {
            $this->service->run(1, 250, true);
            self::fail('A damaged pending archive must prevent the purge.');
        } catch (RuntimeException $exception) {
            self::assertStringContainsString('invalid checksum', $exception->getMessage());
        }
        self::assertSame(1, (int) $this->connection->fetchOne('SELECT COUNT(*) FROM exercise_attempt_recycle_bin WHERE id = ?', [$linked]));
    }

    public function testResumeOffsetLimitsWorkToUnprocessedArchives(): void
    {
        $first = $this->exerciseArchive(93, '2000-01-01 00:00:00');
        $second = $this->exerciseArchive(94, '2000-01-01 00:00:00');

        $firstResult = $this->service->run(1, 1, true);
        self::assertSame($first, $firstResult['exercise_attempt_recycle_bin']['last_id']);
        self::assertSame(1, $firstResult['exercise_attempt_recycle_bin']['purged']);
        self::assertSame(1, (int) $this->connection->fetchOne('SELECT COUNT(*) FROM exercise_attempt_recycle_bin WHERE id = ?', [$second]));

        $secondResult = $this->service->run(1, 1, true, 0, $first);
        self::assertSame($second, $secondResult['exercise_attempt_recycle_bin']['last_id']);
        self::assertSame(1, $secondResult['exercise_attempt_recycle_bin']['purged']);
        self::assertSame(0, (int) $this->connection->fetchOne('SELECT COUNT(*) FROM exercise_attempt_recycle_bin'));
    }

    private function exerciseArchive(int $attemptId, string $deletedAt): int
    {
        $snapshot = $this->codec->encode(['track_e_exercises' => [['exe_id' => (string) $attemptId]]]);
        $this->connection->insert('exercise_attempt_recycle_bin', [
            'attempt_id' => $attemptId,
            'exercise_id' => 194,
            'user_id' => 57,
            'course_id' => 72,
            'session_id' => null,
            'deleted_by' => 1,
            'deleted_at' => $deletedAt,
            'snapshot_sha256' => hash('sha256', $snapshot),
            'snapshot' => $snapshot,
        ]);

        return (int) $this->connection->lastInsertId();
    }

    /**
     * @param list<int> $exerciseArchiveIds
     */
    private function lpArchive(array $exerciseArchiveIds, string $deletedAt): int
    {
        $links = array_map(static fn (int $id): array => ['archive_id' => (string) $id], $exerciseArchiveIds);
        $snapshot = $this->codec->encode(['__exercise_archives' => $links]);
        $this->connection->insert('learning_path_tracking_recycle_bin', [
            'lp_id' => 64,
            'course_id' => 72,
            'session_id' => null,
            'user_id' => 57,
            'deleted_by' => 1,
            'deleted_at' => $deletedAt,
            'snapshot_sha256' => hash('sha256', $snapshot),
            'snapshot' => $snapshot,
        ]);

        return (int) $this->connection->lastInsertId();
    }
}
