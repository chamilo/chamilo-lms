<?php // $Id: install_files.inc.php 11139 2007-02-17 15:43:14Z yannoo $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004 Dokeos S.A.
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact address: Dokeos, 44 rue des palais, B-1030 Brussels, Belgium
	Mail: info@dokeos.com
==============================================================================
*/
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