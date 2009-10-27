<?php //$id: $
/* For licensing terms, see /dokeos_license.txt */
/**
* This is the profile social main page
* @author Julio Montoya <gugli100@gmail.com>
* @author Isaac Flores Paz <florespaz_isaac@hotmail.com>
* @package dokeos.social
*/

/**
 * Init
 */
$language_file = array('registration','messages','userInfo','admin','forum','blog');
$cidReset = true;
require '../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'usermanager.lib.php';
require_once api_get_path(LIBRARY_PATH).'social.lib.php';
require_once api_get_path(LIBRARY_PATH).'array.lib.php';
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

$libpath = api_get_path(LIBRARY_PATH);
require_once api_get_path(SYS_CODE_PATH).'calendar/myagenda.inc.php';
require_once api_get_path(SYS_CODE_PATH).'announcements/announcements.inc.php';
require_once $libpath.'course.lib.php';
require_once $libpath.'formvalidator/FormValidator.class.php';
require_once $libpath.'magpierss/rss_fetch.inc';

api_block_anonymous_users();

$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jquery.js" type="text/javascript" language="javascript"></script>'; //jQuery
$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jquery.corners.min.js" type="text/javascript" language="javascript"></script>'; //jQuery corner
$htmlHeadXtra[] = '<script type="text/javascript" src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/thickbox.js"></script>';
$htmlHeadXtra[] = '<link rel="stylesheet" href="'.api_get_path(WEB_LIBRARY_PATH).'javascript/thickbox.css" type="text/css" media="projection, screen">';
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
		if (confirm("'.get_lang('SendMessageInvitation', '').'")) {
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
		$("#display_response_id").html("&nbsp;&nbsp;&nbsp;'.get_lang('MessageInvitationNotSent', '').'");
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
				$("form").submit()
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

if (isset($_GET['u']) && is_numeric($_GET['u'])) {
	$info_user=api_get_user_info($_GET['u']);
	$interbreadcrumb[]= array (
		'url' => 'javascript: void(0);',
		'name' => api_get_person_name($info_user['firstName'], $info_user['lastName'])
	);
}
if (isset($_GET['u'])) {
	$param_user='u='.Security::remove_XSS($_GET['u']);
}else {
	$info_user=api_get_user_info(api_get_user_id());
	$param_user='';
}
$_SESSION['social_user_id'] = $user_id;
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
/**
 * Get user's feeds
 * @param   int User ID
 * @param   int Limit of posts per feed
 * @return  string  HTML section with all feeds included
 * @author  Yannick Warnier
 * @since   Dokeos 1.8.6.1
 */
function get_user_feeds($user,$limit=5) {
    if (!function_exists('fetch_rss')) { return '';}
	$fields = UserManager::get_extra_fields();
    $feed_fields = array();
    $feeds = array();
    $res = '<div class="sectiontitle">'.get_lang('RSSFeeds').'</div>';
    $res .= '<div class="social-content-training">';
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
    $res .= '</div>';
    $res .= '<div class="clear"></div><br />';
    return $res;
}
/**
 * Display
 */
Display :: display_header(null);

// @todo here we must show the user information as read only
//User picture size is calculated from SYSTEM path

$img_array= UserManager::get_user_picture_path_by_id($user_id,'web',true,true);

//print_r($user_info);
// Added by Ivan Tcholakov, 03-APR-2009.
if (USE_JQUERY_CORNERS_SCRIPT) {
//
echo $s="<script>$(document).ready( function(){
		  $('.rounded').corners();
		});</script>";
//
}
//

//echo '<div id="actions">';
//echo '<a href="../auth/profile.php?show=1"">'.Display::return_icon('edit.gif').'&nbsp;'.api_convert_encoding(get_lang('EditInformation'),'UTF-8',$charset).'</a>';
//echo '</div>';

//Setting some course info
$my_user_id=isset($_GET['u']) ? Security::remove_XSS($_GET['u']) : api_get_user_id();
$personal_course_list = UserManager::get_personal_session_course_list($my_user_id);
$course_list_code = array();
$i=1;
//print_r($personal_course_list);



if (is_array($personal_course_list)) {
	foreach ($personal_course_list as $my_course) {
		if ($i<=10) {
			$list[] = get_logged_user_course_html($my_course,$i);
			//$course_list_code[] = array('code'=>$my_course['c'],'dbName'=>$my_course['db'], 'title'=>$my_course['i']); cause double
			$course_list_code[] = array('code'=>$my_course['c'],'dbName'=>$my_course['db']);

		} else {
			break;
		}
		$i++;
	}
	//to avoid repeted courses
	$course_list_code = array_unique_dimensional($course_list_code);
}

echo '<div class="actions-title">';
if ($user_id == api_get_user_id())
	echo get_lang('ViewMySharedProfile');
