<?php
/* For licensing terms, see /license.txt */
/**
* This is the profile social main page
* @author Julio Montoya <gugli100@gmail.com>
* @author Isaac Flores Paz <florespaz_isaac@hotmail.com>
* @package chamilo.social
*/

$language_file = array('userInfo', 'index');
$cidReset = true;
require_once '../inc/global.inc.php';

if (api_get_setting('allow_social_tool') !='true') {
    $url = api_get_path(WEB_PATH).'whoisonline.php?id='.intval($_GET['u']);
    header('Location: '.$url);
    exit;
    //api_not_allowed();
}

$user_id = api_get_user_id();

$show_full_profile = true;
//social tab
$this_section = SECTION_SOCIAL;

//I'm your friend? I can see your profile?
if (isset($_GET['u'])) {
	$user_id 	= (int) Database::escape_string($_GET['u']);
	if (api_is_anonymous($user_id, true)) {
	    api_not_allowed(true);
	}
	// It's me!
	if (api_get_user_id() != $user_id) {
		$user_info	= UserManager::get_user_info_by_id($user_id);
		$show_full_profile = false;
		if (!$user_info) {
			// user does no exist !!
			api_not_allowed(true);
		} else {
			//checking the relationship between me and my friend
			$my_status= SocialManager::get_relation_between_contacts(api_get_user_id(), $user_id);
			if (in_array($my_status, array(USER_RELATION_TYPE_PARENT, USER_RELATION_TYPE_FRIEND, USER_RELATION_TYPE_GOODFRIEND))) {
				$show_full_profile = true;
			}
			//checking the relationship between my friend and me
			$my_friend_status = SocialManager::get_relation_between_contacts($user_id, api_get_user_id());
			if (in_array($my_friend_status, array(USER_RELATION_TYPE_PARENT, USER_RELATION_TYPE_FRIEND, USER_RELATION_TYPE_GOODFRIEND))) {
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

require_once $libpath.'magpierss/rss_fetch.inc';
$ajax_url = api_get_path(WEB_AJAX_PATH).'message.ajax.php';
api_block_anonymous_users();

$htmlHeadXtra[] = '<script type="text/javascript">
    
function checkLength( o, n, min, max ) {
    if ( o.val().length > max || o.val().length < min ) {
        o.addClass( "ui-state-error" );
        //updateTips( "Length of " + n + " must be between " + min + " and " + max + "." );
        return false;
    } else {
        return true;
    }
}

function send_message_to_user(user_id) {  
    var subject = $( "#subject_id" );
    var content = $( "#content_id" );
    
    $("#send_message_form").show();            
    $("#send_message_div").dialog({
        modal:true,
        height:350,
        buttons: {
            "'.  addslashes(get_lang('Sent')).'": function() {                
                var bValid = true;
                bValid = bValid && checkLength( subject, "subject", 1, 255 );
                bValid = bValid && checkLength( content, "content", 1, 255 );
                
                if ( bValid ) {
                    var url = "'.$ajax_url.'?a=send_message&user_id="+user_id;
                    var params = $("#send_message_form").serialize();						
                    $.ajax({
                        url: url+"&"+params,
                        success:function(data) {                        
                            $("#main_content").before(data);
                            $("#send_message_div").dialog({ buttons:{}});                        
                            //$("#send_message_reponse").html(data);
                            $("#send_message_form").hide();                        
                            $("#send_message_div").dialog("close");		

                            $("#subject_id").val("");
                            $("#content_id").val("");
                        }							
                    });
                }
            },
        },				
        close: function() {		            
        }    
    });
    $("#send_message_div").dialog("open");
    //prevent the browser to follow the link    
}

function send_invitation_to_user(user_id) {    
    var content = $( "#content_invitation_id" );
    $("#send_invitation_form").show();    
    $("#send_invitation_div").dialog({
        modal:true,
        buttons: {
            "'.  addslashes(get_lang('SendInvitation')).'": function() {
                var bValid = true;
                
                bValid = bValid && checkLength( content, "content", 1, 255 );
                if (bValid) {
                    var url = "'.$ajax_url.'?a=send_invitation&user_id="+user_id;
                    var params = $("#send_invitation_form").serialize();						
                    $.ajax({
                        url: url+"&"+params,
                        success:function(data) {                        
                            $("#main_content").before(data);
                            $("#send_invitation_div").dialog({ buttons:{}});                        

                            $("#send_invitation_form").hide();                        
                            $("#send_invitation_div").dialog("close");		                                                
                            $("#content_invitation_id").val("");
                        }							
                    });
                }
            },
        },				
        close: function() {		            
        }    
    });		
    $("#send_invitation_div").dialog("open");        
    //prevent the browser to follow the link    
}

function toogle_course (element_html, course_code){
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
		$("div#"+content).show("fast");
	} else {
		$("div#"+content).hide("fast");
		$(id_button).attr("src","../img/nolines_plus.gif"); var action = "unload";
		return false;
	}

	 $.ajax({
		contentType: "application/x-www-form-urlencoded",
		beforeSend: function(objeto) {
		$("div#"+content).html("<img src=\'../inc/lib/javascript/indicator.gif\' />"); },
		type: "POST",
		url: "'.api_get_path(WEB_AJAX_PATH).'social.ajax.php?a=toogle_course",
		data: "load_ajax="+id_elem+"&action="+action+"&course_code="+course_code,
		success: function(datos) {
		 $("div#"+content).html(datos);
		}
	});
}

$(document).ready(function (){
	$("input#id_btn_send_invitation").bind("click", function(){
		if (confirm("'.get_lang('SendMessageInvitation', '').'")) {
			$("#form_register_friend").submit();
		}
	});
    
	$("#send_message_div").dialog({
		autoOpen: false,
		modal	: false, 
		width	: 550, 
		height	: 300    
   	});
    
	$("#send_invitation_div").dialog({
		autoOpen: false,
		modal	: false, 
		width	: 550, 
		height	: 300    
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
function action_database_panel (option_id, myuser_id) {

	if (option_id==5) {
		my_txt_subject=$("#txt_subject_id").val();
	} else {
		my_txt_subject="clear";
	}
	my_txt_content=$("#txt_area_invite").val();

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
				$("div#dpending_"+user_friend_id).html("<img src=\'../inc/lib/javascript/indicator.gif\' />"); 
			},
			type: "POST",
			url: "'.api_get_path(WEB_AJAX_PATH).'social.ajax.php?a=add_friend",
			data: "friend_id="+user_friend_id+"&is_my_friend="+"friend",
			success: function(datos) {
				$("#dpending_" + user_friend_id).html(datos);
			}
		});
	}
}

</script>';
$nametool = get_lang('ViewMySharedProfile');
if (isset($_GET['shared'])) {
	$my_link='../social/profile.php';
	$link_shared='shared='.Security::remove_XSS($_GET['shared']);
} else {
	$my_link='../social/profile.php';
	$link_shared='';
}
$interbreadcrumb[]= array ('url' =>'home.php','name' => get_lang('SocialNetwork') );

if (isset($_GET['u']) && is_numeric($_GET['u']) && $_GET['u'] != api_get_user_id()) {
	$info_user =   api_get_user_info($_GET['u']);
	$interbreadcrumb[]= array ('url' => '#','name' => api_get_person_name($info_user['firstName'], $info_user['lastName']));
	$nametool = '';
}
if (isset($_GET['u'])) {
	$param_user='u='.Security::remove_XSS($_GET['u']);
}else {
	$info_user = api_get_user_info(api_get_user_id());
	$param_user = '';
}
$_SESSION['social_user_id'] = intval($user_id);

/**
 * Display
 */

//Setting some course info
$my_user_id=isset($_GET['u']) ? Security::remove_XSS($_GET['u']) : api_get_user_id();
$personal_course_list = UserManager::get_personal_session_course_list($my_user_id);

$course_list_code = array();
$i=1;

if (is_array($personal_course_list)) {
	foreach ($personal_course_list as $my_course) {
		if ($i<=10) {
			$list[] = SocialManager::get_logged_user_course_html($my_course, $i);            
			$course_list_code[] = array('code'=>$my_course['code']);
		} else {
			break;
		}
		$i++;
	}
	//to avoid repeted courses
	$course_list_code = array_unique_dimensional($course_list_code);
}

$social_left_content = SocialManager::show_social_menu('shared_profile', null, $user_id, $show_full_profile);

$personal_info = null;
if (!empty($user_info['firstname']) || !empty($user_info['lastname'])) {
	$personal_info .= '<div><h3>'.api_get_person_name($user_info['firstname'], $user_info['lastname']).'</h3></div>';
} else {
	//--- Basic Information
	$personal_info .=  '<div><h3>'.get_lang('Profile').'</h3></div>';
}

if ($show_full_profile) {	
	$personal_info .=  '<dl class="dl-horizontal">';
	$personal_info .=  '<dt>'.get_lang('UserName').'</dt><dd>'. $user_info['username'].'	</dd>';
	if (!empty($user_info['firstname']) || !empty($user_info['lastname']))
		$personal_info .=  '<dt>'.get_lang('Name').'</dt><dd>'. api_get_person_name($user_info['firstname'], $user_info['lastname']).'</dd>';
	if (!empty($user_info['official_code']))
		$personal_info .=  '<dt>'.get_lang('OfficialCode').'</dt><dd>'.$user_info['official_code'].'</dd>';
	if (!empty($user_info['email']))
		if (api_get_setting('show_email_addresses')=='true')
			$personal_info .=  '<dt>'.get_lang('Email').'</dt><dd>'.$user_info['email'].'</dd>';
		if (!empty($user_info['phone']))
			$personal_info .=  '<dt>'.get_lang('Phone').'</dt><dd>'. $user_info['phone'].'</dd>';
		$personal_info .=  '</dl>';	
} else {	
	$personal_info .=  '<dl class="dl-horizontal">';
	if (!empty($user_info['username']))
		$personal_info .=  '<dt>'.get_lang('UserName').'</dt><dd>'. $user_info['username'].'</dd>';
	$personal_info .=  '</dl>';	    
}
$social_right_content =  SocialManager::social_wrapper_div($personal_info, 4);

if ($show_full_profile) {
	
	//SOCIALGOODFRIEND , USER_RELATION_TYPE_FRIEND, USER_RELATION_TYPE_PARENT
	$friends = SocialManager::get_friends($user_id, USER_RELATION_TYPE_FRIEND);

	$friend_html		= '';
	$number_of_images	= 6;
	$number_friends		= 0;	
	$number_friends  	= count($friends);

	if ($number_friends != 0) {
		$friend_html.= '<div><h3>'.get_lang('SocialFriend').'</h3></div>';
		$friend_html.= '<div id="friend-container" class="social-friend-container">';
		$friend_html.= '<div id="friend-header">';

		if ($number_friends == 1) {
			$friend_html.= '<div style="float:left;width:80%">'.$number_friends.' '.get_lang('Friend').'</div>';
		} else {
			$friend_html.= '<div style="float:left;width:80%">'.$number_friends.' '.get_lang('Friends').'</div>';
		}

		if ($number_friends > $number_of_images) {
			if (api_get_user_id() == $user_id) {
				$friend_html.= '<div style="float:right;width:20%"><a href="friends.php">'.get_lang('SeeAll').'</a></div>';
			} else {
				$friend_html.= '<div style="float:right;width:20%"><a href="'.api_get_path(WEB_CODE_PATH).'social/profile_friends_and_groups.inc.php?view=friends&height=390&width=610&&user_id='.$user_id.'" class="thickbox" title="'.get_lang('SeeAll').'" >'.get_lang('SeeAll').'</a></div>';
			}
		}
		$friend_html.= '</div>'; // close div friend-header
        
        $friend_html.='<ul class="thumbnails">';
        
		$j=1;
		for ($k=0;$k<$number_friends;$k++) {
			if ($j > $number_of_images) break;
			
			if (isset($friends[$k])) {
				$friend = $friends[$k];
				$name_user	= api_get_person_name($friend['firstName'], $friend['lastName']);
                $user_info_friend = api_get_user_info($friend['friend_user_id'], true);                
                         
                if ($user_info_friend['user_is_online']) {
                    $status_icon = Display::span('', array('class' => 'online_user_in_text'));
                } else {
                    $status_icon = Display::span('', array('class' => 'offline_user_in_text'));
                }
                
				//$friend_html.= '<div id=div_'.$friend['friend_user_id'].' class="image_friend_network" >';
                $friend_html.= '<li class="span2">';
                $friend_html.= '<div class="thumbnail">';
                
				// the height = 92 must be the sqme in the image_friend_network span style in default.css
				$friends_profile = SocialManager::get_picture_user($friend['friend_user_id'], $friend['image'], 92, USER_IMAGE_SIZE_ORIGINAL);
				
				$friend_html.= '<img src="'.$friends_profile['file'].'"  id="imgfriend_'.$friend['friend_user_id'].'" title="'.$name_user.'" />';                
                
                $friend_html.= '<div class="caption">';
				$friend_html.= $status_icon.'<a href="profile.php?u='.$friend['friend_user_id'].'&amp;'.$link_shared.'">';                
				$friend_html.= $name_user;
                $friend_html.= '</a></div>';
                $friend_html.= '</div>';
				$friend_html.= '</li>';
			}
			$j++;
		}
        $friend_html.='</ul>';
	} else {
		// No friends!! :(
		$friend_html .= '<div><h3>'.get_lang('SocialFriend').'</h3></div>';
		$friend_html.= '<div id="friend-container" class="social-friend-container">';
		$friend_html.= '<div id="friend-header">';
		$friend_html.= '<div>'.get_lang('NoFriendsInYourContactList').'<br /><a class="btn" href="'.api_get_path(WEB_PATH).'whoisonline.php">'.get_lang('TryAndFindSomeFriends').'</a></div>';
		$friend_html.= '</div>'; // close div friend-header
	}
	$friend_html.= '</div>';    
	$social_right_content .=  SocialManager::social_wrapper_div($friend_html, 5);	
}

// Extra information
if ($show_full_profile) {
	//-- Extra Data
	$t_uf	= Database :: get_main_table(TABLE_MAIN_USER_FIELD);
	$t_ufo	= Database :: get_main_table(TABLE_MAIN_USER_FIELD_OPTIONS);
	$extra_user_data = UserManager::get_extra_user_data($user_id);
	$extra_information = '';
	if (is_array($extra_user_data) && count($extra_user_data)>0 ) {
		
		$extra_information .= '<div><h3>'.get_lang('ExtraInformation').'</h3></div>';
		$extra_information .='<div class="social-profile-info">';
		$extra_information_value = '';
		foreach($extra_user_data as $key=>$data) {
		    //Avoding parameters
		    if (in_array($key, array('mail_notify_invitation','mail_notify_message', 'mail_notify_group_message' ))) continue;
			// get display text, visibility and type from user_field table
			$field_variable = str_replace('extra_','',$key);
			$sql = "SELECT field_display_text,field_visible,field_type,id FROM $t_uf WHERE field_variable ='$field_variable'";
			$res_field = Database::query($sql);
			$row_field = Database::fetch_row($res_field);
			$field_display_text = $row_field[0];
			$field_visible = $row_field[1];
			$field_type = $row_field[2];
			$field_id = $row_field[3];
			if ($field_visible == 1) {
				if (is_array($data)) {
					$extra_information_value .= '<dt>'.ucfirst($field_display_text).'</dt><dd> '.implode(',',$data).'</dd>';
				} else {
					if ($field_type == USER_FIELD_TYPE_DOUBLE_SELECT) {
						$id_options = explode(';',$data);
						$value_options = array();
						// get option display text from user_field_options table
						foreach ($id_options as $id_option) {
							$sql = "SELECT option_display_text FROM $t_ufo WHERE id = '$id_option'";
							$res_options = Database::query($sql);
							$row_options = Database::fetch_row($res_options);
							$value_options[] = $row_options[0];
						}
						$extra_information_value .= '<dt>'.ucfirst($field_display_text).':</dt><dd>'.implode(' ',$value_options).'</dd>';
					} elseif($field_type == USER_FIELD_TYPE_TAG ) {
						$user_tags = UserManager::get_user_tags($user_id, $field_id);
						$tag_tmp = array();
						foreach ($user_tags as $tags) {
							//$tag_tmp[] = $tags['tag'];
							$tag_tmp[] = '<a class="tag" href="'.api_get_path(WEB_PATH).'main/social/search.php?q='.$tags['tag'].'">'.$tags['tag'].'</a>';
						}
						if (is_array($user_tags) && count($user_tags)>0) {
							$extra_information_value .= '<dt>'.ucfirst($field_display_text).':</dt><dd>'.implode('', $tag_tmp).'</dd>';
						}
					} elseif ($field_type == USER_FIELD_TYPE_SOCIAL_PROFILE) {
						$icon_path = UserManager::get_favicon_from_url($data);
						$bottom = '0.3';
						//quick hack for hi5
						$domain = parse_url($icon_path, PHP_URL_HOST); if ($domain == 'www.hi5.com' or $domain == 'hi5.com') { $bottom = '0.8'; }
						$data = '<a href="'.$data.'"><img src="'.$icon_path.'" alt="ico" style="margin-right:0.5em;margin-bottom:-'.$bottom.'em;" />'.ucfirst($field_display_text).'</a>';
						$extra_information_value .= '<dd>'.$data.'</dd>'; 
					} else {
						if (!empty($data)) {
							$extra_information_value .= '<dt>'.ucfirst($field_display_text).':</dt><dd>'.$data.'</dd>';
						}
					}
				}
			}
		}
		// if there are information to show
		if (!empty($extra_information_value)) {
			$extra_information .= $extra_information_value;
		}
		$extra_information .= '</div>'; //social-profile-info
	}
	// 	if there are information to show
	if (!empty($extra_information_value))
        $social_right_content .=  SocialManager::social_wrapper_div($extra_information, 9);
}

if ($show_full_profile) {

	// MY GROUPS
    $results = GroupPortalManager::get_groups_by_user($my_user_id, 0);
	$grid_my_groups = array();
	$max_numbers_of_group = 4;
	if (is_array($results) && count($results) > 0) {
		$i = 1;
		foreach ($results as $result) {
			if ($i > $max_numbers_of_group) break;
			$id = $result['id'];
			$url_open  = '<a href="groups.php?id='.$id.'">';
			$url_close = '</a>';
			$icon = '';
			$name = cut($result['name'],CUT_GROUP_NAME,true);
			if ($result['relation_type'] == GROUP_USER_PERMISSION_ADMIN) {
				$icon = Display::return_icon('social_group_admin.png', get_lang('Admin'), array('style'=>'vertical-align:middle;width:16px;height:16px;'));
			} elseif ($result['relation_type'] == GROUP_USER_PERMISSION_MODERATOR) {
				$icon = Display::return_icon('social_group_moderator.png', get_lang('Moderator'), array('style'=>'vertical-align:middle;width:16px;height:16px;'));
			}
			$count_users_group = count(GroupPortalManager::get_all_users_by_group($id));
			if ($count_users_group == 1 ) {
				$count_users_group = $count_users_group.' '.get_lang('Member');
			} else {
				$count_users_group = $count_users_group.' '.get_lang('Members');
			}
			//$picture = GroupPortalManager::get_picture_group($result['id'], $result['picture_uri'],80);
			$item_name = $url_open.$name.$icon.$url_close;

			if ($result['description'] != '') {
				//$item_description = '<div class="box_shared_profile_group_description"><p class="social-groups-text4">'.cut($result['description'],100,true).'</p></div>';
			} else {
				//$item_description = '<div class="box_shared_profile_group_description"><span class="social-groups-text2"></span><p class="social-groups-text4"></p></div>';
			}
			//$result['picture_uri'] = '<div class="box_shared_profile_group_image"><img class="social-groups-image" src="'.$picture['file'].'" hspace="4" height="50" border="2" align="left" width="50" /></div>';
			$item_actions = '';
			if (api_get_user_id() == $user_id) {
				//$item_actions = '<div class="box_shared_profile_group_actions"><a href="groups.php?id='.$id.'">'.get_lang('SeeMore').$url_close.'</div>';
			}
			$grid_my_groups[]= array($item_name,$url_open.$result['picture_uri'].$url_close, $item_actions);
			$i++;
		}
	}

    if (count($grid_my_groups) > 0) {
		$my_groups = '';
        $count_groups = 0;
        if (count($results) == 1 ) {
            $count_groups = count($results);
        } else {
            $count_groups = count($results);
        }
        $my_groups .=  '<div><h3>'.get_lang('MyGroups').' ('.$count_groups.') </h3></div>';

        if ($i > $max_numbers_of_group) {
            if (api_get_user_id() == $user_id) {
                $my_groups .=  '<div class="box_shared_profile_group_actions"><a href="groups.php?#tab_browse-1">'.get_lang('SeeAllMyGroups').'</a></div>';
            } else {
                $my_groups .=  '<div class="box_shared_profile_group_actions"><a href="'.api_get_path(WEB_CODE_PATH).'social/profile_friends_and_groups.inc.php?view=mygroups&height=390&width=610&&user_id='.$user_id.'" class="thickbox" title="'.get_lang('SeeAll').'" >'.get_lang('SeeAllMyGroups').'</a></div>';
            }
        }
        //Display::display_sortable_grid('shared_profile_mygroups', array(), $grid_my_groups, array('hide_navigation'=>true, 'per_page' => 2), $query_vars, false, array(true, true, true,false));
        $total = count($grid_my_groups);
        $i = 1;
        foreach($grid_my_groups as $group) {
            $my_groups .=  $group[0];    			    
            if ($i < $total) {
                $my_groups .=  ', ';
            }
            $i++;
        }
        $social_right_content .=  SocialManager::social_wrapper_div($my_groups, 9);
	}

	// COURSES LIST
	if ( is_array($list) ) {		
        $my_courses .=  '<div><h3>'.api_ucfirst(get_lang('MyCourses')).'</h3></div>';
        $my_courses .=  '<div class="social-content-training">';
        
        //Courses without sessions        
        $i=1;
        foreach ($list as $key=>$value) {            
            if ( empty($value[2]) ) { //if out of any session
                $my_courses .=  $value[1];
                $my_courses .=  '<div id="social_content'.$i.'" class="course_social_content" style="display:none" >s</div>';					
                $i++;
            }
        }
        /*
        $listActives = $listInactives = array();
        foreach ( $list as $key=>$value ) {
            if ( $value['active'] ) { //if the session is still active (as told by get_logged_user_course_html())
                $listActives[] = $value;
            } elseif ( !empty($value[2]) ) { //if there is a session but it is not active
                $listInactives[] = $value;
            }
        }*/
        $my_courses .=  '</div>';		//social-content-training		
		$social_right_content .=  SocialManager::social_wrapper_div($my_courses, 9);
	}
    
	// user feeds
	$user_feeds = SocialManager::get_user_feeds($user_id);
	if (!empty($user_feeds)) {					
        $rss =  '<div><h3>'.get_lang('RSSFeeds').'</h3></div>';
        $rss .=  '<div class="social-content-training">'.$user_feeds.'</div>';	
		$social_right_content .=  SocialManager::social_wrapper_div($rss, 9);
	}

	//--Productions
	$production_list =  UserManager::build_production_list($user_id);

	// Images uploaded by course
	$file_list = '';
	if (is_array($course_list_code) && count($course_list_code)>0) {
		foreach ($course_list_code as $course) {
			$file_list.= UserManager::get_user_upload_files_by_course($user_id, $course['code'], $resourcetype='images');
		}
	}    

	$count_pending_invitations = 0;
	if (!isset($_GET['u']) || (isset($_GET['u']) && $_GET['u']==api_get_user_id())) {
		$pending_invitations = SocialManager::get_list_invitation_of_friends_by_user_id(api_get_user_id());
		$list_get_path_web	 = SocialManager::get_list_web_path_user_invitation_by_user_id(api_get_user_id());
		$count_pending_invitations = count($pending_invitations);
	}


	if (!empty($production_list) || !empty($file_list) || $count_pending_invitations > 0) {
		
		//Pending invitations
		if (!isset($_GET['u']) || (isset($_GET['u']) && $_GET['u']==api_get_user_id())) {
			if ($count_pending_invitations > 0) {								
				$invitations .=  '<div><h3>'.get_lang('PendingInvitations').'</h3></div>';
				for ($i=0;$i<$count_pending_invitations;$i++) {
					$user_invitation_id = $pending_invitations[$i]['user_sender_id'];
					$invitations .=  '<div id="dpending_'.$user_invitation_id.'" class="friend_invitations">';
    					$invitations .=  '<div style="float:left;width:60px;" >';
    					   $invitations .=  '<img style="margin-bottom:5px;" src="'.$list_get_path_web[$i]['dir'].'/'.$list_get_path_web[$i]['file'].'" width="60px">';
    					$invitations .=  '</div>';
    
    					$invitations .=  '<div style="padding-left:70px;">';
        					$user_invitation_info = api_get_user_info($user_invitation_id);
        					$invitations .=  '<a href="'.api_get_path(WEB_PATH).'main/social/profile.php?u='.$user_invitation_id.'">'.api_get_person_name($user_invitation_info['firstname'], $user_invitation_info['lastname']).'</a>';
        					$invitations .=  '<br />';
        					$invitations .=  Security::remove_XSS(cut($pending_invitations[$i]['content'], 50), STUDENT, true);
        					$invitations .=  '<br />';
        					$invitations .=  '<a id="btn_accepted_'.$user_invitation_id.'" class="btn" onclick="register_friend(this)" href="javascript:void(0)">'.get_lang('SocialAddToFriends').'</a>';
        					$invitations .=  '<div id="id_response"></div>';
					    $invitations .=  '</div>';
				    $invitations .=  '</div>';								
				}
				$social_right_content .=  SocialManager::social_wrapper_div($invitations, 4);
			}
		}				
		
		//--Productions
		$production_list =  UserManager::build_production_list($user_id);
		$product_content  = '';
		if (!empty($production_list)) {						
            $product_content .= '<div><h3>'.get_lang('MyProductions').'</h3></div>';
			$product_content .=  $production_list;
			$social_right_content .=  SocialManager::social_wrapper_div($product_content, 5);
		}
        
        $images_uploaded = null;
		// Images uploaded by course
		if (!empty($file_list)) {            
			$images_uploaded .=  '<div><h3>'.get_lang('ImagesUploaded').'</h3></div>';
			$images_uploaded .=  '<div class="social-content-information">';
			$images_uploaded .=  $file_list;
			$images_uploaded .=  '</div>';            
            $social_right_content .=  SocialManager::social_wrapper_div($images_uploaded, 9);
		}
	}	
	 
	if (!empty($user_info['competences']) || !empty($user_info['diplomas']) || !empty($user_info['openarea']) || !empty($user_info['teach']) ) {
		
		$more_info .=  '<div><h3>'.get_lang('MoreInformation').'</h3></div>';		
		$cut_size = 220;
		if (!empty($user_info['competences'])) {
			$more_info .=  '<br />';			
    			$more_info .=  '<div class="social-actions-message"><strong>'.get_lang('MyCompetences').'</strong></div>';
    			$more_info .=  '<div class="social-profile-extended">'.$user_info['competences'].'</div>';			
			$more_info .=  '<br />';
		}
		if (!empty($user_info['diplomas'])) {			
            $more_info .=  '<div class="social-actions-message"><strong>'.get_lang('MyDiplomas').'</strong></div>';
            $more_info .=  '<div class="social-profile-extended">'.$user_info['diplomas'].'</div>';			
			$more_info .=  '<br />';
		}
		if (!empty($user_info['openarea'])) {			
            $more_info .=  '<div class="social-actions-message"><strong>'.get_lang('MyPersonalOpenArea').'</strong></div>';
            $more_info .=  '<div class="social-profile-extended">'.$user_info['openarea'].'</div>';
			$more_info .=  '<br />';
		}
		if (!empty($user_info['teach'])) {			
    		$more_info .=  '<div class="social-actions-message"><strong>'.get_lang('MyTeach').'</strong></div>';
    		$more_info .=  '<div class="social-profile-extended">'.$user_info['teach'].'</div>';			
			$more_info .=  '<br />';
		}				
        $social_right_content .=  SocialManager::social_wrapper_div($more_info, 9);
	}	
}
$social_right_content .= MessageManager::generate_message_form('send_message');
$social_right_content .= MessageManager::generate_invitation_form('send_invitation');


$tpl = new Template(get_lang('Social'));
$tpl->assign('social_left_content', $social_left_content);
$tpl->assign('social_left_menu', $social_left_menu);
$tpl->assign('social_right_content', $social_right_content);
$social_layout = $tpl->get_template('layout/social_layout.tpl');
$content = $tpl->fetch($social_layout);
$tpl->assign('actions', $actions);
$tpl->assign('message', $message);
$tpl->assign('content', $content);
$tpl->display_one_col_template();