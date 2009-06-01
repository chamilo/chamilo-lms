<?php //$id: $
/* For licensing terms, see /dokeos_license.txt */
define(USERUNKNOW,0);
define(SOCIALUNKNOW,1);
define(SOCIALPARENT,2);
define(SOCIALFRIEND,3);
define(SOCIALGOODFRIEND,4);
define(SOCIALENEMY,5);
define(SOCIALDELETED,6);

class UserFriend extends UserManager {	
	
	function __construct() {
		
	}
	/**
	 * Allow to register contact to social network
	 * @author isaac flores paz <isaac.flores@dokeos.com>
	 * @param int user friend id
	 * @param int user id
	 * @param int kind of relation between users
	 * @return void
	 */
	public function register_friend ($friend_id,$my_user_id,$relation_type) {
		$tbl_my_friend = Database :: get_main_table(TABLE_MAIN_USER_FRIEND);
		$sql = 'SELECT COUNT(*) as count FROM ' . $tbl_my_friend . ' WHERE friend_user_id=' . ((int)$friend_id).' AND user_id='.((int)$my_user_id);

		$result = Database::query($sql, __FILE__, __LINE__);
		$row = Database :: fetch_array($result, 'ASSOC');
		if ($row['count'] == 0) {
			$sql_i = 'INSERT INTO ' . $tbl_my_friend . '(friend_user_id,user_id,relation_type)values(' . ((int)$friend_id) . ','.((int)$my_user_id).','.((int)$relation_type).');';
			Database::query($sql_i, __FILE__, __LINE__);
		} else {
			$sql = 'SELECT COUNT(*) as count FROM ' . $tbl_my_friend . ' WHERE friend_user_id=' . ((int)$friend_id) . ' AND user_id='.((int)$my_user_id);
			$result = Database::query($sql, __FILE__, __LINE__);
			$row = Database :: fetch_array($result, 'ASSOC');
			if ($row['count'] == 1) {
				$sql_i = 'UPDATE ' . $tbl_my_friend . ' SET relation_type='.((int)$relation_type).' WHERE friend_user_id=' . ((int)$friend_id).' AND user_id='.((int)$my_user_id);
				Database::query($sql_i, __FILE__, __LINE__);
			}
		}
	}
	
