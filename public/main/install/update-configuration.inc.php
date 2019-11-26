<?php
/* For licensing terms, see /license.txt */

/**
 * Chamilo LMS.
 *
 * Only updates the  main/inc/conf/configuration.php
 *
 * @package chamilo.install
 */
if (defined('SYSTEM_INSTALLATION')) {
    error_log("Starting ".basename(__FILE__));
    $perm = api_get_permissions_for_new_files();

    $newConfFile = api_get_path(CONFIGURATION_PATH).'configuration.php';
    // Check $fromVersionShort, defined in install.lib.php, in the switch
    // on full version numbers, to know from which version we are upgrading
    if ($fromVersionShort == '1.9') {
        $oldConfFile = api_get_path(SYS_CODE_PATH).'inc/conf/configuration.php';

        if (file_exists($oldConfFile)) {
            copy($oldConfFile, $newConfFile);
            @chmod($newConfFile, $perm);
            @rmdir($oldConfFile);
        }
    }

    // Edit the configuration file.
    $file = file($newConfFile);
    $fh = fopen($newConfFile, 'w');

    $found_version_old = false;
    $found_stable_old = false;
    $found_version = false;
    $found_stable = false;
    $found_software_name = false;
    $found_software_url = false;

    foreach ($file as $line) {
        $ignore = false;
        if (stripos($line, '$_configuration[\'system_version\']') !== false) {
            $found_version = true;
            $line = '$_configuration[\'system_version\'] = \''.$GLOBALS['new_version'].'\';'."\r\n";
        } elseif (stripos($line, '$_configuration[\'system_stable\']') !== false) {
            $found_stable = true;
            $line = '$_configuration[\'system_stable\'] = '.($GLOBALS['new_version_stable'] ? 'true' : 'false').';'."\r\n";
        } elseif (stripos($line, '$_configuration[\'software_name\']') !== false) {
            $found_software_name = true;
            $line = '$_configuration[\'software_name\'] = \''.$GLOBALS['software_name'].'\';'."\r\n";
        } elseif (stripos($line, '$_configuration[\'software_url\']') !== false) {
            $found_software_url = true;
            $line = '$_configuration[\'software_url\'] = \''.$GLOBALS['software_url'].'\';'."\r\n";
        } elseif (stripos($line, '$userPasswordCrypted') !== false) {
            $line = '$_configuration[\'password_encryption\'] = \''.$userPasswordCrypted.'\';'."\r\n";
        } elseif (stripos($line, '?>') !== false) {
            $ignore = true;
        }
        if (!$ignore) {
            fwrite($fh, $line);
        }
    }

    if (!$found_version) {
        fwrite($fh, '$_configuration[\'system_version\'] = \''.$new_version.'\';'."\r\n");
    }
    if (!$found_stable) {
        fwrite($fh, '$_configuration[\'system_stable\'] = '.($new_version_stable ? 'true' : 'false').';'."\r\n");
    }
    if (!$found_software_name) {
        fwrite($fh, '$_configuration[\'software_name\'] = \''.$software_name.'\';'."\r\n");
    }
    if (!$found_software_url) {
        fwrite($fh, '$_configuration[\'software_url\'] = \''.$software_url.'\';'."\r\n");
    }
    fclose($fh);

    error_log("configuration.php file updated.");
} else {
    echo 'You are not allowed here !'.__FILE__;
}
