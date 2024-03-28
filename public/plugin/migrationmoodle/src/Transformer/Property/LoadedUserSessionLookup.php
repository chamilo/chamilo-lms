<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Transformer\Property;

use Chamilo\PluginBundle\MigrationMoodle\Task\UserSessionsTask;

/**
 * Class LoadedUserSessionLookup.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Transformer\Property
 */
class LoadedUserSessionLookup extends LoadedKeyLookup
{
    /**
     * LoadedUserSessionLookup constructor.
     */
    public function __construct()
    {
        $this->calledClass = UserSessionsTask::class;
    }
}
