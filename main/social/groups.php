<?php
/* For licensing terms, see /chamilo_license.txt */
/**
 * @package dokeos.social
 * @author Julio Montoya <gugli100@gmail.com>
 */
$cidReset=true;
$language_file = array('userInfo');
require_once '../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'usermanager.lib.php';
require_once api_get_path(LIBRARY_PATH).'group_portal_manager.lib.php';
require_once api_get_path(LIBRARY_PATH).'social.lib.php';
require_once api_get_path(LIBRARY_PATH).'message.lib.php';
require_once api_get_path(LIBRARY_PATH).'text.lib.php';

api_block_anonymous_users();

$this_section = SECTION_SOCIAL;

$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jquery.js" type="text/javascript" language="javascript"></script>'; //jQuery
$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/thickbox.js" type="text/javascript" language="javascript"></script>'; 
$htmlHeadXtra[] = '<link rel="stylesheet" href="'.api_get_path(WEB_LIBRARY_PATH).'javascript/thickbox.css" type="text/css" media="projection, screen">';

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
		
   var valor = "'.Security::remove_XSS($_GET['div_id']).'";

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

$interbreadcrumb[]= array ('url' =>'profile.php','name' => get_lang('Social'));
$interbreadcrumb[]= array ('url' =>'#','name' => get_lang('Groups'));
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
		Security::clear_token();
	}
		
}

// getting group information
$group_id	= intval($_GET['id']);

$who_is_on_line = get_lang('UsersOnline').' '.count(WhoIsOnline(api_get_setting('time_limit_whosonline')));

echo '<div class="actions-title-groups">';
//echo get_lang('Groups');
echo '<table width="100%"><tr><td width="150px" bgcolor="#32578b"><center><span class="menuTex1">'.strtoupper(get_lang('Menu')).'</span></center></td>
		<td width="15px">&nbsp;</td><td bgcolor="#32578b">'.Display::return_icon('whoisonline.png','',array('hspace'=>'6')).'<a href="#" ><span class="menuTex1">'.$who_is_on_line.'</span></a></td>
		</tr></table>';

/*
echo '<div class="menuTitle" align="center"><span class="menuTex1">'.get_lang('Menu').'</span></div>';
echo '<div class="TitleRigth">'.Display::return_icon('whoisonline.png','',array('hspace'=>'6')).'<a href="#" ><span class="menuTex1">'.$who_is_on_line.'</span></a></div>';
*/
echo '</div>';


echo '<div id="socialContent">';


	echo '<div id="socialContentLeft">';	
		//this include the social menu div
		if ($group_id != 0 ) {
			SocialManager::show_social_menu('group_messages',$group_id);	
		} else {
			SocialManager::show_social_menu('groups');
		}
		
	echo '</div>';

	echo '<div id="socialContentRigth">';

	
	