else
	echo get_lang('ViewSharedProfile').' - '.api_get_person_name($user_info['firstname'], $user_info['lastname']);

echo '</div>';

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
				$friend_html .= '<div class="sectiontitle">'.get_lang('SocialFriend').'</div>';
				$friend_html.= '<div id="friend-container" class="social-friend-container">';
					$friend_html.= '<div id="friend-header">';
							//$friend_html.=  $friends_count.' '.get_lang('Friends');
						if ($friends_count == 1)
							$friend_html.= '<div style="float:left;">'.$friends_count.' '.get_lang('Friend').'</div>';
						else
							$friend_html.= '<div style="float:left;">'.$friends_count.' '.get_lang('Friends').'</div>';
						if (api_get_user_id() == $user_id)
					$friend_html.= '<div style="float:right;"><a href="index.php?#remote-tab-6">'.get_lang('SeeAll').'</a></div>';
					$friend_html.= '</div>'; // close div friend-header

				for ($k=0;$k<$loop_friends;$k++) {
					if ($j==$number_of_images) {
						$number_of_images=$number_of_images*2;
					}
					while ($j<$number_of_images) {
						if ($list_friends_file[$j]<>"") {
							$my_user_info=api_get_user_info($list_friends_id[$j]);
							$name_user=api_get_person_name($my_user_info['firstName'], $my_user_info['lastName']);
							$friend_html.='<div id=div_'.$list_friends_id[$j].' class="image_friend_network" ><span><center>';
							// the height = 92 must be the sqme in the image_friend_network span style in default.css
							$friends_profile = UserFriend::get_picture_user($list_friends_id[$j], $list_friends_file[$j], 92, 'medium_', 'width="85" height="90" ');
							$friend_html.='<a href="profile.php?u='.$list_friends_id[$j].'&amp;'.$link_shared.'">';
							$friend_html.='<img src="'.$friends_profile['file'].'" '.$friends_profile['style'].' id="imgfriend_'.$list_friends_id[$j].'" title="'.$name_user.'" />';
							$friend_html.= '</center></span>';
							$friend_html.= '<center class="friend">'.api_get_person_name($my_user_info['firstName'], $my_user_info['lastName']).'</a></center>';
							$friend_html.= '</div>';
						}
						$j++;
					}
				}
			} else {
				// No friends!! :(
					$friend_html .= '<div class="sectiontitle">'.get_lang('Friends').'</div>';
					$friend_html.= '<div id="friend-container" class="social-friend-container">';
					$friend_html.= '<div id="friend-header">';
				$friend_html.= '<div style="float:left; padding:0px 8px 0px 8px;">'.get_lang('NoFriendsInYourContactList').'<br /><a href="'.api_get_path(WEB_PATH).'whoisonline.php">'.get_lang('TryAndFindSomeFriends').'</a></div>';
				$friend_html.= '</div>'; // close div friend-header
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
				echo '<div class="sectiontitle">';
					echo api_convert_encoding(get_lang('PendingInvitations'),$charset,'UTF-8');
					echo '</div><br />';
				for ($i=0;$i<$count_pending_invitations;$i++) {
					//var_dump($invitations);
						echo '<div id="dpending_'.$pending_invitations[$i]['user_sender_id'].'" class="friend_invitations">';
						echo '<div style="float:left;width:60px;" >';
							echo '<img style="margin-bottom:5px;" src="'.$list_get_path_web[$i]['dir'].'/'.$list_get_path_web[$i]['file'].'" width="60px">';
						echo '</div>';
						echo '<div style="padding-left:70px;">';
								echo ' '.api_convert_encoding(substr($pending_invitations[$i]['content'],0,50),$charset,'UTF-8');
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
				echo '<div class="sectiontitle">';
				echo get_lang('MyProductions');
				echo '</div>';
				echo '<div class="rounded1">';
				echo $production_list;
				echo '</div>';
			}

			// Images uploaded by course
			$file_list = '';
			if (is_array($course_list_code) && count($course_list_code)>0) {
			foreach ($course_list_code as $course) {
				$file_list.= UserManager::get_user_upload_files_by_course($user_id,$course['code']);
			}
			}

			if (!empty($file_list)) {
				echo '<div class="clear"></div><br />';
				echo '<div class="sectiontitle">';
				echo get_lang('ImagesUploaded');
				echo '</div><br />';
				echo '</br><div class="social-content-information">';
				echo $file_list;
				echo '</div>';
			}

			//loading this information

			//-- Competences
			if (!empty($user_info['competences']) || !empty($user_info['diplomas']) || !empty($user_info['openarea']) || !empty($user_info['teach']) ) {
				echo '<div class="clear"></div>';
				echo '<div class="sectiontitle">';
				echo get_lang('MoreInformation');
				echo '</div>';
			}
			echo '<div class="social-content-competences">';
			$cut_size = 220;
			if (!empty($user_info['competences'])) {
				echo '<br />';
				echo '<div class="social-background-content" style="width:100%;">';
				echo '<div class="social-actions-message">'.get_lang('MyCompetences').'</div>';
				echo cut($user_info['competences'],$cut_size);
				echo '</div>';
				echo '<br />';
			}

			if (!empty($user_info['diplomas'])) {
				echo '<div class="social-background-content" style="width:100%;" >';
				echo '<div class="social-actions-message">'.get_lang('MyDiplomas').'</div>';
				echo cut($user_info['diplomas'],$cut_size);
				echo '</div>';
				echo '<br />';
			}
			if (!empty($user_info['openarea'])) {
				echo '<div class="social-background-content" style="width:100%;" >';
				echo '<div class="social-actions-message">'.get_lang('MyPersonalOpenArea').'</div>';
				echo cut($user_info['openarea'],$cut_size);
				echo '</div>';
				echo '<br />';
			}
			if (!empty($user_info['teach'])) {
				echo '<div class="social-background-content" style="width:100%;" >';
				echo '<div class="social-actions-message">'.get_lang('MyTeach').'</div>';
				echo cut($user_info['teach'],$cut_size);
				echo '</div>';
				echo '<br />';
			}
			echo '</div>';
		} else {
			echo '<div class="clear"></div><br />';
		}

	echo '</div>'; // end of content section


