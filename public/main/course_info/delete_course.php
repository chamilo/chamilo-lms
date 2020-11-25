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

$tool_name = get_lang('DelCourse');
$type_info_message = 'warning';
if (isset($_GET['delete']) && $_GET['delete'] === 'yes' && $_GET['course_code'] && !empty($_GET['course_code'])) {
    if ($current_course_code == $_GET['course_code']) {
        CourseManager::delete_course($_course['sysCode']);
        // DELETE CONFIRMATION MESSAGE
        Session::erase('_cid');
        Session::erase('_real_cid');
        $message = '<h3>'.get_lang('CourseTitle').' : '.$current_course_name.'</h3>';
        $message .= '<h3>'.get_lang('CourseCode').' : '.$current_course_code.'</h3>';
        $message .= get_lang('HasDel');
        $message .= '<br /><br /><a href="../../index.php">'.get_lang('BackHome').'</a>';
    } else {
        /* message if code course is incorrect */
        $message = '<h3>'.get_lang('CourseTitle').' : '.$current_course_name.'</h3>';
        $message .= '<h3>'.get_lang('CourseCode').' : '.$current_course_code.'</h3>';
        $message .= '<p>'.get_lang('CourseRegistrationCodeIncorrect').'</p>';
        $message .= '<p><a class="btn btn-primary" href="'
            .api_get_path(WEB_CODE_PATH)
            .'course_info/delete_course.php?'
            .api_get_cidreq()
            .'">'.get_lang('BackToPreviousPage').'</a>';
        $message .= '<br /><br /><a href="../../index.php">'.get_lang('BackHome').'</a>';
        $type_info_message = 'error';
    }
} else {
    $message = '<h3>'.get_lang('CourseTitle').' : '.$current_course_name.'</h3>';
    $message .= '<h3>'.get_lang('CourseCode').' : '.$current_course_code.'</h3>';
    $message .= '<p>'.get_lang('ByDel').'</p>';
    $message .= '<p><span class="form_required">*</span>'
        .get_lang('CourseCodeConfirmation')
        .'&nbsp;<input type="text" name="course_code" id="course_code"></p>';

    $message .= '<p>';
    $message .= '<button class="btn btn-danger delete-course">'.get_lang('ValidateChanges').'</button>';
    $message .= '&nbsp;';
    $message .= '<a class="btn btn-primary"href="'
        .api_get_path(WEB_CODE_PATH)
        .'course_info/maintenance.php?'
        .api_get_cidreq().'">'
        .get_lang('No')
        .'</a>';
    $message .= '</p>';
    $interbreadcrumb[] = [
        'url' => 'maintenance.php',
        'name' => get_lang('Maintenance'),
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
