<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Task;

use Chamilo\PluginBundle\MigrationMoodle\Extractor\LoadedCoursesFilterExtractor;
use Chamilo\PluginBundle\MigrationMoodle\Loader\LessonAnswersTrueFalseLoader;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\BaseTransformer;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\LoadedCourseLookup;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\LoadedLessonPageQuizLookup;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\LoadedLessonPageQuizQuestionLookup;
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
            'class' => LoadedCoursesFilterExtractor::class,
            'query' => 'SELECT la.id, la.pageid, la.score, la.answer, la.response, l.course,
                    (
                        SELECT MIN(id) = la.id
                        FROM mdl_lesson_answers
                        WHERE pageid = la.pageid
                        GROUP BY lessonid, pageid
                    ) is_correct
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
                    'class' => LoadedLessonPageQuizLookup::class,
                    'properties' => ['pageid'],
                ],
                'question_id' => [
                    'class' => LoadedLessonPageQuizQuestionLookup::class,
                    'properties' => ['pageid'],
                ],
                'score' => 'score',
                'answer' => [
                    'class' => ReplaceFilePaths::class,
                    'properties' => ['answer', 'course'],
                ],
                'feedback' => 'response',
                'is_correct' => 'is_correct',
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
