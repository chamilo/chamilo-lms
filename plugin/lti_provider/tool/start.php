<?php
/* For license terms, see /license.txt */
use ChamiloSession as Session;

require_once __DIR__.'/../../../main/inc/global.inc.php';
require_once __DIR__.'/../src/LtiProvider.php';
require_once __DIR__.'/../LtiProviderPlugin.php';

$launch = LtiProvider::create()->launch();
if (!$launch->hasNrps()) {
    // throw new Exception("Don't have names and roles!");
}

$launchData = $launch->getLaunchData();

$plugin = LtiProviderPlugin::create();
$toolVars = $plugin->getToolProviderVars($launchData['aud']);

$login = LtiProvider::create()->validateUser($launchData, $toolVars['courseCode'], $toolVars['toolName']);
$ltiSession = [];
if ($login) {
    $values = [];
    $values['issuer'] = $launchData['iss'];
    $values['user_id'] = api_get_user_id();
    $values['client_uid'] = $launchData['sub'];
    $values['course_code'] = $toolVars['courseCode'];
    $values['tool_id'] = $toolVars['toolId'];
    $values['tool_name'] = $toolVars['toolName'];
    $values['lti_launch_id'] = $launch->getLaunchId();
    $plugin->saveResult($values);
    $ltiSession = $values;
}

$cidReq = 'cidReq='.$toolVars['courseCode'].'&id_session=0&gidReq=0&gradebook=0';

if ('lp' == $toolVars['toolName']) {
    $launchUrl = api_get_path(WEB_CODE_PATH).'lp/lp_controller.php?'.$cidReq.'&action=view&lp_id='.$toolVars['toolId'].'&isStudentView=true&lti_launch_id='.$launch->getLaunchId();
} else {
    $launchUrl = api_get_path(WEB_CODE_PATH).'exercise/overview.php?'.$cidReq.'&origin=embeddable&exerciseId='.$toolVars['toolId'].'&lti_launch_id='.$launch->getLaunchId();
}
$ltiSession['launch_url'] = $launchUrl;
Session::write('_ltiProvider', $ltiSession);
header('Location: '.$launchUrl);
exit;
