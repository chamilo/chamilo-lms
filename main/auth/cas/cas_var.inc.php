<?
/* This file contains all the configuration variable for the cas module
 * In the future, these will be in the database
*/
require_once('lib/CAS.php');
define("CAS_VERSION_2_0",'2.0');
define("SAML_VERSION_1_1", 'S1'); 

global $cas_auth_ver, $cas_auth_server, $cas_auth_port, $cas_auth_uri; 

$cas_auth_server = api_get_setting('cas_server');
$cas_auth_uri = api_get_setting('cas_server_uri');
$cas_auth_port = intval(api_get_setting('cas_port'));

$cas_auth_uri = api_get_setting('cas_server_uri');
if ( ! is_string($cas_auth_uri)) $cas_auth_uri = ''; 
	
$cas_auth_ver = '2.0';
//$cas_auth_ver = SAML_VERSION_1_1;
?>
