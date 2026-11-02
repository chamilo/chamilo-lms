<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service\RecycleBin;

use Chamilo\CoreBundle\Service\Exercise\ExerciseAttemptRecycleSnapshotCodec;
use Chamilo\CoreBundle\Service\RecycleBin\RecycleBinRetentionPolicy;
use Chamilo\CoreBundle\Service\RecycleBin\RecycleBinRetentionService;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class RecycleBinPendingLearningPathReferenceTest extends TestCase
{
    public function testPendingLearningPathOwnsItsLinkedExerciseArchive(): void
    {
        $codec = new ExerciseAttemptRecycleSnapshotCodec();
        $payload = $codec->encode([
            'c_lp_view' => [['iid' => '83']],
            '__exercise_archives' => [['archive_id' => '41', 'attempt_id' => '93']],
        ]);
        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())
            ->method('fetchAllAssociative')
            ->with(
                self::callback(static fn (string $sql): bool => str_contains($sql, 'restored_at IS NULL')
                    && str_contains($sql, 'session_id IS NULL')
                    && str_contains($sql, 'course_id = :course')
                    && str_contains($sql, 'user_id = :user')),
                self::callback(static fn (array $params): bool => 72 === $params['course']
                    && 57 === $params['user']
                    && 0 === $params['after']),
            )
            ->willReturn([['id' => 77, 'snapshot' => $payload, 'snapshot_sha256' => hash('sha256', $payload)]])
        ;

        self::assertSame(77, $this->service($codec)->findPendingLearningPathReference($connection, [
            'id' => 41, 'course_id' => 72, 'user_id' => 57, 'session_id' => null,
        ]));
    }

    public function testStandaloneRestoreIsRejectedWhileLearningPathOwnsTheAttempt(): void
    {
        $codec = new ExerciseAttemptRecycleSnapshotCodec();
        $payload = $codec->encode(['__exercise_archives' => [['archive_id' => '41']]]);
        $connection = $this->createMock(Connection::class);
        $connection->method('fetchAllAssociative')->willReturn([
            ['id' => 77, 'snapshot' => $payload, 'snapshot_sha256' => hash('sha256', $payload)],
        ]);

        self::assertFalse($this->service($codec)->canRestoreExerciseArchive($connection, [
            'id' => 41, 'course_id' => 72, 'user_id' => 57, 'session_id' => null,
        ]));
    }

    public function testOwningLearningPathMayRestoreItsLinkedAttempt(): void
    {
        $codec = new ExerciseAttemptRecycleSnapshotCodec();
        $payload = $codec->encode(['__exercise_archives' => [['archive_id' => '41']]]);
        $connection = $this->createMock(Connection::class);
        $connection->method('fetchAllAssociative')->willReturn([
            ['id' => 77, 'snapshot' => $payload, 'snapshot_sha256' => hash('sha256', $payload)],
        ]);

        $archive = ['id' => 41, 'course_id' => 72, 'user_id' => 57, 'session_id' => null];
        self::assertTrue($this->service($codec)->canRestoreExerciseArchive($connection, $archive, 77));
        self::assertFalse($this->service($codec)->canRestoreExerciseArchive($connection, $archive, 78));
    }

    public function testUnrelatedPendingLearningPathDoesNotOwnExerciseArchive(): void
    {
        $codec = new ExerciseAttemptRecycleSnapshotCodec();
        $payload = $codec->encode(['__exercise_archives' => [['archive_id' => '99']]]);
        $connection = $this->createMock(Connection::class);
        $connection->expects(self::exactly(2))
            ->method('fetchAllAssociative')
            ->willReturnOnConsecutiveCalls(
                [['id' => 77, 'snapshot' => $payload, 'snapshot_sha256' => hash('sha256', $payload)]],
                [],
            )
        ;

        self::assertNull($this->service($codec)->findPendingLearningPathReference($connection, [
            'id' => 41, 'course_id' => 72, 'user_id' => 57, 'session_id' => null,
        ]));
    }

    public function testSessionScopeDoesNotScanAnotherSessionsLearningPaths(): void
    {
        $codec = new ExerciseAttemptRecycleSnapshotCodec();
        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())
            ->method('fetchAllAssociative')
            ->with(
                self::callback(static fn (string $sql): bool => str_contains($sql, 'session_id = :session')
                    && !str_contains($sql, 'session_id IS NULL')),
                self::callback(static fn (array $params): bool => 12 === $params['session']
                    && 72 === $params['course']
                    && 57 === $params['user']),
            )
            ->willReturn([])
        ;

        self::assertNull($this->service($codec)->findPendingLearningPathReference($connection, [
            'id' => 41, 'course_id' => 72, 'user_id' => 57, 'session_id' => 12,
        ]));
    }

    public function testCorruptPendingLearningPathPreventsUnsafeRestoreDecision(): void
    {
        $codec = new ExerciseAttemptRecycleSnapshotCodec();
        $payload = $codec->encode(['__exercise_archives' => [['archive_id' => '41']]]);
        $connection = $this->createMock(Connection::class);
        $connection->method('fetchAllAssociative')->willReturn([
            ['id' => 77, 'snapshot' => $payload, 'snapshot_sha256' => str_repeat('0', 64)],
        ]);

        $this->expectException(RuntimeException::class);
        $this->service($codec)->findPendingLearningPathReference($connection, [
            'id' => 41, 'course_id' => 72, 'user_id' => 57, 'session_id' => null,
        ]);
    }

    private function service(ExerciseAttemptRecycleSnapshotCodec $codec): RecycleBinRetentionService
    {
        return new RecycleBinRetentionService(
            $this->createMock(EntityManagerInterface::class),
            new RecycleBinRetentionPolicy($codec),
        );
    }
}
