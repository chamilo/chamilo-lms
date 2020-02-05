<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Task;

use Chamilo\PluginBundle\MigrationMoodle\Extractor\CourseExtractor;
use Chamilo\PluginBundle\MigrationMoodle\Loader\CourseIntroductionLoader;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\BaseTransformer;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\LoadedCourseLookup;

/**
 * Class CourseIntroductionsTask.
 *
 * Migrate the first section (section 0) from a moodle course as introduction for a chamilo course.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Task
 */
class CourseIntroductionsTask extends BaseTask
{
    /**
     * @inheritDoc
     */
    public function getExtractConfiguration()
    {
        return [
            'class' => CourseExtractor::class,
            'query' => "SELECT id, course, name, summary
                FROM mdl_course_sections
                WHERE section = 0 AND (summary != '' AND summary IS NOT NULL)",
        ];
    }

    /**
     * @inheritDoc
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
                'name' => 'name',
                'description' => 'summary',
            ],
        ];
    }

    /**
     * @inheritDoc
     */
    public function getLoadConfiguration()
    {
        return [
            'class' => CourseIntroductionLoader::class,
        ];
    }
}
