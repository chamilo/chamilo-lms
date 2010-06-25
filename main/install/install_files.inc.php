<?php
/* For licensing terms, see /license.txt */

/**
 * Install the Chamilo files
 * Notice : This script has to be included by install/index.php
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
	write_system_config_file(api_get_path(CONFIGURATION_PATH).'configuration.php');

	// Write a distribution file with the config as a backup for the admin
	write_system_config_file(api_get_path(CONFIGURATION_PATH).'configuration.dist.php');

	// Write a .htaccess file in the course repository
	write_courses_htaccess_file($urlAppendPath);

	// Copy distribution files with renaming for being the actual system configuration files.
	copy(api_get_path(CONFIGURATION_PATH).'add_course.conf.dist.php', api_get_path(CONFIGURATION_PATH).'add_course.conf.php');
	copy(api_get_path(CONFIGURATION_PATH).'course_info.conf.dist.php', api_get_path(CONFIGURATION_PATH).'course_info.conf.php');
	copy(api_get_path(CONFIGURATION_PATH).'mail.conf.dist.php', api_get_path(CONFIGURATION_PATH).'mail.conf.php');
	copy(api_get_path(CONFIGURATION_PATH).'profile.conf.dist.php', api_get_path(CONFIGURATION_PATH).'profile.conf.php');

} else {

	echo 'You are not allowed here !';

}
