<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Component\Utils\ChamiloApi;

$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

api_block_anonymous_users();

$studentList = UserManager::getUsersFollowedByStudentBoss(api_get_user_id());

$current_course_tool = TOOL_CALENDAR_EVENT;
$this_section = SECTION_MYAGENDA;

$timezone = new DateTimeZone(api_get_timezone());
$now = new DateTime('now', $timezone);
$currentYear = (int) $now->format('Y');

$searchYear = isset($_GET['year']) ? (int) $_GET['year'] : $currentYear;

$students = UserManager::getUsersFollowedByUser(
    api_get_user_id(),
    STUDENT,
    false,
    false,
    false,
    null,
    null,
    null,
    null,
    null,
    null,
    api_is_student_boss() ? STUDENT_BOSS : COURSEMANAGER
);

$userInfo = api_get_user_info();
$userId = $userInfo['id'];

foreach ($students as &$student) {
    $student = api_get_user_info($student['user_id']);
    $sessionsList = UserManager::getSubscribedSessionsByYear($student, $searchYear);
    $sessions = UserManager::getSessionsCalendarByYear($sessionsList, $searchYear);
    $student['sessions'] = $sessions;
    $colors = ChamiloApi::getColorPalette(false, true, count($sessions));
    $student['colors'] = $colors;
}

$agenda = new Agenda('personal');
$actions = $agenda->displayActions('list', $userId);

$toolName = get_lang('SessionsPlanCalendar');

$template = new Template($toolName);
$template->assign('toolbar', $actions);
$template->assign('student_id', $userId);
$template->assign('search_year', $searchYear);
$template->assign('students', $students);
$layout = $template->get_template('agenda/student_boss_planification.tpl');
$template->display($layout);
