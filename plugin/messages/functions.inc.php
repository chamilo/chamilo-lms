<?php
/*
    DOKEOS - elearning and course management software

    For a full list of contributors, see documentation/credits.html

    This program is free software; you can redistribute it and/or
    modify it under the terms of the GNU General Public License
    as published by the Free Software Foundation; either version 2
    of the License, or (at your option) any later version.
    See "documentation/licence.html" more details.

    Contact:
		Dokeos
		Rue des Palais 44 Paleizenstraat
		B-1030 Brussels - Belgium
		Tel. +32 (2) 211 34 56
*/

/**
*	@package dokeos.survey
* 	@author
* 	@version $Id: functions.inc.php 12281 2007-05-03 16:25:12Z yannoo $
* 	@todo use database library
*/
if(!function_exists('api_get_path')){header('location: view_message.php');die;}
include_once(api_get_path(LIBRARY_PATH).'/online.inc.php');

define ("MESSAGES_DATABASE", "messages");



function get_online_user_list($current_user_id)
{
	$MINUTE=30;
	global $_configuration;
	$userlist = WhoIsOnline($current_user_id,$_configuration['statistics_database'],$MINUTE);
	foreach($userlist as $row)
	{
		$receiver_id = $row[0];
		$online_user_list[$receiver_id] = GetFullUserName($receiver_id).($current_user_id==$receiver_id?("&nbsp;(".get_lang('Myself').")"):(""));
	}

	return $online_user_list;
}

/**
* Displays info stating that the message is sent successfully.
*/
function display_success_message($uid)
{
	$success= "<p style=\"text-align: center\">".
			get_lang('MessageSentTo').
			"&nbsp;<b>".
			GetFullUserName($uid).
			"</b>".
			"<br><a href=\"".
			"inbox.php\">".
			get_lang('BackToInbox').
			"</a>";
	Display::display_confirmation_message($success, false);
}

/**
* @todo this function seems no longer user
* but is still mentioned in comments, what can be the use?
*/
function validate_text($texto)
{
	$MAX_SIZE = 60; /*Tama� m�imo de caracteres por l�ea*/
	$i=0;
	$lines = array(); /*Arreglo que contendr�las l�eas del texto*/
	$token = strtok($texto, "\n");
	while($token)
	{
		$lines[$i]= $token;
		$token = strtok("\n");
		$i++;
	}
	$modificado= "";
	for($i=0; $i<count($lines); $i++ )
	{
		if(strlen($lines[$i])>$MAX_SIZE + 1)
		{
			$modificado2= substr($lines[$i], 0, $MAX_SIZE);
			for($j=$MAX_SIZE; $j<strlen($lines[$i]); $j+=$MAX_SIZE)
			{
				$modificado2 = $modificado2."\n".substr($lines[$i], $j, $MAX_SIZE);
			}
		}
		else
		{
			$modificado2= $lines[$i];
		}
		$modificado = $modificado.$modificado2."\n";
	}
	$modificado = substr($modificado, 0 ,strlen($modificado)-1);
	$modificado = str_replace("&", "&#038", $modificado); // �em
	$modificado = str_replace("<", "&#60", $modificado); // para evitar que lo convierta en html
	$modificado = str_replace(">", "&#62", $modificado); // �em


	return $modificado;
}

/**
* Displays the wysiwyg html editor.
*/
function display_html_editor_area($name,$resp)
{
	api_disp_html_area($name, 'Type your message here.', '', '100%');
}

/**
* Get the new messages for the current user from the database.
*/
function get_new_messages()
{
	if (!api_get_user_id())
	{
		return false;
	}
	$i=0;
	$query = "SELECT * FROM `".MESSAGES_DATABASE."` WHERE id_receiver=".api_get_user_id()." AND status=1;";
	$result = api_sql_query($query,__FILE__,__LINE__);
	$i = mysql_num_rows($result);
	return $i;
}

/**
* Get the list of user_ids of users who are online.
*/
function users_connected_by_id()
{
	global $_configuration, $_user;
	$MINUTE=30;
	$user_connect = WhoIsOnline($_user['user_id'],$_configuration['statistics_database'],$MINUTE);
	for ($i=0; $i<count($user_connect); $i++)
	{
		$user_id_list[$i]=$user_connect[$i][0];
	}
	return $user_id_list;
}

/**
 * Gets the total number of messages, used for the inbox sortable table
 */
function get_number_of_messages()
{
	$sql_query = "SELECT COUNT(*) as number_messages FROM `".MESSAGES_DATABASE."` WHERE id_receiver=".$_SESSION['_user']['user_id'];
	$sql_result = api_sql_query($sql_query,__FILE__,__LINE__);
	$result = mysql_fetch_array($sql_result);
	return $result['number_messages'];
}

/**
 * Gets information about some messages, used for the inbox sortable table
 * @param int $from
 * @param int $number_of_items
 * @param string $direction
 */
function get_message_data($from, $number_of_items, $column, $direction)
{
	$sql_query = "SELECT id as col0, id_sender as col1, title as col2, date as col3 FROM `".MESSAGES_DATABASE."` WHERE id_receiver=".$_SESSION['_user']['user_id']." ORDER BY col$column $direction LIMIT $from,$number_of_items";
	$sql_result = api_sql_query($sql_query,__FILE__,__LINE__);
	$i = 0;
	$message_list = array ();
	while ($result = mysql_fetch_row($sql_result))
	{
		$message[0] = $result[0];
		$message[1] = GetFullUserName($result[1]);
		//$message[1] = "<a href=\"view_message.php?id=".$result[0]."\" title=\"$texto\">".GetFullUserName($result[1])."</a>";
		$message[2] = '<a href="view_message.php?id='.$result[0].'">'.$result[2].'</a>';
		$message[3] = $result[3]; //date stays the same
		$message[4] = '<a href="new_message.php?re_id='.$result[0].'"><img src="'.api_get_path(WEB_IMG_PATH).'forum.gif" alt="'.get_lang("ReplyToMessage").'" align="middle"></img></a>';
		$message_list[] = $message;
		$i++;
	}
	return $message_list;
}

/**
* Displays the inbox of a user, listing all messages.
* In the process of moving towards sortable table.
*/
function inbox_display()
{
	//delete messages if delete action was chosen
	if (isset ($_POST['action']))
	{
		switch ($_POST['action'])
		{
			case 'delete' :
				$number_of_selected_messages = count($_POST['id']);
				foreach ($_POST['id'] as $index => $message_id)
				{
					$query = "DELETE FROM ".MESSAGES_DATABASE." WHERE id_receiver=".api_get_user_id()." AND id='".mysql_real_escape_string($message_id)."'";
					api_sql_query($query,__FILE__,__LINE__);
				}
				Display :: display_normal_message(get_lang('SelectedMessagesDeleted'));
				break;
		}
	}

	// display sortable table with messages of the current user
	$table = new SortableTable('messages', 'get_number_of_messages', 'get_message_data', 1);
	$table->set_header(0, '', false);
	$table->set_header(1, get_lang('From'));
	$table->set_header(2, get_lang('Title'));
	$table->set_header(3, get_lang('Date'));
	$table->set_header(4, get_lang("ReplyToMessage"), false);
	$table->set_form_actions(array ('delete' => get_lang('DeleteSelectedMessages')));
	$table->display();
}
?>