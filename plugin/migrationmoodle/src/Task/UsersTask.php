<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Task;

use Chamilo\PluginBundle\MigrationMoodle\Extractor\UsersExtractor;
use Chamilo\PluginBundle\MigrationMoodle\Loader\UsersLoader;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\BaseTransformer;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\AuthLookup;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\DateTimeObject;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\UserActiveLookup;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\UserStatusLookup;

/**
 * Class UsersTask.
 */
class UsersTask extends BaseTask
{
    /**
     * @return array
     */
    public function getExtractConfiguration()
    {
        return [
            'class' => UsersExtractor::class,
            'query' => 'SELECT * FROM mdl_user',
        ];
    }

    /**
     * @return array
     */
    public function getTransformConfiguration()
    {
        return [
            'class' => BaseTransformer::class,
            'map' => [
                'lastname' => 'lastname',
                'firstname' => 'firstname',
                'email' => 'email',
                'username' => 'username',
                'plain_password' => 'password',
                'language' => 'lang',
                'phone' => 'phone1',
                'address' => 'address',
                'auth_source' => [
                    'class' => AuthLookup::class,
                    'properties' => ['auth'],
                ],
                'registration_date' => [
                    'class' => DateTimeObject::class,
                    'properties' => ['timecreated'],
                ],
                'status' => [
                    'class' => UserStatusLookup::class,
                    'properties' => ['id'],
                ],
                'active' => [
                    'class' => UserActiveLookup::class,
                    'properties' => ['deleted', 'suspended'],
                ],
                'enabled' => [
                    'class' => UserActiveLookup::class,
                    'properties' => ['deleted', 'suspended'],
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    public function getLoadConfiguration()
    {
        return [
            'class' => UsersLoader::class,
        ];
    }
}
