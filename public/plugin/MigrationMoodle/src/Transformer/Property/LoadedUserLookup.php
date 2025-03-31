<?php

namespace Chamilo\PluginBundle\MigrationMoodle\Transformer\Property;

use Chamilo\PluginBundle\MigrationMoodle\Task\UsersTask;

/**
 * Class LoadedUserLookup.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Transformer\Property
 */
class LoadedUserLookup extends LoadedKeyLookup
{
    /**
     * LoadedUserLookup constructor.
     */
    public function __construct()
    {
        $this->calledClass = UsersTask::class;
    }
}
