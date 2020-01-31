<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * This script is about deleting a course.
 * It displays a message box ('are you sure you wish to delete this course')
 * and deletes the course if the user answers affirmatively.
 */
require_once __DIR__.'/../inc/global.inc.php';
$this_section = SECTION_COURSES;
$current_course_tool = TOOL_COURSE_MAINTENANCE;

api_protect_course_script(true);

$_course = api_get_course_info();
$current_course_code = $_course['official_code'];
$current_course_name = $_course['name'];

if (!api_is_allowed_to_edit()) {
    api_not_allowed(true);
}

$tool_name = get_lang('Completely delete this course');

if (isset($_GET['delete']) && 'yes' === $_GET['delete']) {
    CourseManager::delete_course($_course['sysCode']);

    // DELETE CONFIRMATION MESSAGE
    Session::erase('_cid');
    Session::erase('_real_cid');
    $message = '<h2>'.get_lang('Course').' : '.$current_course_name.' ('.$current_course_code.') </h2>';
    $message .= get_lang('has been deleted');
    $message .= '<br /><br /><a href="../../index.php">'.get_lang('Back to Home Page.').'</a>';
} else {
    $message = '<h3>'.get_lang('Course').' : '.$current_course_name.' ('.$current_course_code.') </h3>';
    $message .= '<p>'.get_lang('Deleting this area will permanently delete all the content (documents, links...) it contains and unregister all its members (not remove them from other courses). <p>Do you really want to delete the course?').'</p>';
    $message .= '<p><a class="btn btn-primary" 
        href="'.api_get_path(WEB_CODE_PATH).'course_info/maintenance.php?'.api_get_cidreq().'">'.
        get_lang('No').'</a>&nbsp;<a class="btn btn-danger" href="'.api_get_self().'?delete=yes&'.api_get_cidreq().'">'.
        get_lang('Yes').'</a></p>';
    $interbreadcrumb[] = [
        'url' => 'maintenance.php',
        'name' => get_lang('Backup'),
    ];
}

$tpl = new Template($tool_name);
$tpl->assign('content', Display::return_message($message, 'warning', false));
$tpl->display_one_col_template();
