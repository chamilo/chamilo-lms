<?php //$Id: update-files-1.6.x-1.8.0.inc.php 10950 2007-01-29 02:30:27Z yannoo $
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

/*
==============================================================================
		FUNCTIONS
==============================================================================
*/
function insert_db($db_name, $folder_name, $text){

	$_course['dbName'] = $db_name;
	
	$doc_id = add_document($_course, '/'.$folder_name, 'folder', 0, ucfirst($text));
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
	
	while($courses_directories=mysql_fetch_array($result)){
		
		$currentCourseRepositorySys = $sys_course_path.$courses_directories["directory"]."/";
		$db_name = $courses_directories["db_name"];
		
		//move everything to the new hierarchy (from old path to new path)
		error_log('Renaming '.$updatePath.'courses/'.$courses_directories["directory"].' to '.$sys_course_path.$courses_directories["directory"],0);
		rename($updatePath.'courses/'.$courses_directories["directory"],$sys_course_path.$courses_directories["directory"]);
		
		error_log('Creating dirs in '.$currentCourseRepositorySys,0);
		
		
		
		//FOLDER DOCUMENT
		
			//document > audio
			if(!is_dir($currentCourseRepositorySys."document/audio")){
				mkdir($currentCourseRepositorySys."document/audio",0777);
				insert_db($db_name,"audio",get_lang('Audio'));
			}
			//document > flash
			if(!is_dir($currentCourseRepositorySys."document/flash")){
				mkdir($currentCourseRepositorySys."document/flash",0777);
				insert_db($db_name,"flash",get_lang('Flash'));
			}
			//document > images
			if(!is_dir($currentCourseRepositorySys."document/images")){
				mkdir($currentCourseRepositorySys."document/images",0777);
				insert_db($db_name,"images",get_lang('Images'));
			}
			
			if(!is_dir($currentCourseRepositorySys."document/video")){
				mkdir($currentCourseRepositorySys."document/video",0777);
				insert_db($db_name,"video",get_lang('Video'));
			}
		
		//FOLDER UPLOAD
			
			//upload
			if(!is_dir($currentCourseRepositorySys."upload")){
				mkdir($currentCourseRepositorySys."upload",0777);
			}
		
			//upload > blog
			if(!is_dir($currentCourseRepositorySys."upload/blog")){
				mkdir($currentCourseRepositorySys."upload/blog",0777);
			}
			//upload > forum
			if(!is_dir($currentCourseRepositorySys."upload/forum")){
				mkdir($currentCourseRepositorySys."upload/forum",0777);
			}
			//upload > test
			if(!is_dir($currentCourseRepositorySys."upload/test")){
				mkdir($currentCourseRepositorySys."upload/test",0777);
			}
		
	}
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