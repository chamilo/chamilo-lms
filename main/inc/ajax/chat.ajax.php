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
    require_once api_get_path(SYS_CODE_PATH).'chat/chat_functions.lib.php';

    echo saveMessage(
        $_REQUEST['message'],
        api_get_user_id(),
        api_get_course_info(),
        api_get_session_id(),
        api_get_group_id(),
        true
    );
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
    case 'start_video':
        $room = VideoChat::getChatRoomByUsers(api_get_user_id(), $to_user_id);

        if ($room !== false) {
            $videoChatLink = Display::url(
                Display::tag(
                    'i',
                    null,
                    ['class' => 'fa fa-video-camera']
                ) . "&nbsp;" . get_lang('StartVideoChat'),
                api_get_path(WEB_LIBRARY_JS_PATH) . "chat/video.php?room={$room['room_name']}"
            );

            $chat->send(api_get_user_id(), $to_user_id, $videoChatLink, false);

            echo Display::tag('p', $videoChatLink, ['class' => 'lead']);
            break;
        }

        $form = new FormValidator('start_video_chat');
        $form->addText('chat_room_name', get_lang('ChatRoomName'), false);
        $form->addHidden('to', $to_user_id);
        $form->addButtonSend(get_lang('Create'));

        $template = new Template();
        $template->assign('form', $form->returnForm());

        echo $template->fetch('default/javascript/chat/start_video.tpl');
        break;
    case 'create_room':
        $room = VideoChat::getChatRoomByUsers(api_get_user_id(), $to_user_id);
        $createdRoom = false;

        if ($room === false) {
            $roomName = isset($_REQUEST['room_name']) ? Security::remove_XSS($_REQUEST['room_name']) : null;

            if (VideoChat::nameExists($roomName)) {
                echo Display::return_message(get_lang('TheVideoChatRoomNameAlreadyExists'), 'error');

                break;
            }

            $createdRoom = VideoChat::createRoom($roomName, api_get_user_id(), $to_user_id);
        } else {
            $roomName = $room['room_name'];
            $createdRoom = true;
        }

        if ($createdRoom === false) {
            echo Display::return_message(get_lang('ChatRoomNotCreated'), 'error');
            break;
        }

        $videoChatLink = Display::url(
            Display::tag(
                'i',
                null,
                ['class' => 'fa fa-video-camera']
            ) . "&nbsp;" . get_lang('StartVideoChat'),
            api_get_path(WEB_LIBRARY_JS_PATH) . "chat/video.php?room=$roomName"
        );

        $chat->send(api_get_user_id(), $to_user_id, $videoChatLink, false);

        echo Display::tag('p', $videoChatLink, ['class' => 'lead']);
        break;
    default:
        echo '';
}
exit;
