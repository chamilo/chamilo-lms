<?php
/* See license terms in /dokeos_license.txt */

/* FIX for IE cache when using https */
session_cache_limiter("none");

/**
==============================================================================
*	This is an interface between Dokeos and Videoconference application
*
==============================================================================
*/
/*==== DEBUG ====*/
$debug=0;
/*==== CONSTANTS ==== */
define('VIDEOCONF_UPLOAD_PATH', '/videoconf');
$presentation_extension = array('.ppt', '.odp');
$image_extension = array ('.png', '.jpg', '.gif', '.jpeg');

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


/*==== Flash loose the cookie ===*/
/* needed when using the nice upload window : 
if ($_SERVER['HTTP_USER_AGENT'] == 'Shockwave Flash') {
	$sid = $_REQUEST['sid'];
	if ($debug>0) error_log("reusing: ".$sid);
	session_id($sid);
} */

/*==== INCLUDE ====*/
require_once ('../inc/global.inc.php');
api_block_anonymous_users();
require_once (api_get_path(LIBRARY_PATH)."course.lib.php");
require_once (api_get_path(LIBRARY_PATH)."document.lib.php");
require_once (api_get_path(LIBRARY_PATH)."fileUpload.lib.php");
require_once ("../newscorm/learnpath.class.php");
require_once ("../newscorm/openoffice_presentation.class.php");

/*==== Variables initialisation ====*/
$action = $_REQUEST["action"]; //safe as only used in if()'s
$seek = array('/','%2F','..');
$destroy = array('','','');
$cidReq = str_replace($seek,$destroy,$_REQUEST["cidReq"]);
$cidReq = Security::remove_XSS($cidReq);

$user_id = api_get_user_id();
$coursePath = api_get_path(SYS_COURSE_PATH).$cidReq.'/document';
$_course = CourseManager::get_course_information($cidReq);
$_course['path'] = $_course['directory'];
// FIXME: add_document needs this to work
$_course['dbName'] = $_course['db_name'];

// FIXME: check if CourseManager::get_user_in_course_status return != 
//	COURSEMANAGER when the code is not valid
if ($debug>0) error_log($coursePath, 0);

