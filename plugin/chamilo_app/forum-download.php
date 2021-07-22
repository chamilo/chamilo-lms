<?php
require_once __DIR__ . '/../../main/inc/global.inc.php';
require_once 'webservices/WSApp.class.php';
require_once 'webservices/AppWebService.class.php';

use ChamiloSession as Session;

$username = isset($_GET['username']) ? Security::remove_XSS($_GET['username']) : null;
$apiKey = isset($_GET['api_key']) ? Security::remove_XSS($_GET['api_key']) : null;
$path = isset($_GET['path']) ? Security::remove_XSS($_GET['path']) : null;
//$user_id = isset($_GET['user_id']) ? Security::remove_XSS($_GET['user_id']) : null;
$c_id = isset($_GET['c_id']) ? Security::remove_XSS($_GET['c_id']) : null;
$s_id = isset($_GET['s_id']) ? Security::remove_XSS($_GET['s_id']) : 0;

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
	
	global $_configuration;
	$ruta = $_configuration['root_web'];
	if ((strripos($ruta, '/') + 1) != strlen($ruta)) {
	    $ruta = $ruta.'/';
	}
	//error_log($path);
	//echo $ruta.'main/forum/download.php?file='.$path;
	
	//header('Location:'.$ruta.'main/forum/download.php?file='.$path);
	//exit;
	
    $doc_url = $path;
    //change the '&' that got rewritten to '///' by mod_rewrite back to '&'
    $doc_url = str_replace('///', '&', $doc_url);
    //still a space present? it must be a '+' (that got replaced by mod_rewrite)
    $doc_url = str_replace(' ', '+', $doc_url);
    $doc_url = str_replace('/..', '', $doc_url);
    
    $tbl_forum_attachment = Database::get_course_table(TABLE_FORUM_ATTACHMENT);
    $tbl_forum_post = Database::get_course_table(TABLE_FORUM_POST);
    
    $course_id = api_get_course_int_id();
    $courseInfo = api_get_course_info_by_id($course_id);

    $sql = 'SELECT thread_id, forum_id,filename
        FROM '.$tbl_forum_post.'  f
        INNER JOIN '.$tbl_forum_attachment.' a
        ON a.post_id=f.post_id
        WHERE
            f.c_id = '.$course_id.' AND
            a.c_id = '.$course_id.' AND
            path LIKE BINARY "'.$doc_url.'"';
    
    $result = Database::query($sql);
    $row = Database::fetch_array($result);
    
    if (empty($row)) {
        api_not_allowed();
    }
    
    $forum_thread_visibility = api_get_item_visibility(
        $courseInfo,
        TOOL_FORUM_THREAD,
        $row['thread_id'],
        api_get_session_id()
    );
    $forum_forum_visibility = api_get_item_visibility(
        $courseInfo,
        TOOL_FORUM,
        $row['forum_id'],
        api_get_session_id()
    );
    
    if ($forum_thread_visibility == 1 && $forum_forum_visibility == 1) {
        $full_file_name = api_get_path(SYS_COURSE_PATH).api_get_course_path().'/upload/forum/'.$doc_url;
        if (Security::check_abs_path(
            $full_file_name,
            api_get_path(SYS_COURSE_PATH).$courseInfo['path'].'/upload/forum/'
        )) {
            // launch event
            Event::event_download($doc_url);
            
            $result = DocumentManager::file_send_for_download(
                    $full_file_name,
                    true,
                    $row['filename']
                    );
            
            if ($result === false) {
                error_log("mal3");
                api_not_allowed(true);
            }
        }
    }    
} else {
	error_log("Not valid apiKey");
}
