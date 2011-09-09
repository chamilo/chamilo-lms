<?php
/* For licensing terms, see /license.txt */
/**
 * @package chamilo.social
 * @author Julio Montoya <gugli100@gmail.com>
 */

$cidReset = true;
$language_file = array('userInfo');
require_once '../inc/global.inc.php';

api_block_anonymous_users();
if (api_get_setting('allow_social_tool') !='true') {
    api_not_allowed();
}

require_once api_get_path(LIBRARY_PATH).'group_portal_manager.lib.php';
require_once api_get_path(LIBRARY_PATH).'mail.lib.inc.php';

$this_section = SECTION_SOCIAL;

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
$htmlHeadXtra[] = api_get_jquery_ui_js();
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
			link_attach.innerHTML=\'<a href="javascript://" onclick="return add_image_form()">'.get_lang('AddOneMoreFile').'</a>\';
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
 	/* Binds a tab id in the url */
    $("#tab_browse").bind("tabsselect", function(event, ui) {
		window.location.href=ui.tab;
    });
	$("#tabs").tabs();
	$("#tab_browse").tabs();

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
	if (!isset($_GET['id'])) {
        $interbreadcrumb[]= array ('url' =>'#','name' => get_lang('GroupList'));
	} else {
	    //$interbreadcrumb[]= array ('url' =>'#','name' => get_lang('Group'));
	}
}

Display :: display_header($tool_name, 'Groups');

