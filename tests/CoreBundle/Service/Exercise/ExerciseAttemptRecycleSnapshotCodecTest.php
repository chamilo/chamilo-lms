<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service\Exercise;

use Chamilo\CoreBundle\Service\Exercise\ExerciseAttemptRecycleSnapshotCodec;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ExerciseAttemptRecycleSnapshotCodecTest extends TestCase
{
    public function testPreservesBinaryValuesAndNullsForLosslessRestoration(): void
    {
        $codec = new ExerciseAttemptRecycleSnapshotCodec();
        $tables = [
            'track_e_exercises' => [['exe_id' => 42, 'data_tracking' => "\x00\xFF\x01", 'session_id' => null]],
            'track_e_attempt' => [['id' => 99, 'exe_id' => 42, 'answer' => 'correct']],
        ];

        self::assertSame([
            'track_e_exercises' => [['exe_id' => '42', 'data_tracking' => "\x00\xFF\x01", 'session_id' => null]],
            'track_e_attempt' => [['id' => '99', 'exe_id' => '42', 'answer' => 'correct']],
        ], $codec->decode($codec->encode($tables)));
    }

    public function testRejectsCorruptedBase64InsteadOfRestoringAlteredAnswers(): void
    {
        $codec = new ExerciseAttemptRecycleSnapshotCodec();
        $this->expectException(RuntimeException::class);
        $codec->decode('{"version":1,"tables":{"track_e_attempt":[{"answer":"%%%%"}]}}');
    }

    public function testRejectsUnknownArchiveVersion(): void
    {
        $codec = new ExerciseAttemptRecycleSnapshotCodec();
        $this->expectException(RuntimeException::class);
        $codec->decode('{"version":2,"tables":{}}');
    }
}
