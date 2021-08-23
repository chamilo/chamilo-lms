<?php
/* For license terms, see /license.txt */

require_once __DIR__.'/../../../main/inc/global.inc.php';
require_once __DIR__.'/../src/LtiProvider.php';
require_once __DIR__.'/../LtiProviderPlugin.php';

use Packback\Lti1p3;

$launch = LtiProvider::create()->launch();
if (!$launch->hasNrps()) {
    throw new Exception("Don't have names and roles!");
}


$launchData = $launch->getLaunchData();

$plugin = LtiProviderPlugin::create();
$toolProvider = $plugin->getToolProvider($launchData['iss']);
list($courseCode, $tool) = explode('@@', $toolProvider);

$login = LtiProvider::create()->validateUser($launchData, $courseCode);

list($toolName, $toolId) = explode('-', $tool);
$cidReq = 'cidReq='.$courseCode.'&id_session=0&gidReq=0&gradebook=0';
$launchUrl = api_get_path(WEB_CODE_PATH).'exercise/overview.php?'.$cidReq.'&origin=embeddable&exerciseId='.$toolId.'&lti_launch_id='.$launch->getLaunchId();

header('Location: '.$launchUrl);
exit;

