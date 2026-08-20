<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Gradebook;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\GradebookCategory;
use Chamilo\CoreBundle\Entity\GradebookEvaluation;
use Chamilo\CoreBundle\Entity\GradebookLink;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;

use const SORT_NUMERIC;

final readonly class GradebookLearnerStatisticsCalculator
{
    public function __construct(
        private GradebookScoreCalculator $scoreCalculator,
    ) {}

    /**
     * @param list<User> $students
     *
     * @return array{
     *     ranking: array{position: int, total: int}|null,
     *     best: array<string, mixed>|null,
     *     average: array<string, mixed>|null
     * }
     */
    public function calculate(
        GradebookCategory|GradebookEvaluation|GradebookLink $item,
        User $currentUser,
        array $students,
        Course $course,
        ?Session $session,
    ): array {
        $scores = [];
        $results = [];
        $totalStudents = \count($students);

        foreach ($students as $student) {
            if (!$student instanceof User || null === $student->getId()) {
                continue;
            }

            $result = $this->calculateItem($item, $student, $course, $session);
            $userId = (int) $student->getId();
            $rawScore = true === ($result['hasResult'] ?? false) && is_numeric($result['score'] ?? null)
                ? (float) $result['score']
                : 0.0;
            $scores[$userId] = $rawScore;
            if (true === ($result['hasResult'] ?? false)) {
                $results[$userId] = $result;
            }
        }

        if ([] === $scores) {
            return ['ranking' => null, 'best' => null, 'average' => null];
        }

        arsort($scores, SORT_NUMERIC);
        $ranking = $this->resolveRanking((int) $currentUser->getId(), $scores, $totalStudents);
        $best = null;
        if ([] !== $results) {
            $bestUserId = array_key_first($scores);
            if (null !== $bestUserId && isset($results[$bestUserId])) {
                $best = $results[$bestUserId];
            } else {
                foreach ($scores as $userId => $score) {
                    if (isset($results[$userId])) {
                        $best = $results[$userId];

                        break;
                    }
                }
            }
        }

        $average = $this->calculateAverage($results);

        return [
            'ranking' => $ranking,
            'best' => $best,
            'average' => $average,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function calculateItem(
        GradebookCategory|GradebookEvaluation|GradebookLink $item,
        User $user,
        Course $course,
        ?Session $session,
    ): array {
        if ($item instanceof GradebookCategory) {
            return $this->scoreCalculator->calculateCategory($item, $user, $course, $session);
        }

        if ($item instanceof GradebookEvaluation) {
            $result = $this->scoreCalculator->calculateEvaluation($item, $user);
            $itemCategory = $item->getCategory();
            if (!$itemCategory instanceof GradebookCategory) {
                return $result;
            }
            $configured = $this->scoreCalculator->calculateConfiguredItem(
                $item,
                $itemCategory,
                $user,
                $course,
                $session,
            );

            return null !== $configured ? $configured : $result;
        }

        $result = $this->scoreCalculator->calculateLink($item, $user, $course, $session);
        $itemCategory = $item->getCategory();
        if (!$itemCategory instanceof GradebookCategory) {
            return $result;
        }
        $configured = $this->scoreCalculator->calculateConfiguredItem(
            $item,
            $itemCategory,
            $user,
            $course,
            $session,
        );

        return null !== $configured ? $configured : $result;
    }

    /**
     * @param array<int, float> $scores
     *
     * @return array{position: int, total: int}|null
     */
    private function resolveRanking(int $userId, array $scores, int $totalStudents): ?array
    {
        $previousScore = null;
        $ranking = 0;
        $position = 0;

        foreach ($scores as $scoreUserId => $score) {
            ++$position;
            if (null === $previousScore || $score < $previousScore) {
                $ranking = $position;
            }
            $previousScore = $score;
            if ($scoreUserId === $userId) {
                return [
                    'position' => $ranking,
                    'total' => max($totalStudents, \count($scores)),
                ];
            }
        }

        return null;
    }

    /**
     * @param array<int, array<string, mixed>> $results
     *
     * @return array<string, mixed>|null
     */
    private function calculateAverage(array $results): ?array
    {
        if ([] === $results) {
            return null;
        }

        $scoreSum = 0.0;
        $maxScoreSum = 0.0;
        $scoreCount = 0;
        $maxScoreCount = 0;
        $percentageSum = 0.0;
        $percentageCount = 0;

        foreach ($results as $result) {
            if (is_numeric($result['score'] ?? null)) {
                $scoreSum += (float) $result['score'];
                ++$scoreCount;
            }
            if (is_numeric($result['maxScore'] ?? null)) {
                $maxScoreSum += (float) $result['maxScore'];
                ++$maxScoreCount;
            }
            if (is_numeric($result['percentage'] ?? null)) {
                $percentageSum += (float) $result['percentage'];
                ++$percentageCount;
            }
        }

        if (0 === $scoreCount && 0 === $percentageCount) {
            return null;
        }

        return [
            'score' => $scoreCount > 0 ? $scoreSum / $scoreCount : null,
            'maxScore' => $maxScoreCount > 0 ? $maxScoreSum / $maxScoreCount : null,
            'percentage' => $percentageCount > 0 ? $percentageSum / $percentageCount : null,
            'hasResult' => true,
        ];
    }
}
