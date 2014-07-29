<?php
/* For licensing terms, see /license.txt */

$language_file = 'admin';
$cidReset = true;

// including the global Chamilo file
require_once '../inc/global.inc.php';

api_protect_admin_script(true);

$sessionId = isset($_GET['session_id']) ? $_GET['session_id'] : null;
$userId = isset($_GET['user_id']) ? $_GET['user_id'] : null;

SessionManager::protect_session_edit($sessionId);

$sessionInfo = api_get_session_info($sessionId);
if (empty($sessionInfo)) {
    api_not_allowed(true);
}

if (!isset($sessionInfo['duration']) ||
    isset($sessionInfo['duration']) && empty($sessionInfo['duration'])) {
    api_not_allowed(true);
}

if (!SessionManager::durationPerUserIsEnabled()) {
    api_not_allowed(true);
}
if (empty($sessionId) || empty($userId)) {
    api_not_allowed(true);
}

$interbreadcrumb[] = array('url' => 'index.php', 'name' => get_lang('PlatformAdmin'));
$interbreadcrumb[] = array('url' => 'session_list.php','name' => get_lang('SessionList'));
$interbreadcrumb[] = array('url' => "resume_session.php?id_session=".$sessionId, "name" => get_lang('SessionOverview'));

$form = new FormValidator('edit', 'post', api_get_self().'?session_id='.$sessionId.'&user_id='.$userId);
$form->add_header(get_lang('EditUserSessionDuration'));
$data = SessionManager::getUserSession($userId, $sessionId);

$form->addElement('text', 'duration', array(get_lang('Duration'), null, get_lang('Days')));
$form->addElement('button', 'submit', get_lang('Send'));
$form->setDefaults($data);
$message = null;
if ($form->validate()) {
    $duration = $form->getSubmitValue('duration');
    SessionManager::editUserSessionDuration($duration, $userId, $sessionId);
    $message = Display::return_message(get_lang('ItemUpdated'), 'confirmation');
}

// display the header
Display::display_header(get_lang('Edit'));

echo $message;
$form->display();

Display :: display_footer();
