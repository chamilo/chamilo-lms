<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CourseBundle\Entity\Repository\CStudentPublicationRepository;
use Doctrine\Common\Collections\Criteria;

/**
 * Generate a teacher time report in platform by session only.
 */
$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';
require_once api_get_path(SYS_CODE_PATH).'work/work.lib.php';

if (!api_is_platform_admin(true) && !api_is_teacher()) {
    api_not_allowed(true);
}

$toolName = get_lang('TeacherTimeReportBySession');

$em = Database::getManager();
$sessions = [];
if (api_is_platform_admin()) {
    $sessions = SessionManager::get_sessions_list();
} elseif (api_is_session_admin()) {
    $sessions = SessionManager::getSessionsFollowedByUser(api_get_user_id(), SESSIONADMIN);
} else {
    $sessions = Tracking::get_sessions_coached_by_user(api_get_user_id());
}
$session = null;

$form = new FormValidator('teacher_time_report_by_session', 'GET');
$selectSession = $form->addSelect(
    'session',
    get_lang('Session'),
    [0 => get_lang('None')]
);
$form->addButtonFilter(get_lang('Filter'));

foreach ($sessions as $sessionInfo) {
    $selectSession->addOption($sessionInfo['name'], $sessionInfo['id']);
}

if (isset($_GET['session']) && intval($_GET['session'])) {
    $form->setDefaults(['session' => intval($_GET['session'])]);
    $session = api_get_session_entity($_GET['session']);
}

$data = [];
$coursesInfo = [];
$usersInfo = [];

if ($session) {
    $sessionCourses = $session->getCourses();

    foreach ($sessionCourses as $sessionCourse) {
        $course = $sessionCourse->getCourse();
        $coursesInfo[$course->getId()] = $course->getCode();
        $criteria = Criteria::create()->where(
            Criteria::expr()->eq('status', Session::COACH)
        );
        $userCourseSubscriptions = $session->getUserCourseSubscriptions()->matching($criteria);

        foreach ($userCourseSubscriptions as $userCourseSubscription) {
            $user = $userCourseSubscription->getUser();
            if (!array_key_exists($user->getId(), $usersInfo)) {
                $usersInfo[$user->getId()] = [
                    'code' => $user->getOfficialCode(),
                    'complete_name' => UserManager::formatUserFullName($user),
                    'time_in_platform' => api_time_to_hms(
                        Tracking::get_time_spent_on_the_platform($user->getId(), 'ever')
                    ),
                    'first_connection' => Tracking::get_first_connection_date($user->getId()),
                    'last_connection' => Tracking::get_last_connection_date($user->getId()),
                ];
            }

            $usersInfo[$user->getId()][$course->getId().'_number_of_students'] = null;
            $usersInfo[$user->getId()][$course->getId().'_number_of_works'] = null;
            $usersInfo[$user->getId()][$course->getId().'_last_work'] = null;
            $usersInfo[$user->getId()][$course->getId().'_time_spent_of_course'] = null;

            if (!$session->hasCoachInCourseWithStatus($user, $course)) {
                continue;
            }

            /** @var CStudentPublicationRepository $studentPubRepo */
            $studentPubRepo = $em->getRepository('ChamiloCourseBundle:CStudentPublication');
            $works = $studentPubRepo->findWorksByTeacher($user, $course, $session);

            $usersInfo[$user->getId()][$course->getId().'_number_of_students'] = $sessionCourse->getNbrUsers();
            $usersInfo[$user->getId()][$course->getId().'_number_of_works'] = count($works);
            $usersInfo[$user->getId()][$course->getId().'_time_spent_of_course'] = api_time_to_hms(
                Tracking::get_time_spent_on_the_course($user->getId(), $course->getId(), $session->getId())
            );

            $lastWork = array_pop($works);

            if (!$lastWork) {
                continue;
            }

            $usersInfo[$user->getId()][$course->getId().'_last_work'] = api_get_local_time($lastWork->getSentDate()->getTimestamp());
        }
    }
}

if (isset($_GET['export']) && $session && ($coursesInfo && $usersInfo)) {
    $fileName = get_lang('TeacherTimeReport').' '.api_get_local_time();

    $dataToExport = [];
    $dataToExport[] = [$toolName, $session->getName()];
    $dataToExport['headers'] = [
        get_lang('OfficialCode'),
        get_lang('CoachName'),
        get_lang('TimeSpentOnThePlatform'),
        get_lang('FirstLoginInPlatform'),
        get_lang('LatestLoginInPlatform'),
    ];

    foreach ($coursesInfo as $courseCode) {
        $dataToExport['headers'][] = $courseCode;
        $dataToExport['headers'][] = get_lang('NumberOfWorks');
        $dataToExport['headers'][] = get_lang('LastWork');
        $dataToExport['headers'][] = sprintf(get_lang('TimeReportForCourseX'), $courseCode);
    }

    foreach ($usersInfo as $user) {
        $dataToExport[] = $user;
    }

    foreach ($data as $row) {
        $contents = [
            $row['code'],
            $row['complete_name'],
            $row['time_in_platform'],
            $row['first_connection'],
            $row['last_connection'],
        ];

        foreach ($row['courses'] as $course) {
            $headers[] = $course['code'];
            $headers[] = get_lang('NumberOfWorks');
            $headers[] = get_lang('LastWork');
            $headers[] = sprintf(get_lang('TimeReportForCourseX'), $course['code']);
            $contents[] = $course['number_of_students'];
            $contents[] = $course['number_of_works'];
            $contents[] = $course['last_work'];
            $contents[] = $course['time_spent_of_course'];
        }

        $dataToExport[] = [get_lang('Session'), $session->getName()];
        $dataToExport[] = $headers;
        $dataToExport[] = $contents;
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

$this_section = SECTION_PLATFORM_ADMIN;
$interbreadcrumb[] = ['url' => api_get_path(WEB_CODE_PATH).'mySpace/', 'name' => get_lang('Reporting')];
$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'mySpace/session.php',
    'name' => get_lang('FollowedSessions'),
];

$view = new Template($toolName);
$view->assign('form', $form->returnForm());

if ($session) {
    $view->assign('session', ['id' => $session->getId(), 'name' => $session->getName()]);
    $view->assign('courses', $coursesInfo);
    $view->assign('users', $usersInfo);

    $actions = Display::url(
        Display::return_icon('export_csv.png', get_lang('ExportAsCSV'), [], ICON_SIZE_MEDIUM),
        api_get_self().'?'.http_build_query(['export' => 'csv', 'session' => $session->getId()])
    );
    $actions .= Display::url(
        Display::return_icon('export_excel.png', get_lang('ExportAsXLS'), [], ICON_SIZE_MEDIUM),
        api_get_self().'?'.http_build_query(['export' => 'xls', 'session' => $session->getId()])
    );

    $view->assign(
        'actions',
        Display::toolbarAction('toolbar', [$actions])
    );
}

$template = $view->get_template('admin/teachers_time_by_session_report.tpl');
$content = $view->fetch($template);

$view->assign('header', $toolName);
$view->assign('content', $content);
$view->display_one_col_template();
