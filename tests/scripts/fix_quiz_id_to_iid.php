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

//exit;

require_once '../../main/inc/global.inc.php';

/** @var int $courseId */
$onlyCourseId = 0;
/** @var int $quizId quiz id */
$quizId = 0;

$courses = Database::select('id, code', Database::get_main_table(TABLE_MAIN_COURSE));
$tblCQuiz = Database::get_course_table(TABLE_QUIZ_TEST);
$tblCQuizQuestion = Database::get_course_table(TABLE_QUIZ_QUESTION);
$tblCQuizAnswer = Database::get_course_table(TABLE_QUIZ_ANSWER);

$tblCItemProperty = Database::get_course_table(TABLE_ITEM_PROPERTY);
$tblTrackExercises = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
$tblTrackAttempt = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);
$tblTrackAttemptRecording = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT_RECORDING);
$tblCLpItem = Database::get_course_table(TABLE_LP_ITEM);
$tblCQuizQuestionOption = Database::get_course_table(TABLE_QUIZ_QUESTION_OPTION);
$tblCQuizQuestionRelCategory = Database::get_course_table(TABLE_QUIZ_QUESTION_REL_CATEGORY);
$tblCQuizRelCategory = Database::get_course_table(TABLE_QUIZ_REL_CATEGORY);
$tblCQuizRelQuestion = Database::get_course_table(TABLE_QUIZ_TEST_QUESTION);

$exercises = [];
$sql = "SELECT id, iid, c_id, session_id FROM $tblCQuiz WHERE id != iid";
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
print_r($exercises);
$questions = [];
$sql = "SELECT id, iid, c_id FROM $tblCQuizQuestion WHERE id != iid";
$result = Database::query($sql);
while ($row = Database::fetch_assoc($result)) {
    $questions[$row['c_id'].'-'.$row['id']] = [
        'iid' => $row['iid'],
        'id' => $row['id'],
        'c_id' => $row['c_id'],
    ];
}
print_r($questions);
$answers = [];
$sql = "SELECT id, iid, c_id FROM $tblCQuizAnswer WHERE id != iid";
$result = Database::query($sql);
while ($row = Database::fetch_assoc($result)) {
    $answers[$row['c_id'].'-'.$row['id']] = [
        'id' => $row['id'],
        'iid' => $row['iid'],
        'c_id' => $row['c_id'],
    ];
}
print_r($answers);

exit();

// We have to treat each of the following tables, and replace the contents of
// the field which contains the id by the corresponding iid

// 1. c_item_property, quiz, QuizDeleted
$sql = "SELECT iid, c_id, ref FROM $tblCItemProperty WHERE tool = 'quiz' AND lastedit_type = 'QuizDeleted' AND c_id = %d AND ref = %d";
foreach ($exercises as $key => $value) {
    
}


echo 'finished';
error_log('finished');
