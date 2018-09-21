<?php
/* For licensing terms, see /license.txt */

/**
 * Install the Chamilo files
 * Notice : This script has to be included by install/index.php.
 *
 * The script creates two files:
 * - configuration.php, the file that contains very important info for Chamilo
 *   such as the database names.
 * - .htaccess file (in the courses directory) that is optional but improves
 *   security
 *
 * @package chamilo.install
 */
if (defined('SYSTEM_INSTALLATION')) {
    // Write the system config file
    write_system_config_file(api_get_path(SYS_PATH).'config/configuration.php');
} else {
    echo 'You are not allowed here !'.__FILE__;
}
