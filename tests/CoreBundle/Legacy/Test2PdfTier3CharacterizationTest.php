<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use Chamilo\CoreBundle\Framework\Container;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session as HttpSession;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * Characterization tests for the readers of public/plugin/Test2Pdf/src/test2pdf.lib.php: showExerciseCourse(),
 * getInfoQuiz(), getQuestionsFromCourse(), getInfoQuestion() and getAnswers().
 *
 * Rows are the raw columns of c_quiz / c_quiz_question / c_quiz_answer (SELECT *), so the tests compare them with
 * the raw table row.
 */
final class Test2PdfTier3CharacterizationTest extends KernelTestCase
{
    private Connection $conn;
    private int $courseId = 1;
    private int $otherCourseId = 2;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        Container::setContainer($container);
        Container::setLegacyServices($container);
        $session = new HttpSession(new MockArraySessionStorage());
        Container::setSession($session);
        $request = Request::create('/plugin/Test2Pdf/src/view-pdf.php');
        $request->setSession($session);
        $request->setLocale('en_US');
        Container::setRequest($request);
        $this->conn = $container->get('doctrine')->getConnection();

        require_once api_get_path(SYS_PLUGIN_PATH).'Test2Pdf/src/test2pdf.lib.php';
    }

    public function testExerciseListIsTitleOrderedDistinctAndScopedToCourseAndLiveLinks(): void
    {
        $b = $this->quiz('t2p_B');
        $a = $this->quiz('t2p_A');
        $gone = $this->quiz('t2p_Gone');
        $elsewhere = $this->quiz('t2p_Elsewhere');
        $this->link($b, $this->courseId);
        $this->link($a, $this->courseId);
        $this->link($a, $this->courseId);
        $this->link($gone, $this->courseId, null, '2030-01-01 00:00:00');
        $this->link($elsewhere, $this->otherCourseId);

        $titles = array_column(showExerciseCourse($this->courseId), 'title');
        $mine = array_values(array_filter($titles, static fn ($t) => str_starts_with((string) $t, 't2p_')));

        $this->assertSame(['t2p_A', 't2p_B'], $mine, 'Title ASC, duplicated links collapse, deleted/other-course links drop');
        $this->assertSame(['t2p_Elsewhere'], array_values(array_filter(array_column(showExerciseCourse($this->otherCourseId), 'title'), static fn ($t) => str_starts_with((string) $t, 't2p_'))));
    }

    public function testSessionContextAddsSessionLinksToTheBaseCourseOnes(): void
    {
        $session = $this->session();
        $base = $this->quiz('t2p_Base');
        $inSession = $this->quiz('t2p_InSession');
        $this->link($base, $this->courseId);
        $this->link($inSession, $this->courseId, $session);

        $without = array_column(showExerciseCourse($this->courseId, 0), 'title');
        $with = array_column(showExerciseCourse($this->courseId, $session), 'title');

        $this->assertContains('t2p_Base', $without);
        $this->assertNotContains('t2p_InSession', $without, 'Without session only base-course resources');
        $this->assertContains('t2p_Base', $with);
        $this->assertContains('t2p_InSession', $with, 'In a session both the session and the base links');
    }

    public function testExerciseRowCarriesTheRawQuizColumns(): void
    {
        $id = $this->quiz('t2p_Cols', 'some description');
        $this->link($id, $this->courseId);
        $raw = $this->conn->fetchAssociative('SELECT * FROM c_quiz WHERE iid = ?', [$id]);

        $rows = array_values(array_filter(showExerciseCourse($this->courseId), static fn ($r) => (int) $r['iid'] === $id));

        $this->assertCount(1, $rows);
        $this->assertSameKeys($raw, $rows[0]);
        $this->assertEquals($raw, $rows[0]);
        $this->assertSame($raw['title'], $rows[0]['title']);
        $this->assertSame($raw['description'], $rows[0]['description']);
        $this->assertSame((string) $raw['iid'], (string) $rows[0]['iid']);
    }

    public function testInfoQuizIsScopedToCourseSessionAndLiveLinks(): void
    {
        $session = $this->session();
        $id = $this->quiz('t2p_Info', 'desc');
        $this->link($id, $this->courseId);
        $sessionOnly = $this->quiz('t2p_SessionOnly');
        $this->link($sessionOnly, $this->courseId, $session);
        $deleted = $this->quiz('t2p_Deleted');
        $this->link($deleted, $this->courseId, null, '2030-01-01 00:00:00');
        $raw = $this->conn->fetchAssociative('SELECT * FROM c_quiz WHERE iid = ?', [$id]);

        $info = getInfoQuiz($this->courseId, $id);

        $this->assertSameKeys($raw, $info);
        $this->assertEquals($raw, $info);
        $this->assertFalse(getInfoQuiz($this->otherCourseId, $id), 'Another course: no row');
        $this->assertFalse(getInfoQuiz($this->courseId, $sessionOnly), 'Session link without session context: no row');
        $this->assertSame('t2p_SessionOnly', getInfoQuiz($this->courseId, $sessionOnly, $session)['title']);
        $this->assertSame('t2p_Info', getInfoQuiz($this->courseId, $id, $session)['title'], 'Base link also visible in a session');
        $this->assertFalse(getInfoQuiz($this->courseId, $deleted));
        $this->assertFalse(getInfoQuiz($this->courseId, 0));
    }

    public function testQuestionIdsFollowTheQuizOrderAndKeepOnlyTheSupportedTypes(): void
    {
        $quiz = $this->quiz('t2p_Q');
        $q1 = $this->question('q one', 1);
        $q2 = $this->question('q two', 2);
        $q3 = $this->question('q three', 3);
        $unsupported = $this->question('q four', 4);
        $q14 = $this->question('q fourteen', 14);
        $other = $this->quiz('t2p_Other');
        $qOther = $this->question('q other', 1);
        $this->relate($quiz, $q3, 1);
        $this->relate($quiz, $q1, 3);
        $this->relate($quiz, $unsupported, 2);
        $this->relate($quiz, $q14, 3);
        $this->relate($quiz, $q2, 0);
        $this->relate($other, $qOther, 1);

        $ids = getQuestionsFromCourse($this->courseId, $quiz);

        // question_order ASC, then the link iid ASC for ties (q1 before q14).
        $this->assertSame([$q2, $q3, $q1, $q14], $ids);
        $this->assertSame([], getQuestionsFromCourse($this->courseId, 0));
    }

    public function testQuestionInfoOnlyForSupportedTypes(): void
    {
        $ok = $this->question('supported', 9, 'the description');
        $no = $this->question('unsupported', 5);
        $raw = $this->conn->fetchAssociative('SELECT * FROM c_quiz_question WHERE iid = ?', [$ok]);

        $info = getInfoQuestion($this->courseId, $ok);

        $this->assertSameKeys($raw, $info);
        $this->assertEquals($raw, $info);
        $this->assertSame('supported', $info['question']);
        $this->assertFalse(getInfoQuestion($this->courseId, $no));
        $this->assertFalse(getInfoQuestion($this->courseId, 0));
        foreach ([1, 2, 3, 9, 10, 11, 12, 14] as $type) {
            $this->assertIsArray(getInfoQuestion($this->courseId, $this->question('t'.$type, $type)), 'type '.$type);
        }
        foreach ([4, 5, 6, 7, 8, 13, 15] as $type) {
            $this->assertFalse(getInfoQuestion($this->courseId, $this->question('t'.$type, $type)), 'type '.$type);
        }
    }

    public function testAnswersOfAQuestionAreOrderedByPositionThenId(): void
    {
        $q = $this->question('with answers', 1);
        $otherQ = $this->question('other', 1);
        $c = $this->answer($q, 'third', 3);
        $a = $this->answer($q, 'first', 1);
        $b1 = $this->answer($q, 'second-a', 2);
        $b2 = $this->answer($q, 'second-b', 2);
        $this->answer($otherQ, 'foreign', 1);

        $answers = getAnswers($this->courseId, $q);

        $this->assertSame([$a, $b1, $b2, $c], array_map(static fn ($r) => (int) $r['iid'], $answers));
        $this->assertSame(['first', 'second-a', 'second-b', 'third'], array_column($answers, 'answer'));
        $raw = $this->conn->fetchAssociative('SELECT * FROM c_quiz_answer WHERE iid = ?', [$a]);
        $this->assertSameKeys($raw, $answers[0]);
        $this->assertEquals($raw, $answers[0]);
        $this->assertSame([], getAnswers($this->courseId, 0));
    }

    /**
     * Same column names (the order of the keys is not significant to any reader).
     */
    private function assertSameKeys(array $raw, array $row): void
    {
        $expected = array_keys($raw);
        $actual = array_keys($row);
        sort($expected);
        sort($actual);
        $this->assertSame($expected, $actual);
    }

    private function node(string $title): int
    {
        $this->conn->executeStatement(
            "INSERT INTO resource_node (resource_type_id, title, slug, level, path, created_at, updated_at, public, uuid)
             VALUES (1, ?, ?, 1, ?, '2030-01-02 03:04:05', '2030-01-02 03:04:05', 0, UNHEX(REPLACE(UUID(), '-', '')))",
            [$title, 't2p-'.uniqid(), '/t2p-'.uniqid()]
        );

        return (int) $this->conn->lastInsertId();
    }

    private function quiz(string $title, ?string $description = null): int
    {
        $this->conn->insert('c_quiz', [
            'resource_node_id' => $this->node($title),
            'title' => $title,
            'description' => $description,
            'type' => 1,
            'random' => 0,
            'random_answers' => 0,
            'results_disabled' => 0,
            'max_attempt' => 0,
            'feedback_type' => 0,
            'expired_time' => 0,
            'propagate_neg' => 0,
            'save_correct_answers' => 0,
            'review_answers' => 0,
            'random_by_category' => 0,
            'display_category_name' => 0,
            'pass_percentage' => 0,
            'prevent_backwards' => 0,
            'question_selection_type' => 0,
            'hide_question_number' => 0,
            'hide_question_title' => 0,
            'show_question_max_score' => 0,
            'show_previous_button' => 0,
            'autolaunch' => 0,
            'hide_attempts_table' => 0,
            'allow_question_feedback_reports' => 0,
            'display_chart_degree_certainty' => 0,
            'send_email_chart_degree_certainty' => 0,
            'not_display_balance_percentage_categorie_question' => 0,
            'display_chart_degree_certainty_category' => 0,
            'gather_questions_categories' => 0,
            'page_result_configuration' => '[]',
        ]);

        return (int) $this->conn->lastInsertId();
    }

    private function link(int $quizId, int $courseId, ?int $sessionId = null, ?string $deletedAt = null): void
    {
        $nodeId = (int) $this->conn->fetchOne('SELECT resource_node_id FROM c_quiz WHERE iid = ?', [$quizId]);
        $this->conn->insert('resource_link', [
            'resource_node_id' => $nodeId,
            'c_id' => $courseId,
            'session_id' => $sessionId,
            'visibility' => 1,
            'display_order' => 0,
            'resource_type_group' => 0,
            'deleted_at' => $deletedAt,
            'created_at' => '2030-01-02 03:04:05',
            'updated_at' => '2030-01-02 03:04:05',
        ]);
    }

    private function session(): int
    {
        $this->conn->insert('session', [
            'title' => 't2p_session_'.uniqid(),
            'nbr_courses' => 0,
            'nbr_users' => 0,
            'nbr_classes' => 0,
            'visibility' => 1,
            'status' => 0,
        ]);

        return (int) $this->conn->lastInsertId();
    }

    private function question(string $title, int $type, ?string $description = null): int
    {
        $this->conn->insert('c_quiz_question', [
            'question' => $title,
            'description' => $description,
            'ponderation' => 1.5,
            'position' => 0,
            'type' => $type,
            'level' => 0,
            'mandatory' => 0,
        ]);

        return (int) $this->conn->lastInsertId();
    }

    private function relate(int $quizId, int $questionId, int $order): void
    {
        $this->conn->insert('c_quiz_rel_question', ['quiz_id' => $quizId, 'question_id' => $questionId, 'question_order' => $order]);
    }

    private function answer(int $questionId, string $text, int $position): int
    {
        $this->conn->insert('c_quiz_answer', [
            'question_id' => $questionId,
            'answer' => $text,
            'correct' => 1,
            'ponderation' => 2.5,
            'position' => $position,
        ]);

        return (int) $this->conn->lastInsertId();
    }
}
