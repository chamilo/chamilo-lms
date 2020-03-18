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
     * @inheritDoc
     */
    public function getExtractConfiguration()
    {
        return [
            'class' => LoadedUsersFilterExtractor::class,
            'query' => 'SELECT id, lastlogin FROM mdl_user WHERE lastlogin != 0',
        ];
    }

    /**
     * @inheritDoc
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
     * @inheritDoc
     */
    public function getLoadConfiguration()
    {
        return [
            'class' => UserLastLoginLoader::class,
        ];
    }
}
