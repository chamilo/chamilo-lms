<?php // $Id: new_message.php 19472 2009-03-31 23:51:28Z cvargas1 $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2009 Dokeos SPRL
	Copyright (c) 2009 Julio Montoya Armas <gugli100@gmail.com>
	Copyright (c) Facultad de Matematicas, UADY (M�xico)
	Copyright (c) Evie, Free University of Brussels (Belgium)		
	Copyright (c) 2009 Isaac Flores Paz <isaac.flores.paz@gmail.com>
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
/**
* This script shows a compose area (wysiwyg editor if supported, otherwise
* a simple textarea) where the user can type a message.
* There are three modes
* - standard: type a message, select a user to send it to, press send
* - reply on message (when pressing reply when viewing a message)
* - send to specific user (when pressing send message in the who is online list)
*/
/*
==============================================================================
		INIT SECTION
==============================================================================
*/
// name of the language file that needs to be included
$language_file= 'messages';
$cidReset=true;
include_once('../inc/global.inc.php');

api_block_anonymous_users();

if (api_get_setting('allow_message_tool')!='true'){
	api_not_allowed();
}


require_once'../messages/message.class.php';
require_once(api_get_path(LIBRARY_PATH).'/text.lib.php');
require_once(api_get_path(LIBRARY_PATH).'/formvalidator/FormValidator.class.php');
$nameTools= get_lang('Messages');
/*
-----------------------------------------------------------
	Constants and variables
-----------------------------------------------------------
*/
$htmlHeadXtra[]='
<script language="javascript">
function validate(form,list)
{
	if(list.selectedIndex<0)
	{
    	alert("Please select someone to send the message to.")
    	return false
	}
	else
    	return true
}

</script>';
$htmlHeadXtra[] = '<script src="../inc/lib/javascript/jquery.js" type="text/javascript" language="javascript"></script>'; //jQuery
$htmlHeadXtra[] = '<script type="text/javascript">
$(document).ready(function (){
	cont=0;
      $("#id_text_name").bind("keyup", function(){
      	name=$("#id_text_name").get(0).value;
		$.ajax({
				contentType: "application/x-www-form-urlencoded",
				beforeSend: function(objeto) {
				/*$("#id_div_search").html("Searching...");*/ },
				type: "POST",
				url: "../social/select_options.php",
				data: "search="+name,
				success: function(datos){
				$("#id_div_search").html(datos)
				$("#id_search_name").bind("click", function(){
					name_option=$("select#id_search_name option:selected").text();
					code_option=$("select#id_search_name option:selected").val();
					 $("#user_list").attr("value", code_option);
					 $("#id_text_name").attr("value", name_option);
					 $("#id_div_search").html("");
					 cont++;
				 });
				}
		});
      });  
});
	</script>';	
$nameTools = get_lang('ComposeMessage');
$fck_attribute['Height'] = "150";
$fck_attribute['Width'] = "95%";
$fck_attribute['ToolbarSet'] = "Profil";
/*
==============================================================================
		FUNCTIONS
==============================================================================
*/

/**
* Shows the compose area + a list of users to select from.
*/
function show_compose_to_any ($user_id) {
	$online_user_list = MessageManager::get_online_user_list($user_id);
	$default['user_list'] = 0;
	$online_user_list=null;
	manage_form($default, $online_user_list);
}

function show_compose_reply_to_message ($message_id, $receiver_id) {
	global $charset;
	$table_message = Database::get_main_table(TABLE_MESSAGE);
	$query = "SELECT * FROM $table_message WHERE user_receiver_id=".$receiver_id." AND id='".$message_id."';";
	$result = api_sql_query($query,__FILE__,__LINE__);
	$row = Database::fetch_array($result);

	if (!isset($row[1])) {
		echo get_lang('InvalidMessageId');
		die();
	}
	echo get_lang('To').':&nbsp;<strong>'.	GetFullUserName($row[1]).'</strong>';
	$default['title'] =mb_convert_encoding(get_lang('EnterTitle'),'UTF-8',$charset);
	$default['user_list'] = $row[1];
	manage_form($default);
}

function show_compose_to_user ($receiver_id) {
	global $charset;
	echo get_lang('To').':&nbsp;<strong>'.	GetFullUserName($receiver_id).'</strong>';
	$default['title'] = mb_convert_encoding(get_lang('EnterTitle'),'UTF-8',$charset);
	$default['user_list'] = $receiver_id;
	manage_form($default);
}

