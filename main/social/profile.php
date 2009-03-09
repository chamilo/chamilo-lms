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
/**
==============================================================================
* This is the profile social main page
* @author Julio Montoya <gugli100@gmail.com>
=============================================================================
*/


$language_file = array('registration','messages','userInfo','admin');
require '../inc/global.inc.php';
require_once (api_get_path(LIBRARY_PATH).'usermanager.lib.php');
require_once (api_get_path(LIBRARY_PATH).'social.lib.php');
/*
define(SOCIALUNKNOW,1);
define(SOCIALPARENT,2);
define(SOCIALFRIEND,3);
define(SOCIALGOODFRIEND,4);
define(SOCIALENEMY,5);
define(SOCIALDELETED,6);
*/

$user_id = api_get_user_id();
$show_full_profile = true;

//I'm your friend? I can see your profile?

if (isset($_GET['u'])) {	
	$user_id 	= (int) Database::escape_string($_GET['u']);	
	// It's me! 
	if (api_get_user_id() != $user_id) {
		$user_info	= UserManager::get_user_info_by_id($user_id);	
		$show_full_profile = false;	
		if ($user_info==false) {
			// user does no exist !!
			api_not_allowed();
		} else {
			//checking the relationship between me and my friend			
			$my_status= UserFriend::get_relation_between_contacts(api_get_user_id(), $user_id);
			if (in_array($my_status, array(SOCIALPARENT, SOCIALFRIEND, SOCIALGOODFRIEND))) {	
				$show_full_profile = true;			
			}
			//checking the relationship between my friend and me		
			$my_friend_status = UserFriend::get_relation_between_contacts($user_id, api_get_user_id());		
			if (in_array($my_friend_status, array(SOCIALPARENT, SOCIALFRIEND, SOCIALGOODFRIEND))) {
				$show_full_profile = true;			
			} else {
				// im probably not a good friend
				$show_full_profile = false;
			}
		}	
	} else {
		$user_info	= UserManager::get_user_info_by_id($user_id);	
	}		
} else {
	$user_info	= UserManager::get_user_info_by_id($user_id);
}

require_once (api_get_path(SYS_CODE_PATH).'calendar/myagenda.inc.php');
require_once (api_get_path(SYS_CODE_PATH).'announcements/announcements.inc.php');
require_once (api_get_path(LIBRARY_PATH).'course.lib.php');
require_once (api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php');

api_block_anonymous_users();

$htmlHeadXtra[] = '<script src="../inc/lib/javascript/jquery.js" type="text/javascript" language="javascript"></script>'; //jQuery
$htmlHeadXtra[] = '<script src="../inc/lib/javascript/jquery.corners.min.js" type="text/javascript" language="javascript"></script>'; //jQuery corner

$htmlHeadXtra[] = '
<script type="text/javascript">
function toogle_function (element_html, course_code){
	elem_id=$(element_html).attr("id");	
	id_elem=elem_id.split("_"); 
	ident="div#div_group_"+id_elem[1];
	 
	id_button="#btn_"+id_elem[1]; 
	elem_src=$(id_button).attr("src"); 
	image_show=elem_src.split("/");
	my_image=image_show[2]; 
	if (my_image=="nolines_plus.gif") {
		$(ident).hide("slow");	
		$(id_button).attr("src","../img/nolines_minus.gif"); var action = "load_course"; 
	} else {
		$(ident).show("slow");	
		$(id_button).attr("src","../img/nolines_plus.gif"); var action = "unload";
	}
	var content = \'social_content\' + id_elem[1]; 
	 $.ajax({
		contentType: "application/x-www-form-urlencoded",
		beforeSend: function(objeto) {
		$("#id_response").html("'.get_lang('Loading').'"); },
		type: "POST",
		url: "../social/data_personal.inc.php",
		data: "load_ajax="+id_elem+"&action="+action+"&course_code="+course_code,
		success: function(datos) {
		 //$("div#"+name_div_id).hide("slow");
		 $("div#"+content).html(datos);
		}
	});		
}
</script>';

$interbreadcrumb[]= array ('url' => '#','name' => get_lang('Profile') );

$_SESSION['social_user_id'] = $user_id;

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
		$result .= '<a href="#" id="ln_'.$count.'"  onclick=toogle_function(this,\''.$course_database.'\');>&nbsp;'.$course_title.'</a></h2>';
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
			if (get_setting('display_coursecode_in_courselist') == 'true' OR get_setting('display_teacher_in_courselist') == 'true') {
				$result .= '<br />';
			}
			if (get_setting('display_coursecode_in_courselist') == 'true') {
				$result .= $course_display_code;
			}
			if (get_setting('display_coursecode_in_courselist') == 'true' AND get_setting('display_teacher_in_courselist') == 'true') {
				$result .= ' &ndash; ';
			}
			if (get_setting('display_teacher_in_courselist') == 'true') {
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
			$rs = api_sql_query($sql, __FILE__, __LINE__);
			$sessioncoach = api_store_result($rs);
			$sessioncoach = $sessioncoach[0];
		
			$session = array();
			$session['title'] = $my_course['session_name'];
			if ( $my_course['date_start']=='0000-00-00' ) {
				$session['dates'] = get_lang('WithoutTimeLimits');
				if ( api_get_setting('show_session_coach') === 'true' ) {
					$session['coach'] = get_lang('GeneralCoach').': '.$sessioncoach['lastname'].' '.$sessioncoach['firstname'];
				}
				$active = true;
			} else {
				$session ['dates'] = ' - '.get_lang('From').' '.$my_course['date_start'].' '.get_lang('To').' '.$my_course['date_end'];
				if ( api_get_setting('show_session_coach') === 'true' ) {
					$session['coach'] = get_lang('GeneralCoach').': '.$sessioncoach['lastname'].' '.$sessioncoach['firstname'];
				}
				$active = ($date_start <= $now && $date_end >= $now)?true:false;
			}
		}
		$output = array ($my_course['user_course_cat'], $result, $my_course['id_session'], $session, 'active'=>$active);
	} else {
		$output = array ($my_course['user_course_cat'], $result);
	}	
	return $output;
}

