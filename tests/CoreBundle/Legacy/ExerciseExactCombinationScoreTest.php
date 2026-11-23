<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\Tests\ChamiloTestTrait;
use ChamiloSession;
use Doctrine\DBAL\Connection;
use Exercise;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * The legacy grading (Exercise::manage_answer()) of Exact combination questions: the whole question score for
 * exactly the set of correct answers, 0 otherwise, and 0 when the question has no correct answer at all.
 *
 * Quizzes are inserted as bare c_quiz rows and every fixture title starts with "eca_".
 */
final class ExerciseExactCombinationScoreTest extends KernelTestCase
{
    use ChamiloTestTrait;

    private Connection $conn;

    public static function setUpBeforeClass(): void
    {
        // The legacy Exercise class of this checkout, before the class map points elsewhere.
        if (!class_exists(Exercise::class, false)) {
            require_once \dirname(__DIR__, 3).'/public/main/exercise/exercise.class.php';
        }
    }

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        Container::setContainer($container);
        Container::setLegacyServices($container);
        $session = new Session(new MockArraySessionStorage());
        Container::setSession($session);
        $request = Request::create('/main/exercise/exercise_submit.php');
        $request->setSession($session);
        Container::setRequest($request);
        $this->conn = $container->get('doctrine')->getConnection();

        $user = $this->getUser('admin');
        $course = $this->getCourse(1);
        self::assertNotNull($user);
        self::assertNotNull($course);
        ChamiloSession::write('_user', ['id' => $user->getId()]);
        ChamiloSession::write('_real_cid', $course->getId());
        ChamiloSession::write('_cid', $course->getCode());
        ChamiloSession::write('_course', api_get_course_info($course->getCode()));
        ChamiloSession::write('sid', 0);
        ChamiloSession::erase('login_as');
    }

    /**
     * @param list<int> $correctFlags one per answer, in position order
     * @param list<int> $ticked       positions (1-based) the learner ticked
     *
     * @dataProvider exactCombinationProvider
     */
    public function testExactCombinationScore(array $correctFlags, array $ticked, float $expected): void
    {
        [$quizId, $questionId, $answerIds] = $this->seedQuestion($correctFlags);
        $choice = [];
        foreach ($ticked as $position) {
            $choice[$answerIds[$position - 1]] = 1;
        }

        $exercise = new Exercise((int) $this->getCourse(1)?->getId());
        self::assertTrue($exercise->read($quizId));
        $result = $exercise->manage_answer(0, $questionId, $choice, 'exercise_result', [], false, false, false);

        self::assertIsArray($result);
        self::assertEqualsWithDelta($expected, (float) $result['score'], 0.0001);
    }

    /**
     * @return iterable<string, array{list<int>, list<int>, float}>
     */
    public static function exactCombinationProvider(): iterable
    {
        yield 'exactly the correct answers get the whole score' => [[1, 0, 1], [1, 3], 4.0];

        yield 'a missing correct answer gets 0' => [[1, 0, 1], [1], 0.0];

        yield 'an extra wrong answer gets 0' => [[1, 0, 1], [1, 2, 3], 0.0];

        yield 'nothing ticked with correct answers gets 0' => [[1, 0, 1], [], 0.0];

        yield 'no correct answer and nothing ticked gets 0' => [[0, 0, 0], [], 0.0];

        yield 'no correct answer and something ticked gets 0' => [[0, 0, 0], [2], 0.0];
    }

    /**
     * @param list<int> $correctFlags
     *
     * @return array{0: int, 1: int, 2: list<int>}
     */
    private function seedQuestion(array $correctFlags): array
    {
        $this->conn->insert('c_quiz', [
            'title' => 'eca_quiz',
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
        $quizId = (int) $this->conn->lastInsertId();

        $this->conn->insert('c_quiz_question', [
            'question' => 'eca_question',
            'ponderation' => 4,
            'position' => 0,
            'type' => MULTIPLE_ANSWER_COMBINATION,
            'level' => 0,
            'mandatory' => 0,
        ]);
        $questionId = (int) $this->conn->lastInsertId();
        $this->conn->insert('c_quiz_rel_question', ['quiz_id' => $quizId, 'question_id' => $questionId, 'question_order' => 1]);

        $answerIds = [];
        foreach ($correctFlags as $index => $correct) {
            // Chamilo stores the whole question score on the first answer.
            $this->conn->insert('c_quiz_answer', [
                'question_id' => $questionId,
                'answer' => 'eca_answer_'.($index + 1),
                'correct' => $correct,
                'comment' => '',
                'ponderation' => 0 === $index ? 4 : 0,
                'position' => $index + 1,
            ]);
            $answerIds[] = (int) $this->conn->lastInsertId();
        }

        return [$quizId, $questionId, $answerIds];
    }
}
