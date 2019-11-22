<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Task;

use Chamilo\PluginBundle\MigrationMoodle\Extractor\BaseExtractor;
use Chamilo\PluginBundle\MigrationMoodle\Loader\LessonAnswersShortAnswerLoader;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\BaseTransformer;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\LoadedCourseLookup;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\LoadedLpQuizLookup;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\LoadedLpQuizQuestionLookup;

/**
 * Class LessonAnswersShortAnswerTask.
 *
 * Task to convert Short Answers and Numerical answers from a lesson page in quiz answers for chamilo.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Task
 */
class LessonAnswersShortAnswerTask extends BaseTask
{
    /**
     * @return array
     */
    public function getExtractConfiguration()
    {
        return [
            'class' => BaseExtractor::class,
            'query' => "SELECT
                    la.id,
                    la.pageid,
                    GROUP_CONCAT(la.answer SEPARATOR '|') answers,
                    GROUP_CONCAT(la.response SEPARATOR '') comment,
                    GROUP_CONCAT(la.score) scores,
                    l.course,
                    COUNT(la.pageid) nb
                FROM mdl_lesson_answers la
                INNER JOIN mdl_lesson_pages lp ON (la.pageid = lp.id AND la.lessonid = lp.lessonid)
                INNER JOIN mdl_lesson l ON (lp.lessonid = l.id AND la.lessonid = l.id) 
                WHERE lp.qtype IN (1, 8)
                GROUP BY lp.id
                ORDER BY lp.id",
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
                'scores' => 'scores',
                'answers' => 'answers',
                'comment' => 'comment',
                'nb' => 'nb',
            ],
        ];
    }

    /**
     * @return array
     */
    public function getLoadConfiguration()
    {
        return [
            'class' => LessonAnswersShortAnswerLoader::class,
        ];
    }
}
