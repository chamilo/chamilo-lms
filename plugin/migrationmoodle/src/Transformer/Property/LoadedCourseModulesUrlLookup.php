<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Transformer\Property;

use Chamilo\PluginBundle\MigrationMoodle\Task\CourseModulesUrlTask;

/**
 * Class LoadedCourseModulesUrlLookup.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Transformer\Property
 */
class LoadedCourseModulesUrlLookup extends LoadedKeyLookup
{
    /**
     * LoadedCourseModulesUrlLookup constructor.
     */
    public function __construct()
    {
        $this->calledClass = CourseModulesUrlTask::class;
    }
}
