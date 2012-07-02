<?php

/* For licensing terms, see /license.txt */

/**
 * Chamilo LMS
 *
 * Updates the Dokeos files from version 1.8.6.1 to Chamilo version 1.8.6.2
 * This script operates only in the case of an update, and only to change the
 * active version number (and other things that might need a change) in the
 * current configuration file.
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
            //ignore the line
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

    $perm = api_get_permissions_for_new_directories();

    // The following line is disabled, connection has been already done
    //$link = iDatabase::connect(array('server' => $dbHostForm, 'username' => $dbUsernameForm, 'password' => $dbPassForm));
    //iDatabase::select_db($dbNameForm, $link);
    iDatabase::select_db($dbNameForm);

    $db_name = $dbNameForm;
    $sql = "SELECT * FROM $db_name.course";
    Log::notice('Getting courses for files updates: ' . $sql);
    $result = iDatabase::query($sql);

    if (iDatabase::num_rows($result) > 0) {
        while ($courses_directories = iDatabase::fetch_array($result)) {
            $currentCourseRepositorySys = $sys_course_path . $courses_directories['directory'] . '/';
            // upload > announcements
            if (!is_dir($currentCourseRepositorySys . "upload/announcements")) {
                mkdir($currentCourseRepositorySys . "upload/announcements", $perm);
            }

            // upload > announcements > images
            if (!is_dir($currentCourseRepositorySys . "upload/announcements/images")) {
                mkdir($currentCourseRepositorySys . "upload/announcements/images", $perm);
            }
        }
    }

    //// Create a specific directory for global thumbails
    // home > default_platform_document > template_thumb
    if (!is_dir($pathForm . 'home/default_platform_document/template_thumb')) {
        mkdir($pathForm . 'home/default_platform_document/template_thumb', $perm);
    }
} else {

    echo 'You are not allowed here !' . __FILE__;
}
