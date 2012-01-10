<?php
/* For licensing terms, see /license.txt */
/**
 * Responses to AJAX calls
 */

require_once '../global.inc.php';

require_once api_get_path(LIBRARY_PATH).'chat.lib.php';

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : null;

if (api_is_anonymous()) {
	exit;
}

$to_user_id = intval($_REQUEST['to']);
$message = $_REQUEST['message'];

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
		$chat->send(api_get_user_id(), $_POST['to'], $_POST['message']);
		break;		
	case 'startchatsession':
		$chat->start_session();
		break;		
	default:
        echo '';
	
}
exit;