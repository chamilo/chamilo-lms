<?php
/* For licensing terms, see /license.txt */

$cidReset = true;

// including the global Chamilo file
require_once __DIR__.'/../inc/global.inc.php';

$sessionId = isset($_GET['session_id']) ? $_GET['session_id'] : null;
$userId = isset($_GET['user_id']) ? $_GET['user_id'] : null;

$session = api_get_session_entity($sessionId);
SessionManager::protectSession($session);
$sessionInfo = api_get_session_info($sessionId);

if (empty($sessionInfo)) {
    api_not_allowed(true);
}

if (!isset($sessionInfo['duration']) ||
    (isset($sessionInfo['duration']) && empty($sessionInfo['duration']))
) {
    api_not_allowed(true);
}

if (empty($sessionId) || empty($userId)) {
    api_not_allowed(true);
}

$interbreadcrumb[] = ['url' => 'session_list.php', 'name' => get_lang('Session list')];
$interbreadcrumb[] = [
    'url' => "resume_session.php?id_session=".$sessionId,
    "name" => get_lang('Session overview'),
];

$form = new FormValidator('edit', 'post', api_get_self().'?session_id='.$sessionId.'&user_id='.$userId);
$form->addHeader(get_lang('User\'s session duration edition'));
$userInfo = api_get_user_info($userId);

// Show current end date for the session for this user, if any
$userAccess = CourseManager::getFirstCourseAccessPerSessionAndUser(
    $sessionId,
    $userId
);

if (0 == count($userAccess)) {
    // User never accessed the session. End date is still open
    $msg = sprintf(get_lang('This user never accessed this session before. The duration is currently set to %s days (from the first access date)'), $sessionInfo['duration']);
} else {
    // The user already accessed the session. Show a clear detail of the days count.
    $days = SessionManager::getDayLeftInSession($sessionInfo, $userId);
    $firstAccess = api_strtotime($userAccess['login_course_date'], 'UTC');
    $firstAccessString = api_convert_and_format_date($userAccess['login_course_date'], DATE_FORMAT_SHORT, 'UTC');
    $duration = 0;
    if ($days > 0) {
        $userSubscription = SessionManager::getUserSession($userId, $sessionId);
        $duration = $sessionInfo['duration'];

        if (!empty($userSubscription['duration'])) {
            $duration = $duration + $userSubscription['duration'];
        }

        $msg = sprintf(get_lang('This user\'s first access to the session was on %s. With a session duration of %s days, the end date is scheduled in %s days'), $firstAccessString, $duration, $days);
    } else {
        $endDateInSeconds = $firstAccess + $duration * 24 * 60 * 60;
        $last = api_convert_and_format_date($endDateInSeconds, DATE_FORMAT_SHORT);
        $msg = sprintf(get_lang('This user\'s first access to the session was on %s. With a session duration of %s days, the access to this session already expired on %s'), $firstAccessString, $duration, $last);
    }
}
$form->addElement('html', sprintf(get_lang('User: %s - Session: %s'), $userInfo['complete_name'], $sessionInfo['name']));
$form->addElement('html', '<br>');
$form->addElement('html', $msg);

$form->addElement('text', 'duration', [get_lang('Additional access days for this user'), null, get_lang('days')]);
$form->addButtonSave(get_lang('Save'));

$form->setDefaults(['duration' => 0]);
$message = null;
if ($form->validate()) {
    $duration = $form->getSubmitValue('duration');
    // Only update if the duration is different from the default duration
    if (0 != $duration) {
        SessionManager::editUserSessionDuration($duration, $userId, $sessionId);
        $message = Display::return_message(get_lang('Item updated'), 'confirmation');
    } else {
        $message = Display::return_message(get_lang('The given session duration is the same as the default for the session. Ignoring.'), 'warning');
    }
}

// display the header
Display::display_header(get_lang('Edit'));

echo $message;
$form->display();

Display :: display_footer();
