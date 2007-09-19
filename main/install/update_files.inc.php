<?php
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
* Updates the Dokeos files from an older version
* IMPORTANT: This script has to be included by install/index.php and update_courses.php
*
* DOKEOS_INSTALL is defined in the install/index.php
* DOKEOS_COURSE_UPDATE is defined in update_courses.php
*
* When DOKEOS_INSTALL or DOKEOS_COURSE_UPDATE is defined, do for every course:
* - remove the .htaccess in the document folder
* - remove the index.php in the group folder
* - write a new group/index.php file, make it an empty html file
* - remove the index.php of the course folder
* - write a new index.php file in the course folder, with some settings
* - create a 'temp' directory in the course folder
* - move the course folder inside the courses folder of the new Dokeos installation
* - move the group documents from the group folder to the document folder,
*   keeping subfolders intact
* - stores all documents inside the database (document and item_property tables)
* - remove the visibility field from the document table
* - update the item properties of the group documents
*
* Additionally, when DOKEOS_INSTALL is defined
* - write a config file, configuration.php, with important settings
* - write a .htaccess file (with instructions for Apache) in the courses directory
* - remove the new main/upload/users directory and rename the main/img/users
*   directory of the old version to main/upload/users
* - rename the old configuration.php to configuration.php.old,
*   or if this fails delete the old configuration.php
*
* @package dokeos.install
==============================================================================
*/
/*
==============================================================================
		FUNCTIONS
==============================================================================
*/

/**
* This function puts the documents of the upgraded courses
* into the necessary tables of the new version:
* the document and item_property tables.
*
* It is used to upgrade from Dokeos 1.5.x versions to
* Dokeos 1.6
*
* @return boolean true if everything worked, false otherwise
*/
function fill_document_table($dir)
{
	global $newPath, $course, $mysql_base_course, $_configuration;

	$documentPath = $newPath.'courses/'.$course.'/document';

	if (!@ $opendir = opendir($dir))
	{
		return false;
	}

	while ($readdir = readdir($opendir))
	{
		if ($readdir != '..' && $readdir != '.' && $readdir != '.htaccess')
		{
			$path = str_replace($documentPath, '', $dir.'/'.$readdir);
			$file_date = date("Y-m-d H:i:s", filemtime($dir.'/'.$readdir));

			if (is_file($dir.'/'.$readdir))
			{
				$file_size = filesize($dir.'/'.$readdir);

				$result = mysql_query("SELECT id,visibility FROM `$mysql_base_course".$_configuration['db_glue']."document` WHERE path='".addslashes($path)."' LIMIT 0,1");

				if (list ($id, $visibility) = mysql_fetch_row($result))
				{
					mysql_query("UPDATE `$mysql_base_course".$_configuration['db_glue']."document` SET filetype='file',title='".addslashes($readdir)."',size='$file_size' WHERE id='$id' AND path='".addslashes($path)."'");
				}
				else
				{
					mysql_query("INSERT INTO `$mysql_base_course".$_configuration['db_glue']."document`(path,filetype,title,size) VALUES('".addslashes($path)."','file','".addslashes($readdir)."','$file_size')");

					$id = mysql_insert_id();
				}

				$visibility = ($visibility == 'v') ? 1 : 0;

				mysql_query("INSERT INTO `$mysql_base_course".$_configuration['db_glue']."item_property`(tool,ref,visibility,lastedit_type,to_group_id,insert_date,lastedit_date) VALUES('document','$id','$visibility','DocumentAdded','0','".$file_date."','".$file_date."')");
			}
			elseif (is_dir($dir.'/'.$readdir))
			{
				$result = mysql_query("SELECT id,visibility FROM `$mysql_base_course".$_configuration['db_glue']."document` WHERE path='".addslashes($path)."' LIMIT 0,1");

				if (list ($id, $visibility) = mysql_fetch_row($result))
				{
					mysql_query("UPDATE `$mysql_base_course".$_configuration['db_glue']."document` SET filetype='folder',title='".addslashes($readdir)."' WHERE id='$id' AND path='".addslashes($path)."'");
				}
				else
				{
					mysql_query("INSERT INTO `$mysql_base_course".$_configuration['db_glue']."document`(path,filetype,title) VALUES('".addslashes($path)."','folder','".addslashes($readdir)."')");

					$id = mysql_insert_id();
				}

				$visibility = ($visibility == 'v') ? 1 : 0;

				mysql_query("INSERT INTO `$mysql_base_course".$_configuration['db_glue']."item_property`(tool,ref,visibility, lastedit_type, to_group_id,insert_date,lastedit_date) VALUES('document','$id','$visibility','FolderCreated','0','".$file_date."','".$file_date."')");

				if (!fill_document_table($dir.'/'.$readdir))
				{
					return false;
				}
			}
		}
	}

	closedir($opendir);

	return true;
}

