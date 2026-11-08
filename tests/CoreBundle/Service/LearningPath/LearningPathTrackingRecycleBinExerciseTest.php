<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service\LearningPath;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Service\LearningPath\LearningPathTrackingRecycleBin;
use Chamilo\CourseBundle\Repository\CQuizRepository;
use Chamilo\Tests\ChamiloTestTrait;
use Chamilo\Tests\CoreBundle\Service\GenericRowInsertTrait;
use Chamilo\Tests\CoreBundle\Service\RawReportingFixturesTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * The exercise-attempt branch of LearningPathTrackingRecycleBin (deleteExerciseAttempts = true): the attempts of
 * the learner made from the learning path are archived in the exercise recycle bin and removed, the LP snapshot
 * remembers the archives, and restore() brings them back. The graph (LP, item, quiz) is the installer demo
 * content; attempts and views are raw rows with FIXED dates. Complements LearningPathTrackingRecycleBinTest
 * (which covers the branch where attempts are only detached). Tag sv3_.
 */
final class LearningPathTrackingRecycleBinExerciseTest extends KernelTestCase
{
    use ChamiloTestTrait;
    use RawReportingFixturesTrait;
    use GenericRowInsertTrait;

    private LearningPathTrackingRecycleBin $bin;
    private Course $course;
    private int $lpId;
    private int $itemId;
    private int $quizId;

    protected function setUp(): void
    {
        self::bootKernel();
        $c = self::getContainer();
        $this->bin = $c->get(LearningPathTrackingRecycleBin::class);
        $this->course = $this->getEntityManager()->find(Course::class, 1);
        $this->lpId = (int) $this->conn()->fetchOne('SELECT MIN(iid) FROM c_lp');
        $this->itemId = (int) $this->conn()->fetchOne('SELECT MIN(iid) FROM c_lp_item WHERE lp_id = ?', [$this->lpId]);
        $this->quizId = 0;
        $repo = $c->get(CQuizRepository::class);
        foreach ($this->conn()->fetchFirstColumn('SELECT iid FROM c_quiz ORDER BY iid LIMIT 20') as $id) {
            if (null !== $repo->findInCourseContext((int) $id, $this->course, null)) {
                $this->quizId = (int) $id;

                break;
            }
        }
        if ($this->lpId <= 0 || $this->itemId <= 0 || $this->quizId <= 0) {
            $this->markTestSkipped('The installer demo learning path or quiz is missing.');
        }
    }

    private function attempt(int $userId, int $lp, int $quiz, ?int $sessionId = null, int $courseId = 1): int
    {
        $this->insert('track_e_exercises', [
            'exe_user_id' => $userId, 'exe_exo_id' => $quiz, 'c_id' => $courseId, 'session_id' => $sessionId,
            'orig_lp_id' => $lp, 'orig_lp_item_id' => $lp > 0 ? $this->itemId : 0, 'orig_lp_item_view_id' => 0,
            'exe_date' => '2020-01-01 10:00:00', 'start_date' => '2020-01-01 09:50:00', 'status' => '',
        ]);

        return (int) $this->conn()->lastInsertId();
    }

    private function view(int $userId): void
    {
        $this->conn()->executeStatement(
            'INSERT INTO c_lp_view (user_id, lp_id, c_id, session_id, view_count, last_item, progress) VALUES (?, ?, 1, NULL, 1, 0, 40)',
            [$userId, $this->lpId]
        );
    }

    private function capture(int $userId, bool $deleteAttempts = true): void
    {
        $this->fresh();
        $this->bin->captureBeforeReset($this->lpId, $this->course, null, [$userId], $deleteAttempts, false, 1);
    }

    private function live(int $attemptId): bool
    {
        return false !== $this->conn()->fetchOne('SELECT 1 FROM track_e_exercises WHERE exe_id = ?', [$attemptId]);
    }

    private function snapshotArchives(int $userId): array
    {
        $this->assertSame(1, (int) $this->conn()->fetchOne('SELECT COUNT(*) FROM learning_path_tracking_recycle_bin WHERE user_id = ?', [$userId]));
        $row = $this->conn()->fetchAssociative('SELECT id, snapshot FROM learning_path_tracking_recycle_bin WHERE user_id = ?', [$userId]);
        $decoded = self::getContainer()->get('Chamilo\CoreBundle\Service\Exercise\ExerciseAttemptRecycleSnapshotCodec')->decode($row['snapshot']);

        return [(int) $row['id'], $decoded['__exercise_archives']];
    }

