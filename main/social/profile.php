<?php
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2009 Dokeos SPRL
	Copyright (c) Julio Montoya Armas 
	Copyright (c) Isaac Flores Paz 
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
* @author Isaac Flores Paz <florespaz_isaac@hotmail.com>
=============================================================================
*/


$language_file = array('registration','messages','userInfo','admin');
$cidReset = true;	
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
$htmlHeadXtra[] = '<script type="text/javascript" src="../inc/lib/javascript/thickbox.js"></script>';
$htmlHeadXtra[] = '<link rel="stylesheet" href="../inc/lib/javascript/thickbox.css" type="text/css" media="projection, screen">';
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
	var content = \'social_content\' + id_elem[1]; 
	if (my_image=="nolines_plus.gif") {
		$(id_button).attr("src","../img/nolines_minus.gif"); var action = "load_course";
		$("div#"+content).show("slow");	
	} else {
		$("div#"+content).hide("slow");	
		$(id_button).attr("src","../img/nolines_plus.gif"); var action = "unload";
		return false;
	}

	 $.ajax({
		contentType: "application/x-www-form-urlencoded",
		beforeSend: function(objeto) {
		$("div#"+content).html("<img src=\'../inc/lib/javascript/indicator.gif\' />"); },
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
$htmlHeadXtra[] = '<script type="text/javascript">
$(document).ready(function (){
	$("input#id_btn_send_invitation").bind("click", function(){
		if (confirm("'.get_lang('SendMessageInvitation').'")) {
			$("#form_register_friend").submit();
		}
	}); 
});
function change_panel (mypanel_id,myuser_id) {
		$.ajax({
			contentType: "application/x-www-form-urlencoded",
			beforeSend: function(objeto) {
			$("#id_content_panel").html("<img src=\'../inc/lib/javascript/indicator.gif\' />"); },
			type: "POST",
			url: "../messages/send_message.php",
			data: "panel_id="+mypanel_id+"&user_id="+myuser_id,
			success: function(datos) {
			 $("div#id_content_panel_init").html(datos);
			 $("div#display_response_id").html("");
			}
		});	
}
function action_database_panel (option_id,myuser_id) {
	
	if (option_id==5) {
		my_txt_subject=$("#txt_subject_id").val();
	} else {
		my_txt_subject="clear";
	}
		my_txt_content=$("#txt_area_invite").val();
	if (my_txt_content.length==0 || my_txt_subject.length==0) {
		$("#display_response_id").html("&nbsp;&nbsp;&nbsp;'.get_lang('MessageInvitationNotSent').'");
		setTimeout("message_information_display()",3000);
		return false;
	}
	$.ajax({
		contentType: "application/x-www-form-urlencoded",
		beforeSend: function(objeto) {
		$("#display_response_id").html("<img src=\'../inc/lib/javascript/indicator.gif\' />"); },
		type: "POST",
		url: "../messages/send_message.php",
		data: "panel_id="+option_id+"&user_id="+myuser_id+"&txt_subject="+my_txt_subject+"&txt_content="+my_txt_content,
		success: function(datos) {
		 $("#display_response_id").html(datos);
		}
	});	
}	
function display_hide () {
		setTimeout("hide_display_message()",3000);
}
function message_information_display() {
	$("#display_response_id").html("");
}
function hide_display_message () {
	$("div#display_response_id").html("");
	try {
		$("#txt_subject_id").val("");
		$("#txt_area_invite").val("");
	}catch(e) {
		$("#txt_area_invite").val("");
	}
}	
function register_friend(element_input) {
 if(confirm("'.get_lang('AddToFriends').'")) {
		name_button=$(element_input).attr("id");
		name_div_id="id_"+name_button.substring(13);
		user_id=name_div_id.split("_");
		user_friend_id=user_id[1];
		 $.ajax({
			contentType: "application/x-www-form-urlencoded",
			beforeSend: function(objeto) {
			$("div#dpending_"+user_friend_id).html("<img src=\'../inc/lib/javascript/indicator.gif\' />"); },
			type: "POST",
			url: "../social/register_friend.php",
			data: "friend_id="+user_friend_id+"&is_my_friend="+"friend",
			success: function(datos) {
			// $("div#dpending_"+user_friend_id).html(datos);
			 $("#id_reload").submit();
			}
		});
 }
}

</script>';
if (isset($_GET['shared'])) {
	$my_link='../social/index.php';
	$link_shared='shared='.Security::remove_XSS($_GET['shared']);
} else {
	$my_link='../auth/profile.php';
	$link_shared='';
}
$interbreadcrumb[]= array ('url' =>$my_link,'name' => get_lang('ModifyProfile') );

$interbreadcrumb[]= array (
	'url' => '../social/profile.php?'.$link_shared.'#remote-tab-1',
	'name' => get_lang('ViewMySharedProfile')
);

if (isset($_GET['u'])) {
	$info_user=api_get_user_info(Security::remove_XSS($_GET['u']));	
	$interbreadcrumb[]= array (
		'url' => '#',
		'name' => $info_user['firstName'].' '.$info_user['lastName']
	);	
}
if (isset($_GET['u'])) {
	$param_user='u='.Security::remove_XSS($_GET['u']);
}else {
	$info_user=api_get_user_info(api_get_user_id());
	$param_user='';	
}
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
	//$my_course['creation_date'];
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
			
//echo '<div id="actions">';
	//echo '<a href="../auth/profile.php?show=1"">'.Display::return_icon('edit.gif').'&nbsp;'.mb_convert_encoding(get_lang('EditInformation'),'UTF-8',$charset).'</a>';
//echo '</div>';

//Setting some course info 
$my_user_id=isset($_GET['u']) ? Security::remove_XSS($_GET['u']) : api_get_user_id();
$personal_course_list = UserManager::get_personal_session_course_list($my_user_id);
$course_list_code = array();
$i=1;
//print_r($personal_course_list);
foreach ($personal_course_list as $my_course) {
	if ($i<=10) {
		$list[] = get_logged_user_course_html($my_course,$i);	
		$course_list_code[] = array('code'=>$my_course['c'],'dbName'=>$my_course['db'], 'title'=>$my_course['i']);
	} else {
		break;
	}
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
				$friend_html .= '<div class="actions-profile">'.get_lang('SocialFriend').'</div>';	
				$friend_html.= '<div id="friend-container">';							
					$friend_html.= '<div id="friend-header">';
							//$friend_html.=  $friends_count.' '.get_lang('Friends');
						if ($friends_count == 1)
							$friend_html.= '<div style="float:left;">'.$friends_count.' '.get_lang('Friend').'</div>';
						else 
							$friend_html.= '<div style="float:left;">'.$friends_count.' '.get_lang('Friends').'</div>';
							
						if (api_get_user_id() == $user_id)	
							$friend_html.= '<div style="float:right;"><a href="index.php?#remote-tab-6">'.get_lang('SeeAll').'</a></div>';
													
					$friend_html.= '</div><br/>'; // close div friend-header
						
								
				for ($k=0;$k<$loop_friends;$k++) {				
					if ($j==$number_of_images) {
						$number_of_images=$number_of_images*2;
					}
					while ($j<$number_of_images) {
						if ($list_friends_file[$j]<>"") {
							$my_user_info=api_get_user_info($list_friends_id[$j]);
							$name_user=$my_user_info['firstName'].' '.$my_user_info['lastName'];
							//class="image-social-content"
							$friend_html.='&nbsp;<div id=div_'.$list_friends_id[$j].' style="float:left;" >';
							$margin_top = 10;
							if ($k==0) $margin_top = 0;
							$friend_html.='<a href="profile.php?u='.$list_friends_id[$j].'&amp;'.$link_shared.'">';
								$friend_html.='<img src="'.$list_friends_dir[$j]."/".$list_friends_file[$j].'" width="90px" height="110px" style="margin-left:3px;margin-right:3px;margin-top:'.$margin_top.'px;margin-bottom:3px;" id="imgfriend_'.$list_friends_id[$j].'" title="'.$name_user.'" />';
								$friend_html.= '<br />'.$my_user_info['firstName'].'<br />'.$my_user_info['lastName'];
							$friend_html.= '</a>';
							$friend_html.= '</div>&nbsp;';				
						}
						$j++; 
					}				
				}
				//$friend_html.='</div>'; // close the div friend-container
			} else {
					$friend_html .= '<div class="actions-profile">'.get_lang('Friends').'</div>';
					$friend_html.= '<div id="friend-container">';					
					$friend_html.= '<div id="friend-header">';
					$friend_html.= '<div style="float:left;">'.get_lang('Friends').'</div>';
					$friend_html.= '<div style="float:right;">'.get_lang('SeeAll').'</div>';
					$friend_html.= '</div><br/><br/>'; // close div friend-header					
			}
			$friend_html.= '</div>';		
			echo $friend_html; 				
			//Pending invitations	
			if (!isset($_GET['u']) || (isset($_GET['u']) && $_GET['u']==api_get_user_id())) {
			$pending_invitations = UserFriend::get_list_invitation_of_friends_by_user_id(api_get_user_id());
			$list_get_path_web=UserFriend::get_list_web_path_user_invitation_by_user_id(api_get_user_id());
			$count_pending_invitations = count($pending_invitations);
			//echo '<div class="clear"></div><br />';		
				//javascript:register_friend(this)
				//var_dump($pending_invitations);
			echo '<div class="clear"></div><br />';
			echo '<div id="social-profile-invitations" >';
			if ($count_pending_invitations > 0) {
				echo '<div class="actions-profile">';
				echo get_lang('PendingInvitations');
				echo '</div>';
				for ($i=0;$i<$count_pending_invitations;$i++) {
					//var_dump($invitations);
					echo '<div id="dpending_'.$pending_invitations[$i]['user_sender_id'].'">'; 
						echo '<div style="float:left;width:60px;" >';
							echo '<img style="margin-bottom:5px;" src="'.$list_get_path_web[$i]['dir'].'/'.$list_get_path_web[$i]['file'].'" width="60px">';
						echo '</div>';
						echo '<div style="padding-left:70px;">';
							echo ' '.substr($pending_invitations[$i]['content'],0,50);
							echo '<br />';
							echo '<a id="btn_accepted_'.$pending_invitations[$i]['user_sender_id'].'" onclick="register_friend(this)" href="javascript:void(0)">'.get_lang('SocialAddToFriends').'</a>';
							echo '<div id="id_response">&nbsp;</div>';
						echo '</div>';
					echo '</div>';
					echo '<div class="clear"></div>';
				}
			}
			echo '</div>';
			}
			
			//--Productions			
			$production_list =  UserManager::build_production_list($user_id);
			if (!empty($production_list )) {
				echo '<div class="clear"></div><br />';
				echo '<div class="actions-profile">';
				echo get_lang('MyProductions');
				echo '</div>';
				echo '<div class="rounded1">';
				echo $production_list;
				echo '</div>';	
			}			
			
			// Images uploaded by course			
			$file_list = '';
			foreach ($course_list_code as $course) { 
				$file_list.= UserManager::get_user_upload_files_by_course($user_id,$course['code']);
			}
			if (!empty($file_list)) {
				echo '<div class="clear"></div><br />';
				echo '<div class="actions-profile">';
				echo get_lang('ImagesUploaded');
				echo '</div>';
				echo '</br><div class="rounded2">';
				echo $file_list;
				echo '</div>';		
			}
			
			
			//loading this information 
			
			//-- Competences
			if (!empty($user_info['competences']) || !empty($user_info['diplomas']) || !empty($user_info['openarea']) || !empty($user_info['teach']) ) {
				echo '<div class="actions-profile">';
				echo get_lang('MoreInfo');
				echo '</div>';
			}
			$cut_size = 220;
			if (!empty($user_info['competences'])) {		
				echo '<br />';
				echo get_lang('Competences');
				echo '<div class="rounded social-profile-post" style="width:268px;">';
				echo cut($user_info['competences'],$cut_size);
				echo '<br />';
				echo '</div>';
			}
			
			if (!empty($user_info['diplomas'])) {	
				echo get_lang('Diplomas');
				echo '<div class="rounded social-profile-post" style="width:268px;" >';
				echo cut($user_info['diplomas'],$cut_size); 
				echo '<br />';
				echo '</div>';
			}
			if (!empty($user_info['openarea'])) {	
				echo get_lang('OpenArea');
				echo '<div class="rounded social-profile-post" style="width:268px;" >';
				echo cut($user_info['openarea'],$cut_size); 
				echo '<br />';
				echo '</div>';
			}
			if (!empty($user_info['teach'])) {	
				echo get_lang('Teach');
				echo '<div class="rounded social-profile-post" style="width:268px;" >';
				echo cut($user_info['teach'],$cut_size);
				echo '<br />';
				echo '</div>';
			}				
		}
		
	echo '</div>'; // end of content section
//	echo '</div>'; 
			
		
echo '<div id="social-profile-container">';
	// LEFT COLUMN
	echo '<div id="social-profile-left">';
			//--- User image
    	  	echo '<img src='.$img_array['dir'].$img_array['file'].' /> <br /><br />';
    	  	
    	  	if (api_get_user_id() == $user_id) {
    	  		// if i'm me
    	  		echo Display::return_icon('email.gif');
    	  		echo '&nbsp;&nbsp;<a href="../social/index.php#remote-tab-2">'.get_lang('MyInbox').'</a><br />'; 
    	  		echo Display::return_icon('edit.gif');
    	  		echo '&nbsp;&nbsp;<a href="../auth/profile.php?show=1">'.get_lang('EditInformation').'</a>';
    	  			
    	  	} else {
    	  		echo '&nbsp;&nbsp;<a href="../messages/send_message_to_userfriend.inc.php?height=365&width=610&user_friend='.$user_id.'&view=profile&view_panel=true" class="thickbox" title="'.get_lang('SendMessage').'">'.Display::return_icon('message_new.png').'&nbsp;&nbsp;'.get_lang('SendMessage').'</a><br />'; 
    	  		//echo '&nbsp;&nbsp;<a href="#">'.get_lang('SendMessage').'</a>';	
    	  	}
    	  	echo '<br /><br />';
    	  	
    	  	// Send message or Add to friend links
    	  	/*if (!$show_full_profile) {
    	  		echo '&nbsp;&nbsp;<a href="../messages/send_message_to_userfriend.inc.php?height=365&width=610&user_friend='.$user_id.'&view=profile" class="thickbox" title="'.get_lang('SendMessage').'">'.Display::return_icon('message_new.png').'&nbsp;&nbsp;'.get_lang('SendMessage').'</a><br />'; 		
    	  	}*/
    	  	
    	  	if ($show_full_profile) {    	  		
				//-- Extra Data							
				$extra_user_data = UserManager::get_extra_user_data($user_id);
				if (is_array($extra_user_data) && count($extra_user_data)>0 ) {
					echo '<div class="actions-profile">';
					echo get_lang('ExtraInformation');
					echo '</div>';
					echo '<div class="rounded left-side">';
						foreach($extra_user_data as $key=>$data) {
							echo ucfirst($key).': '.$data;
							echo '<br />';
						}
					echo '</div>';			
					echo '<br /><br />';
				}
				// ---- My Agenda Items
				$my_agenda_items = show_simple_personal_agenda($user_id);
				if (!empty($my_agenda_items)) {
					echo '<div class="actions-profile">';					
					echo get_lang('MyAgenda');
					echo '</div>';
					$tbl_personal_agenda = Database :: get_user_personal_table(TABLE_PERSONAL_AGENDA);
					echo '<div class="rounded left-side">';	
					echo $my_agenda_items; 
					echo '</div>';
					echo '<br /><br />';
				}
					
				//-----Announcements
				$announcement_content = '';		
				$my_announcement_by_user_id=isset($_GET['u']) ? Security::remove_XSS($_GET['u']) : api_get_user_id();
		    	foreach ($course_list_code as $course) {
	    			$content = get_all_annoucement_by_user_course($course['dbName'],$my_announcement_by_user_id);	 			
	    	  		if (!empty($content)) {	 		    	  			  
		    	  		$announcement_content.= '<h3>'.$course['title'].'</h3>';
						$announcement_content.= '<div class="rounded left-side">';							
						$announcement_content.= $content;	
						$announcement_content.= '</div>';
						$announcement_content.= '<br/>';
	    	  		}
	    	  	}
	    	  	
	    	  	if(!empty($announcement_content)) {
	    	  		echo '<div class="actions-profile">';
	    	  		echo get_lang('Announcements');
	    	  		echo '</div>';
	    	  		echo $announcement_content;
	    	  	}					
    	  	}
    echo '</div>';
    
  	// CENTER COLUMN
	echo '<div id="social-profile-content">';
		    //--- Basic Information	
		    echo '<div class="actions-profile">';		
			echo get_lang('Information');  //class="social-profile-info"
			echo '</div>';
			if ($show_full_profile) {		 
				echo '<div class="social-profile-info" >';					
					echo '<dt>'.get_lang('UserName').'</dt>
						  <dd>'. $user_info['username'].'	</dd>';					
					if (!empty($user_info['firstname']) || !empty($user_info['lastname']))
						echo '<dt>'.get_lang('Name').'</dt>		
						  	  <dd>'. $user_info['firstname'].' '.$user_info['lastname'].'</dd>';					
					if (!empty($user_info['official_code']))					
						echo '<dt>'.get_lang('OfficialCode').'</dt>	
						  <dd>'.$user_info['official_code'].'</dd>';
					if (!empty($user_info['email']))
						if (api_get_setting('show_email_addresses')=='true')
							echo '<dt>'.get_lang('Email').'</dt>			
						  <dd>'.$user_info['email'].'</dd>';
					if (!empty($user_info['phone']))
						echo '<dt>'.get_lang('Phone').'</dt>			
						  <dd>'. $user_info['phone'].'</dd>';
					echo '</dl>';
				echo '</div>';			
			} else {
				echo '<div class="social-profile-info" >';
					echo '<dl>';
					if (!empty($user_info['firstname']) || !empty($user_info['lastname']))
						echo '<dt>'.get_lang('Name').'</dt>		
						  <dd>'. $user_info['firstname'].' '.$user_info['lastname'].'</dd>';					
				echo '</div>';
			}			
			echo '<div class="clear"></div><br />';			
			// COURSES LIST
			if ($show_full_profile) {							
				//print_r($personal_course_list);		
				//echo '<pre>';
				if ( is_array($list) ) {
					echo '<div class="actions-profile">';
					echo ucfirst(get_lang('MyCourses'));
					echo '</div>';
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
				echo '<br />';				            
		echo '</div>';
		}
	echo '</div>';
echo '</div>';

echo '</div>'; //from the main
echo '<form id="id_reload" action="#"></form>';
Display :: display_footer();
?>