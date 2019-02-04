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
