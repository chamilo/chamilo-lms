<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Transformer\Property;

use Chamilo\PluginBundle\MigrationMoodle\Interfaces\TransformPropertyInterface;
use Chamilo\PluginBundle\MigrationMoodle\Loader\LessonAnswersShortAnswerLoader;

/**
 * Class UserQuestionAnswerShortanswer.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Transformer\Property
 */
class UserQuestionAnswerShortanswer implements TransformPropertyInterface
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

        $width = LessonAnswersShortAnswerLoader::INPUT_WIDTH;

        return utf8_encode("[$mRightAnswer][$mResponseSummary][$mFraction]::$mDefaultMark:$width:0@");
    }
}
