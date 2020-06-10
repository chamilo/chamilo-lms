<?php

/* For licensing terms, see /license.txt */

require_once __DIR__.'/V2TestCase.php';
require_once __DIR__.'/../../../../vendor/autoload.php';

/**
 * Class UpdateUserFromUsernameTest
 *
 * UPDATE_USER_FROM_USERNAME webservice unit tests
 */
class UpdateUserFromUsernameTest extends V2TestCase
{
    public function action()
    {
        return 'update_user_from_username';
    }

    /**
     * updates a user
     * asserts that its data was updated, including extra fields
     *
     * @throws Exception if it cannot delete the created test user
     */
    public function test()
    {
        // create a user with initial data and extra field values
        $loginName = 'testUser'.time();
        $userId = UserManager::create_user(
            'Initial first name',
            'Initial last name',
            5,
            'initial.email@local',
            $loginName,
            'xXxxXxxXX'
        );

        // create an extra field and initialise its value for the user
        $extraFieldModel = new ExtraField('user');
        $extraFieldName = 'extraUserField'.time();
        $extraFieldId = $extraFieldModel->save(
            [
                'field_type' => ExtraField::FIELD_TYPE_TEXT,
                'variable' => $extraFieldName,
                'display_text' => $extraFieldName,
                'visible_to_self' => 1,
                'visible_to_others' => 1,
                'changeable' => 1,
                'filter' => 1,
            ]
        );
        SessionManager::update_session_extra_field_value($userId, $extraFieldName, 'extra field initial value');

        // update user with new data and extra field data
        $newFirstName = 'New first name';
        $newLastName = 'New last name';
        $newStatus = 1;
        $newEmail = 'new.address@local';
        $parameters = [
            'firstname' => $newFirstName,
            'lastname' => $newLastName,
            'status' => $newStatus,
            'email' => $newEmail,
        ];
        $extraFieldNewValue = 'extra field new value';
        $parameters['extra'] = [
            ['field_name' => $extraFieldName, 'field_value' => $extraFieldNewValue],
        ];
        $parameters['loginname'] = $loginName;
        $updated = $this->boolean($parameters);
        $this->assertTrue($updated);

        // assert the webservice reports an error with a non-existent login name
        $parameters['loginname'] = 'santaClaus';
        $this->assertSame(get_lang('UserNotFound'), $this->errorMessageString($parameters));

        // compare each saved value to the original
        /** @var User $user */
        $userManager = UserManager::getManager();
        $user = $userManager->find($userId);
        $userManager->reloadUser($user);
        $this->assertSame($newFirstName, $user->getFirstname());
        $this->assertSame($newLastName, $user->getLastname());
        $this->assertSame($newStatus, $user->getStatus());
        $this->assertSame($newEmail, $user->getEmail());

        // assert extra field values have been updated
        $extraFieldValueModel = new ExtraFieldValue('user');
        $extraFieldValue = $extraFieldValueModel->get_values_by_handler_and_field_variable($userId, $extraFieldName);
        $this->assertNotFalse($extraFieldValue);
        $this->assertSame($extraFieldNewValue, $extraFieldValue['value']);

        // clean up
        UserManager::delete_user($userId);
        $extraFieldModel->delete($extraFieldId);
    }
}
