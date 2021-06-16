<?php
/* For license terms, see /license.txt */

require_once __DIR__.'/../../../../main/inc/global.inc.php';
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../src/LtiProvider.php';
use \IMSGlobal\LTI;

$launch = LtiProvider::create()->launch(true, $_REQUEST['launch_id']);

if (!$launch->has_nrps()) {
    //throw new Exception("Don't have names and roles!");
}
if (!$launch->has_ags()) {
    throw new Exception("Don't have grades!");
}

$launch_data = $launch->get_launch_data();
$coursecode = $launch_data['https://purl.imsglobal.org/spec/lti/claim/context']['label'];

$data_file = __DIR__ . '/ags/results.json';
$data_content = file_get_contents($data_file);
if (!empty($data_content)) {
    $data = json_decode($data_content, true);
}

$scoreboard = [];
foreach ($data[$coursecode] as $user_id => $member) {
    $scoreboard[] = array('user_id' => $user_id, 'name' => $member['name'], 'score' => $member['score'], 'time' => $member['time']);
}
echo json_encode($scoreboard);
?>
