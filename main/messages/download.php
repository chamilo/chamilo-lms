<?php
/* For licensing terms, see /dokeos_license.txt */
/**
==============================================================================
*	This file is responsible for  passing requested file attachments from messages
*	Html files are parsed to fix a few problems with URLs,
*	but this code will hopefully be replaced soon by an Apache URL
*	rewrite mechanism.
*
*	@package dokeos.messages
==============================================================================
*/

/*
==============================================================================
		MAIN CODE
==============================================================================
*/

session_cache_limiter('public');

require_once '../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'usermanager.lib.php';
require_once api_get_path(LIBRARY_PATH).'document.lib.php';

// IMPORTANT to avoid caching of documents
header('Expires: Wed, 01 Jan 1990 00:00:00 GMT');
header('Cache-Control: public');
header('Pragma: no-cache');

$file_url = $_GET['file'];
//change the '&' that got rewritten to '///' by mod_rewrite back to '&'
$file_url = str_replace('///', '&', $file_url);
//still a space present? it must be a '+' (that got replaced by mod_rewrite)
$file_url = str_replace(' ', '+', $file_url);
$file_url = str_replace('/..', '', $file_url); //echo $doc_url;

$tbl_messsage = Database::get_main_table(TABLE_MESSAGE);
$tbl_messsage_attachment = Database::get_main_table(TABLE_MESSAGE_ATTACHMENT);

$sql= "SELECT filename,message_id FROM $tbl_messsage_attachment WHERE path LIKE BINARY '$file_url'";

$result= Database::query($sql, __FILE__, __LINE__);
$row= Database::fetch_array($result);
$title = str_replace(' ','_', $row['filename']);
$message_id = $row['message_id'];

// allow download only for user sender and user receiver
$sql = "SELECT user_sender_id, user_receiver_id FROM $tbl_messsage WHERE id = '$message_id'";
$rs= Database::query($sql, __FILE__, __LINE__);
$row_users= Database::fetch_row($rs);

$current_uid = api_get_user_id(); 
if (!in_array($current_uid,$row_users)) {
	api_not_allowed();
	exit;
}

$message_uid = '';
$message_type = array('inbox','outbox');
if (in_array($_GET['type'],$message_type)) {	
	if ($_GET['type'] == 'inbox') {
		$message_uid = $row_users[1];
	} else {
		$message_uid = $row_users[0];
	}	
}

$path_user_info = UserManager::get_user_picture_path_by_id($message_uid, 'system', true);
$full_file_name = $path_user_info['dir'].'message_attachments/'.$file_url;

// launch event
event_download($file_url);
DocumentManager::file_send_for_download($full_file_name,TRUE, $title);
exit;
?>