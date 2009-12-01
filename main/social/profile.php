<?php //$id: $
/* For licensing terms, see /dokeos_license.txt */
/**
* This is the profile social main page
* @author Julio Montoya <gugli100@gmail.com>
* @author Isaac Flores Paz <florespaz_isaac@hotmail.com>
* @package dokeos.social
*/

$language_file = array('registration','messages','userInfo','admin','forum','blog');
$cidReset = true;
require '../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'usermanager.lib.php';
require_once api_get_path(LIBRARY_PATH).'social.lib.php';
require_once api_get_path(LIBRARY_PATH).'array.lib.php';
require_once api_get_path(LIBRARY_PATH).'group_portal_manager.lib.php';

$user_id = api_get_user_id();
$show_full_profile = true;
//social tab
$this_section = SECTION_SOCIAL;

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
			$my_status= SocialManager::get_relation_between_contacts(api_get_user_id(), $user_id);
			if (in_array($my_status, array(SOCIALPARENT, SOCIALFRIEND, SOCIALGOODFRIEND))) {
				$show_full_profile = true;
			}
			//checking the relationship between my friend and me
			$my_friend_status = SocialManager::get_relation_between_contacts($user_id, api_get_user_id());
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
	$interbreadcrumb[]= array ('url' => 'javascript: void(0);','name' => api_get_person_name($info_user['firstName'], $info_user['lastName']));
}
if (isset($_GET['u'])) {
	$param_user='u='.Security::remove_XSS($_GET['u']);
}else {
	$info_user=api_get_user_info(api_get_user_id());
	$param_user='';
}
$_SESSION['social_user_id'] = intval($user_id);

/**
 * Display
 */
Display :: display_header(null);



// @todo here we must show the user information as read only
//User picture size is calculated from SYSTEM path

$img_array= UserManager::get_user_picture_path_by_id($user_id,'web',true,true);
$big_image =  UserManager::get_picture_user($user_id, $img_array['file'],'','big_');
$big_image = $big_image['file'].$big_image['dir'];
//print_r($user_info);
// Added by Ivan Tcholakov, 03-APR-2009.
if (USE_JQUERY_CORNERS_SCRIPT) {
	echo $s="<script>$(document).ready( function(){
		  $('.rounded').corners();
		});</script>";
}

//Setting some course info
$my_user_id=isset($_GET['u']) ? Security::remove_XSS($_GET['u']) : api_get_user_id();
$personal_course_list = UserManager::get_personal_session_course_list($my_user_id);
$course_list_code = array();
$i=1;
//print_r($personal_course_list);