    public function testAttemptsMadeFromThisLearningPathAreArchivedAndRemoved(): void
    {
        $user = $this->rawUser('lp');
        $other = $this->rawUser('other');
        $this->view($user);
        $mine = $this->attempt($user, $this->lpId, $this->quizId);
        $standalone = $this->attempt($user, 0, $this->quizId);          // not made from the learning path
        $otherLp = $this->attempt($user, $this->lpId + 9999, $this->quizId);
        $others = $this->attempt($other, $this->lpId, $this->quizId);
        $otherCourse = $this->attempt($user, $this->lpId, $this->quizId, null, 2);

        $this->capture($user);

        $this->assertFalse($this->live($mine), 'Archived and removed from the live table');
        foreach ([$standalone, $otherLp, $others, $otherCourse] as $kept) {
            $this->assertTrue($this->live($kept), 'Attempt #'.$kept.' is not part of this reset');
        }
        $archive = $this->conn()->fetchAssociative('SELECT exercise_id, user_id, course_id, session_id, restored_at FROM exercise_attempt_recycle_bin WHERE attempt_id = ?', [$mine]);
        $this->assertSame([$this->quizId, $user, 1, null, null], [(int) $archive['exercise_id'], (int) $archive['user_id'], (int) $archive['course_id'], $archive['session_id'], $archive['restored_at']]);
        [, $archives] = $this->snapshotArchives($user);
        $this->assertCount(1, $archives);
        $this->assertSame((string) $mine, $archives[0]['attempt_id']);
        $this->assertSame((string) $this->quizId, $archives[0]['exercise_id']);
    }

    public function testWithoutTheFlagAttemptsStayLiveAndOnlyTheLinkIsRemembered(): void
    {
        $user = $this->rawUser('lp');
        $this->view($user);
        $mine = $this->attempt($user, $this->lpId, $this->quizId);

        $this->capture($user, false);

        $this->assertTrue($this->live($mine));
        $this->assertSame(0, (int) $this->conn()->fetchOne('SELECT COUNT(*) FROM exercise_attempt_recycle_bin WHERE attempt_id = ?', [$mine]));
        [, $archives] = $this->snapshotArchives($user);
        $this->assertSame([], $archives);
    }

    public function testAnAttemptOfAnExerciseUnavailableInTheContextAbortsTheCapture(): void
    {
        $user = $this->rawUser('lp');
        $this->insert('c_quiz', ['title' => 'sv3 quiz without any link', 'page_result_configuration' => '[]']);
        $orphanQuiz = (int) $this->conn()->lastInsertId();
        $mine = $this->attempt($user, $this->lpId, $orphanQuiz);

        try {
            $this->capture($user);
            $this->fail('An unavailable exercise must abort the reset');
        } catch (ConflictHttpException $e) {
            $this->assertStringContainsString('unavailable', $e->getMessage());
        }

        $this->assertTrue($this->live($mine), 'Nothing was deleted');
        $this->assertSame(0, (int) $this->conn()->fetchOne('SELECT COUNT(*) FROM learning_path_tracking_recycle_bin WHERE user_id = ?', [$user]));
    }

    public function testRestoreBringsTheArchivedAttemptBack(): void
    {
        $user = $this->rawUser('lp');
        $this->view($user);
        $mine = $this->attempt($user, $this->lpId, $this->quizId);
        $this->capture($user);
        [$archiveId] = $this->snapshotArchives($user);
        $this->conn()->executeStatement('DELETE FROM c_lp_view WHERE user_id = ?', [$user]);

        $this->fresh();
        $this->bin->restore($archiveId, $this->lpId, $this->course, null, 1);

        $this->assertTrue($this->live($mine), 'The attempt is back under its original id');
        $row = $this->conn()->fetchAssociative('SELECT exe_user_id, exe_exo_id, orig_lp_id FROM track_e_exercises WHERE exe_id = ?', [$mine]);
        $this->assertSame([$user, $this->quizId, $this->lpId], [(int) $row['exe_user_id'], (int) $row['exe_exo_id'], (int) $row['orig_lp_id']]);
        $this->assertNotNull($this->conn()->fetchOne('SELECT restored_at FROM exercise_attempt_recycle_bin WHERE attempt_id = ?', [$mine]), 'The exercise archive is marked restored');
        $this->assertNotNull($this->conn()->fetchOne('SELECT restored_at FROM learning_path_tracking_recycle_bin WHERE id = ?', [$archiveId]));
    }

