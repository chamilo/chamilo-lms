<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * This file is responsible for passing requested documents to the browser.
 *
 * @package chamilo.document
 */
session_cache_limiter('none');

require_once __DIR__.'/../inc/global.inc.php';
$this_section = SECTION_COURSES;

// Protection
api_protect_course_script();
$_course = api_get_course_info();

if (!isset($_course)) {
    api_not_allowed(true);
}

/** @var learnpath $obj */
$obj = Session::read('oLP');
// If LP obj exists
if (empty($obj)) {
    api_not_allowed();
}

// If is visible for the current user
if (!learnpath::is_lp_visible_for_student($obj->get_id(), api_get_user_id(), $_course)) {
    api_not_allowed();
}

$doc_url = isset($_GET['doc_url']) ? $_GET['doc_url'] : null;
// Change the '&' that got rewritten to '///' by mod_rewrite back to '&'
$doc_url = str_replace('///', '&', $doc_url);
// Still a space present? it must be a '+' (that got replaced by mod_rewrite)
$doc_url = str_replace(' ', '+', $doc_url);
$doc_url = str_replace(['../', '\\..', '\\0', '..\\'], ['', '', '', ''], $doc_url); //echo $doc_url;

if (strpos($doc_url, '../') || strpos($doc_url, '/..')) {
    $doc_url = '';
}

$sys_course_path = api_get_path(SYS_COURSE_PATH).$_course['path'].'/scorm';

if (is_dir($sys_course_path.$doc_url)) {
    api_not_allowed();
}

if (Security::check_abs_path($sys_course_path.$doc_url, $sys_course_path.'/')) {
    $full_file_name = $sys_course_path.$doc_url;
    // Launch event
    Event::event_download($doc_url);

    $fixLinks = api_get_configuration_value('lp_replace_http_to_https');
    $result = DocumentManager::file_send_for_download($full_file_name, false, '', $fixLinks);
    if ($result === false) {
        api_not_allowed(true, get_lang('FileNotFound'), 404);
    }
} else {
    api_not_allowed(true, get_lang('FileNotFound'), 404);
}
