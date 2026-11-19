<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Legacy;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use Database;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\DBAL\Connection;
use Tracking;

/**
 * Wave 3 characterization tests (second part) for the Tracking library: they pin what the existing tests
 * left open before the raw SQL of the library is rewritten with the QueryBuilder.
 */
final class TrackingTier3C4CharacterizationTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    private Connection $conn;
    private User $alice;
    private User $bob;
    private Course $course;

    protected function setUp(): void
    {
        parent::setUp();

        Database::setManager($this->getEntityManager());
        $this->conn = $this->getEntityManager()->getConnection();
        $this->alice = $this->createUser('c4_alice');
        $this->bob = $this->createUser('c4_bob');
        $this->course = $this->createCourse('c4_course');
    }

    public function testAverageLpProgressForStudentListKeepsOneProgressPerLearningPathAndSession(): void
    {
        $lp = $this->insertLp('c4_lp');
        $cid = (int) $this->course->getId();
        $alice = (int) $this->alice->getId();
        // The same learning path in the base context (80) and in a session (20): two groups, 80 + 20 averaged = 50.
        $this->insertLpView($alice, $lp, $cid, 80, null);
        $this->insertLpView($alice, $lp, $cid, 20, (int) $this->createSession('c4_session')->getId());

        $this->assertEqualsWithDelta(50.0, Tracking::getAverageLearningPathProgressForStudentList([$alice]), 0.0001);
    }

    public function testTimeSpentInLpTakesTheQuizDurationFromTheExerciseAttemptsAndSyncsTheItemView(): void
    {
        $cid = (int) $this->course->getId();
        $alice = (int) $this->alice->getId();
        $lp = $this->insertLp('c4_lp_quiz');
        $quizId = $this->insertQuiz('c4_quiz');
        // The flat ordered list of the learning path only knows the children of its root item.
        $root = $this->insertLpItem($lp, 'root', 0, 0, 'root');
        $quizItem = $this->insertLpItem($lp, 'quiz', 20, 1, (string) $quizId, $root);
        $docItem = $this->insertLpItem($lp, 'document', 0, 2, '', $root);
        $view = $this->insertLpView($alice, $lp, $cid, 10);
        $quizItemView = $this->insertLpItemView($quizItem, $view, 100);
        $this->insertLpItemView($docItem, $view, 30);
        // Two attempts of the quiz of this learning path item: 250 + 50 seconds; an incomplete one and another
        // learner's are not summed.
        $this->insertExercise($alice, $cid, $quizId, $lp, $quizItem, 250, 'completed');
        $this->insertExercise($alice, $cid, $quizId, $lp, $quizItem, 50, '');
        $this->insertExercise($alice, $cid, $quizId, $lp, $quizItem, 999, 'incomplete');
        $this->insertExercise((int) $this->bob->getId(), $cid, $quizId, $lp, $quizItem, 888, 'completed');

        // The item view time (100) is replaced by the attempts duration (300) and persisted, the document keeps 30.
        $this->assertSame(330, Tracking::get_time_spent_in_lp($alice, $this->course, [$lp], 0));
        $this->assertSame(
            '300',
            (string) $this->conn->fetchOne('SELECT total_time FROM c_lp_item_view WHERE iid = ?', [$quizItemView])
        );
    }

    public function testChatConnectionsOfTheLastDaysUseAWindowEndingNow(): void
    {
        $cid = (int) $this->course->getId();
        $alice = (int) $this->alice->getId();
        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        // One chat access a minute inside the 7 days window, one a minute outside, one now.
        foreach (['-7 days +1 minute', '-7 days -1 minute', '-1 hour'] as $offset) {
            $this->conn->insert('track_e_access', [
                'access_user_id' => $alice,
                'access_date' => $now->modify($offset)->format('Y-m-d H:i:s'),
                'c_id' => $cid,
                'access_tool' => 'chat',
                'session_id' => 0,
                'user_ip' => '127.0.0.1',
            ]);
        }

        $this->assertEquals(2, Tracking::chat_connections_during_last_x_days_by_course($this->course->getCode(), 7));
        $this->assertEquals(1, Tracking::chat_connections_during_last_x_days_by_course($this->course->getCode(), 1));
    }

    private function insertQuiz(string $title): int
    {
        $this->conn->insert('c_quiz', [
            'title' => $title,
            'type' => 1,
            'random' => 0,
            'random_answers' => 0,
            'results_disabled' => 0,
            'max_attempt' => 0,
            'feedback_type' => 0,
            'expired_time' => 0,
            'propagate_neg' => 0,
            'review_answers' => 0,
            'random_by_category' => 0,
            'display_category_name' => 0,
            'hide_question_title' => 0,
            'page_result_configuration' => '{}',
        ]);

        return (int) $this->conn->lastInsertId();
    }

    private function insertLpItem(int $lpId, string $type, float $maxScore, int $order, string $path, ?int $parent = null): int
    {
        $this->conn->insert('c_lp_item', [
            'lp_id' => $lpId,
            'title' => 'c4_item_'.$order,
            'item_type' => $type,
            'ref' => '',
            'path' => $path,
            'parent_item_id' => $parent,
            'min_score' => 0,
            'max_score' => $maxScore,
            'display_order' => $order,
            'launch_data' => '',
            'lvl' => 1,
        ]);

        return (int) $this->conn->lastInsertId();
    }

    private function insertLpItemView(int $itemId, int $viewId, int $total): int
    {
        $this->conn->insert('c_lp_item_view', [
            'lp_item_id' => $itemId,
            'lp_view_id' => $viewId,
            'view_count' => 1,
            'start_time' => 1000000,
            'total_time' => $total,
            'score' => 0,
            'status' => 'completed',
        ]);

        return (int) $this->conn->lastInsertId();
    }

    private function insertExercise(
        int $userId,
        int $courseId,
        int $quizId,
        int $lpId,
        int $lpItemId,
        int $duration,
        string $status
    ): void {
        $this->conn->insert('track_e_exercises', [
            'exe_user_id' => $userId,
            'c_id' => $courseId,
            'session_id' => null,
            'exe_exo_id' => $quizId,
            'exe_date' => '2001-01-01 10:00:00',
            'score' => 1,
            'max_score' => 2,
            'user_ip' => '127.0.0.1',
            'status' => $status,
            'data_tracking' => '',
            'start_date' => '2001-01-01 10:00:00',
            'steps_counter' => 0,
            'orig_lp_id' => $lpId,
            'orig_lp_item_id' => $lpItemId,
            'exe_duration' => $duration,
            'orig_lp_item_view_id' => 0,
            'questions_to_check' => '',
        ]);
    }

    private function insertLp(string $title): int
    {
        $this->conn->insert('c_lp', [
            'lp_type' => 1,
            'title' => $title,
            'path' => '',
            'force_commit' => 0,
            'content_maker' => '',
            'content_license' => '',
            'js_lib' => '',
            'debug' => 0,
            'theme' => '',
            'author' => '',
            'prerequisite' => 0,
            'hide_toc_frame' => 0,
            'seriousgame_mode' => 0,
            'autolaunch' => 0,
            'max_attempts' => 0,
            'subscribe_users' => 0,
            'created_on' => '2001-01-01 00:00:00',
            'modified_on' => '2001-01-01 00:00:00',
        ]);

        return (int) $this->conn->lastInsertId();
    }

    private function insertLpView(int $userId, int $lpId, int $courseId, ?int $progress, ?int $sessionId = null): int
    {
        $this->conn->insert('c_lp_view', [
            'user_id' => $userId,
            'lp_id' => $lpId,
            'c_id' => $courseId,
            'session_id' => $sessionId,
            'view_count' => 1,
            'last_item' => 0,
            'progress' => $progress,
        ]);

        return (int) $this->conn->lastInsertId();
    }
}
