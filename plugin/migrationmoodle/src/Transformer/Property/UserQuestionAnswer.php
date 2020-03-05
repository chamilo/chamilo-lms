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
        list($mQType, $mRightAnswer, $mResponseSummary, $mFraction, $mDefaultMark) = array_values($data);

        switch ($mQType) {
            case 'shortanswer':
                return $this->showAnswer($mRightAnswer, $mResponseSummary, $mFraction, $mDefaultMark);
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
    private function showAnswer($mRightAnswer, $mResponseSummary, $mFraction, $mDefaultMark)
    {
        $width = LessonAnswersShortAnswerLoader::INPUT_WIDTH;

        return "[$mRightAnswer][$mResponseSummary][$mFraction]::$mDefaultMark:$width:0@";
    }
}
