<?php
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004-2009 Dokeos SPRL
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
include_once(api_get_path(LIBRARY_PATH).'/main_api.lib.php');
include_once(api_get_path(LIBRARY_PATH).'/online.inc.php');
class MessageManager {	
	function MessageManager() {
		
	}
	public static function get_online_user_list($current_user_id) {
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
	public static function display_success_message($uid) {
			global $charset;
		if ($_SESSION['social_exist']===true) {
			$redirect="#remote-tab-2";	
			if (api_get_setting('allow_social_tool')=='true' && api_get_setting('allow_message_tool')=='true') {
				$success=get_lang('MessageSentTo').
				"&nbsp;<b>".
				GetFullUserName($uid).
				"</b>".
				"<br><a href=\"".
				"../social/index.php$redirect\">".
				get_lang('BackToInbox').
				"</a>";
			}else {
				$success= get_lang('MessageSentTo').
				"&nbsp;<b>".
				GetFullUserName($uid).
				"</b>".
				"<br><a href=\"".
				"../social/index.php$redirect\">".
				get_lang('BackToInbox').
				"</a>";				
			}
				
		} else {
			$success= get_lang('MessageSentTo').
				"&nbsp;<b>".
				GetFullUserName($uid).
				"</b>".
				"<br><a href=\"".
				"inbox.php\">".
				get_lang('BackToInbox').
				"</a>";
		}
		Display::display_confirmation_message(api_xml_http_response_encode($success), false);
	}
	
	/**
	* Displays the wysiwyg html editor.
	*/
	public static function display_html_editor_area($name,$resp) {
		api_disp_html_area($name, get_lang('TypeYourMessage'), '', '100%');
	}
	
	/**
	* Get the new messages for the current user from the database.
	*/
	public static function get_new_messages() {
		$table_message = Database::get_main_table(TABLE_MESSAGE); 
		if (!api_get_user_id()) {
			return false;
		}
		$i=0;		
		$query = "SELECT * FROM $table_message WHERE user_receiver_id=".api_get_user_id()." AND msg_status=1";
		$result = api_sql_query($query,__FILE__,__LINE__);
		$i = Database::num_rows($result);
		return $i;
	}
	
	/**
	* Get the list of user_ids of users who are online.
	*/
	public static function users_connected_by_id() {
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
	public static function get_number_of_messages () {
		$table_message = Database::get_main_table(TABLE_MESSAGE); 
		$sql_query = "SELECT COUNT(*) as number_messages FROM $table_message WHERE msg_status IN (0,1) AND user_receiver_id=".api_get_user_id();
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
	public static function get_message_data ($from, $number_of_items, $column, $direction) {
		global $charset;
		$table_message = Database::get_main_table(TABLE_MESSAGE); 
		$request=api_is_xml_http_request();
		$sql_query = "SELECT id as col0, user_sender_id as col1, title as col2, send_date as col3, msg_status as col4 FROM $table_message " .
					 "WHERE user_receiver_id=".api_get_user_id()." AND msg_status IN (0,1)" .
					 "ORDER BY send_date desc, col$column $direction LIMIT $from,$number_of_items";
		$sql_result = api_sql_query($sql_query,__FILE__,__LINE__);
		$i = 0;
		$message_list = array ();
		while ($result = Database::fetch_row($sql_result)) {
			if ($request===true) {
				$message[0] = '<input type="checkbox" value='.$result[0].' name="id[]">';		
			 } else {
				$message[0] = ($result[0]);	 	
			 }
			 
			if ($request===true) {
				if($result[4]==0)
            	{ 
					$message[1] = Display::return_icon('mail_open.png',get_lang('AlreadyReadMessage'));//Message already read
				}
				else
				{
					$message[1] = Display::return_icon('mail.png',get_lang('UnReadMessage'));//Message without reading 
				}
						
				$message[2] = '<a onclick="get_action_url_and_show_messages(1,'.$result[0].')" href="javascript:void(0)">'.GetFullUserName($result[1]).'</a>';
				$message[3] = '<a onclick="get_action_url_and_show_messages(1,'.$result[0].')" href="javascript:void(0)">'.str_replace("\\","",$result[2]).'</a>';
				$message[5] = '<a onclick="reply_to_messages(\'show\','.$result[0].',\'\')" href="javascript:void(0)">'.Display::return_icon('message_reply.png',get_lang('ReplyToMessage')).'</a>'.
						  '&nbsp;&nbsp;<a onclick="delete_one_message('.$result[0].')" href="javascript:void(0)"  >'.Display::return_icon('message_delete.png',get_lang('DeleteMessage')).'</a>';
			} else {
				$message[2] = '<a href="view_message.php?id='.$result[0].'">'.GetFullUserName(($result[1])).'</a>';;
				$message[3] = '<a href="view_message.php?id='.$result[0].'">'.$result[2].'</a>';
				$message[5] = '<a href="new_message.php?re_id='.$result[0].'">'.Display::return_icon('message_reply.png',get_lang('ReplyToMessage')).'</a>'.
						  '&nbsp;&nbsp;<a delete_one_message('.$result[0].') href="#inbox.php?action=deleteone&id='.$result[0].'">'.Display::return_icon('message_delete.png',get_lang('DeleteMessage')).'</a>';	
			}
			$message[4] = ($result[3]); //date stays the same
			foreach($message as $key => $value) {
				$message[$key] = api_xml_http_response_encode($value);
			}
			$message_list[] = $message;
			
			$i++;
		}
		return $message_list;
	}
	
	 public static function send_message ($receiver_user_id, $title, $content) {
        global $charset;
		$table_message = Database::get_main_table(TABLE_MESSAGE);
        $title = mb_convert_encoding($title,$charset,'UTF-8');
        $content = mb_convert_encoding($content,$charset,'UTF-8');
		//message in inbox
		$sql = "SELECT COUNT(*) as count FROM $table_message WHERE user_sender_id = ".api_get_user_id()." AND user_receiver_id='".Database::escape_string($receiver_user_id)."' AND title = '".Database::escape_string($title)."' AND content ='".Database::escape_string($content)."' ";
		$res_exist = api_sql_query($sql,__FILE__,__LINE__);
		$row_exist = Database::fetch_array($res_exist,'ASSOC');
		if ($row_exist['count'] ==0) {  
			$query = "INSERT INTO $table_message(user_sender_id, user_receiver_id, msg_status, send_date, title, content ) ".
					 " VALUES (".
			 		 "'".api_get_user_id()."', '".Database::escape_string($receiver_user_id)."', '1', '".date('Y-m-d H:i:s')."','".Database::escape_string($title)."','".Database::escape_string($content)."'".
			 		 ")";
			//message in outbox
			$sql = "INSERT INTO $table_message(user_sender_id, user_receiver_id, msg_status, send_date, title, content ) ".
					 " VALUES (".
			 		 "'".api_get_user_id()."', '".Database::escape_string($receiver_user_id)."', '4', '".date('Y-m-d H:i:s')."','".Database::escape_string($title)."','".Database::escape_string($content)."'".
			 		 ")";
			$rs = api_sql_query($sql,__FILE__,__LINE__);
			$result = api_sql_query($query,__FILE__,__LINE__);
			return $result;
		}
		return false;
	}
	
	 public static function delete_message_by_user_receiver ($user_receiver_id,$id) {	
		$table_message = Database::get_main_table(TABLE_MESSAGE); 
		$sql="SELECT COUNT(*) as count FROM $table_message WHERE id=".$id." AND msg_status<>4;";
		$rs=api_sql_query($sql,__FILE__,__LINE__);
		$row=Database::fetch_array($rs,'ASSOC');
		if ($row['count']==1) {
			$query = "DELETE FROM $table_message " .
			"WHERE user_receiver_id=".Database::escape_string($user_receiver_id)." AND id=".Database::escape_string($id);
			$result = api_sql_query($query,__FILE__,__LINE__);
			return $result;	
		} else {
			return false;
		}
	}
	/**
	 * Set status deleted 
	 * @author Isaac FLores Paz <isaac.flores@dokeos.com>
	 * @param  integer
	 * @param  integer
	 * @return array
	 */
	public static function delete_message_by_user_sender ($user_sender_id,$id) {
		$table_message = Database::get_main_table(TABLE_MESSAGE); 
		$query = "DELETE FROM $table_message " .
				 "WHERE user_sender_id=".Database::escape_string($user_sender_id)." AND id=".Database::escape_string($id);
		
		$result = api_sql_query($query,__FILE__,__LINE__);
		return $result;		
	}
	public static function update_message ($user_id, $id) {
		$table_message = Database::get_main_table(TABLE_MESSAGE); 
		$query = "UPDATE $table_message SET msg_status = '0' WHERE msg_status<>4 AND user_receiver_id=".Database::escape_string($user_id)." AND id='".Database::escape_string($id)."'";
		$result = api_sql_query($query,__FILE__,__LINE__);	
	}
	
	 public static function get_message_by_user ($user_id,$id) {
		$table_message = Database::get_main_table(TABLE_MESSAGE); 
		$query = "SELECT * FROM $table_message WHERE user_receiver_id=".Database::escape_string($user_id)." AND id='".Database::escape_string($id)."'";
		$result = api_sql_query($query,__FILE__,__LINE__);
		return $row = Database::fetch_array($result);
	}
	/**
	 * Gets information about if exist messages
	 * @author Isaac FLores Paz <isaac.flores@dokeos.com>
	 * @param  integer
	 * @param  integer
	 * @return boolean
	 */
	 public static function exist_message ($user_id, $id) {
		$table_message = Database::get_main_table(TABLE_MESSAGE); 
		$query = "SELECT id FROM $table_message WHERE user_receiver_id=".Database::escape_string($user_id)." AND id='".Database::escape_string($id)."'";
		$result = api_sql_query($query,__FILE__,__LINE__);
		$num = Database::num_rows($result);
		if ($num>0)
			return true;
		else
			return false;	
	}
	/**
	 * Gets information about messages sent
	 * @author Isaac FLores Paz <isaac.flores@dokeos.com>
	 * @param  integer
	 * @param  integer
	 * @param  string
	 * @return array
	 */
	 public static function get_message_data_sent ($from, $number_of_items, $column, $direction) {
	 	global $charset;
		$table_message = Database::get_main_table(TABLE_MESSAGE); 
		$request=api_is_xml_http_request();
		$sql_query = "SELECT id as col0, user_sender_id as col1, title as col2, send_date as col3, user_receiver_id as col4, msg_status as col5 FROM $table_message " .
					 "WHERE user_sender_id=".api_get_user_id()." AND msg_status=4 " .
					 "ORDER BY col$column $direction LIMIT $from,$number_of_items";
		$sql_result = api_sql_query($sql_query,__FILE__,__LINE__);
		$i = 0;
		$message_list = array ();
		while ($result = Database::fetch_row($sql_result)) {
			if ($request===true) {
				$message[0] = '<input type="checkbox" value='.$result[0].' name="out[]">';		
			 } else {
				$message[0] = ($result[0]);	 	
			 }
			 
			if ($request===true) {
			   if ($result[5]==4)
			   {
			   		$message[1] = Display::return_icon('mail_send.png',get_lang('MessageSent'));//Message Sent
			   }
				$message[2] = '<a onclick="show_sent_message('.$result[0].')" href="javascript:void(0)">'.GetFullUserName($result[4]).'</a>';
				$message[3] = '<a onclick="show_sent_message('.$result[0].')" href="javascript:void(0)">'.str_replace("\\","",$result[2]).'</a>';
				$message[5] = '&nbsp;&nbsp;<a onclick="delete_one_message_outbox('.$result[0].')" href="javascript:void(0)"  >'.Display::return_icon('message_delete.png',get_lang('DeleteMessage')).'</a>';
			} else {
				$message[2] = '<a onclick="show_sent_message ('.$result[0].')" href="#../messages/view_message.php?id_send='.$result[0].'">'.GetFullUserName($result[4]).'</a>';
				$message[3] = '<a onclick="show_sent_message ('.$result[0].')" href="#../messages/view_message.php?id_send='.$result[0].'">'.$result[2].'</a>';
				$message[5] = '<a href="new_message.php?re_id='.$result[0].'">'.Display::return_icon('message_reply.png',get_lang('ReplyToMessage')).'</a>'.
						  '&nbsp;&nbsp;<a href="outbox.php?action=deleteone&id='.$result[0].'"  onclick="javascript:if(!confirm('."'".addslashes(htmlentities(get_lang('ConfirmDeleteMessage')))."'".')) return false;">'.Display::return_icon('message_delete.png',get_lang('DeleteMessage')).'</a>';
			}
			$message[4] = $result[3]; //date stays the same
			foreach($message as $key => $value) {
				$message[$key] = api_xml_http_response_encode($value);
			}
			$message_list[] = $message;
			$i++;
		}
		return $message_list;
	}
	/**
	 * Gets information about number messages sent
	 * @author Isaac FLores Paz <isaac.flores@dokeos.com>
	 * @param void
	 * @return integer
	 */
	 public static function get_number_of_messages_sent () {
		$table_message = Database::get_main_table(TABLE_MESSAGE); 
		$sql_query = "SELECT COUNT(*) as number_messages FROM $table_message WHERE msg_status=4 AND user_sender_id=".api_get_user_id();
		$sql_result = api_sql_query($sql_query,__FILE__,__LINE__);
		$result = Database::fetch_array($sql_result);
		return $result['number_messages'];
	}
	public static function show_message_box () {
		global $charset;
		$table_message = Database::get_main_table(TABLE_MESSAGE);
		if (isset($_GET['id_send'])) {
			$query = "SELECT * FROM $table_message WHERE user_sender_id=".api_get_user_id()." AND id=".$_GET['id_send']." AND msg_status=4;";
			$result = api_sql_query($query,__FILE__,__LINE__);
		    $path='outbox.php';
		} else {
			$query = "UPDATE $table_message SET msg_status = '0' WHERE user_receiver_id=".api_get_user_id()." AND id='".Database::escape_string($_GET['id'])."';";
			$result = api_sql_query($query,__FILE__,__LINE__);
			$query = "SELECT * FROM $table_message WHERE msg_status<>4 AND user_receiver_id=".api_get_user_id()." AND id='".Database::escape_string($_GET['id'])."';";
			$result = api_sql_query($query,__FILE__,__LINE__);
			$path='inbox.php';
		}
		$row = Database::fetch_array($result);
		$user_con = self::users_connected_by_id();
		$band=0;
		$reply='';
		for ($i=0;$i<count($user_con);$i++)
			if ($row[1]==$user_con[$i])
				$band=1;	
		if ($band==1 && !isset($_GET['id_send'])) {
			$reply = '<a onclick="reply_to_messages(\'show\','.$_GET['id'].',\'\')" href="javascript:void(0)">'.Display::return_icon('message_reply.png',api_xml_http_response_encode(get_lang('ReplyToMessage'))).api_xml_http_response_encode(get_lang('ReplyToMessage')).'</a>';
		}
		echo '<div class=actions>';
		echo '<a onclick="close_div_show(\'div_content_messages\')" href="javascript:void(0)">'.Display::return_icon('folder_up.gif',api_xml_http_response_encode(get_lang('BackToInbox'))).api_xml_http_response_encode(get_lang('BackToInbox')).'</a>';
		echo $reply; 
		echo '<a onclick="delete_one_message('.$row[0].')" href="javascript:void(0)"  >'.Display::return_icon('message_delete.png',api_xml_http_response_encode(get_lang('DeleteMessage'))).''.api_xml_http_response_encode(get_lang('DeleteMessage')).'</a>';
		echo '</div><br />';
		echo '
		<table class="message_view_table" >
		    <TR>
		      <TD width=10>&nbsp; </TD>
		      <TD vAlign=top width="100%">
		      	<TABLE>      
		            <TR>
		              <TD width="100%">                              
		                    <TR> <h1>'.str_replace("\\","",api_xml_http_response_encode($row[5])).'</h1></TR>
		              </TD>              		
		              <TR>                       
		              	<TD>'.api_xml_http_response_encode(get_lang('From').'&nbsp;<b>'.GetFullUserName($row[1]).'</b> '.api_strtolower(get_lang('To')).'&nbsp;  <b>'.GetFullUserName($row[2])).'</b> </TD>
		              </TR>                    
		              <TR>
		              <TD >'.api_xml_http_response_encode(get_lang('Date').'&nbsp; '.$row[4]).'</TD>                      
		              </TR>              
		            </TR>          
		        </TABLE>	      		
		        <br />
		        <TABLE height=209 width="100%" bgColor=#ffffff>
		          <TBODY>
		            <TR>
		              <TD vAlign=top>'.str_replace("\\","",api_xml_http_response_encode($row[6])).'</TD>
		            </TR>
		          </TBODY>
		        </TABLE>
		        <DIV class=HT style="PADDING-BOTTOM: 5px"> </DIV></TD>
		      <TD width=10>&nbsp;</TD>
		    </TR>
		</TABLE>';
	}
	public static function show_message_box_sent () {
		global $charset;
		$table_message = Database::get_main_table(TABLE_MESSAGE);
		$query = "SELECT * FROM $table_message WHERE user_sender_id=".api_get_user_id()." AND id=".$_GET['id_send']." AND msg_status=4;";
		$result = api_sql_query($query,__FILE__,__LINE__);
		$path='outbox.php';
		
		$row = Database::fetch_array($result);
		$user_con = self::users_connected_by_id();
		$band=0;
		$reply='';
		for ($i=0;$i<count($user_con);$i++)
			if ($row[1]==$user_con[$i])
				$band=1;	
		echo '<div class=actions>';
		echo '<a onclick="close_and_open_outbox()" href="javascript:void(0)">'.Display::return_icon('folder_up.gif',api_xml_http_response_encode(get_lang('BackToOutbox'))).api_xml_http_response_encode(get_lang('BackToOutbox')).'</a>';
		echo '<a onclick="delete_one_message_outbox('.$row[0].')" href="javascript:void(0)"  >'.Display::return_icon('message_delete.png',api_xml_http_response_encode(get_lang('DeleteMessage'))).api_xml_http_response_encode(get_lang('DeleteMessage')).'</a>';
		echo '</div><br />';
		echo '
		<table class="message_view_table" >
		    <TR>
		      <TD width=10>&nbsp; </TD>
		      <TD vAlign=top width="100%">
		      	<TABLE>      
		            <TR>
		              <TD width="100%">                              
		                    <TR> <h1>'.str_replace("\\","",api_xml_http_response_encode($row[5])).'</h1></TR>
		              </TD>              		
		              <TR>                       
		              	<TD>'.api_xml_http_response_encode(get_lang('From').'&nbsp;<b>'.GetFullUserName($row[1]).'</b> '.api_strtolower(get_lang('To')).'&nbsp;  <b>'.GetFullUserName($row[2])).'</b> </TD>
		              </TR>                    
		              <TR>
		              <TD >'.api_xml_http_response_encode(get_lang('Date').'&nbsp; '.$row[4]).'</TD>                      
		              </TR>              
		            </TR>          
		        </TABLE>	      		
		        <br />
		        <TABLE height=209 width="100%" bgColor=#ffffff>
		          <TBODY>
		            <TR>
		              <TD vAlign=top>'.str_replace("\\","",api_xml_http_response_encode($row[6])).'</TD>
		            </TR>
		          </TBODY>
		        </TABLE>
		        <DIV class=HT style="PADDING-BOTTOM: 5px"> </DIV></TD>
		      <TD width=10>&nbsp;</TD>
		    </TR>
		</TABLE>';
	}
	/**
	 * get user id by user email
	 * @param string $user_email
	 * @return int user id
	 */
	public static function get_user_id_by_email ($user_email) {
		$tbl_user = Database::get_main_table(TABLE_MAIN_USER);
		$sql='SELECT user_id FROM '.$tbl_user.' WHERE email="'.Database::escape_string($user_email).'";';
		$rs=api_sql_query($sql,__FILE__,__LINE__);
		$row=Database::fetch_array($rs,'ASSOC');
		if (isset($row['user_id'])) {
			return $row['user_id'];
		} else {
			return null;
		}
	}
}
?>
