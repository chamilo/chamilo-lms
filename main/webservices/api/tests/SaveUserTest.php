<?php
/* For licensing terms, see /license.txt */

require_once __DIR__.'/V2TestCase.php';

require_once __DIR__.'/../../../../vendor/autoload.php';


/**
 * Class SaveUserTest
 *
 * SAVE_USER webservice unit tests
 */
class SaveUserTest extends V2TestCase
{
    public function action()
    {
        return 'save_user';
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
        $userId = $this->integer( [
            'loginname' => $loginName,
            'email' => $email,
            'status' => $status,
            'password' => 'test',
        ] );

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
     * Creates a test user with an extra field
     * asserts that the extra field values were saved
     *
     * @throws Exception if it cannot delete the created test user
     */
    public function testCreateAUserWithExtraFields()
    {
        // call the web service
        $extraFieldName = 'age';
        $extraFieldOriginalValue = '29';
        $userId = $this->integer( [
            'loginname' => 'testUser'.time(),
            'email' => 'testUser@local',
            'status' => 5,
            'password' => 'test',
            'extra' => [
                ['field_name' => $extraFieldName, 'field_value' => $extraFieldOriginalValue],
            ]
        ] );

        // assert user extra field value was saved
        $savedValue = (new ExtraFieldValue('user'))->get_values_by_handler_and_field_variable($userId, $extraFieldName);
        $this->assertNotFalse($savedValue);
        $this->assertSame($extraFieldOriginalValue, $savedValue['value']);

        // clean up
        UserManager::delete_user($userId);
    }
}
