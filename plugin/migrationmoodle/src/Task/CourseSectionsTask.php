<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Task;

use Chamilo\PluginBundle\MigrationMoodle\Extractor\CourseSectionsExtractor;
use Chamilo\PluginBundle\MigrationMoodle\Loader\CourseSectionsLoader;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\BaseTransformer;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\LoadedCourseCodeLookup;

/**
 * Class CourseSectionsTask.
 *
 * Task to convert Moodle course sections in a Chamilo learning paths.
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
            'class' => CourseSectionsExtractor::class,
            'query' => "SELECT id, course, name, summary
                FROM mdl_course_sections
                WHERE section > 0 AND (name != '' OR name IS NOT NULL)
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
                'c_id' => [
                    'class' => LoadedCourseCodeLookup::class,
                    'properties' => ['course'],
                ],
                'name' => 'name',
                'description' => 'summary',
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
