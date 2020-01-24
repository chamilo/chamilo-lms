<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Task;

use Chamilo\PluginBundle\MigrationMoodle\Extractor\BaseExtractor;

/**
 * Class EfcUsersTask.
 *
 * Task to convert Moodle users in Chamilo users. Filtering the users with username like "efc*".
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Task
 */
class EfcUsersTask extends UsersTask
{
    /**
     * @return array
     */
    public function getExtractConfiguration()
    {
        return [
            'class' => BaseExtractor::class,
            'query' => 'SELECT * FROM mdl_user WHERE username LIKE "efc%"',
        ];
    }
}
