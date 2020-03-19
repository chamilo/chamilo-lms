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
        $query = 'SELECT * FROM mdl_lesson_attempts';

        $userFilter = $this->plugin->getUserFilterSetting();

        if (!empty($userFilter)) {
            $query = "SELECT la.* FROM mdl_lesson_attempts la
                INNER JOIN mdl_user u ON la.userid = u.id
                WHERE u.username LIKE '$userFilter%'";
        }

        return [
            'class' => LoadedUsersFilterExtractor::class,
            'query' => $query,
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
