<?php
/* For licensing terms, see /chamilo_license.txt */
/**
 * Responses to AJAX calls 
 */
require_once '../global.inc.php';
require_once api_get_path(LIBRARY_PATH).'social.lib.php';

$action = $_GET['a'];

$current_user_id	 = api_get_user_id();
switch ($action) {	
	case 'add_friend':
	
		if (api_is_anonymous()){
			echo '';
			break;
		}	

		$my_current_friend		 = Security::remove_XSS($_POST['friend_id']);
		$my_denied_current_friend= Security::remove_XSS($_POST['denied_friend_id']);
		$my_delete_friend        = Security::remove_XSS($_POST['delete_friend_id']);
		$friend_id_qualify       = Security::remove_XSS($_POST['user_id_friend_q']);
		$type_friend_qualify     = Security::remove_XSS($_POST['type_friend_q']); //filtered?
		$is_my_friend            = Security::remove_XSS($_POST['is_my_friend']); //filtered?
		
		if (isset($_POST['is_my_friend'])) {
			$relation_type = USER_RELATION_TYPE_FRIEND; //My friend
		} else {
			$relation_type = USER_RELATION_TYPE_UNKNOW; //Unknown contact
		}
		
		if (isset($_POST['friend_id'])) {
			
			UserManager::relate_users($current_user_id,$my_current_friend,$relation_type);
			UserManager::relate_users($my_current_friend,$current_user_id,$relation_type);
			
			SocialManager::invitation_accepted($my_current_friend,$current_user_id);
			
			if (isset($_POST['is_my_friend'])) {
				echo api_xml_http_response_encode(get_lang('AddedContactToList'));
			} else {
				Display::display_normal_message(api_xml_http_response_encode(get_lang('AddedContactToList')));
			}
		}
		
	case 'deny_friend':
	
		if (api_is_anonymous()){
			echo '';
			break;
		}

		$my_current_friend		 = Security::remove_XSS($_POST['friend_id']);
		$my_denied_current_friend= Security::remove_XSS($_POST['denied_friend_id']);
		$my_delete_friend        = Security::remove_XSS($_POST['delete_friend_id']);
		$friend_id_qualify       = Security::remove_XSS($_POST['user_id_friend_q']);
		$type_friend_qualify     = Security::remove_XSS($_POST['type_friend_q']); //filtered?
		$is_my_friend            = Security::remove_XSS($_POST['is_my_friend']); //filtered?
		if (isset($_POST['is_my_friend'])) {
			$relation_type=USER_RELATION_TYPE_FRIEND;//my friend
		} else {
			$relation_type=USER_RELATION_TYPE_UNKNOW;//Contact unknown
		}
		if (isset($_POST['denied_friend_id'])) {
			SocialManager::invitation_denied($my_denied_current_friend,$current_user_id);
			Display::display_confirmation_message(api_xml_http_response_encode(get_lang('InvitationDenied')));
		}	
		break;
	case 'delete_friend':
	
		if (api_is_anonymous()){
			echo '';
			break;
		}
		
		//deprecated variables?
		//$my_current_friend		 = Security::remove_XSS($_POST['friend_id']);
		//$my_denied_current_friend= Security::remove_XSS($_POST['denied_friend_id']);
		$my_delete_friend        = Security::remove_XSS($_POST['delete_friend_id']);
		//$friend_id_qualify       = Security::remove_XSS($_POST['user_id_friend_q']);
		//$type_friend_qualify     = Security::remove_XSS($_POST['type_friend_q']); //filtered?
		//$is_my_friend            = Security::remove_XSS($_POST['is_my_friend']); //filtered?

		if (isset($_POST['delete_friend_id'])) {
			SocialManager::remove_user_rel_user($my_delete_friend);
		}
		/*
		if(isset($_POST['user_id_friend_q']) && isset($_POST['type_friend_q'])) {
			SocialManager::qualify_friend($friend_id_qualify,$type_friend_qualify);
			echo api_xml_http_response_encode(get_lang('AttachContactsToGroupSuccesfuly'));
		}*/		
		break;
	case 'show_my_friends':
	
		if (api_is_anonymous()) {
			echo '';
			break;
		}
		$list_path_friends	= array();
		$user_id	= api_get_user_id();
		$name_search= Security::remove_XSS($_POST['search_name_q']);
		$number_friends = 0;
		
		if (isset($name_search) && $name_search!='undefined') {
			$friends = SocialManager::get_friends($user_id,null,$name_search);
		} else {
			$friends = SocialManager::get_friends($user_id);
		}
		
		$friend_html = '';
		$number_of_images = 8;
		
		$number_friends = count($friends);
		if ($number_friends != 0) {
			$number_loop   = ($number_friends/$number_of_images);
			$loop_friends  = ceil($number_loop);
			$j=0;
			$friend_html.= '<br /><table width="100%" border="0" cellpadding="0" cellspacing="0" >';
			for ($k=0;$k<$loop_friends;$k++) {
				$friend_html.='<tr><td valign="top">';
				if ($j==$number_of_images) {
					$number_of_images=$number_of_images*2;
				}
				while ($j<$number_of_images) {
					if (isset($friends[$j])) {
						$friend = $friends[$j];
						$user_name = api_xml_http_response_encode($friend['firstName'].' '.$friend['lastName']);
						$friends_profile = SocialManager::get_picture_user($friend['friend_user_id'], $friend['image'], 92);
						$friend_html.='<div onMouseover="show_icon_delete(this)" onMouseout="hide_icon_delete(this)" class="image-social-content" id=div_'.$friends[$j]['friend_user_id'].'>';
						$friend_html.='<span><a href="profile.php?u='.$friend['friend_user_id'].'"><center><img src="'.$friends_profile['file'].'" style="width:60px;height:60px;border:3pt solid #eee" id="imgfriend_'.$friend['friend_user_id'].'" title="'.$user_name.'" /></center></a></span>';
						$friend_html.='<img onclick="delete_friend (this)" id=img_'.$friend['friend_user_id'].' src="../img/blank.gif" alt="" title=""  class="image-delete" /> <center class="friend">'.$user_name.'</center></div>';				
					}
					$j++;
				}
				$friend_html.='</td></tr>';
			}
			$friend_html.='<br/></table>';
		}
		echo $friend_html;	
		break;		
	case 'toogle_course':
		if (api_is_anonymous()){
			echo '';
			break;
		}
		
		require_once api_get_path(LIBRARY_PATH).'blog.lib.php';
		require_once api_get_path(SYS_CODE_PATH).'forum/forumfunction.inc.php';
		require_once api_get_path(LIBRARY_PATH).'course.lib.php';
		
		$user_id = intval($_SESSION['social_user_id']);
		if ($_POST['action']) {$action = $_POST['action'];}
		
		switch($action) {
			
			case 'load_course' :
				$course_db =  $_POST['course_code'];
				
				// @todo goto the course link
				//echo '<a href="'.api_get_path(WEB_COURSE_PATH).$course_directory.'/?id_session='.$my_course['id_session'].'">'.get_lang('GotoCourse').'</a>';
				$course_id=CourseManager::get_course_id_by_database_name($course_db);
	
				if (api_is_user_of_course($course_id,api_get_user_id())) {
	
					$table_forums 			= Database :: get_course_table(TABLE_FORUM,$course_db);
					$table_threads 			= Database :: get_course_table(TABLE_FORUM_THREAD,$course_db);
					$table_posts 			= Database :: get_course_table(TABLE_FORUM_POST,$course_db);
					$table_item_property 	= Database :: get_course_table(TABLE_ITEM_PROPERTY,$course_db);
					$table_users 			= Database :: get_main_table(TABLE_MAIN_USER);
	
					//------Forum messages
	
					$forum_result = get_all_post_from_user($user_id, $course_db);
					$all_result_data = 0;
					if ($forum_result !='') {
						echo '<div id="social-forum-main-title">';
						echo api_xml_http_response_encode(get_lang('Forum'));
						echo '</div>';
						
						
						echo '<div style="background:#FAF9F6; padding:0px;" >';
						echo api_xml_http_response_encode($forum_result);
						echo '</div>';
						echo '<br />';
						$all_result_data++;
					}
	
					//------Blog posts
					$result = get_blog_post_from_user($course_db, $user_id);
					if (!empty($result)) {
						api_display_tool_title(api_xml_http_response_encode(get_lang('Blog')));
						echo '<div style="background:#FAF9F6; padding:0px;">';
						echo api_xml_http_response_encode($result);
						echo '</div>';
						echo '<br />';
						$all_result_data++;
					}
	
					//------Blog comments
					$result = get_blog_comment_from_user($course_db, $user_id);
					if (!empty($result)) {
						echo '<div  style="background:#FAF9F6; padding-left:10px;">';
						api_display_tool_title(api_xml_http_response_encode(get_lang('BlogComments')));
						echo api_xml_http_response_encode($result);
						echo '</div>';
						echo '<br />';
						$all_result_data++;
					}
					if ($all_result_data == 0) {
						echo api_xml_http_response_encode(get_lang('NoDataAvailable'));
					}
	
				} else {
						echo '<div class="clear"></div><br />';
						api_display_tool_title(api_xml_http_response_encode(get_lang('Details')));
						echo '<div style="background:#FAF9F6; padding:0px;">';
						echo api_xml_http_response_encode(get_lang('UserNonRegisteredAtTheCourse'));
						echo '<div class="clear"></div><br />';
						echo '</div>';
						echo '<div class="clear"></div><br />';
				}
				break;
			case 'unload_course' :
				//echo 'load2';
				break;
			default:
				break;
		}		
		break;
	default:
		echo '';	
}
exit;