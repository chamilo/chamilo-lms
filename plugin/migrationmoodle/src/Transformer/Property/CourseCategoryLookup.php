<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Transformer\Property;

use Chamilo\PluginBundle\MigrationMoodle\Task\CourseCategoriesTask;

/**
 * Class CourseCategoryParentLookup.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Transformer\Property
 */
class CourseCategoryLookup extends LoadedKeyLookup
{
    /**
     * CourseCategoryParentLookup constructor.
     */
    public function __construct()
    {
        $this->calledClass = CourseCategoriesTask::class;
    }
}
