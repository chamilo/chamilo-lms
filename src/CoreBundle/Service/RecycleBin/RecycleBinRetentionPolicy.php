<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\RecycleBin;

use Chamilo\CoreBundle\Service\Exercise\ExerciseAttemptRecycleSnapshotCodec;
use DateTimeImmutable;
use InvalidArgumentException;
use RuntimeException;

/**
 * Common retention rules for recoverable exercise and learning path archives.
 */
final readonly class RecycleBinRetentionPolicy
{
    public function __construct(
        private ExerciseAttemptRecycleSnapshotCodec $codec
    ) {}

    /**
     * Validate the platform setting or an explicit CLI override without coercing bad input.
     * A missing setting is equivalent to the documented 30-day default.
     */
    public static function configuredHours(mixed $value): int
    {
        if (null === $value || '' === $value) {
            return 720;
        }

        if ((!\is_string($value) && !\is_int($value))
            || !preg_match('/^[0-9]{1,5}$/D', (string) $value)
            || (int) $value < 1
            || (int) $value > 87600
        ) {
            throw new InvalidArgumentException('Recycle bin retention must be between 1 and 87600 hours.');
        }

        return (int) $value;
    }

    public static function cutoff(int $hours, ?DateTimeImmutable $now = null): string
    {
        if ($hours < 1 || $hours > 87600) {
            throw new InvalidArgumentException('Retention hours must be between 1 and 87600.');
        }

        return ($now ?? new DateTimeImmutable())->modify('-'.$hours.' hours')->format('Y-m-d H:i:s');
    }

    /**
     * @return list<int>
     */
    public function referencedExerciseArchiveIds(string $snapshot, string $checksum): array
    {
        if (!hash_equals($checksum, hash('sha256', $snapshot))) {
            throw new RuntimeException('A pending learning path archive has an invalid checksum; purge aborted.');
        }

        $tables = $this->codec->decode($snapshot);
        if (!isset($tables['__exercise_archives']) || !\is_array($tables['__exercise_archives'])) {
            throw new RuntimeException('A pending learning path archive is missing exercise references; purge aborted.');
        }

        $ids = [];
        foreach ($tables['__exercise_archives'] as $reference) {
            $value = $reference['archive_id'] ?? null;
            if (!\is_string($value) || !ctype_digit($value) || (int) $value <= 0) {
                throw new RuntimeException('A pending learning path archive has invalid exercise references; purge aborted.');
            }
            $ids[] = (int) $value;
        }

        return $ids;
    }
}
