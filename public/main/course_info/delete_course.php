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
$type_info_message = 'warning';
if (isset($_GET['delete']) && 'yes' === $_GET['delete'] && $_GET['course_code'] && !empty($_GET['course_code'])) {
    if ($current_course_code == $_GET['course_code']) {
        if (!CourseManager::delete_course($_course['sysCode'])) {
            // DELETE ERROR MESSAGE
            $message = '<h3>'.get_lang('Course title').' : '.$current_course_name.'</h3>';
            $message .= '<h3>'.get_lang('Course code').' : '.$current_course_code.'</h3>';
            $message .= '<p>'.get_lang('An error occurred while trying to delete the course').'</p>';
            $type_info_message = 'error';
        } else {
            // DELETE CONFIRMATION MESSAGE
            Session::erase('_cid');
            Session::erase('_real_cid');
            $message = '<h3>'.get_lang('Course title').' : '.$current_course_name.'</h3>';
            $message .= '<h3>'.get_lang('Course code').' : '.$current_course_code.'</h3>';
            $message .= get_lang('has been deleted');
        }
        $message .= '<br /><br /><a href="../../index.php">'.get_lang('Back to Home Page.').'</a>';
    } else {
        /* message if code course is incorrect */
        $message = '<h3>'.get_lang('Course title').' : '.$current_course_name.'</h3>';
        $message .= '<h3>'.get_lang('Course code').' : '.$current_course_code.'</h3>';
        $message .= '<p>'.get_lang('Course registration code incorrect').'</p>';
        $message .= '<p><a class="btn btn--primary" href="'
            .api_get_path(WEB_CODE_PATH)
            .'course_info/delete_course.php?'
            .api_get_cidreq()
            .'">'.get_lang('Back to previous page').'</a>';
        $message .= '<br /><br /><a href="../../index.php">'.get_lang('Back to Home Page.').'</a>';
        $type_info_message = 'error';
    }
} else {
    $message = '<h3>'.get_lang('Course title').' : '.$current_course_name.'</h3>';
    $message .= '<h3>'.get_lang('Course code').' : '.$current_course_code.'</h3>';
    $message .= '<p>'.get_lang('Deleting this area will permanently delete all the content (documents, links...) it contains and unregister all its members (not remove them from other courses). <br>Do you really want to delete the course?').'</p>';
    $message .= '<p><span class="form_required">*</span>'
        .get_lang('Course code confirmation')
        .'&nbsp;<input type="text" name="course_code" id="course_code"></p>';

    $message .= '<p>';
    $message .= '<button class="btn btn--danger delete-course">'.get_lang('Validate changes').'</button>';
    $message .= '&nbsp;';
    $message .= '<a class="btn btn--primary"href="'
        .api_get_path(WEB_CODE_PATH)
        .'course_info/maintenance.php?'
        .api_get_cidreq().'">'
        .get_lang('No')
        .'</a>';
    $message .= '</p>';
    $interbreadcrumb[] = [
        'url' => 'maintenance.php',
        'name' => get_lang('Course maintenance'),
    ];
}
    $htmlHeadXtra[] = '<script>
$(function(){
	/* Asking by course code to confirm recycling*/
	$(".delete-course").on("click",function(){
		window.location ="'.api_get_self().'?delete=yes&'.api_get_cidreq().'&course_code=" + $("#course_code").val();
	})
})

</script>';
$tpl = new Template($tool_name);
$tpl->assign('content', Display::return_message($message, $type_info_message, false));
$tpl->display_one_col_template();
