<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 *	This script is about deleting a course.
 *	It displays a message box ('are you sure you wish to delete this course')
 *	and deletes the course if the user answers affirmatively
 *
 *	@package chamilo.course_info
 */

require_once '../inc/global.inc.php';
$this_section = SECTION_COURSES;
$current_course_tool = TOOL_COURSE_MAINTENANCE;
api_protect_course_script(true);

$_course = api_get_course_info();
$current_course_code = $_course['official_code'];
$current_course_name = $_course['name'];

if (!api_is_allowed_to_edit()) {
    api_not_allowed(true);
}

$tool_name = get_lang('DelCourse');

if (isset($_GET['delete']) && $_GET['delete'] === 'yes') {
    CourseManager::delete_course($_course['sysCode']);
    $obj_cat = new Category();
    $obj_cat->update_category_delete($_course['sysCode']);

    // DELETE CONFIRMATION MESSAGE
    Session::erase('_cid');
    Session::erase('_real_cid');
    $noPHP_SELF = true;
    $message = '<h2>'.get_lang('Course').' : '.$current_course_name.' ('.$current_course_code.') </h2>';
    $message .=get_lang('HasDel');
    $message .= '<br /><br /><a href="../../index.php">'.get_lang('BackHome').'</a>';
} else {
    $message = '<h3>'.get_lang('Course').' : '.$current_course_name.' ('.$current_course_code.') </h3>';
    $message .= '<p>'.get_lang('ByDel').'</p>';
    $message .= '<p><a class="btn btn-primary" href="'.api_get_path(WEB_CODE_PATH).'course_info/maintenance.php?'.api_get_cidreq().'">'.
        get_lang('No').'</a>&nbsp;<a class="btn btn-danger" href="'.api_get_self().'?delete=yes&'.api_get_cidreq().'">'.
        get_lang('Yes').'</a></p>';
	$interbreadcrumb[] = array('url' => 'maintenance.php', 'name' => get_lang('Maintenance'));
}

$tpl = new Template($tool_name);

$tpl->assign('content', Display::return_message($message, 'warning', false));
$tpl->display_one_col_template();

