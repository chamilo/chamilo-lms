<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Task;

use Chamilo\PluginBundle\MigrationMoodle\Extractor\BaseExtractor;
use Chamilo\PluginBundle\MigrationMoodle\Loader\LessonAnswersTrueFalseLoader;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\BaseTransformer;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\LoadedCourseLookup;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\LoadedLpQuizLookup;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\LoadedLpQuizQuestionLookup;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\ReplaceFilePaths;

/**
 * Class LessonAnswersTrueFalseTask.
 *
 * Task to convert True/False answers from a lesson page in quiz answers for chamilo.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Task
 */
class LessonAnswersTrueFalseTask extends BaseTask
{
    /**
     * @return array
     */
    public function getExtractConfiguration()
    {
        return [
            'class' => BaseExtractor::class,
            'query' => 'SELECT la.id, la.pageid, la.score, la.answer, la.response, l.course
                FROM mdl_lesson_answers la
                INNER JOIN mdl_lesson_pages lp ON (la.pageid = lp.id AND la.lessonid = lp.lessonid)
                INNER JOIN mdl_lesson l ON (lp.lessonid = l.id AND la.lessonid = l.id) 
                WHERE lp.qtype = 2
                ORDER BY lp.id',
        ];
    }

    /**
     * @return array
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
                    'class' => LoadedLpQuizLookup::class,
                    'properties' => ['pageid'],
                ],
                'question_id' => [
                    'class' => LoadedLpQuizQuestionLookup::class,
                    'properties' => ['pageid'],
                ],
                'score' => 'score',
                'answer' => [
                    'class' => ReplaceFilePaths::class,
                    'properties' => ['answer', 'course'],
                ],
                'feedback' => 'response',
            ],
        ];
    }

    /**
     * @return array
     */
    public function getLoadConfiguration()
    {
        return [
            'class' => LessonAnswersTrueFalseLoader::class,
        ];
    }
}
