<?php
/* For licensing terms, see /license.txt */
/**
 * @author jmontoya
 *
 * @package chamilo.document
 */
require_once __DIR__.'/../inc/global.inc.php';

// Protection
api_protect_course_script(true);

$header_file = isset($_GET['file']) ? Security::remove_XSS($_GET['file']) : null;
$document_id = intval($_GET['id']);

$courseId = api_get_course_int_id();
$course_info = api_get_course_info_by_id($courseId);
$course_code = $course_info['code'];
$session_id = api_get_session_id();

if (empty($course_info)) {
    api_not_allowed(true);
}

// Generate path
if (!$document_id) {
    $document_id = DocumentManager::get_document_id($course_info, $header_file);
}
$document_data = DocumentManager::get_document_data_by_id(
    $document_id,
    $course_code,
    true,
    $session_id
);

if ($session_id != 0 && !$document_data) {
    $document_data = DocumentManager::get_document_data_by_id(
        $document_id,
        $course_code,
        true,
        0
    );
}
if (empty($document_data)) {
    api_not_allowed(true);
}

$header_file = $document_data['path'];
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
$is_allowed_in_course = api_is_allowed_in_course();

if ($is_allowed_in_course == false) {
    api_not_allowed(true);
}

// Check user visibility
$is_visible = DocumentManager::check_visibility_tree(
    $document_id,
    api_get_course_info(),
    api_get_session_id(),
    api_get_user_id(),
    api_get_group_id()
);

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
$file_url_web = api_get_path(WEB_COURSE_PATH).$course_info['path'].'/document'.$header_file.'?'.api_get_cidreq();
$pathinfo = pathinfo($header_file);

if ($pathinfo['extension'] == 'swf') {
    $width = '83%';
    $height = '83%';
} else {
    $width = '100%';
    $height = '100%';
}

echo '<iframe border="0" frameborder="0" scrolling="no" style="width:'.$width.'; height:'.$height.';background-color:#ffffff;" id="mainFrame" name="mainFrame" src="'.$file_url_web.'?'.api_get_cidreq().'&amp;rand='.mt_rand(1, 1000).'"></iframe>';
