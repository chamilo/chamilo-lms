<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Transformer\Property;

use Chamilo\PluginBundle\MigrationMoodle\Task\CourseSectionsTask;

/**
 * Class LoadedCourseSectionLookup.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Transformer\Property
 */
class LoadedCourseSectionLookup extends LoadedKeyLookup
{
    /**
     * LoadedCourseSectionLookup constructor.
     */
    public function __construct()
    {
        $this->calledClass = CourseSectionsTask::class;
    }
}
