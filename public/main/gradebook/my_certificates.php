<?php

/* For licensing terms, see /license.txt */

/**
 * List of achieved certificates by the current user.
 *
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 */
$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';
api_block_inactive_user();

$logInfo = [
    'tool' => 'MyCertificates',
];
Event::registerLog($logInfo);

if (api_is_anonymous()) {
    api_not_allowed(true);
}

$userId = api_get_user_id();

$courseList = GradebookUtils::getUserCertificatesInCourses($userId);
$sessionList = GradebookUtils::getUserCertificatesInSessions($userId);

if (empty($courseList) && empty($sessionList)) {
    Display::addFlash(
        Display::return_message(get_lang('You have not achieved any certificate just yet. Continue on your learning path to get one!'), 'warning')
    );
}

$hideExportLink = api_get_setting('hide_certificate_export_link');
$hideExportLinkStudent = api_get_setting('hide_certificate_export_link_students');
$allowExport = true;
if ('true' === $hideExportLink ||
    (api_is_student() && 'true' === $hideExportLinkStudent)
) {
    $allowExport = false;
}

$template = new Template(get_lang('My certificates'));
$template->assign('course_list', $courseList);
$template->assign('session_list', $sessionList);
$template->assign('allow_export', $allowExport);
$templateName = $template->get_template('gradebook/my_certificates.tpl');
$content = $template->fetch($templateName);

if ('true' === api_get_setting('allow_public_certificates')) {
    $template->assign(
        'actions',
        Display::toolbarButton(
            get_lang('Search certificates'),
            api_get_path(WEB_CODE_PATH).'gradebook/search.php',
            'magnify',
            'info'
        )
    );
}

$template->assign('content', $content);
$template->display_one_col_template();
