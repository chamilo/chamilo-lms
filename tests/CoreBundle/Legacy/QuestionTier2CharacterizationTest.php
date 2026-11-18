<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\Tests\ChamiloTestTrait;
use ChamiloSession;
use Doctrine\DBAL\Connection;
use Question;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * Assertions the other Question characterization file leaves unpinned: the order of a question is looked up in the
 * quiz it is removed from, not in another quiz it also belongs to.
 *
 * search_engine_edit() is not covered: it only runs when the xapian extension is loaded.
 */
final class QuestionTier2CharacterizationTest extends KernelTestCase
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
        $request = Request::create('/main/exercise/question.class.php');
        $request->setSession($session);
        Container::setRequest($request);

        $this->conn = $container->get('doctrine')->getConnection();
        $_SERVER['HTTP_USER_AGENT'] = 'qs_agent';
        $_SERVER['REQUEST_URI'] = '/qs_uri';

        $course = $this->getCourse(1);
        ChamiloSession::write('_user', ['id' => $this->getUser('admin')->getId()]);
        ChamiloSession::write('_real_cid', $course->getId());
        ChamiloSession::write('_cid', $course->getCode());
        ChamiloSession::write('_course', api_get_course_info($course->getCode()));
        ChamiloSession::write('sid', 0);
        ChamiloSession::erase('login_as');
    }

    private function makeQuiz(): int
    {
        $this->conn->insert('c_quiz', [
            'title' => 'qs2_quiz_'.(++$this->seq),
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

    private function makeQuestion(string $title): int
    {
        $this->conn->insert('c_quiz_question', [
            'question' => $title,
            'ponderation' => 1,
            'position' => 0,
            'type' => 1,
            'level' => 0,
            'mandatory' => 0,
        ]);

        return (int) $this->conn->lastInsertId();
    }

    private function link(int $quizId, int $questionId, int $order): void
    {
        $this->conn->insert('c_quiz_rel_question', ['quiz_id' => $quizId, 'question_id' => $questionId, 'question_order' => $order]);
    }

    /**
     * @return array<int,int> question_id => question_order for a quiz
     */
    private function orders(int $quizId): array
    {
        return array_map('intval', $this->conn->fetchAllKeyValue(
            'SELECT question_id, question_order FROM c_quiz_rel_question WHERE quiz_id = ? ORDER BY question_order',
            [$quizId]
        ));
    }

    public function testRemoveFromListRenumbersWithTheOrderOfTheQuestionInThatQuizEvenIfItHasAnotherOrderElsewhere(): void
    {
        $quiz = $this->makeQuiz();
        $other = $this->makeQuiz();
        $a = $this->makeQuestion('qs2_a');
        $b = $this->makeQuestion('qs2_b');
        $c = $this->makeQuestion('qs2_c');
        $this->link($quiz, $a, 1);
        $this->link($quiz, $b, 2);
        $this->link($quiz, $c, 3);
        $this->link($other, $b, 7);

        $question = Question::read($b, api_get_course_info(), false);
        self::assertInstanceOf(Question::class, $question);
        $question->exerciseList = [$quiz, $other];

        self::assertTrue($question->removeFromList($quiz));

        self::assertSame([$a => 1, $c => 2], $this->orders($quiz));
        self::assertSame([$b => 7], $this->orders($other));
    }
}
