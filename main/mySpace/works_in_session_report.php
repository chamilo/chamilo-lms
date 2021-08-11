<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionRelCourse;
use Chamilo\UserBundle\Entity\User;

require_once __DIR__.'/../inc/global.inc.php';

api_block_anonymous_users(true);

if (api_is_student()) {
    api_not_allowed(true);
    exit;
}

$toolName = get_lang('WorksInSessionReport');

$em = Database::getManager();
$session = null;
if (api_is_platform_admin()) {
    $sessionList = SessionManager::get_sessions_list();
} elseif (api_is_drh()) {
    $sessionList = SessionManager::get_sessions_followed_by_drh(api_get_user_id());
} elseif (api_is_session_admin()) {
    $sessionList = SessionManager::getSessionsFollowedByUser(api_get_user_id(), SESSIONADMIN);
} else {
    $sessionList = Tracking::get_sessions_coached_by_user(api_get_user_id());
}
$form = new FormValidator('work_report', 'GET');
$selectSession = $form->addSelect('session', get_lang('Session'), [0 => get_lang('None')]);
$form->addButtonFilter(get_lang('Filter'));

foreach ($sessionList as $sessionInfo) {
    $selectSession->addOption($sessionInfo['name'], $sessionInfo['id']);
}

$sessionId = isset($_GET['session']) ? (int) $_GET['session'] : 0;
$session = null;
if (!empty($sessionId)) {
    $form->setDefaults(['session' => $sessionId]);
    $session = api_get_session_entity($sessionId);
}

$courses = [];
$users = [];
if ($session) {
    $sessionCourses = $session->getCourses();
    /** @var SessionRelCourse $sessionCourse */
    foreach ($sessionCourses as $sessionCourse) {
        $course = $sessionCourse->getCourse();
        $courses[$course->getId()] = $course->getCode();
        $userCourseSubscriptions = $session->getUserCourseSubscriptionsByStatus($course, Session::STUDENT);
        $usersInSession = $session->getUsers();

        // Set defaults.
        foreach ($usersInSession as $userInSession) {
            $user = $userInSession->getUser();
            if (!array_key_exists($user->getId(), $users)) {
                $users[$user->getId()] = [
                    'code' => $user->getOfficialCode(),
                    'complete_name' => UserManager::formatUserFullName($user),
                    'time_in_platform' => api_time_to_hms(
                        Tracking::get_time_spent_on_the_platform($user->getId(), 'ever')
                    ),
                    'first_connection' => Tracking::get_first_connection_date($user->getId()),
                    'last_connection' => Tracking::get_last_connection_date($user->getId()),
                ];
            }
            $users[$user->getId()][$course->getId().'_score'] = null;
            $users[$user->getId()][$course->getId().'_progress'] = null;
            $users[$user->getId()][$course->getId().'_last_sent_date'] = null;
        }

        foreach ($userCourseSubscriptions as $userCourseSubscription) {
            /** @var User $user */
            $user = $userCourseSubscription->getUser();
            if (!$session->hasStudentInCourse($user, $course)) {
                continue;
            }
            $users[$user->getId()][$course->getId().'_score'] = Tracking::get_avg_student_score(
                $user->getId(),
                $course->getCode(),
                [],
                $session->getId(),
                false,
                false,
                true
            );
            $users[$user->getId()][$course->getId().'_progress'] = Tracking::get_avg_student_progress(
                $user->getId(),
                $course->getCode(),
                [],
                $session->getId()
            );

            $lastPublication = Tracking::getLastStudentPublication(
                $user,
                'work',
                $course,
                $session
            );

            if (!$lastPublication) {
                continue;
            }

            $users[$user->getId()][$course->getId().'_last_sent_date'] = api_get_local_time(
                $lastPublication->getSentDate()->getTimestamp()
            );
        }
    }
}

if (isset($_GET['export']) && $session && $courses && $users) {
    $fileName = 'works_in_session_'.api_get_local_time();

    $dataToExport = [];
    $dataToExport[] = [$toolName, $session->getName()];
    $dataToExport['headers'][] = get_lang('OfficialCode');
    $dataToExport['headers'][] = get_lang('StudentName');
    $dataToExport['headers'][] = get_lang('TimeSpentOnThePlatform');
    $dataToExport['headers'][] = get_lang('FirstLoginInPlatform');
    $dataToExport['headers'][] = get_lang('LatestLoginInPlatform');

    foreach ($courses as $courseCode) {
        $dataToExport['headers'][] = $courseCode.' ('.get_lang('BestScore').')';
        $dataToExport['headers'][] = get_lang('Progress');
        $dataToExport['headers'][] = get_lang('LastSentWorkDate');
    }

    foreach ($users as $user) {
        $dataToExport[] = $user;
    }

    switch ($_GET['export']) {
        case 'xls':
            Export::export_table_xls_html($dataToExport, $fileName);
            break;
        case 'csv':
            Export::arrayToCsv($dataToExport, $fileName);
            break;
    }
    exit;
}

$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'mySpace/index.php',
    'name' => get_lang('MySpace'),
];

$actions = null;

if ($session) {
    $actions = Display::url(
        Display::return_icon('export_csv.png', get_lang('ExportAsCSV'), [], ICON_SIZE_MEDIUM),
        api_get_self().'?'.http_build_query(['export' => 'csv', 'session' => $session->getId()])
    );
    $actions .= Display::url(
        Display::return_icon('export_excel.png', get_lang('ExportAsXLS'), [], ICON_SIZE_MEDIUM),
        api_get_self().'?'.http_build_query(['export' => 'xls', 'session' => $session->getId()])
    );
}

$view = new Template($toolName);
$view->assign('form', $form->returnForm());

if ($session) {
    $view->assign('session', ['name' => $session->getName()]);
    $view->assign('courses', $courses);
    $view->assign('users', $users);
}

$template = $view->get_template('my_space/works_in_session_report.tpl');
$content = $view->fetch($template);

$view->assign('header', $toolName);

if ($actions) {
    $view->assign('actions', Display::toolbarAction('toolbar', [$actions]));
}

$view->assign('content', $content);
$view->display_one_col_template();