if ($action == "uploadgui")
{
	echo '<form enctype="multipart/form-data" action="api.php" method="POST">
	<input type="hidden" name="MAX_FILE_SIZE" value="100000000" />
	<input type="hidden" name="action" value="upload" />
	<input type="hidden" name="cidReq" value="'.$cidReq.'" />
	<input type="hidden" name="sid" value="'.Security::remove_XSS($_REQUEST["sid"]).'" />

	'.get_lang('SelectFile').': <input name="Filedata" type="file" /><br />
	<input type="submit" value="'.get_lang('UploadFile').'"  />
	</form>
	';
	die();
}
else if ($action == "upload")
{
	if ($debug >0) error_log("upload".$_FILES['Filedata']);
	/*==== PERMISSION ====*/
	$permissions = CourseManager::get_user_in_course_status($user_id, $cidReq);
	if ($permissions != COURSEMANAGER)
	{
		if ($debug >0) error_log("Upload from videoconf not allowed !!!",0);
		die('Not allowed'); // this user is not allowed to add upload documents
	}
	/*==== UPLOAD ====*/
	$destPath = $coursePath.VIDEOCONF_UPLOAD_PATH;

	/*==== creation of /videoconf ====*/
	if (!is_dir($destPath))
	{
		$result = create_unexisting_directory($_course,$user_id,0,NULL,$coursePath,VIDEOCONF_UPLOAD_PATH);
		if (!$result)
		{
			if ($debug>0) error_log("Can't create ".$destPath." folder",0);
		}
	}

	/*==== file upload ====*/
	$newPath = $_FILES['Filedata']['name'];
	if($debug>0) error_log($newPath);

	/*==== extension extraction ====*/
	$file_name = (strrpos($newPath,'.')>0 ? substr($newPath, 0, strrpos($newPath,'.')) : $newPath);
 	$file_extension = (strrpos($newPath,'.')>0 ? substr($newPath, strrpos($newPath,'.'),10) : '');
	if($debug>0) error_log(strrpos($newPath,'.'));
	if($debug>0) error_log($file_extension);

	/*==== conversion if needed ====*/
	if (!in_array(strtolower($file_extension), $image_extension))
	{
		if($debug>0) error_log("converting: ".$file_extension);
		$take_slide_name = false;
		$o_ppt = new OpenofficePresentation($take_slide_name);
		$o_ppt -> set_slide_size(640,480);
		$o_ppt -> convert_document($_FILES['Filedata'],'add_docs_to_visio');
	}

	echo '<html><body><script language="javascript">setTimeout(1000,window.close());</script></body></html>';
} 
else if ($action == "service") 
{
	/*==== List files ====*/
	if ($debug>0) error_log("sending file list",0);
	$subaction = $_REQUEST["subaction"];
	$is_manager = (CourseManager::get_user_in_course_status($user_id, $cidReq) == COURSEMANAGER);
	if ($subaction == "list") 
	{
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
		
		if (Security::check_abs_path($cwd,api_get_path(SYS_PATH)))
			die();

		// check if user can delete files. He must be manager and be inside /videoconf
		$is_below_videoconf_dir = (substr($cwd,0,strlen(VIDEOCONF_UPLOAD_PATH)) == VIDEOCONF_UPLOAD_PATH);
		if($debug>0) error_log('Current working directory: '.$cwd);
		if($debug>0) error_log('Videoconf upload path: '.VIDEOCONF_UPLOAD_PATH);
		/* $canDelete = ($canDelete && $isBellowVideoConfUploadPath);
		*/
		$can_delete = ($is_manager && $is_below_videoconf_dir);
		
		// get files list
		$files = DocumentManager::get_all_document_data($_course, $cwd, 0, NULL, false);
		printf("<dokeosobject><fileListMeta></fileListMeta><fileList>");
		printf("<folders>");

		// title filter
		if (is_array($files)) foreach (array_keys($files) as $k)
		{
			// converting to UTF-8
			$files[$k]['title'] = api_convert_encoding(
						api_strlen($files[$k]['title']) > 32 ? 
							api_substr($files[$k]['title'],0, 32)."..." : 
							$files[$k]['title'],
						'utf-8',api_get_setting('platform_charset')); 
			// removing '<', '>' and '_'
			$files[$k]['title'] = str_replace(array('<','>','_'),' ', $files[$k]['title']);
		}

		if(is_array($files))
		{
	
			foreach($files as $i)
			{
				if ($i["filetype"] == "folder")
					printf('<folder><path>%s</path><title>%s</title><canDelete>%s</canDelete></folder>', $i['path'],$i['title'],($can_delete?'true':'false'));
			}
		}
		printf("</folders><files>");
		if(is_array($files))
		{
			foreach($files as $i) {
	  			$extension = (strrpos($i['path'],'.')>0 ? substr($i['path'], strrpos($i['path'],'.'),10) : '');
				if ($i["filetype"] == "file" && in_array(strtolower($extension), $image_extension))
					printf('<file><path>%s</path><title>%s</title><canDelete>%s</canDelete></file>', $i['path'],$i['title'],($can_delete?'true':'false'));
			}
		}
		printf("</files><ppts>");
		printf("</ppts>");
		printf("</fileList></dokeosobject>");
	} 
	else if ($subaction == "delete") 
	{
		/*==== PERMISSION ====*/
		$permissions = CourseManager::get_user_in_course_status($user_id, $cidReq);
		if ($permissions != COURSEMANAGER)
		{
			if ($debug > 0) error_log("Upload from videoconf not allowed !!!",0);
			die(); // this user is not allowed to add upload documents
		}
		/*==== DELETE ====*/
		$path = str_replace('../','',$_REQUEST["path"]);
		if ((substr($path,0,strlen(VIDEOCONF_UPLOAD_PATH)) != VIDEOCONF_UPLOAD_PATH))
		{
			if ($debug >0 ) error_log("Delete from videoconf for "+$path+" NOT ALLOWED",0);
			die();
		}
		DocumentManager::delete_document($_course, $path, $coursePath);
		echo "<result>OK</result>"; // We have to returns something to OpenLaszlo 
	}
}
else if ($action == "download")
{
	/*==== DOWNLOAD ====*/
	//check if the document is in the database
	if(!DocumentManager::get_document_id($_course,$_REQUEST['file']))
	{
		//file not found!
		if ($debug>0) error_log("404 ".$_REQUEST["file"]);
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
	$doc_url = str_replace('../','',$_REQUEST['file']);
	if ($debug >0) error_log($doc_url);
	$full_file_name = $coursePath.$doc_url;
	DocumentManager::file_send_for_download($full_file_name,false);
	exit;
}
?>
