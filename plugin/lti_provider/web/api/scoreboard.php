<?php
/* For license terms, see /license.txt */

require_once __DIR__.'/../../../../main/inc/global.inc.php';
require_once __DIR__ . '/../../src/LtiProvider.php';

$launch = LtiProvider::create()->launch(true, $_REQUEST['launch_id']);

if (!$launch->hasNrps()) {
    //throw new Exception("Don't have names and roles!");
}
if (!$launch->hasAgs()) {
    //throw new Exception("Don't have grades!");
}

$launchData = $launch->getLaunchData();
$coursecode = $launchData['https://purl.imsglobal.org/spec/lti/claim/context']['label'];

$dataFile = __DIR__ . '/ags/results.json';
$dataContent = file_get_contents($dataFile);
if (!empty($dataContent)) {
    $data = json_decode($dataContent, true);
}

$scoreboard = [];
foreach ($data[$coursecode] as $userId => $member) {
    $scoreboard[] = array('user_id' => $userId, 'name' => $member['name'], 'score' => $member['score'], 'time' => $member['time']);
}
echo json_encode($scoreboard);
?>
