<?php //$Id: update-files-1.8.3-1.8.4.inc.php 19996 2009-04-22 22:11:14Z cvargas1 $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004-2007 Dokeos S.A.
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
* Updates the Dokeos files from version 1.8.3 to version 1.8.4
* This script operates only in the case of an update, and only to change the
* active version number (and other things that might need a change) in the
* current configuration file.
* As of 1.8.4, the Dokeos version has been added to configuration.php to
* allow for edition (inc/conf is one of the directories that needs write
* permissions on upgrade).
* Being in configuration.php, it benefits from the configuration.dist.php
* advantages that a new version doesn't overwrite it, thus letting the old
* version be available until the end of the installation.
* @package dokeos.install
==============================================================================
*/
require_once("../inc/lib/main_api.lib.php");
require_once("../inc/lib/fileUpload.lib.php");
require_once('../inc/lib/database.lib.php');

if (defined('DOKEOS_INSTALL') || defined('DOKEOS_COURSE_UPDATE'))
{
	// Edit the Dokeos config file
	$file = file('../inc/conf/configuration.php');
	$fh = fopen('../inc/conf/configuration.php','w');
	$found_version = false;
	$found_stable = false;
	foreach($file as $line)
	{
		$ignore = false;
		if(stristr($line,'$_configuration[\'dokeos_version\']'))
		{
			$found_version = true;
			$line = '$_configuration[\'dokeos_version\'] = \''.$new_version.'\';'."\r\n";
		}
		elseif(stristr($line,'$_configuration[\'dokeos_stable\']'))
		{
			$found_stable = true;
			$line = '$_configuration[\'dokeos_stable\'] = '.($new_version_stable?'true':'false').';'."\r\n";
		}
		elseif(stristr($line,'?>'))
		{
			//ignore the line
			$ignore = true;
		}
		if(!$ignore)
		{
			fwrite($fh,$line);
		}
	}
	if(!$found_version)
	{
		fwrite($fh,'$_configuration[\'dokeos_version\'] = \''.$new_version.'\';'."\r\n");
	}
	if(!$found_stable)
	{
		fwrite($fh,'$_configuration[\'dokeos_stable\'] = '.($new_version_stable?'true':'false').';'."\r\n");
	}
	fwrite($fh,'?>');
	fclose($fh);
}
else
{
	echo 'You are not allowed here !';
}
?>