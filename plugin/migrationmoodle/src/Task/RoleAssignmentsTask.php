<?php

namespace Chamilo\PluginBundle\MigrationMoodle\Task;

use Chamilo\PluginBundle\MigrationMoodle\Extractor\BaseExtractor;
use Chamilo\PluginBundle\MigrationMoodle\Loader\RoleAssignmentsLoader;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\BaseTransformer;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\CourseUserStatus;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\LoadedCourseCodeLookup;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\LoadedUserLookup;

/**
 * Class RoleAssignmentsTask.
 *
 * Task to convert the Moodle role assigment in a Chamilo's user course subscription.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Task
 */
class RoleAssignmentsTask extends BaseTask
{
    public const CONTEXT_LEVEL_COURSE = 50;

    /**
     * @return array
     */
    public function getExtractConfiguration()
    {
        return [
            'class' => BaseExtractor::class,
            'query' => 'SELECT ra.id, r.archetype, ra.userid, c.id cid
                FROM mdl_role_assignments ra
                INNER JOIN mdl_role r ON ra.roleid = r.id
                INNER JOIN mdl_context ctx ON ra.contextid = ctx.id
                INNER JOIN mdl_course c ON ctx.instanceid = c.id
                WHERE ctx.contextlevel = '.self::CONTEXT_LEVEL_COURSE,
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
                'status' => [
                    'class' => CourseUserStatus::class,
                    'properties' => ['archetype'],
                ],
                'user_id' => [
                    'class' => LoadedUserLookup::class,
                    'properties' => ['userid'],
                ],
                'course_code' => [
                    'class' => LoadedCourseCodeLookup::class,
                    'properties' => ['cid'],
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
            'class' => RoleAssignmentsLoader::class,
        ];
    }
}
