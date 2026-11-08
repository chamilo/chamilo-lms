<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\Tests\ChamiloTestTrait;
use Doctrine\DBAL\Connection;
use ExerciseLib;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * Exercise::read(), Question::read() and the attempt helpers do not scope by course, so every page taking an exe_id,
 * an exerciseId or a question id from the request must first prove it belongs to the current course (and session).
 * These tests pin the guards doing that (ExerciseLib::attemptBelongsToCourse, exerciseBelongsToCourse,
 * questionBelongsToCourse) and the flag that keeps exercise_show.php's login-free export mode out of requests.
 * Fixtures are raw prepared inserts on spare resource nodes, tags start with "ea_".
 */
final class ExerciseAccessControlTest extends KernelTestCase
{
    use ChamiloTestTrait;

    private Connection $conn;
    private int $seq = 0;
    private int $nextNode = 0;

    /**
     * @var int[]|null
     */
    private ?array $spare = null;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        Container::setContainer($container);
        Container::setLegacyServices($container);
        $session = new Session(new MockArraySessionStorage());
        Container::setSession($session);
        $request = Request::create('/main/exercise/exercise_report.php');
        $request->setSession($session);
        Container::setRequest($request);
        $this->conn = $container->get('doctrine')->getConnection();
    }

    private function link(int $node, ?int $courseId, ?int $sessionId = null, ?string $deletedAt = null): void
    {
        if (null === $courseId) {
            return;
        }
        $now = '2020-01-01 00:00:00';
        $this->conn->insert('resource_link', [
            'resource_node_id' => $node,
            'c_id' => $courseId,
            'session_id' => $sessionId,
            'visibility' => 2,
            'display_order' => 0,
            'resource_type_group' => 1,
            'deleted_at' => $deletedAt,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function node(): int
    {
        $this->spare ??= $this->conn->fetchFirstColumn(
            'SELECT id FROM resource_node WHERE id NOT IN (SELECT resource_node_id FROM c_quiz WHERE resource_node_id IS NOT NULL)'
            .' AND id NOT IN (SELECT resource_node_id FROM c_quiz_question WHERE resource_node_id IS NOT NULL)'
            .' AND id NOT IN (SELECT resource_node_id FROM resource_link WHERE resource_node_id IS NOT NULL) ORDER BY id DESC LIMIT 12'
        );
        $this->assertGreaterThan($this->nextNode, \count($this->spare), 'fixture needs spare resource nodes');

        return (int) $this->spare[$this->nextNode++];
    }

    private function quiz(?int $courseId, ?int $sessionId = null, ?string $deletedAt = null): int
    {
        $node = $this->node();
        $this->conn->insert('c_quiz', [
            'title' => 'ea_quiz_'.(++$this->seq),
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
            'page_result_configuration' => '[]',
            'resource_node_id' => $node,
        ]);
        $id = (int) $this->conn->lastInsertId();
        $this->link($node, $courseId, $sessionId, $deletedAt);

        return $id;
    }

    private function question(?int $courseId): int
    {
        $node = $this->node();
        $this->conn->insert('c_quiz_question', [
            'question' => 'ea_question_'.(++$this->seq),
            'ponderation' => 1,
            'position' => 0,
            'type' => 1,
            'level' => 0,
            'mandatory' => 0,
            'resource_node_id' => $node,
        ]);
        $id = (int) $this->conn->lastInsertId();
        $this->link($node, $courseId);

        return $id;
    }

    private function session(): int
    {
        $this->conn->insert('session', [
            'title' => 'ea_session_'.(++$this->seq).'_'.uniqid(),
            'nbr_courses' => 0,
            'nbr_users' => 0,
            'nbr_classes' => 0,
            'visibility' => 1,
            'status' => 0,
        ]);

        return (int) $this->conn->lastInsertId();
    }

    private function attempt(int $courseId, ?int $quizId = null): int
    {
        $this->conn->insert('track_e_exercises', [
            'exe_user_id' => 1,
            'c_id' => $courseId,
            'session_id' => null,
            'exe_exo_id' => $quizId,
            'exe_date' => '2020-01-01 10:00:00',
            'score' => 0,
            'max_score' => 0,
            'user_ip' => '10.0.0.1',
            'status' => '',
            'data_tracking' => '',
            'start_date' => '2020-01-01 09:00:00',
            'steps_counter' => 0,
            'orig_lp_id' => 0,
            'orig_lp_item_id' => 0,
            'exe_duration' => 0,
            'orig_lp_item_view_id' => 0,
            'questions_to_check' => '',
        ]);

        return (int) $this->conn->lastInsertId();
    }

    public function testAttemptOfAnotherCourseIsRejected(): void
    {
        $exe = $this->attempt(2);

        $this->assertFalse(ExerciseLib::attemptBelongsToCourse($exe, 1), 'a teacher of course 1 must not reach it');
        $this->assertTrue(ExerciseLib::attemptBelongsToCourse($exe, 2));
    }

    public function testAttemptUnknownEmptyOrNoCourseIsRejected(): void
    {
        $exe = $this->attempt(1);

        $this->assertFalse(ExerciseLib::attemptBelongsToCourse(999999999, 1));
        $this->assertFalse(ExerciseLib::attemptBelongsToCourse(0, 1));
        $this->assertFalse(ExerciseLib::attemptBelongsToCourse($exe, 0), 'no course context never matches');
    }

    public function testAttemptDefaultsToTheCurrentCourse(): void
    {
        $exe = $this->attempt(1);

        // No course in the legacy context: the default course id is 0 and nothing matches.
        $this->assertFalse(ExerciseLib::attemptBelongsToCourse($exe));
    }

    public function testExerciseOfAnotherCourseIsRejected(): void
    {
        $quiz = $this->quiz(2);

        $this->assertFalse(ExerciseLib::exerciseBelongsToCourse($quiz, 1, 0));
        $this->assertTrue(ExerciseLib::exerciseBelongsToCourse($quiz, 2, 0));
    }

    public function testExerciseWithoutLinkOrSoftDeletedIsRejected(): void
    {
        $unlinked = $this->quiz(null);
        $deleted = $this->quiz(1, null, '2020-02-02 00:00:00');
        $live = $this->quiz(1);

        $this->assertFalse(ExerciseLib::exerciseBelongsToCourse($unlinked, 1, 0));
        $this->assertFalse(ExerciseLib::exerciseBelongsToCourse($deleted, 1, 0));
        $this->assertTrue(ExerciseLib::exerciseBelongsToCourse($live, 1, 0));
        $this->assertFalse(ExerciseLib::exerciseBelongsToCourse(0, 1, 0));
        $this->assertFalse(ExerciseLib::exerciseBelongsToCourse($live, 0, 0));
    }

    public function testSessionExerciseIsNotBaseCourseContentButBaseContentShowsInSessions(): void
    {
        $sid = $this->session();
        $other = $this->session();
        $base = $this->quiz(1);
        $inSession = $this->quiz(1, $sid);

        $this->assertTrue(ExerciseLib::exerciseBelongsToCourse($base, 1, $sid), 'base content is visible in a session');
        $this->assertTrue(ExerciseLib::exerciseBelongsToCourse($inSession, 1, $sid));
        $this->assertFalse(ExerciseLib::exerciseBelongsToCourse($inSession, 1, 0), 'session content is not base content');
        $this->assertFalse(ExerciseLib::exerciseBelongsToCourse($inSession, 1, $other), 'nor content of another session');
    }

    public function testQuestionOfAnotherCourseIsRejected(): void
    {
        $question = $this->question(2);
        $unlinked = $this->question(null);

        $this->assertFalse(ExerciseLib::questionBelongsToCourse($question, 1, 0));
        $this->assertTrue(ExerciseLib::questionBelongsToCourse($question, 2, 0));
        $this->assertFalse(ExerciseLib::questionBelongsToCourse($unlinked, 1, 0));
        $this->assertFalse(ExerciseLib::questionBelongsToCourse(0, 1, 0));
    }

    public function testExportModeFlagCannotBeSetFromTheRequest(): void
    {
        // exercise_show.php used to trust export_type=all_results|result_pdf from the query string and skipped every
        // access check for it: reading any attempt needed no login at all.
        $_GET = ['action' => 'export', 'export_type' => 'all_results'];
        $_REQUEST = $_GET;
        try {
            $this->assertFalse(ExerciseLib::isInternalAttemptExport());
        } finally {
            $_GET = [];
            $_REQUEST = [];
        }
    }

    public function testGridFilterValuesCannotBreakOutOfTheQuotedContext(): void
    {
        // model.ajax.php getWhereClause() builds the results grid's $extra_where_conditions as
        // " <escapeField(col)> <op> '<escape_string(val)>' ": a value is quoted and a column name is reduced to
        // [A-Za-z0-9_.], so neither can close the quote or add SQL (audit: no injection found).
        $payload = "x' OR '1'='1' -- \\";
        $escaped = \Database::escape_string($payload);
        $this->assertStringNotContainsString("x' OR", $escaped, 'the quote must be escaped');
        $fragment = "te.title = '".$escaped."'";
        $rows = $this->conn->fetchFirstColumn("SELECT te.title FROM (SELECT 'a' AS title) te WHERE 1 = 1 AND ".$fragment);
        $this->assertSame([], $rows, 'the payload is a literal that matches nothing, not extra SQL');
        $this->assertSame('te.exe_idOR1', \Database::escapeField("te.exe_id OR 1; --"));
    }
}
