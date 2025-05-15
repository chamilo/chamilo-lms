<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Transformer\Property;

use Chamilo\PluginBundle\MigrationMoodle\Interfaces\TransformPropertyInterface;

/**
 * Class UserQuestionAnswerGapselect.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Transformer\Property
 */
class UserQuestionAnswerGapselect implements TransformPropertyInterface
{
    /**
     * {@inheritdoc}
     */
    public function transform(array $data)
    {
        list(
            $mQType,
            $mRightAnswer,
            $mResponseSummary,
            $mFraction,
            $mDefaultMark,
            $mQuestionSummary,
            $mQuestionId
        ) = array_values($data);

        $mRightAnswer = $this->gapselectGetRightAnswers($mRightAnswer);

        $mResponseSummary = $this->gapselectGetResponseSummary($mResponseSummary);

        $mQuestionSummary = explode(";", $mQuestionSummary);
        $mQuestionSummary = array_map('trim', $mQuestionSummary);

        $questionText = array_shift($mQuestionSummary);

        $groupsAndOptions = $this->gapselectGetGroupsAndOptions($mQuestionSummary);

        $blanks = $this->gapselectGetBlanks($mRightAnswer, $groupsAndOptions);

        $count = 0;

        foreach ($blanks as $placeholder => $blank) {
            $userAnswer = empty($mResponseSummary[$count]) ? '' : $mResponseSummary[$count];
            $replacement = $blank."[$userAnswer][0]";

            $questionText = str_replace("[[$placeholder]]", $replacement, $questionText);

            $count++;
        }

        $scorePerBlank = $mDefaultMark / count($mRightAnswer);

        $optionsScores = array_fill(0, count($mRightAnswer), $scorePerBlank);
        $width = array_fill(0, count($mRightAnswer), 300);

        return utf8_encode("$questionText::".implode(',', $optionsScores).':'.implode(',', $width).':0@');
    }

    /**
     * @param string $mRightAnswer
     *
     * @return array
     */
    private function gapselectGetRightAnswers($mRightAnswer)
    {
        $rightAnswers = [];

        $mRightAnswer = explode('} {', $mRightAnswer);

        foreach ($mRightAnswer as $i0 => $item) {
            $rightAnswers[$i0 + 1] = trim($item, "{} \t\n\r\x0B");
        }

        return $rightAnswers;
    }

    /**
     * @param string $mResponseSummary
     *
     * @return array
     */
    private function gapselectGetResponseSummary($mResponseSummary)
    {
        $mResponseSummary = explode('} {', $mResponseSummary);

        return array_map(
            function ($item) {
                return trim($item, "{} \t\n\r\x0B");
            },
            $mResponseSummary
        );
    }

    /**
     * @return array
     */
    private function gapselectGetGroupsAndOptions(array $mQuestionSummary)
    {
        $groupsAndOptions = [];

        foreach ($mQuestionSummary as $groupAndOptions) {
            list($group, $options) = explode(' -> ', $groupAndOptions);

            $group = str_replace(['[', ']'], '', $group);
            $options = explode(' / ', trim($options, "{} \t\n\r\x0B"));

            $groupsAndOptions[$group] = $options;
        }

        return $groupsAndOptions;
    }

    /**
     * @return array
     */
    private function gapselectGetBlanks(array $rightAnswers, array $groupsAndOptions)
    {
        $blanks = [];

        foreach ($rightAnswers as $i => $rightAnswer) {
            foreach ($groupsAndOptions as $group => $options) {
                if (in_array($rightAnswer, $options)) {
                    $optionIndex = array_search($rightAnswer, $options);

                    unset($options[$optionIndex]);

                    $options = array_merge([$rightAnswer], $options);

                    $blanks[$i] = '['.implode('|', $options).']';
                }
            }
        }

        return $blanks;
    }
}
