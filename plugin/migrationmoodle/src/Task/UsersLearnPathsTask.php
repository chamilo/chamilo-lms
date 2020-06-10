<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Task;

use Chamilo\PluginBundle\MigrationMoodle\Extractor\LoadedUsersFilterExtractor;
use Chamilo\PluginBundle\MigrationMoodle\Loader\UserLearnPathsLoader;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\BaseTransformer;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\LoadedUserLookup;

/**
 * Class UsersLearnPathsTask.
 *
 * Create views LP and LP items for each user in platform.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Task
 */
class UsersLearnPathsTask extends BaseTask
{
    /**
     * {@inheritdoc}
     */
    public function getExtractConfiguration()
    {
        $userFilter = $this->plugin->getUserFilterSetting();

        $userFilterCondition = '';

        if (!empty($userFilter)) {
            $userFilterCondition = "WHERE username LIKE '$userFilter%'";
        }

        return [
            'class' => LoadedUsersFilterExtractor::class,
            'query' => "SELECT id FROM mdl_user $userFilterCondition",
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getTransformConfiguration()
    {
        return [
            'class' => BaseTransformer::class,
            'map' => [
                'user_id' => [
                    'class' => LoadedUserLookup::class,
                    'properties' => ['id'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getLoadConfiguration()
    {
        return [
            'class' => UserLearnPathsLoader::class,
        ];
    }
}
