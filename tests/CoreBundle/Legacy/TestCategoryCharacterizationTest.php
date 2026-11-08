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
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use TestCategory;
use Throwable;

/**
 * Characterization of the raw-SQL half of the legacy TestCategory class (public/main/exercise/TestCategory.php):
 * quiz question categories, the question -> category link, the per-exercise category tree and the counters
 * built on them.
 *
 * LIVE (exercise.class.php, exercise.lib.php, question.class.php, tests_category.php, imports, API providers):
 * getCategory, removeCategory, getCategoryForQuestion, getCategoryInfoForQuestion, getCategoryNameForQuestion,
 * getListOfCategoriesIDForTest/ForTest/NameForTest/IDForTestObject, getNumberOfQuestionsInCategoryForTest,
 * getNumberOfQuestionRandomByCategory, getNumberMaxQuestionByCat, getQuestionsByCat, getCategoryExerciseTree,
 * get_category_id_for_title, addCategoryToQuestion, sortTabByBracketLabel, getNElementsFromArray.
 * DEAD (no caller, marked @deprecated): getCategoryQuestionsNumber (and it throws: c_id column gone).
 * Doctrine/UI-only (save, modifyCategory, getCategories, displayCategories, forms, tables) hold no raw SQL.
 *
 * Scoping: c_quiz_question_category has no course column any more (the course is the resource_link of its
 * resource node), so getCategory()/get_category_id_for_title() cannot filter by course in SQL; the pages that
 * accept a request-supplied id call ExerciseLib::questionCategoryBelongsToCourse() first (tests_category.php).
 *
 * Quizzes, questions and categories are bare rows (no resource node: creating nodes deadlocks on the shared
 * database). Every fixture title starts with "tc_".
 */
final class TestCategoryCharacterizationTest extends KernelTestCase
{
    use ChamiloTestTrait;

    private Connection $conn;
    private int $seq = 0;

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
        $request = Request::create('/main/exercise/tests_category.php');
        $request->setSession($session);
        Container::setRequest($request);

