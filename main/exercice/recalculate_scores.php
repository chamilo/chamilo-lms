<?php

$exerciseId = $app['request']->get('exerciseId');

$exercise = new Exercise();
$exercise->read($exerciseId);
$examResults = get_all_exercise_results($exerciseId, api_get_course_int_id(), api_get_session_id(), true);

foreach ($examResults as $exerciseAttempt) {
    $exeId = $exerciseAttempt['exe_id'];
    $questionList = $exerciseAttempt['question_list'];

    // Cleaning total result in order to recalculate:
    $stat_table = Database :: get_main_table(TABLE_STATISTIC_TRACK_E_EXERCICES);
    $sql = 'UPDATE '.$stat_table.' SET exe_result = 0, exe_weighting = 0 WHERE exe_id = '.$exeId;
    Database::query($sql);

    $totalWeight = 0;
    foreach ($questionList as $questionId => $questionAttempt) {
        $result = $exercise->manageAnswers($exeId, $questionId, $questionAttempt['answer'], 'exercise_show', array(), true, true, false, array(), true);
        $totalWeight += floatval($result['weight']);
    }

    $sql = 'UPDATE '.$stat_table.' SET exe_weighting = '.$totalWeight.' WHERE exe_id = '.$exeId;
    Database::query($sql);
}

$urlMainExercise = api_get_path(WEB_CODE_PATH).'exercice/';
header('Location: '.$urlMainExercise.'exercise_report.php?exerciseId='.$exerciseId.'&'.api_get_cidreq());
exit;
