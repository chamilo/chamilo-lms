<?php
/* For licensing terms, see /license.txt */
/**
 *    Install the Chamilo database
 *    Notice : This script has to be included by index.php
 *
 * @package chamilo.install
 */

/* This page is called only during a NEW chamilo installation */
/* This page can only be access through including from the install script.  */

/**
 * Init checks
 */
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

$manager = testDbConnect(
    $dbHostForm,
    $dbUsernameForm,
    $dbPassForm,
    null
);

$dbNameForm = preg_replace('/[^a-zA-Z0-9_\-]/', '', $dbNameForm);

//This parameter is needed to run a command line to install Chamilo using BNPanel + ISPConfig see #1799
if (!defined('CLI_INSTALLATION')) {
    $createDatabase = true;
    $databases = $manager->getConnection()->getSchemaManager()->listDatabases();

    if (in_array($dbNameForm, $databases)) {
        $createDatabase = false;
    }

    // Create database
    if ($createDatabase) {
        $manager->getConnection()->getSchemaManager()->createDatabase($dbNameForm);
    }

    $manager = testDbConnect(
        $dbHostForm,
        $dbUsernameForm,
        $dbPassForm,
        $dbNameForm
    );
}

// This parameter is needed to run a command line install of Chamilo (needed for Phing)
if (!defined('CLI_INSTALLATION')) {
    include_once api_get_path(SYS_LANG_PATH) . 'english/trad4all.inc.php';

    if ($languageForm != 'english') {
        include_once api_get_path(SYS_LANG_PATH) . $languageForm . '/trad4all.inc.php';
    }
}

$installation_settings = array();
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

AddCourse::drop_course_tables();

// Initialization of the database encoding to be used.
Database::query("SET storage_engine = INNODB;");
Database::query("SET SESSION character_set_server='utf8';");
Database::query("SET SESSION collation_server='utf8_general_ci';");
Database::query("SET CHARACTER SET 'utf8';"); // See task #1802.
//Database::query("SET NAMES 'utf8';");

createSchema($manager, $installation_settings);

lockSettings();

update_dir_and_files_permissions();

return $manager;
