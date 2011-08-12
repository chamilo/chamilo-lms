<?php
/* For licensing terms, see /license.txt */
/**
 *	@author jmontoya
 *  
 *	@package chamilo.document
 */
/**
 * Code
 */
/*   INITIALIZATION */

$language_file[] = 'document';
require_once '../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'document.lib.php';
require_once api_get_path(LIBRARY_PATH).'glossary.lib.php';
require_once api_get_path(LIBRARY_PATH).'groupmanager.lib.php';

// Protection
api_protect_course_script();

$noPHP_SELF = true;
$header_file = Security::remove_XSS($_GET['file']);
$document_id = intval($_GET['id']);

$course_info = api_get_course_info();
$course_code = api_get_course_id(); 

if (empty($course_info)) {
    api_not_allowed(true);
}

//Generate path 
if (!$document_id) {
    $document_id = DocumentManager::get_document_id($course_info, $header_file);
}
$document_data = DocumentManager::get_document_data_by_id($document_id, $course_code);

if (empty($document_data)) {
    api_not_allowed(true);
}

$header_file  = $document_data['path'];
$name_to_show = cut($header_file, 80);

$path_array = explode('/', str_replace('\\', '/', $header_file));
$path_array = array_map('urldecode', $path_array);
$header_file = implode('/', $path_array);

$file = Security::remove_XSS(urldecode($document_data['path']));

$file_root = $course_info['path'].'/document'.str_replace('%2F', '/', $file);
$file_url_sys = api_get_path(SYS_COURSE_PATH).$file_root;
$file_url_web = api_get_path(WEB_COURSE_PATH).$file_root;

if (!file_exists($file_url_sys)) {
    api_not_allowed(true);
}

if (is_dir($file_url_sys)) {
    api_not_allowed(true);
}

//fix the screen when you try to access a protected course through the url
$is_allowed_in_course = $_SESSION ['is_allowed_in_course'];

if ($is_allowed_in_course == false) {
    api_not_allowed(true);
}

//Check user visibility
//$is_visible = DocumentManager::is_visible_by_id($document_id, $course_info, api_get_session_id(), api_get_user_id());
$is_visible = DocumentManager::check_visibility_tree($document_id, api_get_course_id(), api_get_session_id(), api_get_user_id());
if (!api_is_allowed_to_edit() && !$is_visible) {
    api_not_allowed(true);
}

//TODO:clean all code

/*	Main section */
header('Expires: Wed, 01 Jan 1990 00:00:00 GMT');
//header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
header('Last-Modified: Wed, 01 Jan 2100 00:00:00 GMT');
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');
$browser_display_title = 'Documents - '.Security::remove_XSS($_GET['cidReq']).' - '.$file;

$file_url_web = api_get_path(WEB_COURSE_PATH).$_course['path'].'/document'.$header_file.'?'.api_get_cidreq();

$pathinfo = pathinfo($header_file);
if ($pathinfo['extension']=='wav' && api_get_setting('enable_nanogong') == 'true'){
	echo '<div align="center">';
		echo '<br/>';
		echo '<applet id="applet" archive="../inc/lib/nanogong/nanogong.jar" code="gong.NanoGong" width="160" height="40" >';
			echo '<param name="SoundFileURL" value="'.$file_url_web.'" />';
			echo '<param name="ShowSaveButton" value="false" />';
			echo '<param name="ShowTime" value="true" />';
			echo '<param name="ShowRecordButton" value="false" />';
		echo '</applet>';
	echo '</div>';
} else {
	if ($pathinfo['extension']=='swf') { $width='83%'; $height='83%';} else {$width='100%'; $height='100%';}	
	echo '<iframe border="0" frameborder="0" scrolling="no" style="width:'.$width.'; height:'.$height.';background-color:#ffffff;" id="mainFrame" name="mainFrame" src="'.$file_url_web.'?'.api_get_cidreq().'&amp;rand='.mt_rand(1, 10000).'"></iframe>';
}