<?php
/* For licensing terms, see /dokeos_license.txt */

/**
==============================================================================
*	This class provides methods for messages management.
*	Include/require it in your code to use its features.
*
*	@package dokeos.library
==============================================================================
*/


require_once api_get_path(LIBRARY_PATH).'online.inc.php';
require_once api_get_path(LIBRARY_PATH).'fileUpload.lib.php';
require_once api_get_path(LIBRARY_PATH).'fileDisplay.lib.php';
require_once api_get_path(LIBRARY_PATH).'usermanager.lib.php';
require_once api_get_path(LIBRARY_PATH).'group_portal_manager.lib.php';

/* 
 * @todo use constants!
 */
define('MESSAGE_STATUS_NEW',				'0');
define('MESSAGE_STATUS_UNREAD',				'1');
define('MESSAGE_STATUS_DELETED',			'2');

define('MESSAGE_STATUS_INVITATION_PENDING',	'5');
define('MESSAGE_STATUS_INVITATION_ACCEPTED','6');
define('MESSAGE_STATUS_INVITATION_DENIED',	'7');

class MessageManager
{
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
				"</b>";
			}else {
				$success=get_lang('MessageSentTo').
				"&nbsp;<b>".
				GetFullUserName($uid).
				"</b>";
			}
		} else {
				$success=get_lang('MessageSentTo').
				"&nbsp;<b>".
				GetFullUserName($uid).
				"</b>";
		}
		Display::display_confirmation_message(api_xml_http_response_encode($success), false);
	}

	/**
	* Displays the wysiwyg html editor.
	*/
	public static function display_html_editor_area($name, $resp) {
		api_disp_html_area($name, get_lang('TypeYourMessage'), '', '', null, array('ToolbarSet' => 'Messages', 'Width' => '95%', 'Height' => '250'));
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
		$result = Database::query($query,__FILE__,__LINE__);
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
		$sql_result = Database::query($sql_query,__FILE__,__LINE__);
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
		$from = intval($from);
		$number_of_items = intval($number_of_items);
		$column = intval($column);
		if (!in_array($direction, array('ASC', 'DESC')))
			$direction = 'ASC';

		$table_message = Database::get_main_table(TABLE_MESSAGE);
		$request=api_is_xml_http_request();
		$sql_query = "SELECT id as col0, user_sender_id as col1, title as col2, send_date as col3, msg_status as col4 FROM $table_message " .
					 "WHERE user_receiver_id=".api_get_user_id()." AND msg_status IN (0,1)" .
					 "ORDER BY send_date desc, col$column $direction LIMIT $from,$number_of_items";
		$sql_result = Database::query($sql_query,__FILE__,__LINE__);
		$i = 0;
		$message_list = array ();
		while ($result = Database::fetch_row($sql_result)) {
			
			if ($request===true) {
				$message[0] = '<input type="checkbox" value='.$result[0].' name="id[]">';
			 } else {
				$message[0] = ($result[0]);
			 }

			if ($request===true) {
				if($result[4]==0) {
					$message[1] = Display::return_icon('mail_open.png',get_lang('AlreadyReadMessage'));//Message already read
				} else {
					$message[1] = Display::return_icon('mail.png',get_lang('UnReadMessage'));//Message without reading
				}

				$message[2] = '<a onclick="get_action_url_and_show_messages(1,'.$result[0].')" href="javascript:void(0)">'.GetFullUserName($result[1]).'</a>';
				$message[3] = '<a onclick="get_action_url_and_show_messages(1,'.$result[0].')" href="javascript:void(0)">'.str_replace("\\","",$result[2]).'</a>';
				$message[5] = '<a onclick="reply_to_messages(\'show\','.$result[0].',\'\')" href="javascript:void(0)">'.Display::return_icon('message_reply.png',get_lang('ReplyToMessage')).'</a>'.
						  '&nbsp;&nbsp;<a onclick="delete_one_message('.$result[0].')" href="javascript:void(0)"  >'.Display::return_icon('message_delete.png',get_lang('DeleteMessage')).'</a>';
			} else {
				if($result[4]==1) {
					$class = 'class = "unread"';
				} else {
					$class = 'class = "read"';
				}
				$message[2] = '<a '.$class.' href="view_message.php?id='.$result[0].'">'.GetFullUserName(($result[1])).'</a>';;
				$message[3] = '<a '.$class.' href="view_message.php?id='.$result[0].'">'.$result[2].'</a>';
				$message[5] = '<a href="new_message.php?re_id='.$result[0].'">'.Display::return_icon('message_reply.png',get_lang('ReplyToMessage')).'</a>'.
						  '&nbsp;&nbsp;<a delete_one_message('.$result[0].') href="inbox.php?action=deleteone&id='.$result[0].'">'.Display::return_icon('message_delete.png',get_lang('DeleteMessage')).'</a>';
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

	public static function send_message ($receiver_user_id, $title, $content, $file_attachments = array(), $file_comments = '', $group_id = 0, $parent_id = 0) {	
        global $charset;
		$table_message = Database::get_main_table(TABLE_MESSAGE);
		$group_id = intval($group_id);
        $receiver_user_id = intval($receiver_user_id);
        $parent_id = intval($parent_id);
		$user_sender_id = api_get_user_id();

        if (!empty($receiver_user_id) || !empty($group_id)) {
        	// message for user friend
	        $title = api_convert_encoding($title,$charset,'UTF-8');
	        $title = Database::escape_string($title);
	        $content = api_convert_encoding($content,$charset,'UTF-8');
	        $content = Database::escape_string($content);
			//message in inbox
			$sql = "SELECT COUNT(*) as count FROM $table_message WHERE user_sender_id = ".$user_sender_id." AND user_receiver_id='$receiver_user_id' AND title = '$title' AND content ='$content' AND group_id = '$group_id' AND parent_id = '$parent_id'";
			$res_exist = Database::query($sql,__FILE__,__LINE__);
			$row_exist = Database::fetch_array($res_exist,'ASSOC');
			if ($row_exist['count'] == 0) {
				//message in outbox for user friend or group
				$sql = "INSERT INTO $table_message(user_sender_id, user_receiver_id, msg_status, send_date, title, content, group_id, parent_id ) ".
						 " VALUES ('$user_sender_id', '$receiver_user_id', '4', '".date('Y-m-d H:i:s')."','$title','$content', '$group_id', '$parent_id')";
				$rs = Database::query($sql,__FILE__,__LINE__);
				$outbox_last_id = Database::insert_id();
				
				// save attachment file for outbox messages
				if (is_array($file_attachments)) {
					$o = 0;
					foreach ($file_attachments as $file_attach) {						
						if ($file_attach['error'] == 0) {
							self::save_message_attachment_file($file_attach,$file_comments[$o],$outbox_last_id,$user_sender_id);
						}
						$o++;
					}
				}
				
				if (!empty($group_id)) {
					//message in inbox for group										
					$users_by_group = GroupPortalManager::get_all_users_by_group($group_id);
					$users_ids = array_keys($users_by_group);	
					if (is_array($users_ids) && count($users_ids) > 0) {				
						foreach ($users_ids as $user_id) {
							$receiver_user_id = $user_id;														 
							$query = "INSERT INTO $table_message(user_sender_id, user_receiver_id, msg_status, send_date, title, content, group_id, parent_id ) ".
									 " VALUES ('$user_sender_id', '$receiver_user_id', '1', '".date('Y-m-d H:i:s')."','$title','$content','$group_id','$parent_id')";
							$result = Database::query($query,__FILE__,__LINE__);				
							$inbox_last_id = Database::insert_id();					
							// save attachment file for inbox messages
							if (is_array($file_attachments)) {
								$i = 0;
								foreach ($file_attachments as $file_attach) {						
									if ($file_attach['error'] == 0) {
										self::save_message_attachment_file($file_attach,$file_comments[$i],$inbox_last_id,null,$receiver_user_id);
									}
									$i++;
								}
							}						
							// update parent id for other receiver users belongs the group						
							if (!empty($parent_id)) {
								self::update_parent_ids_from_reply($parent_id, $receiver_user_id, $inbox_last_id);
							}													
						}
					}
				} else {		
								
					//message in inbox for user friend								 
					$query = "INSERT INTO $table_message(user_sender_id, user_receiver_id, msg_status, send_date, title, content, group_id, parent_id ) ".
							 " VALUES ('$user_sender_id', '$receiver_user_id', '1', '".date('Y-m-d H:i:s')."','$title','$content','$group_id','$parent_id')";
					$result = Database::query($query,__FILE__,__LINE__);				
					$inbox_last_id = Database::insert_id();					
					// save attachment file for inbox messages
					if (is_array($file_attachments)) {
						$i = 0;
						foreach ($file_attachments as $file_attach) {						
							if ($file_attach['error'] == 0) {
								self::save_message_attachment_file($file_attach,$file_comments[$i],$inbox_last_id,null,$receiver_user_id);
							}
							$i++;	
						}
					}					
				}														
				return $result;
			}
        }         
		return false;
	}

	/**
	 * Update parent ids for other receiver user from current message in groups
	 * @author Christian Fasanando Flores 
	 * @param  int	parent id
	 * @param  int	receiver user id
	 * @param  int	message id
	 * @return void
	 */
	public static function update_parent_ids_from_reply($parent_id,$receiver_user_id,$message_id) {
		
		$table_message = Database::get_main_table(TABLE_MESSAGE);
		$parent_id = intval($parent_id);		
		$receiver_user_id = intval($receiver_user_id);
		$message_id = intval($message_id);
		// first get data from message id (parent)
		$sql_message= "SELECT * FROM $table_message WHERE id = '$parent_id'";	
		$rs_message	= Database::query($sql_message,__FILE__,__LINE__);
		$row_message= Database::fetch_array($rs_message);
		
		// get message id from data found early for other receiver user
		$sql_msg_id	= " SELECT id FROM $table_message WHERE user_sender_id ='{$row_message[user_sender_id]}' 
				 		AND title='{$row_message[title]}' AND content='{$row_message[content]}' AND group_id='{$row_message[group_id]}' AND user_receiver_id='$receiver_user_id'";									
		$rs_msg_id	= Database::query($sql_msg_id,__FILE__,__LINE__);
		$row = Database::fetch_array($rs_msg_id);			
		
		// update parent_id for other user receiver
		$sql_upd = "UPDATE $table_message SET parent_id = '{$row[id]}' WHERE id = '$message_id'";		
		Database::query($sql_upd,__FILE__,__LINE__);			
	}
	
	public static function delete_message_by_user_receiver ($user_receiver_id,$id) {
		$table_message = Database::get_main_table(TABLE_MESSAGE);
		if ($id != strval(intval($id))) return false;
		$id = Database::escape_string($id);
		$sql="SELECT * FROM $table_message WHERE id=".$id." AND msg_status<>4;";
		$rs=Database::query($sql,__FILE__,__LINE__);
		
		if (Database::num_rows($rs) > 0 ) {
			$row = Database::fetch_array($rs);
			// delete attachment file
			$res = self::delete_message_attachment_file($id,$user_receiver_id);
			// delete message
			$query = "UPDATE $table_message SET msg_status=3 WHERE user_receiver_id=".Database::escape_string($user_receiver_id)." AND id=".$id;
			//$query = "DELETE FROM $table_message WHERE user_receiver_id=".Database::escape_string($user_receiver_id)." AND id=".$id;
			$result = Database::query($query,__FILE__,__LINE__);			
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
		if ($id != strval(intval($id))) return false;
		$table_message = Database::get_main_table(TABLE_MESSAGE);
		
		$id = intval($id);
		$user_sender_id = intval($user_sender_id);
		
		$sql="SELECT * FROM $table_message WHERE id='$id'";
		$rs=Database::query($sql,__FILE__,__LINE__);
		
		if (Database::num_rows($rs) > 0 ) {
			$row = Database::fetch_array($rs);
			// delete attachment file
			$res = self::delete_message_attachment_file($id,$user_sender_id);
			// delete message
			$query = "UPDATE $table_message SET msg_status=3 WHERE user_sender_id='$user_sender_id' AND id='$id'";
			//$query = "DELETE FROM $table_message WHERE user_sender_id='$user_sender_id' AND id='$id'";
			$result = Database::query($query,__FILE__,__LINE__);					
			return $result;		
		}				
		return false;
	}
	
	public static function save_message_attachment_file($file_attach,$file_comment,$message_id,$receiver_user_id=0,$sender_user_id=0) {

		$tbl_message_attach = Database::get_main_table(TABLE_MESSAGE_ATTACHMENT);

		// Try to add an extension to the file if it hasn't one
		$new_file_name = add_ext_on_mime(stripslashes($file_attach['name']), $file_attach['type']);
		
		// user's file name
		$file_name =$file_attach['name'];
	
		if (!filter_extension($new_file_name))  {
			Display :: display_error_message(get_lang('UplUnableToSaveFileFilteredExtension'));
		} else {
			$new_file_name = uniqid('');						

			$message_user_id = '';
			if (!empty($receiver_user_id)) {
				$message_user_id = $receiver_user_id;
			} else {
				$message_user_id = $sender_user_id;
			}			
			
			// User-reserved directory where photos have to be placed.
			$path_user_info = UserManager::get_user_picture_path_by_id($message_user_id, 'system', true);
			$path_message_attach = $path_user_info['dir'].'message_attachments/';
					
			// If this directory does not exist - we create it.
			if (!file_exists($path_message_attach)) {
				$perm = api_get_setting('permissions_for_new_directories');
				$perm = octdec(!empty($perm) ? $perm : '0770');
				@mkdir($path_message_attach, $perm, true);
			}				
			$new_path=$path_message_attach.$new_file_name;						
			if (is_uploaded_file($file_attach['tmp_name'])) {
				$result= @copy($file_attach['tmp_name'], $new_path);
			}							
			$safe_file_comment= Database::escape_string($file_comment);
			$safe_file_name = Database::escape_string($file_name);
			$safe_new_file_name = Database::escape_string($new_file_name);						
			// Storing the attachments if any			
			$sql="INSERT INTO $tbl_message_attach(filename,comment, path,message_id,size)
				  VALUES ( '$safe_file_name', '$safe_file_comment', '$safe_new_file_name' , '$message_id', '".$file_attach['size']."' )";
			$result=Database::query($sql, __LINE__, __FILE__);
			$message.=' / '.get_lang('FileUploadSucces').'<br />';
	
		}	
	}

	/**
	 * Delete message attachment file (logicaly updating the row with a suffix _DELETE_id)
	 * @param  int		message id
	 * @param  int		message user id (receiver user id or sender user id) 
	 * @return void
	 */
	public static function delete_message_attachment_file($message_id,$message_uid) {

		$message_id = intval($message_id);		
		$message_uid = intval($message_uid);
		$table_message_attach = Database::get_main_table(TABLE_MESSAGE_ATTACHMENT);
		
		$sql= "SELECT * FROM $table_message_attach WHERE message_id = '$message_id'";
		$rs	= Database::query($sql,__FILE__,__LINE__);		
		$new_paths = array();
		while ($row = Database::fetch_array($rs)) {
			$path 		= $row['path'];
			$attach_id  = $row['id'];			
			$new_path 	= $path.'_DELETED_'.$attach_id;
			$path_user_info = UserManager::get_user_picture_path_by_id($message_uid, 'system', true);
			$path_message_attach = $path_user_info['dir'].'message_attachments/';					
			if (is_file($path_message_attach.$path)) {				
				if(rename($path_message_attach.$path, $path_message_attach.$new_path)) {					
					$sql_upd = "UPDATE $table_message_attach set path='$new_path' WHERE id ='$attach_id'";
					$rs_upd = Database::query($sql_upd,__FILE__,__LINE__);					
				} 							
			}						
		}				
	}	
	
	public static function update_message ($user_id, $id) {
		if ($id != strval(intval($id)) || $user_id != strval(intval($user_id))) return false;
		$table_message = Database::get_main_table(TABLE_MESSAGE);
		$query = "UPDATE $table_message SET msg_status = '0' WHERE msg_status<>4 AND user_receiver_id=".Database::escape_string($user_id)." AND id='".Database::escape_string($id)."'";
		$result = Database::query($query,__FILE__,__LINE__);
	}

	 public static function get_message_by_user ($user_id,$id) {
	 	if ($id != strval(intval($id)) || $user_id != strval(intval($user_id))) return false;
		$table_message = Database::get_main_table(TABLE_MESSAGE);
		$query = "SELECT * FROM $table_message WHERE user_receiver_id=".Database::escape_string($user_id)." AND id='".Database::escape_string($id)."'";
		$result = Database::query($query,__FILE__,__LINE__);
		return $row = Database::fetch_array($result);
	}
	
	public static function get_messages_by_group($group_id) {	 	
		if ($group_id != strval(intval($group_id))) return false;		
	 	$table_message = Database::get_main_table(TABLE_MESSAGE);
	 	$current_uid = api_get_user_id();
	 	$group_id = intval($group_id);	 			
		$query = "SELECT * FROM $table_message WHERE group_id='$group_id' AND msg_status <> 4 AND user_receiver_id = '$current_uid' ORDER BY id";		
		$rs = Database::query($query,__FILE__,__LINE__);		
		$data = array();
		if (Database::num_rows($rs) > 0) {
			while ($row = Database::fetch_array($rs)) {
				$data[] = $row;
			}		
		}
		return $data;
	}
	
	/**
	 * Gets information about if exist messages
	 * @author Isaac FLores Paz <isaac.flores@dokeos.com>
	 * @param  integer
	 * @param  integer
	 * @return boolean
	 */
	 public static function exist_message ($user_id, $id) {
	 	if ($id != strval(intval($id)) || $user_id != strval(intval($user_id))) return false;
		$table_message = Database::get_main_table(TABLE_MESSAGE);
		$query = "SELECT id FROM $table_message WHERE user_receiver_id=".Database::escape_string($user_id)." AND id='".Database::escape_string($id)."'";
		$result = Database::query($query,__FILE__,__LINE__);
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

	 	$from = intval($from);
		$number_of_items = intval($number_of_items);
		$column = intval($column);
		if (!in_array($direction, array('ASC', 'DESC')))
			$direction = 'ASC';

		$table_message = Database::get_main_table(TABLE_MESSAGE);
		$request=api_is_xml_http_request();
		$sql_query = "SELECT id as col0, user_sender_id as col1, title as col2, send_date as col3, user_receiver_id as col4, msg_status as col5 FROM $table_message " .
					 "WHERE user_sender_id=".api_get_user_id()." AND msg_status=4 " .
					 "ORDER BY col$column $direction LIMIT $from,$number_of_items";

		$sql_result = Database::query($sql_query,__FILE__,__LINE__);
		$i = 0;
		$message_list = array ();
		while ($result = Database::fetch_row($sql_result)) {
			if ($request===true) {
				$message[0] = '<input type="checkbox" value='.$result[0].' name="out[]">';
			 } else {
				$message[0] = ($result[0]);
			 }
			 
			$class = 'class = "read"';
			
			if ($request===true) {
			   if ($result[5]==4) {
			   		$message[1] = Display::return_icon('mail_send.png',get_lang('MessageSent'));//Message Sent
			   }
				$message[2] = '<a onclick="show_sent_message('.$result[0].')" href="javascript:void(0)">'.GetFullUserName($result[4]).'</a>';
				$message[3] = '<a onclick="show_sent_message('.$result[0].')" href="javascript:void(0)">'.str_replace("\\","",$result[2]).'</a>';
				$message[5] = '&nbsp;&nbsp;<a onclick="delete_one_message_outbox('.$result[0].')" href="javascript:void(0)"  >'.Display::return_icon('message_delete.png',get_lang('DeleteMessage')).'</a>';
			} else {
				$message[2] = '<a '.$class.' onclick="show_sent_message ('.$result[0].')" href="../messages/view_message.php?id_send='.$result[0].'">'.GetFullUserName($result[4]).'</a>';
				$message[3] = '<a '.$class.' onclick="show_sent_message ('.$result[0].')" href="../messages/view_message.php?id_send='.$result[0].'">'.$result[2].'</a>';
				$message[5] = '<a '.$class.' href="new_message.php?re_id='.$result[0].'">'.Display::return_icon('message_reply.png',get_lang('ReplyToMessage')).'</a>'.
						  '&nbsp;&nbsp;<a href="outbox.php?action=deleteone&id='.$result[0].'"  onclick="javascript:if(!confirm('."'".addslashes(api_htmlentities(get_lang('ConfirmDeleteMessage')))."'".')) return false;">'.Display::return_icon('message_delete.png',get_lang('DeleteMessage')).'</a>';
			}
			$message[4] = $result[3]; //date stays the same
			foreach($message as $key => $value) {
				$message[$key] = $value;
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
		$sql_result = Database::query($sql_query,__FILE__,__LINE__);
		$result = Database::fetch_array($sql_result);
		return $result['number_messages'];
	}
	
	/**
	 * display message box in the inbox 
	 * @param int the message id
	 * @param string inbox or outbox strings are available
	 * @return string html with the message content
	 */
	public static function show_message_box($message_id, $source = 'inbox') {
		$table_message 		= Database::get_main_table(TABLE_MESSAGE);
		$tbl_message_attach = Database::get_main_table(TABLE_MESSAGE_ATTACHMENT);
		$message_id = intval($message_id);	
		
		if ($source == 'outbox') {
			if (isset($message_id) && is_numeric($message_id)) {
				$query = "SELECT * FROM $table_message WHERE user_sender_id=".api_get_user_id()." AND id=".$message_id." AND msg_status=4;";
				$result = Database::query($query,__FILE__,__LINE__);
			    $path='outbox.php';		    
			}
		} else {	
			if (is_numeric($message_id) && !empty($message_id)) {
				$query = "UPDATE $table_message SET msg_status = '".MESSAGE_STATUS_NEW."' WHERE user_receiver_id=".api_get_user_id()." AND id='".$message_id."';";
				$result = Database::query($query,__FILE__,__LINE__);
				
				$query = "SELECT * FROM $table_message WHERE msg_status<>4 AND user_receiver_id=".api_get_user_id()." AND id='".$message_id."';";
				$result = Database::query($query,__FILE__,__LINE__);
			}						
			$path='inbox.php';			
		}

		$row = Database::fetch_array($result);
		
		// get file attachments by message id
		$files_attachments = self::get_links_message_attachment_files($message_id);
		
		$user_con = self::users_connected_by_id();
		$band=0;
		$reply='';
		for ($i=0;$i<count($user_con);$i++)
			if ($row[1]==$user_con[$i])
				$band=1;

		$message_content =  '
		<table class="message_view_table" >
		    <TR>
		      <TD width=10>&nbsp; </TD>
		      <TD vAlign=top width="100%">
		      	<TABLE>
		            <TR>
		              <TD width="100%">
		               <h1>'.str_replace("\\","",$row[5]).'</h1>
		              </TD>
		              <TR>';
			if (api_get_setting('allow_social_tool') == 'true') {
				$user_image = '';
				/*	@todo add user image
				$user_image = UserManager::get_user_picture_path_by_id($row[1],'web', true,false);				
				$user_image = UserManager::get_picture_user($row[1], $user_image['file'],'40');
				$user_image = '<img src="'.$user_image['file'].'" style="'.$user_image['style'].'" >';
				*/
				$message_content .='<TD>'.get_lang('From').' '.$user_image.'<a href="'.api_get_path(WEB_PATH).'main/social/profile.php?u='.$row[1].'">'.GetFullUserName($row[1]).'</a> '.api_strtolower(get_lang('To')).'&nbsp;<b>'.get_lang('Me').'</b> </TD>';
				
			} else {
				$message_content .='<TD>'.get_lang('From').'&nbsp;'.GetFullUserName($row[1]).'</b> '.api_strtolower(get_lang('To')).' <b>'.get_lang('Me').'</b> </TD>';
			}
		
		 $message_content .='</TR>
		              <TR>
		              <TD >'.get_lang('Date').'&nbsp; '.$row[4].'</TD>
		              </TR>
		            </TR>
		        </TABLE>
		        <br />
		        <TABLE height=209 width="100%" bgColor=#ffffff>
		          <TBODY>
		            <TR>
		              <TD vAlign=top class="view-message-content">'.str_replace("\\","",$row[6]).'</TD>
		            </TR>
		          </TBODY>
		        </TABLE>
		        <div id="message-attach">'.(!empty($files_attachments)?implode('&nbsp;|&nbsp;',$files_attachments):'').'</div>				        		
		        <DIV class=HT style="PADDING-BOTTOM: 5px">';
		        
		    if ($source == 'outbox') {
		    	$message_content .= '<a href="outbox.php">'.Display::return_icon('back.png',get_lang('ReturnToOutbox')).get_lang('ReturnToOutbox').'</a> &nbsp';
		    } else {
		    	$message_content .= '<a href="inbox.php">'.Display::return_icon('back.png',get_lang('ReturnToInbox')).get_lang('ReturnToInbox').'</a> &nbsp';
		    }
			$message_content .= '<a href="new_message.php?re_id='.$message_id.'">'.Display::return_icon('message_reply.png',get_lang('ReplyToMessage')).get_lang('ReplyToMessage').'</a> &nbsp';
			$message_content .= '<a href="inbox.php?action=deleteone&id='.$message_id.'" >'.Display::return_icon('message_delete.png',get_lang('DeleteMessage')).''.get_lang('DeleteMessage').'</a>&nbsp';

		        
		        	
		$message_content .='</DIV></TD>
		      <TD width=10></TD>
		    </TR>
		</TABLE>';
		return $message_content;
	}
	
	
	/**
	 * display message box sent showing it into outbox 
	 * @return void
	 */
	public static function show_message_box_sent () {		
		global $charset;		
		$table_message = Database::get_main_table(TABLE_MESSAGE);
		$tbl_message_attach = Database::get_main_table(TABLE_MESSAGE_ATTACHMENT);
		
		$message_id = '';
		if (is_numeric($_GET['id_send'])) {
			$query = "SELECT * FROM $table_message WHERE user_sender_id=".api_get_user_id()." AND id=".intval(Database::escape_string($_GET['id_send']))." AND msg_status=4;";
			$result = Database::query($query,__FILE__,__LINE__);
			$message_id = intval($_GET['id_send']);
		}
		$path='outbox.php';

		// get file attachments by message id
		$files_attachments = self::get_links_message_attachment_files($message_id);
	
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
		        <div id="message-attach">'.(!empty($files_attachments)?implode('&nbsp;|&nbsp;',$files_attachments):'').'</div>		
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
		$rs=Database::query($sql,__FILE__,__LINE__);
		$row=Database::fetch_array($rs,'ASSOC');
		if (isset($row['user_id'])) {
			return $row['user_id'];
		} else {
			return null;
		}
	}
	
	/**
	 * Displays messages of a group with nested view 
	 * @param int group id
	 */
	public static function display_messages_for_group($group_id) {
						
		$rows = self::get_messages_by_group($group_id);						
		$rows = self::calculate_children($rows);
		$group_info = GroupPortalManager::get_group_data($group_id);		
		$count=0;
		$html = '';
		if (is_array($rows) && count($rows)> 0) {
			foreach ($rows as $message) {
				$indent	= $message['indent_cnt']*'20';
				$user_sender_info = UserManager::get_user_info_by_id($message['user_sender_id']);
				
				if (!empty($message['parent_id'])) {				
					$message_parent_info = self::get_message_by_id($message['parent_id']);								
					$user_parent_info = UserManager::get_user_info_by_id($message_parent_info['user_sender_id']);
					$name_user_parent = api_get_person_name($user_parent_info['firstname'], $user_parent_info['lastname']);
					
					$message_item = 'message-item';
					$message_title_item = 'message-group-title';
				} else {
					$message_item = 'message-topic';
					$message_title_item = 'message-group-title-topic';		
				}
				
				// get file attachments by message id
				$files_attachments = self::get_links_message_attachment_files($message['id']);
				
				$name = api_get_person_name($user_sender_info['firstname'], $user_sender_info['lastname']);						
				$html.= '<div class="'.$message_item.'" id="message-item-'.$count.'" style="margin-left: '.$indent.'px;">';
					
					//if (!isset($message['children'])) {
					//only for admins
					//$html.= '<div id="message-reply-link"><a href="'.api_get_path(WEB_CODE_PATH).'social/groups.php?action=delete_message&group_id='.$group_id.'&message_id='.$message['id'].'">'.Display :: return_icon('delete.gif', get_lang('Delete')).'&nbsp;'.get_lang('Delete').'</a></div>';
					$html.= '<div id="message-reply-link"><a href="'.api_get_path(WEB_CODE_PATH).'social/message_for_group_form.inc.php?view_panel=1&height=390&width=610&&user_friend='.api_get_user_id().'&group_id='.$group_id.'&message_id='.$message['id'].'" class="thickbox" title="'.get_lang('Reply').'">'.Display :: return_icon('forumthread_new.gif', get_lang('Reply')).'&nbsp;'.get_lang('Reply').'</a></div>';

						//echo '<a href="/main/messages/new_message.php?group_id='.$group_id.'&message_id='.$message['id'].'">'.Display::return_icon('forumthread_new.gif',api_xml_http_response_encode(get_lang('Reply'))).'&nbsp;'.api_xml_http_response_encode(get_lang('Reply')).'</a>';
					//}
					
					$html.= '<div class="'.$message_title_item.'">'.$message['title'].'&nbsp;</div>';
											
					$html.= '<div class="message-group-author">'.get_lang('From').'&nbsp;<a href="'.api_get_path(WEB_PATH).'main/social/profile.php?u='.$message['user_sender_id'].'">'.$name.'&nbsp;</a></div>';		
					$html.= '<div class="message-group-content">'.$message['content'].'</div>';
					$html.= '<div class="message-group-date">'.get_lang('PostIn').' '.date_to_str_ago($message['send_date']).'</div>';
					
					$html.= '<div class="message-attach">'.(!empty($files_attachments)?implode('&nbsp;|&nbsp;',$files_attachments):'').'</div>';
					
				$html.= '</div>';
				$count++;						
			}
		}
		return $html;
	}
	
	/**
	 * Add children to messages by id is used for nested view messages  
	 * @param array  rows of messages 
	 * @return array new list adding the item children
	 */	
	public static function calculate_children($rows) {

		foreach($rows as $row) {
			$rows_with_children[$row["id"]]=$row;
			$rows_with_children[$row["parent_id"]]["children"][]=$row["id"];
		}		
		$rows=$rows_with_children;
		$sorted_rows=array(0=>array());
		self::message_recursive_sort($rows, $sorted_rows);
		unset($sorted_rows[0]);
		return $sorted_rows;
	}
	
	/**
	 * Sort recursively the messages, is used for for nested view messages   
	 * @param array  original rows of messages
	 * @param array  list recursive of messages
	 * @param int   seed for calculate the indent
	 * @param int   indent for nested view 
	 * @return void
	 */	
	public static function message_recursive_sort($rows, &$messages, $seed=0, $indent=0) {
		if($seed>0) {
			$messages[$rows[$seed]["id"]]=$rows[$seed];
			$messages[$rows[$seed]["id"]]["indent_cnt"]=$indent;
			$indent++;
		}	
		if(isset($rows[$seed]["children"])) {
			foreach($rows[$seed]["children"] as $child) {
				self::message_recursive_sort($rows, $messages, $child, $indent);
			}
		}
	}
	
	/**
	 * Get array of links (download) for message attachment files    
	 * @param int  		message id 
	 * @return array 
	 */	
	public static function get_links_message_attachment_files($message_id) {
		
		$tbl_message_attach = Database::get_main_table(TABLE_MESSAGE_ATTACHMENT);
		$message_id = intval($message_id);
		
		// get file attachments by message id
		$links_attach_file = array();
		if (!empty($message_id)) {
			$sql = "SELECT * FROM $tbl_message_attach WHERE message_id = '$message_id'";
			$rs_file = Database::query($sql,__FILE__,__LINE__);				
			if (Database::num_rows($rs_file) > 0) {
				$attach_icon = Display::return_icon('attachment.gif');						
				$archiveURL=api_get_path(WEB_CODE_PATH).'messages/download.php?type=inbox&file=';				
				while ($row_file = Database::fetch_array($rs_file)) {
					$archiveFile= $row_file['path'];
					$filename 	= $row_file['filename'];
					$filesize 	= format_file_size($row_file['size']);
					$filecomment= $row_file['comment'];
					$links_attach_file[] = $attach_icon.'&nbsp;<a href="'.$archiveURL.$archiveFile.'">'.$filename.'</a>&nbsp;('.$filesize.')'.(!empty($filecomment)?'&nbsp;-&nbsp;'.$filecomment:''); 
				}
			}						
		}		
		return $links_attach_file;		
	}
	
	/**
	 * Get message list by id    
	 * @param int  message id 
	 * @return array  
	 */	
	public static function get_message_by_id($message_id) {
		$tbl_message = Database::get_main_table(TABLE_MESSAGE);
		$message_id = intval($message_id);				
		$sql = "SELECT * FROM $tbl_message WHERE id = '$message_id'";
		$res = Database::query($sql, __FILE__, __LINE__);
		$item = array(); 
		if (Database::num_rows($res)>0) {
			$item = Database::fetch_array($res,'ASSOC');
		}
		return $item;
	}	
}


//@todo this functions should be in the message class

function inbox_display() {
	global $charset;	
//	$charset = api_get_setting('platform_charset');
	$table_message = Database::get_main_table(TABLE_MESSAGE);
	$request=api_is_xml_http_request();
	if ($_SESSION['social_exist']===true) {
		$redirect="#remote-tab-2";
		if (api_get_setting('allow_social_tool')=='true' && api_get_setting('allow_message_tool')=='true') {
			$success= get_lang('SelectedMessagesDeleted');
		} else {
			$success= get_lang('SelectedMessagesDeleted');
		}
	} else {
		$success= get_lang('SelectedMessagesDeleted');
	}
	if (isset ($_REQUEST['action'])) {
		switch ($_REQUEST['action']) {
			case 'delete' :
    			$number_of_selected_messages = count($_POST['id']);
    			foreach ($_POST['id'] as $index => $message_id) {
    				MessageManager::delete_message_by_user_receiver(api_get_user_id(), $message_id);
    			}
    			Display::display_normal_message(api_xml_http_response_encode($success),false);
    			break;
			case 'deleteone' :
    			MessageManager::delete_message_by_user_receiver(api_get_user_id(), $_GET['id']);
    			Display::display_confirmation_message(api_xml_http_response_encode($success),false);
    			echo '<br />';
    			break;
		}
	}
	// display sortable table with messages of the current user
	$table = new SortableTable('messages', 'get_number_of_messages_mask', 'get_message_data_mask', 3, get_number_of_messages_mask(),'DESC');
	$table->set_header(0, '', false,array ('style' => 'width:20px;'));
	$title=api_xml_http_response_encode(get_lang('Title'));
	$action=api_xml_http_response_encode(get_lang('Actions'));
	$table->set_header(1,api_xml_http_response_encode(get_lang('Status')),false,array('style' => 'width:30px;'));
	$table->set_header(2,api_xml_http_response_encode(get_lang('From')),false);
	$table->set_header(3,$title,false);
	$table->set_header(4,api_xml_http_response_encode(get_lang('Date')),false,array('style' => 'width:150px;'));
	$table->set_header(5,$action,false,array ('style' => 'width:100px;'));
	
    echo '<div id="div_content_table_data">';
	if ($request===true) {
		echo '<form name="form_send" id="form_send" action="" method="post">';
		echo '<input type="hidden" name="action" value="delete" />';
		$table->display();
		echo '</form>';
		if (get_number_of_messages_mask() > 0) {
			echo '<a href="javascript:void(0)" onclick="selectall_cheks()">'.api_xml_http_response_encode(get_lang('SelectAll')).'</a>&nbsp;&nbsp;&nbsp;';
			echo '<a href="javascript:void(0)" onclick="unselectall_cheks()">'.api_xml_http_response_encode(get_lang('UnSelectAll')).'</a>&nbsp;&nbsp;&nbsp;';
			echo '<button class="save" name="delete" type="button" value="'.api_xml_http_response_encode(get_lang('DeleteSelectedMessages')).'" onclick="submit_form(\'inbox\')">'.api_xml_http_response_encode(get_lang('DeleteSelectedMessages')).'</button>';

		}
	} else {
		$table->set_form_actions(array ('delete' => get_lang('DeleteSelectedMessages')));
		$table->display();
	}
    echo '</div>';
}
function get_number_of_messages_mask() {
	return MessageManager::get_number_of_messages();
}
function get_message_data_mask($from, $number_of_items, $column, $direction) {
	$column='3';
	$direction='desc';
	//non set by SortableTable ?
	$number_of_items=get_number_of_messages_mask();
	return MessageManager::get_message_data($from, $number_of_items, $column, $direction);
}
function outbox_display() {
	$table_message = Database::get_main_table(TABLE_MESSAGE);
	$request=api_is_xml_http_request();
	global $charset;
	if ($_SESSION['social_exist']===true) {
		
		$redirect="#remote-tab-3";
		if (api_get_setting('allow_social_tool')=='true' && api_get_setting('allow_message_tool')=='true') {
			$success= get_lang('SelectedMessagesDeleted')."&nbsp<br><a href=\""."../social/index.php?$redirect\">".get_lang('BackToOutbox')."</a>";
		}else {
			$success=get_lang('SelectedMessagesDeleted')."&nbsp<br><a href=\""."../social/index.php?$redirect\">".get_lang('BackToOutbox')."</a>";
		}

	} else {
		$success= get_lang('SelectedMessagesDeleted')."&nbsp</b>"."<br><a href=\""."outbox.php\">".get_lang('BackToOutbox')."</a>";
	}
	if (isset ($_REQUEST['action'])) {
		switch ($_REQUEST['action']) {
			case 'delete' :
			$number_of_selected_messages = count($_POST['id']);
			if ($number_of_selected_messages!=0) {
				foreach ($_POST['id'] as $index => $message_id) {
					MessageManager::delete_message_by_user_receiver(api_get_user_id(), $message_id);
				}
			}
			Display::display_normal_message(api_xml_http_response_encode($success),false);
			break;
			case 'deleteone' :
			MessageManager::delete_message_by_user_receiver(api_get_user_id(), $_GET['id']);
			Display::display_confirmation_message(api_xml_http_response_encode($success),false);
			echo '<br/>';
			break;
		}
	}
	
	// display sortable table with messages of the current user
	$table = new SortableTable('messages', 'get_number_of_messages_send_mask', 'get_message_data_send_mask', 3,get_number_of_messages_send_mask(),'DESC');
	$title=api_xml_http_response_encode(get_lang('Title'));
	$action=api_xml_http_response_encode(get_lang('Actions'));
	$table->set_header(0, '', false,array ('style' => 'width:20px;'));
	$table->set_header(1, api_xml_http_response_encode(get_lang('Status')),false,array ('style' => 'width:30px;'));
	$table->set_header(2, api_xml_http_response_encode(get_lang('To')),false);
	$table->set_header(3, $title,false);
	$table->set_header(4, api_xml_http_response_encode(get_lang('Date')),false,array ('style' => 'width:150px;'));
	$table->set_header(5,$action, false,array ('style' => 'width:100px;'));
	echo '<div id="div_content_table_data_sent">';
		if ($request===true) {
			echo '<form name="form_send_out" id="form_send_out" action="" method="post">';
			echo '<input type="hidden" name="action" value="delete" />';
			$table->display();
			echo '</form>';
			if (get_number_of_messages_send_mask() > 0) {
				echo '<a href="javascript:void(0)" onclick="selectall_cheks()">'.api_xml_http_response_encode(get_lang('SelectAll')).'</a>&nbsp;&nbsp;&nbsp;';
				echo '<a href="javascript:void(0)" onclick="unselectall_cheks()">'.api_xml_http_response_encode(get_lang('UnSelectAll')).'</a>&nbsp;&nbsp;&nbsp;';
				echo '<button class="save" name="delete" type="button" value="'.api_xml_http_response_encode(get_lang('DeleteSelectedMessages')).'" onclick="submit_form(\'outbox\')">'.api_xml_http_response_encode(get_lang('DeleteSelectedMessages')).'</button>';
			}
		} else {
			$table->set_form_actions(array ('delete' => get_lang('DeleteSelectedMessages')));
			$table->display();
		}
	echo '</div>';
}
function get_number_of_messages_send_mask() {
	return MessageManager::get_number_of_messages_sent();
}
function get_message_data_send_mask($from, $number_of_items, $column, $direction) {
	$column='3';
	$direction='desc';
	//non set by SortableTable ?
	$number_of_items=get_number_of_messages_send_mask();
	return MessageManager::get_message_data_sent($from, $number_of_items, $column, $direction);
}
?>
