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
    'url' => 'admin.php?exerciseId='.$exercise->iId.'&'.api_get_cidreq(),
    'name' => $exercise->selectTitle(true),
];

$interbreadcrumb[] = [
    'url' => 'exercise_report.php?'.api_get_cidreq().'&exerciseId='.$exercise->iId,
    'name' => get_lang('StudentScore'),
];
$courseId = api_get_course_int_id();

$table = new HTML_Table(['class' => 'table table-hover table-striped']);
$row = 0;
$column = 0;
$headers = [get_lang('Question'), get_lang('WrongAnswer').' / '.get_lang('Total'), '%'];
foreach ($headers as $header) {
    $table->setHeaderContents($row, $column, $header);
    $column++;
}
$row++;
$scoreDisplay = new ScoreDisplay();
$questions = ExerciseLib::getWrongQuestionResults($courseId, $exerciseId, api_get_session_id());
foreach ($questions as $data) {
    $questionId = (int) $data['question_id'];
    $total = ExerciseLib::getTotalQuestionAnswered($courseId, $exerciseId, $questionId);
    $table->setCellContents($row, 0, $data['question']);
    $table->setCellContents($row, 1, $data['count'].' / '.$total);
    $table->setCellContents($row, 2, $scoreDisplay->display_score([$data['count'], $total], SCORE_AVERAGE));
    $row++;
}

Display::display_header($nameTools, get_lang('Exercise'));
echo $table->toHtml();
Display::display_footer();
