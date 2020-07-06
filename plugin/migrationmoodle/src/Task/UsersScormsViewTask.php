<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Task;

use Chamilo\PluginBundle\MigrationMoodle\Extractor\LoadedUsersFilterExtractor;
use Chamilo\PluginBundle\MigrationMoodle\Loader\UsersScormsViewLoader;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\BaseTransformer;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\LoadedCourseLookup;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\LoadedScormLookup;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\LoadedScormScoLookup;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\LoadedUserLookup;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\ScormScoTrackData;

/**
 * Class UsersScormsViewTask.
 *
 * Task for register the LP view of user coming from moodle scorm track.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Task
 */
class UsersScormsViewTask extends BaseTask
{
    /**
     * {@inheritdoc}
     */
    public function getExtractConfiguration()
    {
        $userFilter = $this->plugin->getUserFilterSetting();

        $userCondition = '';

        if (!empty($userFilter)) {
            $userCondition = "INNER JOIN mdl_user u ON sst.userid = u.id WHERE u.username LIKE '$userFilter%'";
        }

        return [
            'class' => LoadedUsersFilterExtractor::class,
            'query' => "SELECT
                    sst.id,
                    sst.userid,
                    s.id scormid,
                    ss.id scoid,
                    sst.attempt,
                    s.course,
                    GROUP_CONCAT(sst.element, '==>>', sst.value ORDER BY sst.id SEPARATOR '|@|') track_data
                FROM mdl_scorm_scoes_track sst
                INNER JOIN mdl_scorm_scoes ss ON (sst.scoid = ss.id AND sst.scormid = ss.scorm)
                INNER JOIN mdl_scorm s ON (ss.scorm = s.id)
                $userCondition
                GROUP BY sst.userid, s.id, ss.id, sst.attempt
                ORDER BY sst.userid, s.course, s.id",
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
                    'properties' => ['userid'],
                ],
                'lp_id' => [
                    'class' => LoadedScormLookup::class,
                    'properties' => ['scormid'],
                ],
                'lp_item_id' => [
                    'class' => LoadedScormScoLookup::class,
                    'properties' => ['scoid'],
                ],
                'lp_item_view_count' => 'attempt',
                'c_id' => [
                    'class' => LoadedCourseLookup::class,
                    'properties' => ['course'],
                ],
                'item_data' => [
                    'class' => ScormScoTrackData::class,
                    'properties' => ['track_data'],
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
            'class' => UsersScormsViewLoader::class,
        ];
    }
}
