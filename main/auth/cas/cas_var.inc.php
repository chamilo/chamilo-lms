<?php
/* This file contains all the configuration variable for the cas module
 * In the future, these will be in the database
*/
require_once('lib/CAS.php');

global $cas_auth_ver, $cas_auth_server, $cas_auth_port, $cas_auth_uri; 

$cas_auth_server = api_get_setting('cas_server');
$cas_auth_uri = api_get_setting('cas_server_uri');
$cas_auth_port = intval(api_get_setting('cas_port'));
switch (api_get_setting('cas_protocol')) {
    case 'CAS1': $cas_auth_ver = CAS_VERSION_1_0; break;
    case 'CAS2': $cas_auth_ver = CAS_VERSION_2_0; break;
    case 'SAML': $cas_auth_ver = SAML_VERSION_1_1; break;
    default : $cas_auth_ver = CAS_VERSION_2_0; break;
}

$cas_auth_uri = api_get_setting('cas_server_uri');
if ( ! is_string($cas_auth_uri)) $cas_auth_uri = ''; 
?>
