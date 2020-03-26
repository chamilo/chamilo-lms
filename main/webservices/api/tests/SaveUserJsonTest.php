<?php
/* For licensing terms, see /license.txt */

require_once __DIR__.'/V2TestCase.php';
require_once __DIR__.'/../../../../vendor/autoload.php';

/**
 * Class SaveUserJsonTest
 *
 * SAVE_USER_JSON webservice unit tests
 */
class SaveUserJsonTest extends V2TestCase
{
    public function action()
    {
        return 'save_user_json';
    }

    /**
     * creates a minimal test user
     * asserts that it was created with the supplied data
     *
     * @throws Exception if it cannot delete the created test user
     */
    public function testCreateAMinimalUser()
    {
        // call the web service with minimal information
        $loginName = 'testUser'.time();
        $email = 'testUser@local';
        $status = 5;
        $json = json_encode(
            [
                'loginname' => $loginName,
                'firstname' => 'Małgorzata',
                'lastname' => 'Summer',
                'original_user_id_name' => 'external_user_id',
                'original_user_id_value' => $loginName,
                'email' => $email,
                'status' => $status,
                'password' => 'test',
            ]
        );
        $userId = $this->integer([ 'json' => $json ]);

        // assert the user was saved and given the returned user id
        $user = UserManager::getManager()->find($userId);
        $this->assertNotNull($user, 'the returned userId does not point to an user');

        // assert each field was filled with provided information
        $this->assertSame($loginName, $user->getUserName());
        $this->assertSame($email, $user->getEmail());
        $this->assertSame($status, $user->getStatus());

        // clean up
        UserManager::delete_user($userId);
    }

    /**
     * Creates a test user with an extra field asserts that the extra field values were saved.
     *
     * @throws Exception if it cannot delete the created test user
     */
    public function testCreateAUserWithExtraFields()
    {
        // call the web service
        $extraFieldName = 'age';
        $extraFieldOriginalValue = '29';
        $loginName = 'testUser'.time();
        $json = json_encode(
            [
                'loginname' => $loginName,
                'email' => 'testUser@local',
                'original_user_id_name' => 'external_user_id',
                'original_user_id_value' => $loginName,
                'status' => 5,
                'password' => 'test',
                'firstname' => 'Małgorzata',
                'lastname' => 'Summer',
                'extra' => [
                    ['field_name' => $extraFieldName, 'field_value' => $extraFieldOriginalValue],
                ],
            ]
        );
        $userId = $this->integer(['json' => $json]);

        // assert user extra field value was saved
        $savedValue = (new ExtraFieldValue('user'))->get_values_by_handler_and_field_variable($userId, $extraFieldName);
        $this->assertNotFalse($savedValue);
        $this->assertSame($extraFieldOriginalValue, $savedValue['value']);

        // clean up
        UserManager::delete_user($userId);
    }
}
