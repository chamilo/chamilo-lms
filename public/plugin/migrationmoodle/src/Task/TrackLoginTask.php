<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Task;

use Chamilo\PluginBundle\MigrationMoodle\Extractor\LoadedUsersFilterExtractor;
use Chamilo\PluginBundle\MigrationMoodle\Loader\TrackLoginLoader;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\BaseTransformer;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\DateTimeObject;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\LoadedUserLookup;

/**
 * Class TrackLoginTask.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Task
 */
class TrackLoginTask extends BaseTask
{
    /**
     * {@inheritdoc}
     */
    public function getExtractConfiguration()
    {
        $query = 'SELECT id, firstaccess, lastaccess FROM mdl_user WHERE firstaccess != 0';

        $userFilter = $this->plugin->getUserFilterSetting();

        if (!empty($userFilter)) {
            $query .= " AND username LIKE '$userFilter%'";
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
                'login_user_id' => [
                    'class' => LoadedUserLookup::class,
                    'properties' => ['id'],
                ],
                'login_date' => [
                    'class' => DateTimeObject::class,
                    'properties' => ['firstaccess'],
                ],
                'logout_date' => [
                    'class' => DateTimeObject::class,
                    'properties' => ['lastaccess'],
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
            'class' => TrackLoginLoader::class,
        ];
    }
}
