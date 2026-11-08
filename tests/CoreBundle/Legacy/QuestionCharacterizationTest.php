<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Exception\NotAllowedException;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\Tests\ChamiloTestTrait;
use ChamiloSession;
use Doctrine\DBAL\Connection;
use Question;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * Characterization tests for the raw-SQL methods of the legacy Question class
 * (public/main/exercise/question.class.php).
 *
 * They pin what the legacy queries return or write TODAY (rows, ordering, renumbering, DB side effects) so a
 * rewrite to Doctrine/DQL that changes any of it fails here. Quirks are pinned as-is and flagged "PINNED QUIRK".
 *
 * Questions/quizzes are inserted as bare rows (no resource node of their own: creating nodes deadlocks on the shared
 * database), which is why the entity-persisting paths (save() of a NEW question, duplicate()) are not exercised.
 * Every fixture title starts with "qs_".
 */
final class QuestionCharacterizationTest extends KernelTestCase
{
    use ChamiloTestTrait;

    private Connection $conn;
    private int $seq = 0;
    /** @var int[]|null */
    private ?array $spare = null;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        Container::setContainer($container);
        Container::setLegacyServices($container);
        $session = new Session(new MockArraySessionStorage());
        Container::setSession($session);
        $request = Request::create('/main/exercise/question.class.php');
        $request->setSession($session);
        Container::setRequest($request);

        $this->conn = $container->get('doctrine')->getConnection();
        $_SERVER['HTTP_USER_AGENT'] = 'qs_agent';
        $_SERVER['REQUEST_URI'] = '/qs_uri';
        unset($_REQUEST['learnpath_id'], $_REQUEST['learnpath_item_id'], $_REQUEST['learnpath_item_view_id']);
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

    private function ctx(int $sessionId = 0): void
    {
        $this->setContext($this->getUser('admin'), $this->getCourse(1), $sessionId);
    }

    private function setSetting(string $name, string $value): void
    {
        self::getContainer()->get(SettingsManager::class)->updateSetting($name, $value);
    }