	/**
	 * Allow to delete contact to social network
	 *@author isaac flores paz <isaac.flores@dokeos.com>
	 *@param int user friend id
	 *@return void
	 */
	public function removed_friend ($friend_id) {
		$tbl_my_friend = Database :: get_main_table(TABLE_MAIN_USER_FRIEND);
		$tbl_my_message = Database :: get_main_table(TABLE_MAIN_MESSAGE);
		$user_id=api_get_user_id();
		$sql = 'SELECT COUNT(*) as count FROM ' . $tbl_my_friend . ' WHERE user_id=' . ((int)$user_id) . ' AND relation_type<>6 AND friend_user_id='.((int)$friend_id);
		$result = Database::query($sql, __FILE__, __LINE__);
		$row = Database :: fetch_array($result, 'ASSOC');
		if ($row['count'] == 1) {
			//Delete user friend
			$sql_i = 'UPDATE ' . $tbl_my_friend . ' SET relation_type=6 WHERE user_id=' . ((int)$user_id).' AND friend_user_id='.((int)$friend_id);
			$sql_j = 'UPDATE ' . $tbl_my_message . ' SET msg_status=7 WHERE user_receiver_id=' . ((int)$user_id).' AND user_sender_id='.((int)$friend_id);
			//Delete user
			$sql_ij = 'UPDATE ' . $tbl_my_friend . ' SET relation_type=6 WHERE user_id=' . ((int)$friend_id).' AND friend_user_id='.((int)$user_id);
			$sql_ji = 'UPDATE ' . $tbl_my_message . ' SET msg_status=7 WHERE user_receiver_id=' . ((int)$friend_id).' AND user_sender_id='.((int)$user_id);			
			Database::query($sql_i, __FILE__, __LINE__);
			Database::query($sql_j, __FILE__, __LINE__);
			Database::query($sql_ij, __FILE__, __LINE__);
			Database::query($sql_ji, __FILE__, __LINE__);			
		}
	}
	/**
	 * Allow to see contacts list
	 * @author isaac flores paz <florespaz@bidsoftperu.com>
	 * @return array
	 */
	public function show_list_type_friends () {
		$friend_relation_list=array();
		$count_list=0;
		$tbl_my_friend_relation_type = Database :: get_main_table(TABLE_MAIN_USER_FRIEND_RELATION_TYPE);
		$sql='SELECT id,title FROM '.$tbl_my_friend_relation_type.' WHERE id<>6 ORDER BY id ASC';
		$result=Database::query($sql,__FILE__,__LINE__);
		while ($row=Database::fetch_array($result,'ASSOC')) {
			$friend_relation_list[]=$row;
		}
		$count_list=count($friend_relation_list);
		if ($count_list==0) {
			$friend_relation_list[]=get_lang('UnkNow');
		} else {
			return $friend_relation_list;
		}
		
	}
	/**
	 * Get relation type contact by name
	 * @author isaac flores paz <florespaz@bidsoftperu.com>
	 * @param string names of the kind of relation 
	 * @return int
	 */
	public function get_relation_type_by_name ($relation_type_name) {
		$list_type_friend=array();
		$list_type_friend=self::show_list_type_friends();
		foreach ($list_type_friend as $value_type_friend) {
			if (strtolower($value_type_friend['title'])==$relation_type_name) {
				return $value_type_friend['id'];
			}
		}	
	}
	/**
	 * Get the kind of relation between contacts
	 * @author isaac flores paz <florespaz@bidsoftperu.com>
	 * @param int user id
	 * @param int user friend id
	 * @param string
	 */
	public function get_relation_between_contacts ($user_id,$user_friend) {
		$tbl_my_friend_relation_type = Database :: get_main_table(TABLE_MAIN_USER_FRIEND_RELATION_TYPE);
		$tbl_my_friend = Database :: get_main_table(TABLE_MAIN_USER_FRIEND);
		$sql= 'SELECT rt.id as id FROM '.$tbl_my_friend_relation_type.' rt ' .
			  'WHERE rt.id=(SELECT uf.relation_type FROM '.$tbl_my_friend.' uf WHERE  user_id='.((int)$user_id).' AND friend_user_id='.((int)$user_friend).')';
		$res=Database::query($sql,__FILE__,__LINE__);
		$row=Database::fetch_array($res,'ASSOC');
		if (Database::num_rows($res)>0) {
			return $row['id'];	
		} else {
			return USERUNKNOW;
		}
	}
	/**
	 * get contacts id list
	 * @author isaac flores paz <florespaz@bidsoftperu.com>
	 * @param int  user id
	 * @param int group id
	 * @param string name to search
	 * @return array
	 */
	public function get_list_id_friends_by_user_id ($user_id,$id_group=null,$search_name=null) {
		$list_ids_friends=array();
		$tbl_my_friend = Database :: get_main_table(TABLE_MAIN_USER_FRIEND);
		$tbl_my_user = Database :: get_main_table(TABLE_MAIN_USER);		
		$sql='SELECT friend_user_id FROM '.$tbl_my_friend.' WHERE relation_type<>6 AND friend_user_id<>'.((int)$user_id).' AND user_id='.((int)$user_id);
		if (isset($id_group) && $id_group>0) {
			$sql.=' AND relation_type='.$id_group;
		}
		if (isset($search_name) && is_string($search_name)===true) {
			$sql.=' AND friend_user_id IN (SELECT user_id FROM '.$tbl_my_user.' WHERE concat(firstName,lastName) like concat("%","'.Database::escape_string($search_name).'","%"));';
		}
		$res=Database::query($sql,__FILE__,__LINE__);
		while ($row=Database::fetch_array($res,'ASSOC')) {
			$list_ids_friends[]=$row;
		}
		return $list_ids_friends;
	}
	/**
	 * get list web path of contacts by user id
	 * @author isaac flores paz <florespaz@bidsoftperu.com>
	 * @param int user id
	 * @param int group id
	 * @param string name to search
	 * @param array 
	 */
	public function get_list_path_web_by_user_id ($user_id,$id_group=null,$search_name=null) {
		$list_paths=array();
		$list_path_friend=array();
		$array_path_user=array();
		$combine_friend = array(); 
		$list_ids = self::get_list_id_friends_by_user_id ($user_id,$id_group,$search_name);
		if (is_array($list_ids)) {
			foreach ($list_ids as $values_ids) {
				$list_path_image_friend[] = UserManager::get_user_picture_path_by_id($values_ids['friend_user_id'],'web',false,true);
				$combine_friend=array('id_friend'=>$list_ids,'path_friend'=>$list_path_image_friend);
			}
		}
		return $combine_friend;
	}
	/**
	 * get web path of user invitate
	 * @author isaac flores paz <florespaz@bidsoftperu.com>
	 * @param int user id
	 * @return array
	 */
	public function get_list_web_path_user_invitation_by_user_id ($user_id) {
		$list_paths=array();
		$list_path_friend=array();
		$list_ids = self::get_list_invitation_of_friends_by_user_id((int)$user_id);
		foreach ($list_ids as $values_ids) {
			$list_path_image_friend[] = UserManager::get_user_picture_path_by_id($values_ids['user_sender_id'],'web',false,true);
		}
		return $list_path_image_friend;
	}	
	/**
	 * allow to sent an invitation to my contacts
	 * @author isaac flores paz <florespaz@bidsoftperu.com>
	 * @param int user id
	 * @param int user friend id
	 * @param string title of the message
	 * @param string content of the message
	 * @return boolean
	 */
	public function send_invitation_friend ($user_id,$friend_id,$message_title,$message_content) {
		$tbl_message=Database::get_main_table(TABLE_MAIN_MESSAGE);
		$current_date=date('Y-m-d H:i:s',time());
		$status_invitation=5;//status of pending invitation
		$sql_exist='SELECT COUNT(*) AS count FROM '.$tbl_message.' WHERE user_sender_id='.((int)$user_id).' AND user_receiver_id='.((int)$friend_id).' AND msg_status IN(5,6,7);';
		$res_exist=Database::query($sql_exist,__FILE__,__LINE__);
		$row_exist=Database::fetch_array($res_exist,'ASSOC');
		if ($row_exist['count']==0) {
			$sql='INSERT INTO '.$tbl_message.'(user_sender_id,user_receiver_id,msg_status,send_date,title,content) VALUES('.((int)$user_id).','.((int)$friend_id).','.((int)$status_invitation).',"'.$current_date.'","'.$message_title.'","'.$message_content.'")';
			Database::query($sql,__FILE__,__LINE__);
			return true;	
		} elseif ($row_exist['count']==1) {
			$sql_if_exist='SELECT COUNT(*) AS count FROM '.$tbl_message.' WHERE user_sender_id='.((int)$user_id).' AND user_receiver_id='.((int)$friend_id).' AND msg_status=7';
			$res_if_exist=Database::query($sql_if_exist,__FILE__,__LINE__);
			$row_if_exist=Database::fetch_array($res_if_exist,'ASSOC');
			if ($row_if_exist['count']==1) {
				$sql_if_exist_up='UPDATE '.$tbl_message.'SET msg_status=5 WHERE user_sender_id='.((int)$user_id).' AND user_receiver_id='.((int)$friend_id).';';			
				Database::query($sql_if_exist_up,__FILE__,__LINE__);
				return true;
			} else {
				return false;			
			}

		} else {
			return false;	
		}

	}
	/**
	 * Get number messages of the inbox
	 * @author isaac flores paz <florespaz@bidsoftperu.com>
	 * @param int user receiver id
	 * @return int
	 */
	public function get_message_number_invitation_by_user_id ($user_receiver_id) {
		$status_invitation=5;//status of pending invitation
		$tbl_message=Database::get_main_table(TABLE_MAIN_MESSAGE);
		$sql='SELECT COUNT(*) as count_message_in_box FROM '.$tbl_message.' WHERE user_receiver_id='.((int)$user_receiver_id).' AND msg_status=5;';
		$res=Database::query($sql,__FILE__,__LINE__);
		$row=Database::fetch_array($res,'ASSOC');
		return $row['count_message_in_box'];
	}
	/**
	 * get invitation list by user id
	 * @author isaac flores paz <florespaz@bidsoftperu.com>
	 * @param int user id
	 * @return array()
	 */
	public function get_list_invitation_of_friends_by_user_id ($user_id) {
		$list_friend_invitation=array();
		$tbl_message=Database::get_main_table(TABLE_MAIN_MESSAGE);
		$sql='SELECT user_sender_id,send_date,title,content FROM '.$tbl_message.' WHERE user_receiver_id='.((int)$user_id).' AND msg_status=5;';
		$res=Database::query($sql,__FILE__,__LINE__);
		while ($row=Database::fetch_array($res,'ASSOC')) {
			$list_friend_invitation[]=$row;
		}
		return $list_friend_invitation;
	}
	/**
	 * allow accept invitation
	 * @author isaac flores paz <florespaz@bidsoftperu.com>
	 * @param int user sender id
	 * @param int user receiver id
	 * @return void()
	 */
	public function invitation_accepted ($user_send_id,$user_receiver_id) {
		$tbl_message=Database::get_main_table(TABLE_MAIN_MESSAGE);
		$msg_status=6;// friend accepted
		$sql='UPDATE '.$tbl_message.' SET msg_status='.$msg_status.' WHERE user_sender_id='.((int)$user_send_id).' AND user_receiver_id='.((int)$user_receiver_id).';';
		Database::query($sql,__FILE__,__LINE__);
	}
	/**
	 * allow deny invitation
	 * @author isaac flores paz <florespaz@bidsoftperu.com>
	 * @param int user sender id
	 * @param int user receiver id
	 * @return void()
	 */
	public function invitation_denied($user_send_id,$user_receiver_id) {
		$tbl_message=Database::get_main_table(TABLE_MAIN_MESSAGE);
		$msg_status=7;
		$sql='UPDATE '.$tbl_message.' SET msg_status='.$msg_status.' WHERE user_sender_id='.((int)$user_send_id).' AND user_receiver_id='.((int)$user_receiver_id).';';
		Database::query($sql,__FILE__,__LINE__);		
	}
	/**
	 * allow attach to group
	 * @author isaac flores paz <florespaz@bidsoftperu.com>
	 * @param int user to qualify
	 * @param int kind of rating
	 * @return void()
	 */
	public function qualify_friend($id_friend_qualify,$type_qualify) {
		$tbl_user_friend=Database::get_main_table(TABLE_MAIN_USER_FRIEND);
		$user_id=api_get_user_id();
		$sql='UPDATE '.$tbl_user_friend.' SET relation_type='.((int)$type_qualify).' WHERE user_id='.((int)$user_id).' AND friend_user_id='.((int)$id_friend_qualify).';';
		Database::query($sql,__FILE__,__LINE__);		
	}
	/**
	 * Send invitation a your friends
	 * @author Isaac Flores Paz <isaac.flores.paz@gmail.com>
	 * @param void
	 * @return string message invitation
	 */
	function send_invitation_friend_user($userfriend_id,$subject_message='',$content_message='') {
		//$id_user_friend=array();
		$user_info=array();
		$user_info=api_get_user_info($userfriend_id);
		$succes=get_lang('MessageSentTo');
		$succes.= ' : '.$user_info['firstName'].' '.$user_info['lastName'];
		if (isset($subject_message) && isset($content_message) && isset($userfriend_id)) {			
			$send_message = MessageManager::send_message(((int)$userfriend_id),Database::escape_string($subject_message), Database::escape_string($content_message));
			if ($send_message) {
				echo Display::display_confirmation_message($succes,true);
			} else { 	
				echo Display::display_error_message($succes,true);
			}
			exit;
		} elseif(isset($userfriend_id) && !isset($subject_message)) {
			$count_is_true=false;
			$count_number_is_true=0;
			if (isset($userfriend_id) && $userfriend_id>0) {
				$user_info=array();
				$user_id=api_get_user_id();
				$user_info=api_get_user_info($user_id);
				$message_title=get_lang('Invitation');
				$message_content=$content_message;
				$count_is_true=self::send_invitation_friend(((int)$user_id),((int)$userfriend_id),Database::escape_string($message_title),Database::escape_string($message_content));
				if ($count_is_true) {
					echo Display::display_normal_message(get_lang('InvitationHasBeenSent'));
				}else {
					echo Display::display_error_message(get_lang('InvitationHasBeenNotSent'));	
				}
		
			}
		}
	}	
}