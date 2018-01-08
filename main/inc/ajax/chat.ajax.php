<?php
/* For licensing terms, see /license.txt */

/**
 * Responses to AJAX calls
 */

$_dont_save_user_course_access = true;

require_once __DIR__.'/../global.inc.php';

if (api_get_setting('allow_global_chat') == 'false') {
    exit;
}

if (api_is_anonymous()) {
    exit;
}
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : null;

// Course Chat
if ($action == 'preview') {
    echo CourseChatUtils::prepareMessage($_REQUEST['message']);
    exit;
}

$toUserId = isset($_REQUEST['to']) ? $_REQUEST['to'] : null;
$message = isset($_REQUEST['message']) ? $_REQUEST['message'] : null;

if (!isset($_SESSION['chatHistory'])) {
    $_SESSION['chatHistory'] = [];
}

if (!isset($_SESSION['openChatBoxes'])) {
    $_SESSION['openChatBoxes'] = [];
}

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
    case 'chatheartbeat':
        $chat->heartbeat();
        break;
    case 'closechat':
        $chat->close();
        break;
    case 'sendchat':
        $chat->send(api_get_user_id(), $toUserId, $message);
        break;
    case 'startchatsession':
        $chat->startSession();
        break;
    case 'get_previous_messages':
        $userId = isset($_REQUEST['user_id']) ? $_REQUEST['user_id'] : null;
        $visibleMessages = isset($_REQUEST['visible_messages']) ? $_REQUEST['visible_messages'] : null;
        if (empty($userId)) {
            return '';
        }
        $items = $chat->getPreviousMessages(
            $userId,
            api_get_user_id(),
            $visibleMessages
        );
        echo json_encode($items);
        exit;
        break;
    case 'set_status':
        $status = isset($_REQUEST['status']) ? intval($_REQUEST['status']) : 0;
        $chat->setUserStatus($status);
        break;
    case 'create_room':
        $room = VideoChat::getChatRoomByUsers(api_get_user_id(), $toUserId);

        if ($room === false) {
            $createdRoom = VideoChat::createRoom(api_get_user_id(), $toUserId);

            if ($createdRoom === false) {
                echo Display::return_message(
                    get_lang('ChatRoomNotCreated'),
                    'error'
                );
                break;
            }

            $room = VideoChat::getChatRoomByUsers(api_get_user_id(), $toUserId);
        }

        $videoChatUrl = api_get_path(WEB_LIBRARY_JS_PATH)."chat/video.php?room={$room['id']}";
        $videoChatLink = Display::url(
            Display::returnFontAwesomeIcon('video-camera').get_lang('StartVideoChat'),
            $videoChatUrl
        );

        $chat->send(
            api_get_user_id(),
            $toUserId,
            $videoChatLink,
            false,
            false
        );

        echo Display::tag('p', $videoChatLink, ['class' => 'lead']);
        break;
    case 'notify_not_support':
        $chat->send(
            api_get_user_id(),
            $toUserId,
            get_lang('TheXUserBrowserDoesNotSupportWebRTC')
        );
        break;
    default:
        echo '';
}
exit;
