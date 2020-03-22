<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Loader;

use Chamilo\PluginBundle\MigrationMoodle\Interfaces\LoaderInterface;
use Chamilo\UserBundle\Entity\User;

/**
 * Class UsersLoader.
 *
 * Loader to create a Chamilo user coming from a Moodle user.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Loader
 */
class UsersLoader implements LoaderInterface
{
    /**
     * @throws \Doctrine\ORM\OptimisticLockException
     *
     * @return int
     */
    public function load(array $incomingData)
    {
        $userId = \UserManager::create_user(
            $incomingData['firstname'],
            $incomingData['lastname'],
            $incomingData['status'],
            $incomingData['email'],
            $incomingData['username'],
            md5(time()),
            '',
            $incomingData['language'],
            $incomingData['phone'],
            null,
            $incomingData['auth_source'],
            null,
            $incomingData['active'],
            0,
            [],
            null,
            false,
            false,
            $incomingData['address'],
            false,
            null,
            0,
            []
        );

        if (empty($userId)) {
            throw new \Exception('Users not created');
        }

        $incomingData['registration_date'] = $incomingData['registration_date']->format('Y-m-d H:i:s');

        $tblUser = \Database::get_main_table(TABLE_MAIN_USER);

        \Database::query(
            "UPDATE $tblUser SET registration_date = '{$incomingData['registration_date']}' WHERE id = $userId"
        );

        \UserManager::update_extra_field_value($userId, 'moodle_password', $incomingData['plain_password']);

        $urlId = \MigrationMoodlePlugin::create()->getAccessUrlId();

        if ($urlId) {
            \Database::query("UPDATE access_url_rel_user SET access_url_id = $urlId WHERE user_id = $userId");
        }

        return $userId;
    }
}
