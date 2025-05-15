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
        $trackInfo = $objExercise->get_stat_track_exercise_info_by_exe_id($_REQUEST['lti_result_id']);
        $score = $trackInfo['exe_result'];
        $weight = $trackInfo['exe_weighting'];
        $timestamp = date(DATE_ISO8601);
    } else {
        $lpId = (int) $_REQUEST['lti_result_id'];
        $lpProgress = learnpath::getProgress(
            $lpId,
            api_get_user_id(),
            api_get_course_int_id(),
            api_get_session_id()
        );
        $score = $lpProgress;
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
    $grades->putGrade($scoreGrade);

    $plugin = LtiProviderPlugin::create();
    $values = [];
    $values['score'] = $score;
    $values['progress'] = 0;
    $values['duration'] = 0;
    $plugin->saveResult($values, $_REQUEST['launch_id']);

    echo '{"success" : true}';
}
