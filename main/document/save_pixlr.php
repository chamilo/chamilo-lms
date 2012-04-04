<?php
/* For licensing terms, see /license.txt */
/**
 *	This file allows creating new svg and png documents with an online editor.
 *
 *	@package chamilo.document
 *
 * @author Juan Carlos Raña Trabado
 * @since 30/january/2011
*/
/**
 * Code
 */
require_once '../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'fileUpload.lib.php';
require_once api_get_path(LIBRARY_PATH).'document.lib.php';

api_protect_course_script();
api_block_anonymous_users();
var_dump($_GET);

if(!isset($_GET['title']) || !isset($_GET['type']) || !isset($_GET['image'])) {
	api_not_allowed();
	die();
}

if(!isset($_SESSION['paint_dir']) || !isset($_SESSION['whereami']) ){
	api_not_allowed();//
	die();	
}

//pixlr return
$filename=Security::remove_XSS($_GET['title']);//The user preferred file name of the image.
$extension=Security::remove_XSS($_GET['type']);//The image type, "pdx", "jpg", "bmp" or "png".
$urlcontents=Security::remove_XSS($_GET['image']);//A URL to the image on Pixlr.com server or the raw file post of the saved image.

//make variables

$title = Database::escape_string(str_replace('_',' ',$filename));
$current_session_id = api_get_session_id();
$groupId=$_SESSION['_gid'];
$relativeUrlPath=$_SESSION['paint_dir'];
$currentTool=$_SESSION['whereami'];
$dirBaseDocuments = api_get_path(SYS_COURSE_PATH).$_course['path'].'/document';
$saveDir=$dirBaseDocuments.$_SESSION['paint_dir'];

$contents = file_get_contents($urlcontents);


//Verify that the URL is pointing to a file @ pixlr.com domain or an ip @ pixlr.com
/*
$urlcontents1='http://pixlr.com/';
$urlcontents2 = strstr($urlcontents, '_temp');
$urlcontents_to_save=$urlcontents1.$urlcontents2;
$contents = file_get_contents($urlcontents_to_save);//replace line 45.
*/

if ($contents === false) { 
	echo "I cannot read: ".$urlcontents;
    exit;
} 

//Verify that the file is an image
$headers = get_headers($urlcontents, 1);
$content_type = explode("/", $headers['Content-Type']);
if ($content_type[0] != "image"){
	echo "Invalid file type";
	exit;
}



//a bit title security
$filename = addslashes(trim($filename));
$filename = Security::remove_XSS($filename);
$filename = replace_dangerous_char($filename, 'strict');
$filename = disable_dangerous_file($filename);

// a bit extension security
if($extension!= 'jpg' && $extension!= 'png' && $extension!= 'bmp' && $extension!= 'pxd'){
	die();
}

//TODO: a bit mime security

//path, file and title
$paintFileName = $filename.'.'.$extension;
$title = $title.'.'.$extension;

if($currentTool=='document/createpaint'){		
	//check save as and prevent rewrite an older file with same name	
	if (0 != $groupId){
	require_once api_get_path(LIBRARY_PATH).'groupmanager.lib.php';
	$group_properties  = GroupManager :: get_group_properties($groupId);
	$groupPath = $group_properties['directory'];
	}
	else{
		$groupPath ='';
	}	
	
	if (file_exists($saveDir.'/'.$filename.'.'.$extension)){ 
		$i = 1;		
		while (file_exists($saveDir.'/'.$filename.'_'.$i.'.'.$extension)) $i++;
		$paintFileName = $filename . '_' . $i . '.'.$extension;
		$title = $filename . '_' . $i . '.'.$extension;
	}
	
	//
	$documentPath = $saveDir.'/'.$paintFileName;
	//add new document to disk
	file_put_contents( $documentPath, $contents );
	//add document to database
	$doc_id = add_document($_course, $relativeUrlPath.'/'.$paintFileName, 'file', filesize($documentPath), $title);
	api_item_property_update($_course, TOOL_DOCUMENT, $doc_id, 'DocumentAdded', $_user['user_id'], $groupId, null, null, null, $current_session_id);

}elseif($currentTool=='document/editpaint'){
	
	$documentPath = $saveDir.'/'.$paintFileName;
	//add new document to disk
	file_put_contents( $documentPath, $contents );
	
	//check path
	if(!isset($_SESSION['paint_file'])){
		api_not_allowed();
		die();
	}
	if($_SESSION['paint_file']==$paintFileName){		   
		$document_id = DocumentManager::get_document_id($_course, $relativeUrlPath.'/'.$paintFileName);
		update_existing_document($_course, $document_id, filesize($documentPath), null);
		api_item_property_update($_course, TOOL_DOCUMENT, $document_id, 'DocumentUpdated', $_user['user_id'], $groupId, null, null, null, $current_session_id);		
	}else{
		//add a new document
		$doc_id = add_document($_course, $relativeUrlPath.'/'.$paintFileName, 'file', filesize($documentPath), $title);
		api_item_property_update($_course, TOOL_DOCUMENT, $doc_id, 'DocumentAdded', $_user['user_id'], $groupId, null, null, null, $current_session_id);
	}	
}


//delete temporal file
unlink($_SESSION['temp_realpath_image']);

//Clean sessions and return to Chamilo file list
unset($_SESSION['paint_dir']);
unset($_SESSION['paint_file']);
unset($_SESSION['whereami']);
unset($_SESSION['temp_realpath_image']);

if (!isset($_SESSION['exit_pixlr'])) {
	$location=api_get_path(WEB_CODE_PATH).'document/document.php';
	echo '<script>window.parent.location.href="'.$location.'"</script>';					 
	api_not_allowed(true);
}
else{	
	echo '<div align="center" style="padding-top:150; font-family:Arial, Helvetica, Sans-serif;font-size:25px;color:#aaa;font-weight:bold;">'.get_lang('PleaseStandBy').'</div>';
	$location=api_get_path(WEB_CODE_PATH).'document/document.php?curdirpath='.Security::remove_XSS($_SESSION['exit_pixlr']);
	echo '<script>window.parent.location.href="'.$location.'"</script>';
	unset($_SESSION['exit_pixlr']);
}