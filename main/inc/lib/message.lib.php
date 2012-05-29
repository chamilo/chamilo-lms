<?php
/* For licensing terms, see /license.txt */
/**
*	This class provides methods for messages management.
*	Include/require it in your code to use its features.
*
*	@package chamilo.library
*/
/**
 * Code
 */
require_once api_get_path(LIBRARY_PATH).'online.inc.php';
require_once api_get_path(LIBRARY_PATH).'fileUpload.lib.php';
require_once api_get_path(LIBRARY_PATH).'fileDisplay.lib.php';
require_once api_get_path(LIBRARY_PATH).'group_portal_manager.lib.php';

/*
 * @todo use constants!
 */
define('MESSAGE_STATUS_NEW',				'0');
define('MESSAGE_STATUS_UNREAD',				'1');
//2 ??
define('MESSAGE_STATUS_DELETED',			'3');
define('MESSAGE_STATUS_OUTBOX',				'4');
define('MESSAGE_STATUS_INVITATION_PENDING',	'5');
define('MESSAGE_STATUS_INVITATION_ACCEPTED','6');
define('MESSAGE_STATUS_INVITATION_DENIED',	'7');
/**
 * Class
 * @package chamilo.library
 */
class MessageManager
{
	public static function get_online_user_list($current_user_id) {		
		global $_configuration;
        //@todo this is a bad idea to parse all users online
        $count = who_is_online_count();
		$userlist = who_is_online(0, $count, null, null, 30, true);
		$online_user_list = array();
		foreach ($userlist as $user_id) {			
			$online_user_list[$user_id] = GetFullUserName($user_id).($current_user_id==$user_id?("&nbsp;(".get_lang('Myself').")"):(""));
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
		return Display::return_message(api_xml_http_response_encode($success), 'confirmation', false);
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
		$query = "SELECT * FROM $table_message WHERE user_receiver_id=".api_get_user_id()." AND msg_status=".MESSAGE_STATUS_UNREAD;
		$result = Database::query($query);
		$i = Database::num_rows($result);
		return $i;
	}

	/**
	* Get the list of user_ids of users who are online.
	*/
	public static function users_connected_by_id() {		
		$count = who_is_online_count();
		$user_connect = who_is_online(0, $count, null, null, 30, true);
        $user_id_list = array(); 
		for ($i=0; $i<count($user_connect); $i++) {
			$user_id_list[$i]=$user_connect[$i][0];
		}
		return $user_id_list;
	}

	/**
	 * Gets the total number of messages, used for the inbox sortable table
	 */
	public static function get_number_of_messages ($unread = false) {
		$table_message = Database::get_main_table(TABLE_MESSAGE);

		$condition_msg_status = '';
		if ($unread) {
			$condition_msg_status = ' msg_status = '.MESSAGE_STATUS_UNREAD.' ';
		} else {
			$condition_msg_status = ' msg_status IN('.MESSAGE_STATUS_NEW.','.MESSAGE_STATUS_UNREAD.') ';
		}

		$sql_query = "SELECT COUNT(*) as number_messages FROM $table_message WHERE $condition_msg_status AND user_receiver_id=".api_get_user_id();
		$sql_result = Database::query($sql_query);
		$result = Database::fetch_array($sql_result);
		return $result['number_messages'];
	}

	/**
	 * Gets information about some messages, used for the inbox sortable table
	 * @param int $from
	 * @param int $number_of_items
	 * @param string $direction
	 */
	public static function get_message_data($from, $number_of_items, $column, $direction) {
		global $charset;
		$from = intval($from);
		$number_of_items = intval($number_of_items);

		//forcing this order
		if (!isset($direction)) {
			$column = 3;
			$direction = 'DESC';
		} else {
			$column = intval($column);
			if (!in_array($direction, array('ASC', 'DESC')))
				$direction = 'ASC';
		}
		$table_message = Database::get_main_table(TABLE_MESSAGE);
		$request=api_is_xml_http_request();
		$sql_query = "SELECT id as col0, user_sender_id as col1, title as col2, send_date as col3, msg_status as col4 FROM $table_message " .
					 " WHERE user_receiver_id=".api_get_user_id()." AND msg_status IN (0,1)" .
					 " ORDER BY col$column $direction LIMIT $from,$number_of_items";

		$sql_result = Database::query($sql_query);
		$i = 0;
		$message_list = array ();
		while ($result = Database::fetch_row($sql_result)) {		    
			if ($request===true) {
				$message[0] = '<input type="checkbox" value='.$result[0].' name="id[]">';
			 } else {
				$message[0] = ($result[0]);
			 }
			$result[2] = Security::remove_XSS($result[2], STUDENT, true);
			$result[2] = cut($result[2], 80,true);

			if ($request===true) {

				/*if($result[4]==0) {
					$message[1] = Display::return_icon('mail_open.png',get_lang('AlreadyReadMessage'));//Message already read
				} else {
					$message[1] = Display::return_icon('mail.png',get_lang('UnReadMessage'));//Message without reading
				}*/
				$message[1] = '<a onclick="get_action_url_and_show_messages(1,'.$result[0].')" href="javascript:void(0)">'.GetFullUserName($result[1]).'</a>';
				$message[2] = '<a onclick="get_action_url_and_show_messages(1,'.$result[0].')" href="javascript:void(0)">'.str_replace("\\","",$result[3]).'</a>';
				$message[3] = '<a onclick="reply_to_messages(\'show\','.$result[0].',\'\')" href="javascript:void(0)">'.Display::return_icon('message_reply.png',get_lang('ReplyToMessage')).'</a>'.
						  '&nbsp;&nbsp;<a onclick="delete_one_message('.$result[0].')" href="javascript:void(0)"  >'.Display::return_icon('delete.png',get_lang('DeleteMessage')).'</a>';
			} else {		    
				if($result[4]==1) {
					$class = 'class = "unread"';
				} else {
					$class = 'class = "read"';
				}
				$link = '';
				if ($_GET['f']=='social') {
					$link = '&f=social';
				}
				$message[1] = '<a '.$class.' href="view_message.php?id='.$result[0].$link.'">'.$result[2].'</a><br />'.GetFullUserName(($result[1]));
				//$message[2] = '<a '.$class.' href="view_message.php?id='.$result[0].$link.'">'.$result[2].'</a>';
				$message[3] = '<a href="new_message.php?re_id='.$result[0].'&f='.Security::remove_XSS($_GET['f']).'">'.Display::return_icon('message_reply.png',get_lang('ReplyToMessage')).'</a>'.
						  '&nbsp;&nbsp;<a delete_one_message('.$result[0].') href="inbox.php?action=deleteone&id='.$result[0].'&f='.Security::remove_XSS($_GET['f']).'">'.Display::return_icon('delete.png',get_lang('DeleteMessage')).'</a>';
			}
			$message[2] = api_convert_and_format_date($result[3], DATE_TIME_FORMAT_LONG); //date stays the same
			foreach($message as $key => $value) {
				$message[$key] = api_xml_http_response_encode($value);
			}
			$message_list[] = $message;
			$i++;
		}
		return $message_list;
	}

	/**
	 * Sends a message to a user/group
	 * 
	 * @param int 	  receiver user id
	 * @param string  subject
	 * @param string  content
	 * @param array   attachment files array($_FILES) (optional)
	 * @param array   comments about attachment files (optional)
	 * @param int     group id (optional)
	 * @param int     parent id (optional)
	 * @param int 	  message id for updating the message (optional)
     * @param bool    sent an email or not (@todo)
	 * @return bool
	 */
	public static function send_message($receiver_user_id, $subject, $content, $file_attachments = array(), $file_comments = array(), $group_id = 0, $parent_id = 0, $edit_message_id = 0, $topic_id = 0) {        
		$table_message      = Database::get_main_table(TABLE_MESSAGE);
        $group_id           = intval($group_id);
        $receiver_user_id   = intval($receiver_user_id);
        $parent_id          = intval($parent_id);
        $edit_message_id    = intval($edit_message_id);
        $topic_id   		= intval($topic_id);                
		$user_sender_id     = api_get_user_id();

		$total_filesize = 0;
		if (is_array($file_attachments)) {
			foreach ($file_attachments as $file_attach) {
				$total_filesize += $file_attach['size'];
			}
		}

		// validating fields
		if (empty($subject) && empty($group_id) ) {
			return get_lang('YouShouldWriteASubject');
		} else if ($total_filesize > intval(api_get_setting('message_max_upload_filesize'))) {
			return sprintf(get_lang("FilesSizeExceedsX"),format_file_size(api_get_setting('message_max_upload_filesize')));
		}
		
		$inbox_last_id = null;
		
		//Just in case we replace the and \n and \n\r while saving in the DB
		$content = str_replace(array("\n", "\n\r"), '<br />', $content);		

		$now = api_get_utc_datetime();		
        if (!empty($receiver_user_id) || !empty($group_id)) {

        	// message for user friend        	
        	
	        $clean_subject = Database::escape_string($subject);
	        $clean_content = Database::escape_string($content);

			//message in inbox for user friend
            //@todo it's possible to edit a message? yes, only for groups 
			if ($edit_message_id) { 
				$query = " UPDATE $table_message SET update_date = '".$now."', content = '$clean_content' WHERE id = '$edit_message_id' ";                
				$result = Database::query($query);
				$inbox_last_id = $edit_message_id;
			} else {
				$query = "INSERT INTO $table_message(user_sender_id, user_receiver_id, msg_status, send_date, title, content, group_id, parent_id, update_date ) ".
					     "VALUES ('$user_sender_id', '$receiver_user_id', '1', '".$now."','$clean_subject','$clean_content','$group_id','$parent_id', '".$now."')";
				$result = Database::query($query);
				$inbox_last_id = Database::insert_id();
			}

			// Save attachment file for inbox messages
			if (is_array($file_attachments)) {
				$i = 0;
				foreach ($file_attachments as $file_attach) {
					if ($file_attach['error'] == 0) {
						self::save_message_attachment_file($file_attach,$file_comments[$i],$inbox_last_id,null,$receiver_user_id,$group_id);
					}
					$i++;
				}
			}

			if (empty($group_id)) {
				//message in outbox for user friend or group
				$sql = "INSERT INTO $table_message (user_sender_id, user_receiver_id, msg_status, send_date, title, content, group_id, parent_id, update_date ) ".
					   " VALUES ('$user_sender_id', '$receiver_user_id', '4', '".$now."','$clean_subject','$clean_content', '$group_id', '$parent_id', '".$now."')";
				$rs = Database::query($sql);
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
			}
						
			//Load user settings			
			$notification = new Notification();
			$sender_info = api_get_user_info($user_sender_id);
			
		    if (empty($group_id)) {	    	
                $notification->save_notification(NOTIFICATION_TYPE_MESSAGE, array($receiver_user_id), $subject, $content, $sender_info);                
		    } else {
		        $group_info = GroupPortalManager::get_group_data($group_id);
		        $group_info['topic_id'] = $topic_id;
		        $group_info['msg_id'] 	= $inbox_last_id;
		        
		        $user_list  = GroupPortalManager::get_users_by_group($group_id, false, array(),0, 1000);
		        
		        //Adding more sens to the message group
		        $subject = sprintf(get_lang('ThereIsANewMessageInTheGroupX'), $group_info['name']);		        
		        
		        $new_user_list = array();		   
                foreach($user_list as $user_data) {
                    $new_user_list[] = $user_data['user_id'];
                }
                $group_info = array('group_info'=>$group_info, 'user_info' => $sender_info);
                $notification->save_notification(NOTIFICATION_TYPE_GROUP, $new_user_list, $subject, $content, $group_info);                     		
		    }
			return $inbox_last_id;
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
		$rs_message	= Database::query($sql_message);
		$row_message= Database::fetch_array($rs_message);

		// get message id from data found early for other receiver user
		$sql_msg_id	= " SELECT id FROM $table_message WHERE user_sender_id ='{$row_message[user_sender_id]}'
				 		AND title='{$row_message[title]}' AND content='{$row_message[content]}' AND group_id='{$row_message[group_id]}' AND user_receiver_id='$receiver_user_id'";
		$rs_msg_id	= Database::query($sql_msg_id);
		$row = Database::fetch_array($rs_msg_id);

		// update parent_id for other user receiver
		$sql_upd = "UPDATE $table_message SET parent_id = '{$row[id]}' WHERE id = '$message_id'";
		Database::query($sql_upd);
	}

	public static function delete_message_by_user_receiver ($user_receiver_id,$id) {
		$table_message = Database::get_main_table(TABLE_MESSAGE);
		if ($id != strval(intval($id))) return false;
		$user_receiver_id = intval($user_receiver_id);
		$id = Database::escape_string($id);
		$sql="SELECT * FROM $table_message WHERE id=".$id." AND msg_status<>4;";
		$rs=Database::query($sql);

		if (Database::num_rows($rs) > 0 ) {
			$row = Database::fetch_array($rs);
			// delete attachment file
			$res = self::delete_message_attachment_file($id,$user_receiver_id);
			// delete message
			$query = "UPDATE $table_message SET msg_status=3 WHERE user_receiver_id=".$user_receiver_id." AND id=".$id;
			//$query = "DELETE FROM $table_message WHERE user_receiver_id=".Database::escape_string($user_receiver_id)." AND id=".$id;
			$result = Database::query($query);
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
		$rs=Database::query($sql);

		if (Database::num_rows($rs) > 0 ) {
			$row = Database::fetch_array($rs);
			// delete attachment file
			$res = self::delete_message_attachment_file($id,$user_sender_id);
			// delete message
			$query = "UPDATE $table_message SET msg_status=3 WHERE user_sender_id='$user_sender_id' AND id='$id'";
			//$query = "DELETE FROM $table_message WHERE user_sender_id='$user_sender_id' AND id='$id'";
			$result = Database::query($query);
			return $result;
		}
		return false;
	}

	/**
	 * Saves a message attachment files
	 * @param  array 	$_FILES['name']
	 * @param  string  	a comment about the uploaded file
	 * @param  int		message id
	 * @param  int		receiver user id (optional)
	 * @param  int		sender user id (optional)
	 * @param  int		group id (optional)
	 * @return void
	 */
	public static function save_message_attachment_file($file_attach,$file_comment,$message_id,$receiver_user_id=0,$sender_user_id=0,$group_id=0) {

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

			if (!empty($group_id)) {
				$path_user_info = GroupPortalManager::get_group_picture_path_by_id($group_id, 'system', true);
			} else {
				$path_user_info = UserManager::get_user_picture_path_by_id($message_user_id, 'system', true);
			}

			$path_message_attach = $path_user_info['dir'].'message_attachments/';

			// If this directory does not exist - we create it.
			if (!file_exists($path_message_attach)) {
				@mkdir($path_message_attach, api_get_permissions_for_new_directories(), true);
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
			$result=Database::query($sql);
		}
	}

	/**
	 * Delete message attachment files (logically updating the row with a suffix _DELETE_id)
	 * @param  int	message id
	 * @param  int	message user id (receiver user id or sender user id)
	 * @param  int	group id (optional)
	 * @return void
	 */
	public static function delete_message_attachment_file($message_id,$message_uid,$group_id=0) {

		$message_id = intval($message_id);
		$message_uid = intval($message_uid);
		$table_message_attach = Database::get_main_table(TABLE_MESSAGE_ATTACHMENT);

		$sql= "SELECT * FROM $table_message_attach WHERE message_id = '$message_id'";
		$rs	= Database::query($sql);
		$new_paths = array();
		while ($row = Database::fetch_array($rs)) {
			$path 		= $row['path'];
			$attach_id  = $row['id'];
			$new_path 	= $path.'_DELETED_'.$attach_id;

			if (!empty($group_id)) {
				$path_user_info = GroupPortalManager::get_group_picture_path_by_id($group_id, 'system', true);
			} else {
				$path_user_info = UserManager::get_user_picture_path_by_id($message_uid, 'system', true);
			}

			$path_message_attach = $path_user_info['dir'].'message_attachments/';
			if (is_file($path_message_attach.$path)) {
				if(rename($path_message_attach.$path, $path_message_attach.$new_path)) {
					$sql_upd = "UPDATE $table_message_attach set path='$new_path' WHERE id ='$attach_id'";
					$rs_upd = Database::query($sql_upd);
				}
			}
		}
	}

	/**
	 * update messages by user id and message id
	 * @param  int		user id
	 * @param  int		message id
	 * @return resource
	 */
	public static function update_message($user_id, $message_id) {
		if ($message_id != strval(intval($message_id)) || $user_id != strval(intval($user_id))) return false;
		$table_message = Database::get_main_table(TABLE_MESSAGE);
		$query = "UPDATE $table_message SET msg_status = '0' WHERE msg_status<>4 AND user_receiver_id=".intval($user_id)." AND id='".intval($message_id)."'";
		$result = Database::query($query);
	}

	/**
	 * get messages by user id and message id
	 * @param  int		user id
	 * @param  int		message id
	 * @return array
	 */
	 public static function get_message_by_user($user_id,$message_id) {
	 	if ($message_id != strval(intval($message_id)) || $user_id != strval(intval($user_id))) return false;
		$table_message = Database::get_main_table(TABLE_MESSAGE);
		$query = "SELECT * FROM $table_message WHERE user_receiver_id=".intval($user_id)." AND id='".intval($message_id)."'";
		$result = Database::query($query);
		return $row = Database::fetch_array($result);
	}

	/**
	 * get messages by group id
	 * @param  int		group id
	 * @return array
	 */
	public static function get_messages_by_group($group_id) {
		if ($group_id != strval(intval($group_id))) return false;
	 	$table_message = Database::get_main_table(TABLE_MESSAGE);	 	
	 	$group_id = intval($group_id);
		$query = "SELECT * FROM $table_message WHERE group_id= $group_id AND msg_status NOT IN ('".MESSAGE_STATUS_OUTBOX."', '".MESSAGE_STATUS_DELETED."')  ORDER BY id";
		$rs = Database::query($query);
		$data = array();
		if (Database::num_rows($rs) > 0) {
			while ($row = Database::fetch_array($rs,'ASSOC')) {
				$data[] = $row;
			}
		}
		return $data;
	}
	
	
   /**
     * get messages by group id
     * @param  int      group id
     * @return array
     */
    public static function get_messages_by_group_by_message($group_id, $message_id) {
        if ($group_id != strval(intval($group_id))) return false;
        $table_message = Database::get_main_table(TABLE_MESSAGE);        
        $group_id = intval($group_id);
        $query = "SELECT * FROM $table_message WHERE group_id = $group_id AND msg_status NOT IN ('".MESSAGE_STATUS_OUTBOX."', '".MESSAGE_STATUS_DELETED."')  ORDER BY id ";
        
        $rs = Database::query($query);
        $data = array();
        $parents = array();
        if (Database::num_rows($rs) > 0) {            
            while ($row = Database::fetch_array($rs, 'ASSOC')) {                
                if ($message_id == $row['parent_id'] || in_array($row['parent_id'], $parents)) {
                    $parents[]= $row['id'];
                    $data[] = $row;
                }
            }
        }
        return $data;
    }	

	/**
	 * get messages by parent id optionally with limit
	 * @param  int		parent id
	 * @param  int		group id (optional)
	 * @param  int		offset (optional)
	 * @param  int		limit (optional)
	 * @return array
	 */
	public static function get_messages_by_parent($parent_id,$group_id = '',$offset = 0,$limit = 0) {
		if ($parent_id != strval(intval($parent_id))) return false;
	 	$table_message = Database::get_main_table(TABLE_MESSAGE);
	 	$current_uid = api_get_user_id();
	 	$parent_id = intval($parent_id);

	 	$condition_group_id = "";
	 	if ($group_id !== '') {
	 		$group_id = intval($group_id);
	 		$condition_group_id = " AND group_id = '$group_id' ";
	 	}

	 	$condition_limit = "";
	 	if ($offset && $limit) {
	 		$offset = ($offset - 1) * $limit;
	 		$condition_limit = " LIMIT $offset,$limit ";
	 	}

		$query = "SELECT * FROM $table_message WHERE parent_id='$parent_id' AND msg_status <> ".MESSAGE_STATUS_OUTBOX." $condition_group_id ORDER BY send_date DESC $condition_limit ";
		$rs = Database::query($query);
		$data = array();
		if (Database::num_rows($rs) > 0) {
			while ($row = Database::fetch_array($rs)) {
				$data[$row['id']] = $row;
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
		$result = Database::query($query);
		$num = Database::num_rows($result);
		if ($num>0)
			return true;
		else
			return false;
	}
	/**
	 * Gets information about messages sent
	 * @param  integer
	 * @param  integer
	 * @param  string
	 * @return array
	 */
	 public static function get_message_data_sent($from, $number_of_items, $column, $direction) {
	 	global $charset;
	 	$from = intval($from);
		$number_of_items = intval($number_of_items);
		if (!isset($direction)) {
			$column = 3;
			$direction = 'DESC';
		} else {
			$column = intval($column);
			if (!in_array($direction, array('ASC', 'DESC')))
				$direction = 'ASC';
		}
		$table_message = Database::get_main_table(TABLE_MESSAGE);
		$request=api_is_xml_http_request();
		$sql_query = "SELECT id as col0, user_sender_id as col1, title as col2, send_date as col3, user_receiver_id as col4, msg_status as col5 FROM $table_message " .
					 "WHERE user_sender_id=".api_get_user_id()." AND msg_status=".MESSAGE_STATUS_OUTBOX." " .
					 "ORDER BY col$column $direction LIMIT $from,$number_of_items";
		$sql_result = Database::query($sql_query);
		$i = 0;
		$message_list = array ();
		while ($result = Database::fetch_row($sql_result)) {
			if ($request===true) {
				$message[0] = '<input type="checkbox" value='.$result[0].' name="out[]">';
			} else {
				$message[0] = ($result[0]);
			}
			$class = 'class = "read"';
			$result[2] = Security::remove_XSS($result[2]);

			if ($request===true) {
				$message[1] = '<a onclick="show_sent_message('.$result[0].')" href="javascript:void(0)">'.GetFullUserName($result[4]).'</a>';
				$message[2] = '<a onclick="show_sent_message('.$result[0].')" href="javascript:void(0)">'.str_replace("\\","",$result[2]).'</a>';
				$message[3] = api_convert_and_format_date($result[3], DATE_TIME_FORMAT_LONG); //date stays the same
				
				$message[4] = '&nbsp;&nbsp;<a onclick="delete_one_message_outbox('.$result[0].')" href="javascript:void(0)"  >'.Display::return_icon('delete.png',get_lang('DeleteMessage')).'</a>';
			} else {
				$link = '';
				if ($_GET['f']=='social') {
					$link = '&f=social';
				}
				$message[1] = '<a '.$class.' onclick="show_sent_message ('.$result[0].')" href="../messages/view_message.php?id_send='.$result[0].$link.'">'.$result[2].'</a><br />'.GetFullUserName($result[4]);
				//$message[2] = '<a '.$class.' onclick="show_sent_message ('.$result[0].')" href="../messages/view_message.php?id_send='.$result[0].$link.'">'.$result[2].'</a>';
			    $message[2] = api_convert_and_format_date($result[3], DATE_TIME_FORMAT_LONG); //date stays the same
				$message[3] = '<a href="outbox.php?action=deleteone&id='.$result[0].'&f='.Security::remove_XSS($_GET['f']).'"  onclick="javascript:if(!confirm('."'".addslashes(api_htmlentities(get_lang('ConfirmDeleteMessage')))."'".')) return false;">'.Display::return_icon('delete.png',get_lang('DeleteMessage')).'</a>';
			}

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
		$sql_query = "SELECT COUNT(*) as number_messages FROM $table_message WHERE msg_status=".MESSAGE_STATUS_OUTBOX." AND user_sender_id=".api_get_user_id();
		$sql_result = Database::query($sql_query);
		$result = Database::fetch_array($sql_result);
		return $result['number_messages'];
	}

	/**
	 * display message box in the inbox
	 * @param int the message id
	 * @param string inbox or outbox strings are available
	 * @todo replace numbers with letters in the $row array pff... 
	 * @return string html with the message content
	 */
	public static function show_message_box($message_id, $source = 'inbox') {
		$table_message 		= Database::get_main_table(TABLE_MESSAGE);		
		$message_id 		= intval($message_id);

		if ($source == 'outbox') {
			if (isset($message_id) && is_numeric($message_id)) {
				$query	= "SELECT * FROM $table_message WHERE user_sender_id=".api_get_user_id()." AND id=".$message_id." AND msg_status=4;";
				$result = Database::query($query);
			    $path	= 'outbox.php';
			}
		} else {
			if (is_numeric($message_id) && !empty($message_id)) {
				$query = "UPDATE $table_message SET msg_status = '".MESSAGE_STATUS_NEW."' WHERE user_receiver_id=".api_get_user_id()." AND id='".$message_id."';";
				$result = Database::query($query);

				$query = "SELECT * FROM $table_message WHERE msg_status<>4 AND user_receiver_id=".api_get_user_id()." AND id='".$message_id."';";
				$result = Database::query($query);
			}
			$path='inbox.php';
		}
		$row = Database::fetch_array($result, 'ASSOC');		
		$user_sender_id = $row['user_sender_id'];

		// get file attachments by message id
		$files_attachments = self::get_links_message_attachment_files($message_id,$source);

		$user_con = self::users_connected_by_id();
		$band= 0;		
		for ($i=0;$i<count($user_con);$i++)
			if ($user_sender_id == $user_con[$i])
				$band=1;

		$title  	= Security::remove_XSS($row['title'], STUDENT, true);
		$content  	= Security::remove_XSS($row['content'], STUDENT, true);
		
		$from_user  = UserManager::get_user_info_by_id($user_sender_id);
		$name 		= api_get_person_name($from_user['firstname'], $from_user['lastname']);
		$user_image = UserManager::get_picture_user($row['user_sender_id'], $from_user['picture_uri'],80);
		$user_image = Display::img($user_image['file'], $name, array('title'=>$name));		

		$message_content =  Display::page_subheader(str_replace("\\","",$title));
		              
		if (api_get_setting('allow_social_tool') == 'true') {
            $message_content .= $user_image.' ';
        }
                      
        $message_content .='<tr>';
    	if (api_get_setting('allow_social_tool') == 'true') {	
    		if ($source == 'outbox') {
    			$message_content .= get_lang('From').': <a href="'.api_get_path(WEB_PATH).'main/social/profile.php?u='.$user_sender_id.'">'.$name.'</a> '.api_strtolower(get_lang('To')).'&nbsp;<b>'.GetFullUserName($row[2]).'</b>';
    		} else {
    			$message_content .= get_lang('From').' <a href="'.api_get_path(WEB_PATH).'main/social/profile.php?u='.$user_sender_id.'">'.$name.'</a> '.api_strtolower(get_lang('To')).'&nbsp;<b>'.get_lang('Me').'</b>';
    		}    
    	} else {
    		if ($source == 'outbox') {
    			$message_content .= get_lang('From').':&nbsp;'.$name.'</b> '.api_strtolower(get_lang('To')).' <b>'.GetFullUserName($row['user_receiver_id']).'</b>';
    		} else {
    			$message_content .= get_lang('From').':&nbsp;'.$name.'</b> '.api_strtolower(get_lang('To')).' <b>'.get_lang('Me').'</b>';
    		}
    	}
		 $message_content .=' '.get_lang('Date').':  '.api_get_local_time($row['send_date']).'
		        <br />
		        <hr style="color:#ddd" />
		        <table height="209px" width="100%">
		            <tr>
		              <td valign=top class="view-message-content">'.str_replace("\\","",$content).'</td>
		            </tr>
		        </table>
		        <div id="message-attach">'.(!empty($files_attachments)?implode('<br />',$files_attachments):'').'</div>
		        <div style="padding: 15px 0px 5px 0px">';
		    $social_link = '';
		    if ($_GET['f'] == 'social') {
		    	$social_link = 'f=social';
		    }
		    if ($source == 'outbox') {
		    	$message_content .= '<a href="outbox.php?'.$social_link.'">'.Display::return_icon('back.png',get_lang('ReturnToOutbox')).'</a> &nbsp';
		    } else {
		    	$message_content .= '<a href="inbox.php?'.$social_link.'">'.Display::return_icon('back.png',get_lang('ReturnToInbox')).'</a> &nbsp';
		    	$message_content .= '<a href="new_message.php?re_id='.$message_id.'&'.$social_link.'">'.Display::return_icon('message_reply.png',get_lang('ReplyToMessage')).'</a> &nbsp';
		    }
			$message_content .= '<a href="inbox.php?action=deleteone&id='.$message_id.'&'.$social_link.'" >'.Display::return_icon('delete.png',get_lang('DeleteMessage')).'</a>&nbsp';

			$message_content .='</div></td>
		      <td width=10></td>
		    </tr>
		</table>';
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
			$result = Database::query($query);
			$message_id = intval($_GET['id_send']);
		}
		$path='outbox.php';

		// get file attachments by message id
		$files_attachments = self::get_links_message_attachment_files($message_id,'outbox');

		$row = Database::fetch_array($result);
		$user_con = self::users_connected_by_id();
		$band=0;
		$reply='';
		for ($i=0;$i<count($user_con);$i++)
			if ($row[1]==$user_con[$i])
				$band=1;
		echo '<div class=actions>';
		echo '<a onclick="close_and_open_outbox()" href="javascript:void(0)">'.Display::return_icon('folder_up.gif',api_xml_http_response_encode(get_lang('BackToOutbox'))).api_xml_http_response_encode(get_lang('BackToOutbox')).'</a>';
		echo '<a onclick="delete_one_message_outbox('.$row[0].')" href="javascript:void(0)"  >'.Display::return_icon('delete.png',api_xml_http_response_encode(get_lang('DeleteMessage'))).api_xml_http_response_encode(get_lang('DeleteMessage')).'</a>';
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
		        <TABLE height="209px" width="100%" bgColor=#ffffff>
		          <TBODY>
		            <TR>
		              <TD vAlign=top>'.str_replace("\\","",api_xml_http_response_encode($row[6])).'</TD>
		            </TR>
		          </TBODY>
		        </TABLE>
		        <div id="message-attach">'.(!empty($files_attachments)?implode('<br />',$files_attachments):'').'</div>
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
	public static function get_user_id_by_email($user_email) {
		$tbl_user = Database::get_main_table(TABLE_MAIN_USER);
		$sql='SELECT user_id FROM '.$tbl_user.' WHERE email="'.Database::escape_string($user_email).'";';
		$rs=Database::query($sql);
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
		global $my_group_role;
		$rows = self::get_messages_by_group($group_id);		 
		$topics_per_page  = 10;
		$html_messages = '';
		$query_vars = array('id'=>$group_id, 'topics_page_nr'=>0);

		if (is_array($rows) && count($rows) > 0) {
   
			// prepare array for topics with its items
			$topics = array();
			$x = 0;            
			foreach($rows as $index => $value) {			         			    
				if (empty($value['parent_id'])) {
					$topics[$value['id']] = $value;
				}
			}
			
			$new_topics = array();
						
			foreach($topics as $id => $value) {
			    $rows = null;
                $rows = self::get_messages_by_group_by_message($group_id, $value['id']);
                if (!empty($rows)) {            
                    $count = count(self::calculate_children($rows, $value['id']));
                } else {
                    $count = 0;
                }
                $value['count'] = $count;
                $new_topics[$id] = $value;        
			}			
			//$new_topics = sort_column($new_topics,'count');
			$param_names = array_keys($_GET);
			$array_html = array();

			foreach ($new_topics as $index => $topic) {
				$html = '';
				// topics
				$indent	= 0;
				$user_sender_info   = UserManager::get_user_info_by_id($topic['user_sender_id']);
				$files_attachments  = self::get_links_message_attachment_files($topic['id']);
				$name = api_get_person_name($user_sender_info['firstname'], $user_sender_info['lastname']);

				$html .= '<div class="topic_div">';
				
				    $items = $topic['count'];				    
				    $reply_label = ($items == 1) ? get_lang('GroupReply'): get_lang('GroupReplies');
				    $html .= '<table width="100%"><tr><td width="20px" valign="top">'; 
				    $html .= Display::div(Display::tag('span', $items).$reply_label, array('class' =>'group_discussions_replies'));
				    $html .= '</td><td width="150px"  valign="top">';
				    
			        $topic['title'] = trim($topic['title']);
			        
			        if (empty($topic['title'])) {
			            $topic['title'] = get_lang('Untitled');
			        } 				
			        $title = Display::tag('h4', Display::url(Security::remove_XSS($topic['title'], STUDENT, true), 'group_topics.php?id='.$group_id.'&topic_id='.$topic['id']));
                                            
                    $date = '';
                    $link = '';
					if ($topic['send_date']!=$topic['update_date']) {
						if (!empty($topic['update_date']) && $topic['update_date'] != '0000-00-00 00:00:00' ) {
							$date .= '<div class="message-group-date" > <i>'.get_lang('LastUpdate').' '.date_to_str_ago($topic['update_date']).'</i></div>';
						}
					} else {
                        $date .= '<div class="message-group-date"> <i>'.get_lang('Created').' '.date_to_str_ago($topic['send_date']).'</i></div>';
					}					
					$image_path = UserManager::get_user_picture_path_by_id($topic['user_sender_id'], 'web', false, true);							
					$image_repository = $image_path['dir'];
					$existing_image = $image_path['file'];
					$user_info = '<td valign="top"><a href="'.api_get_path(WEB_PATH).'main/social/profile.php?u='.$topic['user_sender_id'].'">'.$name.'&nbsp;</a>';
					$user_info .= '<div class="message-group-author"><img src="'.$image_repository.$existing_image.'" alt="'.$name.'"  width="32" height="32" title="'.$name.'" /></div>';
					$user_info .= '<div class="message-group-author">'.$user.'</div></td>';
					//$date.								 
					$html .= Display::div($title.Security::remove_XSS(cut($topic['content'], 150), STUDENT, true).$user_info, array('class'=>'group_discussions_info')).'</td></table>';						
		
				$html .= '</div>'; //rounded_div
				
				$array_html[] = array($html);
			}
			// grids for items and topics  with paginations
			$html_messages .= Display::return_sortable_grid('topics', array(), $array_html, array('hide_navigation'=>false, 'per_page' => $topics_per_page), $query_vars, false, array(true, true, true,false), false);
		}
		return $html_messages;
	}
	
	
    /**
     * Displays messages of a group with nested view
     * @param int group id
     */
    public static function display_message_for_group($group_id, $topic_id, $is_member, $message_id ) {
        global $my_group_role;        
        $main_message 	= self::get_message_by_id($topic_id);       
        if (empty($main_message)) {
            return false;
        }
        $rows 			= self::get_messages_by_group_by_message($group_id, $topic_id);              
        $rows 			= self::calculate_children($rows, $topic_id);                
        $current_user_id  = api_get_user_id();
        
        $items_per_page   = 50;
        
        $query_vars = array('id' => $group_id, 'topic_id' => $topic_id , 'topics_page_nr' => 0);        
        
        // Main message        
                    
        $user_link = '';
        $links = '';
        $main_content  = '';
        
        $items_page_nr = null;
        
        $html = '';
        
        $delete_button = '';
         if (api_is_platform_admin()) {
            $delete_button =  Display::url(Display::return_icon('delete.png', get_lang('Delete'), array(), ICON_SIZE_SMALL), 'group_topics.php?action=delete&id='.$group_id.'&topic_id='.$topic_id);
        }
        $html .= Display::tag('h3', Security::remove_XSS($main_message['title'].$delete_button, STUDENT, true));
        
        $user_sender_info = UserManager::get_user_info_by_id($main_message['user_sender_id']);
        $files_attachments = self::get_links_message_attachment_files($main_message['id']);
        $name = api_get_person_name($user_sender_info['firstname'], $user_sender_info['lastname']);
            
        $topic_page_nr = isset($_GET['topics_page_nr']) ? intval($_GET['topics_page_nr']) : null;
        $links.= '<div id="message-reply-link">';
        if (($my_group_role == GROUP_USER_PERMISSION_ADMIN || $my_group_role == GROUP_USER_PERMISSION_MODERATOR) || $main_message['user_sender_id'] == $current_user_id) {        	
            $links.= '<a href="'.api_get_path(WEB_CODE_PATH).'social/message_for_group_form.inc.php?view_panel=1&height=390&width=610&&user_friend='.$current_user_id.'&group_id='.$group_id.'&message_id='.$main_message['id'].'&action=edit_message_group&anchor_topic=topic_'.$main_message['id'].'&topics_page_nr='.$topic_page_nr.'&items_page_nr='.$items_page_nr.'&topic_id='.$main_message['id'].'" class="group_message_popup" title="'.get_lang('Edit').'">';
            $links.= Display :: return_icon('edit.png', get_lang('Edit'), array(), ICON_SIZE_SMALL).'</a>';
        }
        $links.= '&nbsp;&nbsp;<a href="'.api_get_path(WEB_CODE_PATH).'social/message_for_group_form.inc.php?view_panel=1&height=390&width=610&&user_friend='.api_get_user_id().'&group_id='.$group_id.'&message_id='.$main_message['id'].'&action=reply_message_group&anchor_topic=topic_'.$main_message['id'].'&topics_page_nr='.$topic_page_nr.'&items_page_nr='.$items_page_nr.'&topic_id='.$main_message['id'].'" class="group_message_popup" title="'.get_lang('Reply').'">';
        $links.= Display :: return_icon('talk.png', get_lang('Reply')).'</a>';
        $links.= '</div>';
                                
        $image_path = UserManager::get_user_picture_path_by_id($main_message['user_sender_id'], 'web', false, true);                                
        $image_repository = $image_path['dir'];
        $existing_image = $image_path['file'];
        $main_content.= '<div class="message-group-author"><img src="'.$image_repository.$existing_image.'" alt="'.$name.'"  width="32" height="32" title="'.$name.'" /></div>';
        $user_link = '<a href="'.api_get_path(WEB_PATH).'main/social/profile.php?u='.$main_message['user_sender_id'].'">'.$name.'&nbsp;</a>';
        
        $date = '';
        if ($main_message['send_date'] != $main_message['update_date']) {
            if (!empty($main_message['update_date']) && $main_message['update_date'] != '0000-00-00 00:00:00' ) {
                $date  = '<div class="message-group-date"> '.get_lang('LastUpdate').' '.date_to_str_ago($main_message['update_date']).'</div>';
            }
        } else {
            $date = '<div class="message-group-date"> '.get_lang('Created').' '.date_to_str_ago($main_message['send_date']).'</div>';
        }
        $attachment = '<div class="message-attach">'.(!empty($files_attachments)?implode('<br />',$files_attachments):'').'</div>';                
        $main_content.= '<div class="message-group-content">'.$links.$user_link.' '.$date.$main_message['content'].$attachment.'</div>';
        $main_content = Security::remove_XSS($main_content, STUDENT, true);

        $html .= Display::div(Display::div(Display::div($main_content, array('class'=>'group_social_sub_item', 'style'=>'background-color:#fff;')), array('class' => 'group_social_item')), array('class' => 'group_social_grid'));
        
        $topic_id = $main_message['id'];
        
        if (is_array($rows) && count($rows)> 0) {
            $topics = $rows;            
            $array_html_items = array();
            foreach ($topics as $index => $topic) {
                if (empty($topic['id'])) {
                    continue;
                }
                $items_page_nr = isset($_GET['items_'.$topic['id'].'_page_nr']) ? intval($_GET['items_'.$topic['id'].'_page_nr']) : null;                
                  
                $user_link = '';
                $links = '';
                $html_items = '';
                $user_sender_info = UserManager::get_user_info_by_id($topic['user_sender_id']);
                $files_attachments = self::get_links_message_attachment_files($topic['id']);
                $name = api_get_person_name($user_sender_info['firstname'], $user_sender_info['lastname']);
                    
                $links.= '<div id="message-reply-link">';                
                if (($my_group_role == GROUP_USER_PERMISSION_ADMIN || $my_group_role == GROUP_USER_PERMISSION_MODERATOR) || $topic['user_sender_id'] == $current_user_id) {
                    $links.= '<a href="'.api_get_path(WEB_CODE_PATH).'social/message_for_group_form.inc.php?view_panel=1&height=390&width=610&&user_friend='.$current_user_id.'&group_id='.$group_id.'&message_id='.$topic['id'].'&action=edit_message_group&anchor_topic=topic_'.$topic_id.'&topics_page_nr='.$topic_page_nr.'&items_page_nr='.$items_page_nr.'&topic_id='.$topic_id.'" class="group_message_popup" title="'.get_lang('Edit').'">'.Display :: return_icon('edit.png', get_lang('Edit'), array(), ICON_SIZE_SMALL).'</a>';
                } 
                $links.= '&nbsp;&nbsp;<a href="'.api_get_path(WEB_CODE_PATH).'social/message_for_group_form.inc.php?view_panel=1&height=390&width=610&&user_friend='.api_get_user_id().'&group_id='.$group_id.'&message_id='.$topic['id'].'&action=reply_message_group&anchor_topic=topic_'.$topic_id.'&topics_page_nr='.$topic_page_nr.'&items_page_nr='.$items_page_nr.'&topic_id='.$topic_id.'" class="group_message_popup" title="'.get_lang('Reply').'">';
                $links.= Display :: return_icon('talk.png', get_lang('Reply')).'</a>';
                $links.= '</div>';
                                        
                $image_path = UserManager::get_user_picture_path_by_id($topic['user_sender_id'], 'web', false, true);                                
                $image_repository = $image_path['dir'];
                $existing_image = $image_path['file'];
                $html_items.= '<div class="message-group-author"><img src="'.$image_repository.$existing_image.'" alt="'.$name.'"  width="32" height="32" title="'.$name.'" /></div>';
                $user_link = '<a href="'.api_get_path(WEB_PATH).'main/social/profile.php?u='.$topic['user_sender_id'].'">'.$name.'&nbsp;</a>';
                
                $date = '';
                if ($topic['send_date'] != $topic['update_date']) {
                    if (!empty($topic['update_date']) && $topic['update_date'] != '0000-00-00 00:00:00' ) {
                        $date  = '<div class="message-group-date"> '.get_lang('LastUpdate').' '.date_to_str_ago($topic['update_date']).'</div>';
                    }
                } else {
                    $date = '<div class="message-group-date"> '.get_lang('Created').' '.date_to_str_ago($topic['send_date']).'</div>';
                }
                $attachment = '<div class="message-attach">'.(!empty($files_attachments)?implode('<br />',$files_attachments):'').'</div>';                
                $html_items.= '<div class="message-group-content">'.$links.$user_link.' '.$date.Security::remove_XSS($topic['content'], STUDENT, true).$attachment.'</div>';                          
                      
                $base_padding = 20;
                
                if ($topic['indent_cnt'] == 0) {
                    $indent = $base_padding;                
                } else {
                    $indent = intval($topic['indent_cnt'])*$base_padding + $base_padding;
                }
                $class = 'group_social_sub_item';
                if (isset($message_id) && $message_id == $topic['id']) {
                	$class .= ' group_social_sub_item_highlight';
                }
                
                $html_items = Display::div($html_items, array('class' => $class, 'id'=>'msg_'.$topic['id']));
                $html_items = Display::div($html_items, array('class' => '', 'style'=>'margin-left:'.$indent.'px'));            
                $array_html_items[] = array($html_items);
            }                   
            // grids for items with paginations
            $options = array('hide_navigation' => false, 'per_page' => $items_per_page);
            $visibility   = array(true, true, true, false);
            
            $style_class = array('item' => array('class'=>'group_social_item'), 'main' => array('class'=>'group_social_grid'));
            if (!empty($array_html_items)) {
                $html .= Display::return_sortable_grid('items_'.$topic['id'], array(), $array_html_items, $options, $query_vars, null, $visibility, false, $style_class);
            }
        }                    
        $html = Display::div($html, array('class'=>'', 'style'=>'width:638px'));
        return $html;
    }	
	
	/**
	 * Add children to messages by id is used for nested view messages
	 * @param array  rows of messages
	 * @return array new list adding the item children
	 */
	public static function calculate_children($rows, $first_seed) {
	    $rows_with_children = array();
		foreach($rows as $row) {
			$rows_with_children[$row["id"]]=$row;
			$rows_with_children[$row["parent_id"]]["children"][]=$row["id"];
		}	    
		$rows = $rows_with_children;		
		$sorted_rows = array(0=>array());					
		self::message_recursive_sort($rows, $sorted_rows, $first_seed);		
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
		if ($seed > 0 && isset($rows[$seed]["id"])) {		    
			$messages[$rows[$seed]["id"]]=$rows[$seed];
			$messages[$rows[$seed]["id"]]["indent_cnt"]=$indent;
			$indent++;
		}
		
		if (isset($rows[$seed]["children"])) {		    
			foreach($rows[$seed]["children"] as $child) {			    
				self::message_recursive_sort($rows, $messages, $child, $indent);
			}
		}
	}

	/**
	 * Sort date by desc from a multi-dimensional array
	 * @param array1  first array to compare
	 * @param array2  second array to compare
	 * @return bool
	 */
	public function order_desc_date($array1,$array2) {
		return strcmp($array2['send_date'],$array1['send_date']);
	}

	/**
	 * Get array of links (download) for message attachment files
	 * @param int  		message id
	 * @param string	type message list (inbox/outbox)
	 * @return array
	 */
	public static function get_links_message_attachment_files($message_id,$type='') {

		$tbl_message_attach = Database::get_main_table(TABLE_MESSAGE_ATTACHMENT);
		$message_id = intval($message_id);

		// get file attachments by message id
		$links_attach_file = array();
		if (!empty($message_id)) {

			$sql = "SELECT * FROM $tbl_message_attach WHERE message_id = '$message_id'";

			$rs_file = Database::query($sql);
			if (Database::num_rows($rs_file) > 0) {
				$attach_icon = Display::return_icon('attachment.gif', '');
				$archiveURL=api_get_path(WEB_CODE_PATH).'messages/download.php?type='.$type.'&file=';
				while ($row_file = Database::fetch_array($rs_file)) {
					$archiveFile= $row_file['path'];
					$filename 	= $row_file['filename'];
					$filesize 	= format_file_size($row_file['size']);
					$filecomment= $row_file['comment'];
					$links_attach_file[] = $attach_icon.'&nbsp;<a href="'.$archiveURL.$archiveFile.'">'.$filename.'</a>&nbsp;('.$filesize.')'.(!empty($filecomment)?'&nbsp;-&nbsp;<i>'.$filecomment.'</i>':'');
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
		$sql = "SELECT * FROM $tbl_message WHERE id = '$message_id' AND msg_status <> '".MESSAGE_STATUS_DELETED."' ";
		$res = Database::query($sql);
		$item = array();
		if (Database::num_rows($res)>0) {
			$item = Database::fetch_array($res,'ASSOC');
		}
		return $item;
	}
    
    function generate_message_form($id, $params = array()) {
        $form = new FormValidator('send_message', null, 'post', null, array('id'=>$id.'_form', 'class' =>'form-vertical'));
        $form->addElement('text', 'subject', get_lang('Subject'), array('id' => 'subject_id', 'class' => 'span5'));
        $form->addElement('textarea', 'content', get_lang('Message'), array('id' => 'content_id', 'rows' => '5', 'class' => 'span5'));
        $div = Display::div($form->return_form(), array('id' => $id.'_div', 'style' => 'display:none'));                
        return $div;
    }
    function generate_invitation_form($id , $params = array()) {
        $form = new FormValidator('send_invitation', null, 'post', null, array('id'=>$id.'_form','class' =>'form-vertical'));
        //$form->addElement('text', 'subject', get_lang('Subject'), array('id' => 'subject_id'));
        $form->addElement('textarea', 'content', get_lang('AddPersonalMessage'), array('id' => 'content_invitation_id', 'rows' => '5', 'class' => 'span5'));
        $div = Display::div($form->return_form(), array('id' => $id.'_div', 'style' => 'display:none'));                
        return $div;
    }
}


//@todo this functions should be in the message class

function inbox_display() {
	global $charset;	
    $success = get_lang('SelectedMessagesDeleted');
    $html = '';
    
	if (isset ($_REQUEST['action'])) {
		switch ($_REQUEST['action']) {
			case 'delete' :
    			$number_of_selected_messages = count($_POST['id']);
    			foreach ($_POST['id'] as $index => $message_id) {
    				MessageManager::delete_message_by_user_receiver(api_get_user_id(), $message_id);
    			}
    			$html .= Display::return_message(api_xml_http_response_encode($success), 'normal', false);
    			break;
			case 'deleteone' :
    			MessageManager::delete_message_by_user_receiver(api_get_user_id(), $_GET['id']);
    			$html .= Display::return_message(api_xml_http_response_encode($success),'confirmation', false);    			
    			break;
		}
	}
    
	// display sortable table with messages of the current user	
	$table = new SortableTable('message_inbox', array('MessageManager','get_number_of_messages'), array('MessageManager','get_message_data'),3,20,'DESC');
	$table->set_header(0, '', false,array ('style' => 'width:15px;'));
	$title=api_xml_http_response_encode(get_lang('Title'));
	$action=api_xml_http_response_encode(get_lang('Modify'));
	$table->set_header(1,api_xml_http_response_encode(get_lang('Messages')),false);	
	$table->set_header(2,api_xml_http_response_encode(get_lang('Date')),true, array('style' => 'width:180px;'));
	$table->set_header(3,$action,false,array ('style' => 'width:70px;'));
	
	if ($_REQUEST['f']=='social') {
		$parameters['f'] = 'social';
		$table->set_additional_parameters($parameters);
	}    
	$table->set_form_actions(array ('delete' => get_lang('DeleteSelectedMessages')));
    $html .= $table->return_table();
	return $html;
	
}

function get_number_of_messages_mask() {
	return MessageManager::get_number_of_messages();
}

function get_message_data_mask($from, $number_of_items, $column, $direction) {
	$column='3';
	$direction='DESC';
	//non set by SortableTable ?
	$number_of_items=get_number_of_messages_mask();
	return MessageManager::get_message_data($from, $number_of_items, $column, $direction);
}

function outbox_display() {	
	$request=api_is_xml_http_request();
	global $charset;

	$social_link = false;
	if ($_REQUEST['f']=='social') {
		$social_link ='f=social';
	}
	$success = get_lang('SelectedMessagesDeleted').'&nbsp</b><br /><a href="outbox.php?'.$social_link.'">'.get_lang('BackToOutbox').'</a>';
	
	if (isset ($_REQUEST['action'])) {
		switch ($_REQUEST['action']) {
			case 'delete' :
    			$number_of_selected_messages = count($_POST['id']);
    			if ($number_of_selected_messages!=0) {
    				foreach ($_POST['id'] as $index => $message_id) {
    					MessageManager::delete_message_by_user_receiver(api_get_user_id(), $message_id);
    				}
    			}
    			$html .= Display::return_message(api_xml_http_response_encode($success),'normal', false);
    			break;
			case 'deleteone' :
    			MessageManager::delete_message_by_user_receiver(api_get_user_id(), $_GET['id']);
    			$html .=Display::return_message(api_xml_http_response_encode($success), 'normal', false);
                $html .= '<br/>';
			 break;
		}
	}

	// display sortable table with messages of the current user	
	$table = new SortableTable('message_outbox', array('MessageManager','get_number_of_messages_sent'), array('MessageManager','get_message_data_sent'),3,20,'DESC');

	$parameters['f'] = Security::remove_XSS($_GET['f']);
	$table->set_additional_parameters($parameters);
	$table->set_header(0, '', false,array ('style' => 'width:15px;'));
	$title = api_xml_http_response_encode(get_lang('Title'));
	$action= api_xml_http_response_encode(get_lang('Modify'));

	$table->set_header(1, api_xml_http_response_encode(get_lang('Messages')),false);
	//$table->set_header(2, $title,true);
	$table->set_header(2, api_xml_http_response_encode(get_lang('Date')),true,array ('style' => 'width:160px;'));
	$table->set_header(3,$action, false,array ('style' => 'width:70px;'));

	
	if ($request===true) {
		$html .=  '<form name="form_send_out" id="form_send_out" action="" method="post">';
		$html .=  '<input type="hidden" name="action" value="delete" />';
		$html .= $table->return_table();
		$html .=  '</form>';
		if (get_number_of_messages_send_mask() > 0) {
			$html .=  '<a href="javascript:void(0)" onclick="selectall_cheks()">'.api_xml_http_response_encode(get_lang('SelectAll')).'</a>&nbsp;&nbsp;&nbsp;';
			$html .=  '<a href="javascript:void(0)" onclick="unselectall_cheks()">'.api_xml_http_response_encode(get_lang('UnSelectAll')).'</a>&nbsp;&nbsp;&nbsp;';
			$html .=  '<button class="save" name="delete" type="button" value="'.api_xml_http_response_encode(get_lang('DeleteSelectedMessages')).'" onclick="submit_form(\'outbox\')">'.api_xml_http_response_encode(get_lang('DeleteSelectedMessages')).'</button>';
		}
	} else {
		$table->set_form_actions(array ('delete' => get_lang('DeleteSelectedMessages')));
		$html .= $table->return_table();
	}
    return $html;	
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
