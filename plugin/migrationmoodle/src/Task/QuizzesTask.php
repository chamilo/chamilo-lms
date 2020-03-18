<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Task;

use Chamilo\PluginBundle\MigrationMoodle\Extractor\LoadedCoursesFilterExtractor;
use Chamilo\PluginBundle\MigrationMoodle\Loader\QuizzesLoader;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\BaseTransformer;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\LoadedCourseLookup;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\LoadedCourseModuleQuizLookup;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\Percentage;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\ReplaceFilePaths;

/**
 * Class QuizzesTask.
 *
 * Task for convert a Moodle quiz inside a cours section in a Chamilo quiz for learning path.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Task
 */
class QuizzesTask extends BaseTask
{
    /**
     * @return array
     */
    public function getExtractConfiguration()
    {
        return [
            'class' => LoadedCoursesFilterExtractor::class,
            'query' => "SELECT
                    q.id,
                    q.course,
                    q.name,
                    q.intro,
                    q.shuffleanswers,
                    q.attempts,
                    q.timeopen,
                    q.timeclose,
                    q.timelimit,
                    gi.gradepass,
                    q.grade,
                    cm.id cm_id
                FROM mdl_quiz q
                INNER JOIN mdl_course_modules cm ON (q.course = cm.course AND cm.instance = q.id)
                INNER JOIN mdl_modules m ON cm.module = m.id
                INNER JOIN mdl_course_sections cs ON (cm.course = cs.course AND cm.section = cs.id )
                INNER JOIN mdl_grade_items gi ON (q.id = gi.iteminstance AND m.name = gi.itemmodule)
                WHERE m.name = 'quiz'
                    AND gi.itemtype = 'mod'
                ORDER BY cs.id, FIND_IN_SET(cm.id, cs.sequence)",
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
                'item_id' => [
                    'class' => LoadedCourseModuleQuizLookup::class,
                    'properties' => ['cm_id'],
                ],
                'exerciseTitle' => 'name',
                'exerciseDescription' => [
                    'class' => ReplaceFilePaths::class,
                    'properties' => ['intro', 'course'],
                ],
                'randomAnswers' => 'shuffleanswers',
                'exerciseAttempts' => 'attempts',
                'start_time' => 'timeopen',
                'end_time' => 'timeclose',
                'enabletimercontroltotalminutes' => 'timelimit',
                'pass_percentage' => [
                    'class' => Percentage::class,
                    'properties' => ['gradepass', 'grade'],
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    public function getLoadConfiguration()
    {
        return [
            'class' => QuizzesLoader::class,
        ];
    }
}
