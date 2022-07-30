<?php
/* For license terms, see /license.txt */

require_once __DIR__.'/../../../../main/inc/global.inc.php';
require_once __DIR__.'/../../src/LtiProvider.php';

$launch = LtiProvider::create()->launch(true, $_REQUEST['launch_id']);

if (!$launch->hasAgs()) {
    throw new Exception("Don't have grades!");
}

if (!isset($_REQUEST['lti_result_id'])) {
    throw new Exception("Any tool result");
}

$launchData = $launch->getLaunchData();

$courseCode = $_REQUEST['cidReq'];
$courseId = api_get_course_int_id($courseCode);
$toolName = $_REQUEST['lti_tool'];

if (in_array($toolName, ['quiz', 'lp'])) {
    if ('quiz' == $toolName) {
        $objExercise = new Exercise($courseId);
        $exeId = (int) $_REQUEST['lti_result_id'];
        $trackInfo = $objExercise->get_stat_track_exercise_info_by_exe_id($exeId);
        $score = $trackInfo['exe_result'];
        $weight = $trackInfo['exe_weighting'];
        $duration = $trackInfo['exe_duration'];
        $timestamp = date(DATE_ISO8601);
        $progress = 0;
    } else {
        $lpId = (int) $_REQUEST['lti_result_id'];
        $lpScore = Tracking::get_avg_student_score(
            api_get_user_id(),
            $courseCode,
            [$lpId],
            api_get_session_id()
        );
        $lpProgress = Tracking::get_avg_student_progress(
            api_get_user_id(),
            $courseCode,
            [$lpId],
            api_get_session_id()
        );
        $lpDuration = Tracking::get_time_spent_in_lp(
            api_get_user_id(),
            $courseCode,
            [$lpId],
            api_get_session_id()
        );
        $score = (int) $lpScore;
        $progress = (int) $lpProgress;
        $duration = (int) $lpDuration;
        $weight = 100;
        $timestamp = date(DATE_ISO8601);
    }

    $grades = $launch->getAgs();
    $scoreGrade = Packback\Lti1p3\LtiGrade::new()
        ->setScoreGiven($score)
        ->setScoreMaximum($weight)
        ->setTimestamp($timestamp)
        ->setActivityProgress('Completed')
        ->setGradingProgress('FullyGraded')
        ->setUserId($launchData['sub']);

    $scoreLineitem = Packback\Lti1p3\LtiLineitem::new()
        ->setTag('score')
        ->setScoreMaximum($weight)
        ->setLabel('Score')
        ->setResourceId($launchData['https://purl.imsglobal.org/spec/lti/claim/resource_link']['id']);

    $grades->putGrade($scoreGrade, $scoreLineitem);

    $timeGrade = Packback\Lti1p3\LtiGrade::new()
        ->setScoreGiven($duration)
        ->setScoreMaximum(999)
        ->setTimestamp($timestamp)
        ->setActivityProgress('Completed')
        ->setGradingProgress('FullyGraded')
        ->setUserId($launchData['sub']);

    $timeLineitem = Packback\Lti1p3\LtiLineitem::new()
        ->setTag('time')
        ->setScoreMaximum(999)
        ->setLabel('Time Taken')
        ->setResourceId($launchData['https://purl.imsglobal.org/spec/lti/claim/resource_link']['id']);

    $grades->putGrade($timeGrade, $timeLineitem);

    if ('lp' == $toolName) {
        $progressGrade = Packback\Lti1p3\LtiGrade::new()
            ->setScoreGiven($progress)
            ->setScoreMaximum(100)
            ->setTimestamp($timestamp)
            ->setActivityProgress('Completed')
            ->setGradingProgress('FullyGraded')
            ->setUserId($launchData['sub']);

        $progressLineitem = Packback\Lti1p3\LtiLineitem::new()
            ->setTag('progress')
            ->setScoreMaximum(100)
            ->setLabel('Progress')
            ->setResourceId($launchData['https://purl.imsglobal.org/spec/lti/claim/resource_link']['id']);

        $grades->putGrade($progressGrade, $progressLineitem);
    }

    $plugin = LtiProviderPlugin::create();
    $values = [];
    $values['score'] = $score;
    $values['progress'] = $progress;
    $values['duration'] = $duration;
    $plugin->saveResult($values, $_REQUEST['launch_id']);

    echo '{"success" : true}';
}
