<?php
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2009 Dokeos SPRL
	Copyright (c) Julio Montoya Armas

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

$language_file = array('registration','messages','userInfo','admin');
require_once ('../inc/global.inc.php');
require_once (api_get_path(LIBRARY_PATH).'usermanager.lib.php');

// @todo here we must show the user information as read only 
//User picture size is calculated from SYSTEM path
$user_info= UserManager::get_user_info_by_id(api_get_user_id());
$img_array= UserManager::get_user_picture_path_by_id(api_get_user_id(),'web',true,true);

if (isset($_POST['load_ajax'])) {
	require_once (api_get_path(LIBRARY_PATH).'blog.lib.php');
	require_once (api_get_path(SYS_CODE_PATH).'forum/forumfunction.inc.php');	
	$user_id = $_SESSION['social_user_id'];
	if ($_POST['action']) {$action = $_POST['action'];}
	switch($action) {
		case 'load_course' :
			$course_db =  $_POST['course_code'];
			// @todo goto the course link							
			//echo '<a href="'.api_get_path(WEB_COURSE_PATH).$course_directory.'/?id_session='.$my_course['id_session'].'">'.get_lang('GotoCourse').'</a>';
		
			//------Forum messages
			api_display_tool_title(get_lang('Forum'));
			//print_r($course);
			$table_forums 			= Database :: get_course_table(TABLE_FORUM,$course_db);
			$table_threads 			= Database :: get_course_table(TABLE_FORUM_THREAD,$course_db);
			$table_posts 			= Database :: get_course_table(TABLE_FORUM_POST,$course_db);
			$table_item_property 	= Database :: get_course_table(TABLE_ITEM_PROPERTY,$course_db);
			$table_users 			= Database :: get_main_table(TABLE_MAIN_USER);
			
			//------Forum messages
			echo '<div class="rounded social-profile-post" style="background:#FAF9F6; padding:0px;" >';			
			get_all_post_from_user($user_id, $course_db);
			echo '</div>';	
			echo '<br />';			
			
			//------Blog posts				
					
			$result = get_blog_post_from_user($course_db, $user_id); 
			if (!empty($result)) {
				api_display_tool_title(get_lang('BlogPosts'));				
				echo '<div class="rounded social-profile-post" style="background:#FAF9F6; padding:0px;">';
				echo $result;
				echo '</div>';
				echo '<br />';				
			}
			
			//------Blog comments			
			$result = get_blog_comment_from_user($course_db, $user_id); 
			if (!empty($result)) {
				api_display_tool_title(get_lang('BlogComments'));							
				echo '<div class="rounded social-profile-post" style="background:#FAF9F6; padding:0px;">';
				echo $result;
				echo '</div>';
				echo '<br />';				
			}
			break;
		case 'unload_course' :
			//echo 'load2';
		break;
		default:
			
	}
	
	
} else {
	// normal behavior
	echo '<div id="actions" class="actions">';
	echo '<a href="../auth/profile.php?show=1"">'.Display::return_icon('edit.gif').'&nbsp;'.mb_convert_encoding(get_lang('EditInformation'),'UTF-8',$charset).'</a>&nbsp;&nbsp;';
	if (api_get_setting('allow_social_tool')=='true' && api_get_setting('allow_message_tool')=='true' && api_get_user_id()<>2 && api_get_user_id()<>0) {
		echo '<a href="../social/profile.php">'.Display::return_icon('edit.gif').'&nbsp;'.mb_convert_encoding(get_lang('ViewSharedProfile'),'UTF-8',$charset).'</a>';	
	}
	echo '</div>';
	
	echo '<div id="profile_container" style="width:550px;display:block;">';
		echo '<div id="picture" style="width:200px;float:right;position:relative;">'; 
			echo '<img src='.$img_array['dir'].$img_array['file'].' />';
		echo '</div>';	
		echo '<div class="social-profile-info">';
			
			echo '<dt>'.mb_convert_encoding(get_lang('UserName'),'UTF-8',$charset).'</dt>
			<dd>'. mb_convert_encoding($user_info['username'],'UTF-8',$charset).'	</dd>';
			echo '<dt>'.mb_convert_encoding(get_lang('FirstName'),'UTF-8',$charset).'</dt>
			<dd>'. mb_convert_encoding($user_info['firstname'],'UTF-8',$charset).'</dd>';
			echo '<dt>'.mb_convert_encoding(get_lang('LastName'),'UTF-8',$charset).'</dt>
			<dd>'. mb_convert_encoding($user_info['lastname'],'UTF-8',$charset).'</dd>';
			echo '<dt>'.mb_convert_encoding(get_lang('OfficialCode'),'UTF-8',$charset).'</dt>	
			<dd>'. mb_convert_encoding($user_info['official_code'],'UTF-8',$charset).'</dd>';
			echo '<dt>'.mb_convert_encoding(get_lang('Email'),'UTF-8',$charset).'</dt>
			<dd>'. mb_convert_encoding($user_info['email'],'UTF-8',$charset).'</dd>';
			echo '<dt>'.mb_convert_encoding(get_lang('Phone'),'UTF-8',$charset).'</dt>
			<dd>'. mb_convert_encoding($user_info['phone'],'UTF-8',$charset).'</dd>';
			
		echo '</div>';
	echo '</div>';	
}
?>