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
//Chamilo load libraries
require_once '../../../../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'fileUpload.lib.php';

//Add security from Chamilo
api_protect_course_script();
api_block_anonymous_users();

//Adding Chamilo style because Display :: display_error_message() dont run well.
?>
<style type="text/css">
<!--
.error-message {
	position: relative;
	margin-top: 10px;
	margin-bottom: 10px;
	border-width: 1px;
	border-style: solid;
	-moz-border-radius: 10px;
	padding: 6px;
	border: 1px solid #FF0000;
	color: #440000;
	background-color: #FFD1D1;
	min-height: 30px;
}
-->
</style>
<?php

if(!isset($_POST['output_svg']) && !isset($_POST['output_png'])) {
	echo '<div class="error-message">'. get_lang('lang_no_access_here').'</div>';// from Chamilo
	die();
}

$file = '';

//$suffix = isset($_POST['output_svg'])?'.svg':'.png';
$suffix = isset($_POST['output_svg'])?'svg':'png';

if(isset($_POST['filename']) && strlen($_POST['filename']) > 0) {
	//$file = $_POST['filename'] . $suffix;
	$file = $_POST['filename'];
} else {
	//$file = 'image' . $suffix;
	$file = 'image';
}

//if($suffix == '.svg') {
if($suffix == 'svg') {
	$mime = 'image/svg+xml';
	$contents = rawurldecode($_POST['output_svg']);
} else {
	$mime = 'image/png';
	$contents = $_POST['output_png'];
	$pos = (strpos($contents, 'base64,') + 7);
	$contents = base64_decode(substr($contents, $pos));
}

/////hack for Chamilo

//get SVG-Edit values
$filename=$file;//from svg-edit
$extension=$suffix;// from svg-edit
$content=$contents;//from svg-edit

$title = Database::escape_string(str_replace('_',' ',$filename));

//get Chamilo variables 

if(!isset($_SESSION['draw_dir']) ||!isset($_SESSION['whereami']) )
{
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

//a bit mime security
$finfo = new finfo(FILEINFO_MIME);
$current_mime=$finfo->buffer($contents);
$mime_png='image/png';//svg-edit return image/png; charset=binary 
$mime_svg='application/xml';//svg-edit return application/xml; charset=us-ascii TODO: shoud be image/svg+xml    (http://www.w3.org/TR/SVG11/mimereg.html)
if(strpos($current_mime, $mime_png)===false && $extension=='png')
{
	die();//File extension does not match its content
}elseif(strpos($current_mime, $mime_svg)===false && $extension=='svg')
{
	die();//File extension does not match its content
}


//checks if the file exists, then rename the new
if(file_exists($saveDir.'/'.$filename.$i.'.'.$extension) && $currentTool=='document/createdraw'){
	$i = 1;
	while (file_exists($saveDir.'/'.$filename.'_'.$i.'.'.$extension)) $i++; //prevent duplicates
	$drawFileName = $filename.'_'.$i.'.'.$extension;
	$title=$title.' '.$i.'.'.$extension;
}else{
	$drawFileName = $filename.'.'.$extension;
	$title = $title.'.'.$extension;
}
$documentPath = $saveDir.'/'.$drawFileName;

//add new document to disk
file_put_contents( $documentPath, $contents );

if($currentTool=='document/createdraw'){
	//add document to database
	$doc_id = add_document($_course, $relativeUrlPath.'/'.$drawFileName, 'file', filesize($documentPath), $title);
	api_item_property_update($_course, TOOL_DOCUMENT, $doc_id, 'DocumentAdded', $_user['user_id'], $groupId, null, null, null, $current_session_id);
}elseif($currentTool=='document/editdraw'){

	//check path
	if(!isset($_SESSION['draw_file'])){
		die();
	}
	if($_SESSION['draw_file']==$drawFileName){		
		$document_id = DocumentManager::get_document_id($_course, $relativeUrlPath.'/'.$drawFileName);
		update_existing_document($_course, $document_id, filesize($documentPath), null);
		api_item_property_update($_course, TOOL_DOCUMENT, $document_id, 'DocumentUpdated', $_user['user_id'], $groupId, null, null, null, $current_session_id);
	}else{
		//add a new document
		$doc_id = add_document($_course, $relativeUrlPath.'/'.$drawFileName, 'file', filesize($documentPath), $title);
		api_item_property_update($_course, TOOL_DOCUMENT, $doc_id, 'DocumentAdded', $_user['user_id'], $groupId, null, null, null, $current_session_id);
	}	
}

?>