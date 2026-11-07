<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\Tests\ChamiloTestTrait;
use ChamiloSession;
use Doctrine\DBAL\Connection;
use Exercise;
use ReflectionMethod;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Throwable;

/**
 * Characterization tests for the raw-SQL methods of the legacy Exercise class
 * (public/main/exercise/exercise.class.php).
 *
 * They pin what the legacy queries return or write TODAY (rows, keys, ordering, NULL/session handling, DB side
 * effects) so that a rewrite to Doctrine/DQL that changes any of it fails here. Quirks are pinned as-is and
 * flagged with "PINNED QUIRK": do not "fix" the expectation without deciding the behaviour change is intended.
 *
 * Quizzes are inserted as bare c_quiz rows (no resource node: creating nodes deadlocks on the shared database),
 * which is why save()/delete() on a real resource are deliberately not exercised here.
 * Every fixture title starts with "ex_" (sibling test files run against the same database).
 */
final class ExerciseCharacterizationTest extends KernelTestCase
{
    use ChamiloTestTrait;

    private Connection $conn;
    private int $seq = 0;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        Container::setContainer($container);
        Container::setLegacyServices($container);
        $session = new Session(new MockArraySessionStorage());
        Container::setSession($session);
        $request = Request::create('/main/exercise/exercise.class.php');
        $request->setSession($session);
        Container::setRequest($request);

