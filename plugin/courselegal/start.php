<?php
/* For license terms, see /license.txt */

require_once __DIR__.'/config.php';

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

$form = new FormValidator('plugin', 'post', $url);
$form->addElement('header', $legal->get_lang('CourseLegal'));
$form->addElement('hidden', 'session_id', $sessionId);
$form->addElement('hidden', 'c_id', $courseId);
$form->addHtmlEditor(
    'content',
    get_lang('Text'),
    true,
    false,
    ['ToolbarSet' => 'TermsAndConditions']
);
$form->addElement('file', 'uploaded_file', get_lang('File'));
$file = $legal->getCurrentFile($courseId, $sessionId);

if (!empty($file)) {
    $form->addElement('label', get_lang('File'), $file);
}

$form->addElement('checkbox', 'delete_file', null, $legal->get_lang('DeleteFile'));
$form->addElement('checkbox', 'remove_previous_agreements', null, $legal->get_lang('RemoveAllUserAgreements'));
$form->addElement('radio', 'warn_users_by_email', null, $legal->get_lang('NoSendWarning'), 1);
$form->addElement('radio', 'warn_users_by_email', $legal->get_lang('WarnAllUsersByEmail'), $legal->get_lang('SendOnlyWarning'), 2);
$form->addElement('radio', 'warn_users_by_email', null, $legal->get_lang('SendAgreementFile'), 3);
$form->addButtonSave(get_lang('Save'));
$defaults = $legal->getData($courseId, $sessionId);
$defaults['warn_users_by_email'] = 1;
$form->setDefaults($defaults);

if ($form->validate()) {
    $values = $form->getSubmitValues();
    $file = isset($_FILES['uploaded_file']) ? $_FILES['uploaded_file'] : [];
    $deleteFile = isset($values['delete_file']) ? $values['delete_file'] : false;
    $legal->save($values, $file, $deleteFile);
    header('Location: '.$url);
    exit;
}
Display::display_header($legal->get_lang('CourseLegal'));
$url = api_get_path(WEB_PLUGIN_PATH).'courselegal/user_list.php?'.api_get_cidreq();
$link = Display::url(Display::return_icon('user.png', get_lang('UserList')), $url);
echo '<div class="actions">'.$link.'</div>';
$form->display();

Display::display_footer();
