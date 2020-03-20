<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Task;

use Chamilo\PluginBundle\MigrationMoodle\Extractor\LoadedUsersFilterExtractor;
use Chamilo\PluginBundle\MigrationMoodle\Loader\UserScormProgressLoader;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\BaseTransformer;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\LoadedUserLookup;

/**
 * Class UsersScormsProgressTask.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Task
 */
class UsersScormsProgressTask extends BaseTask
{
    /**
     * {@inheritdoc}
     */
    public function getExtractConfiguration()
    {
        $query = 'SELECT DISTINCT userid id FROM mdl_scorm_scoes_track';

        $userFilter = $this->plugin->getUserFilterSetting();

        if (!empty($userFilter)) {
            $query = "SELECT DISTINCT scot.userid id
                FROM mdl_scorm_scoes_track scot
                INNER JOIN mdl_user u ON scot.userid = u.id
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
            'class' => UserScormProgressLoader::class,
        ];
    }
}
