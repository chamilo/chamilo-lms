<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Task;

use Chamilo\PluginBundle\MigrationMoodle\Extractor\LoadedCoursesFilterExtractor;
use Chamilo\PluginBundle\MigrationMoodle\Loader\CourseModulesQuizLoader;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\BaseTransformer;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\LoadedCourseCodeLookup;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\LoadedCourseSectionLookup;

/**
 * Class CourseModulesQuizTask.
 *
 * Task for convert a Moodle quiz inside a page section in a quiz item of Chamilo learning path.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Task
 */
class CourseModulesQuizTask extends BaseTask
{
    /**
     * @return array
     */
    public function getExtractConfiguration()
    {
        return [
            'class' => LoadedCoursesFilterExtractor::class,
            'query' => "SELECT cm.id, q.course, q.name, cm.section
                FROM mdl_quiz q
                INNER JOIN mdl_course_modules cm ON (q.course = cm.course AND cm.instance = q.id)
                INNER JOIN mdl_modules m ON cm.module = m.id
                INNER JOIN mdl_course_sections cs ON (cm.course = cs.course AND cm.section = cs.id )
                WHERE m.name = 'quiz'
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
                'c_code' => [
                    'class' => LoadedCourseCodeLookup::class,
                    'properties' => ['course'],
                ],
                'lp_id' => [
                    'class' => LoadedCourseSectionLookup::class,
                    'properties' => ['section'],
                ],
                'title' => 'name',
            ],
        ];
    }

    /**
     * @return array
     */
    public function getLoadConfiguration()
    {
        return [
            'class' => CourseModulesQuizLoader::class,
        ];
    }
}
