<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use Answer;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\Tests\ChamiloTestTrait;
use ChamiloSession;
use Doctrine\DBAL\Connection;
use Exercise;
use Question;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * Characterization of the legacy Answer class (public/main/exercise/answer.class.php): how the answers of a quiz
 * question are read (natural/ordered/random), selected, saved, trimmed and duplicated.
 *
 * LIVE (all the question types, exercise.lib.php, exercise.class.php, hotspot_admin, stats.php, imports, course
 * copy): constructor, read, readOrderedBy, getQuestionType, selectAnswerByAutoId, getAnswers (MigrationMoodle
 * plugin), getAnswersList (QTI export), getGradesList (QTI hotspot export), getAnswerByAutoId, isCorrectByAutoId,
 * createAnswer, updateAnswers, save, duplicate and the select* accessors.
 * DEAD (no caller, marked @deprecated): selectAnswerId (repaired, it used to throw).
 *
 * Scoping: Answer reads rows by question id only; nothing here checks that the question belongs to the course
 * of the request. The callers decide (stats.php and exercise_submit_modal.php take their ids from an exercise
 * the course owns), so a caller that feeds a request-supplied question id to it would hand over the answer key.
 *
 * Questions and answers are inserted as bare rows (no resource node: creating nodes deadlocks on the shared
 * database). Every fixture text starts with "an_".
 */
