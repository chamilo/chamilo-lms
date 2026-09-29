<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Component\Tracking;

final class LearnpathQuizTimeCalculator
{
    /**
     * Calculate active quiz intervals from chronologically ordered access rows.
     *
     * A new learning-path quiz introduction is an interval boundary. Requiring
     * the same exercise and learning-path context also prevents time away from
     * a quiz, another exercise or another learning path from being counted.
     * Standalone quiz intervals contribute only to quiz time.
     *
     * @param array<int, array<string, mixed>> $events
     *
     * @return array{quiz_time: int, learnpath_time: array<int, int>}
     */
    public static function calculate(array $events): array
    {
        $result = [
            'quiz_time' => 0,
            'learnpath_time' => [],
        ];
        $previous = null;

        foreach ($events as $current) {
            if (null === $previous) {
                $previous = $current;
                continue;
            }

            $elapsed = (int) ($current['date_reg'] ?? 0) - (int) ($previous['date_reg'] ?? 0);
            $learnpathId = (int) ($current['action_details'] ?? 0);
            $previousLearnpathId = (int) ($previous['action_details'] ?? 0);
            $exerciseId = (int) ($current['tool_id'] ?? 0);
            $previousExerciseId = (int) ($previous['tool_id'] ?? 0);
            $isIntroduction = self::isIntroduction($current);

            if (
                $elapsed > 0 &&
                $exerciseId > 0 &&
                $exerciseId === $previousExerciseId &&
                $learnpathId === $previousLearnpathId &&
                !$isIntroduction
            ) {
                $result['quiz_time'] += $elapsed;

                if ($learnpathId > 0) {
                    if (!isset($result['learnpath_time'][$learnpathId])) {
                        $result['learnpath_time'][$learnpathId] = 0;
                    }
                    $result['learnpath_time'][$learnpathId] += $elapsed;
                }
            }

            $previous = $current;
        }

        return $result;
    }

    /**
     * Learning-path introductions have an explicit action marker. Standalone
     * introductions use empty action fields, so their logged request path is
     * needed to distinguish them from question and result events.
     *
     * @param array<string, mixed> $event
     */
    private static function isIntroduction(array $event): bool
    {
        if ('learnpath_id' === ($event['action'] ?? '')) {
            return true;
        }

        $path = parse_url((string) ($event['url'] ?? ''), PHP_URL_PATH);
        $script = basename((string) $path);

        return in_array($script, ['overview.php', 'exercise.php'], true);
    }
}
