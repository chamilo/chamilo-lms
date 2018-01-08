<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * Class Chat
 * @todo ChamiloSession instead of $_SESSION
 * @package chamilo.library.chat
 */
class Chat extends Model
{
    public $columns = [
        'id',
        'from_user',
        'to_user',
        'message',
        'sent',
        'recd',
    ];
    public $window_list = [];

    /**
     * The contructor sets the chat table name and the window_list attribute
     */
    public function __construct()
    {
        parent::__construct();
        $this->table = Database::get_main_table(TABLE_MAIN_CHAT);
        $this->window_list = Session::read('window_list');
        Session::write('window_list', $this->window_list);
    }

    /**
     * Get user chat status
     * @return int 0 if disconnected, 1 if connected
     */
    public function getUserStatus()
    {
        $status = UserManager::get_extra_user_data_by_field(
            api_get_user_id(),
            'user_chat_status',
            false,
            true
        );

        return $status['user_chat_status'];
    }

    /**
    * Set user chat status
    * @param int $status 0 if disconnected, 1 if connected
     *
    * @return void
    */
    public function setUserStatus($status)
    {
        UserManager::update_extra_field_value(
            api_get_user_id(),
            'user_chat_status',
            $status
        );
    }

    /**
     * @param int $currentUserId
     * @param int $userId
     * @param bool $latestMessages
     * @return array
     */
    public function getLatestChat($currentUserId, $userId, $latestMessages)
    {
        $items = self::getPreviousMessages(
            $currentUserId,
            $userId,
            0,
            $latestMessages
        );

        return array_reverse($items);
    }

    /**
     * @param array $chatHistory
     * @param int $latestMessages
     * @return mixed
     */
    public function getAllLatestChats($chatHistory, $latestMessages = 5)
    {
        $currentUserId = api_get_user_id();
        $chats = [];
        if (!empty($chatHistory)) {
            foreach ($chatHistory as $chat) {
                $userId = $chat['user_info']['user_id'];
                $items = self::getLatestChat(
                    $currentUserId,
                    $userId,
                    $latestMessages
                );
                $chats[$userId]['items'] = $items;
            }
        }
        return $chats;
    }

    /**
     * Starts a chat session and returns JSON array of status and chat history
     * @return bool (prints output in JSON format)
     */
    public function startSession()
    {
        $chatList = Session::read('chatHistory');
        $chats = self::getAllLatestChats($chatList);
        $return = [
            'user_status' => $this->getUserStatus(),
            'me' => get_lang('Me'),
            'user_id' => api_get_user_id(),
            'items' => $chats
        ];
        echo json_encode($return);
        return true;
    }

    /**
     * @param int $fromUserId
     * @param int $toUserId
     * @return mixed
     */
    public function getCountMessagesExchangeBetweenUsers($fromUserId, $toUserId)
    {
        $row = Database::select(
            'count(*) as count',
            $this->table,
            [
                'where' => [
                    '(from_user = ? AND to_user = ?) OR (from_user = ? AND to_user = ?) ' => [
                        $fromUserId,
                        $toUserId,
                        $toUserId,
                        $fromUserId
                    ]
                ]
            ],
            'first'
        );

        return $row['count'];
    }

    /**
     * @param int $fromUserId
     * @param int $toUserId
     * @param int $visibleMessages
     * @param int $previousMessageCount messages to show
     * @return array
     */
    public function getPreviousMessages(
        $fromUserId,
        $toUserId,
        $visibleMessages = 1,
        $previousMessageCount = 5
    ) {
        $currentUserId = api_get_user_id();
        $toUserId = (int) $toUserId;
        $fromUserId = (int) $fromUserId;
        $previousMessageCount = (int) $previousMessageCount;
        if (empty($toUserId) || empty($fromUserId)) {
            return [];
        }
        $total = self::getCountMessagesExchangeBetweenUsers(
            $fromUserId,
            $toUserId
        );
        $show = $total - $visibleMessages;
        $from = $show - $previousMessageCount;
        if ($from < 0) {
            return [];
        }

        $sql = "SELECT * FROM ".$this->table."
                WHERE 
                    (
                        to_user = $toUserId AND 
                        from_user = $fromUserId)
                    OR
                    (
                        from_user = $toUserId AND 
                        to_user =  $fromUserId
                    )  
                ORDER BY id ASC
                LIMIT $from, $previousMessageCount
                ";
        $result = Database::query($sql);
        $rows = Database::store_result($result);
        $fromUserInfo = api_get_user_info($fromUserId, true);
        $toUserInfo = api_get_user_info($toUserId, true);
        $users = [
            $fromUserId => $fromUserInfo,
            $toUserId => $toUserInfo,
        ];
        $items = [];
        $rows = array_reverse($rows);
        foreach ($rows as $chat) {
            $fromUserId = $chat['from_user'];
            $userInfo = $users[$fromUserId];
            $username = $userInfo['complete_name'];
            if ($currentUserId == $fromUserId) {
                $username = get_lang('Me');
            }

            $chat['message'] = Security::remove_XSS($chat['message']);
            $item = [
                'id' => $chat['id'],
                's' => '0',
                'f' => $fromUserId,
                'm' => $chat['message'],
                'username' => $username,
                'user_info' => [
                    'username' => $username,
                    'online' => $userInfo['user_is_online'],
                    'avatar' => $userInfo['avatar_small'],
                    'user_id' => $userInfo['user_id']
                ],
                'date' => api_strtotime($chat['sent'], 'UTC')
            ];
            $items[] = $item;
            $_SESSION['openChatBoxes'][$fromUserId] = api_strtotime($chat['sent'], 'UTC');
        }
        //array_unshift($_SESSION['chatHistory'][$fromUserId]['items'], $items);

        return $items;
    }

