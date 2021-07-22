<?php
require_once __DIR__ . '/../../main/inc/global.inc.php';
require_once 'webservices/WSApp.class.php';
require_once 'webservices/AppWebService.class.php';

use ChamiloSession as Session;

//require_once __DIR__ . '/../../main/document/document.inc.php';

$username = isset($_GET['username']) ? Security::remove_XSS($_GET['username']) : null;
$apiKey = isset($_GET['api_key']) ? Security::remove_XSS($_GET['api_key']) : null;
$url = isset($_GET['url']) ? Security::remove_XSS($_GET['url']) : null;
$userId = isset($_GET['user_id']) ? (int) $_GET['user_id'] : 0;
$courseId = isset($_GET['course_id']) ? (int) $_GET['course_id'] : 0;
$sessionId = isset($_GET['session_id']) ? (int) $_GET['session_id'] : 0;

$url = str_replace('&amp;','&',$url);

if (AppWebService::isValidApiKey($username, $apiKey)) {
    /* LOGIN */
    $courseInfo = api_get_course_info_by_id($courseId);
    $platformUser = api_get_user_info($userId);
    $_user['user_id'] = $platformUser['user_id'];
    $_user['status'] = (isset($platformUser['status']) ? $platformUser['status'] : 5);
    $_user['uidReset'] = true;
    Session::write('_user', $_user);
    $uidReset = true;
    $logging_in = true;
    Login::init_user($userId, true);
    Login::init_course($courseInfo['code'], true);
    if ($sessionId > 0) {
        Session::write('id_session', $sessionId);
    } else {
        Session::erase('session_name');
        Session::erase('id_session');
    }

    require $includePath.'/local.inc.php';

	global $_configuration;
	$ruta = $_configuration['root_web'];
	if ((strripos($ruta, '/') + 1) != strlen($ruta)) {
	    $ruta = $ruta.'/';
	}
	$url_final = $ruta.'main/lp/lp_controller.php?'.$url;
	header('Location:'.$url_final);
} else {
	error_log("Not valid apiKey");
}
       