/*
==============================================================================
		INIT SECTION
==============================================================================
*/

if (defined('DOKEOS_INSTALL') || defined('DOKEOS_COURSE_UPDATE'))
{
	$newPath = str_replace('\\', '/', realpath('../..')).'/';
	$oldPath = $_POST['updatePath'];

	$perm = api_get_setting('permissions_for_new_directories');
	$perm = octdec(!empty($perm)?$perm:'0770');

	foreach ($coursePath as $key => $course)
	{
		$mysql_base_course = $courseDB[$key];

		@ unlink($oldPath.$course.'/document/.htaccess');

		@ unlink($oldPath.$course.'/group/index.php');

		if ($fp = @ fopen($oldPath.$course.'/group/index.php', 'w'))
		{
			fputs($fp, '<html></html>');

			fclose($fp);
		}

		@ unlink($oldPath.$course.'/index.php');

		if ($fp = @ fopen($oldPath.$course.'/index.php', 'w'))
		{
			fputs($fp, '<?php
															$cidReq = "'.$key.'";
															$dbname = "'.str_replace($dbPrefixForm, '', $mysql_base_course).'";
									
															include("../../main/course_home/course_home.php");
															?>');

			fclose($fp);
		}

		@ mkdir($oldPath.$course.'/temp', $perm);
		@ chmod($oldPath.$course.'/temp', $perm);

		@ rename($oldPath.$course, $newPath.'courses/'.$course);

		// Move group documents to document folder of the course
		$group_dir = $newPath.'courses/'.$course.'/group';

		if ($dir = @ opendir($group_dir))
		{
			while (($entry = readdir($dir)) !== false)
			{
				if ($entry != '.' && $entry != '..' && is_dir($group_dir.'/'.$entry))
				{
					$from_dir = $group_dir.'/'.$entry;
					$to_dir = $newPath.'courses/'.$course.'/document/'.$entry;

					@ rename($from_dir, $to_dir);
				}
			}

			closedir($dir);
		}

		fill_document_table($newPath.'courses/'.$course.'/document');

		mysql_query("ALTER TABLE `$mysql_base_course".$_configuration['db_glue']."document` DROP `visibility`");

		// Update item_properties of group documents
		$sql = "SELECT d.id AS doc_id, g.id AS group_id FROM `$mysql_base_course".$_configuration['db_glue']."group_info` g,`$mysql_base_course".$_configuration['db_glue']."document` d WHERE path LIKE CONCAT(g.secret_directory,'%')";
		$res = mysql_query($sql);

		while ($group_doc = mysql_fetch_object($res))
		{
			$sql = "UPDATE `$mysql_base_course".$_configuration['db_glue']."item_property` SET to_group_id = '".$group_doc->group_id."', visibility = '1' WHERE ref = '".$group_doc->doc_id."' AND tool = '".TOOL_DOCUMENT."'";
			mysql_query($sql);
		}
	}

	if (defined('DOKEOS_INSTALL'))
	{
		// Write the Dokeos config file
		write_dokeos_config_file($newPath.'main/inc/conf/configuration.php');
		// Write a distribution file with the config as a backup for the admin
		write_dokeos_config_file($newPath.'main/inc/conf/configuration.dist.php');
		// Write a .htaccess file in the course repository
		write_courses_htaccess_file($urlAppendPath);

		require_once ('../inc/lib/fileManage.lib.php');
		// First remove the upload/users directory in the new installation
		removeDir($newPath.'main/upload/users');
		// Move the old user images to the new installation
		@ rename($oldPath.'main/img/users', $newPath.'main/upload/users');

		if (!@ rename($oldPath.'main/inc/conf/configuration.php', $oldPath.'main/inc/conf/configuration.php.old'))
		{
			unlink($oldPath.'main/inc/conf/configuration.php');
		}
	}
}
else
{
	echo 'You are not allowed here !';
}
?>