if ($group_id != 0 ) {
	//Loading group information
	if (isset($_GET['status']) && $_GET['status']=='sent') {
		Display::display_confirmation_message(get_lang('MessageHasBeenSent'), false);
	}	

	if (isset($_GET['action']) && $_GET['action']=='leave') {
		$user_leaved = intval($_GET['u']);
		//I can "leave me myself"
		if (api_get_user_id() == $user_leaved) {
			GroupPortalManager::delete_user_rel_group($user_leaved, $group_id);
		}	
	}
	
	// add a user to a group if its open	
	if (isset($_GET['action']) && $_GET['action']=='join') {
		// we add a user only if is a open group
		$user_join = intval($_GET['u']);	
		if (api_get_user_id() == $user_join && !empty($group_id)) {
			$group_info = GroupPortalManager::get_group_data($group_id);
			if ($group_info['visibility'] == GROUP_PERMISSION_OPEN) {
				GroupPortalManager::add_user_to_group($user_join, $group_id);				
			} else {
				GroupPortalManager::add_user_to_group($user_join, $group_id, GROUP_USER_PERMISSION_PENDING_INVITATION_SENT_BY_USER);
			}				
		}
	}
		
	//-- Shows left column
	//echo GroupPortalManager::show_group_column_information($group_id, api_get_user_id());
	//---
		
	//-- Show message groups	
	echo '<div id="layout_right" style="margin-left: 290px;">';	
		echo '<div class="messages">';
			if (GroupPortalManager::is_group_member($group_id)) {
				$content = MessageManager::display_messages_for_group($group_id);				
				if (!empty($content)) {
					echo $content;				
				} else {
					echo get_lang('YouShouldCreateATopic');	
				}
			} else {
				echo get_lang('YouShouldJoinTheGroup');
			}
		echo '</div>'; // end layout messages
	echo '</div>'; // end layout right
	
} else {		

		// My groups -----		
		$results = GroupPortalManager::get_groups_by_user(api_get_user_id(), 0, true);	
		$grid_my_groups = array();
		if (is_array($results) && count($results) > 0) {
			foreach ($results as $result) {
				$id = $result['id'];
				$url_open  = '<a href="groups.php?id='.$id.'">';
				$url_close = '</a>';
				
				$name = strtoupper(cut($result['name'],25,true));				
				if ($result['relation_type'] == GROUP_USER_PERMISSION_ADMIN) {		 	
					$name .= Display::return_icon('admin_star.png', get_lang('Admin'), array('style'=>'vertical-align:middle'));
				} elseif ($result['relation_type'] == GROUP_USER_PERMISSION_MODERATOR) {			
					$name .= Display::return_icon('moderator_star.png', get_lang('Moderator'), array('style'=>'vertical-align:middle'));
				}
				$count_users_group = count(GroupPortalManager::get_all_users_by_group($id));
				if ($count_users_group == 1 ) {
					$count_users_group = $count_users_group.' '.get_lang('Member');	
				} else {
					$count_users_group = $count_users_group.' '.get_lang('Members');
				}					
							
				$result['picture_uri'] = '<img class="imageGroups" src="'.$result['picture_uri'].'" hspace="4" height="50" border="2" align="left" width="50" />';			
				$grid_item_1 = Display::return_icon('boxmygroups.jpg');						
				$item_1 = '<div>'.$url_open.$result['picture_uri'].'<p class="groupTex1"><strong>'.$name.'<br />('.$count_users_group.')</strong></p>'.$url_close.Display::return_icon('linegroups.jpg').'</div>';
				$item_2 = '<div class="box_description_group_title" ><span class="groupText2">'.strtoupper(get_lang('DescriptionGroup')).'</span></div>';
				$item_3 = '<div class="box_description_group_content" >'.cut($result['description'],100,true).'</div>';	
				$item_4 = '<div class="box_description_group_actions" >'.$url_open.get_lang('SeeMore').$url_close.'</div>';			
				$grid_item_2 = $item_1.$item_2.$item_3.$item_4;				
				$grid_my_groups[]= array($grid_item_1,$grid_item_2);
			}
		}
				
		// Newest groups --------		
		$results = GroupPortalManager::get_groups_by_age();
		$grid_newest_groups = array();
		foreach ($results as $result) {
			$id = $result['id'];
			$url_open  = '<a href="groups.php?id='.$id.'">';
			$url_close = '</a>';			
			$count_users_group = count(GroupPortalManager::get_all_users_by_group($id));	
			if ($count_users_group == 1 ) {
					$count_users_group = $count_users_group.' '.get_lang('Member');	
			} else {
				$count_users_group = $count_users_group.' '.get_lang('Members');
			}	
			
			$name = strtoupper(cut($result['name'],30,true));
			$result['picture_uri'] = '<img class="imageGroups" src="'.$result['picture_uri'].'" hspace="4" height="50" border="2" align="left" width="50" />';			
			$grid_item_1 = Display::return_icon('boxmygroups.jpg');						
			$item_1 = '<div>'.$url_open.$result['picture_uri'].'<p class="groupTex1"><strong>'.$name.'<br />('.$count_users_group.')</strong></p>'.$url_close.Display::return_icon('linegroups.jpg').'</div>';
			$item_2 = '<div class="box_description_group_title" ><span class="groupText2">'.strtoupper(get_lang('DescriptionGroup')).'</span></div>';
			$item_3 = '<div class="box_description_group_content" >'.cut($result['description'],100,true).'</div>';	
			$item_4 = '<div class="box_description_group_actions" >'.$url_open.get_lang('SeeMore').$url_close.'</div>';			
			$grid_item_2 = $item_1.$item_2.$item_3.$item_4;
						
			$grid_newest_groups[]= array($grid_item_1,$grid_item_2);			
		}
		
		// Pop groups -----		
		$results = GroupPortalManager::get_groups_by_popularity();
		$grid_pop_groups = array();
		foreach ($results as $result) {
			$id = $result['id'];
			$url_open  = '<a href="groups.php?id='.$id.'">';
			$url_close = '</a>';											
			if ($result['count'] == 1 ) {
				$result['count'] = $result['count'].' '.get_lang('Member');	
			} else {
				$result['count'] = $result['count'].' '.get_lang('Members');
			}			
			$count_users_group = $result['count'];	
			
			$name = strtoupper(cut($result['name'],30,true));
			$result['picture_uri'] = '<img class="imageGroups" src="'.$result['picture_uri'].'" hspace="4" height="50" border="2" align="left" width="50" />';			
			$grid_item_1 = Display::return_icon('boxmygroups.jpg');						
			$item_1 = '<div>'.$url_open.$result['picture_uri'].'<p class="groupTex1"><strong>'.$name.'<br />('.$count_users_group.')</strong></p>'.$url_close.Display::return_icon('linegroups.jpg').'</div>';
			$item_2 = '<div class="box_description_group_title" ><span class="groupText2">'.strtoupper(get_lang('DescriptionGroup')).'</span></div>';
			$item_3 = '<div class="box_description_group_content" >'.cut($result['description'],100,true).'</div>';	
			$item_4 = '<div class="box_description_group_actions" >'.$url_open.get_lang('SeeMore').$url_close.'</div>';			
			$grid_item_2 = $item_1.$item_2.$item_3.$item_4;
						
			$grid_pop_groups[]= array($grid_item_1,$grid_item_2);
						
		}

		echo '<div id="boxmyGroups">';        	
			echo '<div class="groupText3">'.strtoupper(get_lang('MyGroups')).'</div>';                  
	        if (count($grid_my_groups) > 0) {
	        	Display::display_sortable_grid('mygroups', array(), $grid_my_groups, array('hide_navigation'=>true, 'per_page' => 2), $query_vars, false, array(true, true, true,false));
	        }	        	                 		
			if (count($grid_newest_groups) > 0) {
				echo '<div class="groupText3">'.strtoupper(get_lang('Newest')).'</div>';				
				Display::display_sortable_grid('newest', array(), $grid_newest_groups, array('hide_navigation'=>true, 'per_page' => 100), $query_vars, false, array(true, true, true,false));		
			}
			if (count($grid_pop_groups) > 0) {
				echo '<div class="groupText3">'.strtoupper(get_lang('Popular')).'</div>';
				Display::display_sortable_grid('popular', array(), $grid_pop_groups, array('hide_navigation'=>true, 'per_page' => 100), $query_vars, false, array(true, true, true,true,true));
			}					
		echo '</div>';	
	
}
	echo '</div>';
	
echo '</div>';	
Display :: display_footer();
?>

