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
    public const LOAD_MODE_REUSE = 'reuse';
    public const LOAD_MODE_DUPLICATE = 'duplicate';

    /**
     * @var string Load mode: "reuse" or "duplicate". Default is "duplicate".
     */
    private $loadMode = self::LOAD_MODE_DUPLICATE;

    /**
     * @throws \Exception
     *
     * @return int
     */
    public function load(array $incomingData)
    {
        $tblUser = \Database::get_main_table(TABLE_MAIN_USER);

        $userInfo = \Database::fetch_assoc(
            \Database::query("SELECT id FROM $tblUser WHERE username = '{$incomingData['username']}'")
        );

        if (!empty($userInfo)) {
            if ($this->loadMode == self::LOAD_MODE_REUSE) {
                return $userInfo['id'];
            }

            if ($this->loadMode === self::LOAD_MODE_DUPLICATE) {
                $incomingData['username'] .= substr(md5(uniqid(rand())), 0, 10);
            }
        }

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
            throw new \Exception('User was not created');
        }

        if ($incomingData['registration_date']) {
            $incomingData['registration_date'] = $incomingData['registration_date']->format('Y-m-d H:i:s');

            \Database::query(
                "UPDATE $tblUser SET registration_date = '{$incomingData['registration_date']}' WHERE id = $userId"
            );
        }

        \UserManager::update_extra_field_value($userId, 'moodle_password', $incomingData['plain_password']);

        $urlId = \MigrationMoodlePlugin::create()->getAccessUrlId();

        if ($urlId) {
            \Database::query("UPDATE access_url_rel_user SET access_url_id = $urlId WHERE user_id = $userId");
        }

        return $userId;
    }
}
