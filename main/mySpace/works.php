<?php
/* For licensing terms, see /license.txt */
/**
 * Courses reporting
 * @package chamilo.reporting
 */

require_once '../inc/global.inc.php';

if (api_is_student()) {
    api_not_allowed(true);
    exit;
}

$em = Database::getManager();
$session = null;
$sessionsInfo = SessionManager::getSessionsFollowedByUser(api_get_user_id(), COURSEMANAGER);
$coursesData = [];

$form = new FormValidator('work_report');
$selectSession = $form->addSelect('session', get_lang('Session'), [0 => get_lang('None')]);
$form->addButtonFilter(get_lang('Filter'));

foreach ($sessionsInfo as $sessionInfo) {
    $selectSession->addOption($sessionInfo['name'], $sessionInfo['id']);
}

if ($form->validate()) {
    $sessionId = $form->exportValue('session');
    $session = $em->find('ChamiloCoreBundle:Session', $sessionId);
}

if ($session) {
    $userSubscriptions = $session->getUsers();
    $sessionCourses = $session->getCourses();

    foreach ($sessionCourses as $sessionCourse) {
        $course = $sessionCourse->getCourse();
        $userCourseSubscriptions = $session->getUserCourseSubscriptionsByStatus($course, 0);
        $courseInfo = [
            'title' => $course->getTitle()
        ];

        $table = new HTML_Table(['class' => 'table table-hover table-striped']);
        $table->setHeaderContents(0, 0, get_lang('OfficialCode'));
        $table->setHeaderContents(0, 1, get_lang('StudentName'));
        $table->setHeaderContents(0, 2, get_lang('TimeSpentOnThePlatform'));
        $table->setHeaderContents(0, 3, get_lang('FirstLoginInPlatform'));
        $table->setHeaderContents(0, 4, get_lang('LatestLoginInPlatform'));
        $table->setHeaderContents(0, 5, get_lang('Course'));
        $table->setHeaderContents(0, 6, get_lang('Progress'));
        $table->setHeaderContents(0, 7, get_lang('SentDate'));

        foreach ($userCourseSubscriptions as $userCourseSubscription) {
            $user = $userCourseSubscription->getUser();
            
            $lastPublication = Tracking::getLastStudentPublication($user, 'work', $course, $session);
            $lastPublicationFormatted = null;

            if ($lastPublication) {
                $lastPublicationFormatted = api_format_date(
                    $lastPublication->getSentDate()->getTimestamp(),
                    DATE_TIME_FORMAT_SHORT
                );
            }

            $data = [
                $user->getOfficialCode(),
                $user->getCompleteName(),
                api_time_to_hms(
                    Tracking::get_time_spent_on_the_platform($user->getId())
                ),
                Tracking::get_first_connection_date($user->getId()),
                Tracking::get_last_connection_date($user->getId()),
                Tracking::get_avg_student_score($user->getId(), $course->getCode(), null, $session->getId()),
                Tracking::get_avg_student_progress($user->getId(), $course->getCode(), null, $session->getId()),
                $lastPublicationFormatted
            ];

            $table->addRow($data);
        }

        $coursesData[] = [
            'title' => $course->getTitle(),
            'detail_table' => $table->toHtml()
        ];
    }
}

$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH) . 'mySpace/index.php',
    'name' => get_lang('MySpace')
];

$view = new Template(get_lang('WorkReport'));
$view->assign('header', get_lang('WorkReport'));
$view->assign('form', $form->returnForm());

if ($session) {
    $view->assign('session', ['name' => $session->getName()]);
    $view->assign('courses', $coursesData);
}

$template = $view->get_template('my_space/works.tpl');
$content = $view->fetch($template);
$view->assign('content', $content);
$view->display_one_col_template();
