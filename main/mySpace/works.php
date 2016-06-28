<?php
/* For licensing terms, see /license.txt */
/**
 * Courses reporting
 * @package chamilo.reporting
 */

require_once '../inc/global.inc.php';

use \Chamilo\CoreBundle\Entity\Session;
use \Doctrine\Common\Collections\Criteria;

api_block_anonymous_users(true);

if (api_is_student()) {
    api_not_allowed(true);
    exit;
}

$em = Database::getManager();
$session = null;
$sessionsInfo = SessionManager::getSessionsFollowedByUser(api_get_user_id(), COURSEMANAGER);

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

$coursesInfo = [];
$usersInfo = [];

if ($session) {
    $sessionCourses = $session->getCourses();

    foreach ($sessionCourses as $sessionCourse) {
        $course = $sessionCourse->getCourse();
        $coursesInfo[$course->getId()] =  $course->getCode();
        $criteria = Criteria::create()->where(
            Criteria::expr()->eq("status", Session::STUDENT)
        );
        $userCourseSubscriptions = $session
            ->getUserCourseSubscriptions()
            ->matching($criteria);

        foreach ($userCourseSubscriptions as $userCourseSubscription) {
            $user = $userCourseSubscription->getUser();

            if (!array_key_exists($user->getId(), $usersInfo)) {
                $usersInfo[$user->getId()] = [
                    'code' => $user->getOfficialCode(),
                    'complete_name' => $user->getCompleteName(),
                    'time_in_platform' => api_time_to_hms(
                        Tracking::get_time_spent_on_the_platform($user->getId())
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
                $session->getId()
            );
            $usersInfo[$user->getId()][$course->getId() . '_progress'] = Tracking::get_avg_student_progress(
                $user->getId(),
                $course->getCode(),
                null,
                $session->getId()
            );

            $lastPublication = Tracking::getLastStudentPublication($user, 'work', $course, $session);

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

$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH) . 'mySpace/index.php',
    'name' => get_lang('MySpace')
];

$toolName = get_lang('WorkReport');

$view = new Template($toolName);
$view->assign('form', $form->returnForm());

if ($session) {
    $view->assign('session', ['name' => $session->getName()]);
    $view->assign('courses', $coursesInfo);
    $view->assign('users', $usersInfo);
}

$template = $view->get_template('my_space/works.tpl');
$content = $view->fetch($template);

$view->assign('header', $toolName);
$view->assign('content', $content);
$view->display_one_col_template();
