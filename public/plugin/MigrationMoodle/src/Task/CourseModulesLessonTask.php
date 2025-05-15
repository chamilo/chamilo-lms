<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Task;

use Chamilo\PluginBundle\MigrationMoodle\Extractor\LoadedCoursesFilterExtractor;
use Chamilo\PluginBundle\MigrationMoodle\Loader\CourseModulesLessonLoader;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\BaseTransformer;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\LoadedCourseCodeLookup;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\LoadedCourseSectionLookup;

/**
 * Class CourseModulesLessonTask.
 *
 * Task for convert a Moodle course module in a Chamilo learning path section.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Task
 */
class CourseModulesLessonTask extends BaseTask
{
    /**
     * @return array
     */
    public function getExtractConfiguration()
    {
        return [
            'class' => LoadedCoursesFilterExtractor::class,
            'query' => "SELECT cm.id, l.course, l.name, cm.section
                FROM mdl_lesson l
                INNER JOIN mdl_course_modules cm ON (l.course = cm.course AND cm.instance = l.id)
                INNER JOIN mdl_modules m ON cm.module = m.id
                INNER JOIN mdl_course_sections cs ON (cm.course = cs.course AND cm.section = cs.id )
                WHERE m.name = 'lesson'
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
            'class' => CourseModulesLessonLoader::class,
        ];
    }
}
