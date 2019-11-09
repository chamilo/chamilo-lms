<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Transformer\Property;

use Chamilo\PluginBundle\MigrationMoodle\Task\LpItemsTask;

/**
 * Class LoadedLpItemLookup.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Transformer\Property
 */
class LoadedLpItemLookup extends LoadedKeyLookup
{
    /**
     * LoadedLpItemLookup constructor.
     */
    public function __construct()
    {
        $this->calledClass = LpItemsTask::class;
    }
}