Display :: display_header(null);

// @todo here we must show the user information as read only 
//User picture size is calculated from SYSTEM path

$img_array= UserManager::get_user_picture_path_by_id($user_id,'web',true,true);

//print_r($user_info);
echo $s="<script>$(document).ready( function(){
		  $('.rounded').corners();		  
		});</script>";
			
echo '<div id="actions">';
	//echo '<a href="../auth/profile.php?show=1"">'.Display::return_icon('edit.gif').'&nbsp;'.mb_convert_encoding(get_lang('EditInformation'),'UTF-8',$charset).'</a>';
echo '</div>';

//Setting some course info 
$personal_course_list = UserManager::get_personal_session_course_list($_user['user_id']);
$course_list_code = array();
$i=1;
//print_r($personal_course_list);
foreach ($personal_course_list as $my_course) {
	$list[] = get_logged_user_course_html($my_course,$i);	
	$course_list_code[] = array('code'=>$my_course['c'],'dbName'=>$my_course['db'], 'title'=>$my_course['i']);
	$i++;
}
					
echo '<div id="social-profile-wrapper">';
// RIGHT COLUMN
    echo '<div id="social-profile-right">';			
		//---- FRIENDS
		if ($show_full_profile) {
			$list_path_friends= $list_path_normal_friends = $list_path_parents = array();
			
			$list_path_good_friends		= UserFriend::get_list_path_web_by_user_id($user_id, SOCIALGOODFRIEND);	
			$list_path_normal_friends	= UserFriend::get_list_path_web_by_user_id($user_id, SOCIALFRIEND);			
			$list_path_parents 			= UserFriend::get_list_path_web_by_user_id($user_id, SOCIALPARENT);
			
			$list_path_friends = array_merge_recursive($list_path_good_friends, $list_path_normal_friends, $list_path_parents);
			
			$friend_html='';
			$number_of_images=3;
			$number_friends=0;
			$list_friends_id=array();
			$list_friends_dir=array();
			$list_friends_file=array();
			
			if (count($list_path_friends)!=0) {
				$friends_count = count($list_path_friends['id_friend']); 
				for ($z=0;$z< $friends_count ;$z++) {
					$list_friends_id[]  = $list_path_friends['id_friend'][$z]['friend_user_id'];
					$list_friends_dir[] = $list_path_friends['path_friend'][$z]['dir'];
					$list_friends_file[]= $list_path_friends['path_friend'][$z]['file'];
				}
				$number_friends= count($list_friends_dir);
				$number_loop   = ($number_friends/$number_of_images);
				$loop_friends  = ceil($number_loop);
				$j=0;
				$friend_html.= '<div id="friend-container">';				
				api_display_tool_title(get_lang('Friends'));
				
				$friend_html.= '<div id="friend-header">';
					//$friend_html.=  $friends_count.' '.get_lang('Friends');
				if ($friends_count == 1)
					$friend_html.= '<div style="float:left;">'.$friends_count.' '.get_lang('Friend').'</div>';
				else $friend_html.= '<div style="float:left;">'.$friends_count.' '.get_lang('Friends').'</div>';
					$friend_html.= '<div style="float:right;">'.get_lang('SeeAll').'</div>';
				$friend_html.= '</div><br/>';				
							
				for ($k=0;$k<$loop_friends;$k++) {				
					if ($j==$number_of_images) {
						$number_of_images=$number_of_images*2;
					}
					while ($j<$number_of_images) {
						if ($list_friends_file[$j]<>"") {
							$my_user_info=api_get_user_info($list_friends_id[$j]);
							$name_user=$my_user_info['firstName'].' '.$my_user_info['lastName'];
							//class="image-social-content"
							$friend_html.='&nbsp;<div id=div_'.$list_friends_id[$j].' style="float:left" >';
							$margin_top = 10;
							if ($k==0) $margin_top = 0;
							$friend_html.='<a href="profile.php?u='.$list_friends_id[$j].'">';
								$friend_html.='<img src="'.$list_friends_dir[$j]."/".$list_friends_file[$j].'" width="90" height="110" style="margin-left:3px;margin-rigth:3px;margin-top:'.$margin_top.'px;margin-bottom:3px;" id="imgfriend_'.$list_friends_id[$j].'" title="'.$name_user.'" "/>';
								$friend_html.= '<br />'.$my_user_info['firstName'].'<br />'.$my_user_info['lastName'];
							$friend_html.= '</a>';
							$friend_html.= '</div>&nbsp;';				
						}
						$j++; 
					}				
				}
				$friend_html.='</div>';
			}
			echo $friend_html; 
			echo '<div class="clear"></div><br />';
			
			api_display_tool_title(get_lang('PendingInvitations'));
			//Pending invitations		
			$pending_invitations = UserFriend::get_list_invitation_of_friends_by_user_id($user_id);
			$list_get_path_web=UserFriend::get_list_web_path_user_invitation_by_user_id($user_id);
			$count_pending_invitations = count($pending_invitations);
			
			if ($count_pending_invitations > 0) {
				for ($i=0;$i<$count_pending_invitations;$i++) {
					//var_dump($invitations);
					
					echo '<img src="'.$list_get_path_web[$i]['dir'].'/'.$list_get_path_web[$i]['file'].'" width="60px">';
					//echo $pending_invitations[$i]['user_sender_id'];
					echo $pending_invitations[$i]['content'];
					echo '<br />';
				}
			}
		
			echo '<br />';
			//--Productions
			api_display_tool_title(get_lang('Productions'));
			echo '<div class="rounded1">';
			echo UserManager::build_production_list($user_id);
			echo '</div>';
			
			// Images uploaded by course
			api_display_tool_title(get_lang('ImagesUploaded'));
			echo '<div class="rounded2">';
			foreach ($course_list_code as $course) { 
				echo UserManager::get_user_upload_files_by_course($user_id,$course['code']);
			}
			echo '</div>';
		}
		
		
	
		
	echo '</div>'; // end of content section
		
		
