<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Component\Tracking;

use Chamilo\CoreBundle\Component\Tracking\LearnpathQuizTimeCalculator;
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 6).'/src/Chamilo/CoreBundle/Component/Tracking/LearnpathQuizTimeCalculator.php';

final class LearnpathQuizTimeCalculatorTest extends TestCase
{
    public function testCountsEveryPageOfAnEmbeddedQuizInBothTotals(): void
    {
        $events = [
            $this->event(0, 55, 'learnpath_id', 101),
            $this->event(60, 55, '101', 101),
            $this->event(120, 55, '101', 101),
            $this->event(180, 55, '101', 101),
        ];

        self::assertSame(
            ['quiz_time' => 180, 'learnpath_time' => [101 => 180]],
            LearnpathQuizTimeCalculator::calculate($events)
        );
    }

    public function testReopenedIntroductionDoesNotBridgeTimeAwayFromQuiz(): void
    {
        $events = [
            $this->event(0, 55, 'learnpath_id', 101),
            $this->event(480, 55, 'learnpath_id', 101),
            $this->event(540, 55, '101', 101),
            $this->event(600, 55, '101', 101),
        ];

        self::assertSame(
            ['quiz_time' => 120, 'learnpath_time' => [101 => 120]],
            LearnpathQuizTimeCalculator::calculate($events)
        );
    }

    public function testDoesNotBridgeLearningPathsForTheSameExercise(): void
    {
        $events = [
            $this->event(0, 55, 'learnpath_id', 101),
            $this->event(480, 55, 'learnpath_id', 202),
            $this->event(540, 55, '202', 202),
            $this->event(600, 55, '202', 202),
        ];

        self::assertSame(
            ['quiz_time' => 120, 'learnpath_time' => [202 => 120]],
            LearnpathQuizTimeCalculator::calculate($events)
        );
    }

    public function testDoesNotBridgeDifferentExercisesInTheSameLearningPath(): void
    {
        $events = [
            $this->event(0, 55, '101', 101),
            $this->event(600, 66, '101', 101),
            $this->event(660, 66, '101', 101),
        ];

        self::assertSame(
            ['quiz_time' => 60, 'learnpath_time' => [101 => 60]],
            LearnpathQuizTimeCalculator::calculate($events)
        );
    }

    public function testStandaloneQuizDoesNotInflateLearningPathTime(): void
    {
        $events = [
            $this->event(0, 55, '', '', '/main/exercise/overview.php'),
            $this->event(60, 55, '', '', '/main/exercise/exercise_submit.php'),
        ];

        self::assertSame(
            ['quiz_time' => 60, 'learnpath_time' => []],
            LearnpathQuizTimeCalculator::calculate($events)
        );
    }

    public function testReopenedStandaloneQuizDoesNotBridgeTimeSpentElsewhere(): void
    {
        $events = [
            $this->event(0, 55, '', '', '/main/exercise/overview.php'),
            $this->event(60, 55, '', '', '/main/exercise/exercise_submit.php'),
            $this->event(600, 55, '', '', '/main/exercise/overview.php'),
            $this->event(660, 55, '', '', '/main/exercise/exercise_submit.php'),
        ];

        self::assertSame(
            ['quiz_time' => 120, 'learnpath_time' => []],
            LearnpathQuizTimeCalculator::calculate($events)
        );
    }

    public function testIgnoresNonPositiveIntervals(): void
    {
        $events = [
            $this->event(60, 55, '101', 101),
            $this->event(60, 55, '101', 101),
            $this->event(30, 55, '101', 101),
        ];

        self::assertSame(
            ['quiz_time' => 0, 'learnpath_time' => []],
            LearnpathQuizTimeCalculator::calculate($events)
        );
    }

    /**
     * @param int|string $action
     * @param int|string $learnpathId
     *
     * @return array<string, int|string>
     */
    private function event(int $timestamp, int $exerciseId, $action, $learnpathId, string $url = ''): array
    {
        return [
            'date_reg' => $timestamp,
            'tool_id' => $exerciseId,
            'action' => $action,
            'action_details' => $learnpathId,
            'url' => $url,
        ];
    }
}
