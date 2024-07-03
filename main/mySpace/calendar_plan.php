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
$currentOrder = isset($_GET['order']) && 'desc' === $_GET['order'] ? 'desc' : 'asc';

if (api_is_western_name_order()) {
    $orderBy = "firstname";
} else {
    $orderBy = "lastname";
}

$students = UserManager::getUsersFollowedByUser(
    api_get_user_id(),
    STUDENT,
    false,
    false,
    false,
    null,
    null,
    $orderBy,
    $currentOrder,
    null,
    null,
    api_is_student_boss() ? STUDENT_BOSS : COURSEMANAGER
);

if ('desc' === $currentOrder) {
    $order = 'asc';
} else {
    $order = 'desc';
}

$userInfo = api_get_user_info();
$userId = $userInfo['id'];

$globalColors = ChamiloApi::getColorPalette(false, true, 500);
$sessionColors = [];
$sessionColorName = [];

foreach ($students as &$student) {
    $student = api_get_user_info($student['user_id']);
    $sessionsList = UserManager::getSubscribedSessionsByYear($student, $searchYear);
    $sessions = UserManager::getSessionsCalendarByYear($sessionsList, $searchYear);
    $personalColors = [];
    $counter = 0;
    foreach ($sessions as &$session) {
        if (!isset($sessionColors[$session['id']])) {
            $session['color'] = $globalColors[$counter];
            $sessionColors[$session['id']] = $session['color'];
            $sessionColorName[$session['color']] = $session['name'];
        } else {
            $session['color'] = $sessionColors[$session['id']];
        }
        $counter++;
    }
    $student['sessions'] = $sessions;
    //$colors = ChamiloApi::getColorPalette(false, true, count($sessions));
    //$student['colors'] = $colors;
}

$table = new HTML_Table(['class' => 'table table-responsive']);
$headers = [
    get_lang('SessionName'),
    get_lang('Color'),
];
$row = 0;
$column = 0;
foreach ($headers as $header) {
    $table->setHeaderContents($row, $column, $header);
    $column++;
}
$row++;
foreach ($sessionColorName as $color => $name) {
    $table->setCellContents($row, 0, $name);
    $table->setCellContents($row, 1, "<div style='background:$color '>&nbsp;</div>");
    $row++;
}

$agenda = new Agenda('personal');
$actions = $agenda->displayActions('list', $userId);

$toolName = get_lang('SessionsPlanCalendar');

$template = new Template($toolName);
$template->assign('toolbar', $actions);
$template->assign('student_id', $userId);
$template->assign('search_year', $searchYear);
$template->assign('students', $students);
$template->assign('legend', $table->toHtml());
$template->assign('order', $order);
$template->assign('current_order', $currentOrder);

$layout = $template->get_template('agenda/student_boss_planification.tpl');
$template->display($layout);
