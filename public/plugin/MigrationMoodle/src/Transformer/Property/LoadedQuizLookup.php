<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Transformer\Property;

use Chamilo\PluginBundle\MigrationMoodle\Task\QuizzesTask;

/**
 * Class LoadedQuizLookup.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Transformer\Property
 */
class LoadedQuizLookup extends LoadedKeyLookup
{
    /**
     * LoadedQuizLookup constructor.
     */
    public function __construct()
    {
        $this->calledClass = QuizzesTask::class;
    }
}
