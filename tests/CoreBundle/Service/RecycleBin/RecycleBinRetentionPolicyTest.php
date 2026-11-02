<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service\RecycleBin;

use Chamilo\CoreBundle\Service\Exercise\ExerciseAttemptRecycleSnapshotCodec;
use Chamilo\CoreBundle\Service\RecycleBin\RecycleBinRetentionPolicy;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class RecycleBinRetentionPolicyTest extends TestCase
{
    public function testRetentionUsesTheRestorationAwareCutoff(): void
    {
        self::assertSame('2026-10-31 10:00:00', RecycleBinRetentionPolicy::cutoff(
            24,
            new DateTimeImmutable('2026-11-01 10:00:00'),
        ));
    }

    public function testConfiguredRetentionUsesSafeDefaultWhenMissing(): void
    {
        self::assertSame(720, RecycleBinRetentionPolicy::configuredHours(null));
        self::assertSame(720, RecycleBinRetentionPolicy::configuredHours(''));
    }

    public function testConfiguredRetentionAcceptsValidAdminValues(): void
    {
        self::assertSame(48, RecycleBinRetentionPolicy::configuredHours('48'));
        self::assertSame(87600, RecycleBinRetentionPolicy::configuredHours(87600));
    }

    public function testConfiguredRetentionRejectsInvalidAdministratorValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        RecycleBinRetentionPolicy::configuredHours('1 OR 1=1');
    }

    public function testRejectsZeroRetention(): void
    {
        $this->expectException(InvalidArgumentException::class);
        RecycleBinRetentionPolicy::cutoff(0);
    }

    public function testLinkedExerciseArchiveIsIdentifiedWithoutExposingAnswers(): void
    {
        $codec = new ExerciseAttemptRecycleSnapshotCodec();
        $payload = $codec->encode([
            '__exercise_archives' => [['archive_id' => '41', 'attempt_id' => '30']],
            'c_lp_view' => [['iid' => '10']],
        ]);
        $policy = new RecycleBinRetentionPolicy($codec);
        self::assertSame([41], $policy->referencedExerciseArchiveIds($payload, hash('sha256', $payload)));
    }

    public function testCorruptLinkedArchiveBlocksPurge(): void
    {
        $codec = new ExerciseAttemptRecycleSnapshotCodec();
        $policy = new RecycleBinRetentionPolicy($codec);
        $this->expectException(RuntimeException::class);
        $policy->referencedExerciseArchiveIds($codec->encode(['__exercise_archives' => []]), str_repeat('0', 64));
    }

    public function testArchiveWithoutMetadataBlocksPurge(): void
    {
        $codec = new ExerciseAttemptRecycleSnapshotCodec();
        $policy = new RecycleBinRetentionPolicy($codec);
        $payload = $codec->encode(['c_lp_view' => []]);
        $this->expectException(RuntimeException::class);
        $policy->referencedExerciseArchiveIds($payload, hash('sha256', $payload));
    }
}
