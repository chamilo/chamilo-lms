<?php
/* For license terms, see /license.txt */

require_once __DIR__.'/../../../../main/inc/global.inc.php';
require_once __DIR__.'/../../src/LtiProvider.php';

$launch = LtiProvider::create()->launch(true, $_REQUEST['launch_id']);

if (!$launch->hasNrps()) {
    throw new Exception("Don't have names and roles!");
}
if (!$launch->hasAgs()) {
    throw new Exception("Don't have grades!");
}

$ags = $launch->getAgs();

$scoreLineitem = Packback\Lti1p3\LtiLineitem::new()
    ->setTag('score')
    ->setScoreMaximum(100)
    ->setLabel('Score')
    ->setResourceId($launch->getLaunchData()['https://purl.imsglobal.org/spec/lti/claim/resource_link']['id']);
$scores = $ags->getGrades($scoreLineitem);

$timeLineitem = Packback\Lti1p3\LtiLineitem::new()
    ->setTag('time')
    ->setScoreMaximum(999)
    ->setLabel('Time Taken')
    ->setResourceId('time'.$launch->getLaunchData()['https://purl.imsglobal.org/spec/lti/claim/resource_link']['id']);
$times = $ags->getGrades($timeLineitem);

$members = $launch->getNrps()->getMembers();

$scoreboard = [];
foreach ($scores as $score) {
    $result = ['score' => $score['resultScore']];
    foreach ($times as $time) {
        if ($time['userId'] === $score['userId']) {
            $result['time'] = $time['resultScore'];
            break;
}
    }
    foreach ($members as $member) {
        if ($member['user_id'] === $score['userId']) {
            $result['name'] = $member['name'];
            break;
        }
    }
    $scoreboard[] = $result;
}
echo json_encode($scoreboard);
