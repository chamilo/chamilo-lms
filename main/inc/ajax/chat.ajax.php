<?php
/* For licensing terms, see /license.txt */
/**
 * Responses to AJAX calls
 */

$_dont_save_user_course_access  = true;

$action = isset($_GET['action']) ? $_GET['action'] : null;

if (api_is_anonymous()) {
	exit;
}

if (api_get_setting('allow_global_chat') == 'false') {
	exit;
}

$to_user_id = isset($_GET['to']) ? $_GET['to'] : null;
$message	= isset($_GET['message']) ? $_GET['message'] : null;

if (!isset($_SESSION['chatHistory'])) {
	$_SESSION['chatHistory'] = array();
}

if (!isset($_SESSION['openChatBoxes'])) {
	$_SESSION['openChatBoxes'] = array();
}

$chat = new Chat();
if ($chat->is_chat_blocked_by_exercises()) {
    // Disconnect the user
    $chat->set_user_status(0);
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
        $chat->start_session();
        break;
    case 'set_status':
        $status = isset($_REQUEST['status']) ? intval($_REQUEST['status']) : 0;
        $chat->set_user_status($status);
        break;
    default:
        echo '';
}
exit;
