<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Exercise;

use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Entity\CLpItem;
use Chamilo\CourseBundle\Entity\CQuiz;
use InvalidArgumentException;

final class ExerciseLearningPathItemFactory
{
    public function create(
        CLp $learningPath,
        CQuiz $quiz,
        int $exerciseId,
        CLpItem $parent,
        int $displayOrder,
    ): CLpItem {
        if ($exerciseId <= 0) {
            throw new InvalidArgumentException('A persisted exercise is required to create a learning path item.');
        }
        if ($displayOrder <= 0) {
            throw new InvalidArgumentException('The learning path display order must be a positive integer.');
        }

        return (new CLpItem())
            ->setTitle($quiz->getTitle())
            ->setDescription('')
            ->setPath((string) $exerciseId)
            ->setRef('')
            ->setLp($learningPath)
            ->setItemType('quiz')
            ->setMaxScore($quiz->getMaxScore())
            ->setMaxTimeAllowed('0')
            ->setPrerequisite('0')
            ->setDisplayOrder($displayOrder)
            ->setParent($parent)
        ;
    }
}
