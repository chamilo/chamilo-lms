<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Task;

use Chamilo\PluginBundle\MigrationMoodle\Extractor\LoadedUsersFilterExtractor;
use Chamilo\PluginBundle\MigrationMoodle\Loader\TrackCourseAccessLoader;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\BaseTransformer;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\DateTimeObject;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\LoadedCourseLookup;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\LoadedUserLookup;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\LoadedUserSessionLookup;

/**
 * Class TrackCourseAccessTask.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Task
 */
class TrackCourseAccessTask extends BaseTask
{
    /**
     * {@inheritdoc}
     */
    public function getExtractConfiguration()
    {
        $query = "SELECT id, userid, courseid, timecreated, ip
            FROM mdl_logstore_standard_log
            WHERE (userid IS NOT NULL AND userid != 0) AND (courseid IS NOT NULL AND courseid != 0)
            ORDER BY timecreated";

        $userFilter = $this->plugin->getUserFilterSetting();

        if (!empty($userFilter)) {
            $query = "SELECT lsl.id, lsl.userid, lsl.courseid, lsl.timecreated, lsl.ip
                FROM mdl_logstore_standard_log lsl
                INNER JOIN mdl_user u ON lsl.userid = u.id
                WHERE (lsl.courseid IS NOT NULL AND lsl.courseid != 0)
                    AND u.username LIKE '$userFilter%'
                ORDER BY lsl.timecreated";
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
                    'properties' => ['userid'],
                ],
                'c_id' => [
                    'class' => LoadedCourseLookup::class,
                    'properties' => ['courseid'],
                ],
                'login_course_date' => [
                    'class' => DateTimeObject::class,
                    'properties' => ['timecreated'],
                ],
                'ip' => 'ip',
                'session_id' => [
                    'class' => LoadedUserSessionLookup::class,
                    'properties' => ['userid'],
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
            'class' => TrackCourseAccessLoader::class,
        ];
    }
}
