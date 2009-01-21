<?php
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004-2008 Dokeos SPRL
	Copyright (c) Julio Montoya <gugli100@gmail.com>

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

include_once(api_get_path(LIBRARY_PATH).'/online.inc.php');
$table_message = Database::get_course_table(TABLE_MESSAGE);

function get_online_user_list($current_user_id)
{
	$min=30;
	global $_configuration;
	$userlist = WhoIsOnline($current_user_id,$_configuration['statistics_database'],$min);
	foreach($userlist as $row) {
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
	$success= get_lang('MessageSentTo').
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
	$MAX_SIZE = 60; // minimun size of chars 
	$i=0;
	$lines = array(); //array with lines
	$token = strtok($texto, "\n");
	while($token) {
		$lines[$i]= $token;
		$token = strtok("\n");
		$i++;
	}
	$modificado= "";
	for($i=0; $i<count($lines); $i++ ) {
		if(strlen($lines[$i])>$MAX_SIZE + 1) {
			$modificado2= substr($lines[$i], 0, $MAX_SIZE);
			for($j=$MAX_SIZE; $j<strlen($lines[$i]); $j+=$MAX_SIZE) {
				$modificado2 = $modificado2."\n".substr($lines[$i], $j, $MAX_SIZE);
			}
		} else {
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
	api_disp_html_area($name, get_lang('TypeYourMessage'), '', '100%');
}

/**
* Get the new messages for the current user from the database.
*/
function get_new_messages()
{
	global $table_message;
	if (!api_get_user_id()) {
		return false;
	}
	$i=0;		
	$query = "SELECT * FROM $table_message WHERE user_receiver_id=".api_get_user_id()." AND msg_status=1;";
	$result = api_sql_query($query,__FILE__,__LINE__);
	$i = Database::num_rows($result);
	return $i;
}

/**
* Get the list of user_ids of users who are online.
*/
function users_connected_by_id()
{
	global $_configuration, $_user;
	$minute=30;
	$user_connect = WhoIsOnline($_user['user_id'],$_configuration['statistics_database'],$minute);
	for ($i=0; $i<count($user_connect); $i++) {
		$user_id_list[$i]=$user_connect[$i][0];
	}
	return $user_id_list;
}

/**
 * Gets the total number of messages, used for the inbox sortable table
 */
function get_number_of_messages()
{
	global $table_message;
	$sql_query = "SELECT COUNT(*) as number_messages FROM $table_message WHERE user_receiver_id=".api_get_user_id();
	$sql_result = api_sql_query($sql_query,__FILE__,__LINE__);
	$result = Database::fetch_array($sql_result);
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
	global $table_message;
	$sql_query = "SELECT id as col0, user_sender_id as col1, title as col2, send_date as col3 FROM $table_message " .
				 "WHERE user_receiver_id=".api_get_user_id()." " .
				 "ORDER BY col$column $direction LIMIT $from,$number_of_items";
	$sql_result = api_sql_query($sql_query,__FILE__,__LINE__);
	$i = 0;
	$message_list = array ();
	while ($result = Database::fetch_row($sql_result)) {
		$message[0] = $result[0];
		$message[1] = GetFullUserName($result[1]);
		//$message[1] = "<a href=\"view_message.php?id=".$result[0]."\" title=\"$texto\">".GetFullUserName($result[1])."</a>";
		$message[2] = '<a href="view_message.php?id='.$result[0].'">'.$result[2].'</a>';
		$message[3] = $result[3]; //date stays the same
		
		$message[4] = '<a href="new_message.php?re_id='.$result[0].'">'.Display::return_icon('message_reply.png',get_lang('ReplyToMessage')).'</a>'.
					  '&nbsp;&nbsp;<a href="inbox.php?action=deleteone&id='.$result[0].'"  onclick="javascript:if(!confirm('."'".addslashes(htmlentities(get_lang('ConfirmDeleteMessage')))."'".')) return false;">'.Display::return_icon('message_delete.png',get_lang('DeleteMessage')).'</a>';
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
	global $table_message;
	//delete messages if delete action was chosen
	if (isset ($_REQUEST['action']))
	{
		switch ($_REQUEST['action']) {
			case 'delete' :
				$number_of_selected_messages = count($_POST['id']);
				foreach ($_POST['id'] as $index => $message_id) {
					delete_message_by_user_receiver(api_get_user_id(), $message_id);	
				}
				Display::display_normal_message(get_lang('SelectedMessagesDeleted'));
				break;
			case 'deleteone' :
				delete_message_by_user_receiver(api_get_user_id(), $_GET['id']);
				Display::display_confirmation_message(get_lang('MessageDeleted'));
				echo '<br / >';	
			break;
		}
	}

	// display sortable table with messages of the current user
	$table = new SortableTable('messages', 'get_number_of_messages', 'get_message_data', 1);
	$table->set_header(0, '', false);
	$table->set_header(1, get_lang('From'));
	$table->set_header(2, get_lang('Title'));
	$table->set_header(3, get_lang('Date'));
	$table->set_header(4, get_lang('Actions'), false);
	$table->set_form_actions(array ('delete' => get_lang('DeleteSelectedMessages')));
	$table->display();
}

function send_message($receiver_user_id, $title, $content)
{
	global $table_message;
	$id_tmp = api_get_user_id().$receiver_user_id.date('d-D-w-m-Y-H-s').microtime().rand();
	$id_msg = md5($id_tmp);
	$query = "INSERT INTO $table_message ( id, user_sender_id, user_receiver_id, msg_status, send_date, title, content ) ".
			 " VALUES (".
	 		 "' ".$id_msg ."' , '".api_get_user_id()."', '".Database::escape_string($receiver_user_id)."', '1', '".date('Y-m-d H:i:s')."','".Database::escape_string($title)."','".Database::escape_string($content)."'".
	 		 ");";
	$result = api_sql_query($query,__FILE__,__LINE__);
	return $result;	
}

function delete_message_by_user_receiver($user_receiver_id,$id)
{
	global $table_message;
	$query = "DELETE FROM $table_message " .
			 "WHERE user_receiver_id=".Database::escape_string($user_receiver_id)." AND id=".Database::escape_string($id);
	$result = api_sql_query($query,__FILE__,__LINE__);
	return $result;	
}

?>