    /**
     * Refreshes the chat windows (usually called every x seconds through AJAX)
     * @return void (prints JSON array of chat windows)
     */
    public function heartbeat()
    {
        $to_user_id = api_get_user_id();

        $sql = "SELECT * FROM ".$this->table."
                WHERE to_user = '".intval($to_user_id)."' AND (recd = 0)
                ORDER BY id ASC";
        $result = Database::query($sql);

        $chat_list = [];
        while ($chat = Database::fetch_array($result, 'ASSOC')) {
            $chat_list[$chat['from_user']]['items'][] = $chat;
        }

        $items = [];
        foreach ($chat_list as $fromUserId => $rows) {
            $rows = $rows['items'];
            $user_info = api_get_user_info($fromUserId, true);
            $count = $this->getCountMessagesExchangeBetweenUsers(
                $fromUserId,
                $to_user_id
            );

            $chatItems = self::getLatestChat($fromUserId, $to_user_id, 5);

            // Cleaning tsChatBoxes
            unset($_SESSION['tsChatBoxes'][$fromUserId]);

            foreach ($rows as $chat) {
                $_SESSION['openChatBoxes'][$fromUserId] = api_strtotime($chat['sent'], 'UTC');
            }

            $items[$fromUserId]['items'] = $chatItems;
            $items[$fromUserId]['total_messages'] = $count;
            $items[$fromUserId]['user_info']['user_name'] = $user_info['complete_name'];
            $items[$fromUserId]['user_info']['online'] = $user_info['user_is_online'];
            $items[$fromUserId]['user_info']['avatar'] = $user_info['avatar_small'];
            $items[$fromUserId]['user_info']['user_id'] = $user_info['user_id'];

            $_SESSION['chatHistory'][$fromUserId]['items'] = $chatItems;
            $_SESSION['chatHistory'][$fromUserId]['total_messages'] = $count;
            $_SESSION['chatHistory'][$fromUserId]['user_info']['user_id'] = $user_info['user_id'];
            $_SESSION['chatHistory'][$fromUserId]['user_info']['user_name'] = $user_info['complete_name'];
            $_SESSION['chatHistory'][$fromUserId]['user_info']['online'] = $user_info['user_is_online'];
            $_SESSION['chatHistory'][$fromUserId]['user_info']['avatar'] = $user_info['avatar_small'];
        }

        if (!empty($_SESSION['openChatBoxes'])) {
            foreach ($_SESSION['openChatBoxes'] as $userId => $time) {
                if (!isset($_SESSION['tsChatBoxes'][$userId])) {
                    $now = time() - $time;
                    $time = api_convert_and_format_date($time, DATE_TIME_FORMAT_SHORT_TIME_FIRST);
                    $message = sprintf(get_lang('SentAtX'), $time);

                    if ($now > 180) {
                        $item = [
                            's' => '2',
                            'f' => $userId,
                            'm' => $message
                        ];

                        if (isset($_SESSION['chatHistory'][$userId])) {
                            $_SESSION['chatHistory'][$userId]['items'][] = $item;
                        }
                        $_SESSION['tsChatBoxes'][$userId] = 1;
                    }
                }
            }
        }

        $sql = "UPDATE ".$this->table." 
                SET recd = 1
                WHERE to_user = '".$to_user_id."' AND recd = 0";
        Database::query($sql);

        echo json_encode(['items' => $items]);
    }

