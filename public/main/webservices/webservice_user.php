<?php
/* For licensing terms, see /license.txt */
/**
 * @package chamilo.webservices
 */
require_once __DIR__.'/../inc/global.inc.php';
require_once __DIR__.'/webservice.php';

/**
 * Web services available for the User module. This class extends the WS class.
 */
class WSUser extends WS
{
    /**
     * Disables a user.
     *
     * @param string API secret key
     * @param string User id field name. Use "chamilo_user_id" as the field name if you want to use the internal user_id
     * @param string User id value
     */
    public function DisableUser(
        $secret_key,
        $user_id_field_name,
        $user_id_value
    ) {
        $verifKey = $this->verifyKey($secret_key);
        if ($verifKey instanceof WSError) {
            // Let the implementation handle it
            $this->handleError($verifKey);
        } else {
            $result = $this->changeUserActiveState(
                $user_id_field_name,
                $user_id_value,
                0
            );
            if ($result instanceof WSError) {
                $this->handleError($result);
            }
        }
    }

    /**
     * Disables multiple users.
     *
     * @param string API secret key
     * @param array Array of users with elements of the form
     * array('user_id_field_name' => 'name_of_field', 'user_id_value' => 'value')
     *
     * @return array Array with elements like
     *               array('user_id_value' => 'value', 'result' => array('code' => 0, 'message' => 'Operation was successful')).
     *               Note that if the result array contains a code different
     *               than 0, an error occured
     */
    public function DisableUsers($secret_key, $users)
    {
        $verifKey = $this->verifyKey($secret_key);
        if ($verifKey instanceof WSError) {
            // Let the implementation handle it
            $this->handleError($verifKey);
        } else {
            return $this->changeUsersActiveState($users, 0);
        }
    }

    /**
     * Enables a user.
     *
     * @param string API secret key
     * @param string User id field name. Use "chamilo_user_id" as the field name if you want to use the internal user_id
     * @param string User id value
     */
    public function EnableUser($secret_key, $user_id_field_name, $user_id_value)
    {
        $verifKey = $this->verifyKey($secret_key);
        if ($verifKey instanceof WSError) {
            $this->handleError($verifKey);
        } else {
            $result = $this->changeUserActiveState(
                $user_id_field_name,
                $user_id_value,
                1
            );
            if ($result instanceof WSError) {
                $this->handleError($result);
            }
        }
    }

    /**
     * Enables multiple users.
     *
     * @param string API secret key
     * @param array Array of users with elements of the form
     * array('user_id_field_name' => 'name_of_field', 'user_id_value' => 'value')
     *
     * @return array Array with elements like
     *               array('user_id_value' => 'value', 'result' => array('code' => 0, 'message' => 'Operation was successful')).
     *               Note that if the result array contains a code different
     *               than 0, an error occured
     */
    public function EnableUsers($secret_key, $users)
    {
        $verifKey = $this->verifyKey($secret_key);
        if ($verifKey instanceof WSError) {
            // Let the implementation handle it
            $this->handleError($verifKey);
        } else {
            return $this->changeUsersActiveState($users, 1);
        }
    }

    /**
     * Deletes a user.
     *
     * @param string API secret key
     * @param string User id field name. Use "chamilo_user_id" as the field name if you want to use the internal user_id
     * @param string User id value
     */
    public function DeleteUser($secret_key, $user_id_field_name, $user_id_value)
    {
        $verifKey = $this->verifyKey($secret_key);
        if ($verifKey instanceof WSError) {
            $this->handleError($verifKey);
        } else {
            $result = $this->deleteUserHelper(
                $user_id_field_name,
                $user_id_value
            );
            if ($result instanceof WSError) {
                $this->handleError($result);
            }
        }
    }

    /**
     * Deletes multiple users.
     *
     * @param string API secret key
     * @param array Array of users with elements of the form
     * array('user_id_field_name' => 'name_of_field', 'user_id_value' => 'value')
     *
     * @return array Array with elements like
     *               array('user_id_value' => 'value', 'result' => array('code' => 0, 'message' => 'Operation was successful')).
     *               Note that if the result array contains a code different
     *               than 0, an error occured
     */
    public function DeleteUsers($secret_key, $users)
    {
        $verifKey = $this->verifyKey($secret_key);
        if ($verifKey instanceof WSError) {
            $this->handleError($verifKey);
        } else {
            $results = [];
            foreach ($users as $user) {
                $result_tmp = [];
                $result_op = $this->deleteUserHelper(
                    $user['user_id_field_name'],
                    $user['user_id_value']
                );
                $result_tmp['user_id_value'] = $user['user_id_value'];
                if ($result_op instanceof WSError) {
                    // Return the error in the results
                    $result_tmp['result'] = $result_op->toArray();
                } else {
                    $result_tmp['result'] = $this->getSuccessfulResult();
                }
                $results[] = $result_tmp;
            }

            return $results;
        }
    }

