<?php
/* For license terms, see /license.txt */

require_once __DIR__.'/../../../main/inc/global.inc.php';
require_once __DIR__.'/../src/LtiProvider.php';
require_once __DIR__.'/../LtiProviderPlugin.php';

$launch = LtiProvider::create()->launch();
if (!$launch->hasNrps()) {
    throw new Exception("Don't have names and roles!");
}

$launchData = $launch->getLaunchData();

$plugin = LtiProviderPlugin::create();
$toolVars = $plugin->getToolProviderVars($launchData['iss']);

$login = LtiProvider::create()->validateUser($launchData, $toolVars['courseCode']);
$cidReq = 'cidReq='.$toolVars['courseCode'].'&id_session=0&gidReq=0&gradebook=0';

$launchUrl = api_get_path(WEB_CODE_PATH).'exercise/overview.php?'.$cidReq.'&origin=embeddable&exerciseId='.$toolVars['toolId'].'&lti_launch_id='.$launch->getLaunchId();
header('Location: '.$launchUrl);
exit;
