<?php
/* For licensing terms, see /license.txt */
/**
 * @package chamilo.social
 * @author Julio Montoya <gugli100@gmail.com>
 */
$cidReset=true;
$language_file = array('userInfo');
require_once '../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'usermanager.lib.php';
require_once api_get_path(LIBRARY_PATH).'group_portal_manager.lib.php';
require_once api_get_path(LIBRARY_PATH).'social.lib.php';
require_once api_get_path(LIBRARY_PATH).'message.lib.php';
require_once api_get_path(LIBRARY_PATH).'mail.lib.inc.php';

api_block_anonymous_users();

$this_section = SECTION_SOCIAL;

//jquery thickbox already called from main/inc/header.inc.php

// prepare anchor for message group topic
$anchor = '';
if (isset($_GET['anchor_topic'])) {
	$anchor = Security::remove_XSS($_GET['anchor_topic']);
} else {
	$match = 0;
	$param_names = array_keys($_GET);
	foreach ($param_names as $param) {
		if (preg_match('/^items_(\d)_page_nr$/', $param, $match)) {
			break;
		}
	}
	$anchor = 'topic_'.$match[1];
}

$htmlHeadXtra[] = '<script type="text/javascript">

var counter_image = 1;
function remove_image_form(id_elem1) {
	var elem1 = document.getElementById(id_elem1);
	elem1.parentNode.removeChild(elem1);
	counter_image--;
	var filepaths = document.getElementById("filepaths");
	if (filepaths.childNodes.length < 3) {
		var link_attach = document.getElementById("link-more-attach");
		if (link_attach) {
			link_attach.innerHTML=\'<a href="javascript://" onclick="return add_image_form()">'.get_lang('AddOneMoreFile').'</a>&nbsp;('.get_lang('MaximunFileSizeXMB').')\';
		}
	}
}

function add_image_form() {
	// Multiple filepaths for image form
	var filepaths = document.getElementById("filepaths");
	if (document.getElementById("filepath_"+counter_image)) {
		counter_image = counter_image + 1;
	}  else {
		counter_image = counter_image;
	}
	var elem1 = document.createElement("div");
	elem1.setAttribute("id","filepath_"+counter_image);
	filepaths.appendChild(elem1);
	id_elem1 = "filepath_"+counter_image;
	id_elem1 = "\'"+id_elem1+"\'";
	document.getElementById("filepath_"+counter_image).innerHTML = "<input type=\"file\" name=\"attach_"+counter_image+"\"  size=\"20\" />&nbsp;<a href=\"javascript:remove_image_form("+id_elem1+")\"><img src=\"'.api_get_path(WEB_CODE_PATH).'img/delete.gif\"></a>";

	if (filepaths.childNodes.length == 3) {
		var link_attach = document.getElementById("link-more-attach");
		if (link_attach) {
			link_attach.innerHTML="";
		}
	}
}

function validate_text_empty (str,msg) {
	var str = str.replace(/^\s*|\s*$/g,"");
	if (str.length == 0) {
		alert(msg);
		return true;
	}
}

jQuery(document).ready(function() {

   var valor = "'.$anchor.'";

   $(".head").click(function() {
				$(this).next().next().slideToggle("fast");
				image_clicked = $("#" + this.id + " img").attr("src");
				image_clicked_info = image_clicked.split("/");
				image_real_clicked = image_clicked_info[image_clicked_info.length-1];
				image_path = image_clicked.split("img");
				current_path = image_path[0]+"img/";
				if (image_real_clicked == "div_show.gif") {
					current_path = current_path+"div_hide.gif";
					$("#" + this.id + " img").attr("src", current_path);
				} else {
					current_path = current_path+"div_show.gif";
					$("#" + this.id + " img").attr("src", current_path)
				}
				return false;
		 	}).next().next().hide();

   // anchor for current topic
   if (valor) {
   		$("#"+valor).show();
   		window.location = document.URL+"#"+valor;
   }

});


function show_icon_edit(element_html) {
	ident="#edit_image";
	$(ident).show();
}

function hide_icon_edit(element_html)  {
	ident="#edit_image";
	$(ident).hide();
}

