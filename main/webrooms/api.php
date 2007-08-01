<?php
/*
===============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004-2007 Dokeos S.A.
	Copyright (c) Arnaud Ligot

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact address: Dokeos, 44 rue des palais, B-1030 Brussels, Belgium
	Mail: info@dokeos.com
===============================================================================
*/
/**
==============================================================================
*	This is an interface between Dokeos and Videoconference application
*
==============================================================================
*/

/*
==============================================================================
		CONSTANTS
==============================================================================
*/


define('VIDEOCONF_UPLOAD_PATH', '/videoconf');
$PRESENTATION_EXTENSION = array('.ppt', '.odp');
$IMAGE_EXTENSION = array ('.png', '.jpg', '.gif', '.jpeg');
/*
==============================================================================
		SETTING Session ID because flash do not send the cookie
==============================================================================
*/
if (!is_array($_COOKIE) || !isset($_COOKIE["dk_sid"])){
	error_log("setting SID to:".$_REQUEST["sid"]);
	session_id($_REQUEST["sid"]);
}
/*
==============================================================================
		DEBUG
==============================================================================
*/
$debug=0;
if ($debug>0)
{
	// dump the request
	$v = array_keys(get_defined_vars());
	error_log(var_export($v, true),3, '/tmp/log');

	foreach (array_keys(get_defined_vars()) as $k) {
		if ($k == 'GLOBALS')
			continue;
		error_log($k, 3, '/tmp/log');
		error_log(var_export($$k, true), 3, '/tmp/log');
	}

}

/*
==============================================================================
		INCLUDE
==============================================================================
*/
require_once ('../inc/global.inc.php');
api_block_anonymous_users();
require_once (api_get_path(LIBRARY_PATH)."course.lib.php");
require_once (api_get_path(LIBRARY_PATH)."document.lib.php");
require_once (api_get_path(LIBRARY_PATH)."fileUpload.lib.php");

/*
==============================================================================
		SETTING UP SOME VARIABLES
==============================================================================
*/
$debug=0;
$action = $_REQUEST["action"];
$cidReq = $_REQUEST["cidReq"];

$user_id = api_get_user_id();
$coursePath = api_get_path(SYS_COURSE_PATH).$cidReq.'/document';
$_course = CourseManager::get_course_information($cidReq);

// FIXME: this sound very strange !!! but add_document need this to works
$_course['dbName'] = $_course['db_name'];

// FIXME: check if CourseManager::get_user_in_course_status return != 
//	COURSEMANAGER when the code is not valid
// FIXME: There is no valid course code which contains things like ../
if ($debug>0) error_log($coursePath, 0);



