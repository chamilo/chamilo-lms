<?php
/* For licensing terms, see /license.txt */

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

/**
 * Chamilo LMS
 *
 * Updates the Chamilo files from version 1.10.0 to version 1.11.0
 * This script operates only in the case of an update, and only to change the
 * active version number (and other things that might need a change) in the
 * current configuration file.
 * @package chamilo.install
 */
error_log("Starting " . basename(__FILE__));

global $debug;

if (defined('SYSTEM_INSTALLATION')) {
    // Changes for 1.11.x
    // Delete directories and files that are not necessary anymore

    // The main/exercice path was moved to main/exercise, so the code from 1.11
    // should just create the new directory, and we should delete the previous
    // one to avoid the web server to use the old
    $exercisePath = api_get_path(SYS_CODE_PATH) . 'exercice';
    if (is_dir($exercisePath)) {
        @rrmdir($exercisePath);
    }
    // Same with main/newscorm, renamed main/lp
    $lpPath = api_get_path(SYS_CODE_PATH) . 'newscorm';
    if (is_dir($lpPath)) {
        @rrmdir($lpPath);
    }
    $ticketPluginPath = api_get_path(SYS_PLUGIN_PATH) . 'ticket';
    if (is_dir($ticketPluginPath)) {
        @rrmdir($ticketPluginPath);
    }

    if ($debug) {
        error_log('Folders cleaned up');
    }
} else {
    echo 'You are not allowed here !'. __FILE__;
}