</script>';

$allowed_views = array('mygroups','newest','pop');
$interbreadcrumb[]= array ('url' =>'home.php','name' => get_lang('Social'));

if (isset($_GET['view']) && in_array($_GET['view'],$allowed_views)) {
	if ($_GET['view'] == 'mygroups') {
		$interbreadcrumb[]= array ('url' =>'groups.php','name' => get_lang('Groups'));
		$interbreadcrumb[]= array ('url' =>'#','name' => get_lang('MyGroups'));
	} else if ( $_GET['view'] == 'newest') {
		$interbreadcrumb[]= array ('url' =>'groups.php','name' => get_lang('Groups'));
		$interbreadcrumb[]= array ('url' =>'#','name' => get_lang('Newest'));
	} else  {
		$interbreadcrumb[]= array ('url' =>'groups.php','name' => get_lang('Groups'));
		$interbreadcrumb[]= array ('url' =>'#','name' => get_lang('Popular'));
	}
} else {
	$interbreadcrumb[]= array ('url' =>'groups.php','name' => get_lang('Groups'));
	$interbreadcrumb[]= array ('url' =>'#','name' => get_lang('GroupList'));
}

Display :: display_header($tool_name, 'Groups');

// save message group
if (isset($_POST['token']) && $_POST['token'] === $_SESSION['sec_token']) {

	if (isset($_POST['action'])) {
		$title = $_POST['title'];
		$content = $_POST['content'];
		$group_id = intval($_POST['group_id']);
		$parent_id = intval($_POST['parent_id']);

		if ($_POST['action'] == 'edit_message_group') {
			$edit_message_id = 	intval($_POST['message_id']);
			$res = MessageManager::send_message(0, $title, $content, $_FILES, '', $group_id, $parent_id, $edit_message_id);
		} else {
			$res = MessageManager::send_message(0, $title, $content, $_FILES, '', $group_id, $parent_id);
		}

		// display error messages
		if (is_string($res)) {
			Display::display_error_message($res);
		}

		if ($res === true) {
			$groups_user = 	GroupPortalManager::get_users_by_group($group_id);
			$group_info = GroupPortalManager::get_group_data($group_id);
			$admin_user_info = api_get_user_info(1);
			$sender_name = api_get_person_name($admin_user_info['firstName'], $admin_user_info['lastName'], null, PERSON_NAME_EMAIL_ADDRESS);
			$sender_email = $admin_user_info['mail'];
			$subject = sprintf(get_lang('ThereIsANewMessageInTheGroupX'),$group_info['name']);
			$link = api_get_path(WEB_PATH).'main/social/groups.php?'.$_SERVER['QUERY_STRING'];
			$text_link = '<a href="'.$link.'">'.get_lang('ClickHereToSeeMessageGroup')."</a><br />\r\n<br />\r\n".get_lang('OrCopyPasteTheFollowingUrl')." <br />\r\n ".$link;

			$message = sprintf(get_lang('YouHaveReceivedANewMessageInTheGroupX'),$group_info['name'])."<br />$text_link";

			foreach ($groups_user as $group_user) {
				if ($group_user == $current_user) continue;
				$group_user_info = api_get_user_info($group_user['user_id']);
				$recipient_name = api_get_person_name($group_user_info['firstName'], $group_user_info['lastName'], null, PERSON_NAME_EMAIL_ADDRESS);
				$recipient_email = $group_user_info['mail'];
				@api_mail_html($recipient_name, $recipient_email, stripslashes($subject), $message, $sender_name, $sender_email);
			}
		}

		Security::clear_token();
	}
}

