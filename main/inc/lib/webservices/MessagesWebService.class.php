<?php
/* For licensing terms, see /license.txt */

/**
 * Class for manage the messages web service
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 * @package chamilo.webservices.messages
 */
class MessagesWebService extends WebService
{
    const SERVICE_NAME = 'MsgREST';
    const EXTRA_FIELD_GCM_REGISTRATION = 'gcm_registration_id';

    /**
     * Generate the api key for a user
     * @param int $userId The user id
     * @return string The api key
     */
    public function generateApiKey($userId)
    {
        $apiKey = UserManager::get_api_keys($userId, self::SERVICE_NAME);

        if (empty($apiKey)) {
            UserManager::add_api_key($userId, self::SERVICE_NAME);

            $apiKey = UserManager::get_api_keys($userId, self::SERVICE_NAME);
        }

        return current($apiKey);
    }

    /**
     * Get the user api key
     * @param string $username The user name
     * @return string The api key
     */
    public function getApiKey($username)
    {
        $userInfo = api_get_user_info_from_username($username);
        $userId = $userInfo['user_id'];

        if ($this->apiKey !== null) {
            return $this->apiKey;
        } else {
            $this->apiKey = $this->generateApiKey($userId);

            return $this->apiKey;
        }
    }

    /**
     * Check if the api is valid for a user
     * @param string $username The username
     * @param string $apiKeyToValidate The api key
     * @return boolean Whether the api belongs to the user return true. Otherwise return false
     */
    public static function isValidApiKey($username, $apiKeyToValidate)
    {
        $userInfo = api_get_user_info_from_username($username);
        $userId = $userInfo['user_id'];

        $apiKeys = UserManager::get_api_keys($userId, self::SERVICE_NAME);

        if (!empty($apiKeys)) {
            $apiKey = current($apiKeys);

            if ($apiKey == $apiKeyToValidate) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the count of new messages for a user
     * @param string $username The username
     * @param int $lastId The id of the last received message
     * @return int The count fo new messages
     */
    public function countNewMessages($username, $lastId = 0)
    {
        $userInfo = api_get_user_info_from_username($username);
        $userId = $userInfo['user_id'];

        return MessageManager::countMessagesFromLastReceivedMessage($userId, $lastId);
    }

    /**
     * Get the list of new messages for a user
     * @param string $username The username
     * @param int $lastId The id of the last received message
     * @return array the new message list
     */
    public function getNewMessages($username, $lastId = 0)
    {
        $messages = array();

        $userInfo = api_get_user_info_from_username($username);
        $userId = $userInfo['user_id'];

        $lastMessages = MessageManager::getMessagesFromLastReceivedMessage($userId, $lastId);

        foreach ($lastMessages as $message) {
            $hasAttachments = MessageManager::hasAttachments($message['id']);

            $messages[] = array(
                'id' => $message['id'],
                'title' => $message['title'],
                'sender' => array(
                    'id' => $message['user_id'],
                    'lastname' => $message['lastname'],
                    'firstname' => $message['firstname'],
                    'completeName' => api_get_person_name($message['firstname'], $message['lastname']),
                ),
                'sendDate' => $message['send_date'],
                'content' => $message['content'],
                'hasAttachments' => $hasAttachments,
                'platform' => array(
                    'website' => api_get_path(WEB_PATH),
                    'messagingTool' => api_get_path(WEB_PATH) . 'main/messages/inbox.php'
                )
            );
        }

        return $messages;
    }

    /**
     * Create the user extra field
     */
    public static function init()
    {
        $extraField = new ExtraField('user');
        $fieldInfo = $extraField->get_handler_field_info_by_field_variable(self::EXTRA_FIELD_GCM_REGISTRATION);

        if (empty($fieldInfo)) {
            $extraField->save([
                'variable' => self::EXTRA_FIELD_GCM_REGISTRATION,
                'field_type' => ExtraField::FIELD_TYPE_TEXT,
                'display_text' => self::EXTRA_FIELD_GCM_REGISTRATION
            ]);
        }
    }

    /**
     * Register the GCM Registration ID for a user
     * @param Chamilo\UserBundle\Entity\User $user The user
     * @param string $registrationId The token registration id from GCM
     * @return int The id after insert or the number of affected rows after update. Otherwhise return false
     */
    public static function setGcmRegistrationId(Chamilo\UserBundle\Entity\User $user, $registrationId)
    {
        $registrationId = Security::remove_XSS($registrationId);
        $extraFieldValue = new ExtraFieldValue('user');

        return $extraFieldValue->save([
            'variable' => self::EXTRA_FIELD_GCM_REGISTRATION,
            'value' => $registrationId,
            'item_id' => $user->getId()
        ]);
    }

    /**
     * Send the push notifications to MobileMessaging app
     * @param array $userIds The IDs of users who will be notified
     * @param string $title The notification title
     * @param string $content The notification content
     * @return int The number of success notifications. Otherwise returns false
     */
    public static function sendPushNotification(array $userIds, $title, $content)
    {
        if (api_get_configuration_value('messaging_allow_send_push_notification') !== 'true') {
            return false;
        }

        $gdcApiKey = api_get_configuration_value('messaging_gdc_api_key');

        if ($gdcApiKey === false) {
            return false;
        }

        $content = str_replace(['<br>', '<br/>', '<br />'], "\n", $content);
        $content = strip_tags($content);
        $content = html_entity_decode($content, ENT_QUOTES);

        $gcmRegistrationIds = [];

        foreach ($userIds as $userId) {
            $extraFieldValue = new ExtraFieldValue('user');
            $valueInfo = $extraFieldValue->get_values_by_handler_and_field_variable(
                $userId,
                self::EXTRA_FIELD_GCM_REGISTRATION
            );

            if (empty($valueInfo)) {
                continue;
            }

            $gcmRegistrationIds[] = $valueInfo['value'];
        }

        $headers = [
            'Authorization: key=' . $gdcApiKey,
            'Content-Type: application/json'
        ];

        $fields = json_encode([
            'registration_ids' => $gcmRegistrationIds,
            'data' => [
                'title' => $title,
                'message' => $content
            ]
        ]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://gcm-http.googleapis.com/gcm/send');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        $result = curl_exec($ch);
        curl_close($ch);

        $decodedResult = json_decode($result);

        return $decodedResult->success;
    }
}
