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
