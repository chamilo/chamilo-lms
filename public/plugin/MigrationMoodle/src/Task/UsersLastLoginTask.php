<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Task;

use Chamilo\PluginBundle\MigrationMoodle\Extractor\LoadedUsersFilterExtractor;
use Chamilo\PluginBundle\MigrationMoodle\Loader\UserLastLoginLoader;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\BaseTransformer;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\DateTimeObject;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\LoadedUserLookup;

/**
 * Class UsersLastLoginTask.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Task
 */
class UsersLastLoginTask extends BaseTask
{
    /**
     * {@inheritdoc}
     */
    public function getExtractConfiguration()
    {
        $query = 'SELECT id, lastlogin FROM mdl_user WHERE lastlogin != 0';

        $userFilter = $this->plugin->getUserFilterSetting();

        if (!empty($userFilter)) {
            $query = "SELECT id, lastlogin FROM mdl_user
                WHERE lastlogin != 0 AND username LIKE '$userFilter%'";
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
        return [
            'class' => BaseTransformer::class,
            'map' => [
                'user_id' => [
                    'class' => LoadedUserLookup::class,
                    'properties' => ['id'],
                ],
                'last_login' => [
                    'class' => DateTimeObject::class,
                    'properties' => ['lastlogin'],
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
            'class' => UserLastLoginLoader::class,
        ];
    }
}
