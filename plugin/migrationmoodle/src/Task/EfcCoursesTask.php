<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Task;

use Chamilo\PluginBundle\MigrationMoodle\Extractor\BaseExtractor;

/**
 * Class EfcCoursesTask.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Task
 */
class EfcCoursesTask extends CoursesTask
{
    /**
     * @return array
     */
    public function getExtractConfiguration()
    {
        return [
            'class' => BaseExtractor::class,
            'query' => "SELECT DISTINCT c.*
                FROM mdl_course c
                INNER JOIN mdl_context ctx ON c.id = ctx.instanceid
                INNER JOIN mdl_role_assignments ra ON ctx.id = ra.contextid
                INNER JOIN mdl_user u ON ra.userid = u.id
                WHERE u.username LIKE 'efc%'",
        ];
    }
}
