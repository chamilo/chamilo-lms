<?php // $Id: install_files.inc.php 22577 2009-08-03 04:31:24Z yannoo $
/* For licensing terms, see /dokeos_license.txt */
/**
==============================================================================
* Install the Dokeos files
* Notice : This script has to be included by install/index.php
*
* The script creates two files:
* - configuration.php, the file that contains very important info for Dokeos
*   such as the database names.
* - .htaccess file (in the courses directory) that is optional but improves
*   security
*
* @package dokeos.install
==============================================================================
*/

if(defined('DOKEOS_INSTALL'))
{
	// Write the Dokeos config file
	write_dokeos_config_file('../inc/conf/configuration.php');
	// Write a distribution file with the config as a backup for the admin
	write_dokeos_config_file('../inc/conf/configuration.dist.php');
	// Write a .htaccess file in the course repository
	write_courses_htaccess_file($urlAppendPath);
	//copy distribution files into right names for Dokeos install
	copy('../inc/conf/add_course.conf.dist.php','../inc/conf/add_course.conf.php');
	copy('../inc/conf/course_info.conf.dist.php','../inc/conf/course_info.conf.php');
	copy('../inc/conf/mail.conf.dist.php','../inc/conf/mail.conf.php');
	copy('../inc/conf/profile.conf.dist.php','../inc/conf/profile.conf.php');
}
else
{
	echo 'You are not allowed here !';
}
?>