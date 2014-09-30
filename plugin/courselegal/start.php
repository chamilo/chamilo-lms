<?php
/* For license terms, see /license.txt */
$language_file = array('document','gradebook');

require_once dirname(__FILE__) . '/config.php';

// Course legal
$enabled = api_get_plugin_setting('courselegal', 'tool_enable');

if ($enabled != 'true') {
    api_not_allowed(true);
}

if (!api_is_allowed_to_edit()) {
    api_not_allowed(true);
}

$legal = CourseLegalPlugin::create();
$url = api_get_self().'?'.api_get_cidreq();
$courseId = api_get_course_int_id();
$sessionId = api_get_session_id();

$form = new FormValidator('plugin', 'post', $url );
$form->addElement('header', get_lang('CourseLegal'));
$form->addElement('hidden', 'session_id', $sessionId);
$form->addElement('hidden', 'c_id', $courseId);
$form->addElement('textarea', 'content', get_lang('Text'));
$form->addElement('file', 'uploaded_file', get_lang('File'));
$file = $legal->getCurrentFile($courseId, $sessionId);

if (!empty($file)) {
    $form->addElement('label', get_lang('File'), $file);
}

$form->addElement('checkbox', 'delete_file', null, get_lang('DeleteFile'));
$form->addElement('checkbox', 'remove_previous_agreements', null, get_lang('RemoveAllUserAgreements'));
$form->addElement('checkbox', 'warn_users_by_email', null, get_lang('WarnAllUsersByEmail'));
$form->addElement('button', 'submit', get_lang('Send'));

$form->setDefaults($legal->getData($courseId, $sessionId));

if ($form->validate()) {
    $values = $form->getSubmitValues();
    $file = isset($_FILES['uploaded_file']) ? $_FILES['uploaded_file'] : array();
    $deleteFile = isset($values['delete_file']) ? $values['delete_file'] : false;
    $legal->save($values, $file, $deleteFile);
    header('Location: '.$url);
    exit;
}
Display::display_header(get_lang('CourseLegal'));
$form->display();
