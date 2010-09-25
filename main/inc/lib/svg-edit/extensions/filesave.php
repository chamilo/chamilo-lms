<?php
/*
 * filesave.php
 * To be used with ext-server_opensave.js for SVG-edit
 *
 * Licensed under the Apache License, Version 2
 *
 * Copyright(c) 2010 Alexis Deveria
 *
 */
require_once '../../../../inc/global.inc.php';//hack for chamilo
require_once api_get_path(LIBRARY_PATH).'course.lib.php';
require_once api_get_path(LIBRARY_PATH).'groupmanager.lib.php';
require_once api_get_path(LIBRARY_PATH).'security.lib.php';
require_once api_get_path(LIBRARY_PATH).'document.lib.php';

api_protect_course_script();
api_block_anonymous_users();

if(!isset($_POST['output_svg']) && !isset($_POST['output_png'])) {
	die('post fail');
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

$filename=$file;//from svg-edit
$extension=$suffix;// from svg-edit
$content=$contents;//from svg-edit
 
//a bit title security  
$filename = addslashes(trim($filename));
$filename = Security::remove_XSS($filename);
$filename = replace_dangerous_char($filename);
$filename = disable_dangerous_file($filename); 
 
$current_session_id = api_get_session_id();
//TODO:implement groups
if (0 != $groupId)
{
	$groupPart = '_group' . $groupId; // and add groupId to put the same document title in different groups
	$group_properties  = GroupManager :: get_group_properties($groupId);
	$groupPath = $group_properties['directory'];
}
else
{
	$groupPart = '';
	$groupPath ='';
}

$exportDir = api_get_path(SYS_COURSE_PATH).$_course['path'].'/document/';
$groupId=0;
if(file_exists($exportDir . '/' .$filename.$i.'.'.$extension))
{   
	$i = 1;
	while ( file_exists($exportDir . '/' .$filename.'_'.$i.'.'.$extension) ) $i++; //prevent duplicates
	$drawFileName = $filename . '_' . $i . '.'.$extension;
}
else
{
	$drawFileName = $filename.'.'.$extension;;	
}
$documentPath = $exportDir . '/' . $drawFileName;

//add new document to disk
file_put_contents( $documentPath, $contents );	

//add new document to database
$doc_id = add_document($_course, $groupPath.'/'.$drawFileName, 'file', filesize($documentPath), $drawFileName);
api_item_property_update($_course, TOOL_DOCUMENT, $doc_id, 'DocumentAdded', $_user['user_id'], $groupId,null, null,$current_session_id);
api_item_property_update($_course, TOOL_DOCUMENT, $doc_id, 'invisible', $_user['user_id'], $groupId,null, null,$current_session_id);

?>