    private function makeQuiz(array $o = []): int
    {
        $this->conn->insert('c_quiz', $o + [
            'title' => 'qs_quiz_'.(++$this->seq),
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

    private function answer(int $questionId, string $text, int $position = 1): void
    {
        $this->conn->insert('c_quiz_answer', [
            'question_id' => $questionId,
            'answer' => $text,
            'correct' => 1,
            'comment' => '',
            'ponderation' => 1,
            'position' => $position,
        ]);
    }

    private function option(int $questionId, string $title, int $position = 1): int
    {
        $this->conn->insert('c_quiz_question_option', [
            'question_id' => $questionId,
            'title' => $title,
            'position' => $position,
        ]);

        return (int) $this->conn->lastInsertId();
    }

    /** Links the (bare) quiz to course/session through a resource_link on a borrowed spare resource node. */
    private function publishQuiz(int $quizId, int $nodeIdx, int $courseId, ?int $sessionId = null, ?string $deletedAt = null): void
    {
        // Spare nodes: neither used by a quiz nor carrying any resource_link (fixed list for the whole test).
        $this->spare ??= $this->conn->fetchFirstColumn(
            'SELECT id FROM resource_node WHERE id NOT IN (SELECT resource_node_id FROM c_quiz WHERE resource_node_id IS NOT NULL)'
            .' AND id NOT IN (SELECT resource_node_id FROM resource_link WHERE resource_node_id IS NOT NULL) ORDER BY id DESC LIMIT 6'
        );
        $this->assertGreaterThan($nodeIdx, \count($this->spare), 'fixture needs spare resource nodes');
        $node = (int) $this->spare[$nodeIdx];
        $this->conn->update('c_quiz', ['resource_node_id' => $node], ['iid' => $quizId]);
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

    private function read(int $id, bool $withQuizzes = true): Question
    {
        $q = Question::read($id, api_get_course_info(), $withQuizzes);
        $this->assertInstanceOf(Question::class, $q);

        return $q;
    }

    /** @return array<int,int> question_id => question_order for a quiz */
    private function orders(int $quizId): array
    {
        $rows = $this->conn->fetchAllKeyValue(
            'SELECT question_id, question_order FROM c_quiz_rel_question WHERE quiz_id = ? ORDER BY question_order',
            [$quizId]
        );

        return array_map('intval', $rows);
    }

    private function cnt(string $table, string $where, array $params = []): int
    {
        return (int) $this->conn->fetchOne("SELECT COUNT(*) FROM $table WHERE $where", $params);
    }

    private function category(string $title): int
    {
        $this->conn->insert('c_quiz_question_category', ['title' => $title, 'description' => '']);

        return (int) $this->conn->lastInsertId();
    }

    // ---------------------------------------------------------------- read()

    public function testReadReturnsFalseForUnknownOrZeroId(): void
    {
        $this->ctx();
        $this->assertFalse(Question::read(0));
        $this->assertFalse(Question::read(999999999));
    }

    public function testReadHydratesEveryColumnAndPicksTheSubclassByType(): void
    {
        $this->ctx();
        $media = $this->makeQuestion('qs_media', MEDIA_QUESTION);
        $id = $this->makeQuestion('qs_title', MULTIPLE_ANSWER, 2.5, [
            'description' => 'qs_desc',
            'position' => 7,
            'level' => 3,
            'extra' => 'qs_extra',
            'feedback' => 'qs_feedback',
            'parent_media_id' => $media,
            'question_code' => 'QCODE',
        ]);

        $q = $this->read($id);

        $this->assertInstanceOf(\MultipleAnswer::class, $q);
        $this->assertSame($id, $q->iid);
        $this->assertSame('qs_title', $q->question);
        $this->assertSame('qs_desc', $q->description);
        $this->assertEquals(2.5, $q->weighting);
        $this->assertEquals(7, $q->position);
        $this->assertSame(MULTIPLE_ANSWER, $q->type);
        $this->assertSame(3, $q->level);
        $this->assertSame('qs_extra', $q->extra);
        $this->assertSame('qs_feedback', $q->feedback);
        $this->assertSame($media, $q->parent_id);
        $this->assertSame(0, $q->category);
        // PINNED QUIRK: read() looks for a `code` column but the table column is `question_code`, so the
        // question code is never loaded, whatever the row holds.
        $this->assertSame('', $q->code);
    }

    public function testReadWithNullParentMediaGivesZeroParent(): void
    {
        $this->ctx();
        $id = $this->makeQuestion('qs_noparent');
        $this->assertSame(0, $this->read($id)->parent_id);
    }

    public function testReadLoadsTheCategoryOfTheQuestion(): void
    {
        $this->ctx();
        $cat = $this->category('qs_cat');
        $id = $this->makeQuestion('qs_q_cat');
        $this->conn->insert('c_quiz_question_rel_category', ['question_id' => $id, 'category_id' => $cat]);

        $this->assertSame($cat, $this->read($id)->category);
    }

    public function testReadNeverLoadsMandatoryFromTheCategoryRow(): void
    {
        $this->ctx();
        $cat = $this->category('qs_cat_m');
        $id = $this->makeQuestion('qs_q_mand', UNIQUE_ANSWER, 1.0, ['mandatory' => 1]);
        $this->conn->insert('c_quiz_question_rel_category', ['question_id' => $id, 'category_id' => $cat]);

        // PINNED QUIRK: with the setting on, read() asks the c_quiz_question_rel_category row for a `mandatory`
        // column that table does not have (it lives on c_quiz_question), so the flag is never loaded.
        $this->setSetting('exercise.allow_mandatory_question_in_category', 'true');
        $this->assertEmpty($this->read($id)->mandatory);
    }

    public function testReadListsOnlyLiveQuizzesOfTheCurrentSession(): void
    {
        $this->ctx();
        $id = $this->makeQuestion('qs_q_lists');
        $sid = $this->insertSession('qs_sess_read');
        $quizBase = $this->makeQuiz();
        $quizBase2 = $this->makeQuiz();
        $quizDeleted = $this->makeQuiz();
        $quizSession = $this->makeQuiz();
        $quizUnlinked = $this->makeQuiz(); // no resource_link at all
        $this->publishQuiz($quizBase, 0, 1);
        $this->publishQuiz($quizBase2, 1, 1);
        $this->publishQuiz($quizDeleted, 2, 1, null, '2020-01-02 00:00:00');
        $this->publishQuiz($quizSession, 3, 1, $sid);
        foreach ([$quizBase, $quizBase2, $quizDeleted, $quizSession, $quizUnlinked] as $i => $quiz) {
            $this->link($quiz, $id, $i + 1);
        }

        $list = $this->read($id)->exerciseList;
        sort($list);
        $this->assertSame([$quizBase, $quizBase2], $list, 'base course: live links with no session only');

        $this->ctx($sid);
        $this->assertSame([$quizSession], $this->read($id)->exerciseList, 'inside a session only that session\'s quizzes');

        $this->assertSame([], $this->read($id, false)->exerciseList, 'getExerciseList=false skips the lookup');
    }

    private function insertSession(string $title): int
    {
        $this->conn->insert('session', [
            'title' => $title,
            'nbr_courses' => 0,
            'nbr_users' => 0,
            'nbr_classes' => 0,
            'visibility' => 1,
            'status' => 0,
        ]);

        return (int) $this->conn->lastInsertId();
    }

    // ---------------------------------------------------------------- categories

    public function testSaveCategoryInsertsThenUpdatesTheSingleRow(): void
    {
        $this->ctx();
        $a = $this->category('qs_cat_a');
        $b = $this->category('qs_cat_b');
        $id = $this->makeQuestion('qs_q_savecat');
        $q = $this->read($id);

        $this->assertTrue($q->saveCategory($a));
        $this->assertSame(1, $this->cnt('c_quiz_question_rel_category', 'question_id = ?', [$id]));
        $this->assertSame($a, (int) $this->conn->fetchOne('SELECT category_id FROM c_quiz_question_rel_category WHERE question_id = ?', [$id]));

        $q->saveCategory($b);
        $this->assertSame(1, $this->cnt('c_quiz_question_rel_category', 'question_id = ?', [$id]), 'a question has one category');
        $this->assertSame($b, (int) $this->conn->fetchOne('SELECT category_id FROM c_quiz_question_rel_category WHERE question_id = ?', [$id]));
    }

    public function testSaveCategoryWithMandatorySettingOnLosesTheFlagThenFails(): void
    {
        $this->ctx();
        $cat = $this->category('qs_cat_mand');
        $off = $this->makeQuestion('qs_q_mand_off');
        $on = $this->makeQuestion('qs_q_mand_on');

        $this->setSetting('exercise.allow_mandatory_question_in_category', 'false');
        $q = $this->read($off);
        $q->setMandatory(1);
        $this->assertTrue($q->saveCategory($cat));
        $this->assertSame(1, $this->cnt('c_quiz_question_rel_category', 'question_id = ?', [$off]));
        $this->assertSame(0, (int) $this->conn->fetchOne('SELECT mandatory FROM c_quiz_question WHERE iid = ?', [$off]));

        // PINNED QUIRK: with the setting on, the flag is never stored. The INSERT path asks insert_id() of a
        // link table without auto-increment column (0) and silently skips the UPDATE; the UPDATE path
        // (a category row already exists) writes `mandatory` into c_quiz_question_rel_category, which has no such
        // column, and throws.
        $this->setSetting('exercise.allow_mandatory_question_in_category', 'true');
        $q = $this->read($on);
        $q->setMandatory(1);
        $this->assertTrue($q->saveCategory($cat));
        $this->assertSame(0, (int) $this->conn->fetchOne('SELECT mandatory FROM c_quiz_question WHERE iid = ?', [$on]), 'flag lost');
        $this->expectException(NotAllowedException::class);
        $q->saveCategory($cat);
    }

    public function testSaveCategoryWithZeroRemovesTheCategoryAndDeleteCategoryNeedsAnId(): void
    {
        $this->ctx();
        $cat = $this->category('qs_cat_del');
        $id = $this->makeQuestion('qs_q_delcat');
        $other = $this->makeQuestion('qs_q_delcat_other');
        $this->conn->insert('c_quiz_question_rel_category', ['question_id' => $id, 'category_id' => $cat]);
        $this->conn->insert('c_quiz_question_rel_category', ['question_id' => $other, 'category_id' => $cat]);

        $q = $this->read($id);
        $this->assertTrue($q->saveCategory(0));
        $this->assertSame(0, $this->cnt('c_quiz_question_rel_category', 'question_id = ?', [$id]));
        $this->assertSame(1, $this->cnt('c_quiz_question_rel_category', 'question_id = ?', [$other]), 'other questions untouched');

        $noId = new \UniqueAnswer();
        $this->assertFalse($noId->deleteCategory());
    }

    // ---------------------------------------------------------------- quiz <-> question links

    public function testAddToListAppendsAtTheEndOfTheQuizAndIgnoresDuplicates(): void
    {
        $this->ctx();
        $quiz = $this->makeQuiz();
        $a = $this->makeQuestion('qs_a');
        $b = $this->makeQuestion('qs_b');
        $c = $this->makeQuestion('qs_c');
        $this->link($quiz, $a, 1);
        $this->link($quiz, $b, 2);

        $q = $this->read($c);
        $q->addToList($quiz, true);
        $this->assertSame([$a => 1, $b => 2, $c => 3], $this->orders($quiz));
        $this->assertSame([$quiz], $q->exerciseList);

        $q->addToList($quiz, true);
        $this->assertSame([$a => 1, $b => 2, $c => 3], $this->orders($quiz), 'already in the list: nothing added');

        $q->addToList(0, true);
        $this->assertSame(3, $this->cnt('c_quiz_rel_question', 'quiz_id = ?', [$quiz]), 'quiz 0 ignored');
    }

    public function testAddToListUsesTheQuestionCountPlusOneNotTheMaxOrder(): void
    {
        $this->ctx();
        $quiz = $this->makeQuiz();
        $a = $this->makeQuestion('qs_a');
        $b = $this->makeQuestion('qs_b');
        $newQ = $this->makeQuestion('qs_new');
        $this->link($quiz, $a, 1);
        $this->link($quiz, $b, 5);

        $this->read($newQ)->addToList($quiz, true);

        // PINNED QUIRK: the new order is "number of questions + 1" (3), not max(order)+1 (6): with holes in the
        // sequence it can collide with an existing order.
        $this->assertSame([$a => 1, $newQ => 3, $b => 5], $this->orders($quiz));
    }

    public function testRemoveFromListRenumbersTheFollowingQuestionsOfThatQuizOnly(): void
    {
        $this->ctx();
        $quiz = $this->makeQuiz();
        $other = $this->makeQuiz();
        $a = $this->makeQuestion('qs_a');
        $b = $this->makeQuestion('qs_b');
        $c = $this->makeQuestion('qs_c');
        $this->link($quiz, $a, 1);
        $this->link($quiz, $b, 2);
        $this->link($quiz, $c, 3);
        $this->link($other, $b, 2);
        $this->link($other, $c, 3);
        $this->publishQuiz($quiz, 0, 1);
        $this->publishQuiz($other, 1, 1);

        $q = $this->read($b);
        $this->assertTrue($q->removeFromList($quiz));

        $this->assertSame([$a => 1, $c => 2], $this->orders($quiz));
        $this->assertSame([$b => 2, $c => 3], $this->orders($other), 'other quiz untouched');
        $this->assertSame([$other], array_values($q->exerciseList));
        $this->assertFalse($q->removeFromList($quiz), 'no longer in that quiz');
    }

    public function testRemoveFromListDetachesChildrenOfAMediaQuestion(): void
    {
        $this->ctx();
        $quiz = $this->makeQuiz();
        $media = $this->makeQuestion('qs_media', MEDIA_QUESTION);
        $child = $this->makeQuestion('qs_child', UNIQUE_ANSWER, 1.0, ['parent_media_id' => $media]);
        $this->link($quiz, $media, 1);
        $this->link($quiz, $child, 2);
        $this->publishQuiz($quiz, 0, 1);

        $this->read($media)->removeFromList($quiz);

        $this->assertNull($this->conn->fetchOne('SELECT parent_media_id FROM c_quiz_question WHERE iid = ?', [$child]));
    }

    public function testDeleteFromAllQuizzesRemovesQuestionAnswersCategoryAndRenumbers(): void
    {
        $this->ctx();
        $quizA = $this->makeQuiz();
        $quizB = $this->makeQuiz();
        $x = $this->makeQuestion('qs_x');
        $victim = $this->makeQuestion('qs_victim');
        $z = $this->makeQuestion('qs_z');
        $keeper = $this->makeQuestion('qs_keeper');
        $child = $this->makeQuestion('qs_child', UNIQUE_ANSWER, 1.0, ['parent_media_id' => $victim]);
        foreach ([[$quizA, $x, 1], [$quizA, $victim, 2], [$quizA, $z, 3], [$quizB, $victim, 1], [$quizB, $keeper, 2]] as [$qz, $qs, $o]) {
            $this->link($qz, $qs, $o);
        }
        $this->answer($victim, 'qs_ans_v');
        $this->answer($keeper, 'qs_ans_k');
        $cat = $this->category('qs_cat_delq');
        $this->conn->insert('c_quiz_question_rel_category', ['question_id' => $keeper, 'category_id' => $cat]);

        $this->assertTrue($this->read($victim, false)->delete());

        $this->assertSame(0, $this->cnt('c_quiz_question', 'iid = ?', [$victim]));
        $this->assertSame(0, $this->cnt('c_quiz_answer', 'question_id = ?', [$victim]));
        $this->assertSame(0, $this->cnt('c_quiz_rel_question', 'question_id = ?', [$victim]));
        $this->assertSame([$x => 1, $z => 2], $this->orders($quizA), 'followers move up in every quiz');
        // PINNED QUIRK: quizB's first question (order 1) of the deleted pair... victim was order 1 there and
        // `!empty(order)` is true for 1, so keeper moves from 2 to 1.
        $this->assertSame([$keeper => 1], $this->orders($quizB));
        $this->assertNull($this->conn->fetchOne('SELECT parent_media_id FROM c_quiz_question WHERE iid = ?', [$child]));
        $this->assertSame(1, $this->cnt('c_quiz_answer', 'question_id = ?', [$keeper]), 'other questions untouched');
        $this->assertSame(1, $this->cnt('c_quiz_question_rel_category', 'question_id = ?', [$keeper]));
    }

    public function testDeleteOfAQuestionHavingACategoryFailsOnTheForeignKey(): void
    {
        $this->ctx();
        $id = $this->makeQuestion('qs_del_cat');
        $cat = $this->category('qs_cat_delq2');
        $this->conn->insert('c_quiz_question_rel_category', ['question_id' => $id, 'category_id' => $cat]);
        $q = $this->read($id, false);

        // PINNED QUIRK: delete() removes the c_quiz_question row BEFORE the category link, and that FK is
        // ON DELETE RESTRICT: any categorised question can not be deleted (the DELETE throws).
        $this->expectException(NotAllowedException::class);
        $q->delete();
    }

    public function testDeleteFromOneQuizOnlyUnlinksIt(): void
    {
        $this->ctx();
        $quizA = $this->makeQuiz();
        $quizB = $this->makeQuiz();
        $a = $this->makeQuestion('qs_a');
        $b = $this->makeQuestion('qs_b');
        $this->link($quizA, $a, 1);
        $this->link($quizA, $b, 2);
        $this->link($quizB, $a, 1);
        $this->publishQuiz($quizA, 0, 1);
        $this->publishQuiz($quizB, 1, 1);
        $this->answer($a, 'qs_ans_a');

        $this->assertTrue($this->read($a)->delete($quizA));

        $this->assertSame(1, $this->cnt('c_quiz_question', 'iid = ?', [$a]), 'question kept');
        $this->assertSame(1, $this->cnt('c_quiz_answer', 'question_id = ?', [$a]));
        $this->assertSame([$b => 1], $this->orders($quizA));
        $this->assertSame([$a => 1], $this->orders($quizB));
    }

    public function testDeleteReturnsFalseWithoutACourse(): void
    {
        $this->ctx();
        $id = $this->makeQuestion('qs_nocourse');
        $q = $this->read($id);
        $q->course = [];
        $this->assertFalse($q->delete());
        $q->course = ['real_id' => 0];
        $this->assertFalse($q->delete());
        $this->assertSame(1, $this->cnt('c_quiz_question', 'iid = ?', [$id]));
    }

    // ---------------------------------------------------------------- save() of an existing question

    public function testSaveOfAnExistingQuestionUpdatesTheRowAndLinksItToTheQuiz(): void
    {
        $this->ctx();
        $quiz = $this->makeQuiz();
        $id = $this->makeQuestion('qs_before', UNIQUE_ANSWER, 1.0);
        $this->link($quiz, $this->makeQuestion('qs_other'), 1);
        $cat = $this->category('qs_cat_save');

        $q = $this->read($id, false);
        $q->updateTitle('qs_after');
        $q->updateDescription('qs_desc_after');
        $q->updateWeighting(4);
        $q->updateLevel(2);
        $q->updatePosition(5);
        $q->updateCategory($cat);
        $q->setFeedback('qs_fb');
        $q->setExtra('qs_x');
        $exercise = new \Exercise();
        $exercise->iId = $quiz;
        $q->save($exercise);
        $this->getContainer()->get('doctrine')->getManager()->clear();

        $row = $this->conn->fetchAssociative('SELECT * FROM c_quiz_question WHERE iid = ?', [$id]);
        $this->assertSame('qs_after', $row['question']);
        $this->assertSame('qs_desc_after', $row['description']);
        $this->assertEquals(4, $row['ponderation']);
        $this->assertSame(2, (int) $row['level']);
        $this->assertSame(5, (int) $row['position']);
        $this->assertSame('qs_fb', $row['feedback']);
        $this->assertSame('qs_x', $row['extra']);
        $this->assertSame(2, $this->orders($quiz)[$id] ?? null, 'appended after the 1 existing question');
        $this->assertSame(1, $this->cnt('c_quiz_rel_question', 'quiz_id = ? AND question_id = ?', [$quiz, $id]));
    }

    // ---------------------------------------------------------------- create_question()

    public function testCreateQuestionAppendsToTheQuizWithNextPositionAndOrder(): void
    {
        $this->ctx();
        $quiz = $this->makeQuiz();
        $first = $this->makeQuestion('qs_first', 1, 1.0, ['position' => 4]);
        $second = $this->makeQuestion('qs_second', 1, 1.0, ['position' => 9]);
        $this->link($quiz, $first, 6);
        $this->link($quiz, $second, 3);

        $maker = new \UniqueAnswer();
        $newId = $maker->create_question($quiz, 'qs_created', 'qs_created_desc', 3.5, MULTIPLE_ANSWER, 2);

        $row = $this->conn->fetchAssociative('SELECT * FROM c_quiz_question WHERE iid = ?', [$newId]);
        $this->assertSame('qs_created', $row['question']);
        $this->assertSame('qs_created_desc', $row['description']);
        $this->assertEquals(3.5, $row['ponderation']);
        $this->assertSame(10, (int) $row['position'], 'max(position of the quiz questions)+1');
        $this->assertSame(MULTIPLE_ANSWER, (int) $row['type']);
        $this->assertSame(2, (int) $row['level']);
        $this->assertSame([$second => 3, $first => 6, $newId => 7], $this->orders($quiz), 'max(question_order)+1');
    }

    public function testCreateQuestionInAnEmptyQuizStartsAtOne(): void
    {
        $this->ctx();
        $quiz = $this->makeQuiz();
        $newId = (new \UniqueAnswer())->create_question($quiz, 'qs_created_empty');
        $this->assertSame(1, (int) $this->conn->fetchOne('SELECT position FROM c_quiz_question WHERE iid = ?', [$newId]));
        $this->assertSame([$newId => 1], $this->orders($quiz));
        $this->assertSame(1, (int) $this->conn->fetchOne('SELECT type FROM c_quiz_question WHERE iid = ?', [$newId]));
    }

    // ---------------------------------------------------------------- options

    public function testReadQuestionOptionReturnsOptionsInInsertionOrderOfOneQuestion(): void
    {
        $this->ctx();
        $id = $this->makeQuestion('qs_opts');
        $other = $this->makeQuestion('qs_opts_other');
        $o2 = $this->option($id, 'qs_second', 2);
        $o1 = $this->option($id, 'qs_first', 1);
        $this->option($other, 'qs_foreign', 1);

        $rows = Question::readQuestionOption($id);

        $this->assertSame([$o2, $o1], array_map('intval', array_column($rows, 'iid')), 'ordered by iid, not by position');
        $this->assertSame(['qs_second', 'qs_first'], array_column($rows, 'title'));
        $this->assertSame([], Question::readQuestionOption(999999999));
    }

    public function testDeleteAllQuestionOptionsRemovesOnlyThatQuestionsOptions(): void
    {
        $this->ctx();
        $id = $this->makeQuestion('qs_opts_del');
        $other = $this->makeQuestion('qs_opts_keep');
        $this->option($id, 'qs_del_1');
        $this->option($id, 'qs_del_2');
        $this->option($other, 'qs_keep');

        // PINNED QUIRK: filters on a c_id column c_quiz_question_option no longer has: always throws.
        try {
            Question::deleteAllQuestionOptions($id, 1);
            $this->fail('expected the unknown column c_id to throw');
        } catch (NotAllowedException) {
            $this->assertSame(2, $this->cnt('c_quiz_question_option', 'question_id = ?', [$id]));
            $this->assertSame(1, $this->cnt('c_quiz_question_option', 'question_id = ?', [$other]));
        }
    }

    public function testCountAnswers(): void
    {
        $this->ctx();
        $id = $this->makeQuestion('qs_count');
        $this->answer($id, 'a', 1);
        $this->answer($id, 'b', 2);
        $this->answer($this->makeQuestion('qs_count_other'), 'c', 1);

        $this->assertSame(2, $this->read($id)->countAnswers());
        $this->assertSame(0, $this->read($this->makeQuestion('qs_count_none'))->countAnswers());
    }

    // ---------------------------------------------------------------- type changes

    public function testUpdateTypeBetweenUniqueAndMultipleKeepsTheAnswers(): void
    {
        $this->ctx();
        $id = $this->makeQuestion('qs_type_keep', UNIQUE_ANSWER);
        $this->answer($id, 'qs_keep');
        $q = $this->read($id);

        $q->updateType(MULTIPLE_ANSWER);

        $this->assertSame(MULTIPLE_ANSWER, $q->type);
        $this->assertSame(1, $this->cnt('c_quiz_answer', 'question_id = ?', [$id]));
    }

    public function testUpdateTypeToAnotherFamilyDeletesTheAnswersOfThatQuestionOnly(): void
    {
        $this->ctx();
        $id = $this->makeQuestion('qs_type_drop', UNIQUE_ANSWER);
        $other = $this->makeQuestion('qs_type_other', UNIQUE_ANSWER);
        $this->answer($id, 'qs_drop');
        $this->answer($other, 'qs_other_ans');
        $q = $this->read($id);

        // PINNED QUIRK: the DELETE filters on c_quiz_answer.c_id, a column that no longer exists: throws.
        try {
            $q->updateType(FREE_ANSWER);
            $this->fail('expected the unknown column c_id to throw');
        } catch (NotAllowedException) {
            $this->assertSame(1, $this->cnt('c_quiz_answer', 'question_id = ?', [$id]));
            $this->assertSame(UNIQUE_ANSWER, $q->type, 'type not changed');
        }
    }

    public function testSwapSimpleAnswerTypesPersistsTheOppositeType(): void
    {
        $this->ctx();
        $id = $this->makeQuestion('qs_swap', UNIQUE_ANSWER);
        $other = $this->makeQuestion('qs_swap_other', UNIQUE_ANSWER);
        $q = $this->read($id);

        // PINNED QUIRK: UPDATE ... WHERE c_id = ? AND id = ? on a table with neither column: always throws.
        try {
            $q->swapSimpleAnswerTypes();
            $this->fail('expected the unknown columns to throw');
        } catch (NotAllowedException) {
            $this->assertSame(UNIQUE_ANSWER, (int) $this->conn->fetchOne('SELECT type FROM c_quiz_question WHERE iid = ?', [$id]));
            $this->assertSame(UNIQUE_ANSWER, (int) $this->conn->fetchOne('SELECT type FROM c_quiz_question WHERE iid = ?', [$other]));
        }
    }

    // ---------------------------------------------------------------- how many quizzes use a question

    public function testQuizUsageCountersSeeEveryQuizThatLinksTheQuestion(): void
    {
        $this->ctx();
        $id = $this->makeQuestion('qs_usage');
        $solo = $this->makeQuestion('qs_usage_solo');
        $quizA = $this->makeQuiz();
        $quizB = $this->makeQuiz();
        $this->link($quizA, $id, 1);
        $this->link($quizB, $id, 1);
        $this->link($quizA, $solo, 2);

        $q = $this->read($id);
        $this->assertSame(2, $q->getCountExercise());
        $this->assertTrue($q->existsInAnotherExercise());
        $ids = array_map(static fn ($e) => $e->getIid(), $q->getExerciseListWhereQuestionExists());
        sort($ids);
        $this->assertSame([$quizA, $quizB], $ids);

        $s = $this->read($solo);
        $this->assertSame(1, $s->getCountExercise());
        $this->assertFalse($s->existsInAnotherExercise(), 'a single link is not "another" exercise');
    }

    // ---------------------------------------------------------------- media questions

    public function testPrepareCourseMediaSelectListsTopLevelMediaOfTheQuizSortedByTitle(): void
    {
        $this->ctx();
        $quiz = $this->makeQuiz();
        $otherQuiz = $this->makeQuiz();
        $mB = $this->makeQuestion('qs_media_b', MEDIA_QUESTION);
        $mA = $this->makeQuestion('qs_media_a', MEDIA_QUESTION);
        $mEmpty = $this->makeQuestion('', MEDIA_QUESTION);
        $nested = $this->makeQuestion('qs_media_nested', MEDIA_QUESTION, 1.0, ['parent_media_id' => $mA]);
        $plain = $this->makeQuestion('qs_plain', UNIQUE_ANSWER);
        $foreign = $this->makeQuestion('qs_media_foreign', MEDIA_QUESTION);
        foreach ([$mB, $mA, $mEmpty, $nested, $plain] as $i => $qid) {
            $this->link($quiz, $qid, $i + 1);
        }
        $this->link($otherQuiz, $foreign, 1);

        $list = Question::prepare_course_media_select($quiz);

        $this->assertSame([0, $mEmpty, $mA, $mB], array_keys($list), 'sorted by question; empty title first');
        $this->assertSame('qs_media_a', $list[$mA]);
        $this->assertSame(get_lang('Untitled'), $list[$mEmpty]);
        $this->assertSame(get_lang('Not linked to media'), $list[0]);
    }

    public function testCourseMediaListAndCountThrowOnMissingColumns(): void
    {
        $this->ctx();
        // PINNED QUIRK: both filter on c_quiz_question.c_id / parent_id, which do not exist: they always throw.
        // (Only reachable from the 'get_course_exercise_medias' jqgrid action of model.ajax.php, which no page uses.)
        try {
            Question::get_count_course_medias(1);
            $this->fail('expected unknown column');
        } catch (NotAllowedException) {
            $this->addToAssertionCount(1);
        }
        $this->expectException(NotAllowedException::class);
        Question::get_course_medias(1);
    }
}
