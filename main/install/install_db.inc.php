<?php
/* For licensing terms, see /chamilo_license.txt */
/**
==============================================================================
*	Install the Dokeos database
*	Notice : This script has to be included by index.php
*
*	@package chamilo.install
==============================================================================
*/

require_once 'install_upgrade.lib.php';

/*
==============================================================================
		MAIN CODE
==============================================================================
*/

//this page can only be access through including from the install script.

if (!defined('DOKEOS_INSTALL')) {
	echo 'You are not allowed here!';
	exit;
}

set_file_folder_permissions();

@Database::connect(array('server' => $dbHostForm, 'username' => $dbUsernameForm, 'password' => $dbPassForm));

if (Database::errno() > 0) {
	$no = Database::errno();
	$msg = Database::error();

	echo '<hr />['.$no.'] &ndash; '.$msg.'<hr />
	The MySQL server doesn\'t work or login / pass is bad.<br /><br />
	Please check these values:<br /><br />
	<strong>host</strong> : '.$dbHostForm.'<br />
	<strong>user</strong> : '.$dbUsernameForm.'<br />
	<strong>password</strong> : '.str_repeat('*', api_strlen($dbPassForm)).'<br /><br />
	Please go back to step 3.
	<p><button type="submit" class="back" name="step3" value="&lt; Back" >Back</button></p>
	</td></tr></table></form></body></html>';

	exit();
}

// Disabling special SQL modes (MySQL 5)
Database::query("set session sql_mode='';");

// Initialization of the database encoding to be used.
Database::query("SET SESSION character_set_server='utf8';");
Database::query("SET SESSION collation_server='utf8_general_ci';");
Database::query("SET CHARACTER SET 'utf8';");

if ($urlForm[strlen($urlForm) - 1] != '/') {
	$urlForm = $urlForm.'/';
}

switch ($encryptPassForm) {
	case 'md5' :
		$passToStore = md5($passForm);
		break;
	case 'sha1' :
		$passToStore = sha1($passForm);
		break;
	case 'none' :
		$passToStore = $passForm;
		break;
}

$dbPrefixForm = eregi_replace('[^a-z0-9_-]', '', $dbPrefixForm);

$dbNameForm = eregi_replace('[^a-z0-9_-]', '', $dbNameForm);
$dbStatsForm = eregi_replace('[^a-z0-9_-]', '', $dbStatsForm);
$dbUserForm = eregi_replace('[^a-z0-9_-]', '', $dbUserForm);

if (!empty($dbPrefixForm) && !ereg('^'.$dbPrefixForm, $dbNameForm)) {
	$dbNameForm = $dbPrefixForm.$dbNameForm;
}

if (!empty($dbPrefixForm) && !ereg('^'.$dbPrefixForm, $dbStatsForm)) {
	$dbStatsForm = $dbPrefixForm.$dbStatsForm;
}

if (!empty($dbPrefixForm) && !ereg('^'.$dbPrefixForm, $dbUserForm)) {
	$dbUserForm = $dbPrefixForm.$dbUserForm;
}

$mysqlMainDb = $dbNameForm;
$mysqlStatsDb = $dbStatsForm;
$mysqlUserDb = $dbUserForm;

if (empty($mysqlMainDb) || $mysqlMainDb == 'mysql' || $mysqlMainDb == $dbPrefixForm) {
	$mysqlMainDb = $dbPrefixForm.'main';
}

if (empty($mysqlStatsDb) || $mysqlStatsDb == 'mysql' || $mysqlStatsDb == $dbPrefixForm) {
	$mysqlStatsDb = $dbPrefixForm.'stats';
}

if (empty($mysqlUserDb) || $mysqlUserDb == 'mysql' || $mysqlUserDb == $dbPrefixForm) {
	$mysqlUserDb = $dbPrefixForm.'user';
}

$result = Database::query("SHOW VARIABLES LIKE 'datadir'") or die(Database::error());

$mysqlRepositorySys = Database::fetch_array($result);
$mysqlRepositorySys = $mysqlRepositorySys['Value'];

if (!$singleDbForm) {
	Database::query("DROP DATABASE IF EXISTS `$mysqlMainDb`") or die(Database::error());
}
Database::query("CREATE DATABASE IF NOT EXISTS `$mysqlMainDb`") or die(Database::error());

if($mysqlStatsDb == $mysqlMainDb && $mysqlUserDb == $mysqlMainDb) {
	$singleDbForm = true;
}

/**
* CREATING THE STATISTICS DATABASE
*/
if ($mysqlStatsDb != $mysqlMainDb) {
	if (!$singleDbForm) {
		// multi DB mode AND tracking has its own DB so create it
		Database::query("DROP DATABASE IF EXISTS `$mysqlStatsDb`") or die(Database::error());
		Database::query("CREATE DATABASE `$mysqlStatsDb`") or die(Database::error());
	} else {
		// single DB mode so $mysqlStatsDb MUST BE the SAME than $mysqlMainDb
		$mysqlStatsDb = $mysqlMainDb;
	}
}

/**
* CREATING THE USER DATABASE
*/
if ($mysqlUserDb != $mysqlMainDb) {
	if (!$singleDbForm) {
		// multi DB mode AND user data has its own DB so create it
		Database::query("DROP DATABASE IF EXISTS `$mysqlUserDb`") or die(Database::error());
		Database::query("CREATE DATABASE `$mysqlUserDb`") or die(Database::error());
	} else {
		// single DB mode so $mysqlUserDb MUST BE the SAME than $mysqlMainDb
		$mysqlUserDb = $mysqlMainDb;
	}
}

include '../lang/english/create_course.inc.php';

if ($languageForm != 'english') {
	include '../lang/'.$languageForm.'/create_course.inc.php';
}

/**
* creating the tables of the main database
*/
Database::select_db($mysqlMainDb) or die(Database::error());

$installation_settings['{ORGANISATIONNAME}'] = $institutionForm;
$installation_settings['{ORGANISATIONURL}'] = $institutionUrlForm;
$installation_settings['{CAMPUSNAME}'] = $campusForm;
$installation_settings['{PLATFORMLANGUAGE}'] = $languageForm;
$installation_settings['{ALLOWSELFREGISTRATION}'] = trueFalse($allowSelfReg);
$installation_settings['{ALLOWTEACHERSELFREGISTRATION}'] = trueFalse($allowSelfRegProf);
$installation_settings['{ADMINLASTNAME}'] = $adminLastName;
$installation_settings['{ADMINFIRSTNAME}'] = $adminFirstName;
$installation_settings['{ADMINLOGIN}'] = $loginForm;
$installation_settings['{ADMINPASSWORD}'] = $passToStore;
$installation_settings['{ADMINEMAIL}'] = $emailForm;
$installation_settings['{ADMINPHONE}'] = $adminPhoneForm;
$installation_settings['{PLATFORM_AUTH_SOURCE}'] = PLATFORM_AUTH_SOURCE;
$installation_settings['{ADMINLANGUAGE}'] = $languageForm;
$installation_settings['{HASHFUNCTIONMODE}'] = $encryptPassForm;
load_main_database($installation_settings);

/**
* creating the tables of the tracking database
*/

Database::select_db($mysqlStatsDb) or die(Database::error());

load_database_script('dokeos_stats.sql');

$track_countries_table = "track_c_countries";
fill_track_countries_table($track_countries_table);

/**
* creating the tables of the USER database
* this is where the personal agenda items are storen, the user defined course categories (sorting of my courses)
*/

Database::select_db($mysqlUserDb) or die(Database::error());

load_database_script('dokeos_user.sql');
