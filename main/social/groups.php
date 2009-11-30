<?php
/* For licensing terms, see /chamilo_license.txt */
/**
 * @package dokeos.social
 * @author Julio Montoya <gugli100@gmail.com>
 */
 
$language_file = array('userInfo');
require_once '../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'usermanager.lib.php';
require_once api_get_path(LIBRARY_PATH).'group_portal_manager.lib.php';
require_once api_get_path(LIBRARY_PATH).'social.lib.php';
require_once api_get_path(LIBRARY_PATH).'message.lib.php';

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

$interbreadcrumb[]= array ('url' =>'home.php','name' => get_lang('Social'));
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
$group_info = GroupPortalManager::get_group_data($group_id); 

	
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
		if (api_get_user_id() == $user_join) {	
			if ($group_info['visibility'] == GROUP_PERMISSION_OPEN) {
				GroupPortalManager::add_user_to_group($user_join, $group_id);				
			} else {
				GroupPortalManager::add_user_to_group($user_join, $group_id, GROUP_USER_PERMISSION_PENDING_INVITATION);
			}
				
		}
	}
	
	$picture	= GroupPortalManager::get_picture_group($group_id, $group_info['picture_uri'],160,'medium_');
	$tags		= GroupPortalManager::get_group_tags($group_id, true);
	$users		= GroupPortalManager::get_users_by_group($group_id, true);
	
	//my relation with the group is set here
	
	if (is_array($users[api_get_user_id()]) && count($users[api_get_user_id()]) > 0) {
		//im a member
		if ($users[api_get_user_id()]['relation_type'] != '' ) {			
			$my_group_role = $users[api_get_user_id()]['relation_type'];
		} else {
			$my_group_role = GROUP_USER_PERMISSION_ANONYMOUS;		
		}
	} else {
		//im not a member
		$my_group_role = GROUP_USER_PERMISSION_ANONYMOUS;		
	}


	//@todo this must be move to default.css for dev use only
	echo '<style> 		
			#group_members { width:233px; height:300px; overflow-x:none; overflow-y: auto;}
			.group_member_item { width:98px; height:86px; float:left; margin:5px 5px 15px 5px; }
			.group_member_picture { display:block;
height:92px;
margin:0;
overflow:hidden; }; 
	</style>';
	echo '<div id="layout-left" style="float: left; width: 280px; height: 100%;">';

	//Group's title
	echo '<h1>'.$group_info['name'].'</h1>';
	
	//image
	echo '<div id="group_image">';
		echo $img = '<img src="'.$picture['file'].'" />';
	echo '</div>';
	
	//description
	echo '<div id="group_description">';
		echo $group_info['description'];
	echo '</div>';
	
	//Privacy
	echo '<div id="group_privacy">';
		echo get_lang('Privacy').' : ';
		if ($group_info['visibility']== GROUP_PERMISSION_OPEN) {
			echo get_lang('ThisIsAnOpenGroup');
		} elseif ($group_info['visibility']== GROUP_PERMISSION_CLOSED) {
			echo get_lang('ThisIsACloseGroup');
		}
	echo '</div>';
	
	//group tags
	if (!empty($tags)) {
		echo '<div id="group_tags">';
			echo get_lang('Tags').' : '.$tags;
		echo '</div>';
	}
		
	if (in_array($my_group_role, array(GROUP_USER_PERMISSION_ADMIN, GROUP_USER_PERMISSION_READER,GROUP_USER_PERMISSION_MODERATOR))) { 
		echo '<div id="actions" style="margin:10px">';
		echo '<a href="'.api_get_path(WEB_CODE_PATH).'social/message_for_group_form.inc.php?view_panel=1&height=365&width=610&&user_friend='.api_get_user_id().'&group_id='.$group_id.'" class="thickbox" title="'.get_lang('ComposeMessage').'">'.Display :: return_icon('message_new.png', get_lang('ComposeMessage')).'&nbsp;'.get_lang('ComposeMessage').'</a>';
		//echo '<a href="'.api_get_path(WEB_PATH).'main/messages/new_message.php?group_id='.$group_id.'">'.Display::return_icon('message_new.png',api_xml_http_response_encode(get_lang('ComposeMessage'))).api_xml_http_response_encode(get_lang('ComposeMessage')).'</a>';
		echo '</div>';
	}
	
	echo get_lang('Members').' : ';	
	echo '<div id="group_members">';		
	foreach($users as $user) {		
		if (in_array($user['relation_type'] , array(GROUP_USER_PERMISSION_ADMIN, GROUP_USER_PERMISSION_READER,GROUP_USER_PERMISSION_MODERATOR))) {		
			if ($user['relation_type'] == GROUP_USER_PERMISSION_ADMIN) {
				$user['lastname'].= Display::return_icon('admin_star.png', get_lang('Admin'));
			}
			if ($user['relation_type'] == GROUP_USER_PERMISSION_MODERATOR) {
				$user['lastname'].= Display::return_icon('moderator_star.png', get_lang('Moderator'));
			}
			
			echo '<div class="group_member_item"><a href="profile.php?u='.$user['user_id'].'">';
				echo '<div class="group_member_picture">'.$user['image'].'</div>';
				echo api_get_person_name($user['firstname'], $user['lastname']).'</a></div>';
		}
	}
	echo '</div>';
	
		
	echo '<div id="group_permissions">';	
	switch ($my_group_role) {
		case GROUP_USER_PERMISSION_READER:
			// I'm just a reader
			echo '<a href="groups.php?id='.$group_id.'&action=leave&u='.api_get_user_id().'">'.get_lang('LeaveGroup').'</a>';
			echo 'Invite others/';	
			break;
		case GROUP_USER_PERMISSION_ADMIN:
			echo 'Im the admin/';
			echo '<a href="group_edit.php?id='.$group_id.'">'.get_lang('EditGroup').'</a>';
			echo '<a href="group_members.php?id='.$group_id.'">'.get_lang('MemberList').'</a>';
			echo '<a href="group_invitation.php?id='.$group_id.'">'.get_lang('InviteFriends').'</a>';
			break;
		case GROUP_USER_PERMISSION_PENDING_INVITATION:
			echo get_lang('PendingApproval');
			break;
		case GROUP_USER_PERMISSION_PENDING_INVITATION_SENT_BY_USER:
			echo get_lang('PendingApproval');
			break;
		case GROUP_USER_PERMISSION_MODERATOR:
			echo '<a href="group_members.php?id='.$group_id.'">'.get_lang('MemberList').'</a>';
			echo '<a href="group_invitation.php?id='.$group_id.'">'.get_lang('InviteFriends').'</a>';
			break;
		case GROUP_USER_PERMISSION_ANONYMOUS:
			echo '<a href="groups.php?id='.$group_id.'&action=join&u='.api_get_user_id().'">'.get_lang('JoinGroup').'</a>';
	}
	echo '</div>'; // end layout permissions
	
	
	echo '</div>'; // end layout left	
	
	echo '<div id="layout_right" style="margin-left: 282px;">';	
		echo '<div class="messages">';	
			MessageManager::display_messages_for_group($group_id);
		echo '</div>'; // end layout messages
	echo '</div>'; // end layout right

	
} else {
	
	// Newest groups --------
	
	$results = GroupPortalManager::get_groups_by_age(10 , true);
	$groups = array();
	foreach ($results as $result) {
		$id = $result['id'];
		$url_open  = '<a href="groups.php?id='.$id.'">';
		$url_close = '</a>';		
		$groups[]= array($url_open.$result['picture_uri'].$url_close, $url_open.$result['name'].$url_close);
	}
	if (count($groups) > 0) {
		echo '<h1>'.get_lang('Newest').'</h1>';	
		Display::display_sortable_grid('search_users', array(), $groups, array('hide_navigation'=>true, 'per_page' => 100), $query_vars, false, array(true, true, true,false));		
	}	
	
	// Pop groups -----
	
	$results = GroupPortalManager::get_groups_by_popularity(10 , true);
	$groups = array();
	foreach ($results as $result) {
		$id = $result['id'];
		$url_open  = '<a href="groups.php?id='.$id.'">';
		$url_close = '</a>';		
		$groups[]= array($url_open.$result['picture_uri'].$url_close, $url_open.$result['name'].$url_close,$result['count']);
	}
	if (count($groups) > 0) {
		echo '<h1>'.get_lang('Popular').'</h1>';
		Display::display_sortable_grid('search_users', array(), $groups, array('hide_navigation'=>true, 'per_page' => 100), $query_vars, false, array(true, true, true,true));
	}
	
	
	// My groups -----
	
	$results = GroupPortalManager::get_groups_by_user(api_get_user_id(), 0, true);
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
	echo '<h1>'.get_lang('MyGroups').'</h1>';
	echo '<a href="group_add.php">'.get_lang('CreateAgroup').'</a>';
	if (count($groups) > 0) {		
		Display::display_sortable_grid('search_users', array(), $groups, array('hide_navigation'=>true, 'per_page' => 100), $query_vars, false, array(true, true, true,false));
	}
}	
Display :: display_footer();
?>