    public function testRestoreRefusesWhenTheExerciseArchiveWasAlreadyRestoredElsewhere(): void
    {
        $user = $this->rawUser('lp');
        $this->view($user);
        $mine = $this->attempt($user, $this->lpId, $this->quizId);
        $this->capture($user);
        [$archiveId] = $this->snapshotArchives($user);
        $this->conn()->executeStatement('DELETE FROM c_lp_view WHERE user_id = ?', [$user]);
        $this->conn()->executeStatement("UPDATE exercise_attempt_recycle_bin SET restored_at = '2020-02-01 00:00:00' WHERE attempt_id = ?", [$mine]);

        $this->expectException(ConflictHttpException::class);
        $this->expectExceptionMessage('already restored');
        $this->fresh();
        $this->bin->restore($archiveId, $this->lpId, $this->course, null, 1);
    }

    public function testAnOlderRestoredArchiveOfTheSameAttemptIsIgnoredWhenLinkingTheNewOne(): void
    {
        $user = $this->rawUser('lp');
        $this->view($user);
        $mine = $this->attempt($user, $this->lpId, $this->quizId);
        // An earlier archive of this attempt id, already restored (and about another exercise).
        $this->insert('exercise_attempt_recycle_bin', [
            'attempt_id' => $mine, 'exercise_id' => 0, 'user_id' => $user, 'course_id' => 1, 'session_id' => null,
            'restored_at' => '2019-01-01 00:00:00',
        ]);

        $this->capture($user);

        [, $archives] = $this->snapshotArchives($user);
        $this->assertCount(1, $archives);
        $this->assertSame((string) $this->quizId, $archives[0]['exercise_id'], 'The live (not restored, newest) archive is the one linked');
    }

    public function testDetachedAttemptsGoBackToTheLearningPathOnRestore(): void
    {
        $user = $this->rawUser('lp');
        $this->view($user);
        $mine = $this->attempt($user, $this->lpId, $this->quizId);
        $this->capture($user, false);
        [$archiveId] = $this->snapshotArchives($user);
        // What the reporting reset does after the capture: detach the attempt and drop the views.
        $this->conn()->executeStatement('UPDATE track_e_exercises SET orig_lp_id = 0, orig_lp_item_id = 0, orig_lp_item_view_id = 0 WHERE exe_id = ?', [$mine]);
        $this->conn()->executeStatement('DELETE FROM c_lp_view WHERE user_id = ?', [$user]);

        $this->fresh();
        $this->bin->restore($archiveId, $this->lpId, $this->course, null, 1);

        $row = $this->conn()->fetchAssociative('SELECT orig_lp_id, orig_lp_item_id FROM track_e_exercises WHERE exe_id = ?', [$mine]);
        $this->assertSame([$this->lpId, $this->itemId], [(int) $row['orig_lp_id'], (int) $row['orig_lp_item_id']]);
    }

    public function testRestoreRefusesToReattachAnAttemptThatChangedAfterTheReset(): void
    {
        $user = $this->rawUser('lp');
        $this->view($user);
        $mine = $this->attempt($user, $this->lpId, $this->quizId);
        $this->capture($user, false);
        [$archiveId] = $this->snapshotArchives($user);
        $this->conn()->executeStatement('DELETE FROM c_lp_view WHERE user_id = ?', [$user]);
        // The attempt was NOT detached (still linked to the path, to another item): the guarded UPDATE matches nothing.
        $this->conn()->executeStatement('UPDATE track_e_exercises SET orig_lp_item_id = 777 WHERE exe_id = ?', [$mine]);

        $this->expectException(ConflictHttpException::class);
        $this->expectExceptionMessage('changed after the reset');
        $this->fresh();
        $this->bin->restore($archiveId, $this->lpId, $this->course, null, 1);
    }
}
