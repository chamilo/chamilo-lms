<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Transformer\Property;

use Chamilo\PluginBundle\MigrationMoodle\Task\ScormScoesTask;

/**
 * Class LoadedScormScoLookup.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Transformer\Property
 */
class LoadedScormScoLookup extends LoadedKeyLookup
{
    /**
     * LoadedScormScoLookup constructor.
     */
    public function __construct()
    {
        $this->calledClass = ScormScoesTask::class;
    }
}
