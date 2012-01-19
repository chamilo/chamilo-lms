<?php
/* For licensing terms, see /license.txt */
/**
 * @package chamilo.social
 * @author Julio Montoya <gugli100@gmail.com>
 */
/**
 * Initialization
 */
$cidReset = true;
$language_file = array('userInfo');
require_once '../inc/global.inc.php';

api_block_anonymous_users();
if (api_get_setting('allow_social_tool') !='true') {
    api_not_allowed();
}

require_once api_get_path(LIBRARY_PATH).'group_portal_manager.lib.php';

$this_section = SECTION_SOCIAL;
$interbreadcrumb[]= array ('url' =>'home.php','name' => get_lang('Social'));
$interbreadcrumb[]= array ('url' =>'groups.php','name' => get_lang('Groups'));
$interbreadcrumb[]= array ('url' =>'#','name' => get_lang('WaitingList'));


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

// Group information
$admins		= GroupPortalManager::get_users_by_group($group_id, true, array(GROUP_USER_PERMISSION_ADMIN), 0, 1000);
$show_message = ''; 

if (isset($_GET['action']) && $_GET['action']=='accept') {
	// we add a user only if is a open group
	$user_join = intval($_GET['u']);
	//if i'm a moderator		
	if (GroupPortalManager::is_group_moderator($group_id)) {
		GroupPortalManager::update_user_role($user_join, $group_id);
		$show_message = get_lang('UserAdded');
	}	
}

if (isset($_GET['action']) && $_GET['action']=='deny') {	
	// we add a user only if is a open group
	$user_join = intval($_GET['u']);
	//if i'm a moderator		
	if (GroupPortalManager::is_group_moderator($group_id)) {
		GroupPortalManager::delete_user_rel_group($user_join, $group_id); 
		$show_message = get_lang('UserDeleted');
	}
}


if (isset($_GET['action']) && $_GET['action']=='set_moderator') {	
	// we add a user only if is a open group
	$user_moderator= intval($_GET['u']);
	//if i'm the admin		
	if (GroupPortalManager::is_group_admin($group_id)) {
		GroupPortalManager::update_user_role($user_moderator, $group_id, GROUP_USER_PERMISSION_MODERATOR); 
		$show_message = get_lang('UserChangeToModerator');
	}
}

$users	= GroupPortalManager::get_users_by_group($group_id, true, array(GROUP_USER_PERMISSION_PENDING_INVITATION_SENT_BY_USER), 0, 1000);
$new_member_list = array();

$social_left_content = SocialManager::show_social_menu('waiting_list',$group_id);	

if (!empty($show_message)){
    $social_right_content .= Display :: return_message($show_message);
}		
// Display form
foreach($users as $user) {	 
    switch ($user['relation_type']) {			
        case  GROUP_USER_PERMISSION_PENDING_INVITATION_SENT_BY_USER:
        $user['link']  = '<a href="group_waiting_list.php?id='.$group_id.'&u='.$user['user_id'].'&action=accept">'.Display::return_icon('invitation_friend.png', get_lang('AddNormalUser')).'</a>';
        $user['link'] .= '<a href="group_waiting_list.php?id='.$group_id.'&u='.$user['user_id'].'&action=set_moderator">'.Display::return_icon('social_moderator_add.png', get_lang('AddModerator')).'</a>';
        $user['link'] .= '<a href="group_waiting_list.php?id='.$group_id.'&u='.$user['user_id'].'&action=deny">'.Display::return_icon('user_delete.png', get_lang('DenyEntry')).'</a>';
        break;				
    }
    $new_member_list[] = $user;
}

if (count($new_member_list) > 0) {			
    $social_right_content .= Display::return_sortable_grid('search_users', array(), $new_member_list, array('hide_navigation'=>true, 'per_page' => 100), $query_vars, false, array(true, false, true,true,false,true,true));		
} else {
    $social_right_content .= Display :: return_message(get_lang('ThereAreNotUsersInTheWaitingList'));
}				

$tpl = new Template($tool_name);
$tpl->set_help('Groups');
$tpl->assign('social_left_content', $social_left_content);
$tpl->assign('social_left_menu', $social_left_menu);
$tpl->assign('social_right_content', $social_right_content);
$social_layout = $tpl->get_template('layout/social_layout.tpl');
$content = $tpl->fetch($social_layout);
$tpl->assign('actions', $actions);
$tpl->assign('message', $show_message);
$tpl->assign('content', $content);
$tpl->display_one_col_template();
