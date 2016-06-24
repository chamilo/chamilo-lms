<?php
/* For licensing terms, see /license.txt */

/**
 * Responses to AJAX calls
 */

$_dont_save_user_course_access  = true;

require_once '../global.inc.php';

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : null;

if (api_is_anonymous()) {
    exit;
}

// Course Chat
if ($action == 'preview') {
    echo CourseChatUtils::prepareMessage($_REQUEST['message']);
    exit;
}

if (api_get_setting('allow_global_chat') == 'false') {
    exit;
}

$to_user_id = isset($_REQUEST['to']) ? $_REQUEST['to'] : null;
$message	= isset($_REQUEST['message']) ? $_REQUEST['message'] : null;

if (!isset($_SESSION['chatHistory'])) {
    $_SESSION['chatHistory'] = array();
}

if (!isset($_SESSION['openChatBoxes'])) {
    $_SESSION['openChatBoxes'] = array();
}

$chat = new Chat();
if (chat::disableChat()){
    exit;
}
if ($chat->is_chat_blocked_by_exercises()) {
    // Disconnecting the user
    $chat->setUserStatus(0);
    exit;
}

switch ($action) {
    case 'chatheartbeat':
        $chat->heartbeat();
        break;
    case 'closechat':
        $chat->close();
        break;
    case 'sendchat':
        $chat->send(api_get_user_id(), $to_user_id, $message);
        break;
    case 'startchatsession':
        $chat->startSession();
        break;
    case 'set_status':
        $status = isset($_REQUEST['status']) ? intval($_REQUEST['status']) : 0;
        $chat->setUserStatus($status);
        break;
    case 'create_room':
        $room = VideoChat::getChatRoomByUsers(api_get_user_id(), $to_user_id);

        if ($room === false) {
            $createdRoom = VideoChat::createRoom(api_get_user_id(), $to_user_id);

            if ($createdRoom === false) {
                echo Display::return_message(get_lang('ChatRoomNotCreated'), 'error');
                break;
            }

            $room = VideoChat::getChatRoomByUsers(api_get_user_id(), $to_user_id);
        }

        $videoChatUrl = api_get_path(WEB_LIBRARY_JS_PATH) . "chat/video.php?room={$room['id']}";
        $videoChatLink = Display::url(
            Display::returnFontAwesomeIcon('video-camera') . get_lang('StartVideoChat'),
            $videoChatUrl
        );

        $chat->send(
            api_get_user_id(),
            $to_user_id,
            $videoChatLink,
            false,
            false
        );

        echo Display::tag('p', $videoChatLink, ['class' => 'lead']);
        break;
    case 'notify_not_support':
        $chat->send(api_get_user_id(), $to_user_id, get_lang('TheXUserBrowserDoesNotSupportWebRTC'));
        break;
    default:
        echo '';
}
exit;
