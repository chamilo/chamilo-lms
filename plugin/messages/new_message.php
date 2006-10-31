<?php // $Id: new_message.php 9860 2006-10-31 12:00:20Z evie_em $
/*
==============================================================================
	Dokeos - elearning and course management software
	
	Copyright (c) Facultad de Matematicas, UADY (México)
	Copyright (c) Evie, Free University of Brussels (Belgium)
	
	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".
	
	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.
	
	See the GNU General Public License for more details.
	
	Contact address: Dokeos, 44 rue des palais, B-1030 Brussels, Belgium
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
$langFile= "messages";
$cidReset=true;
include_once('../../main/inc/global.inc.php');
echo $_SESSION['prueba'];
api_block_anonymous_users();
include_once('./functions.inc.php');
include_once(api_get_path(LIBRARY_PATH).'/text.lib.php');

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
$nameTools = get_lang('ComposeMessage');

/*
==============================================================================
		FUNCTIONS
==============================================================================
*/ 

/**
* Shows the compose area + a list of users to select from.
*/
function show_compose_to_any($user_id)
{
	echo '<form action="'.$_SERVER['PHP_SELF'].
        '" method="post" name="msgform" id="msgform" onSubmit="return validate(msgform,user_list)">
        <table width="100%" border="0" cellpadding="5" cellspacing="0">
        <tr>
        <td width="64%">
        </td>
        <td width="36%" align="left"><strong>'.get_lang("SendMessageTo").'</strong></td>
        </tr>
        <tr>
        <td valign="top">';
	display_html_editor_area("content",0);
	echo '</td>
			<td valign="top" align="left">';
	display_select_user_list($user_id,'user_list',200,20);
	echo '</td>
          </tr>
          <tr>
          <td><input type="submit" name="Submit" value="' . get_lang("SendMessage") . '">
          <input name="compose" type="hidden" id="compose" value="1"></td>
          <td>&nbsp;</td>
          </tr>
         </table>';
}

function show_compose_reply_to_message($message_id, $receiver_id)
{
	$query = "SELECT * FROM `".MESSAGES_DATABASE."` WHERE id_receiver=".$receiver_id." AND id='".$message_id."';";
	$result = api_sql_query($query,__FILE__,__LINE__);
	$row = mysql_fetch_array($result);
	if(!isset($row[1]))
	{
		echo get_lang('InvalidMessageId');
		die();
	}
	echo '<form action="'.$_SERVER['PHP_SELF'].
        '" method="post" name="msgform" id="msgform" onSubmit="return validate(msgform,user_list)">
        <table width="100%" border="0" cellpadding="5" cellspacing="0">
        <tr>
        <td width="64%">
        '.get_lang('To').':&nbsp;<strong>'.	GetFullUserName($row[1],$mysqlMainDb).'</strong>
        </td>
        <td width="36%"><div align="left"></div></td>
        </tr>
        <tr>
        <td>';
	display_html_editor_area("content",1);
	echo '</td>
          <td><div align="left">';
  
	echo '<input type="hidden" name="user_list" value ="'.$row[1].'">';
	echo '</div></td>
          </tr>
          <tr>
          <td><input type="submit" name="Submit" value="'.get_lang("SendMessage").'">
          <input name="compose" type="hidden" id="compose" value="1"></td>
          <td>&nbsp;</td>
          </tr>
         </table>';
}

function show_compose_to_user($receiver_id)
{
	echo '<form action="'.$_SERVER['PHP_SELF'].
        '" method="post" name="msgform" id="msgform" onSubmit="return validate(msgform,user_list)">
        <table width="100%" border="0" cellpadding="5" cellspacing="0">
        <tr>
        <td width="64%">
        '.get_lang('To').':&nbsp;<strong>' . GetFullUserName($receiver_id,$mysqlMainDb) . '</strong>
        </td>
        <td width="36%"><div align="left"></div></td>
        </tr>
        <tr>
        <td>';
	display_html_editor_area("content",1);
	echo '</td>
          <td><div align="left">';
  
	echo '<input type="hidden" name="user_list" value ="'.$receiver_id.'">';
	echo '</div></td>
          </tr>
          <tr>
          <td><input type="submit" name="Submit" value="'.get_lang("SendMessage").'">
          <input name="compose" type="hidden" id="compose" value="1"></td>
          <td>&nbsp;</td>
          </tr>
         </table>';
}

/*
==============================================================================
		MAIN SECTION
==============================================================================
*/ 
$interbreadcrumb[] = array ("url" => 'inbox.php', "name" => get_lang('Messages'));
Display::display_header($nameTools, get_lang("ComposeMessage"));
api_display_tool_title($nameTools);
if(!isset($_POST['compose']))
{
	if(isset($_GET['re_id']))
	{
		$message_id = $_GET['re_id'];
		$receiver_id = $_SESSION['_uid'];
		show_compose_reply_to_message($message_id, $receiver_id);
	}
	else if(isset($_GET['send_to_user']))
	{
		show_compose_to_user($_GET['send_to_user']);
	}
	else
	{
		show_compose_to_any($_uid);
  	}
}
else
{
	if(isset($_SESSION['_uid']) && isset($_POST['user_list']) && isset($_POST['content']))
	{
		$id_tmp = $_SESSION['_uid'].$_POST['user_list'].date('d-D-w-m-Y-H-s').
					microtime().rand();
		$id_msg = md5($id_tmp);
		$query = "INSERT INTO `".MESSAGES_DATABASE."` ( `id` , `id_sender` , `id_receiver` , `status` , `date` ,`content` ) ".
				 " VALUES (".
		 		 "' ".$id_msg ."' , '".$_SESSION['_uid']."', '".$_POST['user_list']."', '1', '".date('Y-m-d H:i:s')."','".$_POST['content']."'".
		 		 ");";
		@api_sql_query($query,__FILE__,__LINE__);
		display_success_message($_POST['user_list']);
	}
	else
		Display::display_error_message(get_lang('ErrorSendingMessage'));
}


/*
==============================================================================
		FOOTER 
==============================================================================
*/ 
Display::display_footer();
?>