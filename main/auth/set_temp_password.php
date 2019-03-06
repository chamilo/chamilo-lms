<?php
/* For license terms, see /license.txt */

use ChamiloSession as Session;

/**
 * This page aims at requesting a password from a user to access a course
 * protected by password. If the password matches the course password, we
 * store the fact that user can access it during its session.
 */
$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';
$this_section = SECTION_COURSES;
$courseId = isset($_GET['course_id']) ? intval($_GET['course_id']) : null;
$sessionId = isset($_GET['session_id']) ? intval($_GET['session_id']) : null;
$userId = api_get_user_id();

/**
 * Security check.
 */
if (empty($courseId)) {
    api_not_allowed();
}

$courseInfo = api_get_course_info_by_id($courseId);

// Build the form
$form = new FormValidator(
    'set_temp_password',
    'POST',
    api_get_self().'?course_id='.$courseId.'&session_id='.$sessionId
);
$form->addElement('header', get_lang('CourseRequiresPassword'));
$form->addElement('hidden', 'course_id', $courseId);
$form->addElement('hidden', 'session_id', $sessionId);
$form->addElement('password', 'course_password', get_lang('Password'));
$form->addButtonSave(get_lang('Accept'));

if ($form->validate()) {
    $formValues = $form->exportValues();
    if (sha1($formValues['course_password']) === $courseInfo['registration_code']) {
        Session::write('course_password_'.$courseInfo['real_id'], true);
        header('Location: '.api_get_course_url($courseInfo['code'], $sessionId).
        '&action=subscribe&sec_token='.Security::get_existing_token());
        exit;
    } else {
        Display::addFlash(
            Display::return_message(get_lang('CourseRegistrationCodeIncorrect'), 'error')
        );
    }
}

$tpl = new Template(null);
$tpl->assign('content', $form->toHtml());
$tpl->display_one_col_template();
