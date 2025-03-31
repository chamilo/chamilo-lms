<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Transformer\Property;

use Chamilo\PluginBundle\MigrationMoodle\Task\UsersQuizzesAttemptsTask;

/**
 * Class LoadedUserQuizLookup.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Transformer\Property
 */
class LoadedUserQuizLookup extends LoadedKeyLookup
{
    /**
     * LoadedUserQuizLookup constructor.
     */
    public function __construct()
    {
        $this->calledClass = UsersQuizzesAttemptsTask::class;
    }
}
