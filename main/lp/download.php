<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * This file is responsible for  passing requested documents to the browser.
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

$doc_url = $_GET['doc_url'];
// Change the '&' that got rewritten to '///' by mod_rewrite back to '&'
$doc_url = str_replace('///', '&', $doc_url);
// Still a space present? it must be a '+' (that got replaced by mod_rewrite)
$doc_url = str_replace(' ', '+', $doc_url);

$doc_url = str_replace(['../', '\\..', '\\0', '..\\'], ['', '', '', ''], $doc_url); //echo $doc_url;

if (strpos($doc_url, '../') || strpos($doc_url, '/..')) {
    $doc_url = '';
}
$sys_course_path = api_get_path(SYS_COURSE_PATH).$_course['path'].'/scorm';
$user_id = api_get_user_id();
/** @var learnpath $lp */
$lp = Session::read('oLP');
if ($lp) {
    $lp_id = $lp->get_id();
    $lp_item_id = $lp->current;
    $lp_item_info = new learnpathItem($lp_item_id);
    if (!empty($lp_item_info)) {
        $visible = learnpath::is_lp_visible_for_student($lp_id, $user_id);

        if ($visible) {
            Event::event_download($doc_url);
            if (Security::check_abs_path($sys_course_path.$doc_url, $sys_course_path.'/')) {
                $full_file_name = $sys_course_path.$doc_url;
                DocumentManager::file_send_for_download($full_file_name);
                exit;
            }
        }
        //}
    }
}

echo Display::return_message(get_lang('ProtectedDocument'), 'error');
//api_not_allowed backbutton won't work.
exit;
