<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Task;

use Chamilo\PluginBundle\MigrationMoodle\Extractor\UserExtractor;
use Chamilo\PluginBundle\MigrationMoodle\Loader\UsersScormsViewLoader;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\BaseTransformer;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\LoadedCourseLookup;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\LoadedLpLookup;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\LoadedScormLookup;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\LoadedUserLookup;

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
     * @inheritDoc
     */
    public function getExtractConfiguration()
    {
        return [
            'class' => UserExtractor::class,
            'query' => 'SELECT sst.id, sst.userid, MAX(sst.attempt) view_count, s.course, s.id scorm
                FROM mdl_scorm_scoes_track sst
                INNER JOIN mdl_scorm_scoes ss ON (sst.scoid = ss.id AND sst.scormid = ss.scorm)
                INNER JOIN mdl_scorm s ON (ss.scorm = s.id)
                GROUP BY sst.scormid, sst.userid
                ORDER BY s.course, s.id, sst.scoid',
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
                'c_id' => [
                    'class' => LoadedCourseLookup::class,
                    'properties' => ['course'],
                ],
                'lp_id' => [
                    'class' => LoadedScormLookup::class,
                    'properties' => ['scorm'],
                ],
                'user_id' => [
                    'class' => LoadedUserLookup::class,
                    'properties' => ['userid'],
                ],
                'view_count' => 'view_count',
            ],
        ];
    }

    /**
     * @inheritDoc
     */
    public function getLoadConfiguration()
    {
        return [
            'class' => UsersScormsViewLoader::class,
        ];
    }
}