function manage_form ($default, $select_from_user_list = null) {
	global $charset;
	$table_message = Database::get_main_table(TABLE_MESSAGE);
	$request=api_is_xml_http_request();
	if ($request===true) {
		$form = new FormValidator('compose_message','post','index.php?sendform=true#remote-tab-2');
	} else {
		$form = new FormValidator('compose_message');
	}
	if (isset($select_from_user_list)) {
		$form->add_textfield('id_text_name',get_lang('SendMessageTo'),true,array('size' => 40,'id'=>'id_text_name','onclick'=>'send_request_and_search()','onmouseout'=>'list_search_hide ()'));
		$form->addRule('id_text_name', get_lang('ThisFieldIsRequired'), 'required');
		$form->addElement('html','<div id="id_div_search" class="message-search">&nbsp;</div>');
		$form->addElement('hidden','user_list',0,array('id'=>'user_list'));
	} else {
		if ($default['user_list']==0) {
			$form->add_textfield('id_text_name',get_lang('SendMessageTo'),true,array('size' => 40,'id'=>'id_text_name','onclick'=>'send_request_and_search()','onmouseout'=>'list_search_hide ()'));
			$form->addRule('id_text_name', get_lang('ThisFieldIsRequired'), 'required');
			$form->addElement('html','<div id="id_div_search" class="message-search">&nbsp;</div>');
		}
		$form->addElement('hidden','user_list',0,array('id'=>'user_list'));
	}
	$form->add_textfield('title', mb_convert_encoding(get_lang('Title'),'UTF-8',$charset));
	$form->add_html_editor('content', '',false,false);
	if (isset($_GET['re_id'])) {
		$form->addElement('hidden','re_id',$_GET['re_id']);
	}
	$form->addElement('submit', 'compose', get_lang('Send'));
	$form->setDefaults($default);
	if ($form->validate()) {
		$values = $form->exportValues();
		$receiver_user_id = $values['user_list'];
		$title = $values['title'];
		$content = $values['content'];
		//all is well, send the message
		MessageManager::send_message($receiver_user_id, $title, $content);
		MessageManager::display_success_message($receiver_user_id);
	} else {
		$form->display();
	}
}
/*
==============================================================================
		MAIN SECTION
==============================================================================
*/
if (isset($_GET['rs'])) {
	$interbreadcrumb[] = array ('url' => 'inbox.php', 'name' => get_lang('Messages'));
	$interbreadcrumb[]= array (
		'url' => '../social/'.$_SESSION['social_dest'],
		'name' => get_lang('SocialNetwork')
	);
} else {
	$interbreadcrumb[] = array ('url' => '#', 'name' => get_lang('Messages'));
	$interbreadcrumb[]= array (
		'url' => 'outbox.php',
		'name' => get_lang('Outbox')
	);
	$interbreadcrumb[]= array (
		'url' => 'inbox.php',
		'name' => get_lang('Inbox')
	);
}
	$interbreadcrumb[]= array (
		'url' => '#',
		'name' => get_lang('ComposeMessage')
	);
$request=api_is_xml_http_request();
if ($request===false) {
	Display::display_header('');	
}

api_display_tool_title($nameTools);
echo '<div class=actions>';
echo '<a onclick="close_div_show(\'div_content_messages\')" href="javascript:void(0)">'.Display::return_icon('folder_up.gif',get_lang('BackToInbox')).get_lang('BackToInbox').'</a>';
echo '</div>';
if (!isset($_POST['compose'])) {
	if(isset($_GET['re_id'])) {
		$message_id = $_GET['re_id'];
		$receiver_id = api_get_user_id();
		show_compose_reply_to_message($message_id, $receiver_id);
	} elseif(isset($_GET['send_to_user'])) {
		show_compose_to_user($_GET['send_to_user']);
	} else {
		show_compose_to_any($_user['user_id']);
  	}
} else {
	$restrict=isset($_POST['id_text_name']) ? $_POST['id_text_name'] : false;
	if ($restrict===false && isset($_GET['re_id'])) {
		
	}
	if (isset($_GET['re_id'])) {
		$default['title'] = mb_convert_encoding($_POST['title'],'UTF-8',$charset);
		$default['content'] = mb_convert_encoding($_POST['content'],'UTF-8',$charset);
		//$default['user_list'] = $_POST['user_list'];
		manage_form($default);	
	} else {
		if ($restrict) {
			$default['title'] = mb_convert_encoding($_POST['title'],'UTF-8',$charset);
			$default['id_text_name'] = mb_convert_encoding($_POST['id_text_name'],'UTF-8',$charset); 
			$default['user_list'] = $_POST['user_list'];
			manage_form($default);
		} else {
			Display::display_error_message(get_lang('ErrorSendingMessage'));		
		}	
	}
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