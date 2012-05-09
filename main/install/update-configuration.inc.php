<?php

/* For licensing terms, see /license.txt */
/**
 * 
 * Chamilo LMS
 * 
 * Only updates the  main/inc/conf/configuration.php  file with the new version use only from 1.8.8.1 to 1.8.8.2 (with no DB changes) 
 * @package chamilo.install
 */
if (defined('SYSTEM_INSTALLATION')) {

    Log::notice("Starting " . basename(__FILE__));

    // Edit the configuration file
    $file = file(api_get_path(CONFIGURATION_PATH) . 'configuration.php');
    $fh = fopen(api_get_path(CONFIGURATION_PATH) . 'configuration.php', 'w');
    $found_version_old = false;
    $found_stable_old = false;
    $found_version = false;
    $found_stable = false;
    $found_software_name = false;
    $found_software_url = false;

    foreach ($file as $line) {
        $ignore = false;
        if (stripos($line, '$_configuration[\'dokeos_version\']') !== false) {
            $found_version_old = true;
            $line = '$_configuration[\'dokeos_version\'] = \'' . $new_version . '\';' . "\r\n";
            $ignore = true;
        } elseif (stripos($line, '$_configuration[\'system_version\']') !== false) {
            $found_version = true;
            $line = '$_configuration[\'system_version\'] = \'' . $new_version . '\';' . "\r\n";
        } elseif (stripos($line, '$_configuration[\'dokeos_stable\']') !== false) {
            $found_stable_old = true;
            $line = '$_configuration[\'dokeos_stable\'] = ' . ($new_version_stable ? 'true' : 'false') . ';' . "\r\n";
            $ignore = true;
        } elseif (stripos($line, '$_configuration[\'system_stable\']') !== false) {
            $found_stable = true;
            $line = '$_configuration[\'system_stable\'] = ' . ($new_version_stable ? 'true' : 'false') . ';' . "\r\n";
        } elseif (stripos($line, '$_configuration[\'software_name\']') !== false) {
            $found_software_name = true;
            $line = '$_configuration[\'software_name\'] = \'' . $software_name . '\';' . "\r\n";
        } elseif (stripos($line, '$_configuration[\'software_url\']') !== false) {
            $found_software_url = true;
            $line = '$_configuration[\'software_url\'] = \'' . $software_url . '\';' . "\r\n";
        } elseif (stripos($line, '$userPasswordCrypted') !== false) {
            $line = '$userPasswordCrypted = \'' . ($userPasswordCrypted) . '\';' . "\r\n";
        } elseif (stripos($line, '?>') !== false) {
            $ignore = true;
        }
        if (!$ignore) {
            fwrite($fh, $line);
        }
    }
    if (!$found_version) {
        fwrite($fh, '$_configuration[\'system_version\'] = \'' . $new_version . '\';' . "\r\n");
    }
    if (!$found_stable) {
        fwrite($fh, '$_configuration[\'system_stable\'] = ' . ($new_version_stable ? 'true' : 'false') . ';' . "\r\n");
    }
    if (!$found_software_name) {
        fwrite($fh, '$_configuration[\'software_name\'] = \'' . $software_name . '\';' . "\r\n");
    }
    if (!$found_software_url) {
        fwrite($fh, '$_configuration[\'software_url\'] = \'' . $software_url . '\';' . "\r\n");
    }
    fwrite($fh, '?>');
    fclose($fh);
} else {
    echo 'You are not allowed here !';
}