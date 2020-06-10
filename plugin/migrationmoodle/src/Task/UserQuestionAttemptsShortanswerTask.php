<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Task;

/**
 * Class UserQuestionAttemptsShortanswerTask.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Task
 */
class UserQuestionAttemptsShortanswerTask extends UserQuestionAttemptsTask
{
    /**
     * {@inheritdoc}
     */
    public function getExtractConfiguration()
    {
        $this->questionType = 'shortanswer';

        return parent::getExtractConfiguration();
    }
}
