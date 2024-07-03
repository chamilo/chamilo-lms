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

// to avoid duplication we first :
// create a done_e column to mark that exercise id modification has been done
// create a done_q column to mark that question id modification has been done
// at the end we will drop those column
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
echo "(".date('Y-m-d H:i:s').") Work arrays with quiz details prepared".PHP_EOL;
// We have to treat each of the following tables, and replace the contents of
// the field which contains the id by the corresponding iid
// c_item_property, exercises, ref field
$sqlAlter = "ALTER table $tblCItemProperty ADD COLUMN done_e int NOT NULL default 0";
Database::query($sqlAlter);
$sqlTemplate = "UPDATE $tblCItemProperty SET ref = %d, done_e = 1 WHERE tool = 'quiz' AND lastedit_type IN (%s) AND c_id = %d AND ref = %d and done_e = 0";
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
$sqlAlter = "ALTER table $tblCItemProperty DROP COLUMN done_e";
Database::query($sqlAlter);
$sqlAlter = "ALTER table $tblCItemProperty ADD COLUMN done_q int NOT NULL default 0";
Database::query($sqlAlter);
$sqlTemplate = "UPDATE $tblCItemProperty SET ref = %d, done_q = 1 WHERE tool = 'quiz' AND lastedit_type IN (%s) AND c_id = %d AND ref = %d and done_q = 0";
// c_item_property, question, ref field
foreach ($questions as $key => $value) {
    $sql = sprintf($sqlTemplate, $value['iid'], "'QuizQuestionUpdated', 'QuizQuestionDeleted'", $value['c_id'], $value['id']);
    //echo($sql.PHP_EOL);
    Database::query($sql);
}
$sqlAlter = "ALTER table $tblCItemProperty DROP COLUMN done_q";
Database::query($sqlAlter);
echo "(".date('Y-m-d H:i:s').") Updated c_item_property".PHP_EOL;
// track_e_exercises, exercises, exe_exo_id field
$sqlAlter = "ALTER table $tblTrackExercises ADD COLUMN done_e int NOT NULL default 0";
Database::query($sqlAlter);
$sqlTemplate = "UPDATE $tblTrackExercises SET exe_exo_id = %d, done_e = 1 WHERE c_id = %d AND exe_exo_id = %d AND done_e = 0";
foreach ($exercises as $key => $value) {
    $sql = sprintf($sqlTemplate, $value['iid'], $value['c_id'], $value['id']);
    //echo($sql.PHP_EOL);
    Database::query($sql);
}
$sqlAlter = "ALTER table $tblTrackExercises DROP COLUMN done_e";
Database::query($sqlAlter);
echo "(".date('Y-m-d H:i:s').") Updated track_e_exercises.exe_exo_id".PHP_EOL;

// Split and update the track_e_exercises.data_tracking field, which is a
// comma-separated list of question IDs the student had to go through in his/her test
$sqlTemplate = "SELECT id, iid FROM c_quiz_question WHERE c_id = %d AND id = %d";
$sql = "SELECT exe_id, c_id, exe_exo_id, data_tracking FROM $tblTrackExercises ORDER BY exe_id";
$res = Database::query($sql);
$count = Database::num_rows($res);
echo "  (".date('Y-m-d H:i:s').") Counted $count rows in track_e_exercises".PHP_EOL;
$k = 0;
while ($row = Database::fetch_assoc($res)) {
    if (empty($row['data_tracking'])) {
        $k++;
        continue;
    }
    $questionsList = explode(',', $row['data_tracking']);
    if (!empty($questionsList)) {
        $newDataTracking = '';
        foreach ($questionsList as $questionId) {
            $sql = sprintf($sqlTemplate, $row['c_id'], $questionId);
            $resQ = Database::query($sql);
            $rowQ = Database::fetch_assoc($resQ);
            if (!empty($rowQ)) {
                $indexQ = $row['c_id'].'-'.$questionId;
                if (isset($questions[$indexQ])) {
                    // If id = iid, $questions[c_id-id] is not defined anyway
                    $newDataTracking .= $questions[$indexQ]['iid'].',';
                } else {
                    $newDataTracking .= $rowQ['iid'].',';
                }
            }
        }
        if (!empty($newDataTracking)) {
            $newDataTracking = substr($newDataTracking, 0, -1);
        }
        $sqlU = "UPDATE $tblTrackExercises
            SET data_tracking = '$newDataTracking'
            WHERE exe_id = ".$row['exe_id'];
        $resU = Database::query($sqlU);
    }
    $k++;
    if ($k % 5000 === 0) {
        echo "  (".date('Y-m-d H:i:s').") $k of $count rows treated".PHP_EOL;
    }
}
echo "(".date('Y-m-d H:i:s').") Updated track_e_exercises.data_tracking".PHP_EOL;

