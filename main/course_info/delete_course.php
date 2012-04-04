<?php
/* For licensing terms, see /license.txt */
/**
 *	This script is about deleting a course.
 *	It displays a message box ('are you sure you wish to delete this course')
 *	and deletes the course if the user answers affirmatively
 *
 *	@package chamilo.course_info
 */
/**
 * Code
 */

// Language files that need to be included
$language_file = array('admin', 'course_info');

require_once '../inc/global.inc.php';
require_once '../gradebook/lib/be/gradebookitem.class.php';
require_once '../gradebook/lib/be/category.class.php';
$this_section = SECTION_COURSES;
$current_course_tool  = TOOL_COURSE_MAINTENANCE;
api_protect_course_script(true);

$current_course_code = $_course['official_code'];
$current_course_name = $_course['name'];

if (!api_is_allowed_to_edit()) {
	api_not_allowed(true);
}
$tool_name = get_lang('DelCourse');

if (isset($_GET['delete']) && $_GET['delete'] == 'yes') {
	CourseManager :: delete_course($_course['sysCode']);
	$obj_cat = new Category();
	$obj_cat->update_category_delete($_course['sysCode']);

	// DELETE CONFIRMATION MESSAGE
	unset($_course);
	unset($_cid);
	$noPHP_SELF = true;
	$message = '<h2>'.get_lang('Course').' : '.$current_course_name.' ('.$current_course_code.') </h2>';
    $message .=get_lang('HasDel');
	$message .= '<br /><br /><a href="../../index.php">'.get_lang('BackHome').' '.api_get_setting('siteName').'</a>';

} else {	
    $message = '<h3>'.get_lang('Course').' : '.$current_course_name.' ('.$current_course_code.') </h3>';
    $message .= '<p>'.get_lang('ByDel').'</p><p><a class="btn btn-primary" href="maintenance.php">'.get_lang('No').'</a>&nbsp;<a class="btn" href="'.api_get_self().'?delete=yes">'.get_lang('Yes').'</a></p>';
	$interbreadcrumb[] = array('url' => 'maintenance.php', 'name' => get_lang('Maintenance'));
}
Display :: display_header($tool_name, 'Settings');
echo Display::page_header($tool_name);
Display::display_warning_message($message, false);

/*	FOOTER */

Display :: display_footer();
