<?php // $Id: functions.inc.php 9926 2006-11-09 13:46:10Z evie_em $
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

include_once(api_get_path(LIBRARY_PATH).'/online.inc.php');

define ("MESSAGES_DATABASE", "messages");

/**
* Displays a select list containing the users
* who are currently online. Used when composing a message.
*/
function display_select_user_list($_uid,$_name,$width,$size)
{
	$MINUTE=30;
	global $statsDbName;
	$userlist = WhoIsOnline($_uid,$statsDbName,$MINUTE);
	echo '<select  size="'.$size.'" style="width: '.$width.'px;" name="'.$_name.'">';
	foreach($userlist as $row)
		echo "<option value=\"$row[0]\">".GetFullUserName($row[0]).($_uid==$row[0]?("&nbsp;(".get_lang('Myself').")"):(""))."</option>\n";
	echo "</select>";
}

function get_online_user_list($current_user_id)
{
	$MINUTE=30;
	global $statsDbName;
	$userlist = WhoIsOnline($current_user_id,$statsDbName,$MINUTE);
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
	Display::display_normal_message($success);
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
	if (! isset($_SESSION['_uid'])) return false;
	$i=0;
	$query = "SELECT * FROM `".MESSAGES_DATABASE."` WHERE id_receiver=".$_SESSION['_uid']." AND status=1;";
	$result = api_sql_query($query,__FILE__,__LINE__);
	while ($result_row = mysql_fetch_array($result)) $i++;
	return $i;
}

/**
* Get the list of user_ids of users who are online. 
*/
function users_connected_by_id()
{
	global $statsDbName, $_uid;
	$MINUTE=30;
	$user_connect = WhoIsOnline($_uid,$statsDbName,$MINUTE);
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
	$sql_query = "SELECT COUNT(*) as number_messages FROM `".MESSAGES_DATABASE."` WHERE id_receiver=".$_SESSION['_uid'];
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
	$sql_query = "SELECT id as col0, id_sender as col1, date as col2 FROM `".MESSAGES_DATABASE."` WHERE id_receiver=".$_SESSION['_uid']." ORDER BY col$column $direction LIMIT $from,$number_of_items";
	$sql_result = api_sql_query($sql_query,__FILE__,__LINE__);
	$i = 0;
	$message_list = array ();
	while ($result = mysql_fetch_row($sql_result))
	{
		$message[0] = $result[0];
		$message[1] = GetFullUserName($result[1]);
		$message[1] = "<a href=\"view_message.php?id=".$result[0]."\" title=\"$texto\">".GetFullUserName($result[1])."</a></td>";
		$message[2] = $result[2]; //date stays the same
		$message[3] = '<a href="new_message.php?re_id='.$result[0].'"><img src="'.api_get_path(WEB_IMG_PATH).'forum.gif" alt="'.get_lang("ReplyToMessage").'" align="middle"></img></a>';
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
					$query = "DELETE FROM ".MESSAGES_DATABASE." WHERE id_receiver=".$_SESSION['_uid']." AND id='$message_id'";
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
	$table->set_header(2, get_lang('Date'));
	$table->set_header(3, get_lang("ReplyToMessage"), false);
	$table->set_form_actions(array ('delete' => get_lang('DeleteSelectedMessages')));
	$table->display();
}
?>