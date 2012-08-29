<?php

/* For licensing terms, see /license.txt */
$language_file = 'admin';
$cidReset = true;
require_once '../inc/global.inc.php';

$user_id = intval($_REQUEST['user_id']);
$session_id = intval($_REQUEST['id_session']);

if (empty($user_id) && empty($session_id)) {
    api_not_allowed(true);
}

SessionManager::protect_session_edit($session_id);

if (api_is_platform_admin()) {
    $sessions = SessionManager::get_sessions_admin();
} else {
    $sessions = SessionManager::get_sessions_by_coach(api_get_session_id());
}


$message = null;
$session_to_select = array();
foreach ($sessions as $session) {
    if ($session_id != $session['id']) {
        $session_to_select[$session['id']] = $session['name'];
    }
}

$session_name = api_get_session_name($session_id);
$user_info = api_get_user_info($user_id);

$form = new FormValidator('change_user_session', 'post', api_get_self());
$form->addElement('hidden', 'user_id', $user_id);
$form->addElement('hidden', 'id_session', $session_id);
$form->addElement('header', get_lang('ChangeUserSession'));
$form->addElement('label', get_lang('User'), '<b>'.$user_info['complete_name'].'</b>');
$form->addElement('label', get_lang('CurrentSession'), $session_name);

$form->addElement('select', 'reason_id', get_lang('Action'), SessionManager::get_session_change_user_reasons());
$form->addElement('select', 'new_session_id', get_lang('SessionDestination'), $session_to_select);

$form->addRule('new_session_id', get_lang('Required'), 'required');
$form->addElement('button', 'submit', get_lang('Change'));

$content = $form->return_form();

if ($form->validate()) {
    $values = $form->getSubmitValues();
    $result = SessionManager::change_user_session($values['user_id'], $values['id_session'], $values['new_session_id'], $values['reason_id']);
    if ($result) {
        $message = Display::return_message(get_lang('UserSessionWasChanged'));
    }
    header('Location: resume_session.php?id_session='.$values['id_session']);
    exit;
}

$interbreadcrumb[] = array('url' => 'index.php', 'name' => get_lang('PlatformAdmin'));
$interbreadcrumb[] = array('url' => 'session_list.php','name' => get_lang('SessionList'));
$interbreadcrumb[] = array('url' => 'resume_session.php?id_session='.$session_id,'name' => get_lang('SessionOverview'));
$interbreadcrumb[] = array('url' => '#','name' => get_lang('ChangeUserSession'));

$tpl = new Template();

$tpl->assign('message', $message);
$tpl->assign('content', $content);
$tpl->display_one_col_template();