<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\Tests\ChamiloTestTrait;
use ChamiloSession;
use Doctrine\DBAL\Connection;
use Exercise;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * Characterization tests of the Exercise functions that the other Exercise*CharacterizationTest files do not reach
 * (the LP branch of generateStats(), processCreation(), enableAutoLaunch(), manage_answer()). Written and run
 * green against the Database::query() code BEFORE it was migrated to DQL. Fixture titles start with "x3_".
 */
final class ExerciseTier3CharacterizationTest extends KernelTestCase
{
    use ChamiloTestTrait;

    private Connection $conn;
    private ?int $lpId = null;

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
        // Same named lock as ExerciseSaveDeleteCharacterizationTest: resource_node inserts deadlock in parallel.
        $this->conn->fetchOne("SELECT GET_LOCK('chamilo_test_resource_nodes', 300)");
        $_SERVER['HTTP_USER_AGENT'] = 'x3_agent';
        $_SERVER['REQUEST_URI'] = '/x3_uri';
        unset($_REQUEST['learnpath_id'], $_REQUEST['learnpath_item_id'], $_REQUEST['learnpath_item_view_id'], $_POST['language']);
        ChamiloSession::erase('questionList');
        $this->setContext($this->getUser('admin'), $this->getCourse(1));
        $admin = $this->getUser('admin');
        $container->get('security.token_storage')->setToken(
            new UsernamePasswordToken($admin, 'main', $admin->getRoles())
        );
    }

    protected function tearDown(): void
    {
        ChamiloSession::erase('questionList');
        $this->conn->fetchOne("SELECT RELEASE_LOCK('chamilo_test_resource_nodes')");
        parent::tearDown();
    }

    private function setContext(User $user, Course $course, int $sessionId = 0): void
    {
        ChamiloSession::write('_user', ['id' => $user->getId()]);
        ChamiloSession::write('_real_cid', $course->getId());
        ChamiloSession::write('_cid', $course->getCode());
        ChamiloSession::write('_course', api_get_course_info($course->getCode()));
        ChamiloSession::write('sid', $sessionId);
        ChamiloSession::erase('login_as');
    }

    private function setSetting(string $name, string $value): void
    {
        self::getContainer()->get(SettingsManager::class)->updateSetting($name, $value);
    }

    private function savedExercise(string $title = 'x3_quiz'): Exercise
    {
        $exercise = new Exercise();
        $exercise->updateTitle($title);
        $exercise->save();
        $this->assertGreaterThan(0, $exercise->getId());

        return $exercise;
    }

    private function reread(int $id): Exercise
    {
        $e = new Exercise();
        $this->assertTrue($e->read($id, true));

        return $e;
    }

    private function makeSession(): int
    {
        $this->conn->insert('session', [
            'title' => 'x3_session_'.uniqid(),
            'nbr_courses' => 0,
            'nbr_users' => 0,
            'nbr_classes' => 0,
            'visibility' => 1,
            'status' => 0,
        ]);

        return (int) $this->conn->lastInsertId();
    }

    private function lpItemOfQuiz(int $quizId, string $title): int
    {
        $now = '2020-01-01 00:00:00';
        if (null !== $this->lpId) {
            return $this->itemInLp($this->lpId, $quizId, $title);
        }
        $this->conn->insert('c_lp', [
            'resource_node_id' => 1, 'lp_type' => 1, 'title' => 'x3_lp', 'path' => '', 'force_commit' => 0,
            'content_maker' => '', 'content_license' => '', 'js_lib' => '', 'debug' => 0, 'theme' => '',
            'author' => '', 'prerequisite' => 0, 'hide_toc_frame' => 0, 'seriousgame_mode' => 0, 'autolaunch' => 0,
            'max_attempts' => 0, 'subscribe_users' => 0, 'created_on' => $now, 'modified_on' => $now,
        ]);
        $this->lpId = (int) $this->conn->lastInsertId();
        $this->conn->insert('resource_link', [
            'resource_node_id' => 1, 'c_id' => 1, 'visibility' => 2, 'display_order' => 0,
            'resource_type_group' => 1, 'created_at' => $now, 'updated_at' => $now,
        ]);

        return $this->itemInLp($this->lpId, $quizId, $title);
    }

    private function itemInLp(int $lpId, int $quizId, string $title): int
    {
        $this->conn->insert('c_lp_item', [
            'lp_id' => $lpId, 'title' => $title, 'item_type' => 'quiz', 'ref' => '', 'path' => (string) $quizId,
            'min_score' => 0, 'max_score' => 7, 'display_order' => 1, 'launch_data' => '', 'lvl' => 0,
        ]);

        return (int) $this->conn->lastInsertId();
    }

    // ---------------------------------------------------------------- enableAutoLaunch

    public function testEnableAutoLaunchFlagsOnlyThisQuiz(): void
    {
        $a = $this->savedExercise('x3_auto_a');
        $b = $this->savedExercise('x3_auto_b');
        $this->assertSame(0, (int) $this->conn->fetchOne('SELECT autolaunch FROM c_quiz WHERE iid = ?', [$a->getId()]));

        $a->enableAutoLaunch();

        $this->assertSame(1, (int) $this->conn->fetchOne('SELECT autolaunch FROM c_quiz WHERE iid = ?', [$a->getId()]));
        $this->assertSame(0, (int) $this->conn->fetchOne('SELECT autolaunch FROM c_quiz WHERE iid = ?', [$b->getId()]), 'the other quiz is untouched');
    }

    // ---------------------------------------------------------------- processCreation

    private function fakeForm(array $values): object
    {
        return new class($values) {
            public function __construct(
                private array $values
            ) {}

            public function getSubmitValue(string $name): mixed
            {
                return $this->values[$name] ?? null;
            }

            public function getSubmitValues(): array
            {
                return $this->values;
            }
        };
    }

    public function testProcessCreationSavesTheFormValuesAndRenamesTheQuizItemsOfLearningPaths(): void
    {
        $other = $this->savedExercise('x3_other_quiz');
        $e = new Exercise();
        $values = [
            'exerciseTitle' => 'x3_created',
            'exerciseDescription' => '',
            'exerciseAttempts' => 3,
            'exerciseFeedbackType' => 0,
            'exerciseType' => 2,
            'pass_percentage' => 55,
            'update_title_in_lps' => 1,
            'text_when_finished' => '',
            'text_when_finished_failure' => '',
        ];
        // Created once so that the quiz has an id and the lp items can point to it.
        $e->processCreation($this->fakeForm($values));
        $id = $e->getId();
        $this->assertGreaterThan(0, $id);
        $row = $this->conn->fetchAssociative('SELECT title, max_attempt, pass_percentage FROM c_quiz WHERE iid = ?', [$id]);
        $this->assertSame('x3_created', $row['title']);
        $this->assertSame(3, (int) $row['max_attempt']);
        $this->assertSame(55, (int) $row['pass_percentage']);

        $itemOfQuiz = $this->lpItemOfQuiz($id, 'x3_old_title');
        $itemOfQuiz2 = $this->lpItemOfQuiz($id, 'x3_old_title_2');
        $itemOfOther = $this->lpItemOfQuiz($other->getId(), 'x3_unrelated');
        $this->conn->insert('c_lp_item', [
            'lp_id' => (int) $this->conn->fetchOne('SELECT lp_id FROM c_lp_item WHERE iid = ?', [$itemOfQuiz]),
            'title' => 'x3_doc_with_same_path', 'item_type' => 'document', 'ref' => '', 'path' => (string) $id,
            'min_score' => 0, 'max_score' => 0, 'display_order' => 2, 'launch_data' => '', 'lvl' => 0,
        ]);
        $docItem = (int) $this->conn->lastInsertId();

        $values['exerciseTitle'] = 'x3_renamed';
        $e->processCreation($this->fakeForm($values));

        $title = fn (int $iid): string => (string) $this->conn->fetchOne('SELECT title FROM c_lp_item WHERE iid = ?', [$iid]);
        $this->assertSame('x3_renamed', $title($itemOfQuiz));
        $this->assertSame('x3_renamed', $title($itemOfQuiz2));
        $this->assertSame('x3_unrelated', $title($itemOfOther), 'another quiz');
        $this->assertSame('x3_doc_with_same_path', $title($docItem), 'only quiz items are renamed');
        $this->assertSame('x3_renamed', (string) $this->conn->fetchOne('SELECT title FROM c_quiz WHERE iid = ?', [$id]));
    }

    public function testProcessCreationKeepsTheLpItemTitlesWhenNotAsked(): void
    {
        $e = new Exercise();
        $values = ['exerciseTitle' => 'x3_keep', 'exerciseDescription' => '', 'exerciseAttempts' => 0, 'exerciseFeedbackType' => 0, 'exerciseType' => 2, 'text_when_finished' => '', 'text_when_finished_failure' => ''];
        $e->processCreation($this->fakeForm($values));
        $item = $this->lpItemOfQuiz($e->getId(), 'x3_keep_old');

        $values['exerciseTitle'] = 'x3_keep_new';
        $e->processCreation($this->fakeForm($values));

        $this->assertSame('x3_keep_old', $this->conn->fetchOne('SELECT title FROM c_lp_item WHERE iid = ?', [$item]));
    }

    // ---------------------------------------------------------------- generateStats, learning path branch

    public function testGenerateStatsOfAQuizInALearningPathCountsOnlyThatLpAndSessionAttempts(): void
    {
        $this->setSetting('gradebook.allow_gradebook_stats', 'true');
        $e = $this->savedExercise('x3_stats_lp');
        $id = $e->getId();
        $sid = $this->makeSession();
        $this->conn->insert('session_rel_course', ['session_id' => $sid, 'c_id' => 1, 'nbr_users' => 0, 'position' => 0]);
        $this->conn->insert('session_rel_course_rel_user', ['session_id' => $sid, 'c_id' => 1, 'user_id' => 2, 'status' => 0, 'visibility' => 1, 'legal_agreement' => 0, 'progress' => 0]);
        $this->conn->insert('gradebook_category', [
            'c_id' => 1, 'session_id' => $sid, 'title' => 'x3_gb_cat', 'weight' => 100, 'visible' => 1, 'locked' => 0,
            'generate_certificates' => 0, 'is_requirement' => 0, 'calculation_mode' => 'weighted_average',
        ]);
        $categoryId = (int) $this->conn->lastInsertId();
        $nodeId = (int) $this->conn->fetchOne('SELECT resource_node_id FROM c_quiz WHERE iid = ?', [$id]);
        $this->conn->insert('gradebook_link', [
            'ref_id' => $nodeId, 'c_id' => 1, 'category_id' => $categoryId,
            'type' => LINK_EXERCISE, 'created_at' => '2020-01-01 00:00:00', 'weight' => 10, 'visible' => 1, 'locked' => 0,
        ]);
        $linkId = (int) $this->conn->lastInsertId();

        $itemId = $this->lpItemOfQuiz($id, 'x3_lp_item');
        $lpId = (int) $this->conn->fetchOne('SELECT lp_id FROM c_lp_item WHERE iid = ?', [$itemId]);
        $exe = function (int $lp, ?int $session, float $score, string $status, int $user = 2, int $course = 1) use ($id): void {
            $this->conn->insert('track_e_exercises', [
                'exe_user_id' => $user, 'c_id' => $course, 'session_id' => $session, 'exe_exo_id' => $id,
                'exe_date' => '2020-01-02 10:00:00', 'score' => $score, 'max_score' => 10.0, 'user_ip' => '10.0.0.1',
                'status' => $status, 'data_tracking' => '', 'start_date' => '2020-01-02 10:00:00', 'steps_counter' => 0,
                'orig_lp_id' => $lp, 'orig_lp_item_id' => 0, 'exe_duration' => 0, 'orig_lp_item_view_id' => 0,
                'questions_to_check' => '',
            ]);
        };
        $exe($lpId, $sid, 2.0, 'completed');  // older attempt of the student
        $exe($lpId, $sid, 6.0, 'completed');  // latest by exe_id: counts
        $exe($lpId, $sid, 9.0, 'incomplete'); // not finished
        $exe(0, $sid, 9.5, 'completed');      // outside the LP
        $exe($lpId, null, 9.7, 'completed');  // base course, not the session
        $exe($lpId + 1000, $sid, 9.9, 'completed'); // another LP

        $this->setContext($this->getUser('admin'), $this->getCourse(1), $sid);
        $e = $this->reread($id);
        $this->assertTrue($e->exercise_was_added_in_lp, 'read() found the LP link');
        $e->generateStats($id, api_get_course_info(), $sid);

        $link = $this->conn->fetchAssociative('SELECT * FROM gradebook_link WHERE id = ?', [$linkId]);
        $this->assertSame(6.0, (float) $link['best_score']);
        $this->assertSame(6.0, (float) $link['average_score']);
        $this->assertEquals([2 => 6], json_decode((string) $link['user_score_list'], true));
    }

    // ---------------------------------------------------------------- wide "SELECT *" row shapes

    public function testGetExercisesByCourseSessionReturnsTheWholeQuizRowPlusId(): void
    {
        $e = $this->savedExercise('x3_wide_row');
        $id = $e->getId();
        $raw = $this->conn->fetchAssociative('SELECT * FROM c_quiz WHERE iid = ?', [$id]);
        $raw['id'] = $raw['iid'];

        $rows = (new Exercise())->getExercisesByCourseSession(1, 0);
        $mine = array_values(array_filter($rows, static fn (array $r): bool => (int) $r['iid'] === $id));

        $this->assertCount(1, $mine);
        $this->assertEqualsCanonicalizing(array_keys($raw), array_keys($mine[0]), 'same columns as SELECT *');
        $this->assertEquals($raw, $mine[0], 'same values as SELECT *');
        $this->assertSame('x3_wide_row', $mine[0]['title']);
    }

    public function testGetExerciseAndResultReturnsTheWholeAttemptAndQuizColumns(): void
    {
        $e = $this->savedExercise('x3_wide_join');
        $id = $e->getId();
        $this->conn->insert('track_e_exercises', [
            'exe_user_id' => 2, 'c_id' => 1, 'session_id' => null, 'exe_exo_id' => $id,
            'exe_date' => '2020-01-02 10:00:00', 'score' => 4.5, 'max_score' => 10.0, 'user_ip' => '10.0.0.1',
            'status' => 'completed', 'data_tracking' => '1,2', 'start_date' => '2020-01-02 09:00:00', 'steps_counter' => 0,
            'orig_lp_id' => 0, 'orig_lp_item_id' => 0, 'exe_duration' => 12, 'orig_lp_item_view_id' => 0,
            'questions_to_check' => '',
        ]);
        $raw = $this->conn->fetchAssociative(
            'SELECT * FROM track_e_exercises te INNER JOIN c_quiz cq ON cq.iid = te.exe_exo_id WHERE cq.iid = ?',
            [$id]
        );

        $rows = (new Exercise())->getExerciseAndResult(1, 0, [$id]);

        $this->assertCount(1, $rows);
        $this->assertEqualsCanonicalizing(array_keys($raw), array_keys($rows[0]), 'same columns as SELECT *');
        $this->assertEquals($raw, $rows[0], 'same values as SELECT *');
        $this->assertSame(2, (int) $rows[0]['exe_user_id']);
        $this->assertEquals(4.5, $rows[0]['score']);
    }

    // ---------------------------------------------------------------- manage_answer (from the database)

    private function makeQuestion(string $title, int $type, float $weight, string $extra = ''): int
    {
        $this->conn->insert('c_quiz_question', [
            'question' => $title, 'ponderation' => $weight, 'position' => 0, 'type' => $type, 'level' => 0,
            'mandatory' => 0, 'extra' => $extra,
        ]);
        $questionId = (int) $this->conn->lastInsertId();
        $this->conn->insert('c_quiz_rel_question', ['quiz_id' => $this->quizId, 'question_id' => $questionId, 'question_order' => 1]);

        return $questionId;
    }

    private function makeAnswer(int $questionId, string $text, int $correct, float $weight, int $position, array $o = []): int
    {
        $this->conn->insert('c_quiz_answer', $o + [
            'question_id' => $questionId, 'answer' => $text, 'correct' => $correct, 'comment' => '',
            'ponderation' => $weight, 'position' => $position,
        ]);

        return (int) $this->conn->lastInsertId();
    }

    private function attempt(int $exeId, int $questionId, string $answer, float $marks = 0.0, int $position = 0): void
    {
        $this->conn->insert('track_e_attempt', [
            'exe_id' => $exeId, 'user_id' => 1, 'question_id' => $questionId, 'answer' => $answer,
            'teacher_comment' => '', 'marks' => $marks, 'position' => $position, 'tms' => '2020-01-01 10:00:00',
            'seconds_spent' => 0,
        ]);
    }

    private function newAttemptRow(float $score = 0.0): int
    {
        $this->conn->insert('track_e_exercises', [
            'exe_user_id' => 1, 'c_id' => 1, 'session_id' => null, 'exe_exo_id' => $this->quizId,
            'exe_date' => '2020-01-01 10:00:00', 'score' => $score, 'max_score' => 10, 'user_ip' => '10.0.0.1',
            'status' => '', 'data_tracking' => '', 'start_date' => '2020-01-01 09:00:00', 'steps_counter' => 0,
            'orig_lp_id' => 0, 'orig_lp_item_id' => 0, 'exe_duration' => 0, 'orig_lp_item_view_id' => 0,
            'questions_to_check' => '',
        ]);

        return (int) $this->conn->lastInsertId();
    }

    private ?int $quizId = null;

    /**
     * Calls manage_answer() the way the result pages do (everything read back from the database, nothing echoed).
     */
    private function fromDatabase(int $exeId, int $questionId, bool $saveResults = false, int $propagateNeg = 0): array
    {
        $e = $this->reread((int) $this->quizId);
        $result = $e->manage_answer(
            $exeId,
            $questionId,
            null,
            'exercise_show',
            [],
            $saveResults,
            true,
            false,
            $propagateNeg
        );
        $this->assertIsArray($result);

        return $result;
    }

    private function newQuiz(): void
    {
        $this->quizId = $this->savedExercise('x3_manage_answer')->getId();
    }

    public function testManageAnswerUniqueAnswerScoresTheChoiceStoredInTheAttempt(): void
    {
        $this->newQuiz();
        $q = $this->makeQuestion('x3_unique', UNIQUE_ANSWER, 5.0);
        $wrong = $this->makeAnswer($q, 'wrong', 0, -1.0, 1);
        $right = $this->makeAnswer($q, 'right', 1, 5.0, 2);
        $exeRight = $this->newAttemptRow();
        $this->attempt($exeRight, $q, (string) $right);
        $exeWrong = $this->newAttemptRow();
        $this->attempt($exeWrong, $q, (string) $wrong);
        $exeNone = $this->newAttemptRow();

        $this->assertEquals(5.0, $this->fromDatabase($exeRight, $q)['score']);
        $this->assertTrue($this->fromDatabase($exeRight, $q)['user_answered']);
        $this->assertEquals(0, $this->fromDatabase($exeWrong, $q)['score'], 'negative weights are not propagated');
        $this->assertEquals(-1.0, $this->fromDatabase($exeWrong, $q, false, 1)['score'], 'propagated');
        $this->assertEquals(0, $this->fromDatabase($exeNone, $q)['score']);
        $this->assertFalse($this->fromDatabase($exeNone, $q)['user_answered']);
    }

    public function testManageAnswerMultipleAnswerFamilyReadsEveryAttemptRowOfTheQuestion(): void
    {
        $this->newQuiz();
        foreach ([
            MULTIPLE_ANSWER => [2.0, 3.0],
            GLOBAL_MULTIPLE_ANSWER => [2.0, 3.0],
        ] as $type => [$w1, $w2]) {
            $q = $this->makeQuestion('x3_multi_'.$type, $type, $w1 + $w2);
            $a1 = $this->makeAnswer($q, 'a1', 1, $w1, 1);
            $a2 = $this->makeAnswer($q, 'a2', 1, $w2, 2);
            $a3 = $this->makeAnswer($q, 'a3', 0, -1.0, 3);
            $both = $this->newAttemptRow();
            $this->attempt($both, $q, (string) $a1);
            $this->attempt($both, $q, (string) $a2);
            $one = $this->newAttemptRow();
            $this->attempt($one, $q, (string) $a2);
            $other = $this->newAttemptRow();
            $this->attempt($other, $q, (string) $a1);
            $this->attempt($other, $q, (string) $a3);
            $this->attempt($other, 0, (string) $a2); // another question of the same attempt: ignored

            $this->assertEquals(5.0, $this->fromDatabase($both, $q)['score'], 'type '.$type);
            $this->assertEquals(3.0, $this->fromDatabase($one, $q)['score'], 'type '.$type);
            $this->assertEquals(1.0, $this->fromDatabase($other, $q)['score'], 'type '.$type.': 2 - 1');
        }
    }

    public function testManageAnswerMultipleAnswerCombinationIsAllOrNothing(): void
    {
        $this->newQuiz();
        $q = $this->makeQuestion('x3_combo', MULTIPLE_ANSWER_COMBINATION, 4.0);
        $a1 = $this->makeAnswer($q, 'a1', 1, 4.0, 1);
        $a2 = $this->makeAnswer($q, 'a2', 1, 0.0, 2);
        $a3 = $this->makeAnswer($q, 'a3', 0, 0.0, 3);
        $all = $this->newAttemptRow();
        $this->attempt($all, $q, (string) $a1);
        $this->attempt($all, $q, (string) $a2);
        $partial = $this->newAttemptRow();
        $this->attempt($partial, $q, (string) $a1);
        $excess = $this->newAttemptRow();
        $this->attempt($excess, $q, (string) $a1);
        $this->attempt($excess, $q, (string) $a2);
        $this->attempt($excess, $q, (string) $a3);

        $this->assertEquals(4.0, $this->fromDatabase($all, $q)['score']);
        $this->assertEquals(0, $this->fromDatabase($partial, $q)['score']);
        $this->assertEquals(0, $this->fromDatabase($excess, $q)['score']);
    }

    private function makeOption(int $questionId, string $title, int $position): int
    {
        $this->conn->insert('c_quiz_question_option', ['question_id' => $questionId, 'title' => $title, 'position' => $position]);

        return (int) $this->conn->lastInsertId();
    }

    public function testManageAnswerTrueFalseScoresEachRowWithTrueFalseAndDoubtScores(): void
    {
        $this->newQuiz();
        $q = $this->makeQuestion('x3_tf', MULTIPLE_ANSWER_TRUE_FALSE, 4.0, '2:-1:0.5');
        $true = $this->makeOption($q, 'True', 1);
        $false = $this->makeOption($q, 'False', 2);
        $doubt = $this->makeOption($q, "Don't know", 3);
        $a1 = $this->makeAnswer($q, 'a1', $true, 2.0, 1);
        $a2 = $this->makeAnswer($q, 'a2', $false, 2.0, 2);
        $exe = $this->newAttemptRow();
        $this->attempt($exe, $q, $a1.':'.$true);   // right: +2
        $this->attempt($exe, $q, $a2.':'.$true);   // wrong: -1
        $exeDoubt = $this->newAttemptRow();
        $this->attempt($exeDoubt, $q, $a1.':'.$doubt); // doubt: +0.5, the other row has no answer: doubt again
        $exeNone = $this->newAttemptRow();

        $this->assertEquals(1.0, $this->fromDatabase($exe, $q, false, 1)['score']);
        // The options are keyed by their index in the list, not by iid: the "Don't know" lookup by iid misses and
        // the false score (-1) applies to a1, the unanswered a2 counts as doubt (+0.5).
        $this->assertEquals(-0.5, $this->fromDatabase($exeDoubt, $q, false, 1)['score']);
        $this->assertEquals(1.0, $this->fromDatabase($exeNone, $q, false, 1)['score'], 'no answers: doubt score per row');
        $this->assertSame(MULTIPLE_ANSWER_TRUE_FALSE, $this->fromDatabase($exe, $q)['answer_type']);
        $this->assertSame([$a1, $a2], array_map('intval', $this->fromDatabase($exe, $q)['correct_answer_id']));
    }

    public function testManageAnswerTrueFalseWithDegreeOfCertaintyReadsTheThreePartAnswer(): void
    {
        $this->newQuiz();
        $q = $this->makeQuestion('x3_degree', MULTIPLE_ANSWER_TRUE_FALSE_DEGREE_CERTAINTY, 2.0, '1:-0.5:0');
        $true = $this->makeOption($q, 'True', 1);
        $false = $this->makeOption($q, 'False', 2);
        $sure = $this->makeOption($q, '80%', 6);   // position 3..8: the student is "pretty sure"
        $unsureLow = $this->makeOption($q, '20%', 1);
        $a1 = $this->makeAnswer($q, 'a1', $true, 1.0, 1);
        $a2 = $this->makeAnswer($q, 'a2', $false, 1.0, 2);
        $exe = $this->newAttemptRow();
        $this->attempt($exe, $q, $a1.':'.$true.':2');       // degree 2 = third option (80%, position 6): sure, right: +1
        $this->attempt($exe, $q, $a2.':'.$true.':2');       // wrong and sure: -0.5
        $exeLow = $this->newAttemptRow();
        $this->attempt($exeLow, $q, $a1.':'.$true.':3'); // degree 3 = fourth option (20%, position 1): not sure, right: doubt 0
        $this->attempt($exeLow, $q, $a2.':'.$true.':3');

        $this->assertEquals(0.5, $this->fromDatabase($exe, $q, false, 1)['score']);
        $this->assertEquals(0, $this->fromDatabase($exeLow, $q, false, 1)['score']);
    }

    public function testManageAnswerCombinationTrueFalseIsAllOrNothing(): void
    {
        $this->newQuiz();
        $q = $this->makeQuestion('x3_combo_tf', MULTIPLE_ANSWER_COMBINATION_TRUE_FALSE, 3.0);
        $true = $this->makeOption($q, 'True', 1);
        $false = $this->makeOption($q, 'False', 2);
        $a1 = $this->makeAnswer($q, 'a1', $true, 3.0, 1);
        $a2 = $this->makeAnswer($q, 'a2', $false, 0.0, 2);
        $ok = $this->newAttemptRow();
        $this->attempt($ok, $q, $a1.':'.$true);
        $this->attempt($ok, $q, $a2.':'.$false);
        $ko = $this->newAttemptRow();
        $this->attempt($ko, $q, $a1.':'.$true);
        $this->attempt($ko, $q, $a2.':'.$true);

        $this->assertEquals(3.0, $this->fromDatabase($ok, $q)['score']);
        $this->assertEquals(0, $this->fromDatabase($ko, $q)['score']);
    }

    public function testManageAnswerDropdownFamilyReadsTheSelectedChoicesFromTheAttempt(): void
    {
        $this->newQuiz();
        $q = $this->makeQuestion('x3_dd', MULTIPLE_ANSWER_DROPDOWN, 5.0);
        $a1 = $this->makeAnswer($q, 'a1', 1, 2.0, 1);
        $a2 = $this->makeAnswer($q, 'a2', 1, 3.0, 2);
        $a3 = $this->makeAnswer($q, 'a3', 0, 1.0, 3);
        $exe = $this->newAttemptRow();
        $this->attempt($exe, $q, (string) $a1);
        $this->attempt($exe, $q, (string) $a3);
        $this->assertEquals(3.0, $this->fromDatabase($exe, $q)['score'], 'every selected choice weighs, right or wrong');

        $qc = $this->makeQuestion('x3_ddc', MULTIPLE_ANSWER_DROPDOWN_COMBINATION, 4.0);
        $c1 = $this->makeAnswer($qc, 'c1', 1, 0.0, 1);
        $c2 = $this->makeAnswer($qc, 'c2', 1, 0.0, 2);
        $c3 = $this->makeAnswer($qc, 'c3', 0, 0.0, 3);
        $good = $this->newAttemptRow();
        $this->attempt($good, $qc, (string) $c1);
        $this->attempt($good, $qc, (string) $c2);
        $bad = $this->newAttemptRow();
        $this->attempt($bad, $qc, (string) $c1);
        $this->attempt($bad, $qc, (string) $c2);
        $this->attempt($bad, $qc, (string) $c3);
        $this->assertEquals(4.0, $this->fromDatabase($good, $qc)['score']);
        $this->assertEquals(0, $this->fromDatabase($bad, $qc)['score']);
    }

    private function hotspotRow(int $exeId, int $questionId, int $answerId, int $correct, string $coords = '1;1/2;2'): void
    {
        $this->conn->insert('track_e_hotspot', [
            'c_id' => 1, 'hotspot_user_id' => 1, 'hotspot_exe_id' => $exeId, 'hotspot_question_id' => $questionId,
            'hotspot_answer_id' => $answerId, 'hotspot_correct' => $correct, 'hotspot_coordinate' => $coords,
        ]);
    }

    public function testManageAnswerFillInBlanksScoresTheStoredBlanks(): void
    {
        $this->newQuiz();
        foreach ([FILL_IN_BLANKS => 2.0, FILL_IN_BLANKS_COMBINATION => 0.0] as $type => $partial) {
            $q = $this->makeQuestion('x3_fib'.$type, $type, 5.0);
            $this->makeAnswer($q, 'Italy: [Rome] France: [Paris]::2,3:200,200:0@0', 0, 5.0, 1);
            $score = function (string $stored) use ($q): float {
                $exe = $this->newAttemptRow();
                $this->attempt($exe, $q, $stored);

                return (float) $this->fromDatabase($exe, $q)['score'];
            };
            $this->assertEquals(5.0, $score('Italy: [Rome][Rome][1] France: [Paris][Paris][1]::2,3:200,200:0@0'), 'type '.$type);
            $this->assertEquals($partial, $score('Italy: [Rome][Rome][1] France: [Paris][Lyon][0]::2,3:200,200:0@0'), 'type '.$type.': combination is all or nothing');
            $this->assertEquals(0, $score('Italy: [Rome][][0] France: [Paris][][0]::2,3:200,200:0@0'), 'type '.$type);
            $exe = $this->newAttemptRow();
            $this->assertEquals(0, $this->fromDatabase($exe, $q)['score'], 'type '.$type.': no attempt row');
        }
    }

    public function testManageAnswerOpenQuestionsReadTheAnswerAndTheMarksOfTheAttemptRow(): void
    {
        $this->newQuiz();
        foreach ([FREE_ANSWER, ORAL_EXPRESSION, ANNOTATION, UPLOAD_ANSWER, CALCULATED_ANSWER] as $type) {
            $q = $this->makeQuestion('x3_free'.$type, $type, 5.0);
            $this->makeAnswer($q, '', 0, 0.0, 1);
            $with = $this->newAttemptRow();
            $this->attempt($with, $q, 'my text', 3.5);
            $this->attempt($with, $q, 'a later row of the same question', 1.0);
            $without = $this->newAttemptRow();

            $r = $this->fromDatabase($with, $q);
            $this->assertEquals(3.5, $r['score'], 'type '.$type.': marks of the first attempt row');
            if (CALCULATED_ANSWER === $type) {
                continue; // without a row it reads an offset of false (warning): not a behaviour to pin
            }
            $this->assertEquals(0, $this->fromDatabase($without, $q)['score'], 'type '.$type.': no row');
            if (ANNOTATION !== $type && CALCULATED_ANSWER !== $type && ORAL_EXPRESSION !== $type) {
                $this->assertSame('my text', $r['open_answer'], 'type '.$type);
                $this->assertSame('', $this->fromDatabase($without, $q)['open_answer'], 'type '.$type);
            }
            if (ORAL_EXPRESSION === $type) {
                $this->assertSame('my text', $r['open_answer']);
            }
        }
    }

    public function testManageAnswerMatchingScoresTheOptionStoredAtItsPosition(): void
    {
        $this->newQuiz();
        foreach ([MATCHING, DRAGGABLE, MATCHING_DRAGGABLE] as $type) {
            $q = $this->makeQuestion('x3_match'.$type, $type, 4.0);
            $o1 = $this->makeAnswer($q, 'Paris', 1, 2.0, 1);
            $o2 = $this->makeAnswer($q, 'Rome', 2, 2.0, 2);
            $l1 = $this->makeAnswer($q, 'France', 0, 0.0, 3);
            $l2 = $this->makeAnswer($q, 'Italy', 0, 0.0, 4);
            $good = $this->newAttemptRow();
            $this->attempt($good, $q, '1', 0.0, $o1);
            $this->attempt($good, $q, '2', 0.0, $o2);
            $half = $this->newAttemptRow();
            $this->attempt($half, $q, '1', 0.0, $o1);
            $this->attempt($half, $q, (string) $l2, 0.0, $o2);
            $swapped = $this->newAttemptRow();
            $this->attempt($swapped, $q, (string) $l1, 0.0, $o1); // right row, wrong value
            $this->attempt($swapped, $q, '1', 0.0, 2000000000); // a position that is no option: ignored
            $none = $this->newAttemptRow();

            $this->assertEquals(4.0, $this->fromDatabase($good, $q)['score'], 'type '.$type);
            $this->assertEquals(2.0, $this->fromDatabase($half, $q)['score'], 'type '.$type);
            $this->assertEquals(0, $this->fromDatabase($none, $q)['score'], 'type '.$type);
            if (DRAGGABLE !== $type) {
                $this->assertEquals(0, $this->fromDatabase($swapped, $q)['score'], 'type '.$type);
            }
        }
    }

    public function testManageAnswerHotSpotScoresTheHotspotRowsOfTheAttempt(): void
    {
        $this->newQuiz();
        foreach ([HOT_SPOT => [4.0, 2.0], HOT_SPOT_COMBINATION => [4.0, 0.0]] as $type => [$both, $first]) {
            $q = $this->makeQuestion('x3_hot'.$type, $type, 4.0);
            $h1 = $this->makeAnswer($q, 'Zone 1', 1, 2.0, 1, ['hotspot_coordinates' => '0;0|10|10', 'hotspot_type' => 'square']);
            $h2 = $this->makeAnswer($q, 'Zone 2', 2, 2.0, 2, ['hotspot_coordinates' => '20;20|10|10', 'hotspot_type' => 'square']);
            $rows = function (int $a, int $b) use ($q, $h1, $h2): int {
                $exe = $this->newAttemptRow();
                $this->hotspotRow($exe, $q, $h1, $a);
                $this->hotspotRow($exe, $q, $h2, $b);

                return $exe;
            };
            $this->assertEquals($both, $this->fromDatabase($rows(1, 1), $q)['score'], 'type '.$type);
            $this->assertEquals($first, $this->fromDatabase($rows(1, 0), $q)['score'], 'type '.$type);
            $this->assertEquals(0, $this->fromDatabase($rows(0, 0), $q)['score'], 'type '.$type);
            $this->assertEquals(0, $this->fromDatabase($this->newAttemptRow(), $q)['score'], 'type '.$type.': no hotspot row');
            // Rows of the same hotspot saved twice: the oldest (lowest hotspot_id) one counts.
            $twice = $rows(0, 1);
            $this->hotspotRow($twice, $q, $h1, 1);
            // HOT_SPOT: the first row of an answer wins (h1 stays wrong, h2 right). The combination scores every
            // zone from the rows of the attempt through a different path and gives full marks here.
            $this->assertEquals(HOT_SPOT === $type ? 2.0 : 4.0, $this->fromDatabase($twice, $q)['score'], 'type '.$type);
        }
    }

    public function testManageAnswerAddsTheQuestionScoreToTheAttemptWhenSavingResults(): void
    {
        $this->newQuiz();
        $q = $this->makeQuestion('x3_save', UNIQUE_ANSWER, 5.0);
        $wrong = $this->makeAnswer($q, 'wrong', 0, -1.0, 1);
        $right = $this->makeAnswer($q, 'right', 1, 5.0, 2);
        $exe = $this->newAttemptRow(1.0);
        $this->attempt($exe, $q, (string) $right);
        $this->fromDatabase($exe, $q, true);
        $this->assertEquals(6.0, (float) $this->conn->fetchOne('SELECT score FROM track_e_exercises WHERE exe_id = ?', [$exe]));

        $other = $this->newAttemptRow(1.0);
        $this->fromDatabase($other, $q, true);
        $this->assertEquals(1.0, (float) $this->conn->fetchOne('SELECT score FROM track_e_exercises WHERE exe_id = ?', [$other]));
        $this->assertEquals(1.0, (float) $this->conn->fetchOne('SELECT score FROM track_e_exercises WHERE exe_id = ?', [$exe]) - 5.0, 'other attempts are untouched');
    }

    public function testManageAnswerHotSpotFallsBackOnTheLegacyAnswerNumbers(): void
    {
        $this->newQuiz();
        $q = $this->makeQuestion('x3_hot_legacy', HOT_SPOT, 4.0);
        $this->makeAnswer($q, 'Zone 1', 1, 2.0, 1, ['hotspot_coordinates' => '0;0|10|10', 'hotspot_type' => 'square']);
        $this->makeAnswer($q, 'Zone 2', 2, 2.0, 2, ['hotspot_coordinates' => '20;20|10|10', 'hotspot_type' => 'square']);
        // Attempts saved by older versions: hotspot_answer_id is the position of the answer (1, 2), not its iid.
        $exe = $this->newAttemptRow();
        $this->hotspotRow($exe, $q, 1, 1);
        $this->hotspotRow($exe, $q, 2, 0);
        $this->assertEquals(2.0, $this->fromDatabase($exe, $q)['score']);
        $both = $this->newAttemptRow();
        $this->hotspotRow($both, $q, 1, 1);
        $this->hotspotRow($both, $q, 2, 1);
        $this->assertEquals(4.0, $this->fromDatabase($both, $q)['score']);
    }
}
