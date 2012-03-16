<?php

// Language files that need to be included
$language_file = array('create_course', 'course_info', 'admin');

$cidReset = true;

require_once '../inc/global.inc.php';
$this_section = SECTION_COURSES;

$course_code    = isset($_REQUEST['course_code'])  ? $_REQUEST['course_code'] : null;
$session_id     = isset($_REQUEST['session_id']) ? intval($_REQUEST['session_id']) : null;
$user_id        = api_get_user_id();

if (empty($course_code)) {
    api_not_allowed();
}

$course_info = CourseManager::get_course_information($course_code);
$course_legal = $course_info['legal'];

// Build the form
$form = new FormValidator('legal', 'GET', api_get_self().'?course_code='.$course_code.'&session_id='.$session_id);
$form->addElement('header', get_lang('CourseLegalAgreement'));
$form->addElement('label', null, $course_legal);
$form->addElement('hidden', 'course_code', $course_code);
$form->addElement('hidden', 'session_id', $session_id);
$form->addElement('checkbox', 'accept_legal', null, get_lang('AcceptLegal'));
$form->addElement('style_submit_button', null, get_lang('Accept'), 'class="save"');

if ($form->validate()) {
    $accept_legal = $form->exportValue('accept_legal');
        
    if ($accept_legal == 1 ) {
        $result = CourseManager::save_user_legal($user_id, $course_code, $session_id);        
    }
}

$url = api_get_course_url($course_code, $session_id);

if (empty($session_id)) {
    if (CourseManager::is_user_subscribed_in_course($user_id, $course_code)) {
        $user_accepted_legal = CourseManager::is_user_accepted_legal($user_id, $course_code);
        if ($user_accepted_legal) {
            //Redirect to course home
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
    $user_session_status = SessionManager::get_user_status_in_session($user_id, $course_code, $session_id);
    
    if (isset($user_session_status)) {        
        $user_accepted_legal = CourseManager::is_user_accepted_legal($user_id, $course_code, $session_id);        
        if ($user_accepted_legal) {
            //Redirect to course session home
            header('Location: '.$url);
            exit;
        }          
    } else {
        api_not_allowed();
    }  
}

Display :: display_header($nameTools);
$form->display();
Display :: display_footer();