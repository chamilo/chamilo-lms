<?php
/* For licensing terms, see /license.txt */

$cidReset = true;

// including the global Chamilo file
require_once __DIR__.'/../inc/global.inc.php';

$sessionId = isset($_GET['session_id']) ? $_GET['session_id'] : null;
$userId = isset($_GET['user_id']) ? $_GET['user_id'] : null;

SessionManager::protectSession($sessionId);

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

$interbreadcrumb[] = ['url' => 'session_list.php', 'name' => get_lang('SessionList')];
$interbreadcrumb[] = [
    'url' => "resume_session.php?id_session=".$sessionId,
    "name" => get_lang('SessionOverview'),
];

$form = new FormValidator('edit', 'post', api_get_self().'?session_id='.$sessionId.'&user_id='.$userId);
$form->addHeader(get_lang('EditUserSessionDuration'));
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
    $days = SessionManager::getDayLeftInSession($sessionInfo, $userId);
    $firstAccess = api_strtotime($userAccess['login_course_date'], 'UTC');
    $firstAccessString = api_convert_and_format_date($userAccess['login_course_date'], DATE_FORMAT_SHORT, 'UTC');
    if ($days > 0) {
        $userSubscription = SessionManager::getUserSession($userId, $sessionId);
        $duration = $sessionInfo['duration'];

        if (!empty($userSubscription['duration'])) {
            $duration = $duration + $userSubscription['duration'];
        }

        $msg = sprintf(get_lang('FirstAccessWasXSessionDurationYEndDateInZDays'), $firstAccessString, $duration, $days);
    } else {
        $endDateInSeconds = $firstAccess + $duration * 24 * 60 * 60;
        $last = api_convert_and_format_date($endDateInSeconds, DATE_FORMAT_SHORT);
        $msg = sprintf(get_lang('FirstAccessWasXSessionDurationYEndDateWasZ'), $firstAccessString, $duration, $last);
    }
}
$form->addElement('html', sprintf(get_lang('UserXSessionY'), $userInfo['complete_name'], $sessionInfo['name']));
$form->addElement('html', '<br>');
$form->addElement('html', $msg);

$form->addElement('text', 'duration', [get_lang('ExtraDurationForUser'), null, get_lang('Days')]);
$form->addButtonSave(get_lang('Save'));

$form->setDefaults(['duration' => 0]);
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

Display::display_footer();