// getting group information
$group_id	= intval($_GET['id']);
$relation_group_title = '';
$my_group_role = 0;
if ($group_id != 0 ) {
	$user_leave_message = false;
	$user_added_group_message = false;
	$group_info = GroupPortalManager::get_group_data($group_id);

	if (isset($_GET['action']) && $_GET['action']=='leave') {
		$user_leaved = intval($_GET['u']);
		//I can "leave me myself"
		if (api_get_user_id() == $user_leaved) {
			GroupPortalManager::delete_user_rel_group($user_leaved, $group_id);
			$user_leave_message = true;

		}
	}
	// add a user to a group if its open
	if (isset($_GET['action']) && $_GET['action']=='join') {
		// we add a user only if is a open group
		$user_join = intval($_GET['u']);
		if (api_get_user_id() == $user_join && !empty($group_id)) {
			if ($group_info['visibility'] == GROUP_PERMISSION_OPEN) {
				GroupPortalManager::add_user_to_group($user_join, $group_id);
				$user_added_group_message = true;
			} else {
				GroupPortalManager::add_user_to_group($user_join, $group_id, GROUP_USER_PERMISSION_PENDING_INVITATION_SENT_BY_USER);
				$user_added_group_message = true;
			}
		}
	}
}

echo '<div id="social-content">';

	echo '<div id="social-content-left">';
		//this include the social menu div
		if ($group_id != 0 ) {
			SocialManager::show_social_menu('groups',$group_id);
		} else {
			$show_menu = 'groups';
			if (isset($_GET['view']) && $_GET['view'] == 'mygroups') {
				$show_menu = $_GET['view'];
			}
			SocialManager::show_social_menu($show_menu);
		}
	echo '</div>';

	echo '<div id="social-content-right">';