echo '<div id="social-profile-container">';
	// LEFT COLUMN
	echo '<div id="social-profile-left">';

			//--- User image
			echo '<div class="social-content-image">';
			echo '<div class="social-background-content" style="width:95%;" align="center">';
			echo '<br/>';
    	  	echo '<img src='.$img_array['dir'].$img_array['file'].' /> <br /><br />';
    	  	echo '</div>';
    	  	echo '</div>';
    	  	echo '<br/>';
    	  	echo '<div class="actions" style="margin-right:5px;">';
    	  	if (api_get_user_id() == $user_id) {
    	  		// if i'm me
    	  		echo '<div>';
    	  		echo Display::return_icon('email.gif');
    	  		echo '&nbsp;<a href="../social/index.php#remote-tab-2">'.get_lang('MyInbox').'</a>&nbsp;';
    	  		echo '</div>';
    	  		echo '<div>';
    	  		echo Display::return_icon('edit.gif');
    	  		echo '&nbsp;<a href="../auth/profile.php?show=1">'.get_lang('EditInformation').'</a>&nbsp;';
    	  		echo '</div>';
    	  	} else {
    	  		echo '&nbsp;<a href="../messages/send_message_to_userfriend.inc.php?height=365&width=610&user_friend='.$user_id.'&view=profile&view_panel=1" class="thickbox" title="'.get_lang('SendMessage').'">'.Display::return_icon('message_new.png').'&nbsp;&nbsp;'.get_lang('SendMessage').'</a><br />';
    	  		//echo '&nbsp;&nbsp;<a href="javascript: void(0);">'.get_lang('SendMessage').'</a>';
    	  	}
    	  	echo '</div>';
    	  	echo '<br />';

    	  	// Send message or Add to friend links
    	  	/*if (!$show_full_profile) {
    	  		echo '&nbsp;&nbsp;<a href="../messages/send_message_to_userfriend.inc.php?height=365&width=610&user_friend='.$user_id.'&view=profile" class="thickbox" title="'.get_lang('SendMessage').'">'.Display::return_icon('message_new.png').'&nbsp;&nbsp;'.get_lang('SendMessage').'</a><br />';
    	  	}*/

			// Extra information

    	  	if ($show_full_profile) {
				//-- Extra Data
				$t_uf = Database :: get_main_table(TABLE_MAIN_USER_FIELD);
				$t_ufo = Database :: get_main_table(TABLE_MAIN_USER_FIELD_OPTIONS);
				$extra_user_data = UserManager::get_extra_user_data($user_id);
				$extra_information = '';
				if (is_array($extra_user_data) && count($extra_user_data)>0 ) {
					$extra_information = '<div class="sectiontitle">';
					$extra_information .= get_lang('ExtraInformation');
					$extra_information .= '</div><br />';
					$extra_information .='<div class="social-content-information">';
					$extra_information_value = '';					
						foreach($extra_user_data as $key=>$data) {
							// get display text, visibility and type from user_field table
							$field_variable = str_replace('extra_','',$key);
							$sql = "SELECT field_display_text,field_visible,field_type,id FROM $t_uf WHERE field_variable ='$field_variable'";
							$res_field = Database::query($sql,__FILE__,__LINE__);
							$row_field = Database::fetch_row($res_field);
							$field_display_text = $row_field[0];
							$field_visible = $row_field[1];
							$field_type = $row_field[2];
							$field_id = $row_field[3];
							if ($field_visible == 1) {
								if (is_array($data)) {
									$extra_information_value .= '<strong>'.ucfirst($field_display_text).':</strong> '.implode(',',$data).'<br />';
								} else {
									if ($field_type == 8) {
										$id_options = explode(';',$data);
										$value_options = array();
										// get option display text from user_field_options table
										foreach ($id_options as $id_option) {
											$sql = "SELECT option_display_text FROM $t_ufo WHERE id = '$id_option'";
											$res_options = Database::query($sql,__FILE__,__LINE__);
											$row_options = Database::fetch_row($res_options);
											$value_options[] = $row_options[0];
										}
										$extra_information_value .= '<strong>'.ucfirst($field_display_text).':</strong> '.implode(' ',$value_options).'<br />';
									} elseif($field_type == 10) {
										$user_tags = UserManager::get_user_tags($user_id, $field_id);
										$tag_tmp = array();
										foreach ($user_tags as $tags) {
											$tag_tmp[] = $tags[0];
										}									 
										if (is_array($user_tags) && count($user_tags)>0) {							
											$extra_information_value .= '<strong>'.ucfirst($field_display_text).':</strong> '.implode(', ',$tag_tmp).'<br />';
										}
									} else {
										$extra_information_value .= '<strong>'.ucfirst($field_display_text).':</strong> '.$data.'<br />';
									}
								}
							}
					}
					// if there are information to show
					if (!empty($extra_information_value)) {
						$extra_information .= $extra_information_value;
				}
					$extra_information .= '</div>';
					$extra_information .= '<br /><br />';
				}
				// 	if there are information to show
				if (!empty($extra_information_value))
					echo $extra_information;


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

				//-----Announcements
				$announcement_content = '';
				$my_announcement_by_user_id=isset($_GET['u']) ? Security::remove_XSS($_GET['u']) : api_get_user_id();

		    	foreach ($course_list_code as $course) {
	    			$content = get_all_annoucement_by_user_course($course['dbName'],$my_announcement_by_user_id);
	    			$course_info=api_get_course_info($course['code']);
	    	  		if (!empty($content)) {
						$announcement_content.= '<div class="social-background-content" style="width:100%">';
						$announcement_content.= '<div class="actions">'.$course_info['name'].'</div>';
						$announcement_content.= $content;
						$announcement_content.= '</div>';
						$announcement_content.= '<br/>';
	    	  		}

	    	  	}

	    	  	if(!empty($announcement_content)) {
	    	  		echo '<div class="sectiontitle">';
	    	  		echo get_lang('Announcements');
	    	  		echo '</div><br/>';
	    	  		echo '<div class="social-content-announcements">';
	    	  		echo $announcement_content.'<br/>';
	    	  		echo '</div>';
	    	  	}
    	  	}
    echo '</div>';


  	// CENTER COLUMN


	echo '<div id="social-profile-content">';

		    //--- Basic Information
		    echo '<div class="sectiontitle">';
			echo get_lang('Information');  //class="social-profile-info"
			echo '</div>';
			echo '<div class="social-content-information">';
			if ($show_full_profile) {
				echo '<div class="social-profile-info" >';
					echo '<dt>'.get_lang('UserName').'</dt>
						  <dd>'. $user_info['username'].'	</dd>';
					if (!empty($user_info['firstname']) || !empty($user_info['lastname']))
						echo '<dt>'.get_lang('Name').'</dt>
						  	  <dd>'. api_get_person_name($user_info['firstname'], $user_info['lastname']).'</dd>';
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
						  <dd>'. api_get_person_name($user_info['firstname'], $user_info['lastname']).'</dd>';
				echo '</div>';
			}

			echo '<div class="clear"></div><br />';
			echo '</div>';
			// COURSES LIST
			if ($show_full_profile) {
				//print_r($personal_course_list);
				//echo '<pre>';
				if ( is_array($list) ) {
					echo '<div class="sectiontitle">';
					echo api_ucfirst(get_lang('MyCourses'));
					echo '</div>';
					echo '<div class="social-content-training">';
					//Courses whithout sessions
					$old_user_category = 0;
					$i=1;
					foreach($list as $key=>$value) {
						if ( empty($value[2]) ) { //if out of any session
							echo $value[1];
							//echo '<div id="loading'.$i.'">&nbsp;</div>';
							//class="social-profile-rounded maincourse"
							echo '<div id="social_content'.$i.'"  style="background : #EFEFEF; padding:0px; ">';
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
				echo '</ul><br />';
                echo get_user_feeds($user_id);
        		echo '</div>';
		    }
            echo '</div>';
        echo '</div>';
    echo '</div>';
echo '</div>'; //from the main
echo '<form id="id_reload" name="id_reload" action="profile.php">&nbsp;</form>';
Display :: display_footer();