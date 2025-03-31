<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Task;

use Chamilo\PluginBundle\MigrationMoodle\Extractor\LoadedCoursesFilterExtractor;
use Chamilo\PluginBundle\MigrationMoodle\Loader\CourseModulesUrlLoader;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\BaseTransformer;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\LoadedCourseCodeLookup;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\LoadedCourseSectionLookup;

/**
 * Class CourseModulesUrlTask.
 *
 * Task to create a Chamilo Link from a Moodle URL module.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Task
 */
class CourseModulesUrlTask extends BaseTask
{
    /**
     * {@inheritdoc}
     */
    public function getExtractConfiguration()
    {
        return [
            'class' => LoadedCoursesFilterExtractor::class,
            'query' => "SELECT cm.id, u.course, u.name, cm.section
                FROM mdl_url u
                INNER JOIN mdl_course_modules cm ON (u.course = cm.course AND cm.instance = u.id)
                INNER JOIN mdl_modules m ON cm.module = m.id
                INNER JOIN mdl_course_sections cs ON (cm.course = cs.course AND cm.section = cs.id )
                WHERE m.name = 'url'
                    AND u.course NOT IN (
                        SELECT sco.course
                        FROM mdl_scorm sco
                        INNER JOIN mdl_course_modules cm ON (sco.course = cm.course AND cm.instance = sco.id)
                        INNER JOIN mdl_modules m ON cm.module = m.id
                        INNER JOIN mdl_course_sections cs ON (cm.course = cs.course AND cm.section = cs.id )
                        WHERE m.name = 'scorm'
                    )",
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
     * {@inheritdoc}
     */
    public function getLoadConfiguration()
    {
        return [
            'class' => CourseModulesUrlLoader::class,
        ];
    }
}
