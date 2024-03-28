<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Transformer\Property;

use Chamilo\PluginBundle\MigrationMoodle\Interfaces\TransformPropertyInterface;

/**
 * Class QuestionGapselectAnswer.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Transformer\Property
 */
class QuestionGapselectAnswer implements TransformPropertyInterface
{
    /**
     * {@inheritdoc}
     */
    public function transform(array $data)
    {
        list($answers, $questionText, $score) = array_values($data);

        $groupsAndOptions = explode('@||@', $answers);

        $groups = [];
        $positionsByGroup = [];

        foreach ($groupsAndOptions as $zeroPosition => $groupAndOption) {
            $position = $zeroPosition + 1;

            list($group, $option) = explode('==>>', $groupAndOption);

            $positionsByGroup[$position] = $group;
            $groups[$group][$position] = $option;
        }

        $blanks = [];

        foreach ($positionsByGroup as $option => $group) {
            if ($option === 1) {
                $blanks[] = '['.implode($groups[$group], '|').']';

                continue;
            }

            $baz = $groups[$group];

            unset($baz[$option]);

            $baz = [$groups[$group][$option]] + $baz;

            $blanks[] = '['.implode($baz, '|').']';
        }

        $countBlanks = 0;

        foreach ($blanks as $zeroPosition => $blank) {
            $countBlank = 0;
            $position = $zeroPosition + 1;
            $questionText = str_replace("[[$position]]", $blank, $questionText, $countBlank);

            $countBlanks += $countBlank;
        }

        $individualScore = $score / $countBlanks;
        $scores = [];

        for ($i = 0; $i < $countBlanks - 1; $i++) {
            $scores[] = $individualScore;
        }

        $scores[] = $score - array_sum($scores);

        $inputs = str_repeat('300,', $countBlanks - 1).'300';

        return utf8_encode("$questionText::".implode(',', $scores).":$inputs:0@");
    }
}
