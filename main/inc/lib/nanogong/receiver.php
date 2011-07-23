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

//security
api_protect_course_script();
api_block_anonymous_users();
if (!isset($_GET['filename']) || !isset($_GET['filepath']) || !isset($_GET['dir'])){
	api_not_allowed(true);
}
if (!is_uploaded_file($_FILES['voicefile']['tmp_name'])) exit;

//clean
$filename=$_GET['filename'];
$filename=urldecode($filename);//TODO: implement a good for record_audio.php encodeURIComponent
$filepath=urldecode($_GET['filepath']);
$dir=urldecode($_GET['dir']);

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

}
else{
	return get_lang('FileExistRename');
}

?>
