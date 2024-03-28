<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Task;

use Chamilo\PluginBundle\MigrationMoodle\Extractor\LoadedCoursesFilterExtractor;
use Chamilo\PluginBundle\MigrationMoodle\Loader\QuestionGapselectLoader;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\BaseTransformer;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\LoadedCourseLookup;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\LoadedQuestionLookup;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\LoadedQuizLookup;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\QuestionGapselectAnswer;

/**
 * Class QuestionGapselectTask.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Task
 */
class QuestionGapselectTask extends BaseTask
{
    /**
     * {@inheritdoc}
     */
    public function getExtractConfiguration()
    {
        return [
            'class' => LoadedCoursesFilterExtractor::class,
            'query' => "SELECT
                    qa.id,
                    qq.id,
                    qa.question,
                    GROUP_CONCAT(
                        CONCAT(qa.feedback, '==>>', qa.answer) ORDER BY qa.id ASC SEPARATOR '@||@'
                    ) answers,
                    qq.questiontext,
                    qs.maxmark,
                    qg.correctfeedback,
                    q.id quiz_id,
                    q.course
                FROM mdl_question_answers qa
                INNER JOIN mdl_question qq ON qa.question = qq.id
                INNER JOIN mdl_question_gapselect qg ON qq.id = qg.questionid
                INNER JOIN mdl_quiz_slots qs ON qq.id = qs.questionid
                INNER JOIN mdl_quiz q ON qs.quizid = q.id
                WHERE qq.qtype = 'gapselect'
                GROUP BY q.id, qq.id
                ORDER BY q.id, qq.id",
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getTransformConfiguration()
    {
        return [
            'class' => BaseTransformer::class,
            'map' => [
                'c_id' => [
                    'class' => LoadedCourseLookup::class,
                    'properties' => ['course'],
                ],
                'quiz_id' => [
                    'class' => LoadedQuizLookup::class,
                    'properties' => ['quiz_id'],
                ],
                'question_id' => [
                    'class' => LoadedQuestionLookup::class,
                    'properties' => ['question'],
                ],
                'answer' => [
                    'class' => QuestionGapselectAnswer::class,
                    'properties' => ['answers', 'questiontext', 'maxmark'],
                ],
                'score' => 'maxmark',
                'comment' => 'correctfeedback',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getLoadConfiguration()
    {
        return [
            'class' => QuestionGapselectLoader::class,
        ];
    }
}
