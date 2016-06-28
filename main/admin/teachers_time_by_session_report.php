<?php
/* For licensing terms, see /license.txt */

/**
 * Generate a teacher time report in platform by session only
 * @package chamilo.admin
 */
$cidReset = true;

require_once '../inc/global.inc.php';
require_once api_get_path(SYS_CODE_PATH) . 'work/work.lib.php';

api_protect_admin_script();

$em = Database::getManager();
$sessionsInfo = SessionManager::get_sessions_list([], ['name']);
$session = null;

$form = new FormValidator('teacher_time_report_by_session', 'GET');
$selectSession = $form->addSelect('session', get_lang('Session'), [0 => get_lang('None')]);
$form->addButtonFilter(get_lang('Filter'));

foreach ($sessionsInfo as $sessionInfo) {
    $selectSession->addOption($sessionInfo['name'], $sessionInfo['id']);
}

if (isset($_GET['session']) && intval($_GET['session'])) {
    $form->setDefaults(['session' => intval($_GET['session'])]);

    $session = $em->find('ChamiloCoreBundle:Session', intval($_GET['session']));
}

$data = [];

if ($session) {
    $sessionCourses = $session->getCourses();

    foreach ($sessionCourses as $sessionCourse) {
        $course = $sessionCourse->getCourse();
        $userCourseSubscriptions = $session->getUserCourseSubscriptionsByStatus(
            $course,
            \Chamilo\CoreBundle\Entity\Session::COACH
        );

        foreach ($userCourseSubscriptions as $userCourseSubscription) {
            $user = $userCourseSubscription->getUser();

            if (!array_key_exists($user->getId(), $data)) {
                $data[$user->getId()] = [
                    'code' => $user->getOfficialCode(),
                    'complete_name' => $user->getCompleteName(),
                    'time_in_platform' => api_time_to_hms(
                        Tracking::get_time_spent_on_the_platform($user->getId())
                    ),
                    'first_connection' => Tracking::get_first_connection_date($user->getId()),
                    'last_connection' => Tracking::get_last_connection_date($user->getId()),
                    'courses' => []
                ];
            }

            if (array_key_exists($course->getId(), $data[$user->getId()]['courses'])) {
                continue;
            }

            $works = $em
                ->getRepository('ChamiloCourseBundle:CStudentPublication')
                ->findByTeacher($user, $course, $session->getId());
            $lastWork = array_pop($works);
            $lastFormattedDate = null;

            if ($lastWork) {
                $lastFormattedDate = api_format_date($lastWork->getSentDate()->getTimestamp(), DATE_TIME_FORMAT_SHORT);
            }

            $data[$user->getId()]['courses'][$course->getId()] = [
                'id' => $course->getId(),
                'title' => $course->getTitle(),
                'code' => $course->getCode(),
                'number_of_students' => $sessionCourse->getNbrUsers(),
                'number_of_works' => count($works),
                'last_work' => $lastFormattedDate,
                'time_spent_of_course' => api_time_to_hms(
                    Tracking::get_time_spent_on_the_course(
                        $user->getId(),
                        $course->getId(),
                        $session->getId()
                    )
                )
            ];
        }
    }
}

if (isset($_GET['export']) && $session && $data) {
    $dataToExport = [];
    $fileName = get_lang('TeacherTimeReport') . ' ' . api_get_local_time();

    foreach ($data as $row) {
        $headers = [
            get_lang('OfficialCode'),
            get_lang('Name'),
            get_lang('TimeSpentOnThePlatform'),
            get_lang('FirstLoginInPlatform'),
            get_lang('LatestLoginInPlatform')
        ];
        $contents = [
            $row['code'],
            $row['complete_name'],
            $row['time_in_platform'],
            $row['first_connection'],
            $row['last_connection']
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
$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('Administration')];
$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH) . 'admin/teacher_time_report.php',
    'name' => get_lang('TeacherTimeReport')
];
$toolName = get_lang('TeacherTimeReportBySession');

$actions = [];

if ($session) {
    $actions = [
        Display::url(
            Display::return_icon('export_csv.png', get_lang('ExportAsCSV'), [], ICON_SIZE_MEDIUM),
            api_get_self() . '?' . http_build_query(['export' => 'csv', 'session' => $session->getId()])
        ),
        Display::url(
            Display::return_icon('export_excel.png', get_lang('ExportAsXLS'), [], ICON_SIZE_MEDIUM),
            api_get_self() . '?' . http_build_query(['export' => 'xls', 'session' => $session->getId()])
        )
    ];
}

$view = new Template($toolName);
$view->assign('form', $form->returnForm());

if ($session) {
    $view->assign('session', ['id' => $session->getId(), 'name' => $session->getName()]);
    $view->assign('data', $data);
}

$template = $view->get_template('admin/teachers_time_by_session_report.tpl');
$content = $view->fetch($template);

$view->assign('header', $toolName);
$view->assign('actions', implode(' ', $actions));
$view->assign('content', $content);
$view->display_one_col_template();
