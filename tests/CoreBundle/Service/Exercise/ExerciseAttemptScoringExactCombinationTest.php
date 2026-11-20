<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service\Exercise;

use Chamilo\CoreBundle\Entity\TrackEAttempt;
use Chamilo\CoreBundle\Service\Exercise\ExerciseAttemptScoringService;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Entity\CQuizAnswer;
use Chamilo\CourseBundle\Entity\CQuizQuestion;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionProperty;

/**
 * Exact combination questions are scored all or nothing by the 3.x scorer: the whole question score for exactly
 * the set of correct answers, 0 otherwise, and 0 when the question has no correct answer at all.
 */
final class ExerciseAttemptScoringExactCombinationTest extends TestCase
{
    private const MULTIPLE_ANSWER_COMBINATION = 9;
    private const MULTIPLE_ANSWER_COMBINATION_TRUE_FALSE = 12;

    /**
     * @param list<int> $correctFlags one per answer, in position order
     * @param list<int> $ticked       positions (1-based) the learner ticked
     *
     * @dataProvider exactCombinationProvider
     */
    public function testExactCombinationScore(array $correctFlags, array $ticked, float $expected): void
    {
        $question = $this->question(self::MULTIPLE_ANSWER_COMBINATION);
        $answers = $this->answers($question, $correctFlags);
        $rows = array_map(fn (int $position): TrackEAttempt => $this->row((string) (100 + $position)), $ticked);

        self::assertSame($expected, $this->score($question, $answers, $rows));
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

        yield 'no answer at all gets 0' => [[], [], 0.0];
    }

    public function testTrueFalseCombinationIsUnchanged(): void
    {
        $question = $this->question(self::MULTIPLE_ANSWER_COMBINATION_TRUE_FALSE);
        // Every statement is either true (1) or false (2), so nothing ticked never matches.
        $answers = $this->answers($question, [1, 2]);

        self::assertSame(4.0, $this->score($question, $answers, [$this->row('101:1'), $this->row('102:2')]));
        self::assertSame(0.0, $this->score($question, $answers, [$this->row('101:1'), $this->row('102:1')]));
        self::assertSame(0.0, $this->score($question, $answers, []));
    }

    private function question(int $type): CQuizQuestion
    {
        $question = (new CQuizQuestion())->setQuestion('Which cities are in France?')->setType($type)->setPonderation(4.0);
        (new ReflectionProperty($question, 'iid'))->setValue($question, 50);

        return $question;
    }

    /**
     * Answers with iids 101, 102...; the whole score is stored on the first one, as Chamilo does.
     *
     * @param list<int> $correctFlags
     *
     * @return array<int, CQuizAnswer>
     */
    private function answers(CQuizQuestion $question, array $correctFlags): array
    {
        $answers = [];
        foreach ($correctFlags as $index => $correct) {
            $answer = (new CQuizAnswer())
                ->setQuestion($question)
                ->setAnswer('Answer '.($index + 1))
                ->setCorrect($correct)
                ->setPonderation(0 === $index ? 4.0 : 0.0)
                ->setPosition($index + 1)
            ;
            (new ReflectionProperty($answer, 'iid'))->setValue($answer, 101 + $index);
            $answers[101 + $index] = $answer;
        }

        return $answers;
    }

    private function row(string $answer): TrackEAttempt
    {
        return (new TrackEAttempt())->setAnswer($answer);
    }

    /**
     * @param array<int, CQuizAnswer>   $answers
     * @param array<int, TrackEAttempt> $rows
     */
    private function score(CQuizQuestion $question, array $answers, array $rows): float
    {
        $service = (new ReflectionClass(ExerciseAttemptScoringService::class))->newInstanceWithoutConstructor();

        return $service->scoreQuestion(new CQuiz(), $question, $answers, [], $rows);
    }
}
