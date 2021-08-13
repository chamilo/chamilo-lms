<?php

/* For licensing terms, see /license.txt */

/**
 * This script fixes use of id instead of iid for the exercises
 * The main issue fixed by this script is that c_quiz.id, c_quiz_question.id
 * and c_quiz_answer.id used to be different from the iid field in those same
 * tables, but has been the same since about 2016.
 * This script goes through the tables that potentially use this old id as
 * a reference rather than the new iid, and replaces those records with the
 * new iid.
 */

exit;

require_once '../../main/inc/global.inc.php';

/** @var int $courseId */
$onlyCourseId = 0;
/** @var int $quizId quiz id */
$quizId = 0;

$coursesSelect = Database::select('id, code', Database::get_main_table(TABLE_MAIN_COURSE));
$courseCodes = [];
foreach($coursesSelect as $key => $value) {
    $courseCodes[$value['id']] = $value['code'];
}
ksort($courseCodes);
$tblCQuiz = Database::get_course_table(TABLE_QUIZ_TEST);
$tblCQuizQuestion = Database::get_course_table(TABLE_QUIZ_QUESTION);
$tblCQuizAnswer = Database::get_course_table(TABLE_QUIZ_ANSWER);
$tblCItemProperty = Database::get_course_table(TABLE_ITEM_PROPERTY);
$tblTrackExercises = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
$tblTrackAttempt = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);
$tblTrackAttemptRecording = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT_RECORDING);
$tblCLpItem = Database::get_course_table(TABLE_LP_ITEM);
$tblGradebookLink = Database::get_main_table(TABLE_MAIN_GRADEBOOK_LINK);
$tblCQuizQuestionOption = Database::get_course_table(TABLE_QUIZ_QUESTION_OPTION);
$tblCQuizQuestionRelCategory = Database::get_course_table(TABLE_QUIZ_QUESTION_REL_CATEGORY);
$tblCQuizRelCategory = Database::get_course_table(TABLE_QUIZ_REL_CATEGORY);
$tblCQuizRelQuestion = Database::get_course_table(TABLE_QUIZ_TEST_QUESTION);

$exercises = [];
$sql = "SELECT id, iid, c_id, session_id FROM $tblCQuiz WHERE id != iid ORDER BY iid";
$result = Database::query($sql);
while ($row = Database::fetch_assoc($result)) {
    $mySession = $row['session_id'];
    if (empty($mySession)) {
        $mySession = 0;
    }
    $exercises[$row['c_id'].'-'.$row['id'].'-'.$mySession] = [
        'id' => $row['id'],
        'iid' => $row['iid'],
        'c_id' => $row['c_id'],
        's_id' => $mySession,
    ];
}
//print_r($exercises);
$questions = [];
$sql = "SELECT id, iid, c_id FROM $tblCQuizQuestion WHERE id != iid ORDER BY iid";
$result = Database::query($sql);
while ($row = Database::fetch_assoc($result)) {
    $questions[$row['c_id'].'-'.$row['id']] = [
        'iid' => $row['iid'],
        'id' => $row['id'],
        'c_id' => $row['c_id'],
    ];
}
//print_r($questions);
$answers = [];
$sql = "SELECT id, iid, c_id, question_id FROM $tblCQuizAnswer WHERE id != iid ORDER BY iid";
$result = Database::query($sql);
while ($row = Database::fetch_assoc($result)) {
    $answers[$row['c_id'].'-'.$row['id']] = [
        'id' => $row['id'],
        'iid' => $row['iid'],
        'c_id' => $row['c_id'],
        'q_id' => $row['question_id'],
    ];
}
//print_r($answers);

