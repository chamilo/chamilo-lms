<?php //$id: $
/* For licensing terms, see /dokeos_license.txt */

/**
==============================================================================
*	This class provides methods for the social network management.
*	Include/require it in your code to use its features.
*
*	@package dokeos.library
==============================================================================
*/


// Relation type between users
define('USERUNKNOW',	'0');
define('SOCIALUNKNOW',	'1');
define('SOCIALPARENT',	'2');
define('SOCIALFRIEND',	'3');
define('SOCIALGOODFRIEND','4');
define('SOCIALENEMY',	'5');
define('SOCIALDELETED',	'6');

require_once api_get_path(LIBRARY_PATH).'usermanager.lib.php';
require_once api_get_path(LIBRARY_PATH).'message.lib.php';

class SocialManager extends UserManager {

	private function __construct() {

	}
	/**
	 * Allow to register contact to social network
	 * @author isaac flores paz <isaac.flores@dokeos.com>
	 * @author Julio Montoya <gugli100@gmail.com> Cleaning code
	 * @param int user friend id
	 * @param int user id
	 * @param int relation between users see constants definition
	 * @return void
	 */
	public static function register_friend ($friend_id,$my_user_id,$relation_type) {		
		$tbl_my_friend = Database :: get_main_table(TABLE_MAIN_USER_FRIEND);
		
		$friend_id = intval($friend_id);
		$my_user_id = intval($my_user_id);
		$relation_type = intval($relation_type);
		
		$sql = 'SELECT COUNT(*) as count FROM ' . $tbl_my_friend . ' WHERE friend_user_id=' .$friend_id.' AND user_id='.$my_user_id;
		
		$result = Database::query($sql, __FILE__, __LINE__);
		$row = Database :: fetch_array($result, 'ASSOC');
		if ($row['count'] == 0) {
			$current_date=date('Y-m-d H:i:s');
			$sql_i = 'INSERT INTO ' . $tbl_my_friend . '(friend_user_id,user_id,relation_type,last_edit)values(' . $friend_id . ','.$my_user_id.','.$relation_type.',"'.$current_date.'");';
			Database::query($sql_i, __FILE__, __LINE__);
		} else {
			$sql = 'SELECT COUNT(*) as count FROM ' . $tbl_my_friend . ' WHERE friend_user_id=' . $friend_id . ' AND user_id='.$my_user_id;
			$result = Database::query($sql, __FILE__, __LINE__);
			$row = Database :: fetch_array($result, 'ASSOC');
			if ($row['count'] == 1) {
				$sql_i = 'UPDATE ' . $tbl_my_friend . ' SET relation_type='.$relation_type.' WHERE friend_user_id=' . $friend_id.' AND user_id='.$my_user_id;
				Database::query($sql_i, __FILE__, __LINE__);
			}
		}
	}

