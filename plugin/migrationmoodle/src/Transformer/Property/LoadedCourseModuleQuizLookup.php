<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Transformer\Property;

use Chamilo\PluginBundle\MigrationMoodle\Task\CourseModulesQuizTask;

/**
 * Class LoadedCourseModuleQuizLookup.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Transformer\Property
 */
class LoadedCourseModuleQuizLookup extends LoadedKeyLookup
{
    /**
     * LoadedCourseModuleQuizLookup constructor.
     */
    public function __construct()
    {
        $this->calledClass = CourseModulesQuizTask::class;
    }
}
