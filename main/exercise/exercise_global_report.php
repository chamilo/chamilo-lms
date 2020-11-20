<?php

/* For licensing terms, see /license.txt */

require_once __DIR__.'/../inc/global.inc.php';

api_protect_admin_script();

$TBL_EXERCISE_QUESTION = Database::get_course_table(TABLE_QUIZ_TEST_QUESTION);
$TBL_QUESTIONS = Database::get_course_table(TABLE_QUIZ_QUESTION);
$TBL_TRACK_EXERCISES = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
$TBL_TRACK_ATTEMPT = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);
$courseId = api_get_course_int_id();
$courseCode = api_get_course_id();
$data = [];

$students = CourseManager::get_student_list_from_course_code($courseCode);
$categories = TestCategory::getCategoryListInfo('', $courseId);

$sql = "SELECT iid, title FROM c_quiz WHERE c_id = $courseId AND active <> 2 order by iid";
$result = Database::query($sql);
$exercises = Database::store_result($result);
$list = [];
$header = [];

$header[] = get_lang('Username');
$header[] = get_lang('FirstName');
$header[] = get_lang('LastName');
$header[] = get_lang('Email');
$header[] = get_lang('OfficialCode');

/** @var TestCategory $categoryInfo */
foreach ($categories as $categoryInfo) {
    $header[] = 'Aciertos: '.$categoryInfo->name;
    $header[] = 'Errores: '.$categoryInfo->name;
    $header[] = 'Omisiones: '.$categoryInfo->name;
    $header[] = 'Puntos: '.$categoryInfo->name;
}

foreach ($exercises as $exerciseInfo) {
    $header[] = $exerciseInfo['title'];
}

$list[] = $header;
$objExercise = new Exercise();

foreach ($students as $studentInfo) {
    $studentId = $studentInfo['user_id'];
    //$userExtra = UserManager::get_extra_user_data($studentId);

    $data = [];
    $data[] = $studentInfo['username'];
    $data[] = $studentInfo['lastname'];
    $data[] = $studentInfo['firstname'];
    $data[] = $studentInfo['email'];
    $data[] = $studentInfo['official_code'];
    //$data[] = isset($userExtra['extra_nif']) ? $userExtra['extra_nif'] : '';

    $userExerciseData = [];
    $categoryData = [];
    foreach ($exercises as $exerciseInfo) {
        $exerciseId = $exerciseInfo['iid'];
        $objExercise->read($exerciseId);

        $sql = "SELECT exe_id, data_tracking
                FROM $TBL_TRACK_EXERCISES
                WHERE
                    c_id = $courseId AND
                    exe_user_id = $studentId AND
                    exe_exo_id = $exerciseId AND
                    status = ''
                LIMIT 1";
        $result = Database::query($sql);
        $attempt = Database::fetch_array($result, 'ASSOC');
        if (empty($attempt)) {
            $userExerciseData[$exerciseId] = null;
            continue;
        }
        $exeId = $attempt['exe_id'];

        ob_start();
        $stats = ExerciseLib::displayQuestionListByAttempt(
            $objExercise,
            $exeId,
            false,
            '',
            false,
            true,
            true
        );
        ob_end_clean();
        foreach ($categories as $categoryInfo) {
            if (!($categoryInfo instanceof TestCategory)) {
                continue;
            }
            if (isset($stats['category_list'][$categoryInfo->id])) {
                $categoryItem = $stats['category_list'][$categoryInfo->id];
                if (!isset($categoryData[$categoryInfo->id])) {
                    $categoryData[$categoryInfo->id]['passed'] = 0;
                    $categoryData[$categoryInfo->id]['wrong'] = 0;
                    $categoryData[$categoryInfo->id]['no_answer'] = 0;
                    $categoryData[$categoryInfo->id]['score'] = 0;
                }
                $categoryData[$categoryInfo->id]['passed'] += $categoryItem['passed'];
                $categoryData[$categoryInfo->id]['wrong'] += $categoryItem['wrong'];
                $categoryData[$categoryInfo->id]['no_answer'] += $categoryItem['no_answer'];
                $categoryData[$categoryInfo->id]['score'] += $categoryItem['score'];
            }
        }
        $userExerciseData[$exerciseId] = $stats['total_score'];
    }
    foreach ($categories as $categoryInfo) {
        if (isset($categoryData[$categoryInfo->id])) {
            $data[] = $categoryData[$categoryInfo->id]['passed'];
            $data[] = $categoryData[$categoryInfo->id]['wrong'];
            $data[] = $categoryData[$categoryInfo->id]['no_answer'];
            $data[] = $categoryData[$categoryInfo->id]['score'];
        } else {
            $data[] = null;
            $data[] = null;
            $data[] = null;
            $data[] = null;
        }
    }

    foreach ($exercises as $exerciseInfo) {
        $exerciseId = $exerciseInfo['iid'];
        if (isset($userExerciseData[$exerciseId])) {
            $data[] = $userExerciseData[$exerciseId];
        } else {
            $data[] = null;
        }
    }

    $list[] = $data;
}

$filePath = Export::arrayToCsv($list, get_lang('Report'), true);
DocumentManager::file_send_for_download($filePath, true, get_lang('Report').'.csv');
