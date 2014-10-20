<?php
/* For licensing terms, see /license.txt */

/**
 * Class for manage the messages web service
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 * @package chamilo.webservices.messages
 */
class MessagesWebService extends WebService
{

    const FIELD_VARIABLE = 'api_key_message';

    /**
     * Generate the api key for a user
     * @return string The api key
     */
    public function generateApiKey()
    {
        return sha1('Chamilo-LMS');
    }

    /**
     * Get the user api key
     * @param string $username The user name
     * @return string The api key
     */
    public function getApiKey($username)
    {
        $userInfo = api_get_user_info_from_username($username);
        $saveApiKey = false;

        if ($this->apiKey !== null) {
            return $this->apiKey;
        } else {
            $field = new ExtraField('user');
            $fieldData = $field->get_handler_field_info_by_field_variable(self::FIELD_VARIABLE);

            if ($fieldData !== false) { // Exists the api_key_message extra field
                $fieldId = $fieldData['id'];

                $fieldValue = new ExtraFieldValue('user');
                $fieldValueData = $fieldValue->get_values_by_handler_and_field_id($userInfo['user_id'], $fieldId);

                if ($fieldValueData !== false) {
                    return $fieldValueData['field_value'];
                } else {
                    $saveApiKey = true;
                }
            } else {
                $fieldId = UserManager::create_extra_field(self::FIELD_VARIABLE, ExtraField::FIELD_TYPE_TEXT, 'APIKeyMessages', '');

                $saveApiKey = true;
            }

            if ($saveApiKey) {  // If needs save the api key
                $this->apiKey = $this->generateApiKey();

                $fieldValueTable = Database::get_main_table(TABLE_MAIN_USER_FIELD_VALUES);

                Database::insert($fieldValueTable, array(
                    'user_id' => $userInfo['user_id'],
                    'field_id' => $fieldId,
                    'field_value' => $this->apiKey,
                    'tms' => api_get_utc_datetime()
                ));
            }

            return $this->apiKey;
        }
    }

    /**
     * Check if the api is valid for a user
     * @param string $username The username
     * @param string $apiKey The api key
     * @return boolean Whether the api belongs to the user return true. Otherwise return false
     */
    public static function isValidApiKey($username, $apiKey)
    {
        $fieldValueTable = Database::get_main_table(TABLE_MAIN_USER_FIELD_VALUES);
        $fieldTable = Database::get_main_table(TABLE_MAIN_USER_FIELD);
        $userTable = Database::get_main_table(TABLE_MAIN_USER);

        $sql = "SELECT COUNT(1) AS qty "
                . "FROM $fieldValueTable AS v "
                . "INNER JOIN $fieldTable AS f "
                . "ON v.field_id = f.id "
                . "INNER JOIN $userTable AS u "
                . "ON v.user_id = u.user_id "
                . "WHERE u.username = '$username'"
                . "AND (f.field_variable = '" . self::FIELD_VARIABLE . "' "
                . "AND v.field_value = '$apiKey')";

        $result = Database::query($sql);

        if ($result !== false) {
            $row = Database::fetch_assoc($result);

            if ($row['qty'] > 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the count of new messages for a user
     * @param string $username The username
     * @return int The count fo new messages
     */
    public function countNewMessages($username)
    {
        return 0;
    }

    /**
     * Get the list of new messages for a user
     * @param string $username The username
     * @return array the new message list
     */
    public function getNewMessages($username)
    {
        return array();
    }

}
