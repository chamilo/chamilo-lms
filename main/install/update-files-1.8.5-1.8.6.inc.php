<?php 
/* See license terms in /dokeos_license.txt */
/**
==============================================================================
* Updates the Dokeos files from version 1.8.5 to version 1.8.6
* This script operates only in the case of an update, and only to change the
* active version number (and other things that might need a change) in the
* current configuration file.
* As of 1.8.6, the Dokeos version has been added to configuration.php to
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
	
	$sys_course_path = $pathForm.'courses/';
	
	//$tbl_course = Database :: get_main_table(TABLE_MAIN_COURSE);
	//linking
	$link = mysql_connect($dbHostForm, $dbUsernameForm, $dbPassForm);
	mysql_select_db($dbNameForm,$link);
	$db_name = $dbNameForm;
	$sql = "SELECT * FROM $db_name.course";
	error_log('Getting courses for files updates: '.$sql,0);
	$result=mysql_query($sql);
	
	$perm = api_get_setting('permissions_for_new_directories');
	$perm = octdec(!empty($perm)?$perm:'0770');
	$old_umask = umask(0);
	
	
	while($courses_directories=mysql_fetch_array($result))
	{		
		$currentCourseRepositorySys = $sys_course_path.$courses_directories['directory'].'/';
		
		$db_name = $courses_directories['db_name'];
		$origCRS = $updatePath.'courses/'.$courses_directories['directory'];

		if(!is_dir($origCRS)){
			error_log('Directory '.$origCRS.' does not exist. Skipping.',0);
			continue;
		}
		//move everything to the new hierarchy (from old path to new path)
		error_log('Renaming '.$origCRS.' to '.$sys_course_path.$courses_directories['directory'],0);
		rename($origCRS,$sys_course_path.$courses_directories['directory']);
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
        //document > images
        if(!is_dir($currentCourseRepositorySys."document/images/gallery")){
            mkdir($currentCourseRepositorySys."document/images/gallery",$perm);
            insert_db($db_name,"gallery",get_lang('DefaultCourseImages'));
        }
        //document > images
        if(!is_dir($currentCourseRepositorySys."document/images/gallery/shared")){
            mkdir($currentCourseRepositorySys."document/images/gallery/shared",$perm);
            insert_db($db_name,"shared",get_lang('SharedImagesDirectory'));
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

		//upload > forum > images
		if(!is_dir($currentCourseRepositorySys."upload/forum/images")){
			mkdir($currentCourseRepositorySys."upload/forum/images",$perm);
		}

		//upload > learning_path
		if(!is_dir($currentCourseRepositorySys."upload/learning_path")){
			mkdir($currentCourseRepositorySys."upload/learning_path",$perm);
		}	

		//upload > learning_path > images
		if(!is_dir($currentCourseRepositorySys."upload/learning_path/images")){
			mkdir($currentCourseRepositorySys."upload/learning_path/images",$perm);
		}		

		//upload > calendar 
		if(!is_dir($currentCourseRepositorySys."upload/calendar")){
			mkdir($currentCourseRepositorySys."upload/calendar",$perm);
		}	

		//upload > calendar > images
		if(!is_dir($currentCourseRepositorySys."upload/calendar/images")){
			mkdir($currentCourseRepositorySys."upload/calendar/images",$perm);
		}
	}
}
else
{
	echo 'You are not allowed here !';
}
?>
