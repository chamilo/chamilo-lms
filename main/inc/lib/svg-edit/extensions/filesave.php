<?php
/*
 * filesave.php
 * To be used with ext-server_opensave.js for SVG-edit
 *
 * Licensed under the Apache License, Version 2
 *
 * Copyright(c) 2010 Alexis Deveria
 *
 * Integrate svg-edit with Chamilo
 * @author Juan Carlos Raña Trabado
 * @since 25/september/2010
*/

$language_file = array('document');//Chamilo load lang var

//Chamilo load libraries
require_once '../../../../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'fileUpload.lib.php';
require_once api_get_path(LIBRARY_PATH).'document.lib.php';

//Add security from Chamilo
api_protect_course_script();
api_block_anonymous_users();

if(!isset($_POST['output_svg']) && !isset($_POST['output_png'])) {
	api_not_allowed();//from Chamilo
	die();
}

$file = '';
$suffix = isset($_POST['output_svg'])?'svg':'png';

if(isset($_POST['filename']) && strlen($_POST['filename']) > 0) {
	$file = $_POST['filename'];	
} else {
	$file = 'image';
}

if($suffix == 'svg') {
	$mime = 'image/svg+xml';
	$contents = rawurldecode($_POST['output_svg']);
} else {
	$mime = 'image/png';
	$contents = $_POST['output_png'];
	$pos = (strpos($contents, 'base64,') + 7);
	$contents = base64_decode(substr($contents, $pos));
}

//get SVG-Edit values
$filename=$file;//from svg-edit
$extension=$suffix;// from svg-edit
$content=$contents;//from svg-edit

$title = Database::escape_string(str_replace('_',' ',$filename));

//get Chamilo variables 

if(!isset($_SESSION['draw_dir']) && !isset($_SESSION['whereami'])) {
	api_not_allowed();//from Chamilo
	die();	
}

$current_session_id = api_get_session_id();
$groupId=$_SESSION['_gid'];
$relativeUrlPath=$_SESSION['draw_dir'];
$currentTool=$_SESSION['whereami'];
$dirBaseDocuments = api_get_path(SYS_COURSE_PATH).$_course['path'].'/document';
$saveDir=$dirBaseDocuments.$_SESSION['draw_dir'];

//a bit title security

$filename = addslashes(trim($filename));
$filename = Security::remove_XSS($filename);
$filename = replace_dangerous_char($filename, 'strict');
$filename = disable_dangerous_file($filename);

// a bit extension
if ($suffix!= 'svg' && $suffix!= 'png'){
	die();
}

//a bit mime security
//comment because finfo seems stopping the save process files in some php vers.
/*
if (phpversion() >= '5.3') {
	$finfo = new finfo(FILEINFO_MIME);
	$current_mime=$finfo->buffer($contents);
	finfo_close($finfo);
	$mime_png='image/png';//svg-edit return image/png; charset=binary 
	$mime_svg='image/svg+xml';
	$mime_xml='application/xml';//hack for svg-edit because original code return application/xml; charset=us-ascii. See	  
	if(strpos($current_mime, $mime_png)===false && $extension=='png') {
		die();//File extension does not match its content
	} elseif(strpos($current_mime, $mime_svg)===false && strpos($current_mime, $mime_xml)===false && $extension=='svg') {
		die();//File extension does not match its content
	}
}
*/

//checks if the file exists, then rename the new
if (file_exists($saveDir.'/'.$filename.$i.'.'.$extension) && $currentTool=='document/createdraw'){
	echo '<script language="javascript" type="text/javascript">';
	echo 'alert("'.get_lang('FileExistsChangeToSave').'");';
	echo '</script>';
	die();
} else {
	$drawFileName = $filename.'.'.$extension;
	$title = $title.'.'.$extension;
}
$documentPath = $saveDir.'/'.$drawFileName;

//add new document to disk
file_put_contents( $documentPath, $contents );

if ($currentTool=='document/createdraw') {
	//add document to database
	$doc_id = add_document($_course, $relativeUrlPath.'/'.$drawFileName, 'file', filesize($documentPath), $title);
	api_item_property_update($_course, TOOL_DOCUMENT, $doc_id, 'DocumentAdded', $_user['user_id'], $groupId, null, null, null, $current_session_id);

} elseif($currentTool=='document/editdraw') {

	//check path
	if (!isset($_SESSION['draw_file'])){
		api_not_allowed();//from Chamilo
		die();
	}
	if ($_SESSION['draw_file'] == $drawFileName ){
		$document_id = DocumentManager::get_document_id($_course, $relativeUrlPath.'/'.$drawFileName);
		update_existing_document($_course, $document_id, filesize($documentPath), null);
		api_item_property_update($_course, TOOL_DOCUMENT, $document_id, 'DocumentUpdated', $_user['user_id'], $groupId, null, null, null, $current_session_id);
	} else {
		//add a new document
		$doc_id = add_document($_course, $relativeUrlPath.'/'.$drawFileName, 'file', filesize($documentPath), $title);
		api_item_property_update($_course, TOOL_DOCUMENT, $doc_id, 'DocumentAdded', $_user['user_id'], $groupId, null, null, null, $current_session_id);
	}	
}

//clean sessions and add messages and return to current document list
unset($_SESSION['draw_dir']);
unset($_SESSION['draw_file']);
unset($_SESSION['whereami']);

echo '<script language="javascript" type="text/javascript">';
if($suffix!= 'png'){	
	if ($relativeUrlPath==''){$relativeUrlPath='/';};
	$interbreadcrumb=api_get_path(WEB_CODE_PATH).'document/document.php?'.api_get_cidreq().'&curdirpath='.	urlencode($relativeUrlPath);
	echo 'alert("'.get_lang('FileSavedAs').': '.$title.'");';	
	echo 'window.top.location.href="'.$interbreadcrumb.'";';//return to current document list
} else {
	echo 'alert("'.get_lang('FileExportAs').': '.$title.'");';
}
echo '</script>';