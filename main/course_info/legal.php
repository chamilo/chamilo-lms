<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';
$this_section = SECTION_COURSES;

$course_code = isset($_REQUEST['course_code']) ? Security::remove_XSS($_REQUEST['course_code']) : null;
$session_id = isset($_REQUEST['session_id']) ? (int) $_REQUEST['session_id'] : null;
$user_id = api_get_user_id();

if (empty($course_code)) {
    api_not_allowed();
}

$course_info = CourseManager::get_course_information($course_code);
$course_legal = $course_info['legal'];

$enabled = api_get_plugin_setting('courselegal', 'tool_enable');
$pluginExtra = null;
$pluginLegal = false;

if ('true' === $enabled) {
    $pluginLegal = true;
    require_once api_get_path(SYS_PLUGIN_PATH).'courselegal/config.php';
    $plugin = CourseLegalPlugin::create();
    $data = $plugin->getData($course_info['real_id'], $session_id);

    if (!empty($data)) {
        $course_legal = $data['content'];
    }

    $userData = $plugin->getUserAcceptedLegal(
        $user_id,
        $course_info['real_id'],
        $session_id
    );

    if (isset($_GET['web_agreement_link'])) {
        $plugin->saveUserMailLegal(
            $_GET['web_agreement_link'],
            $user_id,
            $course_info['real_id'],
            $session_id
        );
    }
}

// Build the form
$form = new FormValidator('legal', 'GET', api_get_self().'?course_code='.$course_code.'&session_id='.$session_id);
$pluginMessage = null;
$hideForm = false;
if ($pluginLegal && isset($userData) && !empty($userData)) {
    if ($userData['web_agreement'] == 1) {
        if (empty($userData['mail_agreement'])) {
            $pluginMessage = Display::return_message(
                $plugin->get_lang('YouNeedToConfirmYourAgreementCheckYourEmail')
            );
            $hideForm = true;
        }
    }
}
$form->addElement('header', get_lang('CourseLegalAgreement'));
$form->addLabel(null, Security::remove_XSS($course_legal));
if ($pluginLegal && !empty($plugin)) {
    $form->addElement('label', null, $plugin->getCurrentFile($course_info['real_id'], $session_id));
}
$form->addElement('hidden', 'course_code', $course_code);
$form->addElement('hidden', 'session_id', $session_id);
$form->addElement('checkbox', 'accept_legal', null, get_lang('AcceptLegal'));
$form->addButtonSave(get_lang('Accept'));

$variable = 'accept_legal_'.$user_id.'_'.$course_info['real_id'].'_'.$session_id;

$url = api_get_course_url($course_code, $session_id);

if ($form->validate()) {
    $accept_legal = $form->exportValue('accept_legal');
    if (1 == $accept_legal) {
        // Register to private course if is allowed.
        if (empty($session_id) &&
            COURSE_VISIBILITY_REGISTERED == $course_info['visibility'] &&
            1 == $course_info['subscribe']
        ) {
            CourseManager::subscribeUser($user_id, $course_info['code'], STUDENT, 0);
        }

        CourseManager::save_user_legal($user_id, $course_info, $session_id);
        if (api_check_user_access_to_legal($course_info)) {
            Session::write($variable, true);
        }

        if ($pluginLegal) {
            header('Location:'.$url);
            exit;
        }
    }
}

$user_pass_open_course = false;
if (api_check_user_access_to_legal($course_info) && Session::read($variable)) {
    $user_pass_open_course = true;
}

if (empty($session_id)) {
    if (CourseManager::is_user_subscribed_in_course($user_id, $course_code) ||
        api_check_user_access_to_legal($course_info)
    ) {
        $user_accepted_legal = CourseManager::is_user_accepted_legal(
            $user_id,
            $course_code
        );

        if ($user_accepted_legal || $user_pass_open_course) {
            // Redirect to course home
            header('Location: '.$url);
            exit;
        }
    } else {
        api_not_allowed();
    }
} else {
    if (api_is_platform_admin()) {
        header('Location: '.$url);
    }

    $userStatus = SessionManager::get_user_status_in_course_session($user_id, $course_info['real_id'], $session_id);

    if (isset($userStatus) || api_check_user_access_to_legal($course_info)) {
        $user_accepted_legal = CourseManager::is_user_accepted_legal(
            $user_id,
            $course_code,
            $session_id
        );
        if ($user_accepted_legal || $user_pass_open_course) {
            // Redirect to course session home.
            header('Location: '.$url);
            exit;
        }
    } else {
        api_not_allowed();
    }
}

Display::display_header();
echo $pluginMessage;
if ($hideForm == false) {
    $form->display();
}
Display::display_footer();
