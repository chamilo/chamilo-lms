<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Task;

use Chamilo\PluginBundle\MigrationMoodle\Extractor\BaseExtractor;
use Chamilo\PluginBundle\MigrationMoodle\Loader\UsersLoader;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\BaseTransformer;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\AuthLookup;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\DateTimeObject;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\UserActive;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\UserStatus;

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