// getting group information
$group_id	= isset($_GET['id']) ? intval($_GET['id']) : null;
$relation_group_title = '';
$my_group_role = 0;
if ($group_id != 0 ) {
	$user_leave_message = false;
	$user_added_group_message = false;
	$user_invitation_sent = false;
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
				$user_invitation_sent = true;
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
			$show_menu = 'browse_groups';
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
	
    if ($user_invitation_sent) {
        Display::display_confirmation_message(get_lang('InvitationSent'), false);
    }
    
    $is_group_member = GroupPortalManager::is_group_member($group_id);    

	// details about the current group
	echo '<div class="head_group">';    
		echo '<div id="social-group-details">';
				//Group's title
				echo '<h1><a href="groups.php?id='.$group_id.'">'.Security::remove_XSS($group_info['name'], STUDENT, true).'</a></h1>';
				
				//echo '<div class="social-group-details-info"><a target="_blank" href="'.$group_info['url'].'">'.$group_info['url'].'</a></div>';
				
				//Privacy
				if (!$is_group_member) {
    				echo '<div class="social-group-details-info">';
    					echo '<span>'.get_lang('Privacy').' : </span>';
    					if ($group_info['visibility']== GROUP_PERMISSION_OPEN) {
    						echo get_lang('ThisIsAnOpenGroup');
    					} elseif ($group_info['visibility']== GROUP_PERMISSION_CLOSED) {
    						echo get_lang('ThisIsACloseGroup');
    					}
    				echo '</div>';
				}
				
				if (!$is_group_member && $group_info['visibility'] == GROUP_PERMISSION_CLOSED) {
				    $role = GroupPortalManager::get_user_group_role(api_get_user_id(), $group_id);				    
				    if ($role == GROUP_USER_PERMISSION_PENDING_INVITATION_SENT_BY_USER) {
				        echo Display::display_normal_message(get_lang('YouAlreadySentAnInvitation'));
				    }
				}
				 
				if (!empty($relation_group_title)) {
				    /*
					echo '<div class="social-group-details-info">';
					echo '<span>'.get_lang('StatusInThisGroup').' : </span>';
					echo $relation_group_title;
					echo '</div>';*/
				}
				//Group's tags
				if (!empty($tags)) {
					echo '<div id="social-group-details-info"><span>'.get_lang('Tags').' : </span>'.$tags.'</div>';
				}
		echo '</div>';
	echo '</div>';
	echo '<div class="clear"></div>';

	//-- Show message groups
	echo '<div class="messages" style="width:700px">';	    
	     
		if ($is_group_member || $group_info['visibility'] == GROUP_PERMISSION_OPEN) {
		    if (!$is_group_member) {		        
    		    if (!in_array($my_group_role, array(GROUP_USER_PERMISSION_PENDING_INVITATION_SENT_BY_USER, GROUP_USER_PERMISSION_PENDING_INVITATION))) {
                    echo '<a href="groups.php?id='.$group_id.'&action=join&u='.api_get_user_id().'">'.Display::return_icon('group_join.png', get_lang('YouShouldJoinTheGroup'), array('hspace'=>'6')).'<span class="social-menu-text4" >'.get_lang('YouShouldJoinTheGroup').'</a></span>';
                } elseif ($my_group_role == GROUP_USER_PERMISSION_PENDING_INVITATION) {
                    echo '<a href="groups.php?id='.$group_id.'&action=join&u='.api_get_user_id().'">'.Display::return_icon('group_join.png', get_lang('YouHaveBeenInvitedJoinNow'), array('hspace'=>'6')).'<span class="social-menu-text4" >'.get_lang('YouHaveBeenInvitedJoinNow').'</span></a>';
                }
                echo '<br /><br />';
		    }            
			
			$content = MessageManager::display_messages_for_group($group_id);
			if ($is_group_member) {
    			if (empty($content)) {		
    				$content =  '<a href="'.api_get_path(WEB_CODE_PATH).'social/message_for_group_form.inc.php?view_panel=1&height=400&width=610&&user_friend='.api_get_user_id().'&group_id='.$group_id.'&action=add_message_group" class="thickbox" title="'.get_lang('ComposeMessage').'">'.Display::return_icon('compose_message.png', get_lang('NewTopic'), array('hspace'=>'6')).get_lang('YouShouldCreateATopic').'</a></li>';
    			} else {
    			    $create_thread_link = '<a href="'.api_get_path(WEB_CODE_PATH).'social/message_for_group_form.inc.php?view_panel=1&height=400&width=610&&user_friend='.api_get_user_id().'&group_id='.$group_id.'&action=add_message_group" class="thickbox" title="'.get_lang('ComposeMessage').'">'.Display::return_icon('compose_message.png', get_lang('NewTopic'), array('hspace'=>'6')).get_lang('NewTopic').'</a>';
    			    $content = $create_thread_link.$content; 			    
    			}			
			}
			$members		= GroupPortalManager::get_users_by_group($group_id);
            $member_content = '';
            
    		//Members
    		if (count($members) > 0) {
				if ($my_group_role == GROUP_USER_PERMISSION_ADMIN) {
				    $member_content .= Display::url(Display::return_icon('edit.gif', get_lang('EditMembersList')).' '.get_lang('EditMembersList'), 'group_members.php?id='.$group_id);
				}				
				foreach($members as $member) {	
					// if is a member
					if (in_array($member['relation_type'] , array(GROUP_USER_PERMISSION_ADMIN, GROUP_USER_PERMISSION_READER,GROUP_USER_PERMISSION_MODERATOR))) {
						//add icons
						if ($member['relation_type'] == GROUP_USER_PERMISSION_ADMIN) {
							$icon= Display::return_icon('social_group_admin.png', get_lang('Admin'));
						} elseif ($member['relation_type'] == GROUP_USER_PERMISSION_MODERATOR) {
							$icon= Display::return_icon('social_group_moderator.png', get_lang('Moderator'));
						} else{
							$icon= '';
						}
						$image_path = UserManager::get_user_picture_path_by_id($member['user_id'], 'web', false, true);
						$picture = UserManager::get_picture_user($member['user_id'], $image_path['file'], 60, USER_IMAGE_SIZE_MEDIUM);

						$member_content .= '<div class="">';
						$member_name = Display::url(api_get_person_name(cut($member['firstname'],15),cut($member['lastname'],15)).'&nbsp;'.$icon, 'profile.php?u='.$member['user_id']);
						$member_content .= Display::div('<img height="44" border="2" align="middle" vspace="10" class="social-groups-image" src="'.$picture['file'].'"/>&nbsp'.$member_name);						
						$member_content .= '</div>';
					
					}					 	
				}					
    		}		
    		$headers = array(get_lang('Messages'), get_lang('Members'));
			echo Display::tabs($headers, array($content, $member_content),'tabs');			
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
				$result['name'] = Security::remove_XSS($result['name'], STUDENT, true);
				$result['description'] = Security::remove_XSS($result['description'], STUDENT, true);
				$my_group_list[] = $id;
				$url_open  = '<a href="groups.php?id='.$id.'">';
				$url_close = '</a>';

				$name = cut($result['name'], GROUP_TITLE_LENGTH, true);
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
				$item_0  = Display::div($result['picture_uri'], array('class'=>'box_description_group_image'));
				$members = Display::span($count_users_group, array('class'=>'box_description_group_member'));
				$item_1  = Display::div(Display::tag('h2', $url_open.$name.$url_close).$members, array('class'=>'box_description_group_title'));
				
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
				$grid_item_2 = $item_0.$item_1.$item_2.$item_3;
				$grid_my_groups[]= array($grid_item_2);
			}
		}

		// Newest groups 
		$results = GroupPortalManager::get_groups_by_age(4,false);
		$grid_newest_groups = array();
		foreach ($results as $result) {
			$result['name'] = Security::remove_XSS($result['name'], STUDENT, true);
			$result['description'] = Security::remove_XSS($result['description'], STUDENT, true);
			$id = $result['id'];
			$url_open  = '<a href="groups.php?id='.$id.'">';
			$url_close = '</a>';
			$count_users_group = count(GroupPortalManager::get_users_by_group($id, false, array(GROUP_USER_PERMISSION_ADMIN, GROUP_USER_PERMISSION_READER, GROUP_USER_PERMISSION_MODERATOR), 0 , 1000));
			if ($count_users_group == 1 ) {
					$count_users_group = $count_users_group.' '.get_lang('Member');
			} else {
				$count_users_group = $count_users_group.' '.get_lang('Members');
			}

			$name = cut($result['name'],GROUP_TITLE_LENGTH,true);
			$picture = GroupPortalManager::get_picture_group($result['id'], $result['picture_uri'],80);
			$result['picture_uri'] = '<img class="social-groups-image" src="'.$picture['file'].'" hspace="4" height="50" border="2" align="left" width="50" />';
			
			$item_0 = Display::div($result['picture_uri'], array('class'=>'box_description_group_image'));
			$members = Display::span($count_users_group, array('class'=>'box_description_group_member'));
			$item_1  = Display::div(Display::tag('h2', $url_open.$name.$url_close).$members, array('class'=>'box_description_group_title'));
			

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

			$grid_newest_groups[]= array($grid_item_2);
		}

		// Pop groups
		$results = GroupPortalManager::get_groups_by_popularity(4,false);
		$grid_pop_groups = array();

		if (is_array($results) && count($results) > 0) {
			foreach ($results as $result) {
				$result['name'] = Security::remove_XSS($result['name'], STUDENT, true);
				$result['description'] = Security::remove_XSS($result['description'], STUDENT, true);
				$id = $result['id'];
				$url_open  = '<a href="groups.php?id='.$id.'">';
				$url_close = '</a>';

				$count_users_group = count(GroupPortalManager::get_users_by_group($id, false, array(GROUP_USER_PERMISSION_ADMIN, GROUP_USER_PERMISSION_READER, GROUP_USER_PERMISSION_MODERATOR), 0 , 1000));
				if ($count_users_group == 1 ) {
						$count_users_group = $count_users_group.' '.get_lang('Member');
				} else {
					$count_users_group = $count_users_group.' '.get_lang('Members');
				}

				$name = cut($result['name'],GROUP_TITLE_LENGTH,true);
				$picture = GroupPortalManager::get_picture_group($result['id'], $result['picture_uri'],80);
				$result['picture_uri'] = '<img class="social-groups-image" src="'.$picture['file'].'" hspace="4" height="50" border="2" align="left" width="50" />';
				
	            $item_0 = Display::div($result['picture_uri'], array('class'=>'box_description_group_image'));
			    $members = Display::span($count_users_group, array('class'=>'box_description_group_member'));
			    $item_1  = Display::div(Display::tag('h2', $url_open.$name.$url_close).$members, array('class'=>'box_description_group_title'));
			
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
        $query_vars = array();	
		
	   	if (isset($_GET['view']) && in_array($_GET['view'],$allowed_views)) {
	   		$view_group = $_GET['view'];

	   		switch ($view_group) {
	   			case 'mygroups' :
	        		if (count($grid_my_groups) > 0) {	        			
	        			$my_group_content = Display::return_sortable_grid('mygroups', array(), $grid_my_groups, array('hide_navigation'=>true, 'per_page' => 2), $query_vars, false, array(true, true, true,false));
	        		}
        	   		if (api_get_setting('allow_students_to_create_groups_in_social') == 'true') {
    	        	    $create_group_item =  '<a href="'.api_get_path(WEB_PATH).'main/social/group_add.php">'.Display::return_icon('group_add.png',get_lang('CreateASocialGroup'),array('hspace'=>'6','style'=>'float:left')).get_lang('CreateASocialGroup').'</a>';
    	        	    $my_group_content = $create_group_item. $my_group_content;
        	        } else {        	           
    	            	if (api_is_allowed_to_edit(null,true)) {
    	            	    $create_group_item =  '<a href="'.api_get_path(WEB_PATH).'main/social/group_add.php">'.Display::return_icon('group_add.png',get_lang('CreateASocialGroup'),array('hspace'=>'6','style'=>'float:left')).get_lang('CreateASocialGroup').'</a>';
    	        	        $my_group_content = $create_group_item. $my_group_content;
    	            	}
        	        }
	        		break;
	        	case 'newest' :
	        		if (count($grid_newest_groups) > 0) {
						$newest_content = Display::return_sortable_grid('newest', array(), $grid_newest_groups, array('hide_navigation'=>true, 'per_page' => 100), $query_vars, false, array(true, true, true,false));
					}
					if (api_is_platform_admin() || api_get_setting('allow_students_to_create_groups_in_social') == 'true') {
						if (empty($grid_newest_groups)) {
							//echo '<a href="group_add.php">'.get_lang('YouShouldCreateAGroup').'</a>';
						}
					}
	        		break;
	        	default :
	        		if (count($grid_pop_groups) > 0) {
						$popular_content = Display::return_sortable_grid('popular', array(), $grid_pop_groups, array('hide_navigation'=>true, 'per_page' => 100), $query_vars, false, array(true, true, true,true,true));
					}
					if (api_is_platform_admin() || api_get_setting('allow_students_to_create_groups_in_social') == 'true') {
						if (empty($grid_pop_groups)) {
							//echo '<a href="group_add.php">'.get_lang('YouShouldCreateAGroup').'</a>';
						}
					}
	        		break;
	   		}
	   	} else {	   	    
	   	    $my_group_content = null;
	        if (count($grid_my_groups) > 0) {
	        	$my_group_content = Display::return_sortable_grid('mygroups', array(), $grid_my_groups, array('hide_navigation'=>true, 'per_page' => 2), $query_vars, false, array(true, true, true,false));           
	        }
   	        if (api_get_setting('allow_students_to_create_groups_in_social') == 'true') {
                $create_group_item =  '<a href="'.api_get_path(WEB_PATH).'main/social/group_add.php">'.Display::return_icon('group_add.png',get_lang('CreateASocialGroup'),array('hspace'=>'6','style'=>'float:left')).get_lang('CreateASocialGroup').'</a>';
                $my_group_content = $create_group_item. $my_group_content;
            } else {
                if (api_is_allowed_to_edit(null,true)) {
                    $create_group_item =  '<a href="'.api_get_path(WEB_PATH).'main/social/group_add.php">'.Display::return_icon('group_add.png',get_lang('CreateASocialGroup'),array('hspace'=>'6','style'=>'float:left')).get_lang('CreateASocialGroup').'</a>';
                    $my_group_content  = $create_group_item. $my_group_content;
                }
            }
			if (count($grid_newest_groups) > 0) {
				$newest_content  = Display::return_sortable_grid('mygroups', array(), $grid_newest_groups, array('hide_navigation'=>true, 'per_page' => 100), $query_vars, false, array(true, true, true,false));
			}
			if (count($grid_pop_groups) > 0) {
				$popular_content = Display::return_sortable_grid('mygroups', array(), $grid_pop_groups, array('hide_navigation'=>true, 'per_page' => 100), $query_vars, false, array(true, true, true,true,true));
			}
	   	}	   	
	   	$headers = array(get_lang('MyGroups'), get_lang('Newest'), get_lang('Popular'));	   	
		echo Display::tabs($headers, array($my_group_content, $newest_content, $popular_content),'tab_browse');
    }
	echo '</div>';
echo '</div>';
Display :: display_footer();
