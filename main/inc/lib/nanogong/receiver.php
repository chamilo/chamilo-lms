<?php
/* For licensing terms, see /license.txt */

/**
 *	This file allows creating new svg and png documents with an online editor.
 *
 *	@package chamilo.document
 *
 * @author Juan Carlos Ra�a Trabado
 * @since 5/mar/2011
*/
/**
 * Code
 */
require_once '../../../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'fileUpload.lib.php';

//security. Nanogong need less security because under Firefox, Chrome..., not save user_id...
if (api_get_setting('enable_nanogong') == 'false'){
	api_protect_course_script();
	api_block_anonymous_users();
}

if (!isset($_GET['filename']) || !isset($_GET['filepath']) || !isset($_GET['dir']) || !isset($_GET['course_code'])){
	api_not_allowed(true);
}
if (!is_uploaded_file($_FILES['voicefile']['tmp_name'])) exit;

//clean
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

$title= str_replace('_',' ',$filename);
//
$documentPath = $filepath.$filename;

if (!file_exists($documentPath)){
	//add document to disk
	move_uploaded_file($_FILES['voicefile']['tmp_name'], $documentPath);
	
	//add document to database
	$current_session_id = api_get_session_id();
	$groupId=$_SESSION['_gid'];
	$file_size = filesize($documentPath);
	$relativeUrlPath=$dir;		
	$doc_id = add_document($_course, $relativeUrlPath.$filename, 'file', filesize($documentPath), $title);
	api_item_property_update($_course, TOOL_DOCUMENT, $doc_id, 'DocumentAdded', $_user['user_id'], $groupId, null, null, null, $current_session_id);
} else {
	return get_lang('FileExistRename');
}