final class AnswerCharacterizationTest extends KernelTestCase
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
        $request = Request::create('/main/exercise/admin.php');
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

    private function question(int $type = UNIQUE_ANSWER, string $title = ''): int
    {
        $this->conn->insert('c_quiz_question', [
            'question' => '' === $title ? 'an_question_'.(++$this->seq) : $title,
            'ponderation' => 1,
            'position' => 0,
            'type' => $type,
            'level' => 0,
            'mandatory' => 0,
        ]);

        return (int) $this->conn->lastInsertId();
    }

    private function answer(int $questionId, string $text, int $position, int $correct = 0, array $o = []): int
    {
        $this->conn->insert('c_quiz_answer', $o + [
            'question_id' => $questionId,
            'answer' => $text,
            'correct' => $correct,
            'comment' => 'comment of '.$text,
            'ponderation' => 2,
            'position' => $position,
        ]);

        return (int) $this->conn->lastInsertId();
    }

    private function exercise(int $randomAnswers = 0): Exercise
    {
        $e = new Exercise(1);
        $e->random_answers = $randomAnswers;

        return $e;
    }

    private function load(int $questionId, int $randomAnswers = 0): Answer
    {
        return new Answer($questionId, 1, $this->exercise($randomAnswers));
    }

    /**
     * @return array<int, array<string, mixed>> position => row, of the answers stored for a question
     */
    private function stored(int $questionId): array
    {
        $rows = $this->conn->fetchAllAssociative(
            'SELECT * FROM c_quiz_answer WHERE question_id = ? ORDER BY position, iid',
            [$questionId]
        );
        $byPosition = [];
        foreach ($rows as $row) {
            $byPosition[(int) $row['position']] = $row;
        }

        return $byPosition;
    }

    // ---------------------------------------------------------------- reading

    public function testConstructorReadsTheAnswersOfThatQuestionByPositionStartingAtOne(): void
    {
        $q = $this->question();
        $other = $this->question();
        $c = $this->answer($q, 'an_third', 3, 0, ['hotspot_coordinates' => '1;2|3;4', 'hotspot_type' => 'square']);
        $a = $this->answer($q, 'an_first', 1, 1);
        $b = $this->answer($q, 'an_second', 2, 0);
        $this->answer($other, 'an_foreign', 1, 1);

        $ans = $this->load($q);

        $this->assertSame(3, $ans->selectNbrAnswers());
        $this->assertSame($q, $ans->selectQuestionId());
        $this->assertSame('an_first', $ans->selectAnswer(1));
        $this->assertSame('an_second', $ans->selectAnswer(2));
        $this->assertSame('an_third', $ans->selectAnswer(3));
        $this->assertNull($ans->selectAnswer(4));
        $this->assertNull($ans->selectAnswer(0), 'indexes start at 1');
        $this->assertSame([$a, $b, $c], [$ans->selectAutoId(1), $ans->selectAutoId(2), $ans->selectAutoId(3)]);
        $this->assertSame(0, $ans->selectAutoId(9));
        $this->assertEquals(1, $ans->isCorrect(1));
        $this->assertEquals(0, $ans->isCorrect(2));
        $this->assertNull($ans->isCorrect(7));
        $this->assertSame('comment of an_second', $ans->selectComment(2));
        $this->assertEquals(2, $ans->selectWeighting(1));
        $this->assertEquals(3, $ans->selectPosition(3));
        $this->assertSame('1;2|3;4', $ans->selectHotspotCoordinates(3));
        $this->assertSame('square', $ans->selectHotspotType(3));
        $this->assertNull($ans->selectHotspotType(1));
    }

    public function testAQuestionWithoutAnswersHasNone(): void
    {
        $ans = $this->load($this->question());

        $this->assertSame(0, $ans->selectNbrAnswers());
        $this->assertSame([], $ans->getAnswersList());
    }

    public function testGetQuestionTypeReadsTheQuestionRowAndGivesNullForAnUnknownQuestion(): void
    {
        $this->assertSame(MULTIPLE_ANSWER, $this->load($this->question(MULTIPLE_ANSWER))->getQuestionType());
        $this->assertSame(HOT_SPOT, $this->load($this->question(HOT_SPOT))->getQuestionType());
        $this->assertNull((new Answer(2000000000, 1, $this->exercise(), false))->getQuestionType());
    }

    public function testReadOrderedByOrdersByTheGivenFieldAndDirection(): void
    {
        $q = $this->question();
        $this->answer($q, 'an_b', 1);
        $this->answer($q, 'an_c', 2);
        $this->answer($q, 'an_a', 3);
        $ans = new Answer($q, 1, $this->exercise(), false);

        $ans->readOrderedBy('answer', 'ASC');
        $this->assertSame(['an_a', 'an_b', 'an_c'], [$ans->selectAnswer(1), $ans->selectAnswer(2), $ans->selectAnswer(3)]);
        $this->assertSame(3, $ans->selectNbrAnswers());

        $ans->readOrderedBy('answer', 'DESC');
        $this->assertSame(['an_c', 'an_b', 'an_a'], [$ans->selectAnswer(1), $ans->selectAnswer(2), $ans->selectAnswer(3)]);

        // anything but ASC/DESC falls back to ASC; an empty field to position
        $ans->readOrderedBy('', 'sideways');
        $this->assertSame(['an_b', 'an_c', 'an_a'], [$ans->selectAnswer(1), $ans->selectAnswer(2), $ans->selectAnswer(3)]);
    }

    public function testReadOrderedByKeepsTheDoubtOptionOfAUniqueAnswerNoOptionQuestionLast(): void
    {
        $q = $this->question(UNIQUE_ANSWER_NO_OPTION);
        $this->answer($q, 'an_doubt', 666, 0);
        $this->answer($q, 'an_x', 1, 1);
        $this->answer($q, 'an_y', 2, 0);
        $ans = new Answer($q, 1, $this->exercise(), false);

        $ans->readOrderedBy('answer', 'ASC');

        $this->assertSame(['an_x', 'an_y', 'an_doubt'], [$ans->selectAnswer(1), $ans->selectAnswer(2), $ans->selectAnswer(3)]);
        $this->assertEquals(666, $ans->selectPosition(3));
        $this->assertSame(3, $ans->selectNbrAnswers());
    }

    public function testReadOrderedByDelegatesDraggableQuestionsToTheNaturalOrder(): void
    {
        $q = $this->question(DRAGGABLE);
        $this->answer($q, 'an_z', 1);
        $this->answer($q, 'an_a', 2);
        $ans = new Answer($q, 1, $this->exercise(), false);

        $ans->readOrderedBy('answer', 'ASC');

        $this->assertSame('an_z', $ans->selectAnswer(1), 'position order, not the requested answer order');
    }

    public function testRandomAnswersReadTheSameRowsInAnyOrderExceptForCalculatedAnswers(): void
    {
        $q = $this->question();
        $texts = ['an_r1', 'an_r2', 'an_r3', 'an_r4'];
        foreach ($texts as $i => $t) {
            $this->answer($q, $t, $i + 1);
        }

        $ans = $this->load($q, 1);

        $got = [];
        for ($i = 1; $i <= $ans->selectNbrAnswers(); $i++) {
            $got[] = $ans->selectAnswer($i);
        }
        sort($got);
        $this->assertSame($texts, $got);

        // 8 answers: a shuffle would keep the natural order with a probability of 1 in 40320
        $calc = $this->question(CALCULATED_ANSWER);
        for ($i = 8; $i >= 1; --$i) {
            $this->answer($calc, 'an_c'.$i, $i);
        }
        $calcAnswer = $this->load($calc, 1);
        $natural = [];
        for ($i = 1; $i <= 8; ++$i) {
            $natural[] = $calcAnswer->selectAnswer($i);
        }
        $this->assertSame(['an_c1', 'an_c2', 'an_c3', 'an_c4', 'an_c5', 'an_c6', 'an_c7', 'an_c8'], $natural, 'calculated answers are never shuffled');
    }

    public function testSelectAnswerByAutoIdReturnsIdAndTextOrFalse(): void
    {
        $q = $this->question();
        $id = $this->answer($q, 'an_auto', 1);
        $ans = $this->load($q);

        $this->assertSame(['iid' => $id, 'answer' => 'an_auto'], $ans->selectAnswerByAutoId($id));
        $this->assertFalse($ans->selectAnswerByAutoId(2000000000));
        $this->assertFalse($ans->selectAnswerByAutoId(0));
    }

    public function testGetAnswersReturnsTheRawRowsOfTheQuestionByPosition(): void
    {
        $q = $this->question();
        $second = $this->answer($q, 'an_row2', 2, 1);
        $first = $this->answer($q, 'an_row1', 1, 0);
        $this->answer($this->question(), 'an_other', 1);

        $rows = $this->load($q)->getAnswers();

        $this->assertSame(['an_row1', 'an_row2'], array_column($rows, 'answer'));
        $this->assertSame([$first, $second], array_column($rows, 'iid'));
        $this->assertSame(1, $rows[1]['correct']);
    }

    public function testGetAnswersListSkipsEmptyAnswersAndCanDecodeEntities(): void
    {
        $q = $this->question(HOT_SPOT);
        $this->answer($q, 'an_&amp;_one', 1, 1, ['hotspot_coordinates' => '0;0|9;9', 'hotspot_type' => 'square']);
        $this->answer($q, '', 2, 0);
        $this->answer($q, 'an_two', 3, 0);
        $ans = $this->load($q);

        $list = $ans->getAnswersList();

        $this->assertSame([1, 3], array_column($list, 'id'), 'the empty answer is skipped but keeps its index');
        $this->assertSame('an_&amp;_one', $list[0]['answer']);
        $this->assertSame('0;0|9;9', $list[0]['hotspot_coord']);
        $this->assertSame('square', $list[0]['hotspot_type']);
        $this->assertEquals(2, $list[0]['grade']);
        $this->assertEquals(1, $list[0]['correct']);

        $decoded = $ans->getAnswersList(true);
        $this->assertSame('an_&_one', $decoded[0]['answer']);
    }

    public function testGetGradesListHoldsTheWeightingOfEveryNonEmptyAnswer(): void
    {
        $q = $this->question(HOT_SPOT);
        $this->answer($q, 'an_g1', 1);
        $this->answer($q, '', 2, 0, ['ponderation' => 3]);
        $this->answer($q, 'an_g3', 3, 0, ['ponderation' => 7]);
        $ans = $this->load($q);

        // 1-based like every other array of the class; the empty answer is skipped. The loop used to run from 0 to
        // nbrAnswers - 1, so the last answer was always missing.
        $this->assertSame([1 => 2.0, 3 => 7.0], array_map('floatval', $ans->getGradesList()));
    }

    public function testLookupsByAutoIdAndByPosition(): void
    {
        $q = $this->question();
        $a = $this->answer($q, 'an_l1', 1, 1);
        $b = $this->answer($q, 'an_l2', 5, 0);
        $ans = $this->load($q);

        $this->assertSame(['answer' => 'an_l1', 'correct' => 1, 'comment' => 'comment of an_l1'], $ans->getAnswerByAutoId($a));
        $this->assertSame([], $ans->getAnswerByAutoId(2000000000));
        $this->assertTrue($ans->isCorrectByAutoId($a));
        $this->assertFalse($ans->isCorrectByAutoId($b));
        $this->assertFalse($ans->isCorrectByAutoId(2000000000));
        $this->assertSame(2, $ans->selectAnswerIdByPosition(5));
        $this->assertFalse($ans->selectAnswerIdByPosition(99));
    }

    public function testSelectAnswerIdListsTheAnswerIdsOfTheQuestion(): void
    {
        $q = $this->question();
        $first = $this->answer($q, 'an_dead1', 1);
        $second = $this->answer($q, 'an_dead2', 2);
        $this->answer($this->question(), 'an_foreign', 1);
        $ans = $this->load($q);

        // Dead code (no caller, @deprecated). It used to select "id"/"c_id" (columns dropped; the key is iid) and
        // read the undefined property $this->course_id, so it was a SQL error.
        $this->assertEqualsCanonicalizing([$first, $second], array_map('intval', $ans->selectAnswerId()));
        $this->assertSame([], $this->load($this->question())->selectAnswerId());
    }

    // ---------------------------------------------------------------- writing

    public function testSaveInsertsNewAnswersWithTheirPositionsAndThenMovesThemIntoTheMainArrays(): void
    {
        $q = $this->question();
        $ans = new Answer($q, 1, $this->exercise(), false);
        $ans->createAnswer('an_n1', 1, 'fb1', 3, 1);
        $ans->createAnswer('an_n2', 0, 'fb2', 4.5, 2, '1;1|2;2', 'circle');

        $ans->save();

        $stored = $this->stored($q);
        $this->assertSame(['an_n1', 'an_n2'], [$stored[1]['answer'], $stored[2]['answer']]);
        $this->assertSame(['1', '0'], [(string) $stored[1]['correct'], (string) $stored[2]['correct']]);
        $this->assertSame(['fb1', 'fb2'], [$stored[1]['comment'], $stored[2]['comment']]);
        $this->assertEquals(4.5, $stored[2]['ponderation']);
        $this->assertSame('1;1|2;2', $stored[2]['hotspot_coordinates']);
        $this->assertSame('circle', $stored[2]['hotspot_type']);
        // the new arrays became the current ones and were cleared
        $this->assertSame(2, $ans->selectNbrAnswers());
        $this->assertSame('an_n2', $ans->selectAnswer(2));
        $this->assertSame([], $ans->new_answer);
        $this->assertSame(0, $ans->new_nbrAnswers);
    }

    public function testSaveUpdatesExistingAnswersInPlaceAndDeletesTheTrailingOnes(): void
    {
        $q = $this->question();
        $first = $this->answer($q, 'an_old1', 1, 1);
        $second = $this->answer($q, 'an_old2', 2, 0);
        $third = $this->answer($q, 'an_old3', 3, 0);
        $ans = $this->load($q);

        $ans->createAnswer('an_new1', 0, 'new fb1', 9, 1);
        $ans->createAnswer('an_new2', 1, 'new fb2', 8, 2);
        $ans->save();

        $rows = $this->conn->fetchAllAssociative('SELECT * FROM c_quiz_answer WHERE question_id = ? ORDER BY position', [$q]);
        $this->assertCount(2, $rows, 'the third answer is gone (fewer answers saved)');
        $this->assertSame([$first, $second], array_map(static fn (array $r): int => (int) $r['iid'], $rows), 'rows updated in place, same ids');
        $this->assertSame(['an_new1', 'an_new2'], array_column($rows, 'answer'));
        $this->assertSame(['0', '1'], array_map('strval', array_column($rows, 'correct')));
        $this->assertEquals(9, $rows[0]['ponderation']);
        $this->assertSame(0, (int) $this->conn->fetchOne('SELECT COUNT(*) FROM c_quiz_answer WHERE iid = ?', [$third]));
    }

    public function testSaveOnlyDeletesTheTrailingAnswersOfItsOwnQuestion(): void
    {
        $q = $this->question();
        $other = $this->question();
        $this->answer($q, 'an_mine1', 1);
        $this->answer($q, 'an_mine2', 2);
        $this->answer($other, 'an_other1', 1);
        $this->answer($other, 'an_other2', 2);
        $ans = $this->load($q);

        $ans->createAnswer('an_only', 1, '', 1, 1);
        $ans->save();

        $this->assertCount(1, $this->stored($q));
        $this->assertCount(2, $this->stored($other), 'the delete is bound to the question id');
    }

    public function testSaveOfMatchingAnswersPointsCorrectAtTheAnswerAutoId(): void
    {
        $q = $this->question(MATCHING);
        $ans = new Answer($q, 1, $this->exercise(), false);
        // positions 1..2 are the left column items (correct = position of the right item), 3.. the options
        $ans->createAnswer('an_right', 0, '', 0, 1);
        $ans->createAnswer('an_left', 1, '', 1, 2);

        $ans->save();

        $stored = $this->stored($q);
        $this->assertSame((string) $stored[1]['iid'], (string) $stored[2]['correct'], 'correct holds the auto id of the target');
    }

    public function testSaveOfMatchingAnswersLeavesAForwardReferenceAsARawPosition(): void
    {
        $q = $this->question(MATCHING);
        $ans = new Answer($q, 1, $this->exercise(), false);
        $ans->createAnswer('an_left', 2, '', 1, 1);
        $ans->createAnswer('an_right', 0, '', 0, 2);

        $ans->save();

        // PINNED QUIRK: the target (position 2) is not stored yet when the left item is inserted, so its "correct"
        // stays the raw position 2 instead of the auto id (callers must save the targets first).
        $stored = $this->stored($q);
        $this->assertSame('2', (string) $stored[1]['correct']);
    }

    public function testSaveOfDraggableAnswersRewritesCorrectToTheSavedAutoIds(): void
    {
        $q = $this->question(DRAGGABLE);
        $ans = new Answer($q, 1, $this->exercise(), false);
        $ans->createAnswer('an_d1', 2, '', 1, 1);
        $ans->createAnswer('an_d2', 0, '', 1, 2);

        $ans->save();

        $stored = $this->stored($q);
        $this->assertSame((string) $stored[2]['iid'], (string) $stored[1]['correct']);
        $this->assertSame('0', (string) $stored[2]['correct'], 'an empty status is left alone');
    }

    public function testSaveOfMatchingDraggableAnswersRewritesCorrectToTheSavedAutoIds(): void
    {
        foreach ([MATCHING_DRAGGABLE, MATCHING_DRAGGABLE_COMBINATION] as $type) {
            $q = $this->question($type);
            $ans = new Answer($q, 1, $this->exercise(), false);
            $ans->createAnswer('an_target', 0, '', 0, 1);
            $ans->createAnswer('an_item', 1, '', 1, 2);

            $ans->save();

            $stored = $this->stored($q);
            $this->assertSame((string) $stored[1]['iid'], (string) $stored[2]['correct'], 'type '.$type);
            $this->assertSame('0', (string) $stored[1]['correct'], 'type '.$type);
        }
    }

    public function testUpdateAnswersChangesOneRowAndReturnsFalseForAnUnknownId(): void
    {
        $q = $this->question();
        $id = $this->answer($q, 'an_before', 1, 0);
        $neighbour = $this->answer($q, 'an_neighbour', 2, 1);
        $ans = $this->load($q);

        $entity = $ans->updateAnswers($id, 'an_after', 'new comment', 1, 6, 4, null, '5;5', 'square');

        $this->assertNotFalse($entity);
        $row = $this->conn->fetchAssociative('SELECT * FROM c_quiz_answer WHERE iid = ?', [$id]);
        $this->assertSame('an_after', $row['answer']);
        $this->assertSame('new comment', $row['comment']);
        $this->assertSame('1', (string) $row['correct']);
        $this->assertEquals(6, $row['ponderation']);
        $this->assertSame('4', (string) $row['position']);
        $this->assertSame('5;5', $row['hotspot_coordinates']);
        $this->assertSame('an_neighbour', $this->conn->fetchOne('SELECT answer FROM c_quiz_answer WHERE iid = ?', [$neighbour]));
        $this->assertFalse($ans->updateAnswers(2000000000, 'x', 'x', 0, 0, 1, null, null, null));
    }

    public function testDuplicateCopiesTheAnswersOntoTheNewQuestionAndLeavesTheOriginalAlone(): void
    {
        $src = $this->question();
        $this->answer($src, 'an_dup1', 1, 1, ['ponderation' => 5]);
        $this->answer($src, 'an_dup2', 2, 0, ['hotspot_coordinates' => '1;1', 'hotspot_type' => 'square']);
        $dst = $this->question();
        $ans = $this->load($src);

        $ans->duplicate(Question::read($dst, api_get_course_info(), false));

        $copy = $this->stored($dst);
        $this->assertSame(['an_dup1', 'an_dup2'], [$copy[1]['answer'], $copy[2]['answer']]);
        $this->assertEquals(5, $copy[1]['ponderation']);
        $this->assertSame('1', (string) $copy[1]['correct']);
        $this->assertSame('square', $copy[2]['hotspot_type']);
        $this->assertCount(2, $this->stored($src));
        $this->assertNotSame($this->stored($src)[1]['iid'], $copy[1]['iid'], 'new rows, not the same ones');
    }

    public function testDuplicateOfAQuestionWithoutAnswersDoesNothing(): void
    {
        $src = $this->question();
        $dst = $this->question();

        $this->load($src)->duplicate(Question::read($dst, api_get_course_info(), false));

        $this->assertSame([], $this->stored($dst));
    }

    public function testDuplicateOfMatchingAnswersRemapsCorrectToTheCopiedRows(): void
    {
        $src = $this->question(MATCHING);
        $leftId = $this->answer($src, 'an_ml', 1, 0);
        $rightId = $this->answer($src, 'an_mr', 2, 0);
        $this->conn->executeStatement('UPDATE c_quiz_answer SET correct = ? WHERE iid = ?', [$rightId, $leftId]);
        $dst = $this->question(MATCHING);

        $this->load($src)->duplicate(Question::read($dst, api_get_course_info(), false));

        $copy = $this->stored($dst);
        $this->assertSame((string) $copy[2]['iid'], (string) $copy[1]['correct'], 'correct points at the NEW right-hand row');
        $this->assertNotSame((string) $rightId, (string) $copy[1]['correct']);
    }
}
