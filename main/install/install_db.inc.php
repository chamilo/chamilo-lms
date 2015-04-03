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

$installationSettings = array();
$installationSettings['{ORGANISATIONNAME}'] = $institutionForm;
$installationSettings['{ORGANISATIONURL}'] = $institutionUrlForm;
$installationSettings['{CAMPUSNAME}'] = $campusForm;
$installationSettings['{PLATFORMLANGUAGE}'] = $languageForm;
$installationSettings['{ALLOWSELFREGISTRATION}'] = trueFalse($allowSelfReg);
$installationSettings['{ALLOWTEACHERSELFREGISTRATION}'] = trueFalse($allowSelfRegProf);

$installationSettings['{ADMINLASTNAME}'] = $adminLastName;
$installationSettings['{ADMINFIRSTNAME}'] = $adminFirstName;
$installationSettings['{ADMINLOGIN}'] = $loginForm;
$installationSettings['{ADMINPASSWORD}'] = $passToStore;
$installationSettings['{ADMINEMAIL}'] = $emailForm;
$installationSettings['{ADMINPHONE}'] = $adminPhoneForm;

$installationSettings['{PLATFORM_AUTH_SOURCE}'] = PLATFORM_AUTH_SOURCE;
$installationSettings['{ADMINLANGUAGE}'] = $languageForm;
$installationSettings['{HASHFUNCTIONMODE}'] = $encryptPassForm;

AddCourse::drop_course_tables();

return $manager;