    /**
     * Creates a user.
     *
     * @param string API secret key
     * @param string User first name
     * @param string User last name
     * @param int User status
     * @param string Login name
     * @param string Password (encrypted or not)
     * @param string Encrypt method. Leave blank if you are passing the password in clear text,
     * set to the encrypt method used to encrypt the password otherwise. Remember
     * to include the salt in the extra fields if you are encrypting the password
     * @param string User id field name. Use "chamilo_user_id" as the field name if you want to use the internal user_id
     * @param string User id value. Leave blank if you are using the internal user_id
     * @param int Visibility. Set by default to 1
     * @param string User email. Set by default to an empty string
     * @param string Language. Set by default to english
     * @param string Phone. Set by default to an empty string
     * @param string Expiration date. Set to null by default
     * @param array Extra fields. An array with elements of the form
     * array('field_name' => 'name_of_the_field', 'field_value' => 'value_of_the_field'). Set to an empty array by default
     *
     * @return int New user id generated by the system
     */
    public function CreateUser(
        $secret_key,
        $firstname,
        $lastname,
        $status,
        $login,
        $password,
        $encrypt_method,
        $user_id_field_name,
        $user_id_value,
        $visibility = 1,
        $email = '',
        $language = 'english',
        $phone = '',
        $expiration_date = '0000-00-00 00:00:00',
        $extras = []
    ) {
        // First, verify the secret key
        $verifKey = $this->verifyKey($secret_key);
        if ($verifKey instanceof WSError) {
            $this->handleError($verifKey);
        } else {
            $result = $this->createUserHelper(
                $firstname,
                $lastname,
                $status,
                $login,
                $password,
                $encrypt_method,
                $user_id_field_name,
                $user_id_value,
                $visibility,
                $email,
                $language,
                $phone,
                $expiration_date,
                $extras
            );
            if ($result instanceof WSError) {
                $this->handleError($result);
            } else {
                return $result;
            }
        }
    }

    /**
     * Creates multiple users.
     *
     * @param string API secret key
     * @param array Users array. Each member of this array must follow the structure imposed by the CreateUser method
     *
     * @return array Array with elements of the form
     *               array('user_id_value' => 'original value sent', 'user_id_generated' => 'value_generated', 'result' => array('code' => 0, 'message' => 'Operation was successful'))
     */
    public function CreateUsers($secret_key, $users)
    {
        $verifKey = $this->verifyKey($secret_key);
        if ($verifKey instanceof WSError) {
            $this->handleError($verifKey);
        } else {
            $results = [];
            foreach ($users as $user) {
                $result_tmp = [];
                // re-initialize variables just in case
                $firstname = $lastname = $status = $login = $password = $encrypt_method = $user_id_field_name = $user_id_value = $visibility = $email = $language = $phone = $expiration_date = $extras = null;
                extract($user);
                $result = $this->createUserHelper(
                    $firstname,
                    $lastname,
                    $status,
                    $login,
                    $password,
                    $encrypt_method,
                    $user_id_field_name,
                    $user_id_value,
                    $visibility,
                    $email,
                    $language,
                    $phone,
                    $expiration_date,
                    $extras
                );
                if ($result instanceof WSError) {
                    $result_tmp['result'] = $result->toArray();
                    $result_tmp['user_id_value'] = $user_id_value;
                    $result_tmp['user_id_generated'] = 0;
                } else {
                    $result_tmp['result'] = $this->getSuccessfulResult();
                    $result_tmp['user_id_value'] = $user_id_value;
                    $result_tmp['user_id_generated'] = $result;
                }
                $results[] = $result_tmp;
            }

            return $results;
        }
    }

