<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * Class Chat.
 *
 * @todo ChamiloSession instead of $_SESSION
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
     * The contructor sets the chat table name and the window_list attribute.
     */
    public function __construct()
    {
        parent::__construct();
        $this->table = Database::get_main_table(TABLE_MAIN_CHAT);
        $this->window_list = Session::read('window_list');
        Session::write('window_list', $this->window_list);
    }

    /**
     * Get user chat status.
     *
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
     * Set user chat status.
     *
     * @param int $status 0 if disconnected, 1 if connected
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
     * @param int  $currentUserId
     * @param int  $userId
     * @param bool $latestMessages
     *
     * @return array
     */
    public function getLatestChat($currentUserId, $userId, $latestMessages)
    {
        $items = $this->getPreviousMessages(
            $currentUserId,
            $userId,
            0,
            $latestMessages
        );

        return array_reverse($items);
    }

    /**
     * @return string
     */
    public function getContacts()
    {
        $html = SocialManager::listMyFriendsBlock(
            api_get_user_id(),
            '',
            true
        );

        echo $html;
    }

    /**
     * @param array $chatHistory
     * @param int   $latestMessages
     *
     * @return mixed
     */
    public function getAllLatestChats($chatHistory, $latestMessages = 5)
    {
        $currentUserId = api_get_user_id();

        if (empty($chatHistory)) {
            return [];
        }

        $chats = [];
        foreach ($chatHistory as $userId => $time) {
            $total = $this->getCountMessagesExchangeBetweenUsers($userId, $currentUserId);
            $start = $total - $latestMessages;
            if ($start < 0) {
                $start = 0;
            }
            $items = $this->getMessages($userId, $currentUserId, $start, $latestMessages);
            $chats[$userId]['items'] = $items;
            $chats[$userId]['window_user_info'] = api_get_user_info($userId);
        }

        return $chats;
    }

    /**
     * Starts a chat session and returns JSON array of status and chat history.
     *
     * @return bool (prints output in JSON format)
     */
    public function startSession()
    {
        // ofaj
        // $chat = new Chat();
        // $chat->setUserStatus(1);

        $chatList = Session::read('openChatBoxes');
        $chats = $this->getAllLatestChats($chatList);
        $return = [
            'user_status' => $this->getUserStatus(),
            'me' => get_lang('Me'),
            'user_id' => api_get_user_id(),
            'items' => $chats,
            'sec_token' => Security::get_token('chat'),
        ];
        echo json_encode($return);

        return true;
    }

    /**
     * @param int $fromUserId
     * @param int $toUserId
     *
     * @return int
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
                        $fromUserId,
                    ],
                ],
            ],
            'first'
        );

        return (int) $row['count'];
    }

    /**
     * @param int $fromUserId
     * @param int $toUserId
     * @param int $visibleMessages
     * @param int $previousMessageCount messages to show
     *
     * @return array
     */
    public function getPreviousMessages(
        $fromUserId,
        $toUserId,
        $visibleMessages = 1,
        $previousMessageCount = 5,
        $orderBy = ''
    ) {
        $toUserId = (int) $toUserId;
        $fromUserId = (int) $fromUserId;
        $visibleMessages = (int) $visibleMessages;
        $previousMessageCount = (int) $previousMessageCount;

        $total = $this->getCountMessagesExchangeBetweenUsers($fromUserId, $toUserId);
        $show = $total - $visibleMessages;

        if ($show < $previousMessageCount) {
            $show = $previousMessageCount;
        }
        $from = $show - $previousMessageCount;

        if ($from < 0) {
            return [];
        }

        return $this->getMessages($fromUserId, $toUserId, $from, $previousMessageCount, $orderBy);
    }

    /**
     * @param int    $fromUserId
     * @param int    $toUserId
     * @param int    $start
     * @param int    $end
     * @param string $orderBy
     *
     * @return array
     */
    public function getMessages($fromUserId, $toUserId, $start, $end, $orderBy = '')
    {
        $toUserId = (int) $toUserId;
        $fromUserId = (int) $fromUserId;
        $start = (int) $start;
        $end = (int) $end;

        if (empty($toUserId) || empty($fromUserId)) {
            return [];
        }

        $orderBy = Database::escape_string($orderBy);
        if (empty($orderBy)) {
            $orderBy = 'ORDER BY id ASC';
        }

        $sql = "SELECT * FROM ".$this->table."
                WHERE
                    (
                        to_user = $toUserId AND
                        from_user = $fromUserId
                    )
                    OR
                    (
                        from_user = $toUserId AND
                        to_user =  $fromUserId
                    )
                $orderBy
                LIMIT $start, $end
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
            $toUserInfo = $users[$toUserId];

            $items[$chat['id']] = [
                'id' => $chat['id'],
                'message' => Security::remove_XSS($chat['message']),
                'date' => api_strtotime($chat['sent'], 'UTC'),
                'recd' => $chat['recd'],
                'from_user_info' => $userInfo,
                'to_user_info' => $toUserInfo,
            ];
            $_SESSION['openChatBoxes'][$fromUserId] = api_strtotime($chat['sent'], 'UTC');
        }

        return $items;
    }

    /**
     * Refreshes the chat windows (usually called every x seconds through AJAX).
     */
    public function heartbeat()
    {
        $chatHistory = Session::read('chatHistory');
        $currentUserId = api_get_user_id();

        // update current chats
        if (!empty($chatHistory) && is_array($chatHistory)) {
            foreach ($chatHistory as $fromUserId => &$data) {
                $userInfo = api_get_user_info($fromUserId, true);
                $count = $this->getCountMessagesExchangeBetweenUsers($fromUserId, $currentUserId);
                $chatItems = $this->getLatestChat($fromUserId, $currentUserId, 5);
                $data['window_user_info'] = $userInfo;
                $data['items'] = $chatItems;
                $data['total_messages'] = $count;
            }
        }

        $sql = "SELECT * FROM ".$this->table."
                WHERE
                    to_user = '".$currentUserId."' AND recd = 0
                ORDER BY id ASC";
        $result = Database::query($sql);

        $chatList = [];
        while ($chat = Database::fetch_array($result, 'ASSOC')) {
            $chatList[$chat['from_user']][] = $chat;
        }

        foreach ($chatList as $fromUserId => $messages) {
            $userInfo = api_get_user_info($fromUserId, true);
            $count = $this->getCountMessagesExchangeBetweenUsers($fromUserId, $currentUserId);
            $chatItems = $this->getLatestChat($fromUserId, $currentUserId, 5);

            // Cleaning tsChatBoxes
            unset($_SESSION['tsChatBoxes'][$fromUserId]);

            foreach ($messages as $chat) {
                $_SESSION['openChatBoxes'][$fromUserId] = api_strtotime($chat['sent'], 'UTC');
            }

            $chatHistory[$fromUserId] = [
                'window_user_info' => $userInfo,
                'total_messages' => $count,
                'items' => $chatItems,
            ];
        }

        Session::write('chatHistory', $chatHistory);

        $sql = "UPDATE ".$this->table."
                SET recd = 1
                WHERE to_user = $currentUserId AND recd = 0";
        Database::query($sql);

        echo json_encode(['items' => $chatHistory]);
    }

    /**
     * Saves into session the fact that a chat window exists with the given user.
     *
     * @param int $userId
     */
    public function saveWindow($userId)
    {
        $this->window_list[$userId] = true;
        Session::write('window_list', $this->window_list);
    }

    /**
     * Sends a message from one user to another user.
     *
     * @param int    $fromUserId  The ID of the user sending the message
     * @param int    $to_user_id  The ID of the user receiving the message
     * @param string $message     Message
     * @param bool   $printResult Optional. Whether print the result
     * @param bool   $sanitize    Optional. Whether sanitize the message
     */
    public function send(
        $fromUserId,
        $to_user_id,
        $message,
        $printResult = true,
        $sanitize = true
    ) {
        $relation = SocialManager::get_relation_between_contacts($fromUserId, $to_user_id);

        if (!Security::check_token('post', null, 'chat')) {
            if ($printResult) {
                echo '0';
                exit;
            }
        }

        if (USER_RELATION_TYPE_FRIEND == $relation) {
            $now = api_get_utc_datetime();
            $user_info = api_get_user_info($to_user_id, true);
            $this->saveWindow($to_user_id);
            $_SESSION['openChatBoxes'][$to_user_id] = api_strtotime($now, 'UTC');

            if ($sanitize) {
                $messagesan = $this->sanitize($message);
            } else {
                $messagesan = $message;
            }

            if (!isset($_SESSION['chatHistory'][$to_user_id])) {
                $_SESSION['chatHistory'][$to_user_id] = [];
            }
            $item = [
                's' => '1',
                'f' => $fromUserId,
                'm' => $messagesan,
                'date' => api_strtotime($now, 'UTC'),
                'username' => get_lang('Me'),
            ];
            $_SESSION['chatHistory'][$to_user_id]['items'][] = $item;
            $_SESSION['chatHistory'][$to_user_id]['user_info']['user_name'] = $user_info['complete_name'];
            $_SESSION['chatHistory'][$to_user_id]['user_info']['online'] = $user_info['user_is_online'];
            $_SESSION['chatHistory'][$to_user_id]['user_info']['avatar'] = $user_info['avatar_small'];
            $_SESSION['chatHistory'][$to_user_id]['user_info']['user_id'] = $user_info['user_id'];

            unset($_SESSION['tsChatBoxes'][$to_user_id]);

            $params = [];
            $params['from_user'] = (int) $fromUserId;
            $params['to_user'] = (int) $to_user_id;
            $params['message'] = $messagesan;
            $params['sent'] = api_get_utc_datetime();

            if (!empty($fromUserId) && !empty($to_user_id)) {
                $messageId = $this->save($params);

                if ($printResult) {
                    header('Content-Type: application/json');
                    echo json_encode(['id' => $messageId, 'sec_token' => Security::get_token('chat')]);
                    exit;
                }
            }
        }

        if ($printResult) {
            echo '0';
            exit;
        }
    }

    /**
     * Close a specific chat box (user ID taken from $_POST['chatbox']).
     *
     * @param int $userId
     */
    public function closeWindow($userId)
    {
        if (empty($userId)) {
            return false;
        }

        $list = Session::read('openChatBoxes');
        if (isset($list[$userId])) {
            unset($list[$userId]);
            Session::write('openChatBoxes', $list);
        }

        $list = Session::read('chatHistory');
        if (isset($list[$userId])) {
            unset($list[$userId]);
            Session::write('chatHistory', $list);
        }

        return true;
    }

    /**
     * Close chat - disconnects the user.
     */
    public function close()
    {
        Session::erase('tsChatBoxes');
        Session::erase('openChatBoxes');
        Session::erase('chatHistory');
        Session::erase('window_list');
    }

    /**
     * Filter chat messages to avoid XSS or other JS.
     *
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
     * SET Disable Chat.
     *
     * @param bool $status to disable chat
     */
    public static function setDisableChat($status = true)
    {
        Session::write('disable_chat', $status);
    }

    /**
     * Disable Chat - disable the chat.
     *
     * @return bool - return true if setDisableChat status is true
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
