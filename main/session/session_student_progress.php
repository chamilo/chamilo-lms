<?php

/* For licensing terms, see /license.txt */

$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';

api_block_anonymous_users();

$allowToTrack = api_is_platform_admin(true, true) || api_is_teacher();

if (!$allowToTrack) {
    api_not_allowed(true);
}

$sessionId = isset($_GET['session_id']) ? (int) $_GET['session_id'] : 0;
$fileType = $_GET['file_type'] ?? 'xls';

if (empty($sessionId)) {
    api_not_allowed(true);
}

$sessionInfo = api_get_session_info($sessionId);

if (empty($sessionInfo)) {
    api_not_allowed(true);
}

$strDate = api_get_local_time(null, null, null, false, false, true);

$courses = array_map(
    function ($courseInfo) {
        $courseInfo['real_id'] = $courseInfo['c_id'];

        return $courseInfo;
    },
    Tracking::get_courses_list_from_session($sessionId)
);
$users = SessionManager::get_users_by_session($sessionId, 0);
$studentList = array_column($users, 'user_id');

$rowSession = [
    $sessionInfo['name'],
];

$rowDate = [$strDate];

$rowCourses = ['', '', ''];
$rowHeaders = [
    get_lang('LastName'),
    get_lang('FirstName'),
    get_lang('ScormAndLPProgressTotalAverage'),
];

foreach ($courses as $course) {
    $courseInfo = api_get_course_info_by_id($course['c_id']);

    $rowCourses[] = $courseInfo['title'];
    $rowCourses[] = '';

    $rowHeaders[] = get_lang('Progress');
    $rowHeaders[] = get_lang('FinalScore');
}

$data = [];
$data[] = $rowSession;
$data[] = $rowDate;
$data[] = $rowCourses;
$data[] = $rowHeaders;

$totalCourses = count($courses);

foreach ($studentList as $studentId) {
    $studentInfo = api_get_user_info($studentId);

    if (empty($studentInfo)) {
        continue;
    }

    $progressSum = 0;
    $courseValues = [];

    foreach ($courses as $course) {
        if (!CourseManager::is_user_subscribed_in_course($studentId, $course['code'], true, $sessionId)) {
            $courseValues[] = ['', ''];

            continue;
        }

        $objLearnpaths = new LearnpathList(
            $studentId,
            $course,
            $sessionId,
            null,
            false,
            null,
            true,
            false,
            true,
            true
        );
        $lpList = $objLearnpaths->get_flat_list();

        $lastLearnpath = end($lpList);

        if (!$lastLearnpath) {
            $courseValues[] = ['0 %', 0];

            continue;
        }

        $courseProgress = Tracking::get_avg_student_progress(
            $studentId,
            $course['code'],
            [],
            $sessionId
        );

        $evaluationResult = '0';

        $lp = new Learnpath($course['code'], $lastLearnpath['iid'], $studentId);

        if ($finalEvaluationItem = $lp->getFinalEvaluationItem()) {
            $bestScoreData = ExerciseLib::get_best_attempt_by_user(
                $studentId,
                $finalEvaluationItem->path,
                $course['real_id'],
                $sessionId,
                false
            );

            if ($bestScoreData) {
                $evaluationResult = ExerciseLib::show_score(
                    $bestScoreData['exe_result'],
                    $bestScoreData['exe_weighting']
                );
            }
        }

        $value = is_numeric($courseProgress) ? round($courseProgress, 2) : 0;
        $progressSum += $value;

        $courseValues[] = [
            sprintf(get_lang('XPercent'), $value),
            strip_tags($evaluationResult),
        ];
    }

    $average = $totalCourses > 0 ? round($progressSum / $totalCourses, 2) : 0;

    $row = [
        $studentInfo['lastname'],
        $studentInfo['firstname'],
        sprintf(get_lang('XPercent'), $average),
    ];

    foreach ($courseValues as $courseValue) {
        $row[] = $courseValue[0];
        $row[] = $courseValue[1];
    }

    $data[] = $row;
}

$filename = 'session_student_progress_'.$sessionInfo['name'].'_'.api_get_local_time();

switch ($fileType) {
    case 'xls':
        Export::arrayToXls($data, $filename);
        break;
    case 'csv':
    default:
        Export::arrayToCsv($data, $filename);
        break;
}
