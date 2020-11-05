<?php
/* For licensing terms, see /license.txt */

$strings['plugin_title'] = 'Experience API (xAPI)';
$strings['plugin_comment'] = 'Allow to incorporate an Learning Record Store and clients to xAPI';

$strings[XApiPlugin::SETTING_UUID_NAMESPACE] = 'UUID Namespace';
$strings[XApiPlugin::SETTING_UUID_NAMESPACE.'_help'] = 'Namespace for universally unique identifiers used as statement IDs.'
    .'<br>This is automatically by Chamilo LMS. <strong>Don\'t replace it.</strong>';
$strings[XApiPlugin::SETTING_LRS_URL] = 'LRS: URL for API';
$strings[XApiPlugin::SETTING_LRS_URL.'_help'] = 'Sets the LRS base URL.';
$strings[XApiPlugin::SETTING_LRS_AUTH] = 'LRS: Authentication method';
$strings[XApiPlugin::SETTING_LRS_AUTH.'_help'] = 'Sets HTTP authentication credentials.<br>';
$strings[XApiPlugin::SETTING_LRS_AUTH.'_help'] .= 'Choose one auth method: Basic (<code>basic:username:password</code>) or OAuth1 (<code>oauth:key:secret</code>)';

$strings['NoActivities'] = 'No activities added yet';
$strings['ActivityTitle'] = 'Activity';
$strings['AddActivity'] = 'Add activity';
$strings['TinCanPackage'] = 'TinCan package';
$strings['OnlyZipAllowed'] = 'Only ZIP file allowed (.zip).';
$strings['ActivityImported'] = 'Activity imported.';
$strings['EditActivity'] = 'Edit activity';
$strings['ActivityUpdated'] = 'Activity updated';
$strings['ActivityLaunchUrl'] = 'Launch URL';
$strings['ActivityId'] = 'Activity ID';
$strings['ActivityType'] = 'Activity type';
$strings['ActivityDeleted'] = 'Activity deleted';
$strings['ActivityLaunch'] = 'Launch';
$strings['ActivityFirstLaunch'] = 'First launch at';
$strings['ActivityLastLaunch'] = 'Last launch at';
$strings['LaunchNewAttempt'] = 'Launch new attempt';
$strings['LrsConfiguration'] = 'LRS Configuration';
