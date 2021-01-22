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

$courseInfo = api_get_course_info();

if (empty($courseInfo)) {
    api_not_allowed(true);
}

$courseCode = $courseInfo['code'];
$courseName = $courseInfo['name'];

if (!api_is_allowed_to_edit()) {
    api_not_allowed(true);
}

$tool_name = get_lang('DelCourse');
$content = Display::page_subheader(get_lang('CourseTitle').': '.$courseName);
$message = '';

if (isset($_GET['delete']) && $_GET['delete'] === 'yes' && $_GET['course_code'] && !empty($_GET['course_code'])) {
    if ($courseCode === $_GET['course_code']) {
        CourseManager::delete_course($courseInfo['code']);
        // DELETE CONFIRMATION MESSAGE
        Session::erase('_cid');
        Session::erase('_real_cid');
        $message .= Display::return_message($courseCode.' '.get_lang('HasDel'), 'error', false);
    } else {
        /* message if code course is incorrect */
        $message .= Display::return_message(get_lang('CourseRegistrationCodeIncorrect'), 'warning');
        $message .= '<p><a class="btn btn-primary" href="'
            .api_get_path(WEB_CODE_PATH).'course_info/delete_course.php?'.api_get_cidreq().'">'.
            get_lang('BackToPreviousPage').'</a>';
    }
} else {
    $message .= Display::return_message(
        '<strong>'.get_lang('ByDel').'<strong>',
        'error',
        false
    );
    $message .= sprintf(get_lang('CourseCodeToEnteredCapitalLettersToConfirmDeletionX'), $courseCode);
    $message .= '<p><span class="form_required">*</span>'
        .get_lang('CourseCode')
        .'&nbsp;<input type="text" name="course_code" id="course_code"></p>';

    $message .= get_lang('AreYouSureToDeleteJS');
    $message .= '<p>';
    $message .= '<button class="btn btn-danger delete-course">'.get_lang('Yes').'</button>&nbsp;';
    $message .= '<a class="btn btn-primary" href="'
                .api_get_path(WEB_CODE_PATH).'course_info/maintenance.php?'.api_get_cidreq().'">'
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
		window.location = "'.api_get_self().'?delete=yes&'.api_get_cidreq().'&course_code=" + $("#course_code").val();
	})
})
</script>';

$tpl = new Template($tool_name);
$tpl->assign('content', $content.$message);
$tpl->display_one_col_template();
