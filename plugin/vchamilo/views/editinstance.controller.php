<?php

require_once api_get_path(LIBRARY_PATH).'fileManage.lib.php';

api_protect_admin_script();

if (!defined('CHAMILO_INTERNAL')) die('You cannot use this script this way');

if ($data->what == 'addinstance' || $data->what == 'registerinstance') {

    unset($data->what);
    unset($data->submitbutton);
    unset($data->id);
    unset($data->vid);
    unset($data->testconnection);
    unset($data->testdatapath);

    $registeronly = $data->registeronly;
    unset($data->registeronly);
    $data->lastcron = 0;
    $data->lastcrongap = 0;
    $data->croncount = 0;

    if (!empty($data->template)) {
        $template = $data->template;
    } else {
        $template = '';
    }

    $mainDatabase = api_get_configuration_value('main_database');

    if ($mainDatabase == $data->main_database) {
        Display::addFlash(
            Display::return_message('You cannot use the same database as the chamilo master', 'error')
        );
        return ;
    }

    ctrace("Registering: ".$data->root_web);
    $tablename = Database::get_main_table('vchamilo');
    $sql = "SELECT * FROM $tablename 
            WHERE root_web = '".Database::escape_string($data->root_web)."'";
    $result = Database::query($sql);

    if (Database::num_rows($result)) {
        $sql = "SELECT * FROM $tablename 
                WHERE root_web = '".Database::escape_string($data->root_web)."'";
        Database::update($tablename, $data, ['root_web = ?' => $data->root_web]);
        $virtualInfo = Database::fetch_array($result);
        $slug = $virtualInfo['slug'];
    } else {
        $slug = $data->slug = vchamilo_get_slug_from_url($data->root_web);
        Database::insert($tablename, (array) $data);
    }

    if ($registeronly) {
        // Stop it now.
        ctrace("Registering only. out.");
        vchamilo_redirect(api_get_path(WEB_PLUGIN_PATH).'vchamilo/views/manage.php');
    }

    // or we continue with physical creation

    // Create course directory for operations.
    // this is very important here (DO NOT USE api_get_path() !!) because storage may be remotely located
    $absalternatecourse = vchamilo_get_config('vchamilo', 'course_real_root');
    $coursedir = $absalternatecourse.'/'.$slug;

    if (!is_dir($coursedir)) {
        ctrace("Creating physical course dir in $coursedir");
        mkdir($coursedir, 0777, true);
        // initiate default index
        $INDEX = fopen($coursedir.'/index.html', 'w');
        fputs($INDEX, vchamilo_get_default_course_index_fragment());
        fclose($INDEX);

        $HTACCESS = fopen($coursedir.'/.htaccess', 'w');
        fputs($HTACCESS, vchamilo_get_htaccess_fragment($slug));
        fclose($HTACCESS);
    }

    // if real coursedir IS NOT under chamilo install, link to it
    /*$standardlocation = str_replace('//', '/', $_configuration['root_sys'].'/'.$data->course_folder); // where it should be
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
    }*/

    // create homedir

    // Structure of virtualized home folders :

    /*
     * {LegacyHomeContainer} => {VChamiloSubcontainer} => {BrandedAccessUrlHome}
     *
     */
    $absalternatehome = vchamilo_get_config('vchamilo', 'home_real_root');
    // absalternatehome is a vchamilo config setting that tells where the
    // real physical storage for home pages are.
    $homedir = str_replace('//', '/', $absalternatehome.'/'.$slug);

    ctrace("Making home dir as $homedir");

    if (!is_dir($homedir)) {
        if (!mkdir($homedir, 0777, true)) {
            ctrace("Error creating home dir $homedir \n");
        }
    }

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
    $archivedir = $absalternatearchive.'/'.$slug;

    ctrace("Making archive dir as $archivedir ");

    if (!is_dir($archivedir)) {
        if (!mkdir($archivedir, 0777, true)) {
            ctrace("Error creating archive dir $archivedir\n");
        }
    }

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
        vchamilo_load_db_template($data, $template);
        ctrace("Coying files from template $template ");
        vchamilo_load_files_from_template($data, $template);
    }


    // pluging in site name institution
    $settingstable = $data->main_database.'.settings_current';
    $accessurltable = $data->main_database.'.access_url';

    $sitename = Database::escape_string($data->sitename);
    $institution = Database::escape_string($data->institution);
    $sqls[] = "UPDATE {$settingstable} SET selected_value = '{$sitename}' 
               WHERE variable = 'siteName' AND category = 'Platform' ";
    $sqls[] = "UPDATE {$settingstable} SET selected_value = '{$institution}' 
               WHERE variable = 'institution' AND category = 'Platform' ";

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