echo '<div id="social-profile-container">';
	// LEFT COLUMN
	echo '<div id="social-profile-left">';
			//--- User image
    	  	echo '<img src='.$img_array['dir'].$img_array['file'].' /> <br /><br />';
    	  	
    	  	if (api_get_user_id() == $user_id) {
    	  		// if i'm me
    	  		echo Display::return_icon('email.gif');
    	  		echo '&nbsp;&nbsp;<a href="../auth/profile.php?show=1">'.get_lang('MyInbox').'</a><br />'; 
    	  		echo Display::return_icon('edit.gif');
    	  		echo '&nbsp;&nbsp;<a href="../auth/profile.php?show=1">'.get_lang('EditInformation').'</a>';
    	  			
    	  	} else {
    	  		echo Display::return_icon('message_new.png');
    	  		echo '&nbsp;&nbsp;<a href="#">'.get_lang('SendMessage').'</a>';	
    	  	}
    	  	echo '<br /><br />';
    	  	
    	  	// Send message or Add to friend links
    	  	if (!$show_full_profile) {
	    	  	echo Display::return_icon('message_new.png');
	    	  	echo '&nbsp;&nbsp;<a href="#">';
	    	  		echo get_lang('AddToFriends');
	    	  	echo '</a>';		
    	  	}
    	  	
    	  	if ($show_full_profile) {    	  		
				//-- Extra Data
				api_display_tool_title(get_lang('ExtraInformation'));			
				$extra_user_data = UserManager::get_extra_user_data($user_id);
				echo '<div class="rounded left-side">';
					foreach($extra_user_data as $key=>$data) {
						echo ucfirst($key).': '.$data;
						echo '<br />';
					}
				echo '</div>';			
				echo '<br /><br />';
				
				// ---- My Agenda Items
				api_display_tool_title(get_lang('MyAgenda'));
				$tbl_personal_agenda = Database :: get_user_personal_table(TABLE_PERSONAL_AGENDA);
				
				echo '<div class="rounded left-side">';	
					echo show_simple_personal_agenda($user_id);
				echo '</div>';
				echo '<br /><br />';
					
				//-----Announcements
				api_display_tool_title(get_lang('Announcements'));		
	    	  	foreach ($course_list_code as $course) {	  
	    	  		echo '<h2>'.$course['title'].'</h2>';
					echo '<div class="rounded left-side">';							
						get_all_annoucement_by_user_course($course['dbName'],$user_id);
					echo '</div>';
					echo '<br/>';
	    	  	}			
    	  	}
    echo '</div>';
    
  	// CENTER COLUMN
	echo '<div id="social-profile-content">';
		    //--- Basic Information			
			api_display_tool_title(get_lang('Information'));  //class="social-profile-info"
			
			if ($show_full_profile) {		 
				echo '<div class="social-profile-info" >';
					echo '<dl>';					
					echo '<dt>'.mb_convert_encoding(get_lang('UserName'),'UTF-8',$charset).'</dt>		<dd>'. mb_convert_encoding($user_info['username'],'UTF-8',$charset).'	</dd>';
					
					if (!empty($user_info['firstname']) || !empty($user_info['lastname']))
						echo '<dt>'.mb_convert_encoding(get_lang('Name'),'UTF-8',$charset).'</dt>		<dd>'. mb_convert_encoding($user_info['firstname'].' '.$user_info['lastname'],'UTF-8',$charset).'</dd>';
					
					if (!empty($user_info['official_code']))					
						echo '<dt>'.mb_convert_encoding(get_lang('OfficialCode'),'UTF-8',$charset).'</dt>	<dd>'. mb_convert_encoding($user_info['official_code'],'UTF-8',$charset).'</dd>';
					if (!empty($user_info['email']))
						if (api_get_setting('show_email_addresses')=='true')
							echo '<dt>'.mb_convert_encoding(get_lang('Email'),'UTF-8',$charset).'</dt>			<dd>'. mb_convert_encoding($user_info['email'],'UTF-8',$charset).'</dd>';
					if (!empty($user_info['phone']))
						echo '<dt>'.mb_convert_encoding(get_lang('Phone'),'UTF-8',$charset).'</dt>			<dd>'. mb_convert_encoding($user_info['phone'],'UTF-8',$charset).'</dd>';
					echo '</dl>';
				echo '</div>';			
			} else {
				echo '<div class="social-profile-info" >';
					echo '<dl>';
					if (!empty($user_info['firstname']) || !empty($user_info['lastname']))
						echo '<dt>'.mb_convert_encoding(get_lang('Name'),'UTF-8',$charset).'</dt>		<dd>'. mb_convert_encoding($user_info['firstname'].' '.$user_info['lastname'],'UTF-8',$charset).'</dd>';
					echo '</dl>';
				echo '</div>';
			}
			
			echo '<div class="clear"></div><br />';
			
			// COURSES LIST
			if ($show_full_profile) {			
				api_display_tool_title(ucfirst(get_lang('Courses')));			
				//print_r($personal_course_list);		
				//echo '<pre>';
				if ( is_array($list) ) {
					//Courses whithout sessions
					$old_user_category = 0;
					$i=1;
					foreach($list as $key=>$value) {
						if ( empty($value[2]) ) { //if out of any session				
													
							echo $value[1];
							echo '<div id="loading'.$i.'"></div>';
							//class="social-profile-rounded maincourse"							
							echo '<div id="social_content'.$i.'" class="rounded maincourse" style="background : #EFEFEF; padding:0px; ">';							
							echo '</div>';		
							$i++;
						}
					}				
					$listActives = $listInactives = $listCourses = array();
					foreach ( $list as $key=>$value ) {
						if ( $value['active'] ) { //if the session is still active (as told by get_logged_user_course_html())
							$listActives[] = $value;
						} elseif ( !empty($value[2]) ) { //if there is a session but it is not active
							$listInactives[] = $value;
						}
					}
					/*
					// --- Session registered
					api_display_tool_title(get_lang('Sessions'));
					if(count($listActives)>0) {			
						echo "<ul class=\"courseslist\">\n";	
						foreach ($listActives as $key => $value) {
							if (!empty($value[2])) {
								if ((isset($old_session) && $old_session != $value[2]) or ((!isset($old_session)) && isset($value[2]))) {
									$old_session = $value[2];
									if ($key != 0) {
										echo '</ul>';
									}
									//echo '<ul class="session_box"><li class="session_box_title">'.$value[3]['title'].' '.$value[3]['dates'].'</li>';
									echo '<ul>';
									if ( !empty($value[3]['coach']) ) {
										echo '<li class="session_box_coach">'.$value[3]['coach'].'</li>';
									}
									echo '</ul>';	
									echo '<ul class="session_course_item">';
								}
							}
							echo $value[1];	
						}	
						echo '</ul>';	
					}
					*/
				}
				echo '</ul>';	
				
			
				//echo '<pre>';
				foreach ($course_list_code as $course) {	
					// course name
					//echo ucfirst(get_lang('InCourse')).' '.$course['code'].'<br /><br />';				
					
					/*
					//------Blog posts				
					api_display_tool_title(get_lang('BlogPosts'));
						
					echo '<div class="rounded social-profile-post">';		
					get_blog_post_from_user($course['dbName'], $user_id);
					echo '</div>';
					echo '<br />';
					
					//------Blog comments				
					api_display_tool_title(get_lang('BlogComments'));
					echo '<div class="rounded" social-profile-post >';			
						get_blog_comment_from_user($course['dbName'], $user_id);
					echo '</div>';
					echo '<br />';
							
					
					//------Forum messages
					api_display_tool_title(get_lang('Forum'));
					//print_r($course);
					$table_forums 			= Database :: get_course_table(TABLE_FORUM,$course['dbName']);
					$table_threads 			= Database :: get_course_table(TABLE_FORUM_THREAD,$course['dbName']);
					$table_posts 			= Database :: get_course_table(TABLE_FORUM_POST,$course['dbName']);
					$table_item_property 	= Database :: get_course_table(TABLE_ITEM_PROPERTY,$course['dbName']);
					$table_users 			= Database :: get_main_table(TABLE_MAIN_USER);
					
					echo '<div class="rounded social-profile-post">';			
					get_all_post_from_user($user_id);
					echo '</div>';	
					echo '<br />';
					*/
				}
				
				echo '<br />';
				/* this should be somewhere
				//-- Competences
				api_display_tool_title(get_lang('MoreInfo'));
				echo '<br />';
				echo get_lang('Competences');
				echo '<div class="rounded social-profile-post">';
				echo $user_info['competences']; echo '<br />';
				echo '</div>';
				
				echo get_lang('Diplomas');
				echo '<div class="rounded social-profile-post">';
				echo $user_info['diplomas']; echo '<br />';
				echo '</div>';
				
				echo get_lang('OpenArea');
				echo '<div class="rounded social-profile-post">';
				echo $user_info['openarea']; echo '<br />';
				echo '</div>';
				
				echo get_lang('Teach');
				echo '<div class="rounded social-profile-post">';
				echo $user_info['teach']; echo '<br />';
				echo '</div>';
				*/		            
		echo '</div>';
		}
echo '</div>';
			
    		
	
                    
           
echo '</div>';


echo '</div>'; //from the main
Display :: display_footer();
?>