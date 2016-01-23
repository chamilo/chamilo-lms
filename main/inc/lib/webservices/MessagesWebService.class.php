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
}
