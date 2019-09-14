<?php
/* For licensing terms, see /license.txt */
/**
 * This script lists the necessary variables that allow the installation
 * system to know in which version is the current Chamilo install. This
 * script should be overwritten with each upgrade of Chamilo. It is not
 * required from any other process of Chamilo than the installation or upgrade.
 * It also helps for automatic packaging of unstable versions.
 *
 * @package chamilo.install
 */

/**
 * Variables used from the main/install/index.php.
 */
$new_version = '1.11.10';
$new_version_status = 'stable';
$new_version_last_id = 0;
$new_version_stable = true;
$new_version_major = false;
$software_name = 'Chamilo';
$software_url = 'https://chamilo.org/';