    /**
     * Edits user info.
     *
     * @param string API secret key
     * @param string User id field name. Use "chamilo_user_id" in order to use internal system id
     * @param string User id value
     * @param string First name
     * @param string Last name
     * @param int User status
     * @param string Login name
     * @param string Password. Leave blank if you don't want to update it
     * @param string Encrypt method
     * @param string User email
     * @param string Language. Set by default to english
     * @param string Phone. Set by default to an empty string
     * @param string Expiration date. Set to null by default
     * @param array Extra fields. An array with elements of the form
     * ('field_name' => 'name_of_the_field', 'field_value' => 'value_of_the_field'). Leave empty if you don't want to update
     */
    public function EditUser(
        $secret_key,
        $user_id_field_name,
        $user_id_value,
        $firstname,
        $lastname,
        $status,
        $loginname,
        $password,
        $encrypt_method,
        $email,
        $language,
        $phone,
        $expiration_date,
        $extras
    ) {
        // First, verify the secret key
        $verifKey = $this->verifyKey($secret_key);
        if ($verifKey instanceof WSError) {
            $this->handleError($verifKey);
        } else {
            $extras_associative = [];
            if (!empty($extras)) {
                foreach ($extras as $extra) {
                    $extras_associative[$extra['field_name']] = $extra['field_value'];
                }
            }

            $result = $this->editUserHelper(
                $user_id_field_name,
                $user_id_value,
                $firstname,
                $lastname,
                $status,
                $loginname,
                $password,
                $encrypt_method,
                $email,
                $language,
                $phone,
                $expiration_date,
                $extras_associative
            );
            if ($result instanceof WSError) {
                $this->handleError($result);
            }
        }
    }

    /**
     * Edits multiple users.
     *
     * @param string API secret key
     * @param array Users array. Each member of this array must follow the structure imposed by the EditUser method
     *
     * @return array Array with elements like
     *               array('user_id_value' => 'value', 'result' => array('code' => 0, 'message' => 'Operation was successful')).
     *               Note that if the result array contains a code different
     *               than 0, an error occured
     */
    public function EditUsers($secret_key, $users)
    {
        $verifKey = $this->verifyKey($secret_key);
        if ($verifKey instanceof WSError) {
            $this->handleError($verifKey);
        } else {
            $results = [];
            foreach ($users as $user) {
                $result_tmp = [];
                // re-initialize variables just in case
                $user_id_field_name = $user_id_value = $firstname = $lastname = $status = $loginname = $password = $encrypt_method = $email = $language = $phone = $expiration_date = $extras = null;
                extract($user);
                $result_op = $this->editUserHelper(
                    $user_id_field_name,
                    $user_id_value,
                    $firstname,
                    $lastname,
                    $status,
                    $loginname,
                    $password,
                    $encrypt_method,
                    $email,
                    $language,
                    $phone,
                    $expiration_date,
                    $extras
                );
                $result_tmp['user_id_value'] = $user['user_id_value'];
                if ($result_op instanceof WSError) {
                    // Return the error in the results
                    $result_tmp['result'] = $result_op->toArray();
                } else {
                    $result_tmp['result'] = $this->getSuccessfulResult();
                }
                $results[] = $result_tmp;
            }

            return $results;
        }
    }

    /**
     * Enables or disables a user.
     *
     * @param string User id field name
     * @param string User id value
     * @param int Set to 1 to enable and to 0 to disable
     *
     * @return int
     */
    protected function changeUserActiveState(
        $user_id_field_name,
        $user_id_value,
        $state
    ) {
        $user_id = $this->getUserId($user_id_field_name, $user_id_value);
        if ($user_id instanceof WSError) {
            return $user_id;
        } else {
            if ($state == 0) {
                UserManager::disable($user_id);
            } else {
                if ($state == 1) {
                    UserManager::enable($user_id);
                }
            }
        }
    }

    /**
     * Enables or disables multiple users.
     *
     * @param array Users
     * @param int Set to 1 to enable and to 0 to disable
     *
     * @return array Array of results
     */
    protected function changeUsersActiveState($users, $state)
    {
        $results = [];
        foreach ($users as $user) {
            $result_tmp = [];
            $result_op = $this->changeUserActiveState(
                $user['user_id_field_name'],
                $user['user_id_value'],
                $state
            );
            $result_tmp['user_id_value'] = $user['user_id_value'];
            if ($result_op instanceof WSError) {
                // Return the error in the results
                $result_tmp['result'] = $result_op->toArray();
            } else {
                $result_tmp['result'] = $this->getSuccessfulResult();
            }
            $results[] = $result_tmp;
        }

        return $results;
    }

