<?php
/* For licensing terms, see /license.txt */

/**
 *	Install the Chamilo database
 *	Notice : This script has to be included by index.php
 *
 *	@package chamilo.install
 */

// This page can only be access through including from the install script.

if (!defined('SYSTEM_INSTALLATION')) {
	echo 'You are not allowed here!';
	exit;
}

$urlForm = api_add_trailing_slash($urlForm);

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

$dbPrefixForm = preg_replace('/[^a-zA-Z0-9_\-]/', '', $dbPrefixForm);

$dbNameForm = preg_replace('/[^a-zA-Z0-9_\-]/', '', $dbNameForm);
if (!empty($dbPrefixForm) && strpos($dbNameForm, $dbPrefixForm) !== 0) {
	$dbNameForm = $dbPrefixForm.$dbNameForm;
}

$dbStatsForm = preg_replace('/[^a-zA-Z0-9_\-]/', '', $dbStatsForm);
if (!empty($dbPrefixForm) && strpos($dbStatsForm, $dbPrefixForm) !== 0) {
	$dbStatsForm = $dbPrefixForm.$dbStatsForm;
}

$dbUserForm = preg_replace('/[^a-zA-Z0-9_\-]/', '', $dbUserForm);
if (!empty($dbPrefixForm) && strpos($dbUserForm, $dbPrefixForm) !== 0) {
	$dbUserForm = $dbPrefixForm.$dbUserForm;
}

$mysqlMainDb = $dbNameForm;
if (empty($mysqlMainDb) || $mysqlMainDb == 'mysql' || $mysqlMainDb == $dbPrefixForm) {
	$mysqlMainDb = $dbPrefixForm.'main';
}

$mysqlStatsDb = $dbStatsForm;
if (empty($mysqlStatsDb) || $mysqlStatsDb == 'mysql' || $mysqlStatsDb == $dbPrefixForm) {
	$mysqlStatsDb = $dbPrefixForm.'stats';
}

$mysqlUserDb = $dbUserForm;
if (empty($mysqlUserDb) || $mysqlUserDb == 'mysql' || $mysqlUserDb == $dbPrefixForm) {
	$mysqlUserDb = $dbPrefixForm.'user';
}

//This parameter is needed to run a command line to install Chamilo using BNPanel + ISPConfig see #1799
if (!defined('CLI_INSTALLATION')) {

	$result = Database::query("SHOW VARIABLES LIKE 'datadir'") or die(Database::error());
	
	$mysqlRepositorySys = Database::fetch_array($result);
	$mysqlRepositorySys = $mysqlRepositorySys['Value'];
	
	if (!$singleDbForm) {
		Database::query("DROP DATABASE IF EXISTS `$mysqlMainDb`") or die(Database::error());
	}
	
	$create_database = true;	
	if ($singleDbForm) {
        if (database_exists($mysqlMainDb)) {
	       $create_database = false;
        }
	}
	
	if ($create_database) {
		$sql = "CREATE DATABASE IF NOT EXISTS `$mysqlMainDb`";
		Database::query($sql) or die(Database::error());
	}	
}

/**
 * CREATING THE STATISTICS DATABASE
 */

$mysqlStatsDb = $mysqlMainDb;
/*
if ($mysqlStatsDb != $mysqlMainDb) {
	if (!$singleDbForm) {
		// multi DB mode AND tracking has its own DB so create it
		Database::query("DROP DATABASE IF EXISTS `$mysqlStatsDb`") or die(Database::error());
		Database::query("CREATE DATABASE `$mysqlStatsDb`") or die(Database::error());
	} else {
		// single DB mode so $mysqlStatsDb MUST BE the SAME than $mysqlMainDb
		$mysqlStatsDb = $mysqlMainDb;
	}
}*/

/**
 * CREATING THE USER DATABASE
 */

$mysqlUserDb = $mysqlMainDb;
/*
if ($mysqlUserDb != $mysqlMainDb) {
	if (!$singleDbForm) {
		// multi DB mode AND user data has its own DB so create it
		Database::query("DROP DATABASE IF EXISTS `$mysqlUserDb`") or die(Database::error());
		Database::query("CREATE DATABASE `$mysqlUserDb`") or die(Database::error());
	} else {
		// single DB mode so $mysqlUserDb MUST BE the SAME than $mysqlMainDb
		$mysqlUserDb = $mysqlMainDb;
	}
}*/

// This parameter is needed to run a command line install of Chamilo (needed for Phing)
if (!defined('CLI_INSTALLATION')) {
	include api_get_path(SYS_LANG_PATH).'english/create_course.inc.php';

	if ($languageForm != 'english') {
		include api_get_path(SYS_LANG_PATH).$languageForm.'/create_course.inc.php';
	}
}

/**
 * Creating the tables of the main database
 */

Database::select_db($mysqlMainDb) or die(Database::error());

$installation_settings['{ORGANISATIONNAME}'] = $institutionForm;
$installation_settings['{ORGANISATIONURL}'] = $institutionUrlForm;
$installation_settings['{CAMPUSNAME}'] = $campusForm;
$installation_settings['{PLATFORMLANGUAGE}'] = $languageForm;
$installation_settings['{ALLOWSELFREGISTRATION}'] = true_false($allowSelfReg);
$installation_settings['{ALLOWTEACHERSELFREGISTRATION}'] = true_false($allowSelfRegProf);
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

//Adds the c_XXX courses tables see #3910
require_once api_get_path(LIBRARY_PATH).'add_course.lib.inc.php'; 

update_Db_course();

/**
 * Creating the tables of the tracking database
 */

//Database::select_db($mysqlMainDb) or die(Database::error());

load_database_script('db_stats.sql');

$track_countries_table = "track_c_countries";
fill_track_countries_table($track_countries_table);

/**
 * Creating the tables of the USER database
 * This is where the personal agenda items are storen, the user defined course categories (sorting of my courses)
 */

//Database::select_db($mysqlMainDb) or die(Database::error());

load_database_script('db_user.sql');
