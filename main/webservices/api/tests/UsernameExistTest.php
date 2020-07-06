<?php

/* For licensing terms, see /license.txt */

require_once __DIR__.'/V2TestCase.php';
require_once __DIR__.'/../../../../vendor/autoload.php';

/**
 * Class UsernameExistTest
 *
 * USERNAME_EXIST webservice unit tests
 */
class UsernameExistTest extends V2TestCase
{
    public function action()
    {
        return 'username_exist';
    }

    /**
     * test nonexistence of a username which does not exist
     * assert that the webservice returns false
     */
    public function testUsernameWhichDoesNotExist()
    {
        // generate a random name which does not exist in the database
        do {
            $loginName = rand();
        } while (UserManager::get_user_id_from_username($loginName));

        // expect the web service to return false
        $this->assertFalse($this->boolean(['loginname' => $loginName]));
    }

    /**
     * test existence of a username which does exist
     * assert that the webservice returns true
     */
    public function testUsernameWhichDoesExist()
    {
        // generate a random name which does not exist in the database
        do {
            $loginName = rand();
        } while (UserManager::get_user_id_from_username($loginName));

        // create a test user with this login name
        $userId = UserManager::create_user(
            $loginName,
            $loginName,
            STUDENT,
            $loginName.'@local',
            $loginName,
            $loginName
        );

        // expect the web service to return true
        $this->assertTrue($this->boolean(['loginname' => $loginName]));

        // clean up
        UserManager::delete_users([$userId]);
    }
}
