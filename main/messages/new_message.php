<?php // $Id: new_message.php 22097 2009-07-15 04:30:05Z ivantcholakov $
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
require_once '../inc/global.inc.php';

api_block_anonymous_users();

if (api_get_setting('allow_message_tool')!='true'){
	api_not_allowed();
}

require_once api_get_path(LIBRARY_PATH).'text.lib.php';
require_once api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php';
require_once api_get_path(LIBRARY_PATH).'group_portal_manager.lib.php';
require_once api_get_path(LIBRARY_PATH).'message.lib.php';

$request=api_is_xml_http_request();
$nameTools = api_xml_http_response_encode(get_lang('Messages'));
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
		
var counter_image = 1;	
function remove_image_form(id_elem1) {
	var elem1 = document.getElementById(id_elem1);
	elem1.parentNode.removeChild(elem1);        
} 								
function add_image_form() {
    counter_image = counter_image + 1;														
	// Multiple filepaths for image form					
	var filepaths = document.getElementById("filepaths");		
	var elem1 = document.createElement("div");			
	elem1.setAttribute("id","filepath_"+counter_image);							
	filepaths.appendChild(elem1);	
	id_elem1 = "filepath_"+counter_image;		
	id_elem1 = "\'"+id_elem1+"\'";
	document.getElementById("filepath_"+counter_image).innerHTML = "<input type=\"file\" name=\"attach_"+counter_image+"\"  size=\"28\" />&nbsp;<input type=\"text\" name=\"legend[]\" size=\"28\" />&nbsp;<a href=\"javascript:remove_image_form("+id_elem1+")\"><img src=\"'.api_get_path(WEB_CODE_PATH).'img/delete.gif\"></a>";				
}		
		
</script>';

$nameTools = api_xml_http_response_encode(get_lang('ComposeMessage'));

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
	$result = Database::query($query,__FILE__,__LINE__);
	$row = Database::fetch_array($result);

	if (!isset($row[1])) {
		echo api_xml_http_response_encode(get_lang('InvalidMessageId'));
		die();
	}
	echo api_xml_http_response_encode(get_lang('To').':&nbsp;<strong>'.	GetFullUserName($row[1]).'</strong>');
	$default['title'] = api_xml_http_response_encode(get_lang('EnterTitle'));
	$default['user_list'] = $row[1];
	manage_form($default);
}

function show_compose_to_user ($receiver_id) {
	global $charset;
	echo get_lang('To').':&nbsp;<strong>'.	GetFullUserName($receiver_id).'</strong>';
	$default['title'] = api_xml_http_response_encode(get_lang('EnterTitle'));
	$default['user_list'] = $receiver_id;
	manage_form($default);
}