        $this->conn = $container->get('doctrine')->getConnection();
        $_SERVER['HTTP_USER_AGENT'] = 'ex_agent';
        $_SERVER['REQUEST_URI'] = '/ex_uri';
        unset($_REQUEST['learnpath_id'], $_REQUEST['learnpath_item_id'], $_REQUEST['learnpath_item_view_id']);
        ChamiloSession::erase('questionList');
        ChamiloSession::erase('oLP');
    }

    protected function tearDown(): void
    {
        ChamiloSession::erase('questionList');
        ChamiloSession::erase('oLP');
        unset($_REQUEST['learnpath_id'], $_REQUEST['learnpath_item_id'], $_REQUEST['learnpath_item_view_id']);
        parent::tearDown();
    }

    // ---------------------------------------------------------------- helpers

    private function setContext(User $user, Course $course, int $sessionId = 0): void
    {
        ChamiloSession::write('_user', ['id' => $user->getId()]);
        ChamiloSession::write('_real_cid', $course->getId());
        ChamiloSession::write('_cid', $course->getCode());
        ChamiloSession::write('_course', api_get_course_info($course->getCode()));
        ChamiloSession::write('sid', $sessionId);
        ChamiloSession::erase('login_as');
    }

    /** @return array{0: User, 1: Course} admin + fixture course 1, as current context */
    private function ctx(int $sessionId = 0): array
    {
        $user = $this->getUser('admin');
        $course = $this->getCourse(1);
        $this->setContext($user, $course, $sessionId);

        return [$user, $course];
    }

    private function makeQuiz(array $o = []): int
    {
        $this->conn->insert('c_quiz', $o + [
            'title' => 'ex_quiz_'.(++$this->seq),
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
        ]);

        return (int) $this->conn->lastInsertId();
    }

    private function makeQuestion(string $title, int $type = 1, float $weight = 1.0): int
    {
        $this->conn->insert('c_quiz_question', [
            'question' => $title,
            'ponderation' => $weight,
            'position' => 0,
            'type' => $type,
            'level' => 0,
            'mandatory' => 0,
        ]);

        return (int) $this->conn->lastInsertId();
    }

    private function link(int $quizId, int $questionId, int $order): void
    {
        $this->conn->insert('c_quiz_rel_question', [
            'quiz_id' => $quizId,
            'question_id' => $questionId,
            'question_order' => $order,
        ]);
    }

    private function read(int $quizId, bool $parse = true): Exercise
    {
        $exercise = new Exercise();
        $this->assertTrue($exercise->read($quizId, $parse));

        return $exercise;
    }

    /** @param int[] $orders titles ex_q_a, ex_q_b ... in the given question_order sequence */
    private function quizWithQuestions(array $orders, array $titles = []): array
    {
        $quizId = $this->makeQuiz();
        $ids = [];
        foreach ($orders as $i => $order) {
            $title = $titles[$i] ?? 'ex_q_'.chr(97 + $i);
            $qid = $this->makeQuestion($title);
            $this->link($quizId, $qid, $order);
            $ids[$i] = $qid;
        }

        return [$quizId, $ids];
    }

    private function seedExe(array $o): int
    {
        $this->conn->insert('track_e_exercises', $o + [
            'exe_user_id' => 0,
            'c_id' => 0,
            'session_id' => null,
            'exe_exo_id' => null,
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

    private function seedAttempt(int $exeId, int $userId, int $questionId, string $answer, int $position = 1): void
    {
        $this->conn->insert('track_e_attempt', [
            'exe_id' => $exeId,
            'user_id' => $userId,
            'question_id' => $questionId,
            'answer' => $answer,
            'teacher_comment' => '',
            'marks' => 1,
            'position' => $position,
            'tms' => '2020-01-01 10:00:00',
            'seconds_spent' => 0,
        ]);
    }

    private function countRows(string $table, string $where, array $params = []): int
    {
        return (int) $this->conn->fetchOne("SELECT COUNT(*) FROM $table WHERE $where", $params);
    }

    /** Inserts a learning path holding one quiz item, linked to the course. Returns [lpId, itemId]. */
    private function lpWithQuiz(int $quizId, int $courseId, ?string $deletedAt = null): array
    {
        $now = '2020-01-01 00:00:00';
        $this->conn->insert('c_lp', [
            'resource_node_id' => 1,
            'lp_type' => 1,
            'title' => 'ex_lp_'.(++$this->seq),
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
            'created_on' => $now,
            'modified_on' => $now,
        ]);
        $lpId = (int) $this->conn->lastInsertId();
        $this->conn->insert('c_lp_item', [
            'lp_id' => $lpId,
            'title' => 'ex_lp_item',
            'item_type' => 'quiz',
            'ref' => '',
            'path' => (string) $quizId,
            'min_score' => 0,
            'max_score' => 7,
            'display_order' => 1,
            'launch_data' => '',
            'lvl' => 0,
        ]);
        $itemId = (int) $this->conn->lastInsertId();
        $this->conn->insert('resource_link', [
            'resource_node_id' => 1,
            'c_id' => $courseId,
            'visibility' => 2,
            'display_order' => 0,
            'resource_type_group' => 1,
            'deleted_at' => $deletedAt,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return [$lpId, $itemId];
    }

    // ---------------------------------------------------------------- read()

    public function testReadReturnsFalseForZeroOrUnknownId(): void
    {
        $this->ctx();
        $exercise = new Exercise();

        $this->assertFalse($exercise->read(0));
        $this->assertFalse($exercise->read('abc'), 'non numeric ids are cast to 0');
        $this->assertFalse($exercise->read(2000000000));
        $this->assertSame(0, $exercise->getId(), 'a failed read leaves the object untouched');
    }

    public function testReadMapsRowColumnsAndNormalisesThem(): void
    {
        [, $course] = $this->ctx();
        $quizId = $this->makeQuiz([
            'title' => 'ex_read_full',
            'description' => 'ex description',
            'type' => 0,
            'random' => 3,
            'random_answers' => 1,
            'max_attempt' => 4,
            'feedback_type' => 2,
            'results_disabled' => 5,
            'pass_percentage' => 60,
            'review_answers' => 1,
            'hide_question_number' => 1,
            'show_previous_button' => 0,
            'notifications' => '1,3',
            'start_time' => '2030-01-02 03:04:05',
            'expired_time' => 90,
            'propagate_neg' => 1,
            'question_selection_type' => 2,
        ]);

        $e = $this->read($quizId);

        $this->assertSame($quizId, $e->getId());
        $this->assertSame('ex_read_full', $e->selectTitle());
        $this->assertSame('ex description', $e->selectDescription());
        // PINNED QUIRK: a stored type 0 is silently promoted to ONE_PER_PAGE (2) on read.
        $this->assertSame(ONE_PER_PAGE, $e->selectType());
        $this->assertSame(4, (int) $e->selectAttempts(), 'max_attempt is exposed as attempts');
        $this->assertSame(2, (int) $e->getFeedbackType());
        $this->assertSame(60, (int) $e->selectPassPercentage());
        $this->assertSame(['1', '3'], $e->getNotifications(), 'notifications column is exploded on commas');
        $this->assertTrue($e->review_answers);
        $this->assertTrue($e->hideQuestionNumber, 'hide_question_number becomes a boolean');
        $this->assertFalse($e->showPreviousButton, 'show_previous_button 0 is read as false (the getter ignores it while the platform setting is off)');
        $this->assertSame('2030-01-02 03:04:05', $e->start_time);
        $this->assertSame('', $e->end_time, 'a NULL end_time stays the constructor default');
        $this->assertSame(90, (int) $e->selectExpiredTime());
        $this->assertSame(2, (int) $e->getQuestionSelectionType());
        $this->assertSame((int) $course->getId(), (int) $e->course_id);
        $this->assertFalse($e->exercise_was_added_in_lp);
        $this->assertSame([], $e->lpList);
    }

    public function testReadParsesQuestionListOnlyWhenAsked(): void
    {
        $this->ctx();
        [$quizId, $q] = $this->quizWithQuestions([1, 2]);

        $parsed = $this->read($quizId, true);
        $this->assertSame([1 => $q[0], 2 => $q[1]], array_map('intval', $parsed->getQuestionList()));
        $this->assertSame(2, $parsed->selectNbrQuestions());

        $skipped = $this->read($quizId, false);
        $this->assertSame([], $skipped->getQuestionList());
        $this->assertSame(0, $skipped->selectNbrQuestions());
    }

    // ---------------------------------------------------------------- learning path usage

    public function testLpUsageIsReadWithItemRowAndLpTitle(): void
    {
        [, $course] = $this->ctx();
        $quizId = $this->makeQuiz();
        [$lpId, $itemId] = $this->lpWithQuiz($quizId, (int) $course->getId());

        $list = Exercise::getLpListFromExercise($quizId, (int) $course->getId());

        $this->assertCount(1, $list);
        $this->assertSame($lpId, (int) $list[0]['lp_id']);
        $this->assertSame($itemId, (int) $list[0]['item_id']);
        $this->assertSame(7.0, (float) $list[0]['max_score']);
        $this->assertStringStartsWith('ex_lp_', $list[0]['title']);

        $e = $this->read($quizId);
        $this->assertTrue($e->exercise_was_added_in_lp);
        $this->assertCount(1, $e->lpList);
        $this->assertTrue($e->added_in_lp(), 'added_in_lp() looks at c_lp_item only, not at resource links');
        $this->assertSame($lpId, (int) $e->getLpBySession(0)['lp_id']);
        $this->assertSame($lpId, (int) $e->getLpBySession(99)['lp_id'], 'unknown session falls back to the first lp');
    }

    public function testLpUsageIgnoresSoftDeletedLinksAndOtherCourses(): void
    {
        [, $course] = $this->ctx();
        $other = $this->getCourse(2);
        $quizId = $this->makeQuiz();
        $this->lpWithQuiz($quizId, (int) $course->getId(), '2020-02-02 00:00:00');

        $this->assertSame([], Exercise::getLpListFromExercise($quizId, (int) $course->getId()), 'deleted_at set');
        $this->assertSame([], Exercise::getLpListFromExercise($quizId, (int) $other->getId()), 'other course');

        $e = $this->read($quizId);
        $this->assertFalse($e->exercise_was_added_in_lp);
        // PINNED QUIRK: added_in_lp() ignores the resource link, so a soft-deleted LP still counts.
        $this->assertTrue($e->added_in_lp());
        $this->assertSame(
            ['lp_id' => 0, 'max_score' => 0, 'session_id' => 0],
            $e->getLpBySession(0),
            'without lp the default triple is returned'
        );
    }

    public function testAddedInLpIsFalseForQuizWithoutItem(): void
    {
        $this->ctx();
        $this->assertFalse($this->read($this->makeQuiz())->added_in_lp());
    }

    // ---------------------------------------------------------------- question lists

    public function testGetQuestionOrderedListIsKeyedByStoredOrderAndSortedByIt(): void
    {
        $this->ctx();
        [$quizId, $q] = $this->quizWithQuestions([7, 2, 5]);
        $e = $this->read($quizId, false);

        // Inserted as orders 7,2,5; result is sorted by question_order and keyed by it (gaps are kept).
        $this->assertSame([2 => $q[1], 5 => $q[2], 7 => $q[0]], array_map('intval', $e->getQuestionOrderedList()));
    }

    public function testGetQuestionOrderedListAdminViewRenumbersFromOne(): void
    {
        $this->ctx();
        [$quizId, $q] = $this->quizWithQuestions([7, 2, 5]);
        $e = $this->read($quizId, false);

        $this->assertSame([1 => $q[1], 2 => $q[2], 3 => $q[0]], array_map('intval', $e->getQuestionOrderedList(true)));
    }

    public function testGetQuestionOrderedListKeepsQuestionsSharingAnOrder(): void
    {
        $this->ctx();
        [$quizId, $q] = $this->quizWithQuestions([1, 1, 2]);
        $e = $this->read($quizId, false);

        // Fixed: questions sharing a question_order used to collapse into one array slot and one disappeared from
        // the normal view; now such a list falls back to the sequential order so no question is lost.
        $normal = array_map('intval', $e->getQuestionOrderedList());
        $this->assertCount(3, $normal);
        $this->assertEqualsCanonicalizing($q, array_values($normal));
        $this->assertSame($q[2], $normal[3], 'the question with the highest order stays last');
        $this->assertCount(3, $e->getQuestionOrderedList(true));
        $this->assertSame(3, $e->getQuestionCount());
    }

    public function testGetQuestionOrderedListKeepsStoredOrdersAsKeysWhenUnique(): void
    {
        $this->ctx();
        [$quizId, $q] = $this->quizWithQuestions([10, 20, 30]);
        $e = $this->read($quizId, false);

        $this->assertSame([10 => $q[0], 20 => $q[1], 30 => $q[2]], array_map('intval', $e->getQuestionOrderedList()));
    }

    public function testGetQuestionCountAndMembershipChecks(): void
    {
        $this->ctx();
        [$quizId, $q] = $this->quizWithQuestions([1, 2, 3]);
        [$otherQuiz, $otherQ] = $this->quizWithQuestions([1]);
        $e = $this->read($quizId, false);
        $empty = $this->read($this->makeQuiz(), false);

        $this->assertSame(3, $e->getQuestionCount());
        $this->assertSame(0, $empty->getQuestionCount());
        $this->assertSame(0, (new Exercise())->getQuestionCount(), 'unsaved exercise (id 0) counts nothing');

        $this->assertTrue($e->hasQuestion($q[1]));
        $this->assertFalse($e->hasQuestion($otherQ[0]), 'a question of another quiz is not ours');
        $this->assertFalse($e->hasQuestion(0));
    }

    public function testHasQuestionWithTypeAndNotInList(): void
    {
        $this->ctx();
        $quizId = $this->makeQuiz();
        $this->link($quizId, $this->makeQuestion('ex_tf', UNIQUE_ANSWER), 1);
        $this->link($quizId, $this->makeQuestion('ex_free', FREE_ANSWER), 2);
        $e = $this->read($quizId, false);

        $this->assertTrue($e->hasQuestionWithType(UNIQUE_ANSWER));
        $this->assertTrue($e->hasQuestionWithType((string) FREE_ANSWER), 'cast to int');
        $this->assertFalse($e->hasQuestionWithType(MULTIPLE_ANSWER));

        $this->assertTrue($e->hasQuestionWithTypeNotInList([UNIQUE_ANSWER]), 'FREE_ANSWER is not in the list');
        $this->assertFalse($e->hasQuestionWithTypeNotInList([UNIQUE_ANSWER, FREE_ANSWER]));
        // PINNED QUIRK: an empty list short-circuits to false, whereas "NOT IN ()" would match everything.
        $this->assertFalse($e->hasQuestionWithTypeNotInList([]));
    }

    public function testGetQuestionOrderedListByNameSortsByTitleNotOrder(): void
    {
        $this->ctx();
        [$quizId, $q] = $this->quizWithQuestions([1, 2, 3], ['ex_q_charlie', 'ex_q_alpha', 'ex_q_bravo']);
        $e = $this->read($quizId, false);

        $list = $e->getQuestionOrderedListByName();

        $this->assertSame(
            [['question_id' => (string) $q[1]], ['question_id' => (string) $q[2]], ['question_id' => (string) $q[0]]],
            array_map(static fn ($r) => ['question_id' => (string) $r['question_id']], $list)
        );
        $this->assertSame([], (new Exercise())->getQuestionOrderedListByName(), 'unsaved exercise gives []');
    }

    public function testGetRandomListAdminViewAndLimits(): void
    {
        $this->ctx();
        [$quizId, $q] = $this->quizWithQuestions([3, 1, 2, 4]);
        $e = $this->read($quizId, false);

        $e->random = 2;
        $this->assertCount(2, $e->getRandomList(), 'LIMIT = number of random questions');
        $this->assertSame([], array_diff($e->getRandomList(), $q), 'only questions of this quiz');

        $e->random = -1;
        $all = $e->getRandomList();
        sort($all);
        $expected = $q;
        sort($expected);
        $this->assertEquals($expected, $all, '-1 means no limit');

        $e->random = 2;
        // The admin view is strictly "=== true": ordered by question_order and not limited by $random.
        $this->assertEquals([$q[1], $q[2], $q[0], $q[3]], $e->getRandomList(true));
        $this->assertCount(2, $e->getRandomList(1), 'a truthy non-true value is not the admin view');
    }

    public function testSelectQuestionListHonoursSelectionType(): void
    {
        $this->ctx();
        [$quizId, $q] = $this->quizWithQuestions([2, 1, 3]);
        $e = $this->read($quizId, false);

        // From memory (default) nothing is read from the DB.
        $this->assertSame([], $e->selectQuestionList());

        $ordered = array_map('intval', $e->selectQuestionList(true));
        $this->assertSame([1 => $q[1], 2 => $q[0], 3 => $q[2]], $ordered);

        // Random selection but random = 0: still ordered.
        $e->setQuestionSelectionType(EX_Q_SELECTION_RANDOM);
        $e->random = 0;
        $this->assertSame($ordered, array_map('intval', $e->selectQuestionList(true)));

        // Random selection with random = 2 gives 2 questions of the quiz.
        $e->random = 2;
        $picked = $e->selectQuestionList(true);
        $this->assertCount(2, $picked);
        $this->assertSame([], array_diff($picked, $q));

        // Random with 1 question only is not randomised even when random is set.
        $single = $this->quizWithQuestions([1])[0];
        $one = $this->read($single, false);
        $one->setQuestionSelectionType(EX_Q_SELECTION_RANDOM);
        $one->random = 5;
        $this->assertCount(1, $one->selectQuestionList(true));
    }

    public function testGetQuestionForTeacherPaginatesInStoredOrder(): void
    {
        $this->ctx();
        [$quizId, $q] = $this->quizWithQuestions([5, 4, 3, 2, 1]);
        $e = $this->read($quizId, false);

        $this->assertSame([$q[4], $q[3]], array_map('intval', $e->getQuestionForTeacher(0, 2)));
        $this->assertSame([$q[2], $q[1]], array_map('intval', $e->getQuestionForTeacher(2, 2)));
        $this->assertSame([$q[0]], array_map('intval', $e->getQuestionForTeacher(4, 2)));
        $this->assertSame([], $e->getQuestionForTeacher(5, 2));
        $this->assertSame([$q[4], $q[3]], array_map('intval', $e->getQuestionForTeacher(-9, 2)), 'negative start clamps to 0');
    }

    public function testUpdateQuestionPositionsKeepsStoredOrdersAsTheyAre(): void
    {
        $this->ctx();
        [$quizId, $q] = $this->quizWithQuestions([10, 20, 30]);
        $e = $this->read($quizId, false);

        $this->assertTrue($e->update_question_positions());

        // PINNED QUIRK: despite its name it does not renumber. The list is keyed by the stored question_order,
        // and that very key is written back, so gapped orders (10, 20, 30) stay untouched.
        $orders = $this->conn->fetchAllKeyValue(
            'SELECT question_id, question_order FROM c_quiz_rel_question WHERE quiz_id = ?',
            [$quizId]
        );
        $this->assertEquals([$q[0] => 10, $q[1] => 20, $q[2] => 30], $orders);
        $this->assertFalse((new Exercise())->update_question_positions(), 'no id gives false');
    }

    public function testUpdateQuestionPositionsDoesNotRepairDuplicatedOrders(): void
    {
        $this->ctx();
        [$quizId, $q] = $this->quizWithQuestions([1, 1]);
        $e = $this->read($quizId, false);

        $e->update_question_positions();

        // The duplicates are NOT repaired: the rows are rewritten from the collapsed list and, because
        // writes are keyed by question id, both rows still say 1.
        $this->assertSame(
            2,
            $this->countRows('c_quiz_rel_question', 'quiz_id = ? AND question_order = 1', [$quizId])
        );
    }

    public function testGetQuestionListPaginationDefaultsToStoredOrderWithLimits(): void
    {
        $this->ctx();
        [$quizId, $q] = $this->quizWithQuestions([3, 1, 2], ['ex_q_c', 'ex_q_a', 'ex_q_b']);
        $e = $this->read($quizId, false);

        $rows = $e->getQuestionListPagination(0, 2, '', '');

        $this->assertSame([$q[1], $q[2]], array_map(static fn ($r) => (int) $r['id'], $rows));
        $this->assertSame(['ex_q_a', 'ex_q_b'], array_column($rows, 'question'));
        $this->assertSame(1.0, (float) $rows[0]['score']);

        $second = $e->getQuestionListPagination(2, 5, '', '');
        $this->assertSame([$q[0]], array_map(static fn ($r) => (int) $r['id'], $second));
        $this->assertNull((new Exercise())->getQuestionListPagination(0, 5, '', ''), 'unsaved exercise returns null');
    }

    public function testGetQuestionListPaginationSortsByQuestionTitle(): void
    {
        $this->ctx();
        // question_order is the reverse of the alphabetical order, so a sort on the title is observable.
        [$quizId] = $this->quizWithQuestions([2, 1], ['ex_q_alpha', 'ex_q_beta']);
        $e = $this->read($quizId, false);
        $titles = static fn (array $rows): array => array_column($rows, 'question');

        // Fixed: ORDER BY `q.question` (one bogus identifier) became q.question.
        $this->assertSame(['ex_q_alpha', 'ex_q_beta'], $titles($e->getQuestionListPagination(0, 5, 'question', 'asc')));
        $this->assertSame(['ex_q_beta', 'ex_q_alpha'], $titles($e->getQuestionListPagination(0, 5, 'question', 'DESC')));
        // Any other sort field, or an invalid direction, keeps the question_order sequence (beta=1, alpha=2).
        $this->assertSame(['ex_q_beta', 'ex_q_alpha'], $titles($e->getQuestionListPagination(0, 5, 'other', 'asc')));
        $this->assertSame(['ex_q_beta', 'ex_q_alpha'], $titles($e->getQuestionListPagination(0, 5, 'question', 'asc; DROP')));
    }

    public function testCountQuestionsInExerciseUsesTrackedAttemptElseDbCount(): void
    {
        [$user, $course] = $this->ctx();
        [$quizId] = $this->quizWithQuestions([1, 2, 3]);
        $e = $this->read($quizId, false);
        $m = new ReflectionMethod(Exercise::class, 'countQuestionsInExercise');
        $m->setAccessible(true);

        $this->assertSame(3, $m->invoke($e), 'no incomplete attempt: number of questions in the quiz');

        $this->seedExe([
            'exe_user_id' => $user->getId(),
            'c_id' => $course->getId(),
            'exe_exo_id' => $quizId,
            'status' => 'incomplete',
            'data_tracking' => '11,12',
        ]);
        $this->assertSame(2, $m->invoke($e), 'an incomplete attempt wins: the length of its data_tracking');
    }

    // ---------------------------------------------------------------- categories in exercise

    public function testSaveCategoriesInExerciseReplacesWholeSetAndSkipsCategoryZero(): void
    {
        $this->ctx();
        $quizId = $this->makeQuiz();
        $catA = $this->makeCategory('ex_cat_a');
        $catB = $this->makeCategory('ex_cat_b');
        $e = $this->read($quizId, false);

        $e->saveCategoriesInExercise([$catA => 2, $catB => -1, 0 => 9]);
        $stored = $e->getCategoriesInExercise();

        $this->assertSame([$catA, $catB], array_keys($stored), 'keyed by category id; id 0 is never stored');
        $this->assertSame(2, (int) $stored[$catA]['count_questions']);
        $this->assertSame(-1, (int) $stored[$catB]['count_questions']);
        $this->assertSame($quizId, (int) $stored[$catA]['exercise_id']);
        $this->assertSame(1, $e->getNumberQuestionExerciseCategory(), 'SUM(2, -1): -1 ("all") is summed as is');

        // Saving a new set deletes the previous one.
        $e->saveCategoriesInExercise([$catB => 5]);
        $this->assertSame([$catB], array_keys($e->getCategoriesInExercise()));
        $this->assertSame(5, $e->getNumberQuestionExerciseCategory());

        // PINNED QUIRK: an empty set is a no-op, it does NOT clear the existing categories.
        $e->saveCategoriesInExercise([]);
        $this->assertSame([$catB], array_keys($e->getCategoriesInExercise()));
    }

    public function testCategoriesOfUnsavedOrEmptyExerciseAreEmpty(): void
    {
        $this->ctx();
        $new = new Exercise();
        $empty = $this->read($this->makeQuiz(), false);

        $this->assertSame([], $new->getCategoriesInExercise());
        $this->assertSame(0, $new->getNumberQuestionExerciseCategory());
        $this->assertSame([], $empty->getCategoriesInExercise());
        // SUM() over no rows is NULL, cast to int by the caller.
        $this->assertSame(0, $empty->getNumberQuestionExerciseCategory());
    }

    private function makeCategory(string $title): int
    {
        $this->conn->insert('c_quiz_question_category', ['title' => $title]);

        return (int) $this->conn->lastInsertId();
    }

    // ---------------------------------------------------------------- attempts (track_e_exercises)

    public function testGetStatTrackExerciseInfoFiltersByUserCourseStatusLpAndSession(): void
    {
        [$user, $course] = $this->ctx();
        $quizId = $this->makeQuiz();
        $e = $this->read($quizId, false);
        $base = ['exe_user_id' => $user->getId(), 'c_id' => $course->getId(), 'exe_exo_id' => $quizId];

        $this->assertSame([], $e->get_stat_track_exercise_info(), 'no attempt yet');

        $this->seedExe($base + ['status' => 'completed']);
        $this->seedExe($base + ['status' => 'incomplete', 'orig_lp_id' => 5, 'orig_lp_item_id' => 6, 'orig_lp_item_view_id' => 7]);
        $this->assertSame([], $e->get_stat_track_exercise_info(), 'default asks for status incomplete outside any lp');

        $own = $this->seedExe($base + ['status' => 'incomplete', 'data_tracking' => '1,2']);
        $info = $e->get_stat_track_exercise_info();
        $this->assertSame($own, (int) $info['exe_id']);
        $this->assertSame(1, $info['num_exe'], 'num_exe is the row count of a LIMIT 1 query: always 1');
        $this->assertSame('1,2', $info['data_tracking']);

        $lp = $e->get_stat_track_exercise_info(5, 6, 7);
        $this->assertSame('incomplete', $lp['status']);
        $this->assertSame(5, (int) $lp['orig_lp_id']);
        $this->assertSame('completed', $e->get_stat_track_exercise_info(0, 0, 0, 'completed')['status']);

        // A different user or course does not see our attempts.
        $this->seedExe(['exe_user_id' => $this->getUser('anon')->getId(), 'c_id' => $course->getId(), 'exe_exo_id' => $quizId, 'status' => 'abandoned']);
        $this->assertSame([], $e->get_stat_track_exercise_info(0, 0, 0, 'abandoned'));
    }

    public function testGetStatTrackExerciseInfoScopesBySession(): void
    {
        [$user, $course] = $this->ctx();
        $quizId = $this->makeQuiz();
        $sid = (int) $this->createSession('ex_session_a')->getId();
        $base = ['exe_user_id' => $user->getId(), 'c_id' => $course->getId(), 'exe_exo_id' => $quizId, 'status' => 'incomplete'];
        $inSession = $this->seedExe($base + ['session_id' => $sid]);
        $baseNull = $this->seedExe($base + ['session_id' => null]);

        // Base course: session_id NULL (or 0) only.
        $e = $this->read($quizId, false);
        $this->assertSame($baseNull, (int) $e->get_stat_track_exercise_info()['exe_id'], 'NULL session counts as base course');

        // In a session: strictly that session, no fallback to the base course.
        $this->setContext($user, $course, $sid);
        $inside = new Exercise();
        $inside->read($quizId, false);
        $this->assertSame($inSession, (int) $inside->get_stat_track_exercise_info()['exe_id']);
    }

    public function testGetStatTrackExerciseInfoByExeIdAddsFormattedDurationOnlyWhenDurationSet(): void
    {
        [$user, $course] = $this->ctx();
        $quizId = $this->makeQuiz();
        $e = $this->read($quizId, false);
        $base = ['exe_user_id' => $user->getId(), 'c_id' => $course->getId(), 'exe_exo_id' => $quizId];
        $with = $this->seedExe($base + ['exe_duration' => 3725, 'score' => 2.5, 'max_score' => 5]);
        $without = $this->seedExe($base + ['exe_duration' => 0]);

        $this->assertSame([], $e->get_stat_track_exercise_info_by_exe_id(0));
        $this->assertSame([], $e->get_stat_track_exercise_info_by_exe_id(2000000000));

        $row = $e->get_stat_track_exercise_info_by_exe_id($with);
        $this->assertSame($with, (int) $row['exe_id']);
        $this->assertSame(2.5, (float) $row['score']);
        $this->assertNotSame('', $row['duration_formatted']);
        $this->assertSame('', $e->get_stat_track_exercise_info_by_exe_id($without)['duration_formatted']);
    }

    public function testRemindListStorageAddRemoveAndClear(): void
    {
        [$user, $course] = $this->ctx();
        $quizId = $this->makeQuiz();
        $e = $this->read($quizId, false);
        $exe = $this->seedExe(['exe_user_id' => $user->getId(), 'c_id' => $course->getId(), 'exe_exo_id' => $quizId]);
        $stored = fn (): string => (string) $this->conn->fetchOne('SELECT questions_to_check FROM track_e_exercises WHERE exe_id = ?', [$exe]);

        $this->assertFalse($e->addAllQuestionToRemind($exe, []));
        $this->assertSame('', $stored());
        $this->assertTrue($e->addAllQuestionToRemind($exe, [4, 5, 6]));
        $this->assertSame('4,5,6', $stored());

        $e->editQuestionToRemind($exe, 5, 'delete');
        $this->assertSame('4,6', $stored());
        $e->editQuestionToRemind($exe, 9, 'delete');
        $this->assertSame('', $stored(), 'PINNED QUIRK: deleting an id that is not in the list empties the whole list');

        $this->assertFalse($e->removeAllQuestionToRemind(0));
        $e->addAllQuestionToRemind($exe, [1]);
        $this->assertTrue($e->removeAllQuestionToRemind($exe));
        $this->assertSame('', $stored());
    }

    public function testEditQuestionToRemindAddFirstThenFollowsSessionQuestionOrder(): void
    {
        [$user, $course] = $this->ctx();
        $quizId = $this->makeQuiz();
        $e = $this->read($quizId, false);
        $exe = $this->seedExe(['exe_user_id' => $user->getId(), 'c_id' => $course->getId(), 'exe_exo_id' => $quizId]);
        $stored = fn (): string => (string) $this->conn->fetchOne('SELECT questions_to_check FROM track_e_exercises WHERE exe_id = ?', [$exe]);

        $e->editQuestionToRemind($exe, 8);
        $this->assertSame('8', $stored(), 'first reminder is stored as is');
        // PINNED QUIRK: on a non-empty list any action other than add/delete empties the list.
        $e->editQuestionToRemind($exe, 7, 'noop');
        $this->assertSame('', $stored());
        $e->editQuestionToRemind($exe, 8);
        $this->assertSame('8', $stored());

        // Adding to a non-empty list rebuilds it from the "questionList" kept in the session.
        ChamiloSession::write('questionList', [3, 8, 1]);
        $e->editQuestionToRemind($exe, 3);
        $this->assertSame('3,8', $stored(), 'ordered like the session list, not like insertion');

        // PINNED QUIRK: with no questionList in the session an add to a non-empty list wipes it.
        ChamiloSession::erase('questionList');
        $e->editQuestionToRemind($exe, 1);
        $this->assertSame('', $stored());
    }

    // ---------------------------------------------------------------- cleanResults

    private function seedCleanFixture(User $user, Course $course, int $quizId): array
    {
        $base = ['exe_user_id' => $user->getId(), 'c_id' => $course->getId(), 'exe_exo_id' => $quizId];
        $q = $this->makeQuestion('ex_clean_q');
        $ids = [
            'plain_old' => $this->seedExe($base + ['exe_date' => '2020-01-01 00:00:00']),
            'plain_new' => $this->seedExe($base + ['exe_date' => '2021-06-01 00:00:00']),
            'lp' => $this->seedExe($base + ['exe_date' => '2020-01-01 00:00:00', 'orig_lp_id' => 3, 'orig_lp_item_id' => 4]),
        ];
        foreach ($ids as $exe) {
            $this->seedAttempt($exe, $user->getId(), $q, 'a');
        }

        return $ids;
    }

    public function testCleanResultsDeletesNonLpAttemptsAndTheirAnswersByDefault(): void
    {
        [$user, $course] = $this->ctx();
        $quizId = $this->makeQuiz();
        $ids = $this->seedCleanFixture($user, $course, $quizId);
        $otherQuiz = $this->makeQuiz();
        $foreign = $this->seedExe(['exe_user_id' => $user->getId(), 'c_id' => $course->getId(), 'exe_exo_id' => $otherQuiz]);
        $e = $this->read($quizId, false);

        $deleted = $e->cleanResults();

        $this->assertSame(2, $deleted, 'returns the number of attempts found');
        $this->assertSame(0, $this->countRows('track_e_exercises', 'exe_id IN (?, ?)', [$ids['plain_old'], $ids['plain_new']]));
        $this->assertSame(0, $this->countRows('track_e_attempt', 'exe_id IN (?, ?)', [$ids['plain_old'], $ids['plain_new']]));
        $this->assertSame(1, $this->countRows('track_e_exercises', 'exe_id = ?', [$ids['lp']]), 'lp attempts are kept');
        $this->assertSame(1, $this->countRows('track_e_attempt', 'exe_id = ?', [$ids['lp']]));
        $this->assertSame(1, $this->countRows('track_e_exercises', 'exe_id = ?', [$foreign]), 'other quizzes untouched');
        $this->assertSame(
            1,
            $this->countRows('track_e_default', "default_event_type = ? AND default_value_type = ? AND default_value = ? AND c_id = ?", [LOG_EXERCISE_RESULT_DELETE, LOG_EXERCISE_ID, (string) $quizId, $course->getId()]),
            'the deletion is logged once'
        );
    }

    public function testCleanResultsCanIncludeLpAttemptsAndFilterByDate(): void
    {
        [$user, $course] = $this->ctx();
        $quizId = $this->makeQuiz();
        $ids = $this->seedCleanFixture($user, $course, $quizId);
        $e = $this->read($quizId, false);

        // Before-date is inclusive (exe_date <= date) and combined with the lp exclusion.
        $this->assertSame(1, $e->cleanResults(false, '2020-01-01 00:00:00'));
        $this->assertSame(0, $this->countRows('track_e_exercises', 'exe_id = ?', [$ids['plain_old']]));
        $this->assertSame(1, $this->countRows('track_e_exercises', 'exe_id = ?', [$ids['plain_new']]));
        $this->assertSame(1, $this->countRows('track_e_exercises', 'exe_id = ?', [$ids['lp']]));

        // Including lp tests removes what is left of the quiz.
        $this->assertSame(2, $e->cleanResults(true));
        $this->assertSame(0, $this->countRows('track_e_exercises', 'exe_exo_id = ?', [$quizId]));
        $this->assertSame(0, $this->countRows('track_e_attempt', 'exe_id IN (?, ?)', [$ids['plain_new'], $ids['lp']]));
    }

    public function testCleanResultsRejectsInvalidDateWithoutTouchingAnything(): void
    {
        [$user, $course] = $this->ctx();
        $quizId = $this->makeQuiz();
        $this->seedCleanFixture($user, $course, $quizId);
        $e = $this->read($quizId, false);

        $this->assertSame(0, $e->cleanResults(false, 'not-a-date'));
        $this->assertSame(3, $this->countRows('track_e_exercises', 'exe_exo_id = ?', [$quizId]));
    }

    public function testCleanResultsTreatsNullSessionAsBaseCourseAndSkipsSessionAttempts(): void
    {
        [$user, $course] = $this->ctx();
        $quizId = $this->makeQuiz();
        $sid = (int) $this->createSession('ex_session_clean')->getId();
        $base = ['exe_user_id' => $user->getId(), 'c_id' => $course->getId(), 'exe_exo_id' => $quizId];
        $nullSession = $this->seedExe($base + ['session_id' => null]);
        $zeroSession = $this->seedExe($base + ['session_id' => null]);
        $inSession = $this->seedExe($base + ['session_id' => $sid]);
        $e = $this->read($quizId, false);

        $this->assertSame(2, $e->cleanResults());
        $this->assertSame(0, $this->countRows('track_e_exercises', 'exe_id IN (?, ?)', [$nullSession, $zeroSession]));
        $this->assertSame(1, $this->countRows('track_e_exercises', 'exe_id = ?', [$inSession]));
    }

    // ---------------------------------------------------------------- result listings

    public function testGetExerciseAndResultJoinsQuizOrdersByQuizAndScopesBySession(): void
    {
        [$user, $course] = $this->ctx();
        $quizA = $this->makeQuiz(['title' => 'ex_join_a']);
        $quizB = $this->makeQuiz(['title' => 'ex_join_b']);
        $sid = (int) $this->createSession('ex_session_join')->getId();
        $base = ['exe_user_id' => $user->getId(), 'c_id' => $course->getId()];
        $this->seedExe($base + ['exe_exo_id' => $quizB, 'score' => 2]);
        $this->seedExe($base + ['exe_exo_id' => $quizA, 'score' => 1]);
        $this->seedExe($base + ['exe_exo_id' => $quizA, 'score' => 3, 'session_id' => $sid]);
        $e = new Exercise();

        $this->assertSame([], $e->getExerciseAndResult($course->getId(), 0, []));
        $this->assertSame([], $e->getExerciseAndResult($course->getId(), 0, 0), 'empty id is no filter but no result');

        $rows = $e->getExerciseAndResult($course->getId(), 0, [$quizB, $quizA]);
        $this->assertSame([$quizA, $quizA, $quizB], array_map(static fn ($r) => (int) $r['iid'], $rows), 'ordered by quiz iid; session 0 means every session');
        $this->assertSame('ex_join_a', $rows[0]['title'], 'quiz columns are joined in');
        $this->assertArrayHasKey('score', $rows[0]);

        $inSession = $e->getExerciseAndResult($course->getId(), $sid, [$quizA, $quizB]);
        $this->assertCount(1, $inSession);
        $this->assertSame(3.0, (float) $inSession[0]['score']);

        $this->assertSame([], $e->getExerciseAndResult($this->getCourse(2)->getId(), 0, [$quizA]), 'other course');
        $this->assertCount(2, $e->getExerciseAndResult($course->getId(), 0, (string) $quizA), 'scalar id is wrapped');
    }

    public function testGetExercisesByCourseSessionListsQuizzesLinkedToCourseAndSession(): void
    {
        $this->ctx();
        $e = new Exercise();
        $unusedNodes = $this->conn->fetchFirstColumn(
            'SELECT id FROM resource_node WHERE id NOT IN (SELECT resource_node_id FROM c_quiz WHERE resource_node_id IS NOT NULL) ORDER BY id DESC LIMIT 5'
        );
        $this->assertGreaterThanOrEqual(5, \count($unusedNodes), 'fixture needs 5 spare resource nodes');
        $now = '2020-01-01 00:00:00';
        $sessionIds = [];
        foreach (['ex_gecs_session_a', 'ex_gecs_session_b'] as $title) {
            $this->conn->insert('session', [
                'title' => $title,
                'nbr_courses' => 0,
                'nbr_users' => 0,
                'nbr_classes' => 0,
                'visibility' => 1,
                'status' => 0,
            ]);
            $sessionIds[] = (int) $this->conn->lastInsertId();
        }
        [$sidA, $sidB] = $sessionIds;
        $mk = function (string $title, int $node, int $courseId, ?int $sessionId, ?string $deletedAt = null) use ($now): int {
            $id = $this->makeQuiz(['title' => $title, 'resource_node_id' => $node]);
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

            return $id;
        };
        $base = $mk('ex_gecs_base', (int) $unusedNodes[0], 1, null);
        $inSession = $mk('ex_gecs_s1', (int) $unusedNodes[1], 1, $sidA);
        $otherSession = $mk('ex_gecs_s2', (int) $unusedNodes[2], 1, $sidB);
        $otherCourse = $mk('ex_gecs_c2', (int) $unusedNodes[3], 2, null);
        $deleted = $mk('ex_gecs_del', (int) $unusedNodes[4], 1, null, $now);

        $ids = static fn (array $rows): array => array_map('intval', array_column($rows, 'id'));

        // Fixed: quizzes are scoped through resource_link, not the dropped c_quiz.c_id/session_id columns.
        $base0 = $ids($e->getExercisesByCourseSession(1, 0));
        $this->assertContains($base, $base0, 'base-course quiz');
        $this->assertNotContains($inSession, $base0, 'session quiz is not in the base course view');
        $this->assertNotContains($otherCourse, $base0);
        $this->assertNotContains($deleted, $base0, 'soft-deleted link is excluded');

        $rows = $e->getExercisesByCourseSession(1, $sidA);
        $s1 = $ids($rows);
        $this->assertContains($base, $s1, 'base quizzes are also offered inside a session (legacy session_id = 0 branch)');
        $this->assertContains($inSession, $s1);
        $this->assertNotContains($otherSession, $s1);
        $this->assertSame(array_values(array_unique($s1)), $s1, 'one row per quiz');
        $this->assertSame('ex_gecs_base', $rows[array_search($base, $s1, true)]['title']);
        $sorted = $s1;
        sort($sorted);
        $this->assertSame($sorted, $s1, 'ordered by iid');
    }

    // ---------------------------------------------------------------- answers

    public function testGetUserAnswersSavedInExerciseCountsQuestionsWithAnAnswer(): void
    {
        [$user, $course] = $this->ctx();
        $quizId = $this->makeQuiz();
        $q1 = $this->makeQuestion('ex_ans_1', UNIQUE_ANSWER);
        $q2 = $this->makeQuestion('ex_ans_2', UNIQUE_ANSWER);
        $q3 = $this->makeQuestion('ex_ans_3', FILL_IN_BLANKS);
        $q4 = $this->makeQuestion('ex_ans_4', FREE_ANSWER);
        $exe = $this->seedExe(['exe_user_id' => $user->getId(), 'c_id' => $course->getId(), 'exe_exo_id' => $quizId]);
        $this->seedAttempt($exe, $user->getId(), $q1, '12', 1);
        $this->seedAttempt($exe, $user->getId(), $q2, '', 2);
        $this->seedAttempt($exe, $user->getId(), $q3, '0', 3);
        $this->seedAttempt($exe, $user->getId(), $q4, '0', 4);
        $e = $this->read($quizId, false);

        $saved = array_map('intval', $e->getUserAnswersSavedInExercise($exe));

        // q2 is empty. PINNED QUIRK: an answer "0" is empty() in PHP so q4 (free answer) does not count, and the
        // fill-in-blanks guard meant to protect "0" is dead code (the blanks are parsed to '' first), so q3 does not
        // count either: only a non-"0" answer counts as answered.
        $this->assertSame([$q1], $saved);
        $this->assertSame(1, $e->countUserAnswersSavedInExercise($exe));
        $this->assertSame([], $e->getUserAnswersSavedInExercise(2000000000));
        $this->assertSame(0, $e->countUserAnswersSavedInExercise(2000000000));
    }

    public function testGetMaxScoreSumsQuestionWeightsOfOrderedList(): void
    {
        $this->ctx();
        $quizId = $this->makeQuiz();
        $this->link($quizId, $this->makeQuestion('ex_w1', UNIQUE_ANSWER, 2.5), 1);
        $this->link($quizId, $this->makeQuestion('ex_w2', UNIQUE_ANSWER, 4.0), 2);
        $other = $this->makeQuiz();
        $this->link($other, $this->makeQuestion('ex_w3', UNIQUE_ANSWER, 100.0), 1);

        $this->assertEquals(6.5, $this->read($quizId)->getMaxScore());
        $this->assertEquals(0, $this->read($this->makeQuiz())->getMaxScore(), 'no question, no score');
    }

    // ---------------------------------------------------------------- learning path tracking

    public function testSaveExerciseInLpCopiesMaxScoreAndSetsItemViewStatusFromPassMark(): void
    {
        [$user, $course] = $this->ctx();
        $quizId = $this->makeQuiz(['pass_percentage' => 50]);
        [$lpId, $itemId] = $this->lpWithQuiz($quizId, (int) $course->getId());
        $this->conn->insert('c_lp_view', ['user_id' => $user->getId(), 'lp_id' => $lpId, 'c_id' => $course->getId(), 'view_count' => 1, 'last_item' => 0]);
        $viewId = (int) $this->conn->lastInsertId();
        $itemView = function (float $score) use ($itemId, $viewId): int {
            $this->conn->insert('c_lp_item_view', ['lp_item_id' => $itemId, 'lp_view_id' => $viewId, 'view_count' => 1, 'start_time' => 0, 'total_time' => 0, 'score' => $score]);

            return (int) $this->conn->lastInsertId();
        };
        $old = $itemView(0);
        $latest = $itemView(0);
        $exe = $this->seedExe([
            'exe_user_id' => $user->getId(), 'c_id' => $course->getId(), 'exe_exo_id' => $quizId,
            'score' => 8, 'max_score' => 10, 'exe_duration' => 77,
        ]);
        $lp = new class($viewId) {
            public function __construct(private int $viewId) {}

            public function get_view_id(): int
            {
                return $this->viewId;
            }
        };

        // No lp object in session, or empty ids: nothing happens.
        $this->assertFalse(Exercise::saveExerciseInLp($itemId, $exe));
        ChamiloSession::write('oLP', $lp);
        $this->assertFalse(Exercise::saveExerciseInLp(0, $exe));
        $this->assertFalse(Exercise::saveExerciseInLp($itemId, 0));
        $this->assertFalse(Exercise::saveExerciseInLp($itemId, 2000000000), 'unknown (or foreign) attempt');

        Exercise::saveExerciseInLp($itemId, $exe, (int) $course->getId());

        $this->assertEquals(10.0, (float) $this->conn->fetchOne('SELECT max_score FROM c_lp_item WHERE iid = ?', [$itemId]));
        $latestRow = $this->conn->fetchAssociative('SELECT * FROM c_lp_item_view WHERE iid = ?', [$latest]);
        $this->assertSame('passed', $latestRow['status'], '80% >= 50%');
        $this->assertEquals(8.0, (float) $latestRow['score']);
        $this->assertSame(77, (int) $latestRow['total_time']);
        $this->assertSame('not attempted', $this->conn->fetchOne('SELECT status FROM c_lp_item_view WHERE iid = ?', [$old]), 'only the newest view row is updated');
        $this->assertSame($latest, (int) $this->conn->fetchOne('SELECT orig_lp_item_view_id FROM track_e_exercises WHERE exe_id = ?', [$exe]));
    }

    public function testSaveExerciseInLpMarksFailedWhenBelowPassMark(): void
    {
        [$user, $course] = $this->ctx();
        $quizId = $this->makeQuiz(['pass_percentage' => 90]);
        [$lpId, $itemId] = $this->lpWithQuiz($quizId, (int) $course->getId());
        $this->conn->insert('c_lp_view', ['user_id' => $user->getId(), 'lp_id' => $lpId, 'c_id' => $course->getId(), 'view_count' => 1, 'last_item' => 0]);
        $viewId = (int) $this->conn->lastInsertId();
        $this->conn->insert('c_lp_item_view', ['lp_item_id' => $itemId, 'lp_view_id' => $viewId, 'view_count' => 1, 'start_time' => 0, 'total_time' => 0, 'score' => 0]);
        $itemViewId = (int) $this->conn->lastInsertId();
        $exe = $this->seedExe(['exe_user_id' => $user->getId(), 'c_id' => $course->getId(), 'exe_exo_id' => $quizId, 'score' => 5, 'max_score' => 10]);
        ChamiloSession::write('oLP', new class($viewId) {
            public function __construct(private int $viewId) {}

            public function get_view_id(): int
            {
                return $this->viewId;
            }
        });

        Exercise::saveExerciseInLp($itemId, $exe, (int) $course->getId());

        $this->assertSame('failed', $this->conn->fetchOne('SELECT status FROM c_lp_item_view WHERE iid = ?', [$itemViewId]));
    }

    // ---------------------------------------------------------------- delete

    public function testDeleteOfUnknownQuizReturnsFalse(): void
    {
        $this->ctx();
        $e = new Exercise();
        $e->iId = 2000000000;

        $this->assertFalse($e->delete());
        $this->assertSame(0, $this->countRows('c_quiz', 'iid = ?', [2000000000]));
    }
}
