<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Transformer\Property;

use Chamilo\PluginBundle\MigrationMoodle\Task\EfcCoursesTask;

/**
 * Class LoadedCourseLookup.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Transformer\Property
 */
class LoadedCourseLookup extends LoadedKeyLookup
{
    /**
     * LoadedCourseLookup constructor.
     */
    public function __construct()
    {
        $this->calledClass = EfcCoursesTask::class;
    }
}
