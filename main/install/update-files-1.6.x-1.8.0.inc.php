<?php //$Id: update-files-1.6.x-1.8.0.inc.php 17420 2008-12-22 11:50:15Z ivantcholakov $
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
* Updates the Dokeos files from version 1.6.x to version 1.8.0
* IMPORTANT: This script has to be included by install/index.php or 
* update_courses.php
*
* DOKEOS_INSTALL is defined in the install/index.php (means that we are in 
* the regular upgrade process)
* 
* DOKEOS_COURSE_UPDATE is defined in update_courses.php (means we are 
* executing update_courses.php to update courses separately)
*
* When DOKEOS_INSTALL or DOKEOS_COURSE_UPDATE are defined, do for every course:
* - create a new set of directories that reflect the new tools offered by 1.8
* - record an item_property for each directory added
*
* @package dokeos.install
==============================================================================
*/

require_once("../inc/lib/main_api.lib.php");
require_once("../inc/lib/fileUpload.lib.php");
require_once('../inc/lib/database.lib.php');
require_once('install_upgrade.lib.php');

/*
==============================================================================
		FUNCTIONS
==============================================================================
*/
function insert_db($db_name, $folder_name, $text){

	$_course['dbName'] = $db_name;
	
	$doc_id = add_document_180($_course, '/'.$folder_name, 'folder', 0, ucfirst($text));
	api_item_property_update($_course, TOOL_DOCUMENT, $doc_id, 'invisible', 1);
	
}

/*
==============================================================================
		INIT SECTION
==============================================================================
*/

if (defined('DOKEOS_INSTALL') || defined('DOKEOS_COURSE_UPDATE'))
{
	$sys_course_path = $pathForm.'courses/';
	//$tbl_course = Database :: get_main_table(TABLE_MAIN_COURSE);
	mysql_select_db($dbNameForm);
	$db_name = $dbNameForm;
	$sql = "SELECT * FROM course";
	error_log('Getting courses for files updates: '.$sql,0);
	$result=mysql_query($sql);
	
	$perm = api_get_setting('permissions_for_new_directories');
	$perm = octdec(!empty($perm)?$perm:'0770');
	$old_umask = umask(0);
	while($courses_directories=mysql_fetch_array($result)){
		
		$currentCourseRepositorySys = $sys_course_path.$courses_directories["directory"]."/";
		$db_name = $courses_directories["db_name"];
		$origCRS = $updatePath.'courses/'.$courses_directories["directory"];

		if(!is_dir($origCRS)){
			error_log('Directory '.$origCRS.' does not exist. Skipping.',0);
			continue;	
		}
		//move everything to the new hierarchy (from old path to new path)
		error_log('Renaming '.$origCRS.' to '.$sys_course_path.$courses_directories["directory"],0);
		rename($origCRS,$sys_course_path.$courses_directories["directory"]);
		error_log('Creating dirs in '.$currentCourseRepositorySys,0);

		//FOLDER DOCUMENT

		//document > audio
		if(!is_dir($currentCourseRepositorySys."document/audio")){
			mkdir($currentCourseRepositorySys."document/audio",$perm);
			insert_db($db_name,"audio",get_lang('Audio'));
		}
		//document > flash
		if(!is_dir($currentCourseRepositorySys."document/flash")){
			mkdir($currentCourseRepositorySys."document/flash",$perm);
			insert_db($db_name,"flash",get_lang('Flash'));
		}
		//document > images
		if(!is_dir($currentCourseRepositorySys."document/images")){
			mkdir($currentCourseRepositorySys."document/images",$perm);
			insert_db($db_name,"images",get_lang('Images'));
		}
		//document > video
		if(!is_dir($currentCourseRepositorySys."document/video")){
			mkdir($currentCourseRepositorySys."document/video",$perm);
			insert_db($db_name,"video",get_lang('Video'));
		}
		//document > video > flv
		if(!is_dir($currentCourseRepositorySys."document/video/flv")){
			mkdir($currentCourseRepositorySys."document/video/flv",$perm);
			insert_db($db_name,"video",get_lang('Video')." (flv)");
		}

		//FOLDER UPLOAD

		//upload
		if(!is_dir($currentCourseRepositorySys."upload")){
			mkdir($currentCourseRepositorySys."upload",$perm);
		}
		
		//upload > blog
		if(!is_dir($currentCourseRepositorySys."upload/blog")){
			mkdir($currentCourseRepositorySys."upload/blog",$perm);
		}
		//upload > forum
		if(!is_dir($currentCourseRepositorySys."upload/forum")){
			mkdir($currentCourseRepositorySys."upload/forum",$perm);
		}
		//upload > test
		if(!is_dir($currentCourseRepositorySys."upload/test")){
			mkdir($currentCourseRepositorySys."upload/test",$perm);
		}
		
		//Updating index file in courses directories to change claroline/ into main/
		$content = '<?php'."\n".
				'$cidReq="'.$courses_directories['code'].'";'."\n" .
				'$dbname="'.$courses_directories['db_name'].'";'."\n" .
				'include("../../main/course_home/course_home.php");'."\n" .
				'?>';
		unlink($currentCourseRepositorySys.'index.php');
		$fp = @ fopen($currentCourseRepositorySys.'index.php', 'w');
		if ($fp)
		{
			error_log('Writing redirection file in '.$currentCourseRepositorySys.'index.php',0);			
			fwrite($fp, $content);
			fclose($fp);
		}else{
			error_log('Could not open file '.$currentCourseRepositorySys.'index.php',0);
		}
	}

	umask($old_umask);
	// Write the Dokeos config file
	write_dokeos_config_file('../inc/conf/configuration.php');
	// Write a distribution file with the config as a backup for the admin
	write_dokeos_config_file('../inc/conf/configuration.dist.php');
	// Write a .htaccess file in the course repository
	write_courses_htaccess_file($urlAppendPath);
	copy($updatePath.'claroline/inc/conf/add_course.conf.php',$pathForm.'main/inc/conf/add_course.conf.php');
	copy($updatePath.'claroline/inc/conf/course_info.conf.php',$pathForm.'main/inc/conf/course_info.conf.php');
	copy($updatePath.'claroline/inc/conf/mail.conf.php',$pathForm.'main/inc/conf/mail.conf.php');
	copy($updatePath.'claroline/inc/conf/profile.conf.inc.php',$pathForm.'main/inc/conf/profile.conf.php');

	error_log('Renaming '.$updatePath.'claroline/upload/users to '.$pathForm.'main/upload/users',0);
	rename($updatePath.'claroline/upload/users',$pathForm.'main/upload/users');
	error_log('Renaming '.$updatePath.'claroline/upload/audio to '.$pathForm.'main/upload/audio',0);
	rename($updatePath.'claroline/upload/audio',$pathForm.'main/upload/audio');
	error_log('Renaming '.$updatePath.'claroline/upload/images to '.$pathForm.'main/upload/images',0);
	rename($updatePath.'claroline/upload/images',$pathForm.'main/upload/images');
	error_log('Renaming '.$updatePath.'claroline/upload/linked_files to '.$pathForm.'main/upload/linked_files',0);
	rename($updatePath.'claroline/upload/linked_files',$pathForm.'main/upload/linked_files');
	error_log('Renaming '.$updatePath.'claroline/upload/video to '.$pathForm.'main/upload/video',0);
	rename($updatePath.'claroline/upload/video',$pathForm.'main/upload/video');
		
	/*
	if (defined('DOKEOS_INSTALL'))
	{
		//nothing to do this time
	}
	*/
}
else
{
	echo 'You are not allowed here !';
}
?>
