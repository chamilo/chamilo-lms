<?php
/* For licensing terms, see /license.txt */

/**
 *	This file allows creating new svg and png documents with an online editor.
 *
 *	@package chamilo.document
 *
 * @author Juan Carlos Raña Trabado
 * @since 5/mar/2011
*/
/**
 * Code
 */
require_once '../../../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'fileUpload.lib.php';

//security. Nanogong need less security because under Firefox, Chrome..., not stay  SESSION 
if (api_get_setting('enable_nanogong') == 'false'){
	api_protect_course_script();
	api_block_anonymous_users();
}

if (!isset($_GET['filename']) || !isset($_GET['filepath']) || !isset($_GET['dir']) || !isset($_GET['course_code'])|| !isset($_GET['nano_group_id']) || !isset($_GET['nano_session_id']) || !isset($_GET['nano_user_id'])){
	echo 'Error. Not allowed';
	exit;
}
if (!is_uploaded_file($_FILES['voicefile']['tmp_name'])) exit;

//clean
$nano_user_id=Security::remove_XSS($_GET['nano_user_id']);
$nano_group_id=Security::remove_XSS($_GET['nano_group_id']);
$nano_session_id=Security::remove_XSS($_GET['nano_session_id']);

$filename=Security::remove_XSS($_GET['filename']);
$filename=urldecode($filename);
$filepath=Security::remove_XSS(urldecode($_GET['filepath']));
$dir=Security::remove_XSS(urldecode($_GET['dir']));

$course_code = Security::remove_XSS(urldecode($_GET['course_code']));
$_course=api_get_course_info($course_code);

$filename = trim($_GET['filename']);
$filename = Security::remove_XSS($filename);
$filename = Database::escape_string($filename);
$filename = replace_dangerous_char($filename, $strict = 'loose');// or strict
$filename = disable_dangerous_file($filename);

$title= trim(str_replace('_chnano_.','.',$filename));//hide nanogong wav tag at title
$title= str_replace('_',' ',$title);

//
$documentPath = $filepath.$filename;

/*
//comment because here api_get_user_id() return alway 0
if ($nano_user_id!= api_get_user_id() || api_get_user_id()==0 || $nano_user_id==0) {
	echo 'Not allowed';
	exit;
}
*/

//Do not use here check Fileinfo method because return: text/plain

if (!file_exists($documentPath)){
	//add document to disk
	move_uploaded_file($_FILES['voicefile']['tmp_name'], $documentPath);
	
	//add document to database
	$current_session_id = $nano_session_id; // $nano_session_id instead api_get_session_id() because here $_SESSION is lost
	$groupId=$nano_group_id; // $nano_group_id instead $_SESSION['_gid'], because here $_SESSION is lost.
	$file_size = filesize($documentPath);
	$relativeUrlPath=$dir;		
	$doc_id = add_document($_course, $relativeUrlPath.$filename, 'file', filesize($documentPath), $title);
	api_item_property_update($_course, TOOL_DOCUMENT, $doc_id, 'DocumentAdded', $nano_user_id, $groupId, null, null, null, $current_session_id);// $nano_user_id instead $_user['user_id'], because here $_user['user_id'] is lost.
} else {
	return get_lang('FileExistRename');
}