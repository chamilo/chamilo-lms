<?php
/* For licensing terms, see /license.txt */
/**
 * Responses to AJAX calls
 */

$_dont_save_user_course_access  = true;

require_once '../global.inc.php';

require_once api_get_path(LIBRARY_PATH).'chat.lib.php';

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : null;

if (api_is_anonymous()) {
	exit;
}

if (api_get_setting('allow_global_chat') == 'false') {
	exit;	
}

$to_user_id = $_REQUEST['to'];
$message	= $_REQUEST['message'];

if (!isset($_SESSION['chatHistory'])) {
	$_SESSION['chatHistory'] = array();	
}

if (!isset($_SESSION['openChatBoxes'])) {
	$_SESSION['openChatBoxes'] = array();	
}
	
$chat = new Chat();

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
	default:
        echo '';
	
}
exit;