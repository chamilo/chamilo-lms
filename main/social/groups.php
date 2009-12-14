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

$htmlHeadXtra[] = '<script type="text/javascript" src="/main/inc/lib/javascript/jquery.js"></script>';
$htmlHeadXtra[] = '<script type="text/javascript" src="/main/inc/lib/javascript/thickbox.js"></script>';
//$htmlHeadXtra[] = '<script type="text/javascript" src="/main/inc/lib/javascript/ajaxfileupload.js"></script>';
$htmlHeadXtra[] = '<link rel="stylesheet" href="/main/inc/lib/javascript/thickbox.css" type="text/css" media="projection, screen">';

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
			
	</script>';

$interbreadcrumb[]= array ('url' =>'profile.php','name' => get_lang('Social'));
$interbreadcrumb[]= array ('url' =>'#','name' => get_lang('Groups'));
Display :: display_header($tool_name, 'Groups');

// save message group
if (isset($_POST['action']) && $_POST['action']=='send_message_group') {	
	$title = $_POST['title'];
	$content = $_POST['content'];
	$group_id = $_POST['group_id'];
	$parent_id = $_POST['parent_id'];	
	MessageManager::send_message(0, $title, $content, $_FILES, '', $group_id, $parent_id);
}

//show the action menu
SocialManager::show_social_menu();
echo '<div class="actions-title">';
echo get_lang('Groups');
echo '</div>';

// getting group information
$group_id	= intval($_GET['id']);
	
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
	echo GroupPortalManager::show_group_column_information($group_id, api_get_user_id());
	//---
		
	//-- Show message groups	
	echo '<div id="layout_right" style="margin-left: 282px;">';	
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
	
	$groups = array();
	if (is_array($results) && count($results) > 0) {
		foreach ($results as $result) {
			$id = $result['id'];
			$url_open  = '<a href="groups.php?id='.$id.'">';
			$url_close = '</a>';
			if ($result['relation_type'] == GROUP_USER_PERMISSION_ADMIN) {			
				$result['name'].= Display::return_icon('admin_star.png', get_lang('Admin'));
			}
			if ($result['relation_type'] == GROUP_USER_PERMISSION_MODERATOR) {			
				$result['name'].= Display::return_icon('moderator_star.png', get_lang('Moderator'));
			}			
			$groups[]= array($url_open.$result['picture_uri'].$url_close, $url_open.$result['name'].$url_close,cut($result['description'],140));
		}
	}
	echo '<h1>'.get_lang('MyGroups').'</h1>';
	// Everybody can create groups
	if (api_get_setting('allow_students_to_create_groups_in_social') == 'true') {
		echo '<a href="group_add.php">'.get_lang('CreateAgroup').'</a>';	
	} else {
		// Only admins and teachers can create groups		
		if (api_is_allowed_to_edit(null,true)) {
			echo '<a href="group_add.php">'.get_lang('CreateAgroup').'</a>';
		}
	}
	
	if (count($groups) > 0) {		
		Display::display_sortable_grid('groups', array(), $groups, array('hide_navigation'=>true, 'per_page' => 100), $query_vars, false, array(true, true, true,false));
	}
	
	
	// Newest groups --------
	
	$results = GroupPortalManager::get_groups_by_age();
	$groups = array();
	foreach ($results as $result) {
		$id = $result['id'];
		$url_open  = '<a href="groups.php?id='.$id.'">';
		$url_close = '</a>';		
		$groups[]= array($url_open.$result['picture_uri'].$url_close, $url_open.$result['name'].$url_close,cut($result['description'],140));
	}
	if (count($groups) > 0) {
		echo '<h1>'.get_lang('Newest').'</h1>';	
		Display::display_sortable_grid('groups', array(), $groups, array('hide_navigation'=>true, 'per_page' => 100), $query_vars, false, array(true, true, true,false));		
	}	
	
	// Pop groups -----
	
	$results = GroupPortalManager::get_groups_by_popularity();
	$groups = array();
	foreach ($results as $result) {
		$id = $result['id'];
		$url_open  = '<a href="groups.php?id='.$id.'">';
		$url_close = '</a>';		
		
		if ($result['count'] == 1 ) {
			$result['count'] = $result['count'].' '.get_lang('Member');	
		} else {
			$result['count'] = $result['count'].' '.get_lang('Members');
		}
		
		$groups[]= array($url_open.$result['picture_uri'].$url_close, $url_open.$result['name'].$url_close,$result['count'],cut($result['description'],140));
	}
	if (count($groups) > 0) {
		echo '<h1>'.get_lang('Popular').'</h1>';
		Display::display_sortable_grid('groups', array(), $groups, array('hide_navigation'=>true, 'per_page' => 100), $query_vars, false, array(true, true, true,true,true));
	}
	
	

}	
Display :: display_footer();
?>