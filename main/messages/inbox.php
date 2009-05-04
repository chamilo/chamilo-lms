<?php 
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2009 Dokeos SPRL
	Copyright (c) 2009 Julio Montoya Armas <gugli100@gmail.com>
	Copyright (c) Facultad de Matematicas, UADY (México)
	Copyright (c) Evie, Free University of Brussels (Belgium)	
	Copyright (c) 2009 Isaac Flores Paz <isaac.flores@dokeos.com>	

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

    Contact address: Dokeos, rue du Corbeau, 108, B-1030 Brussels, Belgium
    Mail: info@dokeos.com
==============================================================================
*/
/*
==============================================================================
		INIT SECTION
==============================================================================
*/ 
// name of the language file that needs to be included 
$language_file = array('registration','messages','userInfo','admin');
$cidReset=true;
include_once ('../inc/global.inc.php');
require_once '../messages/message.class.php';
include_once(api_get_path(LIBRARY_PATH).'/message.lib.php');
api_block_anonymous_users();
if (isset($_GET['messages_page_nr'])) {
	if (api_get_setting('allow_social_tool')=='true' &&  api_get_setting('allow_message_tool')=='true') {
		header('Location:../social/index.php?pager="'.Security::remove_XSS($_GET['messages_page_nr']).'"&remote=2#remote-tab-2');	
	}
}
if (api_get_setting('allow_message_tool')!='true'){
	api_not_allowed();
}
$htmlHeadXtra[]='<script language="javascript">
<!--
function enviar(miforma) 
{ 
	if(confirm("'.get_lang("SureYouWantToDeleteSelectedMessages").'"))
		miforma.submit();
} 
function select_all(formita)
{ 
   for (i=0;i<formita.elements.length;i++) 
	{
      		if(formita.elements[i].type == "checkbox") 			
				formita.elements[i].checked=1			
	}
}
function deselect_all(formita)
{ 
   for (i=0;i<formita.elements.length;i++) 
	{
      		if(formita.elements[i].type == "checkbox") 			
				formita.elements[i].checked=0			
	}	
}
//-->
</script>';

/*
==============================================================================
		MAIN CODE
==============================================================================
*/
$nameTools = get_lang('Messages');
$request=api_is_xml_http_request();
if (isset($_GET['form_reply']) || isset($_GET['form_delete'])) {
	/***********************************************/
	$info_reply=array();
	$info_delete=array();
	/***********************************************/
	if ( isset($_GET['form_reply']) ) {
		$info_reply=explode(',',$_GET['form_reply']);
		$count_reply=count($info_reply);
		$button_sent=urldecode($info_reply[4]);	
	}
	/***********************************************/
	if ( isset($_GET['form_delete']) ) {
		$info_delete=explode(',',$_GET['form_delete']);
		$count_delete=(count($info_delete)-1);	
	}
	/***********************************************/

	if ( isset($button_sent) ) {
		$title     = urldecode($info_reply[0]);
		$content   = urldecode($info_reply[1]);
		$user_reply= $info_reply[2];
		$user_email_base=str_replace(')','(',$info_reply[5]);
		$user_email_prepare=explode('(',$user_email_base);
		if (count($user_email_prepare)==1) {
			$user_email=trim($user_email_prepare[0]);
		} elseif (count($user_email_prepare)==3) {
			$user_email=trim($user_email_prepare[1]);	
		}
		$user_id_by_email=MessageManager::get_user_id_by_email($user_email);

		if ($info_reply[6]=='save_form') {
			$user_id_by_email=$info_reply[2];
		}
		if ( isset($user_reply) && !is_null($user_id_by_email) && strlen($info_reply[0]) >0) {
			MessageManager::send_message($user_id_by_email, $title, $content);
			MessageManager::display_success_message($user_id_by_email);
			exit;
		} elseif (is_null($user_id_by_email)) {
			$message_box=get_lang('ErrorSendingMessage').
			'&nbsp;
			<br /><a href="../social/index.php?#remote-tab-2">'.
			get_lang('BackToInbox').
			'</a>';
			Display::display_error_message($message_box,false);
			exit;
		}
	} elseif (trim($info_delete[0])=='delete' ) {
		for ($i=1;$i<=$count_delete;$i++) {
			MessageManager::delete_message_by_user_receiver(api_get_user_id(), $info_delete[$i]);	
		}
			$message_box=get_lang('SelectedMessagesDeleted').
			'&nbsp;
			<br /><a href="../social/index.php?#remote-tab-2">'.
			get_lang('BackToInbox').
			'</a>';
			Display::display_normal_message($message_box,false);
		    exit;
	}
}

if ($request===false) {
	$interbreadcrumb[]= array (
		'url' => '#',
		'name' => get_lang('Messages')
	);
	$interbreadcrumb[]= array (
		'url' => 'outbox.php',
		'name' => get_lang('Outbox')
	);
	$interbreadcrumb[]= array (
		'url' => 'inbox.php',
		'name' => get_lang('Inbox')
	);
	Display::display_header('');
	$link_ref="new_message.php";	
} else {
	$link_ref="../messages/new_message.php?rs=1";
}
$table_message = Database::get_main_table(TABLE_MESSAGE);
echo '<div id="div_content_messages">&nbsp;&nbsp;';
//api_display_tool_title(mb_convert_encoding(get_lang('Inbox'),'UTF-8',$charset));
echo '<div class=actions>';
echo '<a onclick="compose_and_show_message(\'show\',\'1\')" href="javascript:void(0)">'.Display::return_icon('message_new.png',mb_convert_encoding(get_lang('ComposeMessage'),'UTF-8',$charset)).mb_convert_encoding(get_lang('ComposeMessage'),'UTF-8',$charset).'</a>';
echo '</div>';
echo '</div>';
if (!isset($_GET['del_msg'])) {
	inbox_display();
} else {
	$num_msg = $_POST['total'];
	for ($i=0;$i<$num_msg;$i++) {
		if($_POST[$i]) {
			//the user_id was necesarry to delete a message??
			MessageManager::delete_message_by_user_receiver(api_get_user_id(), $_POST['_'.$i]);			
		}
	}
	inbox_display();
}

/*
==============================================================================
		FOOTER 
==============================================================================
*/ 
if ($request===false) {
	Display::display_footer();
}
?>