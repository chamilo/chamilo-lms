<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service\Exercise;

use Chamilo\CoreBundle\Entity\TrackEAttempt;
use Chamilo\CoreBundle\Helpers\ExerciseDraggableAnswerHelper;
use Chamilo\CoreBundle\Service\Exercise\ExerciseAttemptScoringService;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Entity\CQuizAnswer;
use Chamilo\CourseBundle\Entity\CQuizQuestion;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionProperty;

/**
 * A sequence ordering question stores the expected slot of each item in its correct field: as a position
 * when the Vue editor saved it, as the iid of the slot when the legacy editor did or when it comes from
 * 1.11. A right order used to score 0 with the second form, the position never matching an iid - refs BT#23866.
 */
final class ExerciseAttemptScoringDraggableTest extends TestCase
{
    private const DRAGGABLE = 18;

    // The question 14266 of the report: two slots, then "pünktlich" expected second and "Sei" first
    private const SLOT_1 = 52109;
    private const SLOT_2 = 52110;
    private const PUENKTLICH = 52111;
    private const SEI = 52112;

    /**
     * @param array<int, int> $order selected position by item iid
     *
     * @dataProvider orderProvider
     */
    public function testOrderScore(bool $legacyFormat, array $order, float $expected): void
    {
        $answers = $this->answers($legacyFormat);
        $rows = [];
        foreach ($order as $itemId => $selectedPosition) {
            // As the runtime saves them: the item in position, the chosen slot in answer
            $rows[] = (new TrackEAttempt())->setPosition($itemId)->setAnswer((string) $selectedPosition);
        }

        $service = (new ReflectionClass(ExerciseAttemptScoringService::class))->newInstanceWithoutConstructor();

        self::assertSame($expected, $service->scoreQuestion(new CQuiz(), $this->question(), $answers, [], $rows));
    }

    /**
     * @return iterable<string, array{bool, array<int, int>, float}>
     */
    public static function orderProvider(): iterable
    {
        foreach (['position' => false, 'slot iid' => true] as $format => $legacyFormat) {
            yield $format.': the right order gets every point' => [$legacyFormat, [self::SEI => 1, self::PUENKTLICH => 2], 2.0];

            yield $format.': a swapped order gets nothing' => [$legacyFormat, [self::SEI => 2, self::PUENKTLICH => 1], 0.0];

            yield $format.': one item in its slot gets its points' => [$legacyFormat, [self::SEI => 1], 1.0];
        }
    }

    public function testBothFormatsGiveTheSameExpectedPositions(): void
    {
        $expected = [self::PUENKTLICH => 2, self::SEI => 1];

        self::assertSame($expected, ExerciseDraggableAnswerHelper::getTargetPositions($this->answers(false)));
        self::assertSame($expected, ExerciseDraggableAnswerHelper::getTargetPositions($this->answers(true)));
    }

    private function question(): CQuizQuestion
    {
        $question = (new CQuizQuestion())->setQuestion('Sei pünktlich')->setType(self::DRAGGABLE)->setPonderation(2.0);
        (new ReflectionProperty($question, 'iid'))->setValue($question, 14266);

        return $question;
    }

    /**
     * @return array<int, CQuizAnswer>
     */
    private function answers(bool $legacyFormat): array
    {
        $rows = [
            [self::SLOT_1, '1', 0, 0.0],
            [self::SLOT_2, '2', 0, 0.0],
            [self::PUENKTLICH, 'pünktlich', $legacyFormat ? self::SLOT_2 : 2, 1.0],
            [self::SEI, 'Sei', $legacyFormat ? self::SLOT_1 : 1, 1.0],
        ];

        $answers = [];
        foreach ($rows as $index => [$iid, $text, $correct, $ponderation]) {
            $answer = (new CQuizAnswer())
                ->setQuestion($this->question())
                ->setAnswer($text)
                ->setCorrect($correct)
                ->setPonderation($ponderation)
                ->setPosition($index + 1)
            ;
            (new ReflectionProperty($answer, 'iid'))->setValue($answer, $iid);
            $answers[$iid] = $answer;
        }

        return $answers;
    }
}
