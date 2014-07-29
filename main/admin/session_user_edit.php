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
$userInfo = api_get_user_info($userId);

// Show current end date for the session for this user, if any
$userAccess = CourseManager::getFirstCourseAccessPerSessionAndUser(
    $sessionId,
    $userId
);
if (count($userAccess) == 0) {
    // User never accessed the session. End date is still open
    $msg = sprintf(get_lang('UserNeverAccessedSessionDefaultDurationIsX'), $sessionInfo['duration']);
} else {
    // The user already accessed the session. Show a clear detail of the days count.
    $duration = $sessionInfo['duration'];
    if (!empty($data['duration'])) {
        $duration = $duration + $data['duration'];
    }
    $days = SessionManager::getDayLeftInSession($sessionId, $userId, $duration);
    $firstAccess = api_strtotime($userAccess['login_course_date'], 'UTC');
    if ($days > 0) {
        $msg = sprintf(get_lang('FirstAccessWasXSessionDurationYEndDateInZ'), $firstAccess, $duration, $days);
    } else {
        $endDateInSeconds = $firstAccess + $duration*24*60*60;
        $last = api_get_local_time($endDateInSeconds);
        $msg = sprintf(get_lang('FirstAccessWasXSessionDurationYEndDateWasZ'), $firstAccess, $duration, $last);
    }
}
$form->addElement('html', sprintf(get_lang('UserXSessionY'), $userInfo['complete_name'], $sessionInfo['name']));
$form->addElement('html', '<br>');
$form->addElement('html', $msg);

$form->addElement('text', 'duration', array(get_lang('ExtraDuration'), null, get_lang('Days')));
$form->addElement('button', 'submit', get_lang('Send'));

if (empty($data['duration'])) {
    $data['duration'] = 0;
}
$form->setDefaults($data);
$message = null;
if ($form->validate()) {
    $duration = $form->getSubmitValue('duration');
    // Only update if the duration is different from the default duration
    if ($duration != 0) {
        SessionManager::editUserSessionDuration($duration, $userId, $sessionId);
        $message = Display::return_message(get_lang('ItemUpdated'), 'confirmation');
    } else {
        $message = Display::return_message(get_lang('DurationIsSameAsDefault'), 'warning');
    }
}

// display the header
Display::display_header(get_lang('Edit'));

echo $message;
$form->display();

Display :: display_footer();
