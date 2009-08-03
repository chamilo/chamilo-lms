<?php
/* For licensing terms, see /dokeos_license.txt */

$language_file = array('registration','messages','userInfo','admin','forum','blog');
require_once '../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'usermanager.lib.php';
require_once api_get_path(LIBRARY_PATH).'social.lib.php';
require_once api_get_path(LIBRARY_PATH).'course.lib.php';

// @todo here we must show the user information as read only 
//User picture size is calculated from SYSTEM path
$user_info= UserManager::get_user_info_by_id(api_get_user_id());
$img_array= UserManager::get_user_picture_path_by_id(api_get_user_id(),'web',true,true);

if (isset($_POST['load_ajax'])) {
	require_once api_get_path(LIBRARY_PATH).'blog.lib.php';
	require_once api_get_path(SYS_CODE_PATH).'forum/forumfunction.inc.php';	
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
					api_display_tool_title(get_lang('Forum'));
					echo '<div class="social-background-content" style="background:#FAF9F6; padding:0px;" >';
					echo api_xml_http_response_encode($forum_result);
					echo '</div>';	
					echo '<br />';
					$all_result_data++;
				}							
				
				//------Blog posts
				$result = get_blog_post_from_user($course_db, $user_id); 
				if (!empty($result)) {
					echo '<div class="clear"></div><br />';
					api_display_tool_title(api_xml_http_response_encode(get_lang('BlogPosts')));				
					echo '<div class="social-background-content" style="background:#FAF9F6; padding:0px;">';
					echo api_xml_http_response_encode($result);
					echo '</div>';
					echo '<br />';
					$all_result_data++;				
				}
				
				//------Blog comments			
				$result = get_blog_comment_from_user($course_db, $user_id); 
				if (!empty($result)) {
					api_display_tool_title(api_xml_http_response_encode(get_lang('BlogComments')));							
					echo '<div class="social-background-content" style="background:#FAF9F6; padding:0px;">';
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
					echo '<div class="social-background-content" style="background:#FAF9F6; padding:0px;">';
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
					
	}
} else {
	// normal behavior
$request=api_is_xml_http_request();
$language_variable=api_xml_http_response_encode(get_lang('PersonalData'));
//api_display_tool_title($language_variable);

	echo '<div class="actions">';
	echo '<a href="../auth/profile.php?show=1"">'.Display::return_icon('edit.gif',api_xml_http_response_encode(get_lang('EditInformation'))).'&nbsp;'.api_xml_http_response_encode(get_lang('EditInformation')).'</a>&nbsp;&nbsp;';
	if (api_get_setting('allow_social_tool')=='true' && api_get_setting('allow_message_tool')=='true' && api_get_user_id()<>2 && api_get_user_id()<>0) {
		echo '<a href="../social/profile.php?shared=true">'.Display::return_icon('shared_profile.png',api_xml_http_response_encode(get_lang('ViewSharedProfile'))).'&nbsp;'.api_xml_http_response_encode(get_lang('ViewSharedProfile')).'</a>';		
	}
	echo '</div>';	
	echo '<div id="profile_container">';			
		echo '<div class="social-profile-info" style="float:left;position:relative">';			
			echo '<dt>'.api_xml_http_response_encode(get_lang('UserName')).'</dt>
			<dd>'. api_xml_http_response_encode($user_info['username']).'	</dd>';
			echo '<dt>'.api_xml_http_response_encode(get_lang('FirstName')).'</dt>
			<dd>'. api_xml_http_response_encode($user_info['firstname']).'</dd>';
			echo '<dt>'.api_xml_http_response_encode(get_lang('LastName')).'</dt>
			<dd>'. api_xml_http_response_encode($user_info['lastname']).'</dd>';
			echo '<dt>'.api_xml_http_response_encode(get_lang('OfficialCode')).'</dt>	
			<dd>'. api_xml_http_response_encode($user_info['official_code']).'</dd>';
			echo '<dt>'.api_xml_http_response_encode(get_lang('Email')).'</dt>
			<dd>'. api_xml_http_response_encode($user_info['email']).'</dd>';
			echo '<dt>'.api_xml_http_response_encode(get_lang('Phone')).'</dt>
			<dd>'. api_xml_http_response_encode($user_info['phone']).'</dd>';			
		echo '</div>';
		
		echo '<div style="float:left;position:relative">';
		echo '<div id="picture" style="width:200px;float:left;position:relative;margin-top:10px;">'; 
			echo '<img src='.$img_array['dir'].$img_array['file'].' />';
		echo '</div>';	
		/*if (api_get_setting('allow_message_tool')=='true') {	
			require_once api_get_path(LIBRARY_PATH).'message.lib.php';
			$number_of_new_messages = MessageManager::get_new_messages();
			$number_of_outbox_message=MessageManager::get_number_of_messages_sent();
			$cant_out_box=' ('.$number_of_outbox_message.')';
			$cant_msg = ' ('.$number_of_new_messages.')';
			$number_of_new_messages_of_friend=UserFriend::get_message_number_invitation_by_user_id(api_get_user_id());
			//echo '<div class="message-view" style="display:none;">'.get_lang('ViewMessages').'</div>';
			echo '<div class="message-content" style="float:right" >
					<h2 class="message-title" style="margin-top:0">'.get_lang('Messages').'</h2>
					<p>
						<a href="../social/index.php#remote-tab-2" class="message-body">'.get_lang('Inbox').$cant_msg.' </a><br />
						<a href="../social/index.php#remote-tab-3" class="message-body">'.get_lang('Outbox').$cant_out_box.'</a><br />
					</p>';		
			echo '<img src="../img/delete.gif" alt="'.get_lang('Close').'" title="'.get_lang('Close').'"  class="message-delete" onclick="delete_message_js()" />';
			if ($number_of_new_messages_of_friend>0) {
				echo '<br/>';
			}
			echo '</div>';
		}*/				
		echo '</div>';			
}
?>