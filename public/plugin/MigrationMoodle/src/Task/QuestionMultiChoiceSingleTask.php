<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Task;

use Chamilo\PluginBundle\MigrationMoodle\Extractor\LoadedCoursesFilterExtractor;
use Chamilo\PluginBundle\MigrationMoodle\Loader\LessonAnswersMultipleChoiceLoader;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\BaseTransformer;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\LoadedCourseLookup;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\LoadedLessonPageQuizLookup;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\LoadedQuestionLookup;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\ReplaceFilePaths;

/**
 * Class QuestionMultiChoiceSingleTask.
 *
 * Task to convert Moodle question answers of multichoice type in Chamilo unique/multiple answers.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Task
 */
class QuestionMultiChoiceSingleTask extends BaseTask
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
                    qa.question,
                    qa.answer,
                    qa.feedback,
                    (qa.fraction * qq.defaultmark) score,
                    IF (qa.fraction = 1, TRUE, FALSE) is_correct,
                    q.id quizid,
                    q.course
                FROM mdl_question_answers qa
                INNER JOIN mdl_question qq ON qa.question = qq.id
                INNER JOIN mdl_qtype_multichoice_options qo ON qq.id = qo.questionid
                INNER JOIN mdl_quiz_slots qs ON qq.id = qs.questionid
                INNER JOIN mdl_quiz q ON qs.quizid = q.id
                WHERE qq.qtype = 'multichoice'
                    AND qo.single = 1",
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
                    'class' => LoadedLessonPageQuizLookup::class,
                    'properties' => ['quizid'],
                ],
                'question_id' => [
                    'class' => LoadedQuestionLookup::class,
                    'properties' => ['question'],
                ],
                'score' => 'score',
                'answer' => [
                    'class' => ReplaceFilePaths::class,
                    'properties' => ['answer', 'course'],
                ],
                'feedback' => [
                    'class' => ReplaceFilePaths::class,
                    'properties' => ['feedback', 'course'],
                ],
                'is_correct' => 'is_correct',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getLoadConfiguration()
    {
        return [
            'class' => LessonAnswersMultipleChoiceLoader::class,
        ];
    }
}