function manage_form ($default, $select_from_user_list = null) {
	global $charset;
	$table_message = Database::get_main_table(TABLE_MESSAGE);
	$request=api_is_xml_http_request();

	$group_id = intval($_REQUEST['group_id']);
	$message_id = intval($_GET['message_id']);

	$form = new FormValidator('compose_message',null,null,null,array('enctype'=>'multipart/form-data'));	
	if (empty($group_id)) {	
		if (isset($select_from_user_list)) {
			$form->add_textfield('id_text_name', api_xml_http_response_encode(get_lang('SendMessageTo')),true,array('size' => 40,'id'=>'id_text_name','onkeyup'=>'send_request_and_search()','autocomplete'=>'off','style'=>'padding:0px'));
			$form->addRule('id_text_name', api_xml_http_response_encode(get_lang('ThisFieldIsRequired')), 'required');
			$form->addElement('html','<div id="id_div_search" style="padding:0px" class="message-select-box" >&nbsp;</div>');
			$form->addElement('hidden','user_list',0,array('id'=>'user_list'));
		} else {
			if ($default['user_list']==0) {
				$form->add_textfield('id_text_name', api_xml_http_response_encode(get_lang('SendMessageTo')),true,array('size' => 40,'id'=>'id_text_name','onkeyup'=>'send_request_and_search()','autocomplete'=>'off','style'=>'padding:0px'));
				$form->addRule('id_text_name', api_xml_http_response_encode(get_lang('ThisFieldIsRequired')), 'required');
				$form->addElement('html','<div id="id_div_search" style="padding:0px" class="message-select-box" >&nbsp;</div>');
			}
			$form->addElement('hidden','user_list',0,array('id'=>'user_list'));
		}
	} else {		
		$group_info = GroupPortalManager::get_group_data($group_id);
		$form->addElement('html','<div class="row"><div class="label">'.get_lang('ToGroup').'</div><div class="formw">'.api_xml_http_response_encode($group_info['name']).'</div></div>');		
		$form->addElement('hidden','group_id',$group_id);
		$form->addElement('hidden','parent_id',$message_id);		
	}
	$form->add_textfield('title', api_xml_http_response_encode(get_lang('Title')));	
	$form->add_html_editor('content', '', false, false, array('ToolbarSet' => 'Messages', 'Width' => '95%', 'Height' => '250'));
	if (isset($_GET['re_id'])) {
		$form->addElement('hidden','re_id',Security::remove_XSS($_GET['re_id']));
		$form->addElement('hidden','save_form','save_form');
	}	
	if (empty($group_id)) {
		$form->addElement('html','<div class="row"><div class="label">'.get_lang('FilesAttachment').'</div><div class="formw">
				<span id="filepaths">
				<div id="filepath_1">
				<input type="file" name="attach_1"  size="28" />
				<input type="text" name="legend[]" size="28" />
				</div></span></div></div>');
		$form->addElement('html','<div class="row"><div class="formw"><a href="javascript://" onclick="return add_image_form()">'.get_lang('AddOneMoreFile').'</a>&nbsp;('.get_lang('MaximunFileSizeXMB').')</div></div>');
	}
		
	$form->addElement('style_submit_button','compose',api_xml_http_response_encode(get_lang('SendMessage')),'class="save"');
	$form->setRequiredNote(api_xml_http_response_encode('<span class="form_required">*</span> <small>'.get_lang('ThisFieldIsRequired').'</small>'));	
	if (!empty($group_id) && !empty($message_id)) {
		$message_info = MessageManager::get_message_by_id($message_id);		
		$default['title']=get_lang('Re:').api_html_entity_decode($message_info['title'],ENT_QUOTES,$charset);		
	}		
	$form->setDefaults($default);
	if ($form->validate()) {				
		$values = $form->exportValues();				
		$receiver_user_id = $values['user_list'];
		$title = $values['title'];
		$content = $values['content'];
		$file_comments = $_POST['legend'];
		$group_id = $values['group_id'];
		$parent_id = $values['parent_id'];
		//all is well, send the message
		MessageManager::send_message($receiver_user_id, $title, $content, $_FILES, $file_comments, $group_id, $parent_id);		
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
	$interbreadcrumb[] = array ('url' => 'main/auth/profile.php', 'name' => get_lang('Profile'));
	$interbreadcrumb[]= array (
		'url' => 'inbox.php',
		'name' => get_lang('Inbox')
	);
}
	$interbreadcrumb[]= array (
		'url' => '#',
		'name' => get_lang('ComposeMessage')
	);

	Display::display_header('');

$group_id = intval($_REQUEST['group_id']);
echo '<div class=actions>';
if ($group_id != 0) {
	echo '<a href="'.api_get_path(WEB_PATH).'main/social/groups.php?id='.$group_id.'">'.Display::return_icon('back.png',api_xml_http_response_encode(get_lang('ComposeMessage'))).api_xml_http_response_encode(get_lang('BackToGroup')).'</a>';
	echo '<a href="'.api_get_path(WEB_PATH).'main/messages/new_message.php?group_id='.$group_id.'">'.Display::return_icon('message_new.png',api_xml_http_response_encode(get_lang('ComposeMessage'))).api_xml_http_response_encode(get_lang('ComposeMessage')).'</a>';
} else {
	echo '<a href="'.api_get_path(WEB_PATH).'main/messages/inbox.php">'.Display::return_icon('inbox.png',api_xml_http_response_encode(get_lang('Inbox'))).api_xml_http_response_encode(get_lang('Inbox')).'</a>';
	echo '<a href="'.api_get_path(WEB_PATH).'main/messages/new_message.php">'.Display::return_icon('message_new.png',api_xml_http_response_encode(get_lang('ComposeMessage'))).api_xml_http_response_encode(get_lang('ComposeMessage')).'</a>';
	echo '<a href="'.api_get_path(WEB_PATH).'main/messages/outbox.php">'.Display::return_icon('outbox.png',api_xml_http_response_encode(get_lang('Outbox'))).api_xml_http_response_encode(get_lang('Outbox')).'</a>';
}
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
	
	$restrict = false;
	if (isset($_POST['id_text_name'])) {
		$restrict = $_POST['id_text_name'];
	} else if (isset($_POST['group_id'])) {
		$restrict = $_POST['group_id'];
	} 
	
	if (isset($_GET['re_id'])) {
		$default['title'] = api_xml_http_response_encode($_POST['title']);
		$default['content'] = api_xml_http_response_encode($_POST['content']);
		//$default['user_list'] = $_POST['user_list'];
		manage_form($default);
	} else {
		if ($restrict) {
			$default['title'] = api_xml_http_response_encode($_POST['title']);			
			if (!isset($_POST['group_id'])) {
				$default['id_text_name'] = api_xml_http_response_encode($_POST['id_text_name']);
				$default['user_list'] = $_POST['user_list'];
			} else {
				$default['group_id'] = $_POST['group_id'];
			}
			manage_form($default);
		} else {
			Display::display_error_message(api_xml_http_response_encode(get_lang('ErrorSendingMessage')));
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
