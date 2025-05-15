<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Task;

/**
 * Class UserQuestionAttemptsTruefalseTask.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Task
 */
class UserQuestionAttemptsTruefalseTask extends UserQuestionAttemptsTask
{
    /**
     * {@inheritdoc}
     */
    public function getExtractConfiguration()
    {
        $this->questionType = 'truefalse';

        return parent::getExtractConfiguration();
    }
}
