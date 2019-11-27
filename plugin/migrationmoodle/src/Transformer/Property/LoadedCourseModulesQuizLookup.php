<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Transformer\Property;

use Chamilo\PluginBundle\MigrationMoodle\Task\CourseModulesQuizTask;

/**
 * Class LoadedCourseModulesQuizLookup.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Transformer\Property
 */
class LoadedCourseModulesQuizLookup extends LoadedKeyLookup
{
    /**
     * LoadedCourseModulesQuizLookup constructor.
     */
    public function __construct()
    {
        $this->calledClass = CourseModulesQuizTask::class;
    }
}
