<?php
/* For licensing terms, see /chamilo_license.txt */
/**
 * @package dokeos.social
 * @author Julio Montoya <gugli100@gmail.com>
 */
 
$language_file = array('userInfo');
require '../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'group_portal_manager.lib.php';
require_once api_get_path(LIBRARY_PATH).'usermanager.lib.php';
require_once api_get_path(LIBRARY_PATH).'social.lib.php';

$this_section = SECTION_SOCIAL;

$interbreadcrumb[]= array ('url' =>'home.php','name' => get_lang('Social'));

api_block_anonymous_users();

$group_id	= intval($_GET['id']);

//todo @this validation could be in a function in group_portal_manager
if (empty($group_id)) {
	api_not_allowed();
} else {
	$group_info = GroupPortalManager::get_group_data($group_id);
	if (empty($group_info)) {
		api_not_allowed();
	}
	//only admin or moderator can do that
	$user_role = GroupPortalManager::get_user_group_role(api_get_user_id(), $group_id);
	if (!in_array($user_role, array(GROUP_USER_PERMISSION_ADMIN, GROUP_USER_PERMISSION_MODERATOR))) {
		api_not_allowed();		
	}
}


Display :: display_header($tool_name, 'Groups');
SocialManager::show_social_menu();
echo '<div class="actions-title">';
echo get_lang('GroupMembers');
echo '</div>'; 

// Group information
$admins		= GroupPortalManager::get_users_by_group($group_id, true,array(GROUP_USER_PERMISSION_ADMIN));
$show_message = ''; 

if (isset($_GET['action']) && $_GET['action']=='add') {
	// we add a user only if is a open group
	$user_join = intval($_GET['u']);
	//if i'm the admin	
	if (isset($admins[api_get_user_id()]) && $admins[api_get_user_id()]['relation_type'] == GROUP_USER_PERMISSION_ADMIN) {
		GroupPortalManager::update_user_role($user_join, $group_id);
		$show_message = get_lang('UserAdded');
	}	
}

if (isset($_GET['action']) && $_GET['action']=='delete') {	
	// we add a user only if is a open group
	$user_join = intval($_GET['u']);
	//if i'm the admin		
	if (isset($admins[api_get_user_id()]) && $admins[api_get_user_id()]['relation_type'] == GROUP_USER_PERMISSION_ADMIN) {
		GroupPortalManager::delete_user_rel_group($user_join, $group_id); 
		$show_message = get_lang('UserDeleted');
	}
}

if (isset($_GET['action']) && $_GET['action']=='set_moderator') {	
	// we add a user only if is a open group
	$user_moderator= intval($_GET['u']);
	//if i'm the admin		
	if (isset($admins[api_get_user_id()]) && $admins[api_get_user_id()]['relation_type'] == GROUP_USER_PERMISSION_ADMIN) {
		GroupPortalManager::update_user_role($user_moderator, $group_id, GROUP_USER_PERMISSION_MODERATOR); 
		$show_message = get_lang('UserChangeToModerator');
	}
}


if (! empty($show_message)){
	Display :: display_normal_message($show_message);
}

$users	= GroupPortalManager::get_users_by_group($group_id, true, array(GROUP_USER_PERMISSION_ADMIN, GROUP_USER_PERMISSION_READER, GROUP_USER_PERMISSION_MODERATOR));
$new_member_list = array();

foreach($users as $user) {	 
	switch ($user['relation_type']) {
		case  GROUP_USER_PERMISSION_ADMIN:
			$user['link'] = Display::return_icon('admin_star.png', get_lang('Admin'));
		break;
		case  GROUP_USER_PERMISSION_READER:
			$user['link'] = '<a href="group_members.php?id='.$group_id.'&u='.$user['user_id'].'&action=delete">'.Display::return_icon('del_user_big.gif', get_lang('DeleteFromGroup')).'</a><br />'.
							'<a href="group_members.php?id='.$group_id.'&u='.$user['user_id'].'&action=set_moderator">'.Display::return_icon('admins.gif', get_lang('AddModerator')).'</a>';
		break;		
		case  GROUP_USER_PERMISSION_PENDING_INVITATION:
			$user['link'] = '<a href="group_members.php?id='.$group_id.'&u='.$user['user_id'].'&action=add">'.Display::return_icon('pending_invitation.png', get_lang('PendingInvitation')).'</a>';
		break;
		case  GROUP_USER_PERMISSION_MODERATOR:
			$user['link'] = Display::return_icon('moderator_star.png', get_lang('Moderator'));
		break;				
	}
	$new_member_list[] = $user;
}

if (count($new_member_list) > 0) {
	Display::display_sortable_grid('search_users', array(), $new_member_list, array('hide_navigation'=>true, 'per_page' => 100), $query_vars, false, array(true, false, true,true,false,true,true));		
}
	
	
Display :: display_footer();
?>