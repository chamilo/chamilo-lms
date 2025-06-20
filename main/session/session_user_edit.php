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
$userInfo = api_get_user_info($userId, false, false, false, false, true);

// Show current end date for the session for this user, if any
$userAccess = CourseManager::getFirstCourseAccessPerSessionAndUser(
    $sessionId,
    $userId
);

$extension = 0;

if (count($userAccess) == 0) {
    // User never accessed the session. End date is still open
    $msg = sprintf(get_lang('UserNeverAccessedSessionDefaultDurationIsX'), $sessionInfo['duration']);
} else {
    // The user already accessed the session. Show a clear detail of the days count.
    $duration = $sessionInfo['duration'];
    $days = SessionManager::getDayLeftInSession($sessionInfo, $userId);
    $userSubscription = SessionManager::getUserSession($userId, $sessionId);
    $extension = $userSubscription['duration'];
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
        if (!empty($subscription['duration'])) {
            $duration = $duration + $subscription['duration'];
        }
        $endDateInSeconds = $firstAccess + $duration * 24 * 60 * 60;
        $last = api_convert_and_format_date($endDateInSeconds, DATE_FORMAT_SHORT);
        $msg = sprintf(get_lang('FirstAccessWasXSessionDurationYEndDateWasZ'), $firstAccessString, $duration, $last);
    }
}

$header =  '<div class="row">';
$header .= '<div class="col-sm-5">';
$header .= '<div class="thumbnail">';
$header .= Display::img($userInfo['avatar'], $userInfo['complete_name'], null, false);
$header .= '</div>';
$header .= '</div>';

$header .= '<div class="col-sm-7">';

$userData = $userInfo['complete_name']
    .PHP_EOL
    .$user_info['mail']
    .PHP_EOL
    .$user_info['official_code'];

$header .= '<h3>Usuario: ' . Display::url(
    $userData,
    api_get_path(WEB_CODE_PATH).'social/profile.php?u='.$user_info['user_id']
) .'</h3>';

$header .= '<p><h3>Sesión: ' . $sessionInfo['name'] . '<h3></p>';

$header .= '</div>';
$header .='</div>';

$header .=  '<div class="row">';
$header .= '<div class="col-sm-12">';

$header .= $msg;
$header .= '<p>'.'</p>';

$header .= '</div>';
$header .= '</div>';

$form->addElement('html', $header);

$formData =  '<div class="row">';
$formData .= '<div class="col-sm-12">';

$form->addElement('html', $formData);
$form->addElement('number', 'duration', [get_lang('ExtraDurationForUser'), null, get_lang('Days')]);
$form->addButtonSave(get_lang('Save'));

$formData = '</div>';
$formData .= '</div>';

$form->addElement('html', $formData);

$form->setDefaults(['duration' => $extension]);
$message = null;
if ($form->validate()) {
    $duration = $form->getSubmitValue('duration');

    SessionManager::editUserSessionDuration($duration, $userId, $sessionId);
    $message = Display::return_message(get_lang('ItemUpdated'), 'confirmation');

    $url = api_get_self().'?'.Security::remove_XSS($_SERVER['QUERY_STRING']);
    $url = str_replace('&amp;', '&', $url);
    header("Location: " . $url);
    exit();
}

// display the header
Display::display_header(get_lang('Edit'));

echo $message;
$form->display();

Display::display_footer();
