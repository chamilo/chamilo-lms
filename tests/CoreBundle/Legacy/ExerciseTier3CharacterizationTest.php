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
}