// track_e_attempt, question *AND* answer, question_id and answer fields
$sqlAlter = "ALTER table $tblTrackAttempt ADD COLUMN done_a int NOT NULL default 0";
Database::query($sqlAlter);
$sqlTemplate = "UPDATE $tblTrackAttempt SET answer = '%s', done_a = 1 WHERE c_id = %d AND question_id = %d AND answer = '%s' AND done_a = 0";
foreach ($answers as $key => $value) {
    $sql = sprintf($sqlTemplate, $value['iid'], $value['c_id'], $value['q_id'], $value['id']);
    //echo($sql.PHP_EOL);
    Database::query($sql);
}
$sqlAlter = "ALTER table $tblTrackAttempt DROP COLUMN done_a";
Database::query($sqlAlter);
$sqlAlter = "ALTER table $tblTrackAttempt ADD COLUMN done_q int NOT NULL default 0";
Database::query($sqlAlter);
$sqlTemplate = "UPDATE $tblTrackAttempt SET question_id = %d, done_q = 1 WHERE c_id = %d AND question_id = %d AND done_q = 0";
foreach ($questions as $key => $value) {
    $sql = sprintf($sqlTemplate, $value['iid'], $value['c_id'], $value['id']);
    //echo($sql.PHP_EOL);
    Database::query($sql);
}
$sqlAlter = "ALTER table $tblTrackAttempt DROP COLUMN done_q";
Database::query($sqlAlter);
echo "(".date('Y-m-d H:i:s').") Updated track_e_attempt".PHP_EOL;

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
$sqlAlter = "ALTER table $tblCLpItem ADD COLUMN done_e int NOT NULL default 0";
Database::query($sqlAlter);
$sqlTemplate = "UPDATE $tblCLpItem SET path = '%s', done_e = 1 WHERE c_id = %d AND path = '%s' and done_e = 0";
foreach ($exercises as $key => $value) {
    $sql = sprintf($sqlTemplate, $value['iid'], $value['c_id'], $value['id']);
    //echo($sql.PHP_EOL);
    Database::query($sql);
}
$sqlAlter = "ALTER table $tblCLpItem DROP COLUMN done_e";
Database::query($sqlAlter);
echo "(".date('Y-m-d H:i:s').") Updated c_lp_item".PHP_EOL;
// gradebook_link, exercises, ref_id
$sqlAlter = "ALTER table $tblGradebookLink ADD COLUMN done_e int NOT NULL default 0";
Database::query($sqlAlter);
$sqlTemplate = "UPDATE $tblGradebookLink SET ref_id = %d, done_e = 1 WHERE course_code = '%s' AND ref_id = %d AND done_e = 0";
foreach ($exercises as $key => $value) {
    $sql = sprintf($sqlTemplate, $value['iid'], $courseCodes[$value['c_id']], $value['id']);
    //echo($sql.PHP_EOL);
    Database::query($sql);
}
$sqlAlter = "ALTER table $tblGradebookLink DROP COLUMN done_e";
Database::query($sqlAlter);
echo "(".date('Y-m-d H:i:s').") Updated gradebook_link".PHP_EOL;
// c_quiz_answer, question, question_id field
$sqlAlter = "ALTER table $tblCQuizAnswer ADD COLUMN done_q int NOT NULL default 0";
Database::query($sqlAlter);
$sqlTemplate = "UPDATE $tblCQuizAnswer SET question_id = %d, done_q = 1 WHERE c_id = %d AND question_id = %d AND done_q = 0";
foreach ($questions as $key => $value) {
    $sql = sprintf($sqlTemplate, $value['iid'], $value['c_id'], $value['id']);
    //echo($sql.PHP_EOL);
    Database::query($sql);
}
$sqlAlter = "ALTER table $tblCQuizAnswer DROP COLUMN done_q";
Database::query($sqlAlter);
echo "(".date('Y-m-d H:i:s').") Updated c_quiz_answer".PHP_EOL;
// c_quiz_question_option, quiz_question, question_id field
$sqlAlter = "ALTER table $tblCQuizQuestionOption ADD COLUMN done_q int NOT NULL default 0";
Database::query($sqlAlter);
$sqlTemplate = "UPDATE $tblCQuizQuestionOption SET question_id = %d, done_q = 1 WHERE c_id = %d AND question_id = %d AND done_q = 0";
foreach ($questions as $key => $value) {
    $sql = sprintf($sqlTemplate, $value['iid'], $value['c_id'], $value['id']);
    //echo($sql.PHP_EOL);
    Database::query($sql);
}
$sqlAlter = "ALTER table $tblCQuizQuestionOption DROP COLUMN done_q";
Database::query($sqlAlter);
echo "(".date('Y-m-d H:i:s').") Updated c_quiz_question_option".PHP_EOL;
// c_quiz_question_rel_category, question, question_id field
$sqlAlter = "ALTER table $tblCQuizQuestionRelCategory ADD COLUMN done_q int NOT NULL default 0";
Database::query($sqlAlter);
$sqlTemplate = "UPDATE $tblCQuizQuestionRelCategory SET question_id = %d, done_q = 1 WHERE c_id = %d AND question_id = %d AND done_q = 0";
foreach ($questions as $key => $value) {
    $sql = sprintf($sqlTemplate, $value['iid'], $value['c_id'], $value['id']);
    //echo($sql.PHP_EOL);
    Database::query($sql);
}
$sqlAlter = "ALTER table $tblCQuizQuestionRelCategory DROP COLUMN done_q";
Database::query($sqlAlter);
echo "(".date('Y-m-d H:i:s').") Updated c_quiz_question_rel_category".PHP_EOL;
// c_quiz_rel_category, exercises, exercise_id field
$sqlAlter = "ALTER table $tblCQuizRelCategory ADD COLUMN done_e int NOT NULL default 0";
Database::query($sqlAlter);
$sqlTemplate = "UPDATE $tblCQuizRelCategory SET exercise_id = %d, done_e = 1 WHERE c_id = %d AND exercise_id = %d AND done_e = 0";
foreach ($exercises as $key => $value) {
    $sql = sprintf($sqlTemplate, $value['iid'], $value['c_id'], $value['id']);
    //echo($sql.PHP_EOL);
    Database::query($sql);
}
$sqlAlter = "ALTER table $tblCQuizRelCategory DROP COLUMN done_e";
Database::query($sqlAlter);
echo "(".date('Y-m-d H:i:s').") Updated c_quiz_rel_category".PHP_EOL;
// c_quiz_rel_question, exercises and question, exercice_id and question_id fields
$sqlAlter = "ALTER table $tblCQuizRelQuestion ADD COLUMN done_e int NOT NULL default 0";
Database::query($sqlAlter);
$sqlTemplate = "UPDATE $tblCQuizRelQuestion SET exercice_id = %d, done_e = 1 WHERE c_id = %d AND exercice_id = %d AND done_e = 0";
foreach ($exercises as $key => $value) {
    $sql = sprintf($sqlTemplate, $value['iid'], $value['c_id'], $value['id']);
    //echo($sql.PHP_EOL);
    Database::query($sql);
}
$sqlAlter = "ALTER table $tblCQuizRelQuestion DROP COLUMN done_e";
Database::query($sqlAlter);
$sqlAlter = "ALTER table $tblCQuizRelQuestion ADD COLUMN done_q int NOT NULL default 0";
Database::query($sqlAlter);
$sqlTemplate = "UPDATE $tblCQuizRelQuestion SET question_id = %d, done_q = 1 WHERE c_id = %d AND question_id = %d AND done_q = 0";
foreach ($questions as $key => $value) {
    $sql = sprintf($sqlTemplate, $value['iid'], $value['c_id'], $value['id']);
    //echo($sql.PHP_EOL);
    Database::query($sql);
}
$sqlAlter = "ALTER table $tblCQuizRelQuestion DROP COLUMN done_q";
Database::query($sqlAlter);
echo "(".date('Y-m-d H:i:s').") Updated c_quiz_rel_question".PHP_EOL;

echo 'Finished';
error_log('Finished');
