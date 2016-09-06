<?php
/* For license terms, see /license.txt */

use ChamiloSession as Session;

/**
 * This page aims at requesting a password from a user to access a course
 * protected by password. If the password matches the course password, we
 * store the fact that user can access it during its session
 */

$cidReset = true;
require_once '../inc/global.inc.php';
$this_section = SECTION_COURSES;
$course_id = isset($_GET['course_id'])  ? intval($_GET['course_id']) : null;
$session_id = isset($_GET['session_id']) ? intval($_GET['session_id']) : null;
$user_id = api_get_user_id();

/**
 * Security check
 */
if (empty($course_id)) {
    api_not_allowed();
}

$course_info = api_get_course_info_by_id($course_id);

$tpl = new Template(null);

// Build the form
$form = new FormValidator('set_temp_password', 'POST', api_get_self().'?course_id='.$course_id.'&session_id='.$session_id);
$form->addElement('header', get_lang('CourseRequiresPassword'));
$form->addElement('hidden', 'course_id', $course_id);
$form->addElement('hidden', 'session_id', $session_id);
$form->addElement('password', 'course_password', get_lang('Password'));
$form->addButtonSave(get_lang('Accept'));

if ($form->validate()) {
    $form_values = $form->exportValues();
    if (sha1($form_values['course_password']) === $course_info['registration_code']) {
        Session::write('course_password_'.$course_info['real_id'], true);
        header('Location: '.api_get_course_url($course_info['code'], $session_id));
        exit;
    } else {
        $tpl->assign('error_message', Display::display_error_message(get_lang('CourseRegistrationCodeIncorrect'), true, true));
    }
}

$tpl->assign('form', $form->toHtml());
$content = $tpl->get_template('auth/set_temp_password.tpl');
$tpl->assign('content', $tpl->fetch($content));
$tpl->display_one_col_template();