	/**
	 * Deletes a contact	  
	 * @param int user friend id
	 * @param bool true will delete ALL friends relationship from $friend_id
	 * @author isaac flores paz <isaac.flores@dokeos.com>
	 * @author Julio Montoya <gugli100@gmail.com> Cleaning code
	 */
	public static function removed_friend ($friend_id, $real_removed = false) {
		$tbl_my_friend  = Database :: get_main_table(TABLE_MAIN_USER_FRIEND);
		$tbl_my_message = Database :: get_main_table(TABLE_MAIN_MESSAGE);
		$friend_id = intval($friend_id);
		
		if ($real_removed == true) {
			
			//Delete user friend
			$sql_delete_relationship1 = 'UPDATE ' . $tbl_my_friend .' SET relation_type='.SOCIALDELETED.' WHERE friend_user_id='.$friend_id;			
			$sql_delete_relationship2 = 'UPDATE ' . $tbl_my_friend . ' SET relation_type='.SOCIALDELETED.' WHERE user_id=' . $friend_id;
			
			Database::query($sql_delete_relationship1, __FILE__, __LINE__);
			Database::query($sql_delete_relationship2, __FILE__, __LINE__);		
			
		} else {
			$user_id=api_get_user_id();
			$sql = 'SELECT COUNT(*) as count FROM ' . $tbl_my_friend . ' WHERE user_id=' . $user_id . ' AND relation_type<>6 AND friend_user_id='.$friend_id;
			$result = Database::query($sql, __FILE__, __LINE__);
			$row = Database :: fetch_array($result, 'ASSOC');
			if ($row['count'] == 1) {
				//Delete user friend
				$sql_i = 'UPDATE ' . $tbl_my_friend .' SET relation_type='.SOCIALDELETED.' WHERE user_id=' . $user_id.' AND friend_user_id='.$friend_id;
				$sql_j = 'UPDATE ' . $tbl_my_message.' SET msg_status=7 WHERE user_receiver_id=' . $user_id.' AND user_sender_id='.$friend_id;
				//Delete user
				$sql_ij = 'UPDATE ' . $tbl_my_friend . ' SET relation_type='.SOCIALDELETED.' WHERE user_id=' . $friend_id.' AND friend_user_id='.$user_id;
				$sql_ji = 'UPDATE ' . $tbl_my_message . ' SET msg_status=7 WHERE user_receiver_id=' . $friend_id.' AND user_sender_id='.$user_id;
				Database::query($sql_i, __FILE__, __LINE__);
				Database::query($sql_j, __FILE__, __LINE__);
				Database::query($sql_ij, __FILE__, __LINE__);
				Database::query($sql_ji, __FILE__, __LINE__);
			}			
		}
		
		
		

	}
	/**
	 * Allow to see contacts list
	 * @author isaac flores paz <florespaz@bidsoftperu.com>
	 * @return array
	 */
	public static function show_list_type_friends () {
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
	public static function get_relation_type_by_name ($relation_type_name) {
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
	public static function get_relation_between_contacts ($user_id,$user_friend) {
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
	public static function get_list_id_friends_by_user_id ($user_id,$id_group=null,$search_name=null) {
		$list_ids_friends=array();
		$tbl_my_friend = Database :: get_main_table(TABLE_MAIN_USER_FRIEND);
		$tbl_my_user = Database :: get_main_table(TABLE_MAIN_USER);
		$sql='SELECT friend_user_id FROM '.$tbl_my_friend.' WHERE relation_type<>6 AND friend_user_id<>'.((int)$user_id).' AND user_id='.((int)$user_id);
		if (isset($id_group) && $id_group>0) {
			$sql.=' AND relation_type='.$id_group;
		}
		if (isset($search_name) && is_string($search_name)===true) {
			$sql.=' AND friend_user_id IN (SELECT user_id FROM '.$tbl_my_user.' WHERE '.(api_is_western_name_order() ? 'concat(firstName, lastName)' : 'concat(lastName, firstName)').' like concat("%","'.Database::escape_string($search_name).'","%"));';
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
	public static function get_list_path_web_by_user_id ($user_id,$id_group=null,$search_name=null) {
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
	public static function get_list_web_path_user_invitation_by_user_id ($user_id) {
		$list_paths=array();
		$list_path_friend=array();
		$list_ids = self::get_list_invitation_of_friends_by_user_id((int)$user_id);
		foreach ($list_ids as $values_ids) {
			$list_path_image_friend[] = UserManager::get_user_picture_path_by_id($values_ids['user_sender_id'],'web',false,true);
		}
		return $list_path_image_friend;
	}
	

	
	/**
	 * Sends an invitation to contacts
	 * @author isaac flores paz <florespaz@bidsoftperu.com>
	 * @author Julio Montya <gugli100@gmail.com> Cleaning code
	 * @param int user id
	 * @param int user friend id
	 * @param string title of the message
	 * @param string content of the message
	 * @return boolean
	 */
	public static function send_invitation_friend ($user_id,$friend_id,$message_title,$message_content) {
		$tbl_message = Database::get_main_table(TABLE_MAIN_MESSAGE);
		$user_id = intval($user_id);
		$friend_id = intval($friend_id);		
		$message_title = Database::escape_string($message_title);
		$message_content = Database::escape_string($message_content);
		
		$current_date = date('Y-m-d H:i:s',time());
		$status_invitation=5;//status of pending invitation
		$sql_exist='SELECT COUNT(*) AS count FROM '.$tbl_message.' WHERE user_sender_id='.($user_id).' AND user_receiver_id='.($friend_id).' AND msg_status IN(5,6,7);';
		error_log($sql_exist);
		$res_exist=Database::query($sql_exist,__FILE__,__LINE__);
		$row_exist=Database::fetch_array($res_exist,'ASSOC');
		
		if ($row_exist['count']==0) {			
			$sql='INSERT INTO '.$tbl_message.'(user_sender_id,user_receiver_id,msg_status,send_date,title,content) VALUES('.$user_id.','.$friend_id.','.$status_invitation.',"'.$current_date.'","'.$message_title.'","'.$message_content.'")';
			Database::query($sql,__FILE__,__LINE__);
			return true;
		} elseif ($row_exist['count']==1) {
			//invitation already exist
			$sql_if_exist='SELECT COUNT(*) AS count FROM '.$tbl_message.' WHERE user_sender_id='.$user_id.' AND user_receiver_id='.$friend_id.' AND msg_status=7';
			$res_if_exist=Database::query($sql_if_exist,__FILE__,__LINE__);
			$row_if_exist=Database::fetch_array($res_if_exist,'ASSOC');
			if ($row_if_exist['count']==1) {
				$sql_if_exist_up='UPDATE '.$tbl_message.'SET msg_status=5 WHERE user_sender_id='.$user_id.' AND user_receiver_id='.$friend_id.';';
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
	public static function get_message_number_invitation_by_user_id ($user_receiver_id) {
		$status_invitation=5;//status of pending invitation
		$tbl_message=Database::get_main_table(TABLE_MAIN_MESSAGE);
		$sql='SELECT COUNT(*) as count_message_in_box FROM '.$tbl_message.' WHERE user_receiver_id='.((int)$user_receiver_id).' AND msg_status='.MESSAGE_STATUS_INVITATION_PENDING;
		$res=Database::query($sql,__FILE__,__LINE__);
		$row=Database::fetch_array($res,'ASSOC');
		return $row['count_message_in_box'];
	}
	
	/**
	 * Get invitation list received by user
	 * @author isaac flores paz <florespaz@bidsoftperu.com>
	 * @param int user id
	 * @return array()
	 */
	public static function get_list_invitation_of_friends_by_user_id ($user_id) {
		$list_friend_invitation=array();
		$tbl_message=Database::get_main_table(TABLE_MAIN_MESSAGE);
		$sql='SELECT user_sender_id,send_date,title,content FROM '.$tbl_message.' WHERE user_receiver_id='.intval($user_id).' AND msg_status = '.MESSAGE_STATUS_INVITATION_PENDING;
		$res=Database::query($sql,__FILE__,__LINE__);
		while ($row=Database::fetch_array($res,'ASSOC')) {
			$list_friend_invitation[]=$row;
		}
		return $list_friend_invitation;
	}
	
	/**
	 * Get invitation list sent by user
	 * @author Julio Montoya <gugli100@gmail.com>
	 * @param int user id
	 * @return array()
	 */
	 
	public static function get_list_invitation_sent_by_user_id ($user_id) {
		$list_friend_invitation=array();
		$tbl_message=Database::get_main_table(TABLE_MAIN_MESSAGE);		
		$sql='SELECT user_receiver_id, send_date,title,content FROM '.$tbl_message.' WHERE user_sender_id = '.intval($user_id).' AND msg_status = '.MESSAGE_STATUS_INVITATION_PENDING;
		$res=Database::query($sql,__FILE__,__LINE__);
		while ($row=Database::fetch_array($res,'ASSOC')) {
			$list_friend_invitation[$row['user_receiver_id']]=$row;
		}
		return $list_friend_invitation;
	}
	
	/**
	 * Accepts invitation
	 * @param int user sender id
	 * @param int user receiver id
	 * @author isaac flores paz <florespaz@bidsoftperu.com>
	 * @author Julio Montoya <gugli100@gmail.com> Cleaning code
	 */
	public static function invitation_accepted ($user_send_id,$user_receiver_id) {
		$tbl_message=Database::get_main_table(TABLE_MAIN_MESSAGE);
		$sql='UPDATE '.$tbl_message.' SET msg_status='.MESSAGE_STATUS_INVITATION_ACCEPTED.' WHERE user_sender_id='.((int)$user_send_id).' AND user_receiver_id='.((int)$user_receiver_id).';';
		Database::query($sql,__FILE__,__LINE__);
	}
	/**
	 * Denies invitation	 
	 * @param int user sender id
	 * @param int user receiver id
	 * @author isaac flores paz <florespaz@bidsoftperu.com>
	 * @author Julio Montoya <gugli100@gmail.com> Cleaning code
	 */
	public static function invitation_denied ($user_send_id,$user_receiver_id) {
		$tbl_message=Database::get_main_table(TABLE_MAIN_MESSAGE);
		//$msg_status=7;
		//$sql='UPDATE '.$tbl_message.' SET msg_status='.$msg_status.' WHERE user_sender_id='.((int)$user_send_id).' AND user_receiver_id='.((int)$user_receiver_id).';';
		$sql='DELETE FROM '.$tbl_message.' WHERE user_sender_id='.((int)$user_send_id).' AND user_receiver_id='.((int)$user_receiver_id).';';
		Database::query($sql,__FILE__,__LINE__);
	}
	/**
	 * allow attach to group
	 * @author isaac flores paz <florespaz@bidsoftperu.com>
	 * @param int user to qualify
	 * @param int kind of rating
	 * @return void()
	 */
	public static function qualify_friend ($id_friend_qualify,$type_qualify) {
		$tbl_user_friend=Database::get_main_table(TABLE_MAIN_USER_FRIEND);
		$user_id=api_get_user_id();
		$sql='UPDATE '.$tbl_user_friend.' SET relation_type='.((int)$type_qualify).' WHERE user_id='.((int)$user_id).' AND friend_user_id='.((int)$id_friend_qualify).';';
		Database::query($sql,__FILE__,__LINE__);
	}
	/**
	 * Sends invitations to friends
	 * @author Isaac Flores Paz <isaac.flores.paz@gmail.com>
	 * @author Julio Montoya <gugli100@gmail.com> Cleaning code
	 * @param void
	 * @return string message invitation
	 */
	public static function send_invitation_friend_user ($userfriend_id,$subject_message='',$content_message='') {
		//$id_user_friend=array();
		$user_info = array();
		$user_info = api_get_user_info($userfriend_id);
		$succes = get_lang('MessageSentTo');
		$succes.= ' : '.api_get_person_name($user_info['firstName'], $user_info['lastName']);
		if (isset($subject_message) && isset($content_message) && isset($userfriend_id)) {			
			error_log('1');
			$send_message = MessageManager::send_message($userfriend_id, $subject_message, $content_message);
			if ($send_message) {
				echo Display::display_confirmation_message($succes,true);
			} else {
				echo Display::display_error_message($succes,true);
			}
			exit;
		} elseif (isset($userfriend_id) && !isset($subject_message)) {
			error_log('2');
			$count_is_true=false;
			$count_number_is_true=0;
			if (isset($userfriend_id) && $userfriend_id>0) {
				$message_title = get_lang('Invitation');				
				$count_is_true = self::send_invitation_friend(api_get_user_id(),$userfriend_id, $message_title, $content_message);
				if ($count_is_true) {
					echo Display::display_normal_message(get_lang('InvitationHasBeenSent'));
				}else {
					echo Display::display_error_message(get_lang('YouAlreadySentAnInvitation'));
				}

			}
		}
	}
	
	/**
	 * Get user's feeds
	 * @param   int User ID
	 * @param   int Limit of posts per feed
	 * @return  string  HTML section with all feeds included
	 * @author  Yannick Warnier
	 * @since   Dokeos 1.8.6.1
	 */
	function get_user_feeds($user, $limit=5) {
	    if (!function_exists('fetch_rss')) { return '';}
		$fields = UserManager::get_extra_fields();
	    $feed_fields = array();
	    $feeds = array();
	    $feed = UserManager::get_extra_user_data_by_field($user,'rssfeeds');
	    if(empty($feed)) { return ''; }
	    $feeds = split(';',$feed['rssfeeds']);
	    if (count($feeds)==0) { return ''; }
	    foreach ($feeds as $url) {
		if (empty($url)) { continue; }
	        $rss = fetch_rss($url);
	    	$res .= '<h2>'.$rss->channel['title'].'</h2>';
	        $res .= '<div class="social-rss-channel-items">';
	        $i = 1;
	        foreach ($rss->items as $item) {
	            if ($limit>=0 and $i>$limit) {break;}
	        	$res .= '<h3><a href="'.$item['link'].'">'.$item['title'].'</a></h3>';
	            $res .= '<div class="social-rss-item-date">'.api_get_datetime($item['date_timestamp']).'</div>';
	            $res .= '<div class="social-rss-item-content">'.$item['description'].'</div><br />';
	            $i++;
	        }
	        $res .= '</div>';
	    }
	    return $res;
	}
	
	/**
	 * Helper functions definition
	 */
	function get_logged_user_course_html($my_course, $count) {
		global $nosession;
		if (api_get_setting('use_session_mode')=='true' && !$nosession) {
			global $now, $date_start, $date_end;
		}
		//initialise
		$result = '';
		// Table definitions
		$main_user_table 		 = Database :: get_main_table(TABLE_MAIN_USER);
		$tbl_session 			 = Database :: get_main_table(TABLE_MAIN_SESSION);
		$course_database 		 = $my_course['db'];
		$course_tool_table 		 = Database :: get_course_table(TABLE_TOOL_LIST, $course_database);
		$tool_edit_table 		 = Database :: get_course_table(TABLE_ITEM_PROPERTY, $course_database);
		$course_group_user_table = Database :: get_course_table(TOOL_USER, $course_database);
	
		$user_id = api_get_user_id();
		$course_system_code = $my_course['k'];
		$course_visual_code = $my_course['c'];
		$course_title = $my_course['i'];
		$course_directory = $my_course['d'];
		$course_teacher = $my_course['t'];
		$course_teacher_email = isset($my_course['email'])?$my_course['email']:'';
		$course_info = Database :: get_course_info($course_system_code);
		//error_log(print_r($course_info,true));
		$course_access_settings = CourseManager :: get_access_settings($course_system_code);
	
		$course_visibility = $course_access_settings['visibility'];
	
		$user_in_course_status = CourseManager :: get_user_in_course_status(api_get_user_id(), $course_system_code);
		//function logic - act on the data
		$is_virtual_course = CourseManager :: is_virtual_course_from_system_code($my_course['k']);
		if ($is_virtual_course) {
			// If the current user is also subscribed in the real course to which this
			// virtual course is linked, we don't need to display the virtual course entry in
			// the course list - it is combined with the real course entry.
			$target_course_code = CourseManager :: get_target_of_linked_course($course_system_code);
			$is_subscribed_in_target_course = CourseManager :: is_user_subscribed_in_course(api_get_user_id(), $target_course_code);
			if ($is_subscribed_in_target_course) {
				return; //do not display this course entry
			}
		}
		$has_virtual_courses = CourseManager :: has_virtual_courses_from_code($course_system_code, api_get_user_id());
		if ($has_virtual_courses) {
			$return_result = CourseManager :: determine_course_title_from_course_info(api_get_user_id(), $course_info);
			$course_display_title = $return_result['title'];
			$course_display_code = $return_result['code'];
		} else {
			$course_display_title = $course_title;
			$course_display_code = $course_visual_code;
		}
		$s_course_status=$my_course['s'];
		$s_htlm_status_icon="";
	
		if ($s_course_status==1) {
			$s_htlm_status_icon=Display::return_icon('teachers.gif', get_lang('Teacher'));
		}
		if ($s_course_status==2) {
			$s_htlm_status_icon=Display::return_icon('coachs.gif', get_lang('GeneralCoach'));
		}
		if ($s_course_status==5) {
			$s_htlm_status_icon=Display::return_icon('students.gif', get_lang('Student'));
		}
	
		//display course entry
		$result .= '<div id="div_'.$count.'">';
		//$result .= '<a id="btn_'.$count.'" href="#" onclick="toogle_function(this,\''.$course_database.'\')">';
		$result .= '<h2><img src="../img/nolines_plus.gif" id="btn_'.$count.'" onclick="toogle_function(this,\''.$course_database.'\' )">';
		$result .= $s_htlm_status_icon;
	
		//show a hyperlink to the course, unless the course is closed and user is not course admin
		if ($course_visibility != COURSE_VISIBILITY_CLOSED || $user_in_course_status == COURSEMANAGER) {
			$result .= '<a href="javascript:void(0)" id="ln_'.$count.'"  onclick=toogle_function(this,\''.$course_database.'\');>&nbsp;'.$course_title.'</a></h2>';
			/*
			if(api_get_setting('use_session_mode')=='true' && !$nosession) {
				if(empty($my_course['id_session'])) {
					$my_course['id_session'] = 0;
				}
				if($user_in_course_status == COURSEMANAGER || ($date_start <= $now && $date_end >= $now) || $date_start=='0000-00-00') {
					//$result .= '<a href="'.api_get_path(WEB_COURSE_PATH).$course_directory.'/?id_session='.$my_course['id_session'].'">'.$course_display_title.'</a>';
					$result .= '<a href="#">'.$course_display_title.'</a>';
				}
			} else {
				//$result .= '<a href="'.api_get_path(WEB_COURSE_PATH).$course_directory.'/">'.$course_display_title.'</a>';
				$result .= '<a href="'.api_get_path(WEB_COURSE_PATH).$course_directory.'/">'.$course_display_title.'</a>';
			}*/
		} else {
			$result .= $course_display_title." "." ".get_lang('CourseClosed')."";
		}
		// show the course_code and teacher if chosen to display this
		// we dont need this!
		/*
				if (api_get_setting('display_coursecode_in_courselist') == 'true' OR api_get_setting('display_teacher_in_courselist') == 'true') {
					$result .= '<br />';
				}
				if (api_get_setting('display_coursecode_in_courselist') == 'true') {
					$result .= $course_display_code;
				}
				if (api_get_setting('display_coursecode_in_courselist') == 'true' AND api_get_setting('display_teacher_in_courselist') == 'true') {
					$result .= ' &ndash; ';
				}
				if (api_get_setting('display_teacher_in_courselist') == 'true') {
					$result .= $course_teacher;
					if(!empty($course_teacher_email)) {
						$result .= ' ('.$course_teacher_email.')';
					}
				}
		*/
		$current_course_settings = CourseManager :: get_access_settings($my_course['k']);
		// display the what's new icons
		//	$result .= show_notification($my_course);
		if ((CONFVAL_showExtractInfo == SCRIPTVAL_InCourseList || CONFVAL_showExtractInfo == SCRIPTVAL_Both) && $nbDigestEntries > 0) {
			reset($digest);
			$result .= '<ul>';
			while (list ($key2) = each($digest[$thisCourseSysCode])) {
				$result .= '<li>';
				if ($orderKey[1] == 'keyTools') {
					$result .= "<a href=\"$toolsList[$key2] [\"path\"] $thisCourseSysCode \">";
					$result .= "$toolsList[$key2][\"name\"]</a>";
				} else {
					$result .= format_locale_date(CONFVAL_dateFormatForInfosFromCourses, strtotime($key2));
				}
				$result .= '</li>';
				$result .= '<ul>';
				reset($digest[$thisCourseSysCode][$key2]);
				while (list ($key3, $dataFromCourse) = each($digest[$thisCourseSysCode][$key2])) {
					$result .= '<li>';
					if ($orderKey[2] == 'keyTools') {
						$result .= "<a href=\"$toolsList[$key3] [\"path\"] $thisCourseSysCode \">";
						$result .= "$toolsList[$key3][\"name\"]</a>";
					} else {
						$result .= format_locale_date(CONFVAL_dateFormatForInfosFromCourses, strtotime($key3));
					}
					$result .= '<ul compact="compact">';
					reset($digest[$thisCourseSysCode][$key2][$key3]);
					while (list ($key4, $dataFromCourse) = each($digest[$thisCourseSysCode][$key2][$key3])) {
						$result .= '<li>';
						$result .= htmlspecialchars(substr(strip_tags($dataFromCourse), 0, CONFVAL_NB_CHAR_FROM_CONTENT));
						$result .= '</li>';
					}
					$result .= '</ul>';
					$result .= '</li>';
				}
				$result .= '</ul>';
				$result .= '</li>';
			}
			$result .= '</ul>';
		}
		$result .= '</li>';
		$result .= '</div>';
	
		if (api_get_setting('use_session_mode')=='true' && !$nosession) {
			$session = '';
			$active = false;
			if (!empty($my_course['session_name'])) {
	
				// Request for the name of the general coach
				$sql = 'SELECT lastname, firstname
						FROM '.$tbl_session.' ts  LEFT JOIN '.$main_user_table .' tu
						ON ts.id_coach = tu.user_id
						WHERE ts.id='.(int) $my_course['id_session']. ' LIMIT 1';
				$rs = Database::query($sql, __FILE__, __LINE__);
				$sessioncoach = Database::store_result($rs);
				$sessioncoach = $sessioncoach[0];
	
				$session = array();
				$session['title'] = $my_course['session_name'];
				if ( $my_course['date_start']=='0000-00-00' ) {
					$session['dates'] = get_lang('WithoutTimeLimits');
					if ( api_get_setting('show_session_coach') === 'true' ) {
						$session['coach'] = get_lang('GeneralCoach').': '.api_get_person_name($sessioncoach['firstname'], $sessioncoach['lastname']);
					}
					$active = true;
				} else {
					$session ['dates'] = ' - '.get_lang('From').' '.$my_course['date_start'].' '.get_lang('To').' '.$my_course['date_end'];
					if ( api_get_setting('show_session_coach') === 'true' ) {
						$session['coach'] = get_lang('GeneralCoach').': '.api_get_person_name($sessioncoach['firstname'], $sessioncoach['lastname']);
					}
					$active = ($date_start <= $now && $date_end >= $now)?true:false;
				}
			}
			$output = array ($my_course['user_course_cat'], $result, $my_course['id_session'], $session, 'active'=>$active);
		} else {
			$output = array ($my_course['user_course_cat'], $result);
		}
		//$my_course['creation_date'];
		return $output;
	}
	
	public static function show_social_menu() {
		/*
		echo '<div class="actions">'; 
			echo '<a href="'.api_get_path(WEB_PATH).'main/social/profile.php">'.Display::return_icon('shared_profile.png').' '.get_lang('ViewMySharedProfile').'</a>';			
			echo '<a href="'.api_get_path(WEB_PATH).'main/messages/inbox.php?f=social">'.Display::return_icon('inbox.png').' '.get_lang('Messages').'</a>';
			echo '<a href="'.api_get_path(WEB_PATH).'main/social/friends.php">'.Display::return_icon('lp_users.png').' '.get_lang('Friends').'</a>';
			echo '<a href="'.api_get_path(WEB_PATH).'main/social/invitations.php">'.Display::return_icon('lp_users.png').' '.get_lang('Invitations').'</a>';
			echo '<a href="'.api_get_path(WEB_PATH).'main/social/groups.php">'.Display::return_icon('group.gif').' '.get_lang('Groups').'</a>';
			echo '<a href="'.api_get_path(WEB_PATH).'main/social/search.php">'.Display::return_icon('search.gif').' '.get_lang('Search').'</a>';
			echo '<a href="'.api_get_path(WEB_PATH).'main/auth/profile.php?show=1">'.Display::return_icon('edit.gif').' '.get_lang('EditProfile').'</a>';	
			
			echo '<span style="float:right; padding-top:7px;">'.
				 '<a href="/main/auth/profile.php?show=1">'.Display::return_icon('edit.gif').' '.get_lang('Configuration').'</a>';
				 '</span>';
				 
		echo '</div>';*/
	}
}