if ($group_id != 0 ) {

	$group_info = GroupPortalManager::get_group_data($group_id);

	//Loading group information
	if (isset($_GET['status']) && $_GET['status']=='sent') {
		Display::display_confirmation_message(get_lang('MessageHasBeenSent'), false);
	}

	if ($user_leave_message) {
		Display::display_confirmation_message(get_lang('UserIsNotSubscribedToThisGroup'), false);
	}

	if ($user_added_group_message) {
		Display::display_confirmation_message(get_lang('UserIsSubscribedToThisGroup'), false);
	}

	// details about the current group
	echo '<div class="head_group">';
    
		echo '<div id="social-group-details">';
				//Group's title
				echo '<h2><a href="groups.php?id='.$group_id.'">'.$group_info['name'].'</a></h2>';

				//Group's description
				echo '<div class="social-group-details-info">'.$group_info['description'].'</div>';
				echo '<div class="social-group-details-info"><a target="_blank" href="'.$group_info['url'].'">'.$group_info['url'].'</a></div>';
				//Privacy
				echo '<div class="social-group-details-info">';
					echo '<span>'.get_lang('Privacy').' : </span>';
					if ($group_info['visibility']== GROUP_PERMISSION_OPEN) {
						echo get_lang('ThisIsAnOpenGroup');
					} elseif ($group_info['visibility']== GROUP_PERMISSION_CLOSED) {
						echo get_lang('ThisIsACloseGroup');
					}
				echo '</div>';

				if (!empty($relation_group_title)) {
					echo '<div class="social-group-details-info">';
					echo '<span>'.get_lang('StatusInThisGroup').' : </span>';
					echo $relation_group_title;
					echo '</div>';
				}

				//Group's tags
				if (!empty($tags)) {
					echo '<div id="social-group-details-info"><span>'.get_lang('Tags').' : </span>'.$tags.'</div>';
				}
		echo '</div>';
	echo '</div>';
	echo '<div class="clear"></div>';

	//-- Show message groups
	echo '<div class="messages">';
		if (GroupPortalManager::is_group_member($group_id)) {
			echo '<h3>'.get_lang('Topics').'</h3>';
			$content = MessageManager::display_messages_for_group($group_id);
			if (!empty($content)) {
				echo $content;
			} else {
				echo '<a href="'.api_get_path(WEB_CODE_PATH).'social/message_for_group_form.inc.php?view_panel=1&height=400&width=610&&user_friend='.api_get_user_id().'&group_id='.$group_id.'&action=add_message_group" class="thickbox" title="'.get_lang('ComposeMessage').'">'.Display::return_icon('compose_message.png', get_lang('NewTopic'), array('hspace'=>'6')).'<span class="social-menu-text4" >'.get_lang('YouShouldCreateATopic').'</span></a></li>';
			}
		} else {
			// if I already sent an invitation message
			if (!in_array($my_group_role, array(GROUP_USER_PERMISSION_PENDING_INVITATION_SENT_BY_USER, GROUP_USER_PERMISSION_PENDING_INVITATION))) {
				echo '<a href="groups.php?id='.$group_id.'&action=join&u='.api_get_user_id().'">'.Display::return_icon('group_join.png', get_lang('YouShouldJoinTheGroup'), array('hspace'=>'6')).'<span class="social-menu-text4" >'.get_lang('YouShouldJoinTheGroup').'</a></span>';
			} elseif ($my_group_role == GROUP_USER_PERMISSION_PENDING_INVITATION) {
				echo '<a href="groups.php?id='.$group_id.'&action=join&u='.api_get_user_id().'">'.Display::return_icon('group_join.png', get_lang('YouHaveBeenInvitedJoinNow'), array('hspace'=>'6')).'<span class="social-menu-text4" >'.get_lang('YouHaveBeenInvitedJoinNow').'</span></a>';
			}
		}
	echo '</div>'; // end layout messages

} else {
		// My groups -----
		$results = GroupPortalManager::get_groups_by_user(api_get_user_id(), 0);
		$grid_my_groups = array();
		$my_group_list = array();
		if (is_array($results) && count($results) > 0) {
			foreach ($results as $result) {
				$id = $result['id'];
				$my_group_list[] = $id;
				$url_open  = '<a href="groups.php?id='.$id.'">';
				$url_close = '</a>';

				$name = cut($result['name'],25,true);
				if ($result['relation_type'] == GROUP_USER_PERMISSION_ADMIN) {
					$name .= ' '.Display::return_icon('social_group_admin.png', get_lang('Admin'), array('style'=>'vertical-align:middle'));
				} elseif ($result['relation_type'] == GROUP_USER_PERMISSION_MODERATOR) {
					$name .= ' '.Display::return_icon('social_group_moderator.png', get_lang('Moderator'), array('style'=>'vertical-align:middle'));
				}
				$count_users_group = count(GroupPortalManager::get_users_by_group($id, false, array(GROUP_USER_PERMISSION_ADMIN, GROUP_USER_PERMISSION_READER, GROUP_USER_PERMISSION_MODERATOR), 0 , 1000));
				if ($count_users_group == 1 ) {
					$count_users_group = $count_users_group.' '.get_lang('Member');
				} else {
					$count_users_group = $count_users_group.' '.get_lang('Members');
				}

				$picture = GroupPortalManager::get_picture_group($result['id'], $result['picture_uri'],80);
				$result['picture_uri'] = '<img class="social-groups-image" src="'.$picture['file'].'" hspace="4" height="50" border="2" align="left" width="50" />';
				//$grid_item_1 = Display::return_icon('boxmygroups.jpg',get_lang('MyGroups'));
				$item_0 = Display::div($result['picture_uri'], array('class'=>'box_description_group_image'));
				$members = Display::span($count_users_group, array('class'=>'box_description_group_member'));
				$item_1  = Display::div(Display::tag('h3', $url_open.$name.$url_close).$members, array('class'=>'box_description_group_title'));
				
				$item_2 = '';
				$item_3 = '';
				if ($result['description'] != '') {					
					$item_3 = '<div class="box_description_group_content" >'.cut($result['description'],100,true).'</div>';
				} else {
					$item_2 = '<div class="box_description_group_title" ><span class="social-groups-text2"></span></div>';
					$item_3 = '<div class="box_description_group_content" ></div>';
				}
				
                /*$join_url = '<a href="groups.php?id='.$group_id.'&action=join&u='.api_get_user_id().'">'.Display::return_icon('group_join.png', get_lang('JoinGroup'), array('hspace'=>'6')).''.get_lang('JoinGroup').'</a> ';                
				$item_4 = '<div class="box_description_group_actions" >'.$join_url. $url_open.get_lang('SeeMore').$url_close.'</div>';*/
				
				$grid_item_2 = $item_0.$item_1.$item_2.$item_3.$item_4;
				$grid_my_groups[]= array($grid_item_1,$grid_item_2);
			}
		}

		// Newest groups 
		$results = GroupPortalManager::get_groups_by_age(4,false);
		$grid_newest_groups = array();
		foreach ($results as $result) {
		    
			$id = $result['id'];
			$url_open  = '<a href="groups.php?id='.$id.'">';
			$url_close = '</a>';
			$count_users_group = count(GroupPortalManager::get_users_by_group($id, false, array(GROUP_USER_PERMISSION_ADMIN, GROUP_USER_PERMISSION_READER, GROUP_USER_PERMISSION_MODERATOR), 0 , 1000));
			if ($count_users_group == 1 ) {
					$count_users_group = $count_users_group.' '.get_lang('Member');
			} else {
				$count_users_group = $count_users_group.' '.get_lang('Members');
			}

			$name = cut($result['name'],30,true);
			$picture = GroupPortalManager::get_picture_group($result['id'], $result['picture_uri'],80);
			$result['picture_uri'] = '<img class="social-groups-image" src="'.$picture['file'].'" hspace="4" height="50" border="2" align="left" width="50" />';
			
			$item_0 = Display::div($result['picture_uri'], array('class'=>'box_description_group_image'));
			$members = Display::span($count_users_group, array('class'=>'box_description_group_member'));
			$item_1  = Display::div(Display::tag('h3', $url_open.$name.$url_close).$members, array('class'=>'box_description_group_title'));
			

			if ($result['description'] != '') {
				$item_3 = '<div class="box_description_group_content" >'.cut($result['description'],100,true).'</div>';
			} else {
				$item_2 = '<div class="box_description_group_title" ><span class="social-groups-text2"></span></div>';
				$item_3 = '<div class="box_description_group_content" ></div>';
			}
            //Avoiding my groups
            $join_url = '';
		    if (!in_array($id,$my_group_list)) {
			    $join_url = '<a href="groups.php?id='.$id.'&action=join&u='.api_get_user_id().'">'.Display::return_icon('group_join.png', get_lang('JoinGroup'), array('hspace'=>'6')).''.get_lang('JoinGroup').'</a> ';
		    }
			
			$item_4 = '<div class="box_description_group_actions" >'.$join_url.'</div>';
			$grid_item_2 = $item_0.$item_1.$item_2.$item_3.$item_4;

			$grid_newest_groups[]= array($grid_item_1,$grid_item_2);
		}

		// Pop groups
		$results = GroupPortalManager::get_groups_by_popularity(4,false);
		$grid_pop_groups = array();

		if (is_array($results) && count($results) > 0) {
			foreach ($results as $result) {
				$id = $result['id'];
				$url_open  = '<a href="groups.php?id='.$id.'">';
				$url_close = '</a>';

				$count_users_group = count(GroupPortalManager::get_users_by_group($id, false, array(GROUP_USER_PERMISSION_ADMIN, GROUP_USER_PERMISSION_READER, GROUP_USER_PERMISSION_MODERATOR), 0 , 1000));
				if ($count_users_group == 1 ) {
						$count_users_group = $count_users_group.' '.get_lang('Member');
				} else {
					$count_users_group = $count_users_group.' '.get_lang('Members');
				}

				$name = cut($result['name'],30,true);
				$picture = GroupPortalManager::get_picture_group($result['id'], $result['picture_uri'],80);
				$result['picture_uri'] = '<img class="social-groups-image" src="'.$picture['file'].'" hspace="4" height="50" border="2" align="left" width="50" />';
				
	            $item_0 = Display::div($result['picture_uri'], array('class'=>'box_description_group_image'));
			    $members = Display::span($count_users_group, array('class'=>'box_description_group_member'));
			    $item_1  = Display::div(Display::tag('h3', $url_open.$name.$url_close).$members, array('class'=>'box_description_group_title'));
			
				if ($result['description'] != '') {					
					$item_3 = '<div class="box_description_group_content" >'.cut($result['description'],100,true).'</div>';
				} else {
					$item_2 = '<div class="box_description_group_title" ><span class="social-groups-text2"></span></div>';
					$item_3 = '<div class="box_description_group_content" ></div>';
				}

			    $join_url = '';
    		    if (!in_array($id,$my_group_list)) {
    			    $join_url = '<a href="groups.php?id='.$id.'&action=join&u='.api_get_user_id().'">'.Display::return_icon('group_join.png', get_lang('JoinGroup'), array('hspace'=>'6')).''.get_lang('JoinGroup').'</a> ';
    		    }
			    $item_4 = '<div class="box_description_group_actions" >'.$join_url.'</div>';
			    
				$grid_item_2 = $item_0.$item_1.$item_2.$item_3.$item_4;
				$grid_pop_groups[]= array($grid_item_2);
			}
		}

		// Display groups (newest, mygroups, pop)
		echo '<div class="social-box-container2">';
	   	if (isset($_GET['view']) && in_array($_GET['view'],$allowed_views)) {
	   		$view_group = $_GET['view'];

	   		switch ($view_group) {
	   			case 'mygroups' :
	        		if (count($grid_my_groups) > 0) {
	        			echo '<h2>'.get_lang('MyGroups').'</h2>';
	        			Display::display_sortable_grid('mygroups', array(), $grid_my_groups, array('hide_navigation'=>true, 'per_page' => 2), $query_vars, false, array(true, true, true,false));
	        		}
	       			if (api_is_platform_admin() || api_get_setting('allow_students_to_create_groups_in_social') == 'true') {
						if (empty($grid_my_groups)) {
							echo '<a href="group_add.php">'.get_lang('YouShouldCreateAGroup').'</a>';
						}
					}

	        		break;
	        	case 'newest' :
	        		if (count($grid_newest_groups) > 0) {
						echo '<h2>'.get_lang('Newest').'</h2>';
						Display::display_sortable_grid('newest', array(), $grid_newest_groups, array('hide_navigation'=>true, 'per_page' => 100), $query_vars, false, array(true, true, true,false));
					}

					if (api_is_platform_admin() || api_get_setting('allow_students_to_create_groups_in_social') == 'true') {
						if (empty($grid_newest_groups)) {
							echo '<a href="group_add.php">'.get_lang('YouShouldCreateAGroup').'</a>';
						}
					}
	        		break;
	        	default :
	        		if (count($grid_pop_groups) > 0) {
						echo '<h2>'.get_lang('Popular').'</h2>';
						Display::display_sortable_grid('popular', array(), $grid_pop_groups, array('hide_navigation'=>true, 'per_page' => 100), $query_vars, false, array(true, true, true,true,true));
					}

					if (api_is_platform_admin() || api_get_setting('allow_students_to_create_groups_in_social') == 'true') {
						if (empty($grid_pop_groups)) {
							echo '<a href="group_add.php">'.get_lang('YouShouldCreateAGroup').'</a>';
						}
					}
	        		break;
	   		}
	   	} else {
	        if (count($grid_my_groups) > 0) {
	        	echo '<h2>'.get_lang('MyGroups').'</h2>';
	        	Display::display_sortable_grid('mygroups', array(), $grid_my_groups, array('hide_navigation'=>true, 'per_page' => 2), $query_vars, false, array(true, true, true,false));
	        }
			if (count($grid_newest_groups) > 0) {					
				echo '<h2>'.get_lang('Newest').'</h2>';
				Display::display_sortable_grid('newest', array(), $grid_newest_groups, array('hide_navigation'=>true, 'per_page' => 100), $query_vars, false, array(true, true, true,false));
			}
			if (count($grid_pop_groups) > 0) {
				//echo '<div class="social-groups-text3">'.get_lang('Popular').'</div>';
				echo '<h2>'.get_lang('Popular').'</h2>';
				Display::display_sortable_grid('popular', array(), $grid_pop_groups, array('hide_navigation'=>true, 'per_page' => 100), $query_vars, false, array(true, true, true,true,true));
			}

			if (api_is_platform_admin() || api_get_setting('allow_students_to_create_groups_in_social') == 'true') {
				if (empty($grid_my_groups)  && empty($grid_newest_groups)  && empty($grid_pop_groups) ) {
					echo '<a href="group_add.php">'.get_lang('YouShouldCreateAGroup').'</a>';
				}
			}
	   	}
		echo '</div>';

}
	echo '</div>';
echo '</div>';
Display :: display_footer();