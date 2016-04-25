<?php

require_once api_get_path(LIBRARY_PATH).'fileManage.lib.php';

if (!defined('CHAMILO_INTERNAL')) die('You cannot use this script this way');

if ($data->what == 'addinstance' || $data->what == 'registerinstance') {

    unset($data->what);
    unset($data->submitbutton);
    unset($data->id);
    unset($data->vid);
    unset($data->testconnection);
    unset($data->testdatapath);

    var_dump($data);exit;

    $registeronly = $data->registeronly;
    unset($data->registeronly);
    $data->lastcron = 0;
    $data->lastcrongap = 0;
    $data->croncount = 0;

    if (isset($data->template)) {
        $template = $data->template;
        unset($data->template);
    }

    ctrace("Registering VChamilo ");
    $tablename = Database::get_main_table('vchamilo');
    $sql = "SELECT * FROM $tablename WHERE root_web = '".Database::escape_string($data->root_web)."'";
    $result = Database::query($sql);

    if (Database::num_rows($result)) {
        $sql = "SELECT * FROM $tablename WHERE root_web = '".Database::escape_string($data->root_web)."'";
        Database::update($tablename, $data, ['root_web = ?' => $data->root_web]);
        //$DB->update_record('vchamilo', $data, 'root_web');
    } else {
        Database::insert($tablename, (array) $data);
    }

    if ($registeronly){
        // Stop it now.
        ctrace("Registering only. out.");
        vchamilo_redirect(api_get_path(WEB_PLUGIN_PATH).'vchamilo/views/manage.php');
    }

    // or we continue with physical creation

    // Create course directory for operations.
    // this is very important here (DO NOT USE api_get_path() !!) because storage may be remotely located
    $absalternatecourse = vchamilo_get_config('vchamilo', 'course_real_root');
    if (!empty($absalternatecourse)) {
        // this is the relocated case
        $coursedir = str_replace('//', '/', $absalternatecourse.'/'.$data->course_folder);
    } else {
        // this is the standard local case
        $coursedir = api_get_path(SYS_PATH).$data->course_folder;
    }

    if (!is_dir($coursedir)) {
        ctrace("Creating physical course dir in $coursedir");
        mkdir($coursedir, 0777, true);
        // initiate default index
        $INDEX = fopen($coursedir.'/index.html', 'w');
        fputs($INDEX, vchamilo_get_default_course_index_fragment());
        fclose($INDEX);

        $HTACCESS = fopen($coursedir.'/.htaccess', 'w');
        fputs($HTACCESS, vchamilo_get_htaccess_fragment($data->course_folder));
        fclose($HTACCESS);
    }

    // if real coursedir IS NOT under chamilo install, link to it
    $standardlocation = str_replace('//', '/', $_configuration['root_sys'].'/'.$data->course_folder); // where it should be
    ctrace("Checking course dir against standard $standardlocation ");
    ctrace("checking standard location : ".is_dir($standardlocation));
    if ($coursedir != $standardlocation) {

        // The standard location dir SHOULD NOT EXIST YET
        assert(!is_dir($standardlocation));
        ctrace("Linking virtual coursedir ");
        chdir(dirname($standardlocation));
        if (!symlink($coursedir, basename($coursedir))) {
            ctrace("Could not link $standardlocation => $coursedir ");
        }
    } else {
        ctrace("Course dir in standard location");
    }

    // create homedir

    // Structure of virtualized home folders :

    /*
     * {LegacyHomeContainer} => {VChamiloSubcontainer} => {BrandedAccessUrlHome}
     *
     */

    $absalternatehome = vchamilo_get_config('vchamilo', 'home_real_root');
    // absalternatehome is a vchamilo config setting that tells where the
    // real physical storage for home pages are.
    $homedir = str_replace('//', '/', $absalternatehome.'/'.$home_folder);

    ctrace("Making home dir as $homedir ");

    if (!is_dir($homedir)){
        ctrace("Creating home dir ");
        if (!mkdir($homedir, 0777, true)) {
            ctrace("Error creating home dir $homedir \n");
        }
    }

    ctrace("Checking direct home dir ");

    // if real homedir IS NOT under chamilo install, link to it
    // Seems not be necessary as we can globally link the whole Home container
    /*
    $standardlocation = $_configuration['root_sys'].'home/'.$home_folder; // where it should be
    if ($homedir != $standardlocation){
        ctrace("Linking virtual home dir ");
        if (!symlink($homedir, $standardlocation)){
            ctrace("could not link $standardlocation => $homedir ");
        }
    }
    */

    // create archive
    $absalternatearchive = vchamilo_get_config('vchamilo', 'archive_real_root');

    $archivedir = str_replace('//', '/', $absalternatearchive.'/'.$archive_folder);
    if (is_dir($archivedir)) {
        $archivedir = $_configuration['root_sys'].'archive/'.$archive_folder;
    }

    ctrace("Making archive dir as $archivedir ");

    if (!is_dir($archivedir)) {
        ctrace("Creating archive dir ");
        if (!mkdir($archivedir, 0777, true)) {
            ctrace("Error creating archive dir $archivedir\n");
        }
    }

    ctrace("Checking direct archive dir ");

    // if real archivedir IS NOT under chamilo install, link to it
    // Seems not be necessary as we can globally link the whole Home container
    /*
    $standardlocation = $_configuration['root_sys'].'archive/'.$archive_folder; // where it should be
    if ($archivedir != $standardlocation){
        ctrace("Linking virtual archive dir ");
        if (!symlink($archivedir, $standardlocation)){
            ctrace("could not link $standardlocation => $archivedir ");
        }
    }
    */

    if (!$template) {
        // Create empty database for install
        ctrace("Creating databases (empty)");
        vchamilo_create_databases($data);
    } else {
        // Deploy template database
        ctrace("Creating databases from template $template ");
        vchamilo_create_databases($data);
        ctrace("Loading data template $template ");
        vchamilo_load_db_template($data, 'main_database', $template);
        ctrace("Coying files from template $template ");
        vchamilo_load_files_from_template($data, $template);
    }

    ctrace("Fixing records");

    // pluging in site name institution
    $settingstable = Database::get_main_table(TABLE_MAIN_SETTINGS_CURRENT);
    $sitename = Database::escape_string($data->sitename);
    $institution = Database::escape_string($data->institution);
    $sqls[] = "UPDATE {$settingstable} SET selected_value = '{$sitename}' WHERE variable = 'siteName' AND category = 'Platform' ";
    $sqls[] = "UPDATE {$settingstable} SET selected_value = '{$institution}' WHERE variable = 'institution' AND category = 'Platform' ";
    $accessurltable = Database::get_main_table(TABLE_MAIN_ACCESS_URL);
    $sqls[] = "UPDATE {$accessurltable} SET url = '{$data->root_web}' WHERE id = '1' ";

    foreach ($sqls as $sql) {
        Database::query($sql);
    }

    ctrace("Finished");

    echo '<a class="btn btn-primary" href="'.api_get_path(WEB_PLUGIN_PATH).'vchamilo/views/manage.php'.'">Continue</a>';
    // vchamilo_redirect(api_get_path(WEB_PLUGIN_PATH).'vchamilo/views/manage.php');
    die;
}

if ($data->what == 'updateinstance') {

    unset($data->what);
    unset($data->submitbutton);
    unset($data->registeronly);
    unset($data->template);
    $data->lastcron = 0;
    $data->lastcrongap = 0;
    $data->croncount = 0;
    $id = $data->vid;
    unset($data->vid);
    unset($data->testconnection);
    unset($data->testdatapath);
    unset($data->vid);

    Database::update('vchamilo', (array) $data, array('id = ?' => $id), true);
    Display::addFlash(Display::return_message(get_lang('Updated')));
    vchamilo_redirect(api_get_path(WEB_PLUGIN_PATH).'vchamilo/views/manage.php');
}
