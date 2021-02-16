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
$content = Display::page_subheader(get_lang('CourseTitle').' : '.$courseName);
$message = '';

$message .= Display::return_message(
    '<strong>'.get_lang('ByDel').'<strong>',
    'error',
    false
);

$form = new FormValidator('delete', 'get');
$form->addLabel(null, sprintf(get_lang('CourseCodeToEnteredCapitalLettersToConfirmDeletionX'), $courseCode));
$form->addText('course_code', get_lang('CourseCode'));
$form->addLabel(null, get_lang('AreYouSureToDeleteJS'));

$buttonGroup[] = $form->addButton('yes', get_lang('Yes'), '', 'danger', '', '', [], true);
$buttonGroup[] = $form->addButton('no', get_lang('No'), '', 'primary', '', '', ['id' => 'no_delete'], true);
$form->addGroup($buttonGroup);
$returnUrl = api_get_path(WEB_CODE_PATH).'course_info/maintenance.php?'.api_get_cidreq();
if ($form->validate()) {
    $values = $form->getSubmitValues();
    $courseCodeFromGet = $values['course_code'];

    if (isset($values['no'])) {
        api_location($returnUrl);
    }

    if (isset($values['yes'])) {
        if ($courseCode === $courseCodeFromGet) {
            CourseManager::delete_course($courseInfo['code']);
            // DELETE CONFIRMATION MESSAGE
            Session::erase('_cid');
            Session::erase('_real_cid');
            Display::addFlash(Display::return_message($courseCode.' '.get_lang('HasDel'), 'error', false));
            api_location(api_get_path(WEB_PATH));
        } else {
            Display::addFlash(Display::return_message(get_lang('CourseRegistrationCodeIncorrect'), 'warning'));
        }
    }
}

$message .= $form->returnForm();
$interbreadcrumb[] = [
    'url' => 'maintenance.php',
    'name' => get_lang('Maintenance'),
];

$htmlHeadXtra[] = '<script>
$(function(){
	/* Asking by course code to confirm recycling*/
	$("#no_delete").on("click", function(e) {
        e.preventDefault();
		window.location = "'.$returnUrl.'";
	});
})
</script>';

$tpl = new Template($tool_name);
$tpl->assign('content', $content.$message);
$tpl->display_one_col_template();