// We have to treat each of the following tables, and replace the contents of
// the field which contains the id by the corresponding iid
// c_item_property, exercises, ref field
$sqlTemplate = "UPDATE $tblCItemProperty SET ref = %d WHERE tool = 'quiz' AND lastedit_type IN (%s) AND c_id = %d AND ref = %d";
foreach ($exercises as $key => $value) {
    $sql = sprintf($sqlTemplate, $value['iid'], "'QuizDeleted', 'QuizUpdated'", $value['c_id'], $value['id']);
    if ($value['s_id'] === 0) {
        $sql .= " AND (session_id = 0 OR session_id IS NULL)";
    } else {
        $sql .= " AND session_id = ".$value['s_id'];
    }
    //echo($sql.PHP_EOL);
    Database::query($sql);
}
// c_item_property, question, ref field
foreach ($questions as $key => $value) {
    $sql = sprintf($sqlTemplate, $value['iid'], "'QuizQuestionUpdated', 'QuizQuestionDeleted'", $value['c_id'], $value['id']);
    //echo($sql.PHP_EOL);
    Database::query($sql);
}
// track_e_exercises, exercises, exe_exo_id field
$sqlTemplate = "UPDATE $tblTrackExercises SET exe_exo_id = %d WHERE c_id = %d AND exe_exo_id = %d";
foreach ($exercises as $key => $value) {
    $sql = sprintf($sqlTemplate, $value['iid'], $value['c_id'], $value['id']);
    //echo($sql.PHP_EOL);
    Database::query($sql);
}
// track_e_attempt, question *AND* answer, question_id and answer fields
$sqlTemplate = "UPDATE $tblTrackAttempt SET answer = '%s' WHERE c_id = %d AND question_id = %d AND answer = '%s'";
foreach ($answers as $key => $value) {
    $sql = sprintf($sqlTemplate, $value['iid'], $value['c_id'], $value['q_id'], $value['id']);
    //echo($sql.PHP_EOL);
    Database::query($sql);
}
$sqlTemplate = "UPDATE $tblTrackAttempt SET question_id = %d WHERE c_id = %d AND question_id = %d";
foreach ($questions as $key => $value) {
    $sql = sprintf($sqlTemplate, $value['iid'], $value['c_id'], $value['id']);
    //echo($sql.PHP_EOL);
    Database::query($sql);
}
// track_e_attempt_recording, question, question_id field
// This cannot be done because this table does not contain a c_id field
/*
$sqlTemplate = "UPDATE $tblTrackAttemptRecording SET question_id = %d WHERE AND question_id = %d";
foreach ($questions as $key => $value) {
    $sql = sprintf($sqlTemplate, $value['iid'], $value['c_id'], $value['id']);
    echo($sql.PHP_EOL);
    //Database::query($sql);
}
*/
// c_lp_item, exercises, path field
$sqlTemplate = "UPDATE $tblCLpItem SET path = '%s' WHERE c_id = %d AND path = '%s'";
foreach ($exercises as $key => $value) {
    $sql = sprintf($sqlTemplate, $value['iid'], $value['c_id'], $value['id']);
    //echo($sql.PHP_EOL);
    Database::query($sql);
}
// gradebook_link, exercises, ref_id
$sqlTemplate = "UPDATE $tblGradebookLink SET ref_id = %d WHERE course_code = '%s' AND ref_id = %d";
foreach ($exercises as $key => $value) {
    $sql = sprintf($sqlTemplate, $value['iid'], $courseCodes[$value['c_id']], $value['id']);
    //echo($sql.PHP_EOL);
    Database::query($sql);
}
// c_quiz_answer, question, question_id field
$sqlTemplate = "UPDATE $tblCQuizAnswer SET question_id = %d WHERE c_id = %d AND question_id = %d";
foreach ($questions as $key => $value) {
    $sql = sprintf($sqlTemplate, $value['iid'], $value['c_id'], $value['id']);
    //echo($sql.PHP_EOL);
    Database::query($sql);
}
// c_quiz_question_option, quiz_question, question_id field
$sqlTemplate = "UPDATE $tblCQuizQuestionOption SET question_id = %d WHERE c_id = %d AND question_id = %d";
foreach ($questions as $key => $value) {
    $sql = sprintf($sqlTemplate, $value['iid'], $value['c_id'], $value['id']);
    //echo($sql.PHP_EOL);
    Database::query($sql);
}
// c_quiz_question_rel_category, question, question_id field
$sqlTemplate = "UPDATE $tblCQuizQuestionRelCategory SET question_id = %d WHERE c_id = %d AND question_id = %d";
foreach ($questions as $key => $value) {
    $sql = sprintf($sqlTemplate, $value['iid'], $value['c_id'], $value['id']);
    //echo($sql.PHP_EOL);
    Database::query($sql);
}
// c_quiz_rel_category, exercises, exercise_id field
$sqlTemplate = "UPDATE $tblCQuizRelCategory SET exercise_id = %d WHERE c_id = %d AND exercise_id = %d";
foreach ($exercises as $key => $value) {
    $sql = sprintf($sqlTemplate, $value['iid'], $value['c_id'], $value['id']);
    //echo($sql.PHP_EOL);
    Database::query($sql);
}
// c_quiz_rel_question, exercises and question, exercice_id and question_id fields
$sqlTemplate = "UPDATE $tblCQuizRelQuestion SET exercice_id = %d WHERE c_id = %d AND exercice_id = %d";
foreach ($exercises as $key => $value) {
    $sql = sprintf($sqlTemplate, $value['iid'], $value['c_id'], $value['id']);
    //echo($sql.PHP_EOL);
    Database::query($sql);
}
$sqlTemplate = "UPDATE $tblCQuizRelQuestion SET question_id = %d WHERE c_id = %d AND question_id = %d";
foreach ($questions as $key => $value) {
    $sql = sprintf($sqlTemplate, $value['iid'], $value['c_id'], $value['id']);
    //echo($sql.PHP_EOL);
    Database::query($sql);
}

echo 'Finished';
error_log('Finished');
