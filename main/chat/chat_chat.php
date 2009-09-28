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
require('../inc/global.inc.php');
include(api_get_path(LIBRARY_PATH).'document.lib.php');
include (api_get_path(LIBRARY_PATH).'fileUpload.lib.php');
//$course=api_get_course_id();

$course=$_GET['cidReq'];

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

	$documentPath=api_get_path(SYS_COURSE_PATH).$_course['path'].'/document/';
	$chatPath=$documentPath.'chat_files/';
	$TABLEITEMPROPERTY= Database::get_course_table(TABLE_ITEM_PROPERTY);

	if(!is_dir($chatPath))
	{
		if(is_file($chatPath))
		{
			@unlink($chatPath);
		}

		if (!api_is_anonymous()) {
			$perm = api_get_setting('permissions_for_new_directories');
			$perm = octdec(!empty($perm)?$perm:'0770');
			@mkdir($chatPath,$perm);
			@chmod($chatPath,$perm);
			$doc_id=add_document($_course,'/chat_files','folder',0,'chat_files');
			Database::query("INSERT INTO ".$TABLEITEMPROPERTY . " (tool,insert_user_id,insert_te,lastedit_date,ref,lastedit_type,lastedit_user_id,to_group_id,to_user_id,visibility) VALUES ('document',1,NOW(),NOW(),$doc_id,'DocumentAdded',1,0,NULL,0)");
		}
	}

	if(!file_exists($chatPath.'messages-'.$dateNow.'.log.html'))
	{
		@fclose(fopen($chatPath.'messages-'.$dateNow.'.log.html','w'));
		if (!api_is_anonymous()) {
			$doc_id=add_document($_course,'/chat_files/messages-'.$dateNow.'.log.html','file',0,'messages-'.$dateNow.'.log.html');
			api_item_property_update($_course, TOOL_DOCUMENT, $doc_id, 'DocumentAdded', $_user['user_id']);
			item_property_update_on_folder($_course,'/chat_files', $_user['user_id']);
		}
	}

	if($reset && $isMaster)
	{
		$i=1;

		while(file_exists($chatPath.'messages-'.$dateNow.'-'.$i.'.log.html'))
		{
			$i++;
		}

		@rename($chatPath.'messages-'.$dateNow.'.log.html',$chatPath.'messages-'.$dateNow.'-'.$i.'.log.html');

		@fclose(fopen($chatPath.'messages-'.$dateNow.'.log.html','w'));

		$doc_id=add_document($_course,'/chat_files/messages-'.$dateNow.'-'.$i.'.log.html','file',filesize($chatPath.'messages-'.$dateNow.'-'.$i.'.log.html'),'messages-'.$dateNow.'-'.$i.'.log.html');

		api_item_property_update($_course, TOOL_DOCUMENT, $doc_id, 'DocumentAdded', $_user['user_id']);
		item_property_update_on_folder($_course,'/chat_files', $_user['user_id']);

		$doc_id = DocumentManager::get_document_id($_course,'/chat_files/messages-'.$dateNow.'.log.html');

		update_existing_document($_course, $doc_id,0);
	}

	$content=file($chatPath.'messages-'.$dateNow.'.log.html');
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
