<?php
/* For licensing terms, see /license.txt */

/**
 * Chamilo LMS.
 *
 * Updates the Chamilo files from version 1.10.0 to version 1.11.0
 * This script operates only in the case of an update, and only to change the
 * active version number (and other things that might need a change) in the
 * current configuration file.
 *
 * @package chamilo.install
 */
error_log("Starting ".basename(__FILE__));

global $debug;

if (defined('SYSTEM_INSTALLATION')) {
    // Changes for 2.0.0
    error_log('Finish script '.basename(__FILE__));
} else {
    echo 'You are not allowed here !'.__FILE__;
}
