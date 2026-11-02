<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service\LearningPath;

use Chamilo\CoreBundle\Service\Exercise\ExerciseAttemptRecycleSnapshotCodec;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class LearningPathRecycleSnapshotTest extends TestCase
{
    public function testPreservesScormTrackingAndLinkedArchiveIdentifiers(): void
    {
        $tables = [
            'c_lp_view' => [['iid' => 9, 'user_id' => 7, 'session_id' => null]],
            'c_lp_item_view' => [['iid' => 10, 'lp_view_id' => 9, 'suspend_data' => "\x00\xE9\xFF"]],
            'c_lp_iv_interaction' => [['iid' => 11, 'lp_iv_id' => 10, 'student_response' => 'response']],
            'c_lp_iv_objective' => [],
            'c_lp_iv_comment' => [['iid' => 12, 'lp_iv_id' => 10, 'comment' => 'Revisión']],
            'track_e_access_complete' => [],
            '__exercise_links' => [],
            '__exercise_archives' => [['archive_id' => 33, 'attempt_id' => 52, 'exercise_id' => 2]],
        ];
        $codec = new ExerciseAttemptRecycleSnapshotCodec();
        $decoded = $codec->decode($codec->encode($tables));

        self::assertSame("\x00\xE9\xFF", $decoded['c_lp_item_view'][0]['suspend_data']);
        self::assertSame('Revisión', $decoded['c_lp_iv_comment'][0]['comment']);
        self::assertSame('33', $decoded['__exercise_archives'][0]['archive_id']);
        self::assertNull($decoded['c_lp_view'][0]['session_id']);
    }

    public function testRejectsCorruptedScormSnapshot(): void
    {
        $this->expectException(RuntimeException::class);
        (new ExerciseAttemptRecycleSnapshotCodec())->decode(
            '{"version":1,"tables":{"c_lp_item_view":[{"suspend_data":"not base64"}]}}',
        );
    }
}
