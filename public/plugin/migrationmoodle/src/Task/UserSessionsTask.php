<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Task;

use Chamilo\PluginBundle\MigrationMoodle\Extractor\LoadedUsersFilterExtractor;
use Chamilo\PluginBundle\MigrationMoodle\Loader\UserSessionLoader;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\BaseTransformer;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\CoursesArrayLookup;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\LoadedUserLookup;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\SessionName;

/**
 * Class UserSessionsTask.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Task
 */
class UserSessionsTask extends BaseTask
{
    public const SEPARATOR_NAME = ' - ';

    /**
     * {@inheritdoc}
     */
    public function getExtractConfiguration()
    {
        $userFilterCondition = '';

        $userFilter = $this->plugin->getUserFilterSetting();

        if (!empty($userFilter)) {
            $userFilterCondition = "AND u.username LIKE '$userFilter%'";
        }

        return [
            'class' => LoadedUsersFilterExtractor::class,
            'query' => "SELECT
                    u.id,
                    u.username,
                    GROUP_CONCAT(c.id) course_ids,
                    GROUP_CONCAT(c.shortname SEPARATOR '".self::SEPARATOR_NAME."') session_name
                FROM mdl_role_assignments ra
                INNER JOIN mdl_role r ON ra.roleid = r.id
                INNER JOIN mdl_context ctx ON ra.contextid = ctx.id
                INNER JOIN mdl_course c ON ctx.instanceid = c.id
                INNER JOIN mdl_user u ON ra.userid = u.id
                WHERE ctx.contextlevel = ".RoleAssignmentsTask::CONTEXT_LEVEL_COURSE."
                $userFilterCondition
                GROUP BY ra.userid",
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
                'name' => [
                    'class' => SessionName::class,
                    'properties' => ['username', 'session_name'],
                ],
                'user_id' => [
                    'class' => LoadedUserLookup::class,
                    'properties' => ['id'],
                ],
                'courses_list' => 'session_name',
                'course_ids' => [
                    'class' => CoursesArrayLookup::class,
                    'properties' => ['course_ids'],
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
            'class' => UserSessionLoader::class,
        ];
    }
}
