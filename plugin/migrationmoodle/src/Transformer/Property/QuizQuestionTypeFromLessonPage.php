<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Transformer\Property;

use Chamilo\PluginBundle\MigrationMoodle\Interfaces\TransformPropertyInterface;

/**
 * Class QuizQuestionTypeFromLessonPage.
 *
 * Transform the qtype from a moodle's lesson page in a type of chamilo question.
 * Get a FILL_IN_BLANKS for short answer (1) and numerical (8).
 * Get a MULTIPLE_ANSWER for multiple choice (3) with extra option. Otherwise get a UNIQUE_ANSWER.
 * Get a MATCHING_DRAGGABLE for matching (5).
 * Get a FREE_ANSWER for essay (10).
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Transformer\Property
 */
class QuizQuestionTypeFromLessonPage implements TransformPropertyInterface
{
    /**
     * @throws \Exception
     *
     * @return int
     */
    public function transform(array $data)
    {
        list($qtype, $qoption) = array_values($data);

        if (in_array($qtype, [1, 8])) {
            return FILL_IN_BLANKS;
        }

        if (in_array($qtype, [2, 3])) {
            if ($qtype == 3 && $qoption) {
                return MULTIPLE_ANSWER;
            }

            return UNIQUE_ANSWER;
        }

        if ($qtype == 5) {
            return MATCHING_DRAGGABLE;
        }

        if ($qtype == 10) {
            return FREE_ANSWER;
        }

        throw new \Exception("Type $qtype not found.");
    }
}
