<?php

/* For licensing terms, see /license.txt */

require_once __DIR__.'/../inc/global.inc.php';
$this_section = SECTION_COURSES;

api_protect_course_script(true);

$isAllowedToEdit = api_is_allowed_to_edit(null, true);

if (!$isAllowedToEdit) {
    api_not_allowed(true);
}

$exerciseId = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;
$exportXls = isset($_GET['export_xls']) && !empty($_GET['export_xls']) ? (int) $_GET['export_xls'] : 0;
if (empty($exerciseId)) {
    api_not_allowed(true);
}

$sessionId = api_get_session_id();

$exercise = new Exercise();
$result = $exercise->read($exerciseId);

if (empty($result)) {
    api_not_allowed(true);
}

$nameTools = get_lang('ExerciseManagement');
$interbreadcrumb[] = [
    'url' => 'exercise.php?'.api_get_cidreq(),
    'name' => get_lang('Exercises'),
];
$interbreadcrumb[] = [
    'url' => 'admin.php?exerciseId='.$exercise->iid.'&'.api_get_cidreq(),
    'name' => $exercise->selectTitle(true),
];

$interbreadcrumb[] = [
    'url' => 'exercise_report.php?'.api_get_cidreq().'&exerciseId='.$exercise->iid,
    'name' => get_lang('StudentScore'),
];

$courseId = api_get_course_int_id();
$courseInfo = api_get_course_info();

$table = new HTML_Table(['class' => 'table table-hover table-striped']);
$row = 0;
$column = 0;

$headers = [
    get_lang('Group'),
    get_lang('AverageScore'),
];

foreach ($headers as $header) {
    $table->setHeaderContents($row, $column, $header);
    $column++;
}
if ($exportXls) {
    $tableXls[] = $headers;
}
$row++;
$scoreDisplay = new ScoreDisplay();

$groups = GroupManager::get_group_list(null, $courseInfo);
if (!empty($groups)) {
    foreach ($groups as $group) {
        $average = ExerciseLib::get_average_score($exerciseId, $courseId, $sessionId, $group['iid']);
        $table->setCellContents($row, 0, $group['name']);
        $averageToDisplay = $scoreDisplay->display_score([$average, 1], SCORE_AVERAGE);
        $table->setCellContents($row, 1, $averageToDisplay);
        if ($exportXls) {
            $tableXls[] = [$group['name'], $averageToDisplay];
        }
        $row++;
    }
}
if ($exportXls) {
    $fileName = get_lang('ComparativeGroupReport').'_'.api_get_course_id().'_'.$exerciseId.'_'.api_get_local_time();
    Export::arrayToXls($tableXls, $fileName);
    exit;
}
Display::display_header($nameTools, get_lang('Exercise'));
$actions = '<a href="exercise_report.php?exerciseId='.$exerciseId.'&'.api_get_cidreq().'">'.
    Display::return_icon(
        'back.png',
        get_lang('GoBackToQuestionList'),
        '',
        ICON_SIZE_MEDIUM
    )
    .'</a>';
$actions .= Display::url(
    Display::return_icon('excel.png', get_lang('ExportAsXLS'), [], ICON_SIZE_MEDIUM),
    'comparative_group_report.php?id='.$exerciseId.'&export_xls=1&'.api_get_cidreq()
);

$actions = Display::div($actions, ['class' => 'actions']);
echo $actions;
echo $table->toHtml();
Display::display_footer();
