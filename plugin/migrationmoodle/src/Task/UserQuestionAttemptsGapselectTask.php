<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Task;

/**
 * Class UserQuestionAttemptsGapselectTask.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Task
 */
class UserQuestionAttemptsGapselectTask extends UserQuestionAttemptsTask
{
    /**
     * @inheritDoc
     */
    public function getExtractConfiguration()
    {
        $this->questionType = 'gapselect';

        return parent::getExtractConfiguration();
    }
}
