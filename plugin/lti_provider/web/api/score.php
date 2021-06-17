<?php
/* For license terms, see /license.txt */

require_once __DIR__.'/../../../../main/inc/global.inc.php';
require_once __DIR__ . '/../../src/LtiProvider.php';

$launch = LtiProvider::create()->launch(true, $_REQUEST['launch_id']);

if (!$launch->hasAgs()) {
    //throw new Exception("Don't have grades!");
}

$launchData = $launch->getLaunchData();
$coursecode = $launchData['https://purl.imsglobal.org/spec/lti/claim/context']['label'];
$userid = $launchData['sub'];
$data = array();

$dataFile = __DIR__ . '/ags/results.json';

$dataContent = file_get_contents($dataFile);
if (!empty($dataContent)) {
    $data = json_decode($dataContent, true);
}
$data[$coursecode][$userid]['name'] = $launchData['given_name'];
if (isset($_REQUEST['score'])) {
    $data[$coursecode][$userid]['score'] = $_REQUEST['score'];
}

if (isset($_REQUEST['time'])) {
    $data[$coursecode][$userid]['time'] = $_REQUEST['time'];

}

if (file_exists($dataFile)) {
    @chmod($dataFile,  0775);
}
file_put_contents($dataFile, json_encode($data));
echo '{"success" : true}';
?>
