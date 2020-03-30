<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Transformer\Property;

use Chamilo\PluginBundle\MigrationMoodle\Task\QuestionsTask;

/**
 * Class UserQuestionAnswerTruefalse.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Transformer\Property
 */
class UserQuestionAnswerTruefalse extends LoadedKeyLookup
{
    /**
     * UserQuestionAnswerTruefalse constructor.
     */
    public function __construct()
    {
        $this->calledClass = QuestionsTask::class;
    }

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

        $questionId = parent::transform([$mQuestionId]);

        $answer = \Database::select(
            'iid',
            \Database::get_course_table(TABLE_QUIZ_ANSWER),
            [
                'where' => [
                    'question_id = ? AND answer = ?' => [$questionId, utf8_encode("<p>$mResponseSummary</p>")],
                ],
            ],
            'first'
        );

        if (empty($answer)) {
            return 0;
        }

        return $answer['iid'];
    }
}
