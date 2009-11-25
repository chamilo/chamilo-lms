<?php
/* For licensing terms, see /dokeos_license.txt */

$language_file = array('admin');
require '../inc/global.inc.php';

require_once api_get_path(LIBRARY_PATH).'group_portal_manager.lib.php';
require_once api_get_path(LIBRARY_PATH).'usermanager.lib.php';
require_once api_get_path(LIBRARY_PATH).'social.lib.php';
$this_section = SECTION_SOCIAL;

$interbreadcrumb[]= array ('url' =>'home.php','name' => get_lang('Social'));

api_block_anonymous_users();

Display :: display_header($tool_name, 'Groups');
SocialManager::show_social_menu();
echo '<div class="actions-title">';
echo get_lang('GroupMembers');
echo '</div>'; 

// Group information
$group_id	= intval($_GET['id']);
$admins		= GroupPortalManager::get_users_by_group($group_id, true,GROUP_USER_PERMISSION_ADMIN);
$show_message = ''; 

if (isset($_GET['action']) && $_GET['action']=='add') {
	// we add a user only if is a open group
	$user_join = intval($_GET['u']);
	//if i'm the admin	
	if (isset($admins[api_get_user_id()]) && $admins[api_get_user_id()]['relation_type'] == GROUP_USER_PERMISSION_ADMIN) {
		GroupPortalManager::update_user_permission($user_join, $group_id);
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

$users		= GroupPortalManager::get_users_by_group($group_id, true);

if (! empty($show_message)){
	Display :: display_normal_message($show_message);
}

$new_member_list = array();

foreach($users as $user) {	 
	switch ($user['relation_type']) {
		case  GROUP_USER_PERMISSION_ADMIN:
			$user['link'] = Display::return_icon('admin_star.png', get_lang('Admin'));
		break;
		case  GROUP_USER_PERMISSION_READER:
			$user['link'] = '<a href="group_members.php?id='.$group_id.'&u='.$user['user_id'].'&action=delete">'.Display::return_icon('delete.png', get_lang('Delete')).'</a>';
		break;		
		case  GROUP_USER_PERMISSION_PENDING_INVITATION:
			$user['link'] = '<a href="group_members.php?id='.$group_id.'&u='.$user['user_id'].'&action=add">'.Display::return_icon('pending_invitation.png', get_lang('PendingInvitation')).'</a>';
		break;				
	}
	$new_member_list[] = $user;
}

if (count($new_member_list) > 0) {
	Display::display_sortable_grid('search_users', array(), $new_member_list, array('hide_navigation'=>true, 'per_page' => 100), $query_vars, false, array(true, false, true,true,false,true,true));		
}
	
	
Display :: display_footer();
?>