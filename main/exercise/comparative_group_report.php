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
$row++;
$scoreDisplay = new ScoreDisplay();

$groups = GroupManager::get_group_list(null, $courseInfo);
if (!empty($groups)) {
    foreach ($groups as $group) {
        $average = ExerciseLib::get_average_score($exerciseId, $courseId, $sessionId, $group['iid']);
        $table->setCellContents($row, 0, $group['name']);
        $averageToDisplay = $scoreDisplay->display_score([$average, 1], SCORE_AVERAGE);
        $table->setCellContents($row, 1, $averageToDisplay);
        $row++;
    }
}

Display::display_header($nameTools, get_lang('Exercise'));
echo $table->toHtml();
Display::display_footer();
