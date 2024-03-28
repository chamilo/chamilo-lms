<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Loader;

use Chamilo\PluginBundle\MigrationMoodle\Interfaces\LoaderInterface;

/**
 * Class CourseModulesLessonLoader.
 *
 * Loader for create and Chamilo learning path section coming from a Moodle course module.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Loader
 */
class CourseModulesLessonLoader implements LoaderInterface
{
    /**
     * Load the data and return the ID inserted.
     *
     * @return int
     */
    public function load(array $incomingData)
    {
        $lp = new \learnpath(
            $incomingData['c_code'],
            $incomingData['lp_id'],
            1
        );
        $itemId = $lp->add_item(
            0,
            0,
            'dir',
            0,
            $incomingData['title'],
            ''
        );

        return $itemId;
    }
}
