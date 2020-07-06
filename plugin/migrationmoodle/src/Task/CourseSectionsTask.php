<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Task;

use Chamilo\PluginBundle\MigrationMoodle\Extractor\LoadedCoursesFilterExtractor;
use Chamilo\PluginBundle\MigrationMoodle\Loader\CourseSectionsLoader;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\BaseTransformer;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\LoadedCourseCodeLookup;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\WrapHtmlReplacingFilePaths;

/**
 * Class CourseSectionsTask.
 *
 * Task to convert Moodle course sections in a Chamilo learning paths.
 * The section summary will be the first item in the learning path.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Task
 */
class CourseSectionsTask extends BaseTask
{
    /**
     * @return array
     */
    public function getExtractConfiguration()
    {
        return [
            'class' => LoadedCoursesFilterExtractor::class,
            'query' => "SELECT id, course, name, summary
                FROM mdl_course_sections
                WHERE section > 0 AND (name != '' OR name IS NOT NULL)
                    AND course NOT IN (
                        SELECT sco.course
                        FROM mdl_scorm sco
                        INNER JOIN mdl_course_modules cm ON (sco.course = cm.course AND cm.instance = sco.id)
                        INNER JOIN mdl_modules m ON cm.module = m.id
                        INNER JOIN mdl_course_sections cs ON (cm.course = cs.course AND cm.section = cs.id )
                        WHERE m.name = 'scorm'
                    )
                ORDER BY course, section",
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
                'course_code' => [
                    'class' => LoadedCourseCodeLookup::class,
                    'properties' => ['course'],
                ],
                'name' => 'name',
                'description' => [
                    'class' => WrapHtmlReplacingFilePaths::class,
                    'properties' => ['summary', 'course'],
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
            'class' => CourseSectionsLoader::class,
        ];
    }
}
