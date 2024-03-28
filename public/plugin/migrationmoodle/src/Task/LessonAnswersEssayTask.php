<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Task;

use Chamilo\PluginBundle\MigrationMoodle\Extractor\LoadedCoursesFilterExtractor;
use Chamilo\PluginBundle\MigrationMoodle\Loader\LessonAnswersEssayLoader;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\BaseTransformer;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\LoadedCourseLookup;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\LoadedLessonPageQuizLookup;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\LoadedLessonPageQuizQuestionLookup;

/**
 * Class LessonAnswersEssayTask.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Task
 */
class LessonAnswersEssayTask extends BaseTask
{
    /**
     * @return array
     */
    public function getExtractConfiguration()
    {
        return [
            'class' => LoadedCoursesFilterExtractor::class,
            'query' => 'SELECT la.id, la.pageid, la.score, l.course
                FROM mdl_lesson_answers la
                INNER JOIN mdl_lesson_pages lp ON (la.pageid = lp.id AND la.lessonid = lp.lessonid)
                INNER JOIN mdl_lesson l ON (lp.lessonid = l.id AND la.lessonid = l.id)
                WHERE lp.qtype = 10
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
                    'class' => LoadedLessonPageQuizLookup::class,
                    'properties' => ['pageid'],
                ],
                'question_id' => [
                    'class' => LoadedLessonPageQuizQuestionLookup::class,
                    'properties' => ['pageid'],
                ],
                'score' => 'score',
            ],
        ];
    }

    /**
     * @return array
     */
    public function getLoadConfiguration()
    {
        return [
            'class' => LessonAnswersEssayLoader::class,
        ];
    }
}
