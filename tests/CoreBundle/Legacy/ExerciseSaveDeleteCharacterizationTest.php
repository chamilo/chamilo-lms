<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\Tests\ChamiloTestTrait;
use ChamiloSession;
use Doctrine\DBAL\Connection;
use Exercise;
use Question;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * Characterization tests for the entity-persisting paths of Exercise and Question (save of a NEW quiz/question,
 * delete, copyExercise, duplicate). They create resource_node/resource_link rows through the repositories INSIDE the
 * test transaction (DAMA rolls everything back). Fixture titles start with "x2_".
 */
final class ExerciseSaveDeleteCharacterizationTest extends KernelTestCase
{
    use ChamiloTestTrait;

    private Connection $conn;

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
        // Inserting resource_node rows deadlocks when two PHPUnit processes do it at the same time on the shared
        // database (verified: three copies of this very suite in parallel). A named lock (session level, not rolled
        // back with the test transaction) serializes the copies of this suite; it is released in tearDown().
        $this->conn->fetchOne("SELECT GET_LOCK('chamilo_test_resource_nodes', 300)");
        $_SERVER['HTTP_USER_AGENT'] = 'x2_agent';
        $_SERVER['REQUEST_URI'] = '/x2_uri';
        unset($_REQUEST['learnpath_id'], $_REQUEST['learnpath_item_id'], $_REQUEST['learnpath_item_view_id'], $_POST['language']);
        ChamiloSession::erase('questionList');
        $this->setContext($this->getUser('admin'), $this->getCourse(1));
        $this->loginToken(true);
    }

    /**
     * A web request has the admin in the Security token (ResourceListener falls back on it for the creator and
     * api_is_platform_admin() reads it); CLI/cron/import code has none.
     */
    private function loginToken(bool $on): void
    {
        $storage = self::getContainer()->get('security.token_storage');
        if (!$on) {
            $storage->setToken(null);

            return;
        }
        $admin = $this->getUser('admin');
        $storage->setToken(new UsernamePasswordToken($admin, 'main', $admin->getRoles()));
    }

    protected function tearDown(): void
    {
        ChamiloSession::erase('questionList');
        unset($_POST['language']);
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

    private function newExercise(string $title = 'x2_quiz'): Exercise
    {
        $exercise = new Exercise();
        $exercise->updateTitle($title);

        return $exercise;
    }

    // ---------------------------------------------------------------- helpers (rows)

    private function makeSession(): int
    {
        $this->conn->insert('session', [
            'title' => 'x2_session_'.uniqid(),
            'nbr_courses' => 0,
            'nbr_users' => 0,
            'nbr_classes' => 0,
            'visibility' => 1,
            'status' => 0,
        ]);

        return (int) $this->conn->lastInsertId();
    }

    private function quizRow(int $id): array
    {
        $row = $this->conn->fetchAssociative('SELECT * FROM c_quiz WHERE iid = ?', [$id]);
        $this->assertIsArray($row, 'c_quiz row '.$id);

        return $row;
    }

    private function countLinks(int $nodeId, bool $onlyLive = true): int
    {
        $sql = 'SELECT COUNT(*) FROM resource_link WHERE resource_node_id = ?'.($onlyLive ? ' AND deleted_at IS NULL' : '');

        return (int) $this->conn->fetchOne($sql, [$nodeId]);
    }

    private function makeQuestion(string $title, int $type = 1, float $weight = 1.0, array $o = []): int
    {
        $this->conn->insert('c_quiz_question', $o + [
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

    private function makeCategory(string $title): int
    {
        $this->conn->insert('c_quiz_question_category', ['title' => $title]);

        return (int) $this->conn->lastInsertId();
    }

    private function categorize(int $questionId, int $categoryId): void
    {
        $this->conn->insert('c_quiz_question_rel_category', ['question_id' => $questionId, 'category_id' => $categoryId]);
    }

    private function savedExercise(string $title = 'x2_quiz', array $props = []): Exercise
    {
        $exercise = $this->newExercise($title);
        foreach ($props as $k => $v) {
            $exercise->$k = $v;
        }
        $exercise->save();
        $this->assertGreaterThan(0, $exercise->getId());

        return $exercise;
    }

    private function reread(int $id, bool $parse = true): Exercise
    {
        $e = new Exercise();
        $this->assertTrue($e->read($id, $parse));

        return $e;
    }

    // ---------------------------------------------------------------- Exercise::save()

    public function testSaveNewCreatesQuizRowNodeAndCourseLink(): void
    {
        $exercise = $this->newExercise();
        $id = $exercise->save();

        $this->assertGreaterThan(0, (int) $id);
        $this->assertSame((int) $id, $exercise->getId());
        $row = $this->quizRow((int) $id);
        $this->assertSame('x2_quiz', $row['title']);
        $this->assertNotEmpty($row['resource_node_id']);
        $this->assertSame(1, $this->countLinks((int) $row['resource_node_id']));
        $link = $this->conn->fetchAssociative(
            'SELECT c_id, session_id, visibility FROM resource_link WHERE resource_node_id = ?',
            [$row['resource_node_id']]
        );
        $this->assertSame(1, (int) $link['c_id']);
        $this->assertNull($link['session_id'], 'no session context: the link is a base-course link');
    }

    public function testSaveNewInSessionContextLinksTheQuizToTheSession(): void
    {
        $sid = $this->makeSession();
        $this->setContext($this->getUser('admin'), $this->getCourse(1), $sid);
        $exercise = $this->newExercise('x2_in_session');
        $id = (int) $exercise->save();

        $row = $this->quizRow($id);
        $link = $this->conn->fetchAssociative(
            'SELECT c_id, session_id FROM resource_link WHERE resource_node_id = ?',
            [$row['resource_node_id']]
        );
        $this->assertSame($sid, (int) $link['session_id']);
        $this->assertSame(1, (int) $link['c_id']);
    }

    public function testSaveWritesEveryScalarSettingAndReadReturnsThem(): void
    {
        $e = $this->newExercise('x2_full');
        $e->description = 'x2 description';
        $e->type = ONE_PER_PAGE;
        $e->random = 3;
        $e->random_answers = 1;
        $e->attempts = 4;
        $e->feedback_type = EXERCISE_FEEDBACK_TYPE_END;
        $e->results_disabled = RESULT_DISABLE_SHOW_SCORE_ONLY;
        $e->expired_time = 25;
        $e->propagate_neg = 1;
        $e->saveCorrectAnswers = 2;
        $e->review_answers = true;
        $e->randomByCat = 0;
        $e->text_when_finished = 'x2 ok';
        $e->text_when_finished_failure = 'x2 ko';
        $e->display_category_name = 1;
        $e->pass_percentage = 60;
        $e->start_time = '2030-01-02 03:04:05';
        $e->end_time = '2030-02-03 04:05:06';
        $id = (int) $e->save();

        $row = $this->quizRow($id);
        $this->assertSame('x2 description', $row['description']);
        $this->assertSame(ONE_PER_PAGE, (int) $row['type']);
        $this->assertSame(3, (int) $row['random']);
        $this->assertSame(1, (int) $row['random_answers']);
        $this->assertSame(4, (int) $row['max_attempt']);
        $this->assertSame(EXERCISE_FEEDBACK_TYPE_END, (int) $row['feedback_type']);
        $this->assertSame(RESULT_DISABLE_SHOW_SCORE_ONLY, (int) $row['results_disabled']);
        $this->assertSame(25, (int) $row['expired_time']);
        $this->assertSame(1, (int) $row['propagate_neg']);
        $this->assertSame(2, (int) $row['save_correct_answers']);
        $this->assertSame(1, (int) $row['review_answers']);
        $this->assertSame('x2 ok', $row['text_when_finished']);
        $this->assertSame('x2 ko', $row['text_when_finished_failure']);
        $this->assertSame(1, (int) $row['display_category_name']);
        $this->assertSame(60, (int) $row['pass_percentage']);
        $this->assertSame('2030-01-02 03:04:05', $row['start_time']);
        $this->assertSame('2030-02-03 04:05:06', $row['end_time']);

        $back = $this->reread($id);
        $this->assertSame('x2_full', $back->selectTitle());
        $this->assertSame(3, (int) $back->random);
        $this->assertSame(4, (int) $back->attempts);
        $this->assertSame(60, (int) $back->pass_percentage);
        $this->assertSame('2030-01-02 03:04:05', (string) $back->start_time);
    }

    public function testSaveUpdatesTheSameRowWithoutNewNodeOrLink(): void
    {
        $e = $this->savedExercise('x2_before');
        $id = $e->getId();
        $nodeId = (int) $this->quizRow($id)['resource_node_id'];
        $nodes = (int) $this->conn->fetchOne('SELECT COUNT(*) FROM resource_node');
        $quizzes = (int) $this->conn->fetchOne('SELECT COUNT(*) FROM c_quiz');

        $again = $this->reread($id);
        $again->updateTitle('x2_after');
        $again->attempts = 7;
        $this->assertSame($id, (int) $again->save(), 'update returns the same id');

        $this->assertSame($quizzes, (int) $this->conn->fetchOne('SELECT COUNT(*) FROM c_quiz'));
        $this->assertSame($nodes, (int) $this->conn->fetchOne('SELECT COUNT(*) FROM resource_node'));
        $row = $this->quizRow($id);
        $this->assertSame('x2_after', $row['title']);
        $this->assertSame(7, (int) $row['max_attempt']);
        $this->assertSame($nodeId, (int) $row['resource_node_id']);
        $this->assertSame(1, $this->countLinks($nodeId));
        $this->assertSame(
            'x2_after',
            $this->conn->fetchOne('SELECT title FROM resource_node WHERE id = ?', [$nodeId]),
            'updateNodeForResource keeps the node title in sync'
        );
    }

    public function testDirectOrPopupFeedbackForcesResultsEnabled(): void
    {
        foreach ([EXERCISE_FEEDBACK_TYPE_DIRECT, EXERCISE_FEEDBACK_TYPE_POPUP] as $fb) {
            $e = $this->savedExercise('x2_fb_'.$fb, [
                'feedback_type' => $fb,
                'results_disabled' => RESULT_DISABLE_NO_SCORE_AND_EXPECTED_ANSWERS,
            ]);
            $this->assertSame(0, (int) $this->quizRow($e->getId())['results_disabled'], 'feedback type '.$fb);
        }
        $e = $this->savedExercise('x2_fb_end', [
            'feedback_type' => EXERCISE_FEEDBACK_TYPE_END,
            'results_disabled' => RESULT_DISABLE_NO_SCORE_AND_EXPECTED_ANSWERS,
        ]);
        $this->assertSame(
            RESULT_DISABLE_NO_SCORE_AND_EXPECTED_ANSWERS,
            (int) $this->quizRow($e->getId())['results_disabled']
        );
    }

    public function testEmptyStartAndEndTimeAreStoredAsNull(): void
    {
        $e = $this->savedExercise('x2_dates');
        $row = $this->quizRow($e->getId());
        $this->assertNull($row['start_time']);
        $this->assertNull($row['end_time']);
    }

    public function testNotificationsAreStoredOnlyWhenThePerExerciseSettingIsOn(): void
    {
        $this->setSetting('exercise.allow_notification_setting_per_exercise', 'false');
        $e = $this->savedExercise('x2_notif_off');
        $e->setNotifications([1, 3]);
        $e->save();
        $this->assertEmpty($this->quizRow($e->getId())['notifications']);

        $this->setSetting('exercise.allow_notification_setting_per_exercise', 'true');
        $e->save();
        $this->assertSame('1,3', $this->quizRow($e->getId())['notifications']);
        $this->assertSame(['1', '3'], $this->reread($e->getId())->getNotifications());

        // PINNED QUIRK: an emptied selection is "not provided": the stored value is never cleared.
        $e->setNotifications([]);
        $e->save();
        $this->assertSame('1,3', $this->quizRow($e->getId())['notifications']);
    }

    public function testQuizCategoryIsStoredOnlyWhenTheSettingIsOn(): void
    {
        $this->conn->insert('c_quiz_category', ['c_id' => 1, 'title' => 'x2_quiz_category', 'position' => 0, 'created_at' => '2020-01-01 00:00:00', 'updated_at' => '2020-01-01 00:00:00']);
        $catId = (int) $this->conn->lastInsertId();
        $this->setSetting('exercise.allow_exercise_categories', 'false');
        $e = $this->savedExercise('x2_qcat_off', ['quizCategoryId' => $catId]);
        $this->assertNull($this->quizRow($e->getId())['quiz_category_id']);

        $this->setSetting('exercise.allow_exercise_categories', 'true');
        $e->save();
        $this->assertSame($catId, (int) $this->quizRow($e->getId())['quiz_category_id']);
    }

    public function testSavePersistsCategoriesOfTheExercise(): void
    {
        $catA = $this->makeCategory('x2_cat_a');
        $e = $this->savedExercise('x2_cats');
        $e->categories = [$catA => 2];
        $e->save();
        $this->assertSame([$catA], array_keys($e->getCategoriesInExercise()));

        // a later save() with the form not providing categories (null) leaves them alone
        $e->categories = null;
        $e->save();
        $this->assertSame([$catA], array_keys($e->getCategoriesInExercise()));
    }

    public function testPageResultConfigurationIsWrittenToTheQuizRow(): void
    {
        $this->setSetting('exercise.allow_quiz_results_page_config', 'true');
        $e = $this->savedExercise('x2_pageconf');
        $e->setPageResultConfiguration(['hide_total_score' => '1', 'hide_expected_answer' => '1']);
        $e->save();
        $stored = json_decode($this->quizRow($e->getId())['page_result_configuration'], true);
        $this->assertSame('1', $stored['hide_total_score']);
        $this->assertSame('1', $stored['hide_expected_answer']);
        $this->assertSame('', $stored['hide_question_score'], 'unchecked options are stored as empty strings');
    }

    public function testPageResultConfigurationIsIgnoredWhenTheSettingIsOff(): void
    {
        $this->setSetting('exercise.allow_quiz_results_page_config', 'false');
        $e = $this->savedExercise('x2_pageconf_off');
        $e->setPageResultConfiguration(['hide_total_score' => '1']);
        $this->assertNull($e->pageResultConfiguration, 'nothing is staged when the feature is off');
        $this->assertSame([], $e->getPageResultConfiguration());
        $this->assertNull($e->getPageConfigurationAttribute('hide_total_score'));
        $e->save();
        $this->assertSame('[]', $this->quizRow($e->getId())['page_result_configuration']);
    }

    public function testPageResultConfigurationAttributeAndDefaults(): void
    {
        $this->setSetting('exercise.allow_quiz_results_page_config', 'true');
        $e = $this->newExercise();
        $e->setPageResultConfiguration(['hide_question_score' => 1]);
        $this->assertSame(1, $e->getPageConfigurationAttribute('hide_question_score'));
        $this->assertNull($e->getPageConfigurationAttribute('unknown_option'));
        $this->assertSame(
            ['hide_expected_answer', 'hide_question_score', 'hide_total_score', 'hide_category_table', 'hide_correct_answered_questions'],
            array_keys($e->getPageResultConfiguration())
        );

        $defaults = ['title' => 'x'];
        $e->setPageResultConfigurationDefaults($defaults);
        $this->assertSame(1, $defaults['hide_question_score']);
        $this->assertSame('x', $defaults['title']);

        $empty = [];
        $e->setPageResultConfigurationDefaults($empty);
        $this->assertSame([], $empty, 'no defaults array: nothing is merged');
    }

    public function testResultsPageConfigurationDrivesTheQuestionRibbonAndExpectedColumn(): void
    {
        $this->setSetting('exercise.allow_quiz_results_page_config', 'true');
        $e = $this->newExercise();
        $e->results_disabled = RESULT_DISABLE_SHOW_SCORE_AND_EXPECTED_ANSWERS;
        $this->assertTrue($e->showExpectedChoiceColumn());
        $this->assertNotSame('', $e->getQuestionRibbon('success', 'Right', '1/1', ['used' => 1, 'missing' => 1]));

        $e->setPageResultConfiguration(['hide_expected_answer' => 1, 'hide_question_score' => 1]);
        $this->assertFalse($e->showExpectedChoiceColumn());
        $this->assertSame('', $e->getQuestionRibbon('success', 'Right', '1/1', ['used' => 1, 'missing' => 1]));

        $e->forceShowExpectedChoiceColumn = true;
        $this->assertTrue($e->showExpectedChoiceColumn(), 'the force flag wins over the configuration');
    }

    // ---------------------------------------------------------------- Exercise::delete()

    public function testDeleteSoftDeletesTheLinkButKeepsTheQuizRowAndDetachesQuestions(): void
    {
        $e = $this->savedExercise('x2_del');
        $id = $e->getId();
        $nodeId = (int) $this->quizRow($id)['resource_node_id'];
        $q1 = $this->makeQuestion('x2_del_q1');
        $q2 = $this->makeQuestion('x2_del_q2');
        $this->link($id, $q1, 1);
        $this->link($id, $q2, 2);
        $other = $this->savedExercise('x2_del_other');
        $this->link($other->getId(), $q2, 1);

        $this->assertTrue($this->reread($id)->delete());

        $this->assertSame(0, $this->countLinks($nodeId), 'no live resource link left');
        $this->assertSame(1, $this->countLinks($nodeId, false), 'the link is soft-deleted, not removed');
        $this->assertSame('x2_del', $this->quizRow($id)['title'], 'the c_quiz row itself stays');
        $this->assertSame(
            0,
            (int) $this->conn->fetchOne('SELECT COUNT(*) FROM c_quiz_rel_question WHERE quiz_id = ?', [$id]),
            'questions are detached from the deleted test'
        );
        $this->assertSame(
            1,
            (int) $this->conn->fetchOne('SELECT COUNT(*) FROM c_quiz_rel_question WHERE quiz_id = ?', [$other->getId()]),
            'the other test keeps its question'
        );
        $this->assertSame(
            2,
            (int) $this->conn->fetchOne('SELECT COUNT(*) FROM c_quiz_question WHERE iid IN (?, ?)', [$q1, $q2]),
            'orphan question cleanup is off by default'
        );
    }

    public function testDeleteRemovesOrphanQuestionsOnlyWhenTheSettingIsOn(): void
    {
        $this->setSetting('exercise.quiz_question_delete_automatically_when_deleting_exercise', 'true');
        $e = $this->savedExercise('x2_orphan');
        $q1 = $this->makeQuestion('x2_orphan_only_here');
        $q2 = $this->makeQuestion('x2_orphan_shared');
        $this->link($e->getId(), $q1, 1);
        $this->link($e->getId(), $q2, 2);
        $other = $this->savedExercise('x2_orphan_other');
        $this->link($other->getId(), $q2, 1);

        $this->assertTrue($this->reread($e->getId())->delete());

        $this->assertFalse(
            (bool) $this->conn->fetchOne('SELECT iid FROM c_quiz_question WHERE iid = ?', [$q1]),
            'a question used by this test only is deleted'
        );
        $this->assertTrue(
            (bool) $this->conn->fetchOne('SELECT iid FROM c_quiz_question WHERE iid = ?', [$q2]),
            'a question still used by another test survives'
        );
    }

    public function testDeleteIsRefusedForTeachersWhenAccessIsLimitedAndForUnknownQuizzes(): void
    {
        $e = $this->savedExercise('x2_del_limit');
        $nodeId = (int) $this->quizRow($e->getId())['resource_node_id'];

        $this->setSetting('exercise.limit_exercise_teacher_access', 'true');
        $this->assertTrue($this->reread($e->getId())->delete(), 'a platform admin is never limited');
        $this->assertSame(0, $this->countLinks($nodeId));

        $e2 = $this->savedExercise('x2_del_limit2');
        $node2 = (int) $this->quizRow($e2->getId())['resource_node_id'];
        $this->loginToken(false);
        $this->assertFalse($this->reread($e2->getId())->delete(), 'a non-admin is refused when the access is limited');
        $this->assertSame(1, $this->countLinks($node2), 'nothing was touched');

        $this->setSetting('exercise.limit_exercise_teacher_access', 'false');
        $this->loginToken(true);
        $ghost = new Exercise();
        $ghost->iId = 999999999;
        $this->assertFalse($ghost->delete(), 'a quiz id that does not exist');
    }

    // ---------------------------------------------------------------- Exercise::copyExercise()

    public function testCopyExerciseDuplicatesTheQuizItsQuestionsAndAnswers(): void
    {
        $src = $this->savedExercise('x2_src', ['attempts' => 3, 'pass_percentage' => 55, 'description' => 'x2 src desc']);
        $q1 = $this->makeQuestion('x2_copy_q1', UNIQUE_ANSWER, 2.0, ['feedback' => 'x2 fb']);
        $q2 = $this->makeQuestion('x2_copy_q2', FREE_ANSWER, 5.0);
        $this->link($src->getId(), $q1, 1);
        $this->link($src->getId(), $q2, 2);
        $this->conn->insert('c_quiz_answer', [
            'question_id' => $q1, 'answer' => 'x2_ans_right', 'correct' => 1, 'comment' => 'x2 c',
            'ponderation' => 2, 'position' => 1,
        ]);
        $this->conn->insert('c_quiz_answer', [
            'question_id' => $q1, 'answer' => 'x2_ans_wrong', 'correct' => 0, 'comment' => '',
            'ponderation' => 0, 'position' => 2,
        ]);
        $quizzes = (int) $this->conn->fetchOne('SELECT COUNT(*) FROM c_quiz');

        $copy = $this->reread($src->getId());
        $copy->copyExercise();

        $newId = $copy->getId();
        $this->assertNotSame($src->getId(), $newId);
        $this->assertSame($quizzes + 1, (int) $this->conn->fetchOne('SELECT COUNT(*) FROM c_quiz'));
        $row = $this->quizRow($newId);
        $this->assertSame('x2_src - '.get_lang('Copy'), $row['title']);
        $this->assertSame(3, (int) $row['max_attempt']);
        $this->assertSame(55, (int) $row['pass_percentage']);
        $this->assertSame('x2 src desc', $row['description']);
        $this->assertNotSame(
            $this->quizRow($src->getId())['resource_node_id'],
            $row['resource_node_id'],
            'the copy has its own resource node'
        );
        $this->assertSame('x2_src', $this->quizRow($src->getId())['title'], 'the source is untouched');

        $rel = $this->conn->fetchAllAssociative(
            'SELECT r.question_id, r.question_order, q.question, q.type, q.ponderation FROM c_quiz_rel_question r
             JOIN c_quiz_question q ON q.iid = r.question_id WHERE r.quiz_id = ? ORDER BY r.question_order',
            [$newId]
        );
        $this->assertSame(['x2_copy_q1', 'x2_copy_q2'], array_column($rel, 'question'));
        $this->assertSame([1, 2], array_map('intval', array_column($rel, 'question_order')));
        $this->assertSame([UNIQUE_ANSWER, FREE_ANSWER], array_map('intval', array_column($rel, 'type')));
        $this->assertNotContains($q1, array_map('intval', array_column($rel, 'question_id')), 'questions are copies');
        $this->assertNotContains($q2, array_map('intval', array_column($rel, 'question_id')));

        $answers = $this->conn->fetchAllAssociative(
            'SELECT answer, correct, ponderation, position FROM c_quiz_answer WHERE question_id = ? ORDER BY position',
            [$rel[0]['question_id']]
        );
        $this->assertSame(['x2_ans_right', 'x2_ans_wrong'], array_column($answers, 'answer'));
        $this->assertSame([1, 0], array_map('intval', array_column($answers, 'correct')));
        $this->assertSame(
            2,
            (int) $this->conn->fetchOne('SELECT COUNT(*) FROM c_quiz_answer WHERE question_id = ?', [$q1]),
            'the source answers are still there'
        );
    }

    public function testCopyExerciseCopiesQuestionCategoriesAndTheCategoryCounts(): void
    {
        $cat = $this->makeCategory('x2_copy_cat');
        $src = $this->savedExercise('x2_src_cat');
        $q1 = $this->makeQuestion('x2_cc_q1');
        $q2 = $this->makeQuestion('x2_cc_q2');
        $this->link($src->getId(), $q1, 1);
        $this->link($src->getId(), $q2, 2);
        $this->categorize($q1, $cat);
        $this->categorize($q2, $cat);
        $src->saveCategoriesInExercise([$cat => 2]);

        $copy = $this->reread($src->getId());
        $copy->copyExercise();
        $newId = $copy->getId();

        $this->assertSame(
            2,
            (int) $this->conn->fetchOne(
                'SELECT COUNT(*) FROM c_quiz_question_rel_category qrc
                 JOIN c_quiz_rel_question r ON r.question_id = qrc.question_id
                 WHERE r.quiz_id = ? AND qrc.category_id = ?',
                [$newId, $cat]
            ),
            'both copied questions are in the category'
        );
        $this->assertSame(
            2,
            (int) $this->conn->fetchOne(
                'SELECT count_questions FROM c_quiz_rel_category WHERE exercise_id = ? AND category_id = ?',
                [$newId, $cat]
            )
        );
        $this->assertSame(
            2,
            (int) $this->conn->fetchOne(
                'SELECT count_questions FROM c_quiz_rel_category WHERE exercise_id = ? AND category_id = ?',
                [$src->getId(), $cat]
            ),
            'the source keeps its own category row'
        );
    }

    public function testCopyOfAnEmptyExerciseStillCreatesTheNewQuiz(): void
    {
        $src = $this->savedExercise('x2_src_empty');
        $copy = $this->reread($src->getId());
        $copy->copyExercise();

        $this->assertNotSame($src->getId(), $copy->getId());
        $this->assertSame('x2_src_empty - '.get_lang('Copy'), $this->quizRow($copy->getId())['title']);
        $this->assertSame(
            0,
            (int) $this->conn->fetchOne('SELECT COUNT(*) FROM c_quiz_rel_question WHERE quiz_id = ?', [$copy->getId()])
        );
    }

    // ---------------------------------------------------------------- Question::save() / duplicate()

    private function newQuestion(string $title, int $type = UNIQUE_ANSWER, float $weight = 2.0): Question
    {
        $q = Question::getInstance($type);
        $q->updateTitle($title);
        $q->updateWeighting($weight);
        $q->updateDescription('x2 desc');

        return $q;
    }

    public function testSaveNewQuestionCreatesRowNodeLinkAndPositionsAfterTheMaximum(): void
    {
        $e = $this->savedExercise('x2_qsave');
        $first = $this->newQuestion('x2_q_first');
        $first->save($e);
        $this->assertGreaterThan(0, (int) $first->id);

        $row = $this->conn->fetchAssociative('SELECT * FROM c_quiz_question WHERE iid = ?', [$first->id]);
        $this->assertSame('x2_q_first', $row['question']);
        $this->assertSame(UNIQUE_ANSWER, (int) $row['type']);
        $this->assertSame('x2 desc', $row['description']);
        $this->assertEqualsWithDelta(2.0, (float) $row['ponderation'], 0.001);
        $this->assertSame(1, (int) $row['position'], 'first question of the quiz');
        $this->assertSame(1, $this->countLinks((int) $row['resource_node_id']));

        $second = $this->newQuestion('x2_q_second', MULTIPLE_ANSWER, 3.0);
        $second->save($e);
        $this->assertSame(
            2,
            (int) $this->conn->fetchOne('SELECT position FROM c_quiz_question WHERE iid = ?', [$second->id]),
            'max(position of this quiz) + 1'
        );

        $rel = $this->conn->fetchAllAssociative(
            'SELECT question_id, question_order FROM c_quiz_rel_question WHERE quiz_id = ? ORDER BY question_order',
            [$e->getId()]
        );
        $this->assertSame([(int) $first->id, (int) $second->id], array_map('intval', array_column($rel, 'question_id')));
        $this->assertSame([1, 2], array_map('intval', array_column($rel, 'question_order')));
    }

    public function testSaveNewHotspotQuestionCreatesItsFirstAnswer(): void
    {
        $e = $this->savedExercise('x2_hs');
        $q = $this->newQuestion('x2_hotspot', HOT_SPOT, 10.0);
        $q->save($e);

        $a = $this->conn->fetchAllAssociative('SELECT * FROM c_quiz_answer WHERE question_id = ?', [$q->id]);
        $this->assertCount(1, $a);
        $this->assertSame('0;0|0|0', $a[0]['hotspot_coordinates']);
        $this->assertSame('square', $a[0]['hotspot_type']);
        $this->assertSame(10.0, (float) $a[0]['ponderation']);

        $d = $this->newQuestion('x2_delineation', HOT_SPOT_DELINEATION, 10.0);
        $d->save($e);
        $this->assertSame(
            'delineation',
            $this->conn->fetchOne('SELECT hotspot_type FROM c_quiz_answer WHERE question_id = ?', [$d->id])
        );

        $f = $this->newQuestion('x2_free', FREE_ANSWER, 1.0);
        $f->save($e);
        $this->assertSame(
            0,
            (int) $this->conn->fetchOne('SELECT COUNT(*) FROM c_quiz_answer WHERE question_id = ?', [$f->id]),
            'other types get no automatic answer'
        );
    }

    public function testSaveNewQuestionWithoutExerciseOnlyCreatesTheQuestion(): void
    {
        $q = $this->newQuestion('x2_orphan_q');
        $e = new Exercise();
        $q->save($e);

        $this->assertGreaterThan(0, (int) $q->id);
        $this->assertSame(
            0,
            (int) $this->conn->fetchOne('SELECT COUNT(*) FROM c_quiz_rel_question WHERE question_id = ?', [$q->id])
        );
    }

    public function testSaveOfAnExistingQuestionUpdatesInPlace(): void
    {
        $e = $this->savedExercise('x2_qupd');
        $q = $this->newQuestion('x2_q_old');
        $q->save($e);
        $id = (int) $q->id;
        $nodes = (int) $this->conn->fetchOne('SELECT COUNT(*) FROM resource_node');

        $read = Question::read($id);
        $read->updateTitle('x2_q_new');
        $read->updateWeighting(9.0);
        $read->save($e);

        $row = $this->conn->fetchAssociative('SELECT * FROM c_quiz_question WHERE iid = ?', [$id]);
        $this->assertSame('x2_q_new', $row['question']);
        $this->assertSame(9.0, (float) $row['ponderation']);
        $this->assertSame($nodes, (int) $this->conn->fetchOne('SELECT COUNT(*) FROM resource_node'));
        $this->assertSame(
            1,
            (int) $this->conn->fetchOne('SELECT COUNT(*) FROM c_quiz_rel_question WHERE question_id = ?', [$id]),
            'saving again does not re-link the question'
        );
    }

    public function testDuplicateCopiesFieldsAndCreatesANewNodeInTheSameCourse(): void
    {
        $src = $this->makeQuestion('x2_dup_src', UNIQUE_ANSWER, 4.0, [
            'description' => 'x2 dup desc', 'level' => 3, 'feedback' => 'x2 dup fb', 'extra' => 'x2 extra',
        ]);

        $origin = Question::read($src);
        $this->assertNotFalse($origin);
        $newId = (int) $origin->duplicate();

        $this->assertGreaterThan(0, $newId);
        $this->assertNotSame($src, $newId);
        $row = $this->conn->fetchAssociative('SELECT * FROM c_quiz_question WHERE iid = ?', [$newId]);
        $this->assertSame('x2_dup_src', $row['question']);
        $this->assertSame('x2 dup desc', $row['description']);
        $this->assertSame(UNIQUE_ANSWER, (int) $row['type']);
        $this->assertSame(4.0, (float) $row['ponderation']);
        $this->assertSame(3, (int) $row['level']);
        $this->assertSame('x2 dup fb', $row['feedback']);
        $this->assertSame('x2 extra', $row['extra']);
        $this->assertNotEmpty($row['resource_node_id']);
        $this->assertNotSame(
            $this->conn->fetchOne('SELECT resource_node_id FROM c_quiz_question WHERE iid = ?', [$src]),
            $row['resource_node_id']
        );
        $this->assertSame(1, $this->countLinks((int) $row['resource_node_id']));
        $this->assertSame(
            0,
            (int) $this->conn->fetchOne('SELECT COUNT(*) FROM c_quiz_rel_question WHERE question_id = ?', [$newId]),
            'duplicate() does not attach the copy to any test'
        );
    }

    public function testDuplicateOfAQuestionWithOptions(): void
    {
        $src = $this->makeQuestion('x2_dup_opts', MULTIPLE_ANSWER_TRUE_FALSE, 4.0);
        $this->conn->insert('c_quiz_question_option', ['question_id' => $src, 'title' => 'x2_opt_true', 'position' => 1]);
        $this->conn->insert('c_quiz_question_option', ['question_id' => $src, 'title' => 'x2_opt_false', 'position' => 2]);

        // Database::select() rows carry the numeric column indexes too: they must not reach the INSERT.
        $newId = (int) Question::read($src)->duplicate();

        $this->assertGreaterThan(0, $newId);
        $options = $this->conn->fetchAllAssociative(
            'SELECT title, position FROM c_quiz_question_option WHERE question_id = ? ORDER BY position',
            [$newId]
        );
        $this->assertSame(['x2_opt_true', 'x2_opt_false'], array_column($options, 'title'));
        $this->assertSame(
            2,
            (int) $this->conn->fetchOne('SELECT COUNT(*) FROM c_quiz_question_option WHERE question_id = ?', [$src]),
            'the source options are untouched'
        );
    }

    public function testDuplicateDoesNotCopyTheMandatoryFlag(): void
    {
        $this->setSetting('exercise.allow_mandatory_question_in_category', 'true');
        $src = $this->makeQuestion('x2_dup_mand', UNIQUE_ANSWER, 1.0, ['mandatory' => 1]);
        $this->categorize($src, $this->makeCategory('x2_dup_mand_cat'));
        $this->assertSame(1, Question::read($src)->mandatory);

        $newId = (int) Question::read($src)->duplicate();

        // PINNED QUIRK (bug): the copy loses the "mandatory" flag, so a copied test draws it like any other question.
        $this->assertSame(
            0,
            (int) $this->conn->fetchOne('SELECT mandatory FROM c_quiz_question WHERE iid = ?', [$newId])
        );
    }

    public function testDuplicateSetsTheCreatorWithoutASecurityToken(): void
    {
        $src = $this->makeQuestion('x2_dup_notoken');
        $origin = Question::read($src);
        $this->loginToken(false);

        // CLI, cron and import code has no Security token: save() sets the creator explicitly, so must duplicate().
        $newId = (int) $origin->duplicate();

        $this->assertGreaterThan(0, $newId);
        $nodeId = (int) $this->conn->fetchOne('SELECT resource_node_id FROM c_quiz_question WHERE iid = ?', [$newId]);
        $this->assertSame(
            (int) $this->getUser('admin')->getId(),
            (int) $this->conn->fetchOne('SELECT creator_id FROM resource_node WHERE id = ?', [$nodeId])
        );
    }

    public function testDuplicateWithoutCourseInfoReturnsFalse(): void
    {
        $q = Question::getInstance(UNIQUE_ANSWER);
        $q->course = [];
        $this->assertFalse($q->duplicate());
    }

    // ---------------------------------------------------------------- in-memory list

    public function testAddToListUsesMaxPositionPlusOneAndRefusesDuplicates(): void
    {
        $e = $this->newExercise();
        $this->assertTrue($e->addToList(11));
        $this->assertSame([1 => 11], $e->questionList);
        $e->questionList = [1 => 11, 5 => 55];
        $this->assertTrue($e->addToList(66));
        $this->assertSame([1 => 11, 5 => 55, 6 => 66], $e->questionList, 'max(key)+1, holes are not refilled');
        $this->assertFalse($e->addToList(55), 'already listed');
        $this->assertSame(3, $e->selectNbrQuestions());
    }

    public function testRemoveFromListDecrementsTheRandomLimitAndSaves(): void
    {
        $e = $this->savedExercise('x2_rm', ['random' => 2]);
        $e->questionList = [1 => 11, 2 => 22, 3 => 33];
        $this->assertTrue($e->removeFromList(22));
        $this->assertSame([1 => 11, 3 => 33], $e->questionList);
        $this->assertSame(1, (int) $e->random, 'the random limit follows the list size');
        $this->assertSame(1, (int) $this->quizRow($e->getId())['random'], 'and is persisted by save()');

        $this->assertFalse($e->removeFromList(999), 'unknown question');
    }

    public function testRemoveFromListKeepsTheRandomLimitWhenRandomByCategoryOrOutOfRange(): void
    {
        $e = $this->savedExercise('x2_rm_cat', ['random' => 2, 'randomByCat' => 1]);
        $e->questionList = [1 => 11, 2 => 22];
        $e->removeFromList(11);
        $this->assertSame(2, (int) $e->random, 'random-by-category: the limit is a per-category count');

        $e2 = $this->savedExercise('x2_rm_big', ['random' => 5]);
        $e2->questionList = [1 => 11, 2 => 22];
        $e2->removeFromList(11);
        $this->assertSame(5, (int) $e2->random, 'fewer questions than the limit: untouched');
    }

    // ---------------------------------------------------------------- selection (random / by category)

    /**
     * @return array{0: Exercise, 1: int[]} quiz with 6 questions q0..q5 in order 1..6
     */
    private function quizOfSix(array $props = []): array
    {
        $e = $this->savedExercise('x2_six', $props);
        $ids = [];
        for ($i = 0; $i < 6; $i++) {
            $ids[$i] = $this->makeQuestion('x2_six_q'.$i);
            $this->link($e->getId(), $ids[$i], $i + 1);
        }

        return [$this->reread($e->getId()), $ids];
    }

    public function testRandomListHonoursTheLimitAndAdminViewIgnoresIt(): void
    {
        [$e, $ids] = $this->quizOfSix(['random' => 3, 'questionSelectionType' => EX_Q_SELECTION_RANDOM]);

        $picked = $e->selectQuestionList(true);
        $this->assertCount(3, $picked, 'random=3 picks three questions');
        $this->assertCount(3, array_unique($picked));
        $this->assertSame([], array_diff($picked, $ids), 'all picks belong to the quiz');

        $this->assertSame($ids, array_values($e->selectQuestionList(true, true)), 'admin view: everything, in order');

        $e->random = -1;
        $this->assertCount(6, $e->selectQuestionList(true), 'random=-1 shuffles all questions');
        $this->assertEqualsCanonicalizing($ids, $e->selectQuestionList(true));

        $e->random = 0;
        $this->assertSame($ids, array_values($e->selectQuestionList(true)), 'random=0 is the ordered list');
    }

    public function testRandomSelectionNeedsAtLeastTwoQuestions(): void
    {
        $e = $this->savedExercise('x2_one', ['random' => 5, 'questionSelectionType' => EX_Q_SELECTION_RANDOM]);
        $q = $this->makeQuestion('x2_one_q');
        $this->link($e->getId(), $q, 1);
        $this->assertSame([1 => $q], $this->reread($e->getId())->selectQuestionList(true));
    }

    public function testGetValidatedQuestionListWithoutRandomByCategory(): void
    {
        [$e, $ids] = $this->quizOfSix(['random' => 2]);
        $this->assertCount(2, $e->get_validated_question_list());

        $e->random = 0;
        $this->assertSame($e->selectQuestionList(), $e->get_validated_question_list(), 'not random: the loaded list');
    }

    public function testRandomByCategoryTakesRandomPerCategoryQuestions(): void
    {
        [$e, $ids] = $this->quizOfSix(['random' => 2, 'randomByCat' => 1]);
        $catA = $this->makeCategory('x2_rc_a');
        $catB = $this->makeCategory('x2_rc_b');
        foreach ([0, 1, 2] as $i) {
            $this->categorize($ids[$i], $catA);
        }
        foreach ([3, 4] as $i) {
            $this->categorize($ids[$i], $catB);
        }
        $list = $e->get_validated_question_list();

        $byCat = [$catA => 0, $catB => 0];
        foreach ($list as $qid) {
            $byCat[$qid === $ids[3] || $qid === $ids[4] ? $catB : $catA]++;
        }
        $this->assertSame(2, $byCat[$catA], 'two out of the three questions of A');
        $this->assertSame(2, $byCat[$catB], 'both questions of B (fewer than the limit)');
        $this->assertNotContains($ids[5], $list, 'PINNED QUIRK: a question with no category is never picked');
    }

    public function testRandomByCategoryWithoutRandomLimitReturnsNothing(): void
    {
        [$e] = $this->quizOfSix(['random' => 0, 'randomByCat' => 1]);
        $this->assertSame([], $e->get_validated_question_list(), 'PINNED QUIRK (documented "Should not happen")');
    }

    private function categorizedQuiz(int $type): array
    {
        [$e, $ids] = $this->quizOfSix(['questionSelectionType' => $type]);
        $catB = $this->makeCategory('x2_sel_b');
        $catA = $this->makeCategory('x2_sel_a');
        // B is created first (lower id) but sorts after A by title
        $this->categorize($ids[0], $catB);
        $this->categorize($ids[1], $catA);
        $this->categorize($ids[2], $catB);
        $this->categorize($ids[3], $catA);
        $this->categorize($ids[4], $catA);
        $e->saveCategoriesInExercise([$catB => 2, $catA => 2]);

        return [$this->reread($e->getId()), $ids, $catA, $catB];
    }

    public function testCategoriesOrderedByTitleWithOrderedQuestions(): void
    {
        [$e, $ids, $catA, $catB] = $this->categorizedQuiz(EX_Q_SELECTION_CATEGORIES_ORDERED_QUESTIONS_ORDERED);
        $list = array_values($e->selectQuestionList(true));

        // A (title order) first with its first two questions, then B; q4 (3rd of A) is over the count; q5 has no category
        $this->assertSame([$ids[1], $ids[3], $ids[0], $ids[2]], $list);
        $this->assertSame([$catA, $catB], array_keys($e->categoryWithQuestionList));
        $this->assertSame('x2_sel_a', $e->categoryWithQuestionList[$catA]['category']['name']);
        $this->assertSame([$ids[1], $ids[3]], $e->categoryWithQuestionList[$catA]['question_list']);
    }

    public function testCategorySelectionCountOfMinusOneMeansAllQuestions(): void
    {
        [$e, $ids, $catA, $catB] = $this->categorizedQuiz(EX_Q_SELECTION_CATEGORIES_ORDERED_QUESTIONS_ORDERED);
        $e->saveCategoriesInExercise([$catA => -1, $catB => 1]);
        $list = array_values($this->reread($e->getId())->selectQuestionList(true));
        $this->assertSame([$ids[1], $ids[3], $ids[4], $ids[0]], $list);
    }

    public function testCategoryWithCountZeroIsDroppedFromTheSelection(): void
    {
        [$e, $ids, $catA, $catB] = $this->categorizedQuiz(EX_Q_SELECTION_CATEGORIES_ORDERED_QUESTIONS_ORDERED);
        $e->saveCategoriesInExercise([$catA => 0, $catB => 2]);
        $list = array_values($this->reread($e->getId())->selectQuestionList(true));
        $this->assertSame([$ids[0], $ids[2]], $list);
    }

    public function testRandomQuestionsInsideOrderedCategories(): void
    {
        [$e, $ids, $catA, $catB] = $this->categorizedQuiz(EX_Q_SELECTION_CATEGORIES_ORDERED_QUESTIONS_RANDOM);
        $list = array_values($e->selectQuestionList(true));
        $this->assertCount(4, $list);
        $this->assertEqualsCanonicalizing([$ids[0], $ids[2]], array_slice($list, 2), 'B has exactly two questions');
        $head = array_slice($list, 0, 2);
        $this->assertSame([], array_diff($head, [$ids[1], $ids[3], $ids[4]]), 'two of the three A questions, A first');
    }

    public function testMandatoryQuestionsAreAlwaysPickedWhenTheSettingIsOn(): void
    {
        $this->setSetting('exercise.allow_mandatory_question_in_category', 'true');
        [$e, $ids, $catA] = $this->categorizedQuiz(EX_Q_SELECTION_CATEGORIES_ORDERED_QUESTIONS_RANDOM);
        $this->conn->update('c_quiz_question', ['mandatory' => 1], ['iid' => $ids[4]]);
        $e->saveCategoriesInExercise([$catA => 2]);
        for ($i = 0; $i < 6; $i++) {
            $list = array_values($this->reread($e->getId())->selectQuestionList(true));
            $this->assertContains($ids[4], $list, 'the mandatory question is in every draw');
            $this->assertCount(2, $list);
        }
    }

    // ---------------------------------------------------------------- results access / blocking

    private function setExtra(string $variable, int $quizId, string $value): void
    {
        $fieldId = (int) $this->conn->fetchOne(
            'SELECT id FROM extra_field WHERE variable = ? AND item_type = ?',
            [$variable, ExtraField::EXERCISE_FIELD_TYPE]
        );
        if (0 === $fieldId) {
            $this->conn->insert('extra_field', [
                'item_type' => ExtraField::EXERCISE_FIELD_TYPE,
                'value_type' => ExtraField::FIELD_TYPE_TEXT,
                'variable' => $variable,
                'display_text' => $variable,
                'visible_to_self' => 1,
                'visible_to_others' => 1,
                'changeable' => 1,
                'filter' => 0,
                'created_at' => '2020-01-01 00:00:00',
            ]);
            $fieldId = (int) $this->conn->lastInsertId();
        }
        $this->conn->insert('extra_field_values', [
            'field_id' => $fieldId,
            'item_id' => $quizId,
            'field_value' => $value,
            'created_at' => '2020-01-01 00:00:00',
            'updated_at' => '2020-01-01 00:00:00',
        ]);
    }

    public function testResultAccessWindowIsReadFromTheExtraField(): void
    {
        $e = $this->savedExercise('x2_access');
        $this->assertSame(0, $e->getResultAccess(), 'no extra field value: unlimited');
        $this->assertFalse($e->hasResultsAccess(['exe_date' => gmdate('Y-m-d H:i:s')]));

        $this->setExtra('results_available_for_x_minutes', $e->getId(), '10');
        $this->assertSame(10, $e->getResultAccess());
        // PINNED QUIRK (bug): getResultsAccess() casts the whole extra-field array to int (always 1); it feeds the
        // "You have passed the %s minutes limit" message.
        $this->assertSame(1, $e->getResultsAccess());

        $recent = ['exe_date' => gmdate('Y-m-d H:i:s', time() - 60)];
        $diff = $e->getResultAccessTimeDiff($recent);
        $this->assertGreaterThan(530, $diff);
        $this->assertLessThanOrEqual(540, $diff, 'about 9 minutes left');
        $this->assertTrue($e->hasResultsAccess($recent));

        $old = ['exe_date' => gmdate('Y-m-d H:i:s', time() - 3600)];
        $this->assertSame(0, $e->getResultAccessTimeDiff($old));
        $this->assertFalse($e->hasResultsAccess($old), 'window elapsed');
    }

    public function testBlockedByPercentage(): void
    {
        $e = $this->savedExercise('x2_block');
        $this->assertFalse($e->isBlockedByPercentage([]), 'no attempt');
        $this->assertFalse($e->isBlockedByPercentage(['score' => 1, 'max_score' => 10]), 'no blocking percentage');

        $this->setExtra('blocking_percentage', $e->getId(), '50');
        $this->assertTrue($e->isBlockedByPercentage(['score' => 4, 'max_score' => 10]), '40% <= 50%');
        $this->assertTrue($e->isBlockedByPercentage(['score' => 5, 'max_score' => 10]), 'the limit itself is blocked');
        $this->assertFalse($e->isBlockedByPercentage(['score' => 6, 'max_score' => 10]), '60% > 50%');
        $this->assertTrue($e->isBlockedByPercentage(['score' => 0, 'max_score' => 0]), 'max_score 0 is counted as 1');
        $this->assertTrue($e->isBlockedByPercentage(['score' => 1]), 'no max_score: percentage stays 0');
    }

    // ---------------------------------------------------------------- attempts

    public function testSaveStatTrackExerciseInfoStoresTheAttemptHeader(): void
    {
        $e = $this->savedExercise('x2_track');
        $q1 = $this->makeQuestion('x2_track_q1');
        $q2 = $this->makeQuestion('x2_track_q2');
        $pb = $this->makeQuestion('x2_track_break', PAGE_BREAK);
        $media = $this->makeQuestion('x2_track_media', MEDIA_QUESTION);

        $exeId = (int) $e->save_stat_track_exercise_info(0, 3, 4, 5, [$q1, $pb, $q2, $media], 7.5);

        $row = $this->conn->fetchAssociative('SELECT * FROM track_e_exercises WHERE exe_id = ?', [$exeId]);
        $this->assertSame($e->getId(), (int) $row['exe_exo_id']);
        $this->assertSame(1, (int) $row['c_id']);
        $this->assertSame(1, (int) $row['exe_user_id']);
        $this->assertNull($row['session_id']);
        $this->assertSame($q1.','.$q2, $row['data_tracking'], 'page breaks and media questions are not tracked');
        $this->assertSame(7.5, (float) $row['max_score']);
        $this->assertSame([3, 4, 5], [(int) $row['orig_lp_id'], (int) $row['orig_lp_item_id'], (int) $row['orig_lp_item_view_id']]);
        $this->assertSame('incomplete', $row['status']);
        $this->assertNull($row['expired_time_control'], 'empty clock = no expiry');

        $withClock = (int) $e->save_stat_track_exercise_info(new \DateTime('2030-01-01 10:00:00', new \DateTimeZone('UTC')), 0, 0, 0, [$q1]);
        $this->assertSame(
            '2030-01-01 10:00:00',
            $this->conn->fetchOne('SELECT expired_time_control FROM track_e_exercises WHERE exe_id = ?', [$withClock])
        );
    }

    public function testTrackedIncompleteAttemptIsFoundAndDrivesTheQuestionCount(): void
    {
        $e = $this->savedExercise('x2_track2');
        $qs = [];
        for ($i = 0; $i < 3; $i++) {
            $qs[$i] = $this->makeQuestion('x2_track2_q'.$i);
            $this->link($e->getId(), $qs[$i], $i + 1);
        }
        $e = $this->reread($e->getId());
        $count = new \ReflectionMethod(Exercise::class, 'countQuestionsInExercise');
        $count->setAccessible(true);
        $this->assertSame([], $e->get_stat_track_exercise_info());
        $this->assertSame(3, $count->invoke($e), 'no attempt: the quiz size');

        $e->save_stat_track_exercise_info(0, 0, 0, 0, [$qs[0], $qs[2]], 4);
        $info = $e->get_stat_track_exercise_info();
        $this->assertSame($qs[0].','.$qs[2], $info['data_tracking']);
        $this->assertSame(2, $count->invoke($e), 'the drawn list of the open attempt wins over the quiz size');

        $_REQUEST['learnpath_id'] = 9;
        $this->assertSame([], $e->get_stat_track_exercise_info((int) $_REQUEST['learnpath_id']), 'other LP context');
    }

    // ---------------------------------------------------------------- results page configuration read-back

    public function testReadBringsBackTheStoredPageResultConfiguration(): void
    {
        $this->setSetting('exercise.allow_quiz_results_page_config', 'true');
        $e = $this->savedExercise('x2_pageconf_read');
        $e->setPageResultConfiguration(['hide_total_score' => '1']);
        $e->save();

        $back = $this->reread($e->getId());
        // PINNED QUIRK (bug): read() has the assignment commented out, so the legacy result pages
        // (getPageConfigurationAttribute) never see what the teacher configured.
        $this->assertNull($back->getPageConfigurationAttribute('hide_total_score'));
    }

    // ---------------------------------------------------------------- mail notification selection

    private function messageCount(): int
    {
        return (int) $this->conn->fetchOne('SELECT COUNT(*) FROM message');
    }

    /**
     * @param int[] $options
     */
    private function notifier(array $options): Exercise
    {
        $this->setSetting('exercise.allow_notification_setting_per_exercise', 'true');
        $e = $this->savedExercise('x2_mail_'.implode('', $options));
        $e->setNotifications($options);

        return $e;
    }

    public function testNoNotificationConfiguredSendsNothing(): void
    {
        $e = $this->savedExercise('x2_mail_none');
        $before = $this->messageCount();
        $this->assertFalse($e->send_mail_notification_for_exam('end', [], '', 1, 1.0, 2.0));
        $this->assertSame($before, $this->messageCount());
    }

    public function testNotificationOptionZeroDisablesEverything(): void
    {
        $e = $this->notifier([1, 0]);
        $before = $this->messageCount();
        $this->assertFalse($e->send_mail_notification_for_exam('end', [], '', 1, 1.0, 2.0));
        $this->assertSame($before, $this->messageCount());
    }

    public function testNotificationTypeMustMatchTheConfiguredMoment(): void
    {
        $before = $this->messageCount();
        $this->assertFalse($this->notifier([1])->send_mail_notification_for_exam('start', [], '', 1, 1.0, 2.0), 'end-only, start event');
        $this->assertFalse($this->notifier([2])->send_mail_notification_for_exam('end', [], '', 1, 1.0, 2.0), 'start-only, end event');
        $this->assertSame($before, $this->messageCount());
    }

    public function testEndNotificationMessagesTheTeachersOfTheCourse(): void
    {
        $e = $this->notifier([1]);
        $before = $this->messageCount();
        $e->send_mail_notification_for_exam('end', [], '', 4242, 1.0, 2.0);
        $this->assertSame($before + 1, $this->messageCount(), 'one message for the one teacher of the fixture course');
        $msg = $this->conn->fetchAssociative('SELECT title, content FROM message ORDER BY id DESC LIMIT 1');
        $this->assertSame(get_lang('A learner attempted an exercise'), $msg['title']);
        $this->assertStringContainsString('x2_mail_1', $msg['content']);
        $this->assertStringContainsString('id=4242', $msg['content'], 'the link opens the attempt');
    }

    public function testStartNotificationUsesTheStartSubject(): void
    {
        $e = $this->notifier([2]);
        $before = $this->messageCount();
        $e->send_mail_notification_for_exam('start', [], '', 7, null, null);
        $this->assertSame($before + 1, $this->messageCount());
        $this->assertSame(
            get_lang('Student just started an exercise'),
            $this->conn->fetchOne('SELECT title FROM message ORDER BY id DESC LIMIT 1')
        );
    }

    public function testOpenQuestionOptionOnlyNotifiesWhenAFreeAnswerWasGiven(): void
    {
        $e = $this->notifier([3]);
        $before = $this->messageCount();
        $e->send_mail_notification_for_exam('end', [
            ['question' => 'Q', 'answer' => 'A', 'answer_type' => UNIQUE_ANSWER],
        ], '', 1, 1.0, 2.0);
        $this->assertSame($before, $this->messageCount(), 'no free answer: no message; and option 3 is not the plain end mail');
    }

    public function testOpenQuestionOptionNotifiesWithTheFreeAnswerText(): void
    {
        $e = $this->notifier([3]);
        $before = $this->messageCount();
        $e->send_mail_notification_for_exam('end', [
            ['question' => 'x2 question text', 'answer' => 'x2 free answer text', 'answer_type' => FREE_ANSWER],
            ['question' => 'x2 other', 'answer' => 'x2 chosen', 'answer_type' => UNIQUE_ANSWER],
        ], '', 31, 1.0, 2.0);

        $this->assertSame($before + 1, $this->messageCount());
        $msg = $this->conn->fetchAssociative('SELECT title, content FROM message ORDER BY id DESC LIMIT 1');
        $this->assertSame(get_lang('A learner has answered an open question'), $msg['title']);
        $this->assertStringContainsString('x2 free answer text', $msg['content']);
        $this->assertStringNotContainsString('x2 chosen', $msg['content'], 'only free answers are listed');
        $this->assertStringContainsString('id=31', $msg['content']);

        $e->send_mail_notification_for_exam('end', [
            ['question' => 'q', 'answer' => 'a', 'answer_type' => FREE_ANSWER],
        ], 'learnpath', 32, 1.0, 2.0);
        $lp = (string) $this->conn->fetchOne('SELECT content FROM message ORDER BY id DESC LIMIT 1');
        $this->assertStringNotContainsString('id=32', $lp, 'inside a learning path there is no "check the answer" link');
    }

    // ---------------------------------------------------------------- generateStats (gradebook link statistics)

    private function exe(int $quizId, int $userId, float $score, float $max, string $status, string $date): int
    {
        $this->conn->insert('track_e_exercises', [
            'exe_user_id' => $userId, 'c_id' => 1, 'session_id' => null, 'exe_exo_id' => $quizId,
            'exe_date' => $date, 'score' => $score, 'max_score' => $max, 'user_ip' => '10.0.0.1',
            'status' => $status, 'data_tracking' => '', 'start_date' => $date, 'steps_counter' => 0,
            'orig_lp_id' => 0, 'orig_lp_item_id' => 0, 'exe_duration' => 0, 'orig_lp_item_view_id' => 0,
            'questions_to_check' => '',
        ]);

        return (int) $this->conn->lastInsertId();
    }

    /**
     * @return array{0: Exercise, 1: int} quiz with one 10 points question, and the id of its gradebook link
     */
    private function gradedQuiz(): array
    {
        $e = $this->savedExercise('x2_graded');
        $q = $this->makeQuestion('x2_graded_q', UNIQUE_ANSWER, 10.0);
        $this->link($e->getId(), $q, 1);
        $this->conn->insert('course_rel_user', ['user_id' => 2, 'c_id' => 1, 'relation_type' => 0, 'status' => 5, 'progress' => 0]);
        $this->conn->insert('gradebook_category', [
            'c_id' => 1, 'title' => 'x2_gb_cat', 'weight' => 100, 'visible' => 1, 'locked' => 0,
            'generate_certificates' => 0, 'is_requirement' => 0, 'calculation_mode' => 'weighted_average',
        ]);
        $categoryId = (int) $this->conn->lastInsertId();
        $this->conn->insert('gradebook_link', [
            'ref_id' => (int) $this->quizRow($e->getId())['resource_node_id'], 'c_id' => 1, 'category_id' => $categoryId,
            'type' => LINK_EXERCISE, 'created_at' => '2020-01-01 00:00:00', 'weight' => 10, 'visible' => 1, 'locked' => 0,
        ]);

        $linkId = (int) $this->conn->lastInsertId();

        return [$this->reread($e->getId()), $linkId];
    }

    public function testGenerateStatsIsOffUnlessTheGradebookStatsSettingIsOn(): void
    {
        [$e, $linkId] = $this->gradedQuiz();
        $this->setSetting('gradebook.allow_gradebook_stats', 'false');
        $this->assertFalse($e->generateStats($e->getId(), api_get_course_info(), 0));
        $this->assertNull($this->conn->fetchOne('SELECT best_score FROM gradebook_link WHERE id = ?', [$linkId]));

        $this->setSetting('gradebook.allow_gradebook_stats', 'true');
        $this->assertFalse($e->generateStats($e->getId(), [], 0), 'no course');
    }

    public function testGenerateStatsStoresBestAverageAndLatestScorePerStudent(): void
    {
        $this->setSetting('gradebook.allow_gradebook_stats', 'true');
        [$e, $linkId] = $this->gradedQuiz();
        $id = $e->getId();
        $this->exe($id, 2, 3.0, 10.0, 'completed', '2020-01-01 10:00:00');
        $this->exe($id, 2, 8.0, 10.0, 'completed', '2020-01-02 10:00:00'); // latest by exe_id: the one that counts
        $this->exe($id, 2, 9.5, 10.0, 'incomplete', '2020-01-03 10:00:00'); // not finished
        $this->exe($id, 1, 10.0, 10.0, 'completed', '2020-01-04 10:00:00'); // the admin is not a student of the course

        $e->generateStats($id, api_get_course_info(), 0);

        $link = $this->conn->fetchAssociative('SELECT * FROM gradebook_link WHERE id = ?', [$linkId]);
        $this->assertSame(8.0, (float) $link['best_score']);
        $this->assertSame(8.0, (float) $link['average_score'], 'sum of the latest scores / number of students');
        $this->assertSame(10.0, (float) $link['score_weight'], 'the max score of the test');
        $this->assertEquals([2 => 8], json_decode($link['user_score_list'], true));
    }

    public function testGenerateStatsAveragesOverEveryStudentOfTheCourse(): void
    {
        $this->setSetting('gradebook.allow_gradebook_stats', 'true');
        [$e, $linkId] = $this->gradedQuiz();
        $this->conn->executeStatement(
            "INSERT INTO user (username, username_canonical, email, email_canonical, password, salt, roles, locale, timezone,
                locked, expired, credentials_expired, status, active, uuid, created_at, updated_at, firstname, lastname)
             VALUES ('x2_student', 'x2_student', 'x2_student@example.com', 'x2_student@example.com', 'x', 'x', '[]', 'en_US', 'UTC',
                0, 0, 0, 5, 1, UNHEX(REPLACE(UUID(), '-', '')), '2020-01-01 00:00:00', '2020-01-01 00:00:00', 'X', 'Two')"
        );
        $student = (int) $this->conn->lastInsertId();
        $this->conn->insert('course_rel_user', ['user_id' => $student, 'c_id' => 1, 'relation_type' => 0, 'status' => 5, 'progress' => 0]);
        $this->exe($e->getId(), 2, 8.0, 10.0, 'completed', '2020-01-02 10:00:00');
        $this->exe($e->getId(), $student, 4.0, 10.0, 'completed', '2020-01-02 11:00:00');

        $e->generateStats($e->getId(), api_get_course_info(), 0);

        $link = $this->conn->fetchAssociative('SELECT * FROM gradebook_link WHERE id = ?', [$linkId]);
        $this->assertSame(8.0, (float) $link['best_score']);
        $this->assertSame(6.0, (float) $link['average_score'], '(8 + 4) / 2 students');
        $this->assertEquals([2 => 8, $student => 4], json_decode($link['user_score_list'], true));
    }

    public function testGenerateStatsSkipsAttemptsWithoutMaxScore(): void
    {
        $this->setSetting('gradebook.allow_gradebook_stats', 'true');
        [$e, $linkId] = $this->gradedQuiz();
        $this->exe($e->getId(), 2, 0.0, 0.0, 'completed', '2020-01-02 10:00:00');

        // PINNED QUIRK (bug): when no student has a scored attempt $students is never defined and
        // setUserScoreList(null) is a TypeError (PHP 8: warning first), so the link is never refreshed.
        try {
            $e->generateStats($e->getId(), api_get_course_info(), 0);
            $this->fail('expected the undefined-variable warning');
        } catch (\PHPUnit\Framework\Error\Warning $w) {
            $this->assertStringContainsString('$students', $w->getMessage());
        }
    }

    // ---------------------------------------------------------------- tool visibility guard of the result pages

    /**
     * Source-level pin (the pages are procedural and end in api_not_allowed()/exit): the third argument of
     * api_protect_course_script() is the TOOL NAME checked against the course tool visibility. Passing `true`
     * becomes the string "1", no tool is called "1", and a student could keep using the pages of a course whose
     * teacher hid the tests tool.
     */
    public function testResultPagesNameTheQuizToolInTheCourseScriptGuard(): void
    {
        foreach (['stats.php', 'exercise_report.php', 'exercise_show.php'] as $page) {
            $source = (string) file_get_contents(__DIR__.'/../../../public/main/exercise/'.$page);
            $this->assertMatchesRegularExpression('/api_protect_course_script\([^;]*TOOL_QUIZ\)/', $source, $page);
            $this->assertDoesNotMatchRegularExpression('/api_protect_course_script\([^;]*, true\)/', $source, $page);
        }
        $this->assertSame('quiz', TOOL_QUIZ);
        $this->assertSame(
            1,
            (int) $this->conn->fetchOne("SELECT COUNT(DISTINCT title) FROM tool WHERE title = 'quiz'"),
            'the tool table knows the tool by this name'
        );
    }
}
