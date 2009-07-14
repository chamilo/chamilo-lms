#!/usr/bin/php
<?php 
/**
 * This script allows installing Dokeos from the command line, using a list of 
 * parameters (launch the command alone to see a list of parameters).
 * This script uses the classical web-based files as a base and prepares the
 * parameters correspndingly
 */
/**
 * Environment initialization - prepare the environment to execute this script
 */
ini_set('register_argc_argv','On');
ini_set('max_execution_time',0);
ini_set('memory_limit','100M');
ini_set('log_errors','On');
ini_set('display_errors','On');
if (PHP_SAPI!='cli') {
	die('This script has to be launched from the command line!');
}

// Some constants
define('DOKEOS_INSTALL',1);
define('MAX_COURSE_TRANSFER',100);
define('INSTALL_TYPE_UPDATE', 'update');
define('FORM_FIELD_DISPLAY_LENGTH', 40);
define('DATABASE_FORM_FIELD_DISPLAY_LENGTH', 25);
define('MAX_FORM_FIELD_LENGTH', 80);
define('DEFAULT_LANGUAGE', 'english');
$session_lifetime=360000;
define('SESSION_LIFETIME',$session_lifetime);

// Character set during installation: ISO-8859-15 for Latin 1 languages, UTF-8 for other languages.
$charset = 'UTF-8';
if (isset($install_language))
{
	// TODO: This is for backward compatibility. Actually, all the languages may use UTF-8.
	$charset = 'ISO-8859-15';
}
header('Content-Type: text/html; charset='. $charset);
//api_set_default_encoding($charset); // Initialization of the default encoding that will be used by the string routines.

require_once 'install_upgrade.lib.php'; //also defines constants
require_once 'install_functions.inc.php';

/**
 * Inclusion initialization - includes necessary Dokeos libs 
 */
require dirname(__FILE__).'/../inc/lib/main_api.lib.php';
/**
 * Check parameters
 */
if ($argc <= 10) {
	echo "\nWARNING: This script will install the Dokeos portal from the\n".
		 "command line. As such, it is considered dangerous and should be\n" .
		 "used with caution, providing the following parameters in order.\n\n" .
		 "USAGE: php5 cli_install.php  -l username -p userpass\n" .
		 "       -U dbuser  -P dbpass  -u 'http://new.dokeos.com/'\n" .
		 "       [-L 'portal_language_name'] [-H 'db_host'] [-X 'db_prefix']\n".
		 "       [-M 'main_db'] [-S 'stats_db'] [-R 'user_db'] [-t enable_tracking_true_false]\n".
		 "       [-m single_db_true_false] [-r allow_self_registration_true_false]\n".
		 "       [-q allow_self_registration_teacher_true_false]\n [-n sys_abs_path_to_dokeos_root]".
		 "       [-e encrypt_pass_md5_sha1_none] [-z 'admin_mail'] [-f 'admin_fname']\n".
		 "       [-g 'admin_lname'] [-t 'admin_phone'] [-c 'campus_name']\n".
		 "       [-y 'My company'] [-w 'http://www.dokeos.com']\n\n";
}
$opts = 'l:p:U:P:u:L:H:X:M:S:R:t:m:r:q:e:z:f:g:t:c:y:w:';
$params = getopt($opts);
//die(print_r($params,1));
$error = false;
if (empty($params['l'])) {
	echo "  -l param must be defined.\n";
	$error = true;
}
if (empty($params['p'])) {
	echo "  -p param must be defined.\n";
	$error = true;
}
if (empty($params['U'])) {
	echo "  -U param must be defined.\n";
	$error = true;
}
if (empty($params['P'])) {
	echo "  -P param must be defined.\n";
	$error = true;
}
if (empty($params['u'])) {
	echo "  -u param must be defined.\n";
	$error = true;
}
if ($error === true) { die('Please ensure you type the command correctly.'."\n\n"); }
$config = array();
/**
 * Init default values
 */
// Values without default (mandatory)
$loginForm = $loginForm = $params['l'];
$passForm = $params['p'];
$dbUsernameForm = $params['U'];
$dbPassForm = $params['P'];
$urlForm = $params['u'];
$installType = 'new';
// Values with defaults
$languageForm = 'english';
if (!empty($params['L'])) { $languageForm = $params['L']; }
$dbHostForm = 'localhost';
if (!empty($params['H'])) { $dbHostForm = $params['H']; }
$dbPrefixForm = 'dokeos_';
if (!empty($params['X'])) { $dbPrefixForm = $params['X']; }
$dbNameForm = 'main';
if (!empty($params['M'])) { $dbNameForm = $params['M']; }
$dbStatsForm = 'stats';
if (!empty($params['S'])) { $dbStatsForm = $params['S']; }
$dbUserForm = 'user';
if (!empty($params['R'])) { $dbUserForm = $params['R']; }
$enableTrackingForm = true;
if (!empty($params['t'])) { $enableTrackingForm = getBoolFromString($params['t']); }
$singleDbForm = false;
if (!empty($params['m'])) { $singleDbForm = getBoolFromString($params['m']); }
$allowSelfReg = 'false';
if (!empty($params['r'])) { $allowSelfReg = $params['r']; }
$allowSelfRegProf = 'false';
if (!empty($params['q'])) { $allowSelfRegProf = $params['q']; }
$encryptPassForm = 'md5';
if (!empty($params['e'])) { $encryptPassForm = $params['e']; }
$emailForm = 'admin@localhost';
if (!empty($params['z'])) { $emailForm = $params['z']; }
$adminFirstName = 'John';
if (!empty($params['f'])) { $adminFirstName = $params['f']; }
$adminLastName = 'Doe';
if (!empty($params['g'])) { $adminLastName = $params['g']; }
$adminPhoneForm = '';
if (!empty($params['t'])) { $adminPhoneForm = $params['t']; }
$campusForm = 'My Campus';
if (!empty($params['c'])) { $campusForm = $params['c']; }
$institutionForm = 'My company';
if (!empty($params['y'])) { $institutionForm = $params['y']; }
$institutionUrlForm = 'http://www.dokeos.com/';
if (!empty($params['w'])) { $institutionUrlForm = $params['w']; }
$pathForm = realpath(dirname(__FILE__).'/../..');
if (!empty($params['n'])) { $pathForm = $params['n']; }

//die(print_r($singleDbForm,1));

echo "All params collected, now starting install...\n\n";

require_once(dirname(__FILE__).'/install_db.inc.php');
require_once(dirname(__FILE__).'/install_files.inc.php');

echo "Installation completed! Please browse $urlForm \nwith login $loginForm/$passForm to ensure the installation is OK...\n\n";

/**
 * Convert string to bool
 * @param string 'true' or 'false'
 * @param bool	true or false
 */
function getBoolFromString($b) {
	return ($b=='true'?true:false);
}