if (is_array($personal_course_list)) {
	foreach ($personal_course_list as $my_course) {
		if ($i<=10) {
			$list[] = SocialManager::get_logged_user_course_html($my_course,$i);
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

//show the action menu
SocialManager::show_social_menu();

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

			//SOCIALGOODFRIEND , SOCIALFRIEND, SOCIALPARENT

			$friends = SocialManager::get_friends($user_id, SOCIALFRIEND);
			
			$friend_html		= '';
			$number_of_images	= 3;
			$number_friends		= 0;
			$list_friends_id	= array();
			$number_friends  	= count($friends); 
			$number_of_images	= $number_friends;
			if ($number_friends != 0) {
				$number_loop	= ($number_friends/$number_of_images);
				$loop_friends	= ceil($number_loop);
				$j=0;
				$friend_html	.= '<div class="sectiontitle">'.get_lang('SocialFriend').'</div>';
				$friend_html	.= '<div id="friend-container" class="social-friend-container">';
					$friend_html.= '<div id="friend-header">';
							//$friend_html.=  $friends_count.' '.get_lang('Friends');
						if ($number_friends == 1)
							$friend_html.= '<div style="float:left;">'.$number_friends.' '.get_lang('Friend').'</div>';
						else
							$friend_html.= '<div style="float:left;">'.$number_friends.' '.get_lang('Friends').'</div>';
						if (api_get_user_id() == $user_id) {
							$friend_html.= '<div style="float:right;"><a href="friends.php">'.get_lang('SeeAll').'</a></div>';
						}
					$friend_html.= '</div>'; // close div friend-header

				for ($k=0;$k<$loop_friends;$k++) {
					if ($j==$number_of_images) {
						$number_of_images=$number_of_images*2;
					}
					
					while ($j<$number_of_images) {
						if (isset($friends[$j])) {
							$friend = $friends[$j];				
							$name_user	= api_get_person_name($friend['firstName'], $friend['lastName']);
							$friend_html.='<div id=div_'.$friend['friend_user_id'].' class="image_friend_network" ><span><center>';
							// the height = 92 must be the sqme in the image_friend_network span style in default.css
							$friends_profile = SocialManager::get_picture_user($friend['friend_user_id'], $friend['image'], 92, 'medium_', 'width="85" height="90" ');
							
							$friend_html.='<a href="profile.php?u='.$friend['friend_user_id'].'&amp;'.$link_shared.'">';
							$friend_html.='<img src="'.$friends_profile['file'].'" '.$friends_profile['style'].' id="imgfriend_'.$friend['friend_user_id'].'" title="'.$name_user.'" />';
							$friend_html.= '</center></span>';
							$friend_html.= '<center class="friend">'.$name_user.'</a></center>';
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
				$pending_invitations = SocialManager::get_list_invitation_of_friends_by_user_id(api_get_user_id());
				$list_get_path_web=SocialManager::get_list_web_path_user_invitation_by_user_id(api_get_user_id());
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
			
			//Load right plugin's
		    echo SocialManager::get_plugins(SOCIAL_RIGHT_PLUGIN);
		    	
		} else {
			echo '<div class="clear"></div><br />';
		}

	echo '</div>'; // end of content section


echo '<div id="social-profile-container">';

	//-------------------------------------
	// LEFT COLUMN
	
	echo '<div id="social-profile-left">';

			//--- User image
			echo '<div class="social-content-image">';
			echo '<div class="social-background-content" style="width:95%;" align="center">';
			echo '<br/>';
			
			if ($img_array['file'] != 'unknown.jpg') {
    	  		echo '<a class="thickbox" href="'.$big_image.'"><img src='.$img_array['dir'].$img_array['file'].' /> </a><br /><br />';
			} else {
				echo '<img src='.$img_array['dir'].$img_array['file'].' /><br /><br />';
			}
    	  	echo '</div>';
    	  	echo '</div>';
    	  	   	  	
	  		echo '<br/>';
	  		echo '<div class="actions" style="margin-right:5px;">';
	  		echo '&nbsp;<a href="/main/messages/send_message_to_userfriend.inc.php?height=300&width=610&user_friend='.$user_id.'&view=profile&view_panel=1" class="thickbox" title="'.get_lang('SendMessage').'">';
	  		echo Display::return_icon('message_new.png').'&nbsp;&nbsp;'.get_lang('SendMessage').'</a><br />';
	  		
	  		//check if I already sent an invitation message
	  		$invitation_sent_list = SocialManager::get_list_invitation_sent_by_user_id(api_get_user_id());
	  		
	  		if (is_array($invitation_sent_list) && is_array($invitation_sent_list[$user_id]) && count($invitation_sent_list[$user_id]) >0 ) {  	  		
	  			echo '<a href="'.api_get_path(WEB_PATH).'main/social/invitations.php">'.get_lang('YouAlreadySentAnInvitation').'</a>';
	  		} else {
	  			if (!$show_full_profile) {
	  				echo '&nbsp;<a href="/main/messages/send_message_to_userfriend.inc.php?view_panel=2&height=260&width=610&user_friend='.$user_id.'" class="thickbox" title="'.get_lang('SendInvitation').'">'.Display :: return_icon('add_multiple_users.gif', get_lang('SocialInvitationToFriends')).'&nbsp;'.get_lang('SendInvitation').'</a>';
	  			}
	  		}
			
    	  		echo '</div>';
     	  	
    	  	echo '<br />';

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
									if ($field_type == USER_FIELD_TYPE_DOUBLE_SELECT) {
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
									} elseif($field_type == USER_FIELD_TYPE_TAG ) {
										$user_tags = UserManager::get_user_tags($user_id, $field_id);
										$tag_tmp = array();
										foreach ($user_tags as $tags) {
											//$tag_tmp[] = $tags['tag'];
											$tag_tmp[] = '<a href="'.api_get_path(WEB_PATH).'main/social/search.php?q='.$tags['tag'].'">'.$tags['tag'].'</a>';
										}					 				 
										if (is_array($user_tags) && count($user_tags)>0) {							
											$extra_information_value .= '<strong>'.ucfirst($field_display_text).':</strong> '.implode(', ',$tag_tmp).'<br />';
										}
									} else {
										if (!empty($data)) {
											$extra_information_value .= '<strong>'.ucfirst($field_display_text).':</strong> '.$data.'<br />';
										}
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
				
					$results = GroupPortalManager::get_groups_by_user($user_id , 0, true);
					$groups = array();

					foreach ($results as $result) {
						$id = $result['id'];
						$url_open  = '<a href="groups.php?id='.$id.'">';
						$url_close = '</a>';
						if ($result['relation_type'] == GROUP_USER_PERMISSION_ADMIN) {			
							$result['name'].= Display::return_icon('admin_star.png', get_lang('Admin'));
						}
						$groups[]= array($url_open.$result['picture_uri'].$url_close, $url_open.$result['name'].$url_close);
					}
					if (count($groups)> 0) {
						echo '<h2>'.get_lang('MyGroups').'</h2>';
						Display::display_sortable_grid('groups', array(), $groups, array('hide_navigation'=>true, 'per_page' => 100), $query_vars, false, array(true, true, true,false));
					}
	

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
	    	  	
	    	  	
	    	  	//Load left plugin
		    	echo SocialManager::get_plugins(SOCIAL_LEFT_PLUGIN);
		    
		    
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
				}
				echo '</ul><br />';
				
				$user_feeds = SocialManager::get_user_feeds($user_id);
				if (is_array($user_feeds )) {
					echo '<div class="sectiontitle">'.get_lang('RSSFeeds').'</div>';
	    			echo '<div class="social-content-training">';
                	echo $user_feeds;
                	echo '</div>';
	    			echo '<div class="clear"></div><br />';
        			echo '</div>';
				}
		    }
		    
   		    //Load plugin center
		    echo SocialManager::get_plugins(SOCIAL_CENTER_PLUGIN);


			//--------------------------
		    
            echo '</div>';
        echo '</div>';
    echo '</div>';
echo '</div>'; //from the main

echo '<form id="id_reload" name="id_reload" action="profile.php">&nbsp;</form>';

Display :: display_footer();