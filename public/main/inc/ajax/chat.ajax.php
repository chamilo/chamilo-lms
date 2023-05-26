<?php
/* For licensing terms, see /license.txt */

/**
 * Responses to AJAX calls.
 */
$_dont_save_user_course_access = true;

require_once __DIR__.'/../global.inc.php';

api_block_anonymous_users();

if ('false' == api_get_setting('allow_global_chat')) {
    exit;
}

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

// Course Chat
if ('preview' === $action) {
    echo CourseChatUtils::prepareMessage($_REQUEST['message']);
    exit;
}

$toUserId = isset($_REQUEST['to']) ? $_REQUEST['to'] : null;
$message = isset($_REQUEST['message']) ? $_REQUEST['message'] : null;
$currentUserId = api_get_user_id();

$chat = new Chat();

if (Chat::disableChat()) {
    exit;
}

if ($chat->isChatBlockedByExercises()) {
    // Disconnecting the user
    $chat->setUserStatus(0);
    exit;
}

switch ($action) {
    case 'get_message_status':
        $messageId = isset($_REQUEST['message_id']) ? $_REQUEST['message_id'] : 0;
        $messageInfo = $chat->get($messageId);
        if ($messageInfo && $messageInfo['from_user'] == $currentUserId) {
            echo json_encode($messageInfo);
        }
        break;
    case 'chatheartbeat':
        $chat->heartbeat();
        break;
    case 'close_window':
        // Closes friend window
        $chatId = isset($_POST['chatbox']) ? $_POST['chatbox'] : '';
        $chat->closeWindow($chatId);
        echo '1';
        exit;
        break;
    case 'close':
        // Disconnects user from all chat
        $chat->close();

        echo '1';
        exit;
        break;
    case 'create_room':
        if ('true' === api_get_setting('chat.hide_chat_video')) {
            api_not_allowed();
        }
        /*$room = VideoChat::getChatRoomByUsers(api_get_user_id(), $toUserId);

        if ($room === false) {
            $createdRoom = VideoChat::createRoom(api_get_user_id(), $toUserId);

            if ($createdRoom === false) {
                echo Display::return_message(
                    get_lang('Chatroom could not be created'),
                    'error'
                );
                break;
            }

            $room = VideoChat::getChatRoomByUsers(api_get_user_id(), $toUserId);
        }

        $videoChatUrl = api_get_path(WEB_LIBRARY_JS_PATH)."chat/video.php?room={$room['id']}";
        $videoChatLink = Display::url(
            Display::returnFontAwesomeIcon('video-camera').get_lang('Start video call'),
            $videoChatUrl
        );

        $chat->send(
            api_get_user_id(),
            $toUserId,
            $videoChatLink,
            false,
            false
        );
        echo Display::tag('p', $videoChatLink, ['class' => 'lead']);*/
        break;
    case 'get_contacts':
        echo $chat->getContacts();
        break;
    case 'get_previous_messages':
        $userId = isset($_REQUEST['user_id']) ? $_REQUEST['user_id'] : 0;
        $visibleMessages = isset($_REQUEST['visible_messages']) ? $_REQUEST['visible_messages'] : 0;
        if (empty($userId)) {
            return '';
        }

        $items = $chat->getPreviousMessages(
            $userId,
            $currentUserId,
            $visibleMessages
        );

        if (!empty($items)) {
            sort($items);
            echo json_encode($items);
            exit;
        }
        echo json_encode([]);
        exit;
        break;
    case 'notify_not_support':
        $chat->send(
            $currentUserId,
            $toUserId,
            get_lang('The browser of %s does not support native video transmission. Sorry.')
        );
        break;
    case 'sendchat':
        $chat->send($currentUserId, $toUserId, $message);
        break;
    case 'startchatsession':
        $chat->startSession();
        break;
    case 'set_status':
        $status = isset($_REQUEST['status']) ? (int) $_REQUEST['status'] : 0;
        $chat->setUserStatus($status);
        break;
    default:
        echo '';
}
exit;
