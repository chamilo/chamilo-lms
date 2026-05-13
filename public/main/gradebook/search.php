<?php

/* For licensing terms, see /license.txt */

/**
 * Search user certificates when certificate search is enabled.
 */

$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

if ('true' !== api_get_setting('certificate.allow_certificates_search')) {
    api_not_allowed(
        true,
        Display::return_message(get_lang('Certificates search is not available'), 'warning')
    );
}

$userId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$firstname = isset($_GET['firstname']) ? trim((string) $_GET['firstname']) : '';
$lastname = isset($_GET['lastname']) ? trim((string) $_GET['lastname']) : '';

$firstname = mb_substr($firstname, 0, 255);
$lastname = mb_substr($lastname, 0, 255);

$userList = [];
$userInfo = [];
$courseList = [];
$sessionList = [];
$warningMessage = '';

$hasSearch = '' !== $firstname || '' !== $lastname;

if ($userId > 0) {
    $userInfo = api_get_user_info($userId);

    if (empty($userInfo)) {
        $warningMessage = get_lang('No user');
    } else {
        $courseList = GradebookUtils::getUserCertificatesInCourses($userId, false);
        $sessionList = GradebookUtils::getUserCertificatesInSessions($userId, false);

        if (empty($courseList) && empty($sessionList)) {
            $warningMessage = sprintf(
                get_lang('User %s hast not acquired any certificate yet'),
                $userInfo['complete_name']
            );
        }
    }
} elseif ($hasSearch) {
    $userList = UserManager::getUsersByName($firstname, $lastname);

    if (empty($userList)) {
        $warningMessage = get_lang('No results found');
    }
}

$template = new Template(get_lang('Search certificates'));

$template->assign('search_url', api_get_self());
$template->assign('firstname', $firstname);
$template->assign('lastname', $lastname);
$template->assign('has_search', $hasSearch);
$template->assign('warning_message', $warningMessage);
$template->assign('user_list', $userList);
$template->assign('user_info', $userInfo);
$template->assign('course_list', $courseList);
$template->assign('session_list', $sessionList);

$templateName = $template->get_template('gradebook/search.tpl');
$content = $template->fetch($templateName);

$template->assign('header', get_lang('Search certificates'));
$template->assign('content', $content);

$template->display_one_col_template();
