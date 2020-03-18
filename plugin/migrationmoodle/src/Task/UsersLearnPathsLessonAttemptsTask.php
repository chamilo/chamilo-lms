<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Task;

use Chamilo\PluginBundle\MigrationMoodle\Extractor\LoadedUsersFilterExtractor;
use Chamilo\PluginBundle\MigrationMoodle\Loader\UserLearnPathLessonAttemptLoader;

/**
 * Class UsersLearnPathsLessonAttemptsTask.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Task
 */
class UsersLearnPathsLessonAttemptsTask extends UsersLearnPathsLessonBranchTask
{
    /**
     * {@inheritdoc}
     */
    public function getExtractConfiguration()
    {
        return [
            'class' => LoadedUsersFilterExtractor::class,
            'query' => "SELECT * FROM mdl_lesson_attempts",
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getTransformConfiguration()
    {
        $config = parent::getTransformConfiguration();

        $config['map']['is_correct'] = 'correct';

        return $config;
    }

    /**
     * {@inheritdoc}
     */
    public function getLoadConfiguration()
    {
        return [
            'class' => UserLearnPathLessonAttemptLoader::class,
        ];
    }
}
