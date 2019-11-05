<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Loader;

use Chamilo\PluginBundle\MigrationMoodle\Interfaces\LoaderInterface;

/**
 * Class CoursesLoader.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Loader
 */
class CoursesLoader implements LoaderInterface
{
    /**
     * @param array $incomingData
     *
     * @return int
     */
    public function load(array $incomingData)
    {
        $courseInfo = \CourseManager::create_course($incomingData, 1);

        return $courseInfo['real_id'];
    }
}