    /**
     * Deletes a user (helper method).
     *
     * @param string User id field name. Use "chamilo_user_id" as the field name if you want to use the internal user_id
     * @param string User id value
     *
     * @return mixed True if user was successfully deleted, WSError otherwise
     */
    protected function deleteUserHelper($user_id_field_name, $user_id_value)
    {
        $user_id = $this->getUserId($user_id_field_name, $user_id_value);
        if ($user_id instanceof WSError) {
            return $user_id;
        } else {
            if (!UserManager::delete_user($user_id)) {
                return new WSError(
                    101,
                    "There was a problem while deleting this user"
                );
            } else {
                return true;
            }
        }
    }

    /**
     * Creates a user (helper method).
     *
     * @param string User first name
     * @param string User last name
     * @param int User status
     * @param string Login name
     * @param string Password (encrypted or not)
     * @param string Encrypt method. Leave blank if you are passing the password in clear text,
     * set to the encrypt method used to encrypt the password otherwise. Remember
     * to include the salt in the extra fields if you are encrypting the password
     * @param string User id field name. Use "chamilo_user_id" as the field name if you want to use the internal user_id
     * @param string User id value. Leave blank if you are using the internal user_id
     * @param int visibility
     * @param string user email
     * @param string language
     * @param string phone
     * @param string Expiration date
     * @param array Extra fields. An array with elements of the form
     * array('field_name' => 'name_of_the_field', 'field_value' => 'value_of_the_field').
     *
     * @return mixed New user id generated by the system, WSError otherwise
     */
    protected function createUserHelper(
        $firstname,
        $lastname,
        $status,
        $login,
        $password,
        $encrypt_method,
        $user_id_field_name,
        $user_id_value,
        $visibility,
        $email,
        $language,
        $phone,
        $expiration_date,
        $extras = []
    ) {
        // Add the original user id field name and value to the extra fields if needed
        $extras_associative = [];
        if ($user_id_field_name != "chamilo_user_id") {
            $extras_associative[$user_id_field_name] = $user_id_value;
        }
        if (!empty($extras)) {
            foreach ($extras as $extra) {
                $extras_associative[$extra['field_name']] = $extra['field_value'];
            }
        }
        $result = UserManager::create_user(
            $firstname,
            $lastname,
            $status,
            $email,
            $login,
            $password,
            '',
            $language,
            $phone,
            '',
            PLATFORM_AUTH_SOURCE,
            $expiration_date,
            $visibility,
            0,
            $extras_associative,
            $encrypt_method
        );
        if (!$result) {
            return new WSError(104, 'There was an error creating the user');
        } else {
            return $result;
        }
    }

    /**
     * Edits user info (helper method).
     *
     * @param string User id field name. Use "chamilo_user_id" in order to use internal system id
     * @param string User id value
     * @param string First name
     * @param string Last name
     * @param int User status
     * @param string Login name
     * @param string Password. Leave blank if you don't want to update it
     * @param string Encrypt method
     * @param string User email
     * @param string Language. Set by default to english
     * @param string Phone. Set by default to an empty string
     * @param string Expiration date. Set to null by default
     * @param array Extra fields. An array with elements of the form
     * ('field_name' => 'name_of_the_field', 'field_value' => 'value_of_the_field').
     * Leave empty if you don't want to update
     *
     * @return mixed True if user was successfully updated, WSError otherwise
     */
    protected function editUserHelper(
        $user_id_field_name,
        $user_id_value,
        $firstname,
        $lastname,
        $status,
        $loginname,
        $password,
        $encrypt_method,
        $email,
        $language,
        $phone,
        $expiration_date,
        $extras
    ) {
        $user_id = $this->getUserId($user_id_field_name, $user_id_value);
        if ($user_id instanceof WSError) {
            return $user_id;
        } else {
            if ($password == '') {
                $password = null;
            }
            $user_info = api_get_user_info($user_id);
            if (count($extras) == 0) {
                $extras = null;
            }

            $result = UserManager::update_user(
                $user_id,
                $firstname,
                $lastname,
                $loginname,
                $password,
                PLATFORM_AUTH_SOURCE,
                $email,
                $status,
                '',
                $phone,
                $user_info['picture_uri'],
                $expiration_date,
                $user_info['active'],
                null,
                $user_info['hr_dept_id'],
                $extras,
                $encrypt_method
            );
            if (!$result) {
                return new WSError(105, 'There was an error updating the user');
            } else {
                return $result;
            }
        }
    }
}
