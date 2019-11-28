<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Transformer\Property;

use Chamilo\PluginBundle\MigrationMoodle\Task\QuestionsTask;

/**
 * Class LoadedQuestionLookup.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Transformer\Property
 */
class LoadedQuestionLookup extends LoadedKeyLookup
{
    /**
     * LoadedQuestionLookup constructor.
     */
    public function __construct()
    {
        $this->calledClass = QuestionsTask::class;
    }
}
