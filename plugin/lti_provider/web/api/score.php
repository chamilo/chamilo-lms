<?php
/* For license terms, see /license.txt */

require_once __DIR__.'/../../../../main/inc/global.inc.php';
require_once __DIR__.'/../../src/LtiProvider.php';

$launch = LtiProvider::create()->launch(true, $_REQUEST['launch_id']);

if (!$launch->hasAgs()) {
    throw new Exception("Don't have grades!");
}

$grades = $launch->getAgs();

$score = Packback\Lti1p3\LtiGrade::new()
    ->setScoreGiven($_REQUEST['score'])
    ->setScoreMaximum(100)
    ->setTimestamp(date(DateTime::ISO8601))
    ->setActivityProgress('Completed')
    ->setGradingProgress('FullyGraded')
    ->setUserId($launch->getLaunchData()['sub']);


$scoreLineitem = Packback\Lti1p3\LtiLineitem::new()
    ->setTag('score')
    ->setScoreMaximum(100)
    ->setLabel('Score')
    ->setResourceId($launch->getLaunchData()['https://purl.imsglobal.org/spec/lti/claim/resource_link']['id']);

$grades->putGrade($score, $scoreLineitem);


$time = Packback\Lti1p3\LtiGrade::new()
    ->setScoreGiven($_REQUEST['time'])
    ->setScoreMaximum(999)
    ->setTimestamp(DateTime::ISO8601)
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