if ($action == "upload")
{
/*
==============================================================================
		PERMISSION
==============================================================================
*/
	$permissions = CourseManager::get_user_in_course_status($user_id, $cidReq);
	if ($permissions != COURSEMANAGER)
	{
		error_log("Upload from videoconf not allowed !!!",0);
		die(); // this user is not allowed to add upload documents
	}
/*
==============================================================================
		UPLOAD
==============================================================================
*/

	$destPath = $coursePath.VIDEOCONF_UPLOAD_PATH;
	if (!is_dir($destPath)){
		$result = create_unexisting_directory($_course,$user_id,0,NULL,$coursePath,VIDEOCONF_UPLOAD_PATH);
		if (!$result)
			error_log("Can't create ".$destPath." folder",0);
	}
	
	$newPath = handle_uploaded_document($_course,$_FILES['Filedata'],$coursePath,VIDEOCONF_UPLOAD_PATH,$user_id,0,NULL,'',0,'rename',false);
	// based on ../newscorm/presentation.class.php
    	$file_name = (strrpos($newPath,'.')>0 ? substr($newPath, 0, strrpos($newPath,'.')) : $newPath);
    	$file_extension = (strrpos($newPath,'.')>0 ? substr($newPath, strrpos($newPath,'.'),10) : '');
/*	error_log($newPath,0);
	error_log($file_name,0);
	error_log($file_extension,0);
*/
	if (in_array($file_extension, $PRESENTATION_EXTENSION))
	{
//		error_log("converting $coursePath$newPath", 0);
		/* creating output folder */
		$created_dir = create_unexisting_directory($_course,$user_id,0,NULL,$coursePath,$file_name);
//		error_log($created_dir,0);		
		
		/* alow user of openoffice to write into the folder */
		// FIXME
		chmod($coursePath.$created_dir, 0777);

		/*
		 * exec java application
		 * the parameters of the program are :
		 * - javacommand on this server ;
		 * - host where openoffice is running;
		 * - port with which openoffice is listening
		 * - file to convert
		 * - folder where put the slides
		 * - ftppassword if required
		 * The program fills $files with the list of slides created
		 */
		/* building command line */
		$classpath = '-cp .:ridl.jar:js.jar:juh.jar:jurt.jar:jut.jar:java_uno.jar:java_uno_accessbridge.jar:edtftpj-1.5.2.jar:unoil.jar';
		if(strpos($_ENV['OS'],'Windows') !== false)
		{
			$classpath = str_replace(':',';',$classpath);
		}
		list($slide_width, $slide_height) = explode('x',api_get_setting('service_ppt2lp','size'));
		
		if(strpos($_ENV['OS'],'Windows') !== false)
		{
			$cmd = 'cd '.str_replace('/','\\',api_get_path(SYS_PATH)).'main/inc/lib/ppt2png && java '.$classpath.' DocumentConverter '.api_get_setting('service_ppt2lp','host').' 2002'.' "'.$coursePath.$newPath.'" "'.$coursePath.$created_dir.'"'.' '.$slide_width.' '.$slide_height.' '.api_get_setting('service_ppt2lp','user').' '.api_get_setting('service_ppt2lp','ftp_password');
		}
		else
		{
			$cmd = 'cd '.api_get_path(SYS_PATH).'main/inc/lib/ppt2png && java '.$classpath.' DocumentConverter '.api_get_setting('service_ppt2lp','host').' 2002'.' "'.$coursePath.$newPath.'" "'.$coursePath.$created_dir.'"'.' '.$slide_width.' '.$slide_height.' '.api_get_setting('service_ppt2lp','user').' '.api_get_setting('service_ppt2lp','ftp_password');
		}
		error_log($cmd,0);

		/* Exec */
		$shell = exec($cmd, $files, $return); // files: list of created files, return: shell return code
		
		/* Add Files */
		foreach($files as $f)
		{
			$did = add_document($_course, $created_dir.'/'.$f, 'file', filesize($coursePath.$created_dir.'/'.$f), $f);
//			error_log($created_dir.'/'.$f);
			if ($did)
				api_item_property_update($_course, TOOL_DOCUMENT, $did, 'DocumentAdded', $user_id, 0, NULL);
		}
	}
} else if ($action == "service") 
{/*
==============================================================================
		LIST FILES
==============================================================================
*/
	error_log("sending file list",0);
	$subaction = $_REQUEST["subaction"];
	$canDelete = (CourseManager::get_user_in_course_status($user_id, $cidReq) == COURSEMANAGER);
	if ($subaction == "list") {
		// FIXME: check security around $_REQUEST["cwd"]
		$cwd = $_REQUEST["cwd"];

		// treat /..
		$nParent = 0; // the number of /.. into the url
		while (substr($cwd, -3, 3) == "/..")
		{
			// go to parent directory
			$cwd= substr($cwd, 0, -3);
			if (strlen($cwd) == 0) $cwd="/";
			$nParent++;
		}
		for (;$nParent >0; $nParent--){
			$cwd = (strrpos($cwd,'/')>-1 ? substr($cwd, 0, strrpos($cwd,'/')) : $cwd);
		}		

		if (strlen($cwd) == 0) $cwd="/";
		
		// check if user can delete files. He must be manager and be inside /videoconf
		$isBellowVideoConfUploadPath = (substr($cwd,0,strlen(VIDEOCONF_UPLOAD_PATH)) == VIDEOCONF_UPLOAD_PATH);
		$canDelete = ($canDelete && $isBellowVideoConfUploadPath);
		
		// get files list
		$files = DocumentManager::get_all_document_data($_course, $cwd, 0, NULL, false);
		printf("<dokeosobject><fileListMeta></fileListMeta><fileList>");
		printf("<folders>");
		foreach($files as $i)
			if ($i["filetype"] != "folder")
				continue;
			else 
			{
				printf('<folder><path>%s</path><title>%s</title><canDelete>%s</canDelete></folder>', $i['path'],$i['title'],($canDelete?"true":"false"));
			}
		printf("</folders><files>");
		foreach($files as $i) {
  			$extension = (strrpos($i['path'],'.')>0 ? substr($i['path'], strrpos($i['path'],'.'),10) : '');
			if ($i["filetype"] != "file" || !in_array($extension, $IMAGE_EXTENSION))
				continue;
			else 
			{
				printf('<file><path>%s</path><title>%s</title><canDelete>%s</canDelete></file>', $i['path'], $i['title'],($canDelete?"true":"false"));
			}
		}
		printf("</files><ppts>");
		foreach($files as $i) {
			// FIXME: Please optimise me !!!
  			$extension = (strrpos($i['path'],'.')>0 ? substr($i['path'], strrpos($i['path'],'.'),10) : '');
			if ($i["filetype"] != "file" || !in_array($extension, $PRESENTATION_EXTENSION))
				continue;
			else 
			{
				printf('<ppt><path>%s</path><title>%s</title><canDelete>%s</canDelete></ppt>', $i['path'], $i['title'],($canDelete?"true":"false"));
			}
		}
//		printf("<file><realname>fr1</realname><name>fn1</name></file>");

		printf("</ppts>");
		printf("</fileList></dokeosobject>");
	} else if ($subaction == "delete") {/*
==============================================================================
		PERMISSION
==============================================================================
*/
	$permissions = CourseManager::get_user_in_course_status($user_id, $cidReq);
	if ($permissions != COURSEMANAGER)
	{
		error_log("Upload from videoconf not allowed !!!",0);
		die(); // this user is not allowed to add upload documents
	}

/*
==============================================================================
		DELETE
==============================================================================
*/
		$path = $_REQUEST["path"];
		if ((substr($path,0,strlen(VIDEOCONF_UPLOAD_PATH)) != VIDEOCONF_UPLOAD_PATH))
		{
			error_log("Delete from videoconf for "+$path+" NOT ALLOWED",0);
			die();
		}
	
		DocumentManager::delete_document($_course, $path, $coursePath);
		echo "<result>OK</result>"; // We have to returns something to OpenLaszlo 
	}
} else if ($action == "download")
{
/*
==============================================================================
		DOWNLOAD
==============================================================================
*/
	//check if the document is in the database
	if(!DocumentManager::get_document_id($_course,$_REQUEST['file']))
	{
		//file not found!
		error_log("404 ".$_REQUEST["file"]);
		header("HTTP/1.0 404 Not Found");
		$error404 = '<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">';
		$error404 .= '<html><head>';
		$error404 .= '<title>404 Not Found</title>';
		$error404 .= '</head><body>';
		$error404 .= '<h1>Not Found</h1>';
		$error404 .= '<p>The requested URL was not found on this server.</p>';
		$error404 .= '<hr>';
		$error404 .= '</body></html>';
		echo($error404);
		exit;
	}
	$doc_url=$_REQUEST['file'];
	error_log($doc_url);
	$full_file_name = $coursePath.$doc_url;
	DocumentManager::file_send_for_download($full_file_name,false);
	exit;
}

?>
