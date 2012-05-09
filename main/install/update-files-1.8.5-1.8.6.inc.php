<?php

/* For licensing terms, see /license.txt */

/**
 * Chamilo LMS
 *
 * Updates the Dokeos files from version 1.8.5 to version 1.8.6
 * This script operates only in the case of an update, and only to change the
 * active version number (and other things that might need a change) in the
 * current configuration file.
 * As of 1.8.6, the Dokeos version has been added to configuration.php to
 * allow for edition (inc/conf is one of the directories that needs write
 * permissions on upgrade).
 * Being in configuration.php, it benefits from the configuration.dist.php
 * advantages that a new version doesn't overwrite it, thus letting the old
 * version be available until the end of the installation.
 * @package chamilo.install
 */

Log::notice('Entering file');

if (defined('SYSTEM_INSTALLATION')) {

    // Edit the configuration file
    $file = file(api_get_path(CONFIGURATION_PATH) . 'configuration.php');
    $fh = fopen(api_get_path(CONFIGURATION_PATH) . 'configuration.php', 'w');
    $found_version = false;
    $found_stable = false;
    foreach ($file as $line) {
        $ignore = false;
        if (stripos($line, '$_configuration[\'dokeos_version\']') !== false) {
            $found_version = true;
            $line = '$_configuration[\'dokeos_version\'] = \'' . $new_version . '\';' . "\r\n";
        } elseif (stripos($line, '$_configuration[\'dokeos_stable\']') !== false) {
            $found_stable = true;
            $line = '$_configuration[\'dokeos_stable\'] = ' . ($new_version_stable ? 'true' : 'false') . ';' . "\r\n";
        } elseif (stripos($line, '$userPasswordCrypted') !== false) {
            $line = '$userPasswordCrypted 									= \'' . ($userPasswordCrypted) . '\';' . "\r\n";
        } elseif (stripos($line, '?>') !== false) {
            // Ignore the line
            $ignore = true;
        }
        if (!$ignore) {
            fwrite($fh, $line);
        }
    }
    if (!$found_version) {
        fwrite($fh, '$_configuration[\'dokeos_version\'] = \'' . $new_version . '\';' . "\r\n");
    }
    if (!$found_stable) {
        fwrite($fh, '$_configuration[\'dokeos_stable\'] = ' . ($new_version_stable ? 'true' : 'false') . ';' . "\r\n");
    }
    fwrite($fh, '?>');
    fclose($fh);

    $sys_course_path = $pathForm . 'courses/';

    //$tbl_course = Database :: get_main_table(TABLE_MAIN_COURSE);
    //// Linking (The following line is disabled, connection has been already done)
    //$res = @iDatabase::connect(array('server' => $dbHostForm, 'username' => $dbUsernameForm, 'password' => $dbPassForm));
    //iDatabase::select_db($dbNameForm, $link);
    iDatabase::select_db($dbNameForm);

    $db_name = $dbNameForm;
    $sql = "SELECT * FROM $db_name.course";
    Log::notice('Getting courses for files updates: ' . $sql);
    $result = iDatabase::query($sql);

    while ($courses_directories = iDatabase::fetch_array($result)) {

        $currentCourseRepositorySys = $sys_course_path . $courses_directories['directory'] . '/';

        $db_name = $courses_directories['db_name'];
        $origCRS = $updatePath . 'courses/' . $courses_directories['directory'];

        if (!is_dir($origCRS)) {
            Log::error('Directory ' . $origCRS . ' does not exist. Skipping.');
            continue;
        }
        // Move everything to the new hierarchy (from old path to new path)
        Log::notice('Renaming ' . $origCRS . ' to ' . $sys_course_path . $courses_directories['directory']);
        rename($origCRS, $sys_course_path . $courses_directories['directory']);
        Log::notice('Creating dirs in ' . $currentCourseRepositorySys);

        // DOCUMENT FOLDER
        // document > shared_folder
        if (!is_dir($currentCourseRepositorySys . "document/shared_folder")) {
            mkdir($currentCourseRepositorySys . "document/shared_folder", $perm);
        }

        // UPLOAD FOLDER
        // upload > forum > images
        if (!is_dir($currentCourseRepositorySys . "upload/forum/images")) {
            mkdir($currentCourseRepositorySys . "upload/forum/images", $perm);
        }

        // upload > learning_path
        if (!is_dir($currentCourseRepositorySys . "upload/learning_path")) {
            mkdir($currentCourseRepositorySys . "upload/learning_path", $perm);
        }

        // upload > learning_path > images
        if (!is_dir($currentCourseRepositorySys . "upload/learning_path/images")) {
            mkdir($currentCourseRepositorySys . "upload/learning_path/images", $perm);
        }

        // upload > calendar
        if (!is_dir($currentCourseRepositorySys . "upload/calendar")) {
            mkdir($currentCourseRepositorySys . "upload/calendar", $perm);
        }

        // upload > calendar > images
        if (!is_dir($currentCourseRepositorySys . "upload/calendar/images")) {
            mkdir($currentCourseRepositorySys . "upload/calendar/images", $perm);
        }
    }
} else {

    echo 'You are not allowed here !';
}
