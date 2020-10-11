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
$TBL_USER = Database::get_main_table(TABLE_MAIN_USER);
$TBL_EXERCISES = Database::get_course_table(TABLE_QUIZ_TEST);
$questionTable = Database::get_course_table(TABLE_QUIZ_QUESTION);
$attemptTable = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);
$trackTable = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
$courseId = api_get_course_int_id();

$table = new HTML_Table(['class' => 'table table-hover table-striped']);
$row = 0;
$column = 0;
$headers = [get_lang('Question')];
//$headers = [get_lang('Question'), get_lang('Count')];
foreach ($headers as $header) {
    $table->setHeaderContents($row, $column, $header);
    $column++;
}
$row++;

$sql = "SELECT q.question, question_id, count(q.iid) count
        FROM $attemptTable t
        INNER JOIN $questionTable q
        ON (q.c_id = t.c_id AND q.id = t.question_id)
        INNER JOIN $trackTable te
        ON (te.c_id = q.c_id AND t.exe_id = te.exe_id)
        WHERE
            t.c_id = $courseId AND
            t.marks != q.ponderation AND
            exe_exo_id = $exerciseId
        GROUP BY q.iid
        ORDER BY count DESC
        LIMIT 10
        ";
$query = Database::query($sql);
while ($data = Database::fetch_array($query, 'ASSOC')) {
    /*$questionId = $row['question_id'];
    $question = Question::read($row['question_id']);
    $exeId = $row['exe_id'];
    $answer = $row['answer'];
    $row['question_id'];*/
    /*$data = $exercise->manage_answer(
        $exeId,
        $questionId,
        $answer,
        $from = 'exercise_result',
        [],
        false,
        true,
        false
    );*/
    $table->setCellContents($row, 0, $data['question']);
    //$table->setCellContents($row, 1, $data['count']);
    $row++;
}

Display::display_header($nameTools, get_lang('Exercise'));
echo $table->toHtml();
Display::display_footer();
