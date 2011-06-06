<?php //$id: $
/* For licensing terms, see /license.txt */

/**
*	This class provides methods for the social network management.
*	Include/require it in your code to use its features.
*
*	@package chamilo.social
*/

//PLUGIN PLACES
define('SOCIAL_LEFT_PLUGIN',	1);
define('SOCIAL_CENTER_PLUGIN',	2);
define('SOCIAL_RIGHT_PLUGIN',	3);

require_once api_get_path(LIBRARY_PATH).'usermanager.lib.php';
require_once api_get_path(LIBRARY_PATH).'message.lib.php';

class SocialManager extends UserManager {

	private function __construct() {
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
		$result=Database::query($sql);
		while ($row=Database::fetch_array($result,'ASSOC')) {
			$friend_relation_list[]=$row;
		}
		$count_list=count($friend_relation_list);
		if ($count_list==0) {
			$friend_relation_list[]=get_lang('Unknown');
		} else {
			return $friend_relation_list;
		}

	}
	/**
	 * Get relation type contact by name
	 * @param string names of the kind of relation
	 * @return int
	 * @author isaac flores paz <florespaz@bidsoftperu.com>
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
	 * @param int user id
	 * @param int user friend id
	 * @param string
	 * @author isaac flores paz <florespaz@bidsoftperu.com>
	 */
	public static function get_relation_between_contacts ($user_id,$user_friend) {
		$tbl_my_friend_relation_type = Database :: get_main_table(TABLE_MAIN_USER_FRIEND_RELATION_TYPE);
		$tbl_my_friend = Database :: get_main_table(TABLE_MAIN_USER_REL_USER);
		$sql= 'SELECT rt.id as id FROM '.$tbl_my_friend_relation_type.' rt ' .
			  'WHERE rt.id=(SELECT uf.relation_type FROM '.$tbl_my_friend.' uf WHERE  user_id='.((int)$user_id).' AND friend_user_id='.((int)$user_friend).' AND uf.relation_type <> '.USER_RELATION_TYPE_RRHH.' )';
		$res=Database::query($sql);
		if (Database::num_rows($res)>0) {
			$row=Database::fetch_array($res,'ASSOC');
			return $row['id'];
		} else {
			return USER_UNKNOW;
		}
	}

	/**
	 * Gets friends id list
	 * @param int  user id
	 * @param int group id
	 * @param string name to search
	 * @param bool true will load firstname, lastname, and image name
	 * @return array
	 * @author Julio Montoya <gugli100@gmail.com> Cleaning code, function renamed, $load_extra_info option added
	 * @author isaac flores paz <florespaz@bidsoftperu.com>
	 */
	public static function get_friends($user_id, $id_group=null, $search_name=null, $load_extra_info = true) {
		$list_ids_friends=array();
		$tbl_my_friend = Database :: get_main_table(TABLE_MAIN_USER_REL_USER);
		$tbl_my_user = Database :: get_main_table(TABLE_MAIN_USER);
		$sql='SELECT friend_user_id FROM '.$tbl_my_friend.' WHERE relation_type NOT IN ('.USER_RELATION_TYPE_DELETED.', '.USER_RELATION_TYPE_RRHH.') AND friend_user_id<>'.((int)$user_id).' AND user_id='.((int)$user_id);
		if (isset($id_group) && $id_group>0) {
			$sql.=' AND relation_type='.$id_group;
		}
		if (isset($search_name) && is_string($search_name)===true) {
			$search_name = trim($search_name);
			$search_name = str_replace(' ', '', $search_name);
			//$sql.=' AND friend_user_id IN (SELECT user_id FROM '.$tbl_my_user.' WHERE '.(api_is_western_name_order() ? 'concat(firstName, lastName)' : 'concat(lastName, firstName)').' like concat("%","'.Database::escape_string($search_name).'","%"));';
			$sql.=' AND friend_user_id IN (SELECT user_id FROM '.$tbl_my_user.' WHERE firstName LIKE "%'.Database::escape_string($search_name).'%" OR lastName LIKE "%'.Database::escape_string($search_name).'%"   OR    '.(api_is_western_name_order() ? 'concat(firstName, lastName)' : 'concat(lastName, firstName)').' like concat("%","'.Database::escape_string($search_name).'","%")    ) ';
		}

		$res = Database::query($sql);
		while ($row = Database::fetch_array($res, 'ASSOC')) {
			if ($load_extra_info) {
				$path = UserManager::get_user_picture_path_by_id($row['friend_user_id'],'web',false,true);
				$my_user_info = api_get_user_info($row['friend_user_id']);
				$list_ids_friends[] = array('friend_user_id'=>$row['friend_user_id'],'firstName'=>$my_user_info['firstName'] , 'lastName'=>$my_user_info['lastName'], 'username'=>$my_user_info['username'], 'image'=>$path['file']);
			} else {
				$list_ids_friends[] = $row;
			}
		}
		return $list_ids_friends;
	}


