<?php
/* For licensing terms, see /license.txt */

/**
 * Chamilo LMS
 *
 * Updates the Chamilo files from version 1.9.0 to version 1.10.0
 * This script operates only in the case of an update, and only to change the
 * active version number (and other things that might need a change) in the
 * current configuration file.
 * @package chamilo.install
 */
Log::notice('Entering file');

if (defined('SYSTEM_INSTALLATION')) {

    $conf_dir = api_get_path(CONFIGURATION_PATH);

    // Changes for 1.10.x
    // Delete directories and files that are not necessary anymore
    // pChart (1) lib, etc

} else {
    echo 'You are not allowed here !'. __FILE__;
}