        $this->conn = $container->get('doctrine')->getConnection();
        $this->setContext($this->getUser('admin'), $this->getCourse(1));
        $_REQUEST = [];
    }

    // ---------------------------------------------------------------- helpers

    private function setContext(User $user, Course $course): void
    {
        ChamiloSession::write('_user', ['id' => $user->getId()]);
        ChamiloSession::write('_real_cid', $course->getId());
        ChamiloSession::write('_cid', $course->getCode());
        ChamiloSession::write('_course', api_get_course_info($course->getCode()));
        ChamiloSession::write('sid', 0);
        ChamiloSession::erase('login_as');
        Container::getEntityManager()->clear();
    }

    private function category(string $title = '', string $description = ''): int
    {
        $this->conn->insert('c_quiz_question_category', [
            'title' => '' === $title ? 'tc_cat_'.(++$this->seq) : $title,
            'description' => $description,
        ]);

        return (int) $this->conn->lastInsertId();
    }

    /**
     * Links a (bare) category to a course through a resource_link on a borrowed spare resource node.
     */
    private function linkCategory(int $categoryId, int $nodeIdx, int $courseId, ?string $deletedAt = null): void
    {
        $this->spare ??= $this->conn->fetchFirstColumn(
            'SELECT id FROM resource_node WHERE id NOT IN (SELECT resource_node_id FROM c_quiz_question_category WHERE resource_node_id IS NOT NULL)'
            .' AND id NOT IN (SELECT resource_node_id FROM resource_link WHERE resource_node_id IS NOT NULL) ORDER BY id DESC LIMIT 6'
        );
        $this->assertGreaterThan($nodeIdx, \count($this->spare), 'fixture needs spare resource nodes');
        $node = (int) $this->spare[$nodeIdx];
        $this->conn->update('c_quiz_question_category', ['resource_node_id' => $node], ['iid' => $categoryId]);
        $now = '2020-01-01 00:00:00';
        $this->conn->insert('resource_link', [
            'resource_node_id' => $node,
            'c_id' => $courseId,
            'session_id' => null,
            'visibility' => 2,
            'display_order' => 0,
            'resource_type_group' => 1,
            'deleted_at' => $deletedAt,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function quiz(): int
    {
        $this->conn->insert('c_quiz', [
            'title' => 'tc_quiz_'.(++$this->seq),
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

    private function question(int $quizId, int $order, ?int $categoryId = null, bool $mandatory = false): int
    {
        $this->conn->insert('c_quiz_question', [
            'question' => 'tc_question_'.(++$this->seq),
            'ponderation' => 1,
            'position' => 0,
            'type' => 1,
            'level' => 0,
            'mandatory' => $mandatory ? 1 : 0,
        ]);
        $id = (int) $this->conn->lastInsertId();
        $this->conn->insert('c_quiz_rel_question', ['quiz_id' => $quizId, 'question_id' => $id, 'question_order' => $order]);
        if (null !== $categoryId) {
            $this->conn->insert('c_quiz_question_rel_category', ['question_id' => $id, 'category_id' => $categoryId]);
        }

        return $id;
    }

    private function treeRow(int $quizId, ?int $categoryId, int $count): void
    {
        $this->conn->insert('c_quiz_rel_category', [
            'exercise_id' => $quizId,
            'category_id' => $categoryId,
            'count_questions' => $count,
        ]);
    }

    private function exercise(int $quizId): Exercise
    {
        $e = new Exercise(1);
        $this->assertTrue($e->read($quizId, false));

        return $e;
    }

    // ---------------------------------------------------------------- getCategory

    public function testGetCategoryFillsTheObjectAndReturnsItselfOrFalse(): void
    {
        $id = $this->category('tc_found', 'tc_description');
        $cat = new TestCategory();

        $this->assertSame($cat, $cat->getCategory($id));
        $this->assertSame($id, (int) $cat->id);
        $this->assertSame('tc_found', $cat->name);
        $this->assertSame('tc_description', $cat->description);
        $this->assertFalse((new TestCategory())->getCategory(2000000000));
        $this->assertFalse((new TestCategory())->getCategory(0));
        $this->assertFalse((new TestCategory())->getCategory('1 OR 1=1'), 'ids are cast to int');
    }

    public function testGetCategoryDoesNotScopeByCourse(): void
    {
        // PINNED QUIRK: no c_id column, no join on resource_link: any category id loads whatever the current
        // course is (the $courseId argument is accepted and ignored). tests_category.php therefore calls
        // ExerciseLib::questionCategoryBelongsToCourse() before using it.
        $id = $this->category('tc_foreign_course');
        $this->assertFalse(\ExerciseLib::questionCategoryBelongsToCourse($id, 1, 0), 'it belongs to no course at all');

        $cat = (new TestCategory())->getCategory($id, 2);

        $this->assertSame('tc_foreign_course', $cat->name);
    }

    // ---------------------------------------------------------------- question -> category

    public function testCategoryOfAQuestionIsItsOnlyLinkOrZero(): void
    {
        $quiz = $this->quiz();
        $cat = $this->category('tc_of_question');
        $with = $this->question($quiz, 1, $cat);
        $without = $this->question($quiz, 2);

        $this->assertSame($cat, TestCategory::getCategoryForQuestion($with));
        $this->assertSame(0, TestCategory::getCategoryForQuestion($without));
        $this->assertSame(0, TestCategory::getCategoryForQuestion(0));
        $info = TestCategory::getCategoryInfoForQuestion($with);
        $this->assertSame($with, (int) $info['question_id']);
        $this->assertSame($cat, (int) $info['category_id']);
        $this->assertSame([], TestCategory::getCategoryInfoForQuestion($without));
        // PINNED QUIRK: no question id (or no course) gives int 0 instead of the [] of the "no row" case.
        $this->assertSame(0, TestCategory::getCategoryInfoForQuestion(0));
        $this->assertSame($cat, TestCategory::getCategoryForQuestion($with, 2), 'the course id is not part of the query');
    }

    public function testCategoryNameForAQuestionIsEmptyWithoutCategory(): void
    {
        $quiz = $this->quiz();
        $cat = $this->category('tc_named');
        $with = $this->question($quiz, 1, $cat);
        $without = $this->question($quiz, 2);

        $this->assertSame('tc_named', TestCategory::getCategoryNameForQuestion($with));
        $this->assertSame('', TestCategory::getCategoryNameForQuestion($without));
        $this->assertSame('', TestCategory::getCategoryNameForQuestion(2000000000));
    }

    public function testAddCategoryToQuestionOnlyLinksAQuestionThatHasNoCategoryYet(): void
    {
        $quiz = $this->quiz();
        $first = $this->category('tc_first');
        $second = $this->category('tc_second');
        $q = $this->question($quiz, 1);

        TestCategory::addCategoryToQuestion($first, $q, 1);
        $again = TestCategory::addCategoryToQuestion($second, $q, 1);

        $this->assertFalse($again, 'a question has a single category in this version');
        $this->assertSame($first, TestCategory::getCategoryForQuestion($q));
        $this->assertFalse(TestCategory::addCategoryToQuestion($first, 0, 1), 'no question');
        $this->assertFalse(TestCategory::addCategoryToQuestion($first, $q + 1000000, 0), 'no course');
        $this->assertSame(1, (int) $this->conn->fetchOne('SELECT COUNT(*) FROM c_quiz_question_rel_category WHERE question_id = ?', [$q]));
    }

    public function testGetCategoryIdForTitleMatchesTheTitleExactlyInTheGivenCourseOnly(): void
    {
        $inCourse1 = $this->category('tc_by_title');
        $this->linkCategory($inCourse1, 0, 1);
        $quoted = $this->category("tc_o'brien");
        $this->linkCategory($quoted, 1, 1);
        $inCourse2 = $this->category('tc_only_in_two');
        $this->linkCategory($inCourse2, 2, 2);
        $deleted = $this->category('tc_deleted_link');
        $this->linkCategory($deleted, 3, 1, '2020-02-02 00:00:00');
        $this->category('tc_no_course_at_all');

        $this->assertSame($inCourse1, (int) TestCategory::get_category_id_for_title('tc_by_title', 1));
        $this->assertSame($inCourse1, (int) TestCategory::get_category_id_for_title('tc_by_title'), 'defaults to the course of the request');
        $this->assertSame(0, TestCategory::get_category_id_for_title('tc_by_titl', 1));
        $this->assertSame($quoted, (int) TestCategory::get_category_id_for_title("tc_o'brien", 1), 'the quote is escaped');
        $this->assertSame(0, TestCategory::get_category_id_for_title("x' OR '1'='1", 1));
        // A category of another course (or of none, or with a deleted link) is never reused.
        $this->assertSame(0, TestCategory::get_category_id_for_title('tc_by_title', 2), 'course 2 has no such category');
        $this->assertSame(0, TestCategory::get_category_id_for_title('tc_only_in_two', 1));
        $this->assertSame($inCourse2, (int) TestCategory::get_category_id_for_title('tc_only_in_two', 2));
        $this->assertSame(0, TestCategory::get_category_id_for_title('tc_deleted_link', 1));
        $this->assertSame(0, TestCategory::get_category_id_for_title('tc_no_course_at_all', 1));
    }

    public function testRemoveCategoryReturnsFalseForAnUnknownCategory(): void
    {
        $this->assertFalse((new TestCategory())->removeCategory(2000000000));
        $this->assertFalse((new TestCategory())->removeCategory(0));
    }

    public function testRemoveCategoryUnlinksItsQuestionsBeforeDeletingIt(): void
    {
        $quiz = $this->quiz();
        $cat = $this->category('tc_to_remove');
        $keep = $this->category('tc_to_keep');
        $q1 = $this->question($quiz, 1, $cat);
        $q2 = $this->question($quiz, 2, $keep);

        try {
            $ok = (new TestCategory())->removeCategory($cat);
        } catch (Throwable $e) {
            // PINNED QUIRK: the repository hard delete needs the resource node a bare fixture does not have; the
            // link removal (raw SQL, first statement) has already run when that happens.
            $ok = null;
        }

        $this->assertSame(0, (int) $this->conn->fetchOne('SELECT COUNT(*) FROM c_quiz_question_rel_category WHERE category_id = ?', [$cat]));
        $this->assertSame($keep, TestCategory::getCategoryForQuestion($q2), 'other links untouched');
        $this->assertSame(0, TestCategory::getCategoryForQuestion($q1));
        $this->assertContains($ok, [true, null]);
    }

    public function testGetCategoryQuestionsNumberQueriesAColumnThatNoLongerExists(): void
    {
        // PINNED QUIRK: dead code (no caller). c_quiz_question_rel_category has no c_id any more, so the count
        // query is a SQL error. Marked @deprecated.
        $cat = new TestCategory();
        $cat->id = $this->category();
        $threw = false;
        try {
            @$cat->getCategoryQuestionsNumber();
        } catch (Throwable) {
            $threw = true;
        }
        $this->assertTrue($threw);
    }

    // ---------------------------------------------------------------- per exercise

    public function testCategoriesOfATestAreTheDistinctCategoriesOfItsQuestions(): void
    {
        $quiz = $this->quiz();
        $other = $this->quiz();
        $a = $this->category('tc_b_cat');
        $b = $this->category('tc_a_cat');
        $foreign = $this->category('tc_foreign');
        $this->question($quiz, 1, $a);
        $this->question($quiz, 2, $a);
        $this->question($quiz, 3, $b);
        $this->question($quiz, 4);
        $this->question($other, 1, $foreign);

        $byId = TestCategory::getListOfCategoriesIDForTest($quiz);
        $byName = TestCategory::getListOfCategoriesNameForTest($quiz);

        $this->assertEqualsCanonicalizing([$a, $b], array_keys($byId));
        $this->assertSame('tc_b_cat', $byId[$a]['title']);
        $this->assertEqualsCanonicalizing([$a, $b], array_keys($byName));
        $this->assertSame(['id' => $a, 'title' => 'tc_b_cat', 'parent_id' => ''], $byName[$a]);
        $this->assertSame([], TestCategory::getListOfCategoriesIDForTest($this->quiz()));
    }

    public function testListOfCategoriesForATestObjectKeepsTheQuestionOrder(): void
    {
        $quiz = $this->quiz();
        $a = $this->category('tc_obj_a');
        $b = $this->category('tc_obj_b');
        $this->question($quiz, 1, $b);
        $this->question($quiz, 2, $a);
        $this->question($quiz, 3, $b);
        $this->question($quiz, 4);
        $exercise = $this->exercise($quiz);

        $ids = TestCategory::getListOfCategoriesIDForTestObject($exercise);
        $full = TestCategory::getListOfCategoriesForTest($exercise);

        $this->assertEqualsCanonicalizing([$a, $b], array_values($ids));
        $this->assertEqualsCanonicalizing([$a, $b], array_keys($full));
        $this->assertSame('tc_obj_a', $full[$a]['title']);
        $this->assertSame($a, (int) $full[$a]['iid']);
    }

    public function testNumberOfQuestionsInACategoryOfATestCountsOnlyThatTest(): void
    {
        $quiz = $this->quiz();
        $other = $this->quiz();
        $a = $this->category('tc_cnt_a');
        $b = $this->category('tc_cnt_b');
        $this->question($quiz, 1, $a);
        $this->question($quiz, 2, $a);
        $this->question($quiz, 3, $b);
        $this->question($quiz, 4);
        $this->question($other, 1, $a);

        $this->assertSame(2, TestCategory::getNumberOfQuestionsInCategoryForTest($quiz, $a));
        $this->assertSame(1, TestCategory::getNumberOfQuestionsInCategoryForTest($quiz, $b));
        $this->assertSame(0, TestCategory::getNumberOfQuestionsInCategoryForTest($quiz, 2000000000));
        $this->assertSame(1, TestCategory::getNumberOfQuestionsInCategoryForTest($other, $a));
    }

    public function testRandomByCategoryCountersNeverCountAnythingBecauseTheyReadTheWrongKey(): void
    {
        $quiz = $this->quiz();
        $a = $this->category('tc_rnd_a');
        $b = $this->category('tc_rnd_b');
        foreach ([1, 2, 3] as $order) {
            $this->question($quiz, $order, $a);
        }
        $this->question($quiz, 4, $b);

        // PINNED QUIRK (bug): the category rows come keyed by "iid" but both loops test $category['id'], which is
        // never set, so every category is skipped: the "random questions per category" total is always 0 and the
        // maximum number of questions in a category is always 0.
        $this->assertSame(0, TestCategory::getNumberOfQuestionRandomByCategory($quiz, 2));
        $this->assertSame(0, TestCategory::getNumberMaxQuestionByCat($quiz));
    }

    public function testQuestionsByCategoryGroupTheQuestionIdsOfOneTest(): void
    {
        $quiz = $this->quiz();
        $other = $this->quiz();
        $a = $this->category('tc_q_a');
        $b = $this->category('tc_q_b');
        $q1 = $this->question($quiz, 1, $a);
        $q2 = $this->question($quiz, 2, $a, true);
        $q3 = $this->question($quiz, 3, $b);
        $this->question($quiz, 4);
        $this->question($other, 1, $a);

        $all = TestCategory::getQuestionsByCat($quiz);

        $this->assertEqualsCanonicalizing([$q1, $q2], $all[$a]);
        $this->assertSame([$q3], $all[$b]);
        $this->assertCount(2, $all, 'questions without a category are not listed');
        $this->assertSame([], TestCategory::getQuestionsByCat($this->quiz()));
    }

    public function testQuestionsByCategoryCanBeRestrictedToAQuestionList(): void
    {
        $quiz = $this->quiz();
        $a = $this->category('tc_r_a');
        $b = $this->category('tc_r_b');
        $q1 = $this->question($quiz, 1, $a);
        $this->question($quiz, 2, $a);
        $q3 = $this->question($quiz, 3, $b);

        $this->assertSame([$a => [$q1]], TestCategory::getQuestionsByCat($quiz, [$q1]));
        $this->assertSame([$a => [$q1], $b => [$q3]], TestCategory::getQuestionsByCat($quiz, [$q1, $q3]));
    }

    public function testQuestionsByCategoryCanBeRestrictedToTheMandatoryQuestions(): void
    {
        $quiz = $this->quiz();
        $a = $this->category('tc_m_a');
        $b = $this->category('tc_m_b');
        $this->question($quiz, 1, $a);
        $mandatory = $this->question($quiz, 2, $a, true);
        $this->question($quiz, 3, $b);

        // The flag lives on c_quiz_question (the filter used to read it from the link table: a SQL error).
        $this->assertSame([$a => [$mandatory]], TestCategory::getQuestionsByCat($quiz, [], [], true));
        $this->assertCount(2, TestCategory::getQuestionsByCat($quiz, [], [], false));
    }

    public function testQuestionsByCategoryKeepsOnlyTheCategoriesAddedToTheTestAndAddsTheUncategorised(): void
    {
        $quiz = $this->quiz();
        $a = $this->category('tc_add_a');
        $b = $this->category('tc_add_b');
        $q1 = $this->question($quiz, 1, $a);
        $q2 = $this->question($quiz, 2, $b);
        $q3 = $this->question($quiz, 3);

        $onlyA = TestCategory::getQuestionsByCat($quiz, [$q1, $q2, $q3], [['category_id' => $a]]);
        $this->assertSame([$a => [$q1]], $onlyA);

        $withGeneral = TestCategory::getQuestionsByCat($quiz, [$q1, $q2, $q3], [['category_id' => $a], ['category_id' => 0]]);
        $this->assertSame([$a => [$q1], 0 => [$q3]], $withGeneral, 'category 0 collects the questions without any category');
    }

    public function testCategoryExerciseTreeListsTheConfiguredCategoriesOfAnExercise(): void
    {
        $quiz = $this->quiz();
        $other = $this->quiz();
        $a = $this->category('tc_t_a');
        $b = $this->category('tc_t_b');
        $this->treeRow($quiz, $a, 3);
        $this->treeRow($quiz, $b, 0);
        $this->treeRow($quiz, null, 2);
        $this->treeRow($other, $a, 9);
        $exercise = $this->exercise($quiz);

        $tree = (new TestCategory())->getCategoryExerciseTree($exercise);

        // The NULL category (questions without category) is keyed by the empty string (a NULL array key).
        $this->assertEqualsCanonicalizing([$a, 0], array_map('intval', array_keys($tree)), 'count_questions = 0 is excluded');
        $this->assertArrayHasKey('', $tree);
        $this->assertSame('3', (string) $tree[$a]['count_questions']);
        $this->assertSame('tc_t_a', $tree[$a]['title']);
        $this->assertSame(get_lang('General'), $tree['']['title'], 'the NULL category is shown as General');

        $all = (new TestCategory())->getCategoryExerciseTree($exercise, null, false, false);
        $this->assertEqualsCanonicalizing([$a, $b, 0], array_map('intval', array_keys($all)));
    }

    public function testCategoryExerciseTreeOrdersByTheGivenTail(): void
    {
        $quiz = $this->quiz();
        $a = $this->category('tc_o_a');
        $b = $this->category('tc_o_b');
        $this->treeRow($quiz, $a, 1);
        $this->treeRow($quiz, $b, 2);
        $exercise = $this->exercise($quiz);

        $asc = array_map('intval', array_keys((new TestCategory())->getCategoryExerciseTree($exercise, 'count_questions ASC')));
        $desc = array_map('intval', array_keys((new TestCategory())->getCategoryExerciseTree($exercise, 'count_questions DESC')));

        $this->assertSame([$a, $b], $asc);
        $this->assertSame([$b, $a], $desc);
        $this->assertSame([], (new TestCategory())->getCategoryExerciseTree($this->exercise($this->quiz())));
    }

    // ---------------------------------------------------------------- pure helpers over the above

    public function testSortTabByBracketLabelSortsTheCategoryBucketsByCategoryName(): void
    {
        $z = $this->category('tc_zz');
        $a = $this->category('tc_aa');
        $m = $this->category('tc_mm');

        $sorted = TestCategory::sortTabByBracketLabel([$z => [1], $a => [2, 3], $m => [4]]);

        $this->assertSame([$a, $m, $z], array_keys($sorted));
        $this->assertSame([2, 3], $sorted[$a]);
    }

    public function testGetNElementsFromArrayKeepsTheMandatoryElementsFirst(): void
    {
        $this->assertSame([1, 2], TestCategory::getNElementsFromArray([1, 2, 3, 4], 2, false));
        $this->assertSame([1, 2, 3], TestCategory::getNElementsFromArray([1, 2, 3], 5, false), 'asking for more returns all');
        $this->assertSame([9], TestCategory::getNElementsFromArray([1, 2, 9], 1, false, [9]));
        $this->assertSame([7, 8], TestCategory::getNElementsFromArray([7, 8, 9], 2, false, [7, 8, 9]), 'too many mandatory: truncated');
        $mixed = TestCategory::getNElementsFromArray([1, 2, 3, 4], 3, false, [4]);
        $this->assertSame(3, count($mixed));
        $this->assertContains(4, $mixed);
    }
}
