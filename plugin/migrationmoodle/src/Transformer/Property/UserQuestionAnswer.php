<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Transformer\Property;

use Chamilo\PluginBundle\MigrationMoodle\Interfaces\TransformPropertyInterface;
use Chamilo\PluginBundle\MigrationMoodle\Loader\LessonAnswersShortAnswerLoader;

/**
 * Class UserQuestionAnswer.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Transformer\Property
 */
class UserQuestionAnswer implements TransformPropertyInterface
{
    /**
     * @inheritDoc
     */
    public function transform(array $data)
    {
        list(
            $mQType,
            $mRightAnswer,
            $mResponseSummary,
            $mFraction,
            $mDefaultMark,
            $mQuestionSummary
        ) = array_values($data);

        switch ($mQType) {
            case 'shortanswer':
                return $this->shortAnswer($mRightAnswer, $mResponseSummary, $mFraction, $mDefaultMark);
            case 'gapselect':
                return $this->gapSelect($mResponseSummary, $mFraction, $mDefaultMark, $mQuestionSummary);
            default:
                return '';
        }
    }

    /**
     * @param string $mRightAnswer
     * @param string $mResponseSummary
     * @param float  $mFraction
     * @param float  $mDefaultMark
     *
     * @return string
     */
    private function shortAnswer($mRightAnswer, $mResponseSummary, $mFraction, $mDefaultMark)
    {
        $width = LessonAnswersShortAnswerLoader::INPUT_WIDTH;

        return "[$mRightAnswer][$mResponseSummary][$mFraction]::$mDefaultMark:$width:0@";
    }

    /**
     * @param string $mResponseSummary
     * @param float  $mFraction
     * @param float  $mDefaultMark
     * @param string $mQuestionSummary
     *
     * @return string
     */
    private function gapSelect($mResponseSummary, $mFraction, $mDefaultMark, $mQuestionSummary)
    {
        $mResponseSummary = explode('} {', $mResponseSummary);
        $mResponseSummary = array_map(
            function ($item) {
                return trim($item, "{} \t\n\r\x0B");
            },
            $mResponseSummary
        );

        $mQuestionSummary = explode("\n;", $mQuestionSummary);
        $mQuestionSummary = array_map('trim', $mQuestionSummary);

        list($questionText, $questionOptions) = $mQuestionSummary;

        $questionOptions = explode('; ', $questionOptions);

        $mDefaultMark = count($mQuestionSummary) > 0 ? $mDefaultMark / count($mQuestionSummary) : 0;
        $userScore = count($mQuestionSummary) > 0 ? $mFraction / count($mQuestionSummary) : 0;

        $width = [];
        $score = [];

        foreach ($questionOptions as $i => $questionOption) {
            list($position, $options) = explode(' -> ', $questionOption);

            $options = str_replace(['{', '}', ' / '], ['[', ']', '|'], $options);
            $options .= '['.$mResponseSummary[$i].']';
            $options .= '['.$userScore.']';

            $questionText = str_replace($position, $options, $questionText);

            $width[] = 300;
            $score[] = $mDefaultMark;
        }

        return "$questionText::".implode(',', $score).':'.implode(',', $width).':0@';
    }
}
