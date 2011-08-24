<?php
/* For licensing terms, see /license.txt */
/**
*	This file is responsible for  passing requested file attachments from messages
*	Html files are parsed to fix a few problems with URLs,
*	but this code will hopefully be replaced soon by an Apache URL
*	rewrite mechanism.
*
*	@package chamilo.messages
*/
/**
 * MAIN CODE
 */

session_cache_limiter('public');

require_once '../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'group_portal_manager.lib.php';
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

$file_url = Database::escape_string($file_url);
$sql= "SELECT filename, message_id FROM $tbl_messsage_attachment WHERE path LIKE BINARY '$file_url'";

$result     = Database::query($sql);
$row        = Database::fetch_array($result, 'ASSOC');
$title      = str_replace(' ','_', $row['filename']);
$message_id = $row['message_id'];

// allow download only for user sender and user receiver
$sql = "SELECT user_sender_id, user_receiver_id, group_id FROM $tbl_messsage WHERE id = '$message_id'";
$rs           = Database::query($sql);
$row_users    = Database::fetch_array($rs, 'ASSOC');
$current_uid  = api_get_user_id();

// get message user id for inbox/outbox
$message_uid = '';
$message_type = array('inbox','outbox');
if (in_array($_GET['type'],$message_type)) {
	if ($_GET['type'] == 'inbox') {
		$message_uid = $row_users['user_receiver_id'];
	} else {
		$message_uid = $row_users['user_sender_id'];
	}
}

// allow to the correct user for download this file
$not_allowed_to_edit = false;
if (!empty($row_users['group_id'])) {
	$users_group = GroupPortalManager::get_all_users_by_group($row_users['group_id']);
	if (!in_array($current_uid,array_keys($users_group))) {
		$not_allowed_to_edit = true;
	}
} else {
	if ($current_uid != $message_uid) {
		$not_allowed_to_edit = true;
	}
}

if ($not_allowed_to_edit) {
	api_not_allowed();
	exit;
}

// set the path directory file
if (!empty($row_users['group_id'])) {
	$path_user_info = GroupPortalManager::get_group_picture_path_by_id($row_users['group_id'], 'system', true);
} else {
	$path_user_info = UserManager::get_user_picture_path_by_id($message_uid, 'system', true);
}

$full_file_name = $path_user_info['dir'].'message_attachments/'.$file_url;

if (Security::check_abs_path($full_file_name, $path_user_info['dir'].'message_attachments/')) {
    // launch event
    event_download($file_url);
    DocumentManager::file_send_for_download($full_file_name,TRUE, $title);
}
exit;