	/**
	 * get list web path of contacts by user id
	 * @param int user id
	 * @param int group id
	 * @param string name to search
	 * @param array
	 * @author isaac flores paz <florespaz@bidsoftperu.com>
	 */
	public static function get_list_path_web_by_user_id ($user_id,$id_group=null,$search_name=null) {
		$list_paths=array();
		$list_path_friend=array();
		$array_path_user=array();
		$combine_friend = array();
		$list_ids = self::get_friends($user_id,$id_group,$search_name);
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
	 * @param int user id
	 * @param int user friend id
	 * @param string title of the message
	 * @param string content of the message
	 * @return boolean
	 * @author isaac flores paz <florespaz@bidsoftperu.com>
	 * @author Julio Montoya <gugli100@gmail.com> Cleaning code
	 */
	public static function send_invitation_friend ($user_id,$friend_id,$message_title,$message_content) {
		$tbl_message = Database::get_main_table(TABLE_MAIN_MESSAGE);
		$user_id = intval($user_id);
		$friend_id = intval($friend_id);
		$message_title   = Database::escape_string($message_title);
		$message_content = Database::escape_string($message_content);

		$current_date = date('Y-m-d H:i:s',time());
		$sql_exist='SELECT COUNT(*) AS count FROM '.$tbl_message.' WHERE user_sender_id='.($user_id).' AND user_receiver_id='.($friend_id).' AND msg_status IN(5,6,7);';

		$res_exist=Database::query($sql_exist);
		$row_exist=Database::fetch_array($res_exist,'ASSOC');

		if ($row_exist['count']==0) {
		    		    
			$sql='INSERT INTO '.$tbl_message.'(user_sender_id,user_receiver_id,msg_status,send_date,title,content) VALUES('.$user_id.','.$friend_id.','.MESSAGE_STATUS_INVITATION_PENDING.',"'.$current_date.'","'.$message_title.'","'.$message_content.'")';
			Database::query($sql);			
			
			require_once api_get_path(LIBRARY_PATH).'notification.lib.php';
			$notification = new Notification();
		    $notification->save_invitation_notifications(array($friend_id), $message_title, $message_content);    		
		    	
			return true;
		} else {
			//invitation already exist
			$sql_if_exist='SELECT COUNT(*) AS count, id FROM '.$tbl_message.' WHERE user_sender_id='.$user_id.' AND user_receiver_id='.$friend_id.' AND msg_status=7';
			$res_if_exist=Database::query($sql_if_exist);
			$row_if_exist=Database::fetch_array($res_if_exist,'ASSOC');
			if ($row_if_exist['count']==1) {
				$sql_if_exist_up='UPDATE '.$tbl_message.'SET msg_status=5, content = "'.$message_content.'"  WHERE user_sender_id='.$user_id.' AND user_receiver_id='.$friend_id.' AND msg_status = 7 ';
				//$sql_if_exist_up='UPDATE '.$tbl_message.'SET msg_status=5, set content = '.$message_content.' WHERE id='.$row_if_exist['id'].'';
				Database::query($sql_if_exist_up);
				return true;
			} else {
				return false;
			}
		}
	}
	/**
	 * Get number messages of the inbox
	 * @author isaac flores paz <florespaz@bidsoftperu.com>
	 * @param int user receiver id
	 * @return int
	 */
	public static function get_message_number_invitation_by_user_id ($user_receiver_id) {
		$tbl_message=Database::get_main_table(TABLE_MAIN_MESSAGE);
		$sql='SELECT COUNT(*) as count_message_in_box FROM '.$tbl_message.' WHERE user_receiver_id='.intval($user_receiver_id).' AND msg_status='.MESSAGE_STATUS_INVITATION_PENDING;
		$res=Database::query($sql);
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
		$res=Database::query($sql);
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
		$res=Database::query($sql);
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
		Database::query($sql);
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
		Database::query($sql);
	}
	/**
	 * allow attach to group
	 * @author isaac flores paz <florespaz@bidsoftperu.com>
	 * @param int user to qualify
	 * @param int kind of rating
	 * @return void()
	 */
	public static function qualify_friend ($id_friend_qualify,$type_qualify) {
		$tbl_user_friend=Database::get_main_table(TABLE_MAIN_USER_REL_USER);
		$user_id=api_get_user_id();
		$sql='UPDATE '.$tbl_user_friend.' SET relation_type='.((int)$type_qualify).' WHERE user_id='.((int)$user_id).' AND friend_user_id='.((int)$id_friend_qualify).';';
		Database::query($sql);
	}
	/**
	 * Sends invitations to friends
	 * @author Isaac Flores Paz <isaac.flores.paz@gmail.com>
	 * @author Julio Montoya <gugli100@gmail.com> Cleaning code
	 * @param void
	 * @return string message invitation
	 */
	public static function send_invitation_friend_user ($userfriend_id,$subject_message='',$content_message='') {
		global $charset;
		//$id_user_friend=array();
		$user_info = array();
		$user_info = api_get_user_info($userfriend_id);
		$succes = get_lang('MessageSentTo');
		$succes.= ' : '.api_get_person_name($user_info['firstName'], $user_info['lastName']);
		if (isset($subject_message) && isset($content_message) && isset($userfriend_id)) {
			$send_message = MessageManager::send_message($userfriend_id, $subject_message, $content_message);
			if ($send_message) {
				echo Display::display_confirmation_message($succes,true);
			} else {
				echo Display::display_error_message($succes,true);
			}
			return false;
		} elseif (isset($userfriend_id) && !isset($subject_message)) {
			$count_is_true=false;
			$count_number_is_true=0;
			if (isset($userfriend_id) && $userfriend_id>0) {
				$message_title = get_lang('Invitation');
				$count_is_true = self::send_invitation_friend(api_get_user_id(), $userfriend_id, $message_title, $content_message);

				if ($count_is_true) {
					echo Display::display_normal_message(api_htmlentities(get_lang('InvitationHasBeenSent'), ENT_QUOTES,$charset),false);
				} else {
					echo Display::display_error_message(api_htmlentities(get_lang('YouAlreadySentAnInvitation'), ENT_QUOTES,$charset),false);
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
	public static function get_user_feeds($user, $limit=5) {
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
	        $rss = @fetch_rss($url);
	        $i = 1;
			if (!empty($rss->items)) {
			    $icon_rss = '';
			    if (!empty($feed)) {
                    $icon_rss = Display::url(Display::return_icon('rss.png', '', array(), 32), Security::remove_XSS($feed['rssfeeds']), array('target'=>'_blank'));
                }
				$res .= '<h2>'.$rss->channel['title'].''.$icon_rss.'</h2>';
	        	$res .= '<div class="social-rss-channel-items">';
		        foreach ($rss->items as $item) {
		            if ($limit>=0 and $i>$limit) {break;}
		        	$res .= '<h3><a href="'.$item['link'].'">'.$item['title'].'</a></h3>';
		            $res .= '<div class="social-rss-item-date">'.api_get_datetime($item['date_timestamp']).'</div>';
		            $res .= '<div class="social-rss-item-content">'.$item['description'].'</div><br />';
		            $i++;
		        }
		        $res .= '</div>';
			}
	    }
	    return $res;
	}

	/**
	 * Helper functions definition
	 */
	public static function get_logged_user_course_html($my_course, $count) {
		global $nosession, $nbDigestEntries, $orderKey, $digest, $thisCourseSysCode;
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
			$s_htlm_status_icon=Display::return_icon('course.gif', get_lang('Course')).' '.Display::return_icon('teachers.gif', get_lang('Status').': '.get_lang('Teacher'),array('style'=>'width:11px; height:11px'));
		}
		if ($s_course_status==2) {
			$s_htlm_status_icon=Display::return_icon('course.gif', get_lang('Course')).' '.Display::return_icon('coachs.gif', get_lang('Status').': '.get_lang('GeneralCoach'),array('style'=>'width:11px; height:11px'));
		}
		if ($s_course_status==5) {
			$s_htlm_status_icon=Display::return_icon('course.gif', get_lang('Course')).' '.Display::return_icon('students.gif', get_lang('Status').': '.get_lang('Student'),array('style'=>'width:11px; height:11px'));
		}

		//display course entry
		$result .= '<div id="div_'.$count.'">';
		$result .= '<h2><img src="../img/nolines_plus.gif" id="btn_'.$count.'" onclick="toogle_course(this,\''.$course_database.'\' )">';
		$result .= $s_htlm_status_icon;

		//show a hyperlink to the course, unless the course is closed and user is not course admin
		if ($course_visibility != COURSE_VISIBILITY_CLOSED || $user_in_course_status == COURSEMANAGER) {
			$result .= '<a href="javascript:void(0)" id="ln_'.$count.'"  onclick=toogle_course(this,\''.$course_database.'\');>&nbsp;'.$course_title.'</a></h2>';
		} else {
			$result .= $course_display_title." "." ".get_lang('CourseClosed')."";
		}
		$current_course_settings = CourseManager :: get_access_settings($my_course['k']);
		// display the what's new icons
		if ((CONFVAL_showExtractInfo == SCRIPTVAL_InCourseList || CONFVAL_showExtractInfo == SCRIPTVAL_Both) && $nbDigestEntries > 0) {
			reset($digest);
			$result .= '<ul>';
			while (list ($key2) = each($digest[$thisCourseSysCode])) {
				$result .= '<li>';
				if ($orderKey[1] == 'keyTools') {
					$result .= "<a href=\"$toolsList[$key2] [\"path\"] $thisCourseSysCode \">";
					$result .= "$toolsList[$key2][\"name\"]</a>";
				} else {
					$result .= api_convert_and_format_date($key2, DATE_FORMAT_LONG, date_default_timezone_get());
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
						$result .= api_convert_and_format_date($key3, DATE_FORMAT_LONG, date_default_timezone_get());
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
				$rs = Database::query($sql);
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

	/**
	 * Shows the right menu of the Social Network tool
	 *
	 * @param string highlight link possible values: group_add, home, messages, messages_inbox, messages_compose ,messages_outbox ,invitations, shared_profile, friends, groups search
	 * @param int group id
	 * @param int user id
	 * @param bool show profile or not (show or hide the user image/information)
	 *
	 */
	public static function show_social_menu($show = '', $group_id = 0, $user_id = 0, $show_full_profile = false) {

		if (empty($user_id)) {
			$user_id = api_get_user_id();
		}

		$show_groups      = array('groups', 'group_messages', 'messages_list', 'group_add', 'mygroups', 'group_edit', 'member_list', 'invite_friends', 'waiting_list', 'browse_groups');
		$show_messages    = array('messages', 'messages_inbox', 'messages_outbox', 'messages_compose');

		// get count unread message and total invitations
		$count_unread_message = MessageManager::get_number_of_messages(true);
		$count_unread_message = (!empty($count_unread_message)?' ('.$count_unread_message.')':'');

		$number_of_new_messages_of_friend	= SocialManager::get_message_number_invitation_by_user_id(api_get_user_id());
		$group_pending_invitations = GroupPortalManager::get_groups_by_user(api_get_user_id(), GROUP_USER_PERMISSION_PENDING_INVITATION,false);
		$group_pending_invitations = count($group_pending_invitations);
		$total_invitations = $number_of_new_messages_of_friend + $group_pending_invitations;
		$total_invitations = (!empty($total_invitations)?' ('.$total_invitations.')':'');

		// Everybody can create groups
		/*
		if (api_get_setting('allow_students_to_create_groups_in_social') == 'true') {
			$create_group_item =  '<li class="social-menu-sub-level"><a href="'.api_get_path(WEB_PATH).'main/social/group_add.php">'.Display::return_icon('group_add.png',get_lang('CreateASocialGroup'),array('hspace'=>'6','style'=>'float:left')).'<span class="'.($show=='group_add'?'social-menu-text-active':'social-menu-text4').'" >'.get_lang('CreateASocialGroup').'</span></a></li>';
		} else {
			// Only admins and teachers can create groups
			if (api_is_allowed_to_edit(null,true)) {
				$create_group_item =  '<li class="social-menu-sub-level"><a href="'.api_get_path(WEB_PATH).'main/social/group_add.php">'.Display::return_icon('group_add.png',get_lang('CreateASocialGroup'),array('hspace'=>'6','style'=>'float:left')).'<span class="'.($show=='group_add'?'social-menu-text-active':'social-menu-text4').'" >'.get_lang('CreateASocialGroup').'</span></a></li>';
			}
		}*/

		echo '<div class="social-menu">';
		
	    echo '<script type="text/javascript">      
            function show_icon_edit(element_html) { 
                ident="#edit_image";
                $(ident).show();
            }       
            
            function hide_icon_edit(element_html)  {
                ident="#edit_image";
                $(ident).hide();
            }               
            </script>';
		      

	  	if (in_array($show, $show_groups) && !empty($group_id)) {
			//--- Group image
			$group_info = GroupPortalManager::get_group_data($group_id);
			$big		= GroupPortalManager::get_picture_group($group_id, $group_info['picture_uri'],160,GROUP_IMAGE_SIZE_BIG);
			$original	= GroupPortalManager::get_picture_group($group_id, $group_info['picture_uri'],'',GROUP_IMAGE_SIZE_ORIGINAL);            
	
			echo '<div class="social-content-image">';                
				echo '<div class="social-background-content" onmouseout="hide_icon_edit()" onmouseover="show_icon_edit()"><center>';				
				echo Display::url('<img src='.$big['file'].' class="social-groups-image" /> </a><br /><br />', api_get_path(WEB_PATH).'main/social/groups.php?id='.$group_id);
				if (GroupPortalManager::is_group_admin($group_id, api_get_user_id())) {
					echo '<div id="edit_image" class="hidden_message" style="display:none"><a href="'.api_get_path(WEB_PATH).'main/social/group_edit.php?id='.$group_id.'">'.get_lang('EditGroup').'</a></div>';
				}
	    		echo '</center></div>';
		  	echo '</div>';
	  	} else {
	  		$img_array = UserManager::get_user_picture_path_by_id($user_id,'web',true,true);
			$big_image = UserManager::get_picture_user($user_id, $img_array['file'],'', USER_IMAGE_SIZE_BIG);
			$big_image = $big_image['file'].$big_image['dir'];

	  		//--- User image
			echo '<div class="social-content-image">';
				echo '<div class="social-background-content" onmouseout="hide_icon_edit()" onmouseover="show_icon_edit()"><center>';

					if ($img_array['file'] != 'unknown.jpg') {
		    	  		echo '<a class="thickbox" href="'.$big_image.'"><img src='.$img_array['dir'].$img_array['file'].' /> </a>';
					} else {
						echo '<img src='.$img_array['dir'].$img_array['file'].' width="110px" />';
					}
					if (api_get_user_id() == $user_id) {
						echo '<div id="edit_image" class="hidden_message" style="display:none"><a href="'.api_get_path(WEB_PATH).'main/auth/profile.php">'.get_lang('EditProfile').'</a></div>';
					}

	    	  	echo '</center></div>';
		  	echo '</div>';
	  	}

		if (!in_array($show, array('shared_profile', 'groups', 'group_edit', 'member_list','waiting_list','invite_friends'))) {
			echo '<div align="center" class="social-menu-title">';
			echo '<span class="social-menu-text1">'.get_lang('Menu').'</span>';
			echo '</div>';

	        echo '<div><ul>';
	        	                	
            echo '<li><a href="'.api_get_path(WEB_PATH).'main/social/home.php">'.Display::return_icon('home.png',get_lang('Home'),array('hspace'=>'6')).'<span class="'.($show=='home'?'social-menu-text-active':'social-menu-text4').'" >'.get_lang('Home').'</span></a></li>';
            echo '<li><a href="'.api_get_path(WEB_PATH).'main/messages/inbox.php?f=social">'.Display::return_icon('instant_message.png',get_lang('Messages'),array('hspace'=>'6')).'<span class="'.($show=='messages'?'social-menu-text-active':'social-menu-text4').'" >'.get_lang('Messages').$count_unread_message.'</span></a></li>';
            
		    if (in_array($show, $show_messages)) {
				/*echo '<li><ul>';
					echo '<li class="social-menu-sub-level"><a href="'.api_get_path(WEB_PATH).'main/messages/new_message.php?f=social">'.Display::return_icon('compose_message.png', get_lang('ComposeMessage'), array('hspace'=>'6','style'=>'float:left')).'<span class="'.($show=='messages_compose'?'social-menu-text-active':'social-menu-text4').'" >'.get_lang('ComposeMessage').'</span></a></li>';
					echo '<li class="social-menu-sub-level"><a href="'.api_get_path(WEB_PATH).'main/messages/inbox.php?f=social">'.Display::return_icon('inbox.png', get_lang('Inbox'), array('hspace'=>'6')).'<span class="'.($show=='messages_inbox'?'social-menu-text-active':'social-menu-text4').'" >'.get_lang('Inbox').'</span></a></li>';						
					echo '<li class="social-menu-sub-level" style="background:none;padding:0px"><a href="'.api_get_path(WEB_PATH).'main/messages/outbox.php?f=social">'.Display::return_icon('outbox.png', get_lang('Outbox'), array('hspace'=>'6')).'<span class="'.($show=='messages_outbox'?'social-menu-text-active':'social-menu-text4').'" >'.get_lang('Outbox').'</span></a></li>';
				echo '</ul></li>';*/
			}

	        //Invitations
	        echo '<li><a href="'.api_get_path(WEB_PATH).'main/social/invitations.php">'.Display::return_icon('invitation.png',get_lang('Invitations'),array('hspace'=>'6')).'<span class="'.($show=='invitations'?'social-menu-text-active':'social-menu-text4').'" >'.get_lang('Invitations').$total_invitations.'</span></a></li>';
			
			//Shared profile and groups
	        echo '<li><a href="'.api_get_path(WEB_PATH).'main/social/profile.php">'.Display::return_icon('my_shared_profile.png',get_lang('ViewMySharedProfile'),array('hspace'=>'6')).'<span class="'.($show=='shared_profile'?'social-menu-text-active':'social-menu-text4').'" >'.get_lang('ViewMySharedProfile').'</span></a></li>
	        	  <li><a href="'.api_get_path(WEB_PATH).'main/social/friends.php">'.Display::return_icon('friend.png',get_lang('Friends'),array('hspace'=>'6')).'<span class="'.($show=='friends'?'social-menu-text-active':'social-menu-text4').'" >'.get_lang('Friends').'</span></a></li>
	              <li><a href="'.api_get_path(WEB_PATH).'main/social/groups.php">'.Display::return_icon('group.png',get_lang('SocialGroups'),array('hspace'=>'6')).'<span class="'.($show=='browse_groups'?'social-menu-text-active':'social-menu-text4').'" >'.get_lang('SocialGroups').'</span></a></li>';
			
			//Show groups
/*	        if (in_array($show,$show_groups)) {					
    			echo $create_group_item;
                if (api_get_setting('allow_students_to_create_groups_in_social') == 'true' || api_is_platform_admin()) {                        
                    echo '<li><ul>';    
                    echo '<li class="social-menu-sub-level" style="background:none;padding:0px"><a href="'.api_get_path(WEB_PATH).'main/social/groups.php?view=mygroups">'.Display::return_icon('group.png',get_lang('MySocialGroups'),array('hspace'=>'6')).'<span class="'.($show=='mygroups'?'social-menu-text-active':'social-menu-text4').'" >'.get_lang('MySocialGroups').'</span></a></li>';
                    echo '</ul></li>';
                }
			}*/
			
			//Groups
			/*
			if (api_get_setting('show_groups_to_users') == 'true') {
    			$group_class = 'social-menu-text4';
    			if ($show == 'classes') {
    			    $group_class = 'social-menu-text-active';     
    			}
                echo Display::tag('li', Display::return_icon('group.png',get_lang('MyGroups'),array('hspace'=>'6')).Display::url(Display::tag('span',get_lang('MyGroups'),array('class'=>$group_class)),api_get_path(WEB_PATH).'main/social/usergroups.php'));
			}            
			*/
			//Search users
	        echo '<li><a href="'.api_get_path(WEB_PATH).'main/social/search.php">'.Display::return_icon('zoom.png',get_lang('Search'),array('hspace'=>'6')).'<span class="'.($show=='search'?'social-menu-text-active':'social-menu-text4').'" >'.get_lang('Search').'</span></a></li>';
            		
			//My files
			 echo '<li><a href="'.api_get_path(WEB_PATH).'main/social/myfiles.php">'.Display::return_icon('briefcase.png',get_lang('MyFiles'),array('hspace'=>'6'),16).'<span class="'.($show=='myfiles'?'social-menu-text-active':'social-menu-text4').'" >'.get_lang('MyFiles').'</span></a></li>';	
	        echo'</ul>
				  </div>';	     
		}
		
        if (in_array($show, $show_groups) && !empty($group_id)) {
            echo GroupPortalManager::show_group_column_information($group_id, api_get_user_id(), $show);
        }

        if ($show == 'shared_profile') {

				echo '<div align="center" class="social-menu-title" ><span class="social-menu-text1">'.get_lang('Menu').'</span></div>';
	        	echo 	'<div>
	                	<ul>';

    	  	// My own profile
    	  	if ($show_full_profile && $user_id == intval(api_get_user_id())) {
	        	echo '
	                     <li><a href="'.api_get_path(WEB_PATH).'main/social/home.php">'.Display::return_icon('home.png',get_lang('Home'),array('hspace'=>'6')).'<span class="social-menu-text4" >'.get_lang('Home').'</span></a></li>
	                     <li><a href="'.api_get_path(WEB_PATH).'main/messages/inbox.php?f=social">'.Display::return_icon('instant_message.png',get_lang('Messages'),array('hspace'=>'6')).'<span class="social-menu-text4" >'.get_lang('Messages').$count_unread_message.'</span></a></li>';
	        	echo '   <li><a href="'.api_get_path(WEB_PATH).'main/social/invitations.php">'.Display::return_icon('invitation.png',get_lang('Invitations'),array('hspace'=>'6')).'<span class="'.($show=='invitations'?'social-menu-text-active':'social-menu-text4').'" >'.get_lang('Invitations').$total_invitations.'</span></a></li>';
	        	echo	'<li><a href="'.api_get_path(WEB_PATH).'main/social/profile.php">'.Display::return_icon('my_shared_profile.png',get_lang('ViewMySharedProfile'),array('hspace'=>'6','style'=>'float:left')).'<span class="social-menu-text-active" >'.get_lang('ViewMySharedProfile').'</span></a></li>
	        			 <li><a href="'.api_get_path(WEB_PATH).'main/social/friends.php">'.Display::return_icon('friend.png',get_lang('Friends'),array('hspace'=>'6')).'<span class="social-menu-text4" >'.get_lang('Friends').'</span></a></li>
	                     <li><a href="'.api_get_path(WEB_PATH).'main/social/groups.php">'.Display::return_icon('group.png',get_lang('SocialGroups'),array('hspace'=>'6')).'<span class="social-menu-text4" >'.get_lang('SocialGroups').'</span></a></li>';
				echo '<li><a href="'.api_get_path(WEB_PATH).'main/social/myfiles.php">'.Display::return_icon('briefcase.png',get_lang('MyFiles'),array('hspace'=>'6'),16).'<span class="'.($show=='myfiles'?'social-menu-text-active':'social-menu-text4').'" >'.get_lang('MyFiles').'</span></a></li>';
    	  	}


    	  	// My friend profile
    	  	$html_actions = '';

	  		if ($user_id != api_get_user_id()) {
	  			echo  '<li><a href="'.api_get_path(WEB_PATH).'main/messages/send_message_to_userfriend.inc.php?height=300&width=470&user_friend='.$user_id.'&view=profile&view_panel=1" class="thickbox" title="'.get_lang('SendMessage').'">';
	  			echo  Display::return_icon('compose_message.png',get_lang('SendMessage')).'&nbsp;&nbsp;'.get_lang('SendMessage').'</a></li>';
	  		}

	  		//check if I already sent an invitation message
	  		$invitation_sent_list = SocialManager::get_list_invitation_sent_by_user_id(api_get_user_id());

	  		if (is_array($invitation_sent_list) && is_array($invitation_sent_list[$user_id]) && count($invitation_sent_list[$user_id]) >0 ) {
	  			echo '<li><a href="'.api_get_path(WEB_PATH).'main/social/invitations.php">'.Display::return_icon('invitation.png',get_lang('YouAlreadySentAnInvitation')).'&nbsp;&nbsp;'.get_lang('YouAlreadySentAnInvitation').'</a></li>';
	  		} else {
	  			if (!$show_full_profile) {
	  				echo  '<li><a href="'.api_get_path(WEB_PATH).'main/messages/send_message_to_userfriend.inc.php?view_panel=2&height=230&width=500&user_friend='.$user_id.'" class="thickbox" title="'.get_lang('SendInvitation').'">'.Display :: return_icon('invitation.png', get_lang('SocialInvitationToFriends')).'&nbsp;'.get_lang('SendInvitation').'</a></li>';
	  			}
	  		}
	  		echo '</ul></div>';

        	/*
        	// ---- My Agenda Items
			$my_agenda_items = show_simple_personal_agenda($user_id);
			if (!empty($my_agenda_items)) {
				echo '<div class="sectiontitle">';
					echo get_lang('MyAgenda');
				echo '</div>';
				$tbl_personal_agenda = Database :: get_user_personal_table(TABLE_PERSONAL_AGENDA);
				echo '<div class="social-content-agenda">';
					echo '<div class="social-background-content">';
					echo $my_agenda_items;
					echo '</div>';
				echo '<br /><br />';
				echo '</div>';
			}
			*/
			if ($show_full_profile && $user_id == intval(api_get_user_id())) {
				$personal_course_list = UserManager::get_personal_session_course_list($user_id);
				$course_list_code = array();
				$i=1;
				if (is_array($personal_course_list)) {
					foreach ($personal_course_list as $my_course) {
						if ($i<=10) {
							$list[] = SocialManager::get_logged_user_course_html($my_course,$i);
							$course_list_code[] = array('code'=>$my_course['c'],'dbName'=>$my_course['db']);
						} else {
							break;
						}
						$i++;
					}
					//to avoid repeted courses
					$course_list_code = array_unique_dimensional($course_list_code);
				}

				//-----Announcements
				$my_announcement_by_user_id= intval($user_id);
				$announcements = array();
		    	foreach ($course_list_code as $course) {
	    			$content = AnnouncementManager::get_all_annoucement_by_user_course($course['dbName'], $my_announcement_by_user_id);	    			
	    			$course_info=api_get_course_info($course['code']);
	    	  		if (!empty($content)) {
	    				$announcements[] = '<li><a href="'.api_get_path(WEB_CODE_PATH).'announcements/announcements.php?cidReq='.$course['code'].'"'.Display::return_icon('announcement.png',get_lang('Announcements'),array('hspace'=>'6')).$course_info['name'].' ('.$content['count'].')</a></li>';
	    	  		}
	    	  	}
	    	  	if (!empty($announcements)) {
			    	echo '<div align="center" class="social-menu-title" ><span class="social-menu-text1">'.get_lang('ToolAnnouncement').'</span></div>';
					echo '<div>';
					echo '<ul>';
						foreach ($announcements as $announcement) {
							echo $announcement;
						}
					echo '</ul>';
					echo '</div>';
	    	  	}
			}
        }
        echo '</div>';
	}

	/**
	 * Displays a sortable table with the list of online users.
	 * @param array $user_list
	 */
	public static function display_user_list($user_list) {
		global $charset;
		if ($_GET['id'] == '') {
			$extra_params = array();
			$course_url = '';
			if (strlen($_GET['cidReq']) > 0) {
				$extra_params['cidReq'] = Security::remove_XSS($_GET['cidReq']);
				$course_url = '&amp;cidReq='.Security::remove_XSS($_GET['cidReq']);
			}

			foreach ($user_list as $user) {
				$uid = $user[0];
				$user_info = api_get_user_info($uid);
				$table_row = array();
				//Anonymous users can't have access to the profile
				if (!api_is_anonymous()) {
					if (api_get_setting('allow_social_tool')=='true') {
						$url = api_get_path(WEB_PATH).'main/social/profile.php?u='.$uid.$course_url;
					} else {
						$url = '?id='.$uid.$course_url;
					}
				} else {
					$url = '#';
				}
				$image_array = UserManager::get_user_picture_path_by_id($uid, 'system', false, true);
				
				// reduce image
                $name = api_get_person_name($user_info['firstName'], $user_info['lastName']);
                
                if ($image_array['file'] == 'unknown.jpg') {
                    $friends_profile['file'] = api_get_path(WEB_CODE_PATH).'img/unknown_180_100.jpg'; 
                    $table_row[] = '<a href="'.$url.'"><img title = "'.$name.'" class="social-home-anonymous-online" alt="'.$name.'" src="'.$friends_profile['file'].'"></a>';
                } else {
                    $friends_profile = UserManager::get_picture_user($uid, $image_array['file'], 80, USER_IMAGE_SIZE_ORIGINAL );
                    $table_row[] = '<a href="'.$url.'"><img title = "'.$name.'" class="social-home-users-online" alt="'.$name.'" src="'.$friends_profile['file'].'"></a>';    
                }				
				$table_row[] = '<a href="'.$url.'">'.(cut($user_info['firstName'],16)).'<br />'.cut($user_info['lastName'],18).'</a>';
				
				$table_data[] = $table_row;
			}
			$table_header[] = array(get_lang('UserPicture'), false, 'width="90"');
			
			if (api_get_setting('show_email_addresses') == 'true') {
				//$table_header[] = array(get_lang('Email'), true);
			}
			echo Display::return_sortable_grid('online', $table_header, $table_data, array('per_page' => 20));
			//Display::display_sortable_table($table_header, $table_data, array(), array('per_page' => 10), $extra_params, array(),'grid');
		}
	}
	/**
	 * Displays the information of an individual user
	 * @param int $user_id
	 */
	public static function display_individual_user($user_id) {
		global $interbreadcrumb;
		$safe_user_id = intval($user_id);

		$user_table = Database::get_main_table(TABLE_MAIN_USER);
		$sql = "SELECT * FROM $user_table WHERE user_id = ".$safe_user_id;
		$result = Database::query($sql);
		if (Database::num_rows($result) == 1) {
			$user_object = Database::fetch_object($result);
			$name = GetFullUserName($user_id).($_SESSION['_uid'] == $user_id ? '&nbsp;<strong>('.get_lang('Me').')</strong>' : '' );
			$alt  = GetFullUserName($user_id).($_SESSION['_uid'] == $user_id ? '&nbsp;('.get_lang('Me').')' : '');

			$status = get_status_from_code($user_object->status);
			
			$interbreadcrumb[] = array('url' => 'whoisonline.php', 'name' => get_lang('UsersOnLineList'));
			Display::display_header($alt);
			echo '<div class="actions-title">';
			echo $alt;
			echo '</div><br />';
			echo '<div>';

			echo '<div style="margin:0 auto; width:350px; border:1px;">';
				echo '<div id="whoisonline-user-image" style="float:left; padding:5px;">';
				if (strlen(trim($user_object->picture_uri)) > 0) {
					$sysdir_array = UserManager::get_user_picture_path_by_id($safe_user_id, 'system');
					$sysdir = $sysdir_array['dir'];
					$webdir_array = UserManager::get_user_picture_path_by_id($safe_user_id, 'web');
					$webdir = $webdir_array['dir'];
					$fullurl = $webdir.$user_object->picture_uri;
					$system_image_path = $sysdir.$user_object->picture_uri;
					list($width, $height, $type, $attr) = @getimagesize($system_image_path);
					$resizing = (($height > 200) ? 'height="200"' : '');
					$height += 30;
					$width += 30;
					$window_name = 'window'.uniqid('');
					// get the path,width and height from original picture
					$big_image = $webdir.'big_'.$user_object->picture_uri;
					$big_image_size = api_getimagesize($big_image);
					$big_image_width = $big_image_size[0];
					$big_image_height = $big_image_size[1];
					$url_big_image = $big_image.'?rnd='.time();
					echo '<input type="image" src="'.$fullurl.'" alt="'.$alt.'" onclick="javascript: return show_image(\''.$url_big_image.'\',\''.$big_image_width.'\',\''.$big_image_height.'\');"/><br />';
				} else {
					echo Display::return_icon('unknown.jpg', get_lang('Unknown'));
					echo '<br />';
				}
				
				if (!empty($status)) {
				    echo '<div style="text-align:center;padding-top:5px;">'.$status.'</div>';
				}
				echo '</div>';

				echo '<div id="whoisonline-user-info" style="float:left; padding-left:15px;">';

				global $user_anonymous;
				if (api_get_setting('allow_social_tool') == 'true' && api_get_user_id() <> $user_anonymous && api_get_user_id() <> 0) {
					echo '<p><a href="'.api_get_path(WEB_CODE_PATH).'social/profile.php?u='.$safe_user_id.'">'.Display :: return_icon('my_shared_profile.png', get_lang('SocialInvitationToFriends'),array('height'=>'18px')).get_lang('ViewSharedProfile').'</a></p>';

					$user_anonymous = api_get_anonymous_id();

					if ($safe_user_id != api_get_user_id() && !api_is_anonymous($safe_user_id)) {
						$user_relation = SocialManager::get_relation_between_contacts(api_get_user_id(), $safe_user_id);
						if ($user_relation == 0 || $user_relation == 6) {
							echo  '<p><a href="main/messages/send_message_to_userfriend.inc.php?view_panel=2&height=300&width=610&user_friend='.$safe_user_id.'" class="thickbox" title="'.get_lang('SendInvitation').'">'.Display :: return_icon('invitation.png', get_lang('SocialInvitationToFriends'),array('height'=>'18px')).'&nbsp;'.get_lang('SendInvitation').'</a></p>
								   <p><a href="main/messages/send_message_to_userfriend.inc.php?view_panel=1&height=310&width=610&user_friend='.$safe_user_id.'" class="thickbox" title="'.get_lang('SendAMessage').'">'.Display :: return_icon('mail_send.png', get_lang('SendAMessage'),array('height'=>'18px')).'&nbsp;'.get_lang('SendAMessage').'</a></p>';
						} else {
							echo  '<p><a href="main/messages/send_message_to_userfriend.inc.php?view_panel=1&height=310&width=610&user_friend='.$safe_user_id.'" class="thickbox" title="'.get_lang('SendAMessage').'">'.Display :: return_icon('mail_send.png', get_lang('SendAMessage'),array('height'=>'18px')).'&nbsp;'.get_lang('SendAMessage').'</a></p>';
						}
					}
				}
				if (api_get_setting('show_email_addresses') == 'true') {
					echo Display::encrypted_mailto_link($user_object->email,$user_object->email).'<br />';
				}
				echo '</div>';
			echo '</div>';
			echo '</div>';
			echo '<div class="clear"></div>';
			echo '<div>';
			if ($user_object->competences) {
				echo '<dt><div class="actions-message"><strong>'.get_lang('MyCompetences').'</strong></div></dt>';
				echo '<dd>'.$user_object->competences.'</dd>';
			}
			if ($user_object->diplomas) {
				echo '<dt><div class="actions-message"><strong>'.get_lang('MyDiplomas').'</strong></div></dt>';
				echo '<dd>'.$user_object->diplomas.'</dd>';
			}
			if ($user_object->teach) {
				echo '<dt><div class="actions-message"><strong>'.get_lang('MyTeach').'</strong></div></dt>';
				echo '<dd>'.$user_object->teach.'</dd>';;
			}
			SocialManager::display_productions($user_object->user_id);
			if ($user_object->openarea) {
				echo '<dt><div class="actions-message"><strong>'.get_lang('MyPersonalOpenArea').'</strong></div></dt>';
				echo '<dd>'.$user_object->openarea.'</dd>';
			}
			echo '</div>';
		} else	{
			Display::display_header(get_lang('UsersOnLineList'));
			echo '<div class="actions-title">';
			echo get_lang('UsersOnLineList');
			echo '</div>';
		}
	}
	/**
	 * Display productions in whoisonline
	 * @param int $user_id User id
	 * @todo use the correct api_get_path instead of $clarolineRepositoryWeb
	 */
	public static function display_productions($user_id) {
		$sysdir_array = UserManager::get_user_picture_path_by_id($user_id, 'system', true);
		$sysdir = $sysdir_array['dir'].$user_id.'/';
		$webdir_array = UserManager::get_user_picture_path_by_id($user_id, 'web', true);
		$webdir = $webdir_array['dir'].$user_id.'/';
		if (!is_dir($sysdir)) {
			mkdir($sysdir, api_get_permissions_for_new_directories(), true);
		}
		/*
		$handle = opendir($sysdir);
		$productions = array();
		while ($file = readdir($handle)) {
			if ($file == '.' || $file == '..' || $file == '.htaccess') {
				continue;						// Skip current and parent directories
			}
			if (preg_match('/('.$user_id.'|[0-9a-f]{13}|saved)_.+\.(png|jpg|jpeg|gif)$/i', $file)) {
				// User's photos should not be listed as productions.
				continue;
			}
			$productions[] = $file;
		}
		*/
		$productions = UserManager::get_user_productions($user_id);

		if (count($productions) > 0) {
			echo '<dt><strong>'.get_lang('Productions').'</strong></dt>';
			echo '<dd><ul>';
			foreach ($productions as $index => $file) {
				// Only display direct file links to avoid browsing an empty directory
				if (is_file($sysdir.$file) && $file != $webdir_array['file']) {
					echo '<li><a href="'.$webdir.urlencode($file).'" target=_blank>'.$file.'</a></li>';
				}
				// Real productions are under a subdirectory by the User's id
				if (is_dir($sysdir.$file)) {
					$subs = scandir($sysdir.$file);
					foreach ($subs as $my => $sub) {
						if (substr($sub, 0, 1) != '.' && is_file($sysdir.$file.'/'.$sub)) {
							echo '<li><a href="'.$webdir.urlencode($file).'/'.urlencode($sub).'" target=_blank>'.$sub.'</a></li>';
						}
					}
				}
			}
			echo '</ul></dd>';
		}
	}
	/**
	 * Dummy function
	 *
	 */
	public static function get_plugins($place = SOCIAL_CENTER_PLUGIN) {
		$content = '';
		switch ($place) {
			case SOCIAL_CENTER_PLUGIN:
				$social_plugins = array(1, 2);
				if (is_array($social_plugins) && count($social_plugins)>0) {
				    $content.= '<div id="social-plugins">';
				    foreach($social_plugins as $plugin ) {
				    	$content.=  '<div class="social-plugin-item">';
				    	$content.=  $plugin;
				    	$content.=  '</div>';
				    }
				    $content.=  '</div>';
			    }
			break;
			case SOCIAL_LEFT_PLUGIN:
			break;
			case SOCIAL_RIGHT_PLUGIN:
			break;
		}
		return $content;
	}
}