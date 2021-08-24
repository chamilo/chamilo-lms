<?php
/* For license terms, see /license.txt */

require_once __DIR__.'/../../../../main/inc/global.inc.php';
require_once __DIR__.'/../../src/LtiProvider.php';

$launch = LtiProvider::create()->launch(true, $_REQUEST['launch_id']);

if (!$launch->hasAgs()) {
    throw new Exception("Don't have grades!");
}

if (!isset($_REQUEST['exeId'])) {
    throw new Exception("Any Exercise result");
}

$launchData = $launch->getLaunchData();

$label = 'Score';
$courseClient = $launchData['https://purl.imsglobal.org/spec/lti/claim/resource_link']['title'];
if (!empty($courseClient)) {
    $label = $courseClient;
}

$exeId = (int) $_REQUEST['exeId'];
$trackInfo = Exercise::get_stat_track_exercise_info_by_exe_id($exeId);
$score = $trackInfo['exe_result'];
$weight = $trackInfo['exe_weighting'];
$duration = $trackInfo['duration'];
$timestamp = date(DateTime::ISO8601);

$grades = $launch->getAgs();
$score = Packback\Lti1p3\LtiGrade::new()
    ->setScoreGiven($score)
    ->setScoreMaximum($weight)
    ->setTimestamp($timestamp)
    ->setActivityProgress('Completed')
    ->setGradingProgress('FullyGraded')
    ->setUserId($launch->getLaunchData()['sub']);

$scoreLineitem = Packback\Lti1p3\LtiLineitem::new()
    ->setTag('score')
    ->setScoreMaximum($weight)
    ->setLabel($label)
    ->setResourceId($launch->getLaunchData()['https://purl.imsglobal.org/spec/lti/claim/resource_link']['id']);

$grades->putGrade($score, $scoreLineitem);

$time = Packback\Lti1p3\LtiGrade::new()
    ->setScoreGiven($duration)
    ->setScoreMaximum(999)
    ->setTimestamp($timestamp)
    ->setActivityProgress('Completed')
    ->setGradingProgress('FullyGraded')
    ->setUserId($launch->getLaunchData()['sub']);

$timeLineitem = Packback\Lti1p3\LtiLineitem::new()
    ->setTag('time')
    ->setScoreMaximum(999)
    ->setLabel('Time Taken')
    ->setResourceId('time'.$launch->getLaunchData()['https://purl.imsglobal.org/spec/lti/claim/resource_link']['id']);

$grades->putGrade($time, $timeLineitem);

echo '{"success" : true}';
