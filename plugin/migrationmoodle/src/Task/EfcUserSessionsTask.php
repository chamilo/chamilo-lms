<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Task;

use Chamilo\PluginBundle\MigrationMoodle\Extractor\UserExtractor;
use Chamilo\PluginBundle\MigrationMoodle\Loader\EfcUserSessionLoader;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\BaseTransformer;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\LoadedUserLookup;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\SessionName;

/**
 * Class EfcUserSessionsTask.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Task
 */
class EfcUserSessionsTask extends BaseTask
{
    const SEPARATOR_NAME = ' - ';

    /**
     * @inheritDoc
     */
    public function getExtractConfiguration()
    {
        return [
            'class' => UserExtractor::class,
            'query' => 'SELECT
                    u.id, u.username,
                    GROUP_CONCAT(c.shortname SEPARATOR "'.self::SEPARATOR_NAME.'") session_name
                FROM mdl_role_assignments ra
                INNER JOIN mdl_role r ON ra.roleid = r.id
                INNER JOIN mdl_context ctx ON ra.contextid = ctx.id
                INNER JOIN mdl_course c ON ctx.instanceid = c.id
                INNER JOIN mdl_user u ON ra.userid = u.id
                WHERE ctx.contextlevel = '.RoleAssignmentsTask::CONTEXT_LEVEL_COURSE.'
                AND u.username LIKE "efc%"
                GROUP BY ra.userid',
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
                'name' => [
                    'class' => SessionName::class,
                    'properties' => ['username', 'session_name']
                ],
                'user_id' => [
                    'class' => LoadedUserLookup::class,
                    'properties' => ['id'],
                ],
                'courses_list' => 'session_name',
            ],
        ];
    }

    /**
     * @inheritDoc
     */
    public function getLoadConfiguration()
    {
        return [
            'class' => EfcUserSessionLoader::class,
        ];
    }
}
