<?php
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
	 *@author isaac flores paz <isaac.flores@dokeos.com>
	 *@param int user friend id
	 *@param int user id
	 *@param int kind of relation between users
	 *@return void
	 */
	public function register_friend ($friend_id,$my_user_id,$relation_type) {
		$tbl_my_friend = Database :: get_main_table(TABLE_MAIN_USER_FRIEND);
		$sql = 'SELECT COUNT(*) as count FROM ' . $tbl_my_friend . ' WHERE friend_user_id=' . $friend_id.' AND user_id='.$my_user_id;
		$result = api_sql_query($sql, __FILE__, __LINE__);
		$row = Database :: fetch_array($result, 'ASSOC');
		if ($row['count'] == 0) {
			$sql_i = 'INSERT INTO ' . $tbl_my_friend . '(friend_user_id,user_id,relation_type)values(' . $friend_id . ','.$my_user_id.','.$relation_type.');';
			api_sql_query($sql_i, __FILE__, __LINE__);
		} else {
			$sql = 'SELECT COUNT(*) as count FROM ' . $tbl_my_friend . ' WHERE friend_user_id=' . $friend_id . ' AND user_id='.$my_user_id;
			$result = api_sql_query($sql, __FILE__, __LINE__);
			$row = Database :: fetch_array($result, 'ASSOC');
			if ($row['count'] == 1) {
				$sql_i = 'UPDATE ' . $tbl_my_friend . ' SET relation_type='.$relation_type.' WHERE friend_user_id=' . $friend_id.' AND user_id='.$my_user_id;
				api_sql_query($sql_i, __FILE__, __LINE__);
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
		$sql = 'SELECT COUNT(*) as count FROM ' . $tbl_my_friend . ' WHERE user_id=' . $user_id . ' AND relation_type<>6 AND friend_user_id='.$friend_id;
		$result = api_sql_query($sql, __FILE__, __LINE__);
		$row = Database :: fetch_array($result, 'ASSOC');
		if ($row['count'] == 1) {
			$sql_i = 'UPDATE ' . $tbl_my_friend . ' SET relation_type=6 WHERE user_id=' . $user_id.' AND friend_user_id='.$friend_id;
			$sql_j = 'UPDATE ' . $tbl_my_message . ' SET msg_status=7 WHERE user_receiver_id=' . $user_id.' AND user_sender_id='.$friend_id;
			api_sql_query($sql_i, __FILE__, __LINE__);
			api_sql_query($sql_j, __FILE__, __LINE__);
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
		$result=api_sql_query($sql,__FILE__,__LINE__);
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
		$sql= 'SELECT rt.id FROM '.$tbl_my_friend_relation_type.' rt ' .
			  'WHERE rt.id=(SELECT uf.relation_type FROM '.$tbl_my_friend.' uf WHERE  user_id='.$user_id.' AND friend_user_id='.$user_friend.')';
		$res=api_sql_query($sql,__FILE__,__LINE__);
		$row=Database::fetch_array($res,'ASSOC');
		if (Database::num_rows($res)>0) {
			return $row['id'];	
		} else {
			return self::SOCIALUNKNOW;
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
		$sql='SELECT friend_user_id FROM '.$tbl_my_friend.' WHERE relation_type<>6 AND friend_user_id<>'.$user_id.' AND user_id='.$user_id;
		if (isset($id_group) && $id_group>0) {
			$sql.=' AND relation_type='.$id_group;
		}
		if (isset($search_name) && is_string($search_name)===true) {
			$sql.=' AND friend_user_id IN (SELECT user_id FROM '.$tbl_my_user.' WHERE concat(firstName,lastName) like concat("%","'.$search_name.'","%"));';
		}
		$res=api_sql_query($sql,__FILE__,__LINE__);
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
		$list_ids = self::get_list_id_friends_by_user_id ($user_id,$id_group,$search_name);
		foreach ($list_ids as $values_ids) {
			$list_path_image_friend[] = UserManager::get_user_picture_path_by_id($values_ids['friend_user_id'],'web',false,true);
			$combine_friend=array('id_friend'=>$list_ids,'path_friend'=>$list_path_image_friend);
		}
		return $combine_friend;
	}
	/**
	 * get web path of user invitate
	 * @author isaac flores paz <florespaz@bidsoftperu.com>
	 * @param int user id
	 * @param array
	 */
	public function get_list_web_path_user_invitation_by_user_id ($user_id) {
		$list_paths=array();
		$list_path_friend=array();
		$list_ids = self::get_list_invitation_of_friends_by_user_id($user_id);
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
		$sql_exist='SELECT COUNT(*) AS count FROM '.$tbl_message.' WHERE user_sender_id='.$user_id.' AND user_receiver_id='.$friend_id.' AND msg_status IN(5,6,7);';
		$res_exist=api_sql_query($sql_exist,__FILE__,__LINE__);
		$row_exist=Database::fetch_array($res_exist,'ASSOC');
		if ($row_exist['count']==0) {
			$sql='INSERT INTO '.$tbl_message.'(user_sender_id,user_receiver_id,msg_status,send_date,title,content) VALUES('.$user_id.','.$friend_id.','.$status_invitation.',"'.$current_date.'","'.$message_title.'","'.$message_content.'")';
			api_sql_query($sql,__FILE__,__LINE__);
			return true;	
		} elseif($row_exist['count']==1) {
			$sql_if_exist='SELECT COUNT(*) AS count FROM '.$tbl_message.' WHERE user_sender_id='.$user_id.' AND user_receiver_id='.$friend_id.' AND msg_status=7';
			$res_if_exist=api_sql_query($sql_if_exist,__FILE__,__LINE__);
			$row_if_exist=Database::fetch_array($res_if_exist,'ASSOC');
			if ($row_if_exist['count']==1) {
				$sql_if_exist_up='UPDATE '.$tbl_message.'SET msg_status=5 WHERE user_sender_id='.$user_id.' AND user_receiver_id='.$friend_id.';';			
				api_sql_query($sql_if_exist_up,__FILE__,__LINE__);
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
		$sql='SELECT COUNT(*) as count_message_in_box FROM '.$tbl_message.' WHERE user_receiver_id='.$user_receiver_id.' AND msg_status=5;';
		$res=api_sql_query($sql,__FILE__,__LINE__);
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
		$sql='SELECT user_sender_id,send_date,title,content FROM '.$tbl_message.' WHERE user_receiver_id='.$user_id.' AND msg_status=5;';
		$res=api_sql_query($sql,__FILE__,__LINE__);
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
		$sql='UPDATE '.$tbl_message.' SET msg_status='.$msg_status.' WHERE user_sender_id='.$user_send_id.' AND user_receiver_id='.$user_receiver_id.';';
		api_sql_query($sql,__FILE__,__LINE__);
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
		$sql='UPDATE '.$tbl_message.' SET msg_status='.$msg_status.' WHERE user_sender_id='.$user_send_id.' AND user_receiver_id='.$user_receiver_id.';';
		api_sql_query($sql,__FILE__,__LINE__);		
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
		$sql='UPDATE '.$tbl_user_friend.' SET relation_type='.$type_qualify.' WHERE user_id='.$user_id.' AND friend_user_id='.$id_friend_qualify.';';
		api_sql_query($sql,__FILE__,__LINE__);		
	}
	
}
?>