    /**
     * Saves into session the fact that a chat window exists with the given user
     * @param int The ID of the user with whom the current user is chatting
     * @param integer $userId
     */
    public function saveWindow($userId)
    {
        $this->window_list[$userId] = true;
        Session::write('window_list', $this->window_list);
    }

    /**
     * Sends a message from one user to another user
     * @param int $fromUserId The ID of the user sending the message
     * @param int $to_user_id The ID of the user receiving the message
     * @param string $message Message
     * @param boolean $printResult Optional. Whether print the result
     * @param boolean $sanitize Optional. Whether sanitize the message
     *
     * @return void Prints "1"
     */
    public function send(
        $fromUserId,
        $to_user_id,
        $message,
        $printResult = true,
        $sanitize = true
    ) {
        $user_friend_relation = SocialManager::get_relation_between_contacts(
            $fromUserId,
            $to_user_id
        );

        if ($user_friend_relation == USER_RELATION_TYPE_FRIEND) {
            $now = api_get_utc_datetime();
            $user_info = api_get_user_info($to_user_id, true);
            $this->saveWindow($to_user_id);
            $_SESSION['openChatBoxes'][$to_user_id] = $now;

            if ($sanitize) {
                $messagesan = self::sanitize($message);
            } else {
                $messagesan = $message;
            }

            if (!isset($_SESSION['chatHistory'][$to_user_id])) {
                $_SESSION['chatHistory'][$to_user_id] = [];
            }
            $item = [
                "s" => "1",
                "f" => $fromUserId,
                "m" => $messagesan,
                'date' => api_strtotime($now, 'UTC'),
                'username' => get_lang('Me')
            ];
            $_SESSION['chatHistory'][$to_user_id]['items'][] = $item;
            $_SESSION['chatHistory'][$to_user_id]['user_info']['user_name'] = $user_info['complete_name'];
            $_SESSION['chatHistory'][$to_user_id]['user_info']['online'] = $user_info['user_is_online'];
            $_SESSION['chatHistory'][$to_user_id]['user_info']['avatar'] = $user_info['avatar_small'];
            $_SESSION['chatHistory'][$to_user_id]['user_info']['user_id'] = $user_info['user_id'];

            unset($_SESSION['tsChatBoxes'][$to_user_id]);

            $params = [];
            $params['from_user'] = intval($fromUserId);
            $params['to_user'] = intval($to_user_id);
            $params['message'] = $message;
            $params['sent'] = api_get_utc_datetime();

            if (!empty($fromUserId) && !empty($to_user_id)) {
                $this->save($params);
            }

            if ($printResult) {
                echo '1';
                exit;
            }
        } else {
            if ($printResult) {
                echo '0';
                exit;
            }
        }
    }

    /**
     * Close a specific chat box (user ID taken from $_POST['chatbox'])
     * @return void Prints "1"
     */
    public function close()
    {
        unset($_SESSION['openChatBoxes'][$_POST['chatbox']]);
        unset($_SESSION['chatHistory'][$_POST['chatbox']]);
        echo "1";
        exit;
    }

    /**
     * Filter chat messages to avoid XSS or other JS
     * @param string $text Unfiltered message
     *
     * @return string Filtered message
     */
    public function sanitize($text)
    {
        $text = htmlspecialchars($text, ENT_QUOTES);
        $text = str_replace("\n\r", "\n", $text);
        $text = str_replace("\r\n", "\n", $text);
        $text = str_replace("\n", "<br>", $text);

        return $text;
    }

    /**
     * SET Disable Chat
     * @param boolean $status to disable chat
     * @return void
     */
    public static function setDisableChat($status = true)
    {
        Session::write('disable_chat', $status);
    }

    /**
     * Disable Chat - disable the chat
     * @return boolean - return true if setDisableChat status is true
     */
    public static function disableChat()
    {
        $status = Session::read('disable_chat');
        if (!empty($status)) {
            if ($status == true) {
                Session::write('disable_chat', null);
                return true;
            }
        }

        return false;
    }

    /**
     * @return bool
     */
    public function isChatBlockedByExercises()
    {
        $currentExercises = Session::read('current_exercises');
        if (!empty($currentExercises)) {
            foreach ($currentExercises as $attempt_status) {
                if ($attempt_status == true) {
                    return true;
                }
            }
        }

        return false;
    }
}
