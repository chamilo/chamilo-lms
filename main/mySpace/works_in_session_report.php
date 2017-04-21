<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Session;
use Chamilo\UserBundle\Entity\User;
use Chamilo\CoreBundle\Entity\Course;

/**
 * Courses reporting
 * @package chamilo.reporting
 */

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
} else {
    $sessionList = Tracking::get_sessions_coached_by_user(api_get_user_id());
}
$form = new FormValidator('work_report', 'GET');
$selectSession = $form->addSelect('session', get_lang('Session'), [0 => get_lang('None')]);
$form->addButtonFilter(get_lang('Filter'));

foreach ($sessionList as $sessionInfo) {
    $selectSession->addOption($sessionInfo['name'], $sessionInfo['id']);
}

if (isset($_GET['session']) && intval($_GET['session'])) {
    $form->setDefaults(['session' => intval($_GET['session'])]);
    /** @var Session $session */
    $session = $em->find('ChamiloCoreBundle:Session', intval($_GET['session']));
}

$coursesInfo = [];
$usersInfo = [];

if ($session) {
    $sessionCourses = $session->getCourses();

    foreach ($sessionCourses as $sessionCourse) {
        /** @var Course $course */
        $course = $sessionCourse->getCourse();
        $coursesInfo[$course->getId()] =  $course->getCode();
        $userCourseSubscriptions = $session->getUserCourseSubscriptionsByStatus($course, Session::STUDENT);

        foreach ($userCourseSubscriptions as $userCourseSubscription) {
            /** @var User $user */
            $user = $userCourseSubscription->getUser();

            if (!array_key_exists($user->getId(), $usersInfo)) {
                $usersInfo[$user->getId()] = [
                    'code' => $user->getOfficialCode(),
                    'complete_name' => $user->getCompleteName(),
                    'time_in_platform' => api_time_to_hms(
                        Tracking::get_time_spent_on_the_platform($user->getId(), 'ever')
                    ),
                    'first_connection' => Tracking::get_first_connection_date($user->getId()),
                    'last_connection' => Tracking::get_last_connection_date($user->getId())
                ];
            }

            $usersInfo[$user->getId()][$course->getId() . '_score'] = null;
            $usersInfo[$user->getId()][$course->getId() . '_progress'] = null;
            $usersInfo[$user->getId()][$course->getId() . '_last_sent_date'] = null;

            if (!$session->hasStudentInCourse($user, $course)) {
                continue;
            }

            $usersInfo[$user->getId()][$course->getId() . '_score'] = Tracking::get_avg_student_score(
                $user->getId(),
                $course->getCode(),
                null,
                $session->getId(),
                false,
                false,
                true

            );
            $usersInfo[$user->getId()][$course->getId() . '_progress'] = Tracking::get_avg_student_progress(
                $user->getId(),
                $course->getCode(),
                null,
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

            $usersInfo[$user->getId()][$course->getId() . '_last_sent_date'] = api_format_date(
                $lastPublication->getSentDate()->getTimestamp(),
                DATE_TIME_FORMAT_SHORT
            );
        }
    }
}

if (isset($_GET['export']) && $session && ($coursesInfo && $usersInfo)) {
    $fileName = 'works_in_session_' . api_get_local_time();

    $dataToExport = [];
    $dataToExport[] = [$toolName, $session->getName()];
    $dataToExport['headers'][] = get_lang('OfficialCode');
    $dataToExport['headers'][] = get_lang('StudentName');
    $dataToExport['headers'][] = get_lang('TimeSpentOnThePlatform');
    $dataToExport['headers'][] = get_lang('FirstLoginInPlatform');
    $dataToExport['headers'][] = get_lang('LatestLoginInPlatform');

    foreach ($coursesInfo as $courseCode) {
        $dataToExport['headers'][] = $courseCode. ' ('.get_lang('BestScore').')';
        $dataToExport['headers'][] = get_lang('Progress');
        $dataToExport['headers'][] = get_lang('LastSentWorkDate');
    }

    foreach ($usersInfo as $user) {
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
    'url' => api_get_path(WEB_CODE_PATH) . 'mySpace/index.php',
    'name' => get_lang('MySpace')
];

$actions = null;

if ($session) {
    $actions = Display::url(
        Display::return_icon('export_csv.png', get_lang('ExportAsCSV'), [], ICON_SIZE_MEDIUM),
        api_get_self() . '?' . http_build_query(['export' => 'csv', 'session' => $session->getId()])
    );
    $actions .=Display::url(
        Display::return_icon('export_excel.png', get_lang('ExportAsXLS'), [], ICON_SIZE_MEDIUM),
        api_get_self() . '?' . http_build_query(['export' => 'xls', 'session' => $session->getId()])
    );
}

$view = new Template($toolName);
$view->assign('form', $form->returnForm());

if ($session) {
    $view->assign('session', ['name' => $session->getName()]);
    $view->assign('courses', $coursesInfo);
    $view->assign('users', $usersInfo);
}

$template = $view->get_template('my_space/works_in_session_report.tpl');
$content = $view->fetch($template);

$view->assign('header', $toolName);

if ($actions) {
    $view->assign(
        'actions',
        Display::toolbarAction('toolbar', [$actions])
    );
}

$view->assign('content', $content);
$view->display_one_col_template();
