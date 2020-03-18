<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Transformer\Property;

use Chamilo\PluginBundle\MigrationMoodle\Task\CourseModulesUrlTask;

/**
 * Class LoadedCourseModuleUrlLookup.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Transformer\Property
 */
class LoadedCourseModuleUrlLookup extends LoadedKeyLookup
{
    /**
     * LoadedCourseModuleUrlLookup constructor.
     */
    public function __construct()
    {
        $this->calledClass = CourseModulesUrlTask::class;
    }
}
