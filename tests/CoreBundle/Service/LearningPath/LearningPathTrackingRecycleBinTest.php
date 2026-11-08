<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service\LearningPath;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Service\Exercise\ExerciseAttemptRecycleSnapshotCodec;
use Chamilo\CoreBundle\Service\LearningPath\LearningPathTrackingRecycleBin;
use Chamilo\Tests\ChamiloTestTrait;
use Chamilo\Tests\CoreBundle\Service\RawReportingFixturesTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * The snapshot taken before a learning path reporting reset (LearningPathReportingResetProcessor) and its restore
 * (LearningPathTrackingRecycleBinController). The scoping questions that matter: a snapshot only holds the rows of
 * ONE learner in ONE (course, session) context, the list only shows users the caller may manage, and a restore
 * never overwrites newer progress or restores into another context. Learners are raw users ("rp_"), the learning
 * path and item ids come from the installer demo content, every other row is inserted with prepared statements.
 * The exercise-attempt branch (deleteExerciseAttempts=true) needs the quiz repository and is NOT covered here.
 */
final class LearningPathTrackingRecycleBinTest extends KernelTestCase
{
    use ChamiloTestTrait;
    use RawReportingFixturesTrait;

    private LearningPathTrackingRecycleBin $bin;
    private int $lpId;
    private int $itemId;
    private Course $course;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->bin = self::getContainer()->get(LearningPathTrackingRecycleBin::class);
        $this->lpId = (int) $this->conn()->fetchOne('SELECT MIN(iid) FROM c_lp');
        $this->itemId = (int) $this->conn()->fetchOne('SELECT MIN(iid) FROM c_lp_item WHERE lp_id = ?', [$this->lpId]);
        if ($this->lpId <= 0 || $this->itemId <= 0) {
            $this->markTestSkipped('The installer demo learning path is missing.');
        }
        $this->course = $this->getEntityManager()->find(Course::class, 1);
    }

    private function sessionEntity(int $id): Session
    {
        return $this->getEntityManager()->find(Session::class, $id);
    }

    private function view(int $userId, ?int $sessionId = null, int $progress = 50): int
    {
        $this->conn()->executeStatement(
            'INSERT INTO c_lp_view (user_id, lp_id, c_id, session_id, view_count, last_item, progress) VALUES (?, ?, 1, ?, 1, 0, ?)',
            [$userId, $this->lpId, $sessionId, $progress]
        );

        return (int) $this->conn()->lastInsertId();
    }

    private function itemView(int $viewId, string $status = 'completed'): int
    {
        $this->conn()->executeStatement(
            "INSERT INTO c_lp_item_view (lp_item_id, lp_view_id, view_count, start_time, total_time, score, progress, status, core_exit)
             VALUES (?, ?, 1, 1000, 77, 8.5, 100, ?, 'none')",
            [$this->itemId, $viewId, $status]
        );

        return (int) $this->conn()->lastInsertId();
    }

    private function comment(int $itemViewId): int
    {
        $this->conn()->executeStatement(
            "INSERT INTO c_lp_iv_comment (c_id, lp_iv_id, order_id, source, comment, location, comment_timestamp) VALUES (1, ?, 1, 'sv2', 'sv2 comment', '', '0')",
            [$itemViewId]
        );

        return (int) $this->conn()->lastInsertId();
    }

    private function accessComplete(int $userId, int $sessionId = 0): void
    {
        $this->conn()->executeStatement(
            "INSERT INTO track_e_access_complete (user_id, date_reg, tool, tool_id, tool_id_detail, action, action_details, current_id, ip_user, user_agent, session_id, c_id, ch_sid, login_as, info, url)
             VALUES (?, '2001-01-01 10:00:00', 'learnpath', ?, 0, 'view', '', 0, '', '', ?, 1, '', 0, '', '')",
            [$userId, $this->lpId, $sessionId]
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function archives(int $userId): array
    {
        return $this->conn()->fetchAllAssociative('SELECT * FROM learning_path_tracking_recycle_bin WHERE user_id = ? ORDER BY id', [$userId]);
    }

    private function capture(int $userId, ?int $sessionId = null, bool $access = false): void
    {
        $this->fresh();
        $this->bin->captureBeforeReset($this->lpId, $this->course, null === $sessionId ? null : $this->sessionEntity($sessionId), [$userId], false, $access, 1);
    }

    private function wipeLive(int $userId): void
    {
        $this->conn()->executeStatement('DELETE FROM c_lp_iv_comment WHERE lp_iv_id IN (SELECT iid FROM c_lp_item_view WHERE lp_view_id IN (SELECT iid FROM c_lp_view WHERE user_id = ?))', [$userId]);
        $this->conn()->executeStatement('DELETE FROM c_lp_item_view WHERE lp_view_id IN (SELECT iid FROM c_lp_view WHERE user_id = ?)', [$userId]);
        $this->conn()->executeStatement('DELETE FROM c_lp_view WHERE user_id = ?', [$userId]);
    }

    private function restore(int $archiveId, ?int $sessionId = null): void
    {
        $this->fresh();
        $this->bin->restore($archiveId, $this->lpId, $this->course, null === $sessionId ? null : $this->sessionEntity($sessionId), 1);
    }

    public function testCaptureWithNothingToArchiveWritesNoRecord(): void
    {
        $user = $this->rawUser('lp');
        $this->capture($user);
        $this->assertSame([], $this->archives($user));
    }

    public function testCaptureStoresOneRecordWithActorAndIntegrityHash(): void
    {
        $user = $this->rawUser('lp');
        $viewId = $this->view($user);
        $itemViewId = $this->itemView($viewId);
        $this->comment($itemViewId);

        $this->capture($user);

        $rows = $this->archives($user);
        $this->assertCount(1, $rows);
        $row = $rows[0];
        $this->assertSame($this->lpId, (int) $row['lp_id']);
        $this->assertSame(1, (int) $row['course_id']);
        $this->assertNull($row['session_id']);
        $this->assertSame(1, (int) $row['deleted_by']);
        $this->assertNull($row['restored_at']);
        $this->assertSame(hash('sha256', $row['snapshot']), $row['snapshot_sha256']);

        $tables = (new ExerciseAttemptRecycleSnapshotCodec())->decode($row['snapshot']);
        $this->assertSame([(string) $viewId], array_column($tables['c_lp_view'], 'iid'));
        $this->assertSame([(string) $itemViewId], array_column($tables['c_lp_item_view'], 'iid'));
        $this->assertCount(1, $tables['c_lp_iv_comment']);
        $this->assertSame([], $tables['track_e_access_complete']);
        $this->assertSame([], $tables['__exercise_links']);
    }

    public function testCaptureOnlyTakesTheRowsOfTheRequestedLearnerAndContext(): void
    {
        $user = $this->rawUser('lp');
        $other = $this->rawUser('other');
        $session = $this->rawSession('lp');
        $base = $this->view($user);
        $this->itemView($base);
        $inSession = $this->view($user, $session);
        $this->itemView($inSession);
        $otherView = $this->view($other);
        $this->itemView($otherView);

        $this->capture($user);

        $tables = (new ExerciseAttemptRecycleSnapshotCodec())->decode($this->archives($user)[0]['snapshot']);
        $this->assertSame([(string) $base], array_column($tables['c_lp_view'], 'iid'), 'Session progress and other learners stay out');
        $this->assertCount(1, $tables['c_lp_item_view']);
        $this->assertSame([], $this->archives($other));

        $this->capture($user, $session);
        $sessionArchive = $this->archives($user)[1];
        $this->assertSame($session, (int) $sessionArchive['session_id']);
        $tables = (new ExerciseAttemptRecycleSnapshotCodec())->decode($sessionArchive['snapshot']);
        $this->assertSame([(string) $inSession], array_column($tables['c_lp_view'], 'iid'));
    }

    public function testCaptureIncludesAccessCompletionOnlyWhenAskedAndOnlyForTheContext(): void
    {
        $user = $this->rawUser('lp');
        $session = $this->rawSession('lp');
        $this->view($user);
        $this->accessComplete($user, 0);
        $this->accessComplete($user, $session);

        $this->capture($user, null, false);
        $without = (new ExerciseAttemptRecycleSnapshotCodec())->decode($this->archives($user)[0]['snapshot']);
        $this->assertSame([], $without['track_e_access_complete']);

        $this->capture($user, null, true);
        $with = (new ExerciseAttemptRecycleSnapshotCodec())->decode($this->archives($user)[1]['snapshot']);
        $this->assertCount(1, $with['track_e_access_complete']);
        $this->assertSame('0', $with['track_e_access_complete'][0]['session_id'], 'No session is stored as 0 there');
    }

    public function testListShowsOnlyAllowedUsersOfTheContextNewestFirstWithoutRestoredOnes(): void
    {
        $a = $this->rawUser('a');
        $b = $this->rawUser('b');
        $session = $this->rawSession('lp');
        foreach ([$a, $b] as $u) {
            $this->view($u);
            $this->capture($u);
        }
        $this->view($a, $session);
        $this->capture($a, $session);
        $this->view($a);
        $this->capture($a);

        $archivesA = $this->archives($a);
        $this->conn()->executeStatement("UPDATE learning_path_tracking_recycle_bin SET deleted_at = '2001-01-01 10:00:00' WHERE id = ?", [$archivesA[0]['id']]);
        $this->conn()->executeStatement("UPDATE learning_path_tracking_recycle_bin SET deleted_at = '2001-01-02 10:00:00' WHERE id = ?", [$archivesA[2]['id']]);
        $this->conn()->executeStatement("UPDATE learning_path_tracking_recycle_bin SET restored_at = '2001-01-03 10:00:00' WHERE id = ?", [$this->archives($b)[0]['id']]);

        $list = $this->bin->listForLearningPath($this->lpId, 1, null, [$a, $b]);
        $ids = array_map(static fn (array $r): int => (int) $r['id'], $list);
        $this->assertSame([(int) $archivesA[2]['id'], (int) $archivesA[0]['id']], array_values(array_intersect($ids, array_map('intval', array_column($archivesA, 'id')))), 'Newest deleted_at first');
        $this->assertNotContains((int) $archivesA[1]['id'], $ids, 'The session snapshot is not in the course-level list');
        $this->assertNotContains((int) $this->archives($b)[0]['id'], $ids, 'Restored snapshots are hidden');
        $this->assertSame(['id', 'user_id', 'deleted_by', 'deleted_at'], array_keys($list[0]), 'The snapshot payload is never listed');

        $onlyB = $this->bin->listForLearningPath($this->lpId, 1, null, [$b]);
        $this->assertSame([], array_values(array_filter($onlyB, static fn (array $r): bool => (int) $r['user_id'] === $a)), 'Users outside the allowed list are never shown');

        $sessionList = $this->bin->listForLearningPath($this->lpId, 1, $session, [$a]);
        $this->assertSame([(int) $archivesA[1]['id']], array_map(static fn (array $r): int => (int) $r['id'], $sessionList));
    }

    public function testListWithoutAllowedUsersIsEmptyWithoutQuerying(): void
    {
        $this->assertSame([], $this->bin->listForLearningPath($this->lpId, 1, null, []));
    }

    public function testListIsScopedToTheLearningPathAndCourse(): void
    {
        $a = $this->rawUser('a');
        $this->view($a);
        $this->capture($a);

        $this->assertNotSame([], $this->bin->listForLearningPath($this->lpId, 1, null, [$a]));
        $this->assertSame([], $this->bin->listForLearningPath($this->lpId + 9999, 1, null, [$a]));
        $this->assertSame([], $this->bin->listForLearningPath($this->lpId, 2, null, [$a]));
    }

    public function testGetArchiveUserIdChecksLearningPathCourseAndSession(): void
    {
        $a = $this->rawUser('a');
        $session = $this->rawSession('lp');
        $this->view($a);
        $this->capture($a);
        $archiveId = (int) $this->archives($a)[0]['id'];

        $this->assertSame($a, $this->bin->getArchiveUserId($archiveId, $this->lpId, 1, null));

        foreach ([[$this->lpId + 9999, 1, null], [$this->lpId, 2, null], [$this->lpId, 1, $session]] as [$lp, $course, $sid]) {
            try {
                $this->bin->getArchiveUserId($archiveId, $lp, $course, $sid);
                $this->fail('A record of another context must not be resolved');
            } catch (NotFoundHttpException) {
                $this->addToAssertionCount(1);
            }
        }

        $this->conn()->executeStatement("UPDATE learning_path_tracking_recycle_bin SET restored_at = '2001-01-01 00:00:00' WHERE id = ?", [$archiveId]);
        $this->expectException(NotFoundHttpException::class);
        $this->bin->getArchiveUserId($archiveId, $this->lpId, 1, null);
    }

    public function testRestoreRecreatesTheDeletedProgressWithItsOriginalIds(): void
    {
        $a = $this->rawUser('a');
        $viewId = $this->view($a, null, 63);
        $itemViewId = $this->itemView($viewId);
        $commentId = $this->comment($itemViewId);
        $this->capture($a);
        $archiveId = (int) $this->archives($a)[0]['id'];
        $this->wipeLive($a);

        $this->restore($archiveId);

        $view = $this->conn()->fetchAssociative('SELECT * FROM c_lp_view WHERE iid = ?', [$viewId]);
        $this->assertSame($a, (int) $view['user_id']);
        $this->assertSame(63, (int) $view['progress']);
        $item = $this->conn()->fetchAssociative('SELECT * FROM c_lp_item_view WHERE iid = ?', [$itemViewId]);
        $this->assertSame($viewId, (int) $item['lp_view_id']);
        $this->assertSame('completed', $item['status']);
        $this->assertSame(77, (int) $item['total_time']);
        $this->assertEqualsWithDelta(8.5, (float) $item['score'], 0.0001);
        $this->assertSame(1, (int) $this->conn()->fetchOne('SELECT COUNT(*) FROM c_lp_iv_comment WHERE iid = ?', [$commentId]));

        $record = $this->archives($a)[0];
        $this->assertNotNull($record['restored_at']);
        $this->assertSame(1, (int) $record['restored_by']);
    }

    public function testRestoreTwiceIsRefused(): void
    {
        $a = $this->rawUser('a');
        $this->itemView($this->view($a));
        $this->capture($a);
        $archiveId = (int) $this->archives($a)[0]['id'];
        $this->wipeLive($a);
        $this->restore($archiveId);

        $this->expectException(NotFoundHttpException::class);
        $this->restore($archiveId);
    }

    public function testRestoreNeverOverwritesNewProgress(): void
    {
        $a = $this->rawUser('a');
        $this->view($a, null, 10);
        $this->capture($a);
        $archiveId = (int) $this->archives($a)[0]['id'];
        $this->wipeLive($a);
        $newView = $this->view($a, null, 99);

        try {
            $this->restore($archiveId);
            $this->fail('The learner started again, the restore must be refused');
        } catch (ConflictHttpException $e) {
            $this->assertStringContainsString('new progress', $e->getMessage());
        }
        $this->assertSame(99, (int) $this->conn()->fetchOne('SELECT progress FROM c_lp_view WHERE iid = ?', [$newView]));
        $this->assertNull($this->archives($a)[0]['restored_at']);
    }

    public function testRestoreIntoAnotherSessionContextIsRefused(): void
    {
        $a = $this->rawUser('a');
        $session = $this->rawSession('lp');
        $this->view($a);
        $this->capture($a);
        $archiveId = (int) $this->archives($a)[0]['id'];
        $this->wipeLive($a);

        $this->expectException(NotFoundHttpException::class);
        $this->restore($archiveId, $session);
    }

    public function testRestoreRefusesATamperedSnapshot(): void
    {
        $a = $this->rawUser('a');
        $this->view($a);
        $this->capture($a);
        $row = $this->archives($a)[0];
        $this->wipeLive($a);
        $this->conn()->executeStatement('UPDATE learning_path_tracking_recycle_bin SET snapshot = ? WHERE id = ?', [$row['snapshot'].' ', $row['id']]);

        try {
            $this->restore((int) $row['id']);
            $this->fail('A snapshot that no longer matches its hash must not be restored');
        } catch (ConflictHttpException $e) {
            $this->assertStringContainsString('integrity', $e->getMessage());
        }
        $this->assertSame(0, (int) $this->conn()->fetchOne('SELECT COUNT(*) FROM c_lp_view WHERE user_id = ?', [$a]));
    }

    public function testRestoreRefusesAForgedSnapshotWithAValidHashForAnotherLearner(): void
    {
        // The hash only proves integrity, not origin: the content is re-checked against the archive's own context.
        $a = $this->rawUser('a');
        $victim = $this->rawUser('victim');
        $this->view($a);
        $this->capture($a);
        $row = $this->archives($a)[0];
        $this->wipeLive($a);

        $codec = new ExerciseAttemptRecycleSnapshotCodec();
        $tables = $codec->decode($row['snapshot']);
        $tables['c_lp_view'][0]['user_id'] = (string) $victim;
        $forged = $codec->encode($tables);
        $this->conn()->executeStatement('UPDATE learning_path_tracking_recycle_bin SET snapshot = ?, snapshot_sha256 = ? WHERE id = ?', [$forged, hash('sha256', $forged), $row['id']]);

        $this->expectException(ConflictHttpException::class);
        $this->expectExceptionMessage('inconsistent');
        $this->restore((int) $row['id']);
    }

    public function testRestoreRefusesAnArchiveWithAnUnknownTable(): void
    {
        $a = $this->rawUser('a');
        $this->view($a);
        $this->capture($a);
        $row = $this->archives($a)[0];
        $this->wipeLive($a);

        $codec = new ExerciseAttemptRecycleSnapshotCodec();
        $tables = $codec->decode($row['snapshot']);
        $tables['user'] = [['id' => '1', 'username' => 'pwned']];
        $forged = $codec->encode($tables);
        $this->conn()->executeStatement('UPDATE learning_path_tracking_recycle_bin SET snapshot = ?, snapshot_sha256 = ? WHERE id = ?', [$forged, hash('sha256', $forged), $row['id']]);

        $this->expectException(ConflictHttpException::class);
        $this->expectExceptionMessage('Invalid learning path archive structure');
        $this->restore((int) $row['id']);
    }

    public function testRestoreRefusesAnItemViewOfAnotherView(): void
    {
        $a = $this->rawUser('a');
        $viewId = $this->view($a);
        $this->itemView($viewId);
        $this->capture($a);
        $row = $this->archives($a)[0];
        $this->wipeLive($a);

        $codec = new ExerciseAttemptRecycleSnapshotCodec();
        $tables = $codec->decode($row['snapshot']);
        $tables['c_lp_item_view'][0]['lp_view_id'] = (string) ($viewId + 100000);
        $forged = $codec->encode($tables);
        $this->conn()->executeStatement('UPDATE learning_path_tracking_recycle_bin SET snapshot = ?, snapshot_sha256 = ? WHERE id = ?', [$forged, hash('sha256', $forged), $row['id']]);

        $this->expectException(ConflictHttpException::class);
        $this->expectExceptionMessage('different view');
        $this->restore((int) $row['id']);
    }

    public function testDeleteCommentsRemovesOnlyTheCommentsOfTheGivenItemViews(): void
    {
        $a = $this->rawUser('a');
        $viewId = $this->view($a);
        $i1 = $this->itemView($viewId);
        $i2 = $this->itemView($viewId);
        $this->comment($i1);
        $this->comment($i2);

        $this->bin->deleteComments([]);
        $this->assertSame(2, (int) $this->conn()->fetchOne('SELECT COUNT(*) FROM c_lp_iv_comment WHERE lp_iv_id IN (?, ?)', [$i1, $i2]));

        $this->bin->deleteComments([$i1]);
        $this->assertSame(0, (int) $this->conn()->fetchOne('SELECT COUNT(*) FROM c_lp_iv_comment WHERE lp_iv_id = ?', [$i1]));
        $this->assertSame(1, (int) $this->conn()->fetchOne('SELECT COUNT(*) FROM c_lp_iv_comment WHERE lp_iv_id = ?', [$i2]));
    }
}
