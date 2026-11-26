<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Helpers;

use Chamilo\CourseBundle\Entity\CQuizAnswer;

/**
 * Reads the expected position of each item of a sequence ordering (draggable) question.
 *
 * Such a question holds its slots first (correct = 0), then the items to order. The Vue
 * editor stores in the correct of an item the expected position (1, 2...), while the legacy
 * editor, and the data migrated from 1.11, store the iid of the expected slot.
 */
class ExerciseDraggableAnswerHelper
{
    /**
     * @param iterable<CQuizAnswer> $answers all the answers of the question
     *
     * @return array<int, int> the expected position by item iid
     */
    public static function getTargetPositions(iterable $answers): array
    {
        $sorted = [];
        foreach ($answers as $answer) {
            $sorted[] = $answer;
        }
        usort(
            $sorted,
            static fn (CQuizAnswer $left, CQuizAnswer $right): int => [(int) $left->getPosition(), (int) $left->getIid()]
                <=> [(int) $right->getPosition(), (int) $right->getIid()]
        );

        // Numbered in their order, as ExerciseQti2ExportService::exportOrderingItem() does
        $slotPositions = [];
        foreach ($sorted as $answer) {
            if (0 === (int) ($answer->getCorrect() ?? 0)) {
                $slotPositions[(int) $answer->getIid()] = \count($slotPositions) + 1;
            }
        }

        $targetPositions = [];
        foreach ($sorted as $answer) {
            $correct = (int) ($answer->getCorrect() ?? 0);
            if ($correct > 0) {
                $targetPositions[(int) $answer->getIid()] = $slotPositions[$correct] ?? $correct;
            }
        }

        return $targetPositions;
    }
}
