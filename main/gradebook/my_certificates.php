<?php

/* For licensing terms, see /license.txt */
/**
 * List of achieved certificates by the current user
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 * @package chamilo.gradebook
 */
$cidReset = true;

require_once '../inc/global.inc.php';

if (api_is_anonymous()) {
    api_not_allowed(true);
}

$userId = api_get_user_id();

$courseList = GradebookUtils::getUserCertificatesInCourses($userId);
$sessionList = GradebookUtils::getUserCertificatesInSessions($userId);

$template = new Template(get_lang('MyCertificates'));

$template->assign('course_list', $courseList);
$template->assign('session_list', $sessionList);
$content = $template->fetch('default/gradebook/my_certificates.tpl');

if (empty($courseList) || empty($sessionList)) {
    $template->assign(
        'message',
        Display::return_message(get_lang('YouNotYetAchievedCertificates'), 'warning')
    );
}

$template->assign(
    'actions',
    Display::toolbarButton(
        get_lang('CertificatesSearch'),
        api_get_path(WEB_CODE_PATH) . "gradebook/search.php",
        'search',
        'info'
    )
);

$template->assign('content', $content);
$template->display_one_col_template();
