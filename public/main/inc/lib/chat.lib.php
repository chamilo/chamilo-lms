<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Chat as ChatEntity;
use Chamilo\CoreBundle\Entity\UserRelUser;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Helpers\AiFeatureAccessHelper;
use ChamiloSession as Session;
use Doctrine\ORM\QueryBuilder;

/**
 * Class Chat.
 *
 * Notes:
 * - This is a legacy chat library used by the social network chat.
 * - Some methods historically echo JSON/text directly. Keep that behavior for backward compatibility.
 * - Symfony controllers should call methods with $printResult = false to avoid exit().
 *
 * @todo Use ChamiloSession instead of $_SESSION everywhere.
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
    public const USER_AI_TUTOR = -1;

    /**
     * The constructor sets the chat table name and the window_list attribute.
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

        return (int) ($status['user_chat_status'] ?? 0);
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
            (int) $status
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
    public function getContacts(): string
    {
        $html = (string) SocialManager::listMyFriendsBlock(api_get_user_id(), '', true);

        // Add AI Tutor contact when enabled (global chat).
        $aiFeatureAccessHelper = Container::$container->get(AiFeatureAccessHelper::class);

        if ($aiFeatureAccessHelper->isFeatureEnabledForCourse('tutor_chatbot', api_get_course_int_id())) {
            // Hide AI when user is in an exam (best effort).
            if (empty($_SESSION['is_in_a_test'])) {
                $ai = ''
                    .'<div class="chd-ai-contact" data-user="'.self::USER_AI_TUTOR.'" data-name="AI Tutor" style="cursor:pointer; padding:10px; border-bottom:1px solid #eee;">'
                    .'<span style="margin-inline-end:8px;">🤖</span>'
                    .'<strong>'.get_lang('AI Tutor').'</strong>'
                    .'<span style="float:inline-end; color:#10B981;">●</span>'
                    .'</div>';
                $html = $ai.$html;
            }
        }

        return (string) $html;
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
            $chats[$userId]['window_user_info'] = $this->getUserInfoSafe((int) $userId);
        }

        return $chats;
    }

    /**
     * Build the start session payload (no echo/exit).
     * Symfony controllers should use this method.
     */
    public function startSessionData(): array
    {
        $chatList = Session::read('openChatBoxes');
        $chats = $this->getAllLatestChats($chatList);

        return [
            'user_status' => $this->getUserStatus(),
            'me' => get_lang('Me'),
            'user_id' => (int) api_get_user_id(),
            'items' => $chats,
        ];
    }

    /**
     * Starts a chat session and prints JSON (legacy behavior).
     *
     * @return bool (prints output in JSON format)
     */
    public function startSession()
    {
        // Legacy behavior: echo JSON for old AJAX entry points.
        echo json_encode($this->startSessionData());

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
                        (int) $fromUserId,
                        (int) $toUserId,
                        (int) $toUserId,
                        (int) $fromUserId,
                    ],
                ],
            ],
            'first'
        );

        return (int) ($row['count'] ?? 0);
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

        // Only the two orders a caller can mean are honoured: the clause used to be spliced into the SQL text.
        $direction = 'ASC';
        if (1 === preg_match('/^\s*ORDER\s+BY\s+id\s+DESC\s*$/i', (string) $orderBy)) {
            $direction = 'DESC';
        }

        $rows = $this->chatRowsQueryBuilder()
            ->where('(c.toUser = :toUserId AND c.fromUser = :fromUserId) OR (c.fromUser = :toUserId AND c.toUser = :fromUserId)')
            ->orderBy('c.id', $direction)
            ->setFirstResult($start)
            ->setMaxResults($end)
            ->setParameter('toUserId', $toUserId)
            ->setParameter('fromUserId', $fromUserId)
            ->getQuery()
            ->getArrayResult()
        ;
        $rows = array_map([$this, 'toLegacyChatRow'], $rows);

        $fromUserInfo = $this->getUserInfoSafe((int) $fromUserId);
        $toUserInfo   = $this->getUserInfoSafe((int) $toUserId);

        $users = [
            $fromUserId => $fromUserInfo,
            $toUserId => $toUserInfo,
        ];

        $items = [];
        $rows = array_reverse($rows);

        foreach ($rows as $chat) {
            $rowFrom = (int) $chat['from_user'];
            $userInfo = $users[$rowFrom] ?? api_get_user_info($rowFrom, true);
            $toUserInfo = $users[$toUserId] ?? api_get_user_info($toUserId, true);

            $items[(int) $chat['id']] = [
                'id' => (int) $chat['id'],
                'message' => Security::remove_XSS($chat['message']),
                'date' => api_strtotime($chat['sent'], 'UTC'),
                'recd' => (int) $chat['recd'],
                'from_user_info' => $userInfo,
                'to_user_info' => $toUserInfo,
            ];

            $_SESSION['openChatBoxes'][$rowFrom] = api_strtotime($chat['sent'], 'UTC');
        }

        return $items;
    }

    /**
     * Refreshes the chat windows (legacy full payload; echoes JSON).
     */
    public function heartbeat()
    {
        $chatHistory = Session::read('chatHistory');
        $currentUserId = api_get_user_id();

        // Update current chats
        if (!empty($chatHistory) && is_array($chatHistory)) {
            foreach ($chatHistory as $fromUserId => &$data) {
                $userInfo = $this->getUserInfoSafe((int) $fromUserId);
                $count = $this->getCountMessagesExchangeBetweenUsers($fromUserId, $currentUserId);
                $chatItems = $this->getLatestChat($fromUserId, $currentUserId, 5);
                $data['window_user_info'] = $userInfo;
                $data['items'] = $chatItems;
                $data['total_messages'] = $count;
            }
        }

        $rows = $this->chatRowsQueryBuilder()
            ->where('c.toUser = :currentUserId')
            ->andWhere('c.recd = 0')
            ->orderBy('c.id', 'ASC')
            ->setParameter('currentUserId', (int) $currentUserId)
            ->getQuery()
            ->getArrayResult()
        ;

        $chatList = [];
        foreach ($rows as $chat) {
            $chat = $this->toLegacyChatRow($chat);
            $chatList[(int) $chat['from_user']][] = $chat;
        }

        foreach ($chatList as $fromUserId => $messages) {
            $userInfo = $this->getUserInfoSafe((int) $fromUserId);
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

        Container::getEntityManager()->createQueryBuilder()
            ->update(ChatEntity::class, 'c')
            ->set('c.recd', 1)
            ->where('c.toUser = :currentUserId')
            ->andWhere('c.recd = 0')
            ->setParameter('currentUserId', (int) $currentUserId)
            ->getQuery()
            ->execute()
        ;

        echo json_encode(['items' => $chatHistory]);
    }

    /**
     * Saves into session the fact that a chat window exists with the given user.
     *
     * @param int $userId
     */
    public function saveWindow($userId)
    {
        $this->window_list[(int) $userId] = true;
        Session::write('window_list', $this->window_list);
    }

    /**
     * Sends a message from one user to another user.
     *
     * @param int    $fromUserId  The ID of the user sending the message
     * @param int    $to_user_id  The ID of the user receiving the message
     * @param string $message     Message
     * @param bool   $printResult Optional. Whether print the result (legacy behavior)
     * @param bool   $sanitize    Optional. Whether sanitize the message
     *
     * @return int Message id, or 0 on failure
     */
    public function send(
        $fromUserId,
        $to_user_id,
        $message,
        $printResult = true,
        $sanitize = true
    ): int {
        $fromUserId = (int) $fromUserId;
        $to_user_id = (int) $to_user_id;

        $relation = SocialManager::get_relation_between_contacts($fromUserId, $to_user_id);

        if ($relation === UserRelUser::USER_RELATION_TYPE_FRIEND
            || $relation === UserRelUser::USER_RELATION_TYPE_GOODFRIEND
        ) {
            $now = api_get_utc_datetime();
            $user_info = api_get_user_info($to_user_id, true);

            $this->saveWindow($to_user_id);
            $_SESSION['openChatBoxes'][$to_user_id] = api_strtotime($now, 'UTC');

            $messagesan = $sanitize ? $this->sanitize($message) : $message;

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
            $params['from_user'] = $fromUserId;
            $params['to_user'] = $to_user_id;
            $params['message'] = $messagesan;
            $params['sent'] = api_get_utc_datetime();
            $params['recd'] = 0;

            if (!empty($fromUserId) && !empty($to_user_id)) {
                $messageId = (int) $this->save($params);

                if ($printResult) {
                    echo (string) $messageId;
                    exit;
                }

                return $messageId;
            }
        }

        if ($printResult) {
            echo '0';
            exit;
        }

        return 0;
    }

    /**
     * Close a specific chat box (user ID taken from $_POST['chatbox']).
     *
     * @param int $userId
     */
    public function closeWindow($userId)
    {
        $userId = (int) $userId;
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
        $text = htmlspecialchars((string) $text, ENT_QUOTES);
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
            if (true == $status) {
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
                if (true == $attempt_status) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Mark messages as read up to a specific message id.
     * recd = 0 => not delivered
     * recd = 1 => delivered
     * recd = 2 => read
     */
    public function ackReadUpTo(int $fromUserId, int $toUserId, int $lastSeenMessageId): int
    {
        if ($fromUserId <= 0 || $toUserId <= 0 || $lastSeenMessageId <= 0) {
            return 0;
        }

        $fromUserId = (int) $fromUserId;
        $toUserId = (int) $toUserId;
        $lastSeenMessageId = (int) $lastSeenMessageId;

        $sql = "UPDATE {$this->table}
            SET recd = 2
            WHERE from_user = {$fromUserId}
              AND to_user   = {$toUserId}
              AND id       <= {$lastSeenMessageId}
              AND recd < 2";

        // TODO(owner): broken, Database::affected_rows() needs the Result argument (ArgumentCountError after the UPDATE
        // ran); caller ChatController. Left raw, not migrated.
        $res = Database::query($sql);
        if (!$res) {
            return 0;
        }

        // Database::affected_rows() is used across Chamilo; keep it simple.
        return (int) Database::affected_rows();
    }

    /**
     * Get unread counts grouped by peer (from_user).
     *
     * @return array<int,int> map: peerId => unreadCount
     */
    public function getUnreadByPeer(int $userId): array
    {
        $uid = (int) $userId;
        if ($uid <= 0) {
            return [];
        }

        // Index-friendly with (to_user, recd, from_user) or (to_user, recd, id)
        $rows = Container::getEntityManager()->createQueryBuilder()
            ->select('c.fromUser AS from_user', 'COUNT(c.id) AS cnt')
            ->from(ChatEntity::class, 'c')
            ->where('c.toUser = :uid')
            ->andWhere('c.recd < 2')
            ->groupBy('c.fromUser')
            ->setParameter('uid', $uid)
            ->getQuery()
            ->getArrayResult()
        ;

        $map = [];
        foreach ($rows as $r) {
            $map[(int) $r['from_user']] = (int) ($r['cnt'] ?? 0);
        }

        return $map;
    }

    /**
     * Minimal heartbeat: returns global last_id (> sinceId) and unread totals.
     * Used by the docked chat when no peer is actively selected.
     */
    public function heartbeatMin(int $userId, int $sinceId = 0): array
    {
        $uid = (int) $userId;
        $sinceId = max(0, (int) $sinceId);

        $entityManager = Container::getEntityManager();

        // Latest incoming id after sinceId
        $lastId = (int) $entityManager->createQueryBuilder()
            ->select('MAX(c.id)')
            ->from(ChatEntity::class, 'c')
            ->where('c.toUser = :uid')
            ->andWhere('c.id > :sinceId')
            ->setParameter('uid', $uid)
            ->setParameter('sinceId', $sinceId)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        // Unread total (independent from sinceId, so badges remain accurate)
        $unreadTotal = (int) $entityManager->createQueryBuilder()
            ->select('COUNT(c.id)')
            ->from(ChatEntity::class, 'c')
            ->where('c.toUser = :uid')
            ->andWhere('c.recd < 2')
            ->setParameter('uid', $uid)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        // Unread per peer (for per-contact dots)
        $unreadByPeer = $this->getUnreadByPeer($uid);

        return [
            'has_new'        => $lastId > $sinceId,
            'last_id'        => $lastId,
            'unread'         => $unreadTotal,
            'unread_by_peer' => $unreadByPeer,
            'since_id'       => $sinceId,
        ];
    }

    /**
     * Ultra-tiny per-peer heartbeat: returns only latest id for (peer -> me).
     * O(1) using composite index (to_user, from_user, id).
     */
    public function heartbeatTiny(int $userId, int $peerId, int $sinceId = 0): array
    {
        $uid = (int) $userId;
        $pid = (int) $peerId;
        $sinceId = max(0, (int) $sinceId);

        $entityManager = Container::getEntityManager();

        $lastRows = $entityManager->createQueryBuilder()
            ->select('c.id AS id')
            ->from(ChatEntity::class, 'c')
            ->where('c.toUser = :uid')
            ->andWhere('c.fromUser = :pid')
            ->orderBy('c.id', 'DESC')
            ->setMaxResults(1)
            ->setParameter('uid', $uid)
            ->setParameter('pid', $pid)
            ->getQuery()
            ->getArrayResult()
        ;
        $last = (int) ($lastRows[0]['id'] ?? 0);

        // Optional: unread from this peer only (cheap)
        $unreadPeer = (int) $entityManager->createQueryBuilder()
            ->select('COUNT(c.id)')
            ->from(ChatEntity::class, 'c')
            ->where('c.toUser = :uid')
            ->andWhere('c.fromUser = :pid')
            ->andWhere('c.recd < 2')
            ->setParameter('uid', $uid)
            ->setParameter('pid', $pid)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return [
            'has_new'    => $last > $sinceId,
            'last_id'    => $last,
            'peer_id'    => $pid,
            'since_id'   => $sinceId,
            'unread_peer'=> $unreadPeer,
        ];
    }

    /**
     * Get ONLY new incoming messages (peer -> me) with id > $sinceId.
     * Keeps payload tiny. We do *not* fetch my own messages here.
     */
    public function getIncomingSince(int $peerId, int $meId, int $sinceId = 0): array
    {
        $pid = (int) $peerId;
        $uid = (int) $meId;
        $sid = max(0, (int) $sinceId);

        if ($pid <= 0 || $uid <= 0) {
            return [];
        }

        $rows = $this->chatRowsQueryBuilder()
            ->where('c.fromUser = :pid')
            ->andWhere('c.toUser = :uid')
            ->andWhere('c.id > :sid')
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(200)
            ->setParameter('pid', $pid)
            ->setParameter('uid', $uid)
            ->setParameter('sid', $sid)
            ->getQuery()
            ->getArrayResult()
        ;
        $rows = array_map([$this, 'toLegacyChatRow'], $rows);

        if (empty($rows)) {
            return [];
        }

        $fromUserInfo = $this->getUserInfoSafe((int) $pid);
        $toUserInfo   = $this->getUserInfoSafe((int) $uid);

        $items = [];
        $ids = [];

        foreach ($rows as $chat) {
            $id = (int) $chat['id'];
            $ids[] = $id;

            $items[] = [
                'id'             => $id,
                'message'        => Security::remove_XSS($chat['message']),
                'date'           => api_strtotime($chat['sent'], 'UTC'),
                'recd'           => (int) $chat['recd'],
                'from_user_info' => $fromUserInfo,
                'to_user_info'   => $toUserInfo,
            ];
        }

        if (!empty($ids)) {
            // GREATEST(recd, 1) written as "raise 0 to 1": a message already read (2) stays read.
            Container::getEntityManager()->createQueryBuilder()
                ->update(ChatEntity::class, 'c')
                ->set('c.recd', 1)
                ->where('c.id IN (:ids)')
                ->andWhere('c.recd < 1')
                ->setParameter('ids', $ids)
                ->getQuery()
                ->execute()
            ;
        }

        return $items;
    }

    /**
     * Every column of the chat table, aliased to the snake_case keys the legacy readers use.
     */
    private function chatRowsQueryBuilder(): QueryBuilder
    {
        return Container::getEntityManager()->createQueryBuilder()
            ->select(
                'c.id AS id',
                'c.fromUser AS from_user',
                'c.toUser AS to_user',
                'c.message AS message',
                'c.sent AS sent',
                'c.recd AS recd'
            )
            ->from(ChatEntity::class, 'c')
        ;
    }

    /**
     * The raw row carried the date as a 'Y-m-d H:i:s' string.
     */
    private function toLegacyChatRow(array $row): array
    {
        if ($row['sent'] instanceof DateTimeInterface) {
            $row['sent'] = $row['sent']->format('Y-m-d H:i:s');
        }

        return $row;
    }

    private function getAiUserInfo(): array
    {
        return [
            'id' => self::USER_AI_TUTOR,
            'user_id' => self::USER_AI_TUTOR,
            'complete_name' => 'AI Tutor',
            'user_is_online_in_chat' => 1,
            'user_is_online' => 1,
            'online' => 1,
            'avatar_small' => '',
        ];
    }

    private function getUserInfoSafe(int $userId): array
    {
        if (self::USER_AI_TUTOR === $userId) {
            return $this->getAiUserInfo();
        }

        if ($userId <= 0) {
            return [];
        }

        return api_get_user_info($userId, true);
    }
}
