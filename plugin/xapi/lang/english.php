<?php

/* For licensing terms, see /license.txt */

$strings['plugin_title'] = 'Experience API (xAPI)';
$strings['plugin_comment'] = 'Allows you to connect to an external (or internal) Learning Record Store and use activities compatible with the xAPI standard.';

$strings[XApiPlugin::SETTING_UUID_NAMESPACE] = 'UUID Namespace';
$strings[XApiPlugin::SETTING_UUID_NAMESPACE.'_help'] = 'Namespace for universally unique identifiers used as statement IDs.'
    .'<br>This is generated automatically by Chamilo LMS. <strong>Don\'t replace it.</strong>';
$strings['lrs_url'] = 'LRS endpoint';
$strings['lrs_url_help'] = 'Base URL of the LRS';
$strings['lrs_auth_username'] = 'LRS user';
$strings['lrs_auth_username_help'] = 'Username for basic HTTP authentication';
$strings['lrs_auth_password'] = 'LRS password';
$strings['lrs_auth_password_help'] = 'Password for basic HTTP authentication';
$strings['cron_lrs_url'] = 'Cron: LRS endpoint';
$strings['cron_lrs_url_help'] = 'Alternative base URL of the LRS for the cron process';
$strings['cron_lrs_auth_username'] = 'Cron: LRS user';
$strings['cron_lrs_auth_username_help'] = 'Alternative username for basic HTTP authentication for the cron process';
$strings['cron_lrs_auth_password'] = 'Cron: LRS password';
$strings['cron_lrs_auth_password_help'] = 'Alternative password for basic HTTP authentication for the cron process';
$strings['lrs_lp_item_viewed_active'] = 'Learning path item viewed';
$strings['lrs_lp_end_active'] = 'Learning path ended';
$strings['lrs_quiz_active'] = 'Quiz ended';
$strings['lrs_quiz_question_active'] = 'Quiz question answered';
$strings['lrs_portfolio_active'] = 'Portfolio events';

$strings['NoActivities'] = 'No activities added yet';
$strings['ActivityTitle'] = 'Activity';
$strings['AddActivity'] = 'Add activity';
$strings['TinCanPackage'] = 'TinCan package (zip)';
$strings['Cmi5Package'] = 'Cmi5 package (zip)';
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
$strings['Verb'] = 'Verb';
$strings['Actor'] = 'Actor';
$strings['ToolTinCan'] = 'Activities';
$strings['Terminated'] = 'Terminated';
$strings['Completed'] = 'Completed';
$strings['Answered'] = 'Answered';
$strings['Viewed'] = 'Viewed';
$strings['ActivityAddedToLPCannotBeAccessed'] = 'This activity has been included in a learning path, so it cannot be accessed by students directly from here.';
$strings['XApiPackage'] = 'XApi Package';
$strings['TinCanAllowMultipleAttempts'] = 'Allow multiple attempts';
