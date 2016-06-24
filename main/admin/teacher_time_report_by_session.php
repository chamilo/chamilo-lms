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

$form = new FormValidator('teacher_time_report_by_session');
$selectSession = $form->addSelect('session', get_lang('Session'), [0 => get_lang('None')]);
$form->addButtonFilter(get_lang('Filter'));

foreach ($sessionsInfo as $sessionInfo) {
    $selectSession->addOption($sessionInfo['name'], $sessionInfo['id']);
}

if ($form->validate()) {
    $sessionId = $form->exportValue('session');
    $session = $em
        ->find('ChamiloCoreBundle:Session', $sessionId);
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

$this_section = SECTION_PLATFORM_ADMIN;
$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('Administration')];
$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH) . 'admin/teacher_time_report.php',
    'name' => get_lang('TeacherTimeReport')
];
$toolName = get_lang('TeacherTimeReportBySession');

$view = new Template($toolName);
$view->assign('form', $form->returnForm());

if ($session) {
    $view->assign('session', ['id' => $session->getId(), 'name' => $session->getName()]);
    $view->assign('data', $data);
}

$template = $view->get_template('admin/teacher_time_report_by_session.tpl');
$content = $view->fetch($template);

$view->assign('header', $toolName);
$view->assign('content', $content);
$view->display_one_col_template();
