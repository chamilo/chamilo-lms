<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Task;

use Chamilo\PluginBundle\MigrationMoodle\Loader\CourseFilesLoader;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\BaseTransformer;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\LoadedCourseLookup;

/**
 * Class CourseFilesTask.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Task
 */
abstract class CourseFilesTask extends BaseTask
{
    /**
     * @return array
     */
    public function getTransformConfiguration()
    {
        return [
            'class' => BaseTransformer::class,
            'map' => [
                'contenthash' => 'contenthash',
                'filepath' => 'filepath',
                'filename' => 'filename',
                'filesize' => 'filesize',
                'mimetype' => 'mimetype',
                'course' => [
                    'class' => LoadedCourseLookup::class,
                    'properties' => ['course'],
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
            'class' => CourseFilesLoader::class,
        ];
    }
}
