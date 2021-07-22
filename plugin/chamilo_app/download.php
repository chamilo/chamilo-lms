<?php
require_once __DIR__ . '/../../main/inc/global.inc.php';
require_once 'webservices/WSApp.class.php';
require_once 'webservices/AppWebService.class.php';

use ChamiloSession as Session;

//require_once __DIR__ . '/../../main/document/document.inc.php';

$username = isset($_GET['username']) ? Security::remove_XSS($_GET['username']) : null;
$apiKey = isset($_GET['api_key']) ? Security::remove_XSS($_GET['api_key']) : null;
$c_id = isset($_GET['c_id']) ? Security::remove_XSS($_GET['c_id']) : null;
$s_id = isset($_GET['s_id']) ? Security::remove_XSS($_GET['s_id']) : 0;
$document_id = isset($_GET['id']) ? Security::remove_XSS($_GET['id']) : null;

if (AppWebService::isValidApiKey($username, $apiKey)) {
    $courseInfo = api_get_course_info_by_id($c_id);	
	$user_id = UserManager::get_user_id_from_username($username);
	
	/* LOGIN */
	$chamiloUser = api_get_user_info($user_id);
	$_user['user_id'] = $chamiloUser['user_id'];
	$_user['status'] = (isset($chamiloUser['status']) ? $chamiloUser['status'] : 5);
	$_user['uidReset'] = true;
	Session::write('_user', $_user);
	$uidReset = true;
	$logging_in = true;
	//Event::event_login($_user['user_id']);
	Event::eventLogin($_user['user_id']);
	Login::init_user($user_id, true);
	Login::init_course($courseInfo['code'], true);
	if ($s_id > 0) {
		$_SESSION['id_session'] = $s_id;
	} else {
		Session::erase('session_name');
		Session::erase('id_session');
	}
	
	$course_dir = $courseInfo['directory'].'/document';
	$sys_course_path = api_get_path(SYS_COURSE_PATH);
	$base_work_dir = $sys_course_path.$course_dir;

	$document_data = DocumentManager::get_document_data_by_id(
		$document_id,
		$courseInfo['code'],
		false,
		$s_id
	);

	// Check whether the document is in the database
	if (empty($document_data)) {
		api_not_allowed();
	}
	// Launch event
	Event::event_download($document_data['url']);

	$full_file_name = $base_work_dir.$document_data['path'];

	if (Security::check_abs_path($full_file_name, $base_work_dir.'/')) {
		DocumentManager::file_send_for_download($full_file_name, true);
	}
	exit;

} else {
	error_log("Not valid apiKey");
}
       
