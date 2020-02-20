<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Task;

use Chamilo\PluginBundle\MigrationMoodle\Extractor\BaseExtractor;
use Chamilo\PluginBundle\MigrationMoodle\Loader\UsersLoader;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\BaseTransformer;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\AuthLookup;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\DateTimeObject;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\UserActive;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\UserStatus;

/**
 * Class UsersTask.
 *
 * Task to convert Moodle users in Chamilo users.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Task
 */
class UsersTask extends BaseTask
{
    /**
     * @return array
     */
    public function getExtractConfiguration()
    {
        $query = "SELECT * FROM mdl_user WHERE username NOT IN ('admin', 'guest')";

        $userFilter = $this->plugin->getUserFilterSetting();

        if (!empty($userFilter)) {
            $query = "SELECT * FROM mdl_user WHERE username LIKE '$userFilter%'";
        }

        return [
            'class' => BaseExtractor::class,
            'query' => $query,
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
                    'class' => UserStatus::class,
                    'properties' => ['id'],
                ],
                'active' => [
                    'class' => UserActive::class,
                    'properties' => ['deleted', 'suspended'],
                ],
                'enabled' => [
                    'class' => UserActive::class,
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
