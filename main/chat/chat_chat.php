<?php // $Id: chat_chat.php,v 1.10 2005/05/18 13:58:10 bvanderkimpen Exp $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004 Dokeos S.A.
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)
	Copyright (c) Olivier Brouckaert

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact: Dokeos, 181 rue Royale, B-1000 Brussels, Belgium, info@dokeos.com
==============================================================================
*/
/**
==============================================================================
*	Chat frame that shows the message list
*
*	@author Olivier Brouckaert
*	@package dokeos.chat
==============================================================================
*/

define('FRAME','chat');

$language_file = array ('chat');
require_once '../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'document.lib.php';
require_once api_get_path(LIBRARY_PATH).'fileUpload.lib.php';
require_once api_get_path(LIBRARY_PATH).'groupmanager.lib.php';
//$course=api_get_course_id();

$course=$_GET['cidReq'];
$session_id = intval($_SESSION['id_session']);
$group_id 	= intval($_SESSION['_gid']);

// if we have the session set up
if (!empty($course))
{
	$reset=$_GET['reset']?true:false;
	$tbl_user = Database::get_main_table(TABLE_MAIN_USER);
	$query="SELECT username FROM $tbl_user WHERE user_id='".$_user['user_id']."'";
	$result=Database::query($query,__FILE__,__LINE__);

	list($pseudoUser)=Database::fetch_row($result);

	$isAllowed=(empty($pseudoUser) || !$_cid)?false:true;
	$isMaster=$is_courseAdmin?true:false;


	$dateNow=date('Y-m-d');
	$basepath_chat = '';
	$documentPath=api_get_path(SYS_COURSE_PATH).$_course['path'].'/document';
	if (!empty($group_id)) {
		$group_info = GroupManager :: get_group_properties($group_id);
		$basepath_chat = $group_info['directory'].'/chat_files';
	} else {
		$basepath_chat = '/chat_files';
	}
	$chatPath=$documentPath.$basepath_chat.'/';

	$TABLEITEMPROPERTY= Database::get_course_table(TABLE_ITEM_PROPERTY);

	if(!is_dir($chatPath))
	{
		if(is_file($chatPath))
		{
			@unlink($chatPath);
		}

		if (!api_is_anonymous()) {
			@mkdir($chatPath, api_get_permissions_for_new_directories());
			// save chat files document for group into item property
			if (!empty($group_id)) {
				$doc_id=add_document($_course,$basepath_chat,'folder',0,'chat_files');
				$sql = "INSERT INTO $TABLEITEMPROPERTY (tool,insert_user_id,insert_date,lastedit_date,ref,lastedit_type,lastedit_user_id,to_group_id,to_user_id,visibility)
						VALUES ('document',1,NOW(),NOW(),$doc_id,'FolderCreated',1,$group_id,NULL,0)";
				Database::query($sql,__FILE__,__LINE__);
			}
		}
	}

	$filename_chat = '';
	if (!empty($group_id)) {
		$filename_chat = 'messages-'.$dateNow.'_gid-'.$group_id.'.log.html';
	} else if (!empty($session_id)) {
		$filename_chat = 'messages-'.$dateNow.'_sid-'.$session_id.'.log.html';
	} else {
		$filename_chat = 'messages-'.$dateNow.'.log.html';
	}

	if(!file_exists($chatPath.$filename_chat))
	{
		@fclose(fopen($chatPath.$filename_chat,'w'));
		if (!api_is_anonymous()) {
			$doc_id=add_document($_course,$basepath_chat.'/'.$filename_chat,'file',0,$filename_chat);
			api_item_property_update($_course, TOOL_DOCUMENT, $doc_id, 'DocumentAdded', $_user['user_id'],$group_id,null,null,null,$session_id);
			api_item_property_update($_course, TOOL_DOCUMENT, $doc_id, 'invisible', $_user['user_id'],$group_id,null,null,null,$session_id);
			item_property_update_on_folder($_course,$basepath_chat, $_user['user_id']);
		}
	}

	$basename_chat = '';
	if (!empty($group_id)) {
		$basename_chat = 'messages-'.$dateNow.'_gid-'.$group_id;
	} else if (!empty($session_id)) {
		$basename_chat = 'messages-'.$dateNow.'_sid-'.$session_id;
	} else {
		$basename_chat = 'messages-'.$dateNow;
	}

	if($reset && $isMaster)
	{

		$i=1;
		while(file_exists($chatPath.$basename_chat.'-'.$i.'.log.html'))
		{
			$i++;
		}

		@rename($chatPath.$basename_chat.'.log.html',$chatPath.$basename_chat.'-'.$i.'.log.html');

		@fclose(fopen($chatPath.$basename_chat.'.log.html','w'));

		$doc_id=add_document($_course,$basepath_chat.'/'.$basename_chat.'-'.$i.'.log.html','file',filesize($chatPath.$basename_chat.'-'.$i.'.log.html'),$basename_chat.'-'.$i.'.log.html');

		api_item_property_update($_course, TOOL_DOCUMENT, $doc_id, 'DocumentAdded', $_user['user_id'],$group_id,null,null,null,$session_id);
		api_item_property_update($_course, TOOL_DOCUMENT, $doc_id, 'invisible', $_user['user_id'],$group_id,null,null,null,$session_id);
		item_property_update_on_folder($_course,$basepath_chat, $_user['user_id']);

		$doc_id = DocumentManager::get_document_id($_course,$basepath_chat.'/'.$basename_chat.'.log.html');

		update_existing_document($_course, $doc_id,0);
	}

	$content=file($chatPath.$basename_chat.'.log.html');
	$nbr_lines=sizeof($content);
	$remove=$nbr_lines-100;

	if($remove < 0)
	{
		$remove=0;
	}

	array_splice($content,0,$remove);
	include('header_frame.inc.php');

	if ($_GET["origin"]=='whoisonline') {  //the caller
		$content[0]=get_lang('CallSent').'<br>'.$content[0];
	}
	if ($_GET["origin"]=='whoisonlinejoin') {   //the joiner (we have to delete the chat request to him when he joins the chat)
		$track_user_table = Database::get_main_table(TABLE_MAIN_USER);
		$sql="update $track_user_table set chatcall_user_id = '', chatcall_date = '', chatcall_text='' where (user_id = ".$_user['user_id'].")";
		$result=Database::query($sql,__FILE__,__LINE__);
	}

	echo '<div style="margin-left: 5px;">';
	foreach($content as $thisLine)
	{
		echo strip_tags(api_html_entity_decode($thisLine),'<br> <span> <b> <i> <img> <font>');
	}
	echo '</div>';

	?>

	<a name="bottom" style="text-decoration:none;">&nbsp;</a>

	<?php
	if($isMaster || $is_courseCoach)
	{
		$rand=mt_rand(1,1000);
		echo '<div style="margin-left: 5px;">';
		echo '<a href="'.api_get_self().'?rand='.$rand.'&reset=1&cidReq='.$_GET['cidReq'].'#bottom" onclick="javascript:if(!confirm(\''.addslashes(api_htmlentities(get_lang('ConfirmReset'),ENT_QUOTES,$charset)).'\')) return false;">'.Display::return_icon('delete.gif', get_lang('ClearList')).' '.get_lang('ClearList').'</a>';
		echo '</div>';
	}
}
else
{
	include('header_frame.inc.php');
	$message=get_lang('CloseOtherSession');
	Display :: display_error_message($message);
}
include('footer_frame.inc.php');
?>
