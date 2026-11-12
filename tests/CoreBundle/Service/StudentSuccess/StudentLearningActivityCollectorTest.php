<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service\StudentSuccess;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Service\StudentSuccess\StudentLearningActivityCollector;
use Chamilo\Tests\ChamiloTestTrait;
use Chamilo\Tests\CoreBundle\Service\RawReportingFixturesTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * The activity payload sent to the Student Success AI feedback (StudentSuccessController). The SQL scopes every
 * section to ONE learner, ONE course and ONE session context (no session = session_id NULL or 0); free text is
 * stripped of HTML. Only the learning path and exercise sections are pinned here: the forum, assignment, survey and
 * attendance sections join resource_link/resource_node rows, which tests must not insert (deadlocks), and the
 * gradebook section is a calculator call, not SQL. Learners are raw users ("rp_"); the quiz, its question and
 * answers come from the installer demo content.
 */
final class StudentLearningActivityCollectorTest extends KernelTestCase
{
    use ChamiloTestTrait;
    use RawReportingFixturesTrait;

    private StudentLearningActivityCollector $collector;
    private Course $course;
    private int $lpId;
    private int $itemId;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->collector = self::getContainer()->get(StudentLearningActivityCollector::class);
        $this->course = $this->getEntityManager()->find(Course::class, 1);
        $this->lpId = (int) $this->conn()->fetchOne('SELECT MIN(iid) FROM c_lp');
        $this->itemId = (int) $this->conn()->fetchOne('SELECT MIN(iid) FROM c_lp_item WHERE lp_id = ?', [$this->lpId]);
        if ($this->lpId <= 0 || $this->itemId <= 0) {
            $this->markTestSkipped('The installer demo learning path is missing.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function collect(int $userId, ?int $sessionId = null, int $courseId = 1): array
    {
        $this->fresh();
        $em = $this->getEntityManager();

        return $this->collector->collect(
            $em->find(Course::class, $courseId),
            null === $sessionId ? null : $em->find(Session::class, $sessionId),
            $this->entityUser($userId)
        );
    }

    private function view(int $userId, ?int $sessionId, int $progress, int $courseId = 1): int
    {
        $this->conn()->executeStatement(
            'INSERT INTO c_lp_view (user_id, lp_id, c_id, session_id, view_count, last_item, progress, completion_date) VALUES (?, ?, ?, ?, 2, ?, ?, ?)',
            [$userId, $this->lpId, $courseId, $sessionId, $this->itemId, $progress, 100 === $progress ? '2001-02-03' : null]
        );

        return (int) $this->conn()->lastInsertId();
    }

    private function itemView(int $viewId, string $status, float $score, ?string $maxScore = '10'): int
    {
        $this->conn()->executeStatement(
            'INSERT INTO c_lp_item_view (lp_item_id, lp_view_id, view_count, start_time, total_time, score, max_score, progress, status, core_exit)
             VALUES (?, ?, 3, 1000, 120, ?, ?, 50.5, ?, \'none\')',
            [$this->itemId, $viewId, $score, $maxScore, $status]
        );

        return (int) $this->conn()->lastInsertId();
    }

    private function attempt(int $userId, ?int $sessionId, string $start, float $score, float $max, int $courseId = 1, int $quizId = 1): int
    {
        $this->conn()->executeStatement(
            "INSERT INTO track_e_exercises (exe_exo_id, exe_user_id, c_id, session_id, exe_date, score, max_score, user_ip, status, data_tracking, start_date, steps_counter, orig_lp_id, orig_lp_item_id, exe_duration, orig_lp_item_view_id, questions_to_check)
             VALUES (?, ?, ?, ?, ?, ?, ?, '', '', '', ?, 0, 0, 0, 90, 0, '')",
            [$quizId, $userId, $courseId, $sessionId, $start, $score, $max, $start]
        );

        return (int) $this->conn()->lastInsertId();
    }

    private function questionAttempt(int $exeId, int $userId, string $answer, float $marks): int
    {
        $this->conn()->executeStatement(
            "INSERT INTO track_e_attempt (exe_id, user_id, question_id, answer, teacher_comment, marks, tms, seconds_spent)
             VALUES (?, ?, 1, ?, '', ?, '2001-01-01 10:05:00', 7)",
            [$exeId, $userId, $answer, $marks]
        );

        return (int) $this->conn()->lastInsertId();
    }

    public function testPayloadShapeDeclaresNoDirectIdentifiersAndTheCourseContext(): void
    {
        $user = $this->rawUser('ss');
        $session = $this->rawSession('ss');

        $payload = $this->collect($user, $session);

        $this->assertSame(1, $payload['version']);
        $this->assertSame(1, $payload['course']['id']);
        $this->assertSame($session, $payload['context']['sessionId']);
        $this->assertSame(['reference' => 'student', 'directIdentifiersIncluded' => false], $payload['student']);
        $this->assertFalse($payload['privacy']['emailIncluded']);
        $this->assertFalse($payload['privacy']['usernameIncluded']);
        $this->assertFalse($payload['privacy']['ipAddressIncluded']);
        $this->assertSame(0, $this->collect($user)['context']['sessionId'], 'No session is 0');
        $this->assertFalse($payload['sections']['chat']['available']);
        $this->assertSame(['learningPaths', 'exercises', 'forums', 'assignments', 'surveys', 'attendance', 'gradebook', 'chat'], array_keys($payload['sections']));
        $this->assertSame(['learningPaths', 'exercises', 'forums', 'assignments', 'surveys', 'attendance', 'gradebook', 'chat'], array_keys($payload['stats']));
        $this->assertStringNotContainsString($this->entityUser($user)->getEmail(), json_encode($payload));
    }

    public function testLearnerWithoutActivityGetsEmptyLearningPathsAndExercises(): void
    {
        $user = $this->rawUser('ss');
        $payload = $this->collect($user);

        $this->assertSame(['available' => true, 'items' => []], $payload['sections']['learningPaths']);
        $this->assertSame(['available' => true, 'items' => []], $payload['sections']['exercises']);
        $this->assertSame(0, $payload['stats']['learningPaths']);
        $this->assertSame(0, $payload['stats']['exercises']);
    }

    public function testLearningPathViewsAreScopedToLearnerCourseAndSession(): void
    {
        $user = $this->rawUser('ss');
        $other = $this->rawUser('other');
        $session = $this->rawSession('ss');
        $base = $this->view($user, null, 40);
        $inSession = $this->view($user, $session, 100);
        $this->view($user, null, 42, 2);
        $this->view($other, null, 43);

        $noSession = $this->collect($user)['sections']['learningPaths']['items'];
        $this->assertSame([$base], array_column($noSession, 'viewId'), 'Only the session-less view, none of the session or other course');
        $this->assertSame([40], array_column($noSession, 'progress'));

        $inSessionItems = $this->collect($user, $session)['sections']['learningPaths']['items'];
        $this->assertSame([$inSession], array_column($inSessionItems, 'viewId'));
        $this->assertSame('2001-02-03', $inSessionItems[0]['completionDate']);
        $this->assertNull($noSession[0]['completionDate']);
        $this->assertSame($this->lpId, $inSessionItems[0]['learningPathId']);
        $this->assertSame(2, $inSessionItems[0]['viewCount']);
        $this->assertSame($this->itemId, $inSessionItems[0]['lastItemId']);
    }

    public function testLearningPathItemViewsAreAttachedToTheirViewWithTypedValues(): void
    {
        $user = $this->rawUser('ss');
        $v1 = $this->view($user, null, 10);
        $v2 = $this->view($user, null, 20);
        $i1 = $this->itemView($v1, 'completed', 8.5, '10');
        $i2 = $this->itemView($v2, 'incomplete', 0.0, null);

        $items = $this->collect($user)['sections']['learningPaths']['items'];
        $byView = [];
        foreach ($items as $item) {
            $byView[$item['viewId']] = $item['items'];
        }

        $this->assertCount(1, $byView[$v1]);
        $this->assertSame($i1, $byView[$v1][0]['itemViewId']);
        $this->assertSame($this->itemId, $byView[$v1][0]['itemId']);
        $this->assertSame('completed', $byView[$v1][0]['status']);
        $this->assertSame(8.5, $byView[$v1][0]['score']);
        $this->assertSame(10.0, $byView[$v1][0]['maxScore']);
        $this->assertSame(50.5, $byView[$v1][0]['progress']);
        $this->assertSame(120, $byView[$v1][0]['timeSeconds']);
        $this->assertSame(3, $byView[$v1][0]['viewCount']);
        $this->assertNull($byView[$v2][0]['maxScore'], 'A missing max score is null, not 0');
        $this->assertSame($i2, $byView[$v2][0]['itemViewId']);
    }

    public function testExercisesAreScopedToLearnerCourseAndSessionNewestStartFirst(): void
    {
        $user = $this->rawUser('ss');
        $other = $this->rawUser('other');
        $session = $this->rawSession('ss');
        $old = $this->attempt($user, null, '2001-01-01 10:00:00', 3, 10);
        $new = $this->attempt($user, null, '2001-01-02 10:00:00', 7.5, 10);
        $third = $this->attempt($user, null, '2001-01-03 10:00:00', 1, 10);
        $inSession = $this->attempt($user, $session, '2001-01-04 10:00:00', 9, 10);
        $this->attempt($user, null, '2001-01-05 10:00:00', 9, 10, 2);
        $this->attempt($other, null, '2001-01-06 10:00:00', 9, 10);

        $items = $this->collect($user)['sections']['exercises']['items'];
        $this->assertCount(3, $items, 'Session-less attempts of this course only');
        $this->assertSame($third, $items[0]['attemptId']);
        $this->assertSame($old, $items[2]['attemptId']);
        $this->assertSame($new, $items[1]['attemptId']);
        $this->assertSame('2001-01-03 10:00:00', $items[0]['startedAt']);

        $sessionItems = $this->collect($user, $session)['sections']['exercises']['items'];
        $this->assertSame([$inSession], array_column($sessionItems, 'attemptId'));
        $this->assertSame(90.0, $sessionItems[0]['percentage']);
        $this->assertSame(1, $sessionItems[0]['exerciseId']);
        $this->assertSame('Mini-test 1', $sessionItems[0]['title']);
        $this->assertSame(90, $sessionItems[0]['durationSeconds']);
    }

    public function testExercisePercentageIsNullWhenTheMaximumIsZero(): void
    {
        $user = $this->rawUser('ss');
        $this->attempt($user, null, '2001-01-01 10:00:00', 0, 0);
        $this->attempt($user, null, '2001-01-02 10:00:00', 1, 3);

        $items = $this->collect($user)['sections']['exercises']['items'];
        $this->assertSame(33.33, $items[0]['percentage']);
        $this->assertNull($items[1]['percentage']);
    }

    public function testQuestionAttemptsBelongToTheLearnerAndCarryThePossibleAnswers(): void
    {
        $user = $this->rawUser('ss');
        $other = $this->rawUser('other');
        $exe = $this->attempt($user, null, '2001-01-01 10:00:00', 3, 10);
        $mine = $this->questionAttempt($exe, $user, '<p>My   <b>answer</b> &amp; more</p>', 2.5);
        $this->questionAttempt($exe, $other, 'someone else', 9);

        $questions = $this->collect($user)['sections']['exercises']['items'][0]['questions'];

        $this->assertCount(1, $questions, 'Attempt rows of another user on the same attempt are ignored');
        $this->assertSame($mine, $questions[0]['attemptAnswerId']);
        $this->assertSame(1, $questions[0]['questionId']);
        $this->assertSame('My answer & more', $questions[0]['studentAnswer'], 'HTML is stripped and whitespace collapsed');
        $this->assertSame(2.5, $questions[0]['marks']);
        $this->assertSame(7, $questions[0]['secondsSpent']);
        $this->assertSame('2001-01-01 10:05:00', $questions[0]['answeredAt']);

        $expected = (int) $this->conn()->fetchOne('SELECT COUNT(*) FROM c_quiz_answer WHERE question_id = 1');
        $this->assertSame($expected, \count($questions[0]['possibleAnswers']));
        $this->assertSame(['id', 'title', 'correct'], array_keys($questions[0]['possibleAnswers'][0]));
    }

    public function testStatsCountTheItemsOfEverySection(): void
    {
        $user = $this->rawUser('ss');
        $this->view($user, null, 1);
        $this->view($user, null, 2);
        $this->attempt($user, null, '2001-01-01 10:00:00', 1, 2);

        $stats = $this->collect($user)['stats'];
        $this->assertSame(2, $stats['learningPaths']);
        $this->assertSame(1, $stats['exercises']);
        $this->assertSame(0, $stats['chat']);
    }

    public function testTiesOnTheStartDateAreBrokenByTheNewestAttemptAndViewsAreNewestFirst(): void
    {
        $user = $this->rawUser('ss');
        $first = $this->attempt($user, null, '2001-01-01 10:00:00', 1, 2);
        $second = $this->attempt($user, null, '2001-01-01 10:00:00', 2, 2);
        $v1 = $this->view($user, null, 1);
        $v2 = $this->view($user, null, 2);

        $payload = $this->collect($user)['sections'];
        $this->assertSame([$second, $first], array_column($payload['exercises']['items'], 'attemptId'));
        $this->assertSame([$v2, $v1], array_column($payload['learningPaths']['items'], 'viewId'));
    }
}
