<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Transformer\Property;

use Chamilo\PluginBundle\MigrationMoodle\Task\CourseModulesScormTask;

/**
 * Class LoadedScormLookup.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Transformer\Property
 */
class LoadedScormLookup extends LoadedKeyLookup
{
    /**
     * LoadedScormLookup constructor.
     */
    public function __construct()
    {
        $this->calledClass = CourseModulesScormTask::class;
    }
}
