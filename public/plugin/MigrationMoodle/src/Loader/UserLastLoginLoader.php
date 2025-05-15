<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Loader;

use Chamilo\PluginBundle\MigrationMoodle\Interfaces\LoaderInterface;

/**
 * Class UserLastLoginLoader.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Loader
 */
class UserLastLoginLoader implements LoaderInterface
{
    /**
     * {@inheritdoc}
     */
    public function load(array $incomingData)
    {
        \Database::update(
            \Database::get_main_table(TABLE_MAIN_USER),
            ['last_login' => $incomingData['last_login']->format('Y-m-d H:i:s')],
            ['id = ?' => [$incomingData['user_id']]]
        );

        return $incomingData['user_id'];
    }
}
