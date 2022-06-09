<?php
/* For licensing terms, see /license.txt */

/**
 * @package chamilo.social
 *
 * @author Julio Montoya <gugli100@gmail.com>
 */
$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';

api_block_anonymous_users();
if (api_get_setting('allow_social_tool') != 'true') {
    api_not_allowed();
}

$this_section = SECTION_SOCIAL;
$group_id = intval($_GET['id']);
$usergroup = new UserGroup();

//todo @this validation could be in a function in group_portal_manager
if (empty($group_id)) {
    api_not_allowed();
} else {
    $group_info = $usergroup->get($group_id);
    if (empty($group_info)) {
        api_not_allowed();
    }
    //only admin or moderator can do that
    $user_role = $usergroup->get_user_group_role(api_get_user_id(), $group_id);
    if (!in_array($user_role, [GROUP_USER_PERMISSION_ADMIN, GROUP_USER_PERMISSION_MODERATOR])) {
        api_not_allowed();
    }
}

$interbreadcrumb[] = ['url' => 'groups.php', 'name' => get_lang('Groups')];
$interbreadcrumb[] = ['url' => 'group_view.php?id='.$group_id, 'name' => $group_info['name']];
$interbreadcrumb[] = ['url' => '#', 'name' => get_lang('WaitingList')];

// Group information
$admins = $usergroup->get_users_by_group(
    $group_id,
    true,
    [GROUP_USER_PERMISSION_ADMIN],
    0,
    1000
);
$show_message = '';

if (isset($_GET['action']) && $_GET['action'] == 'accept') {
    // we add a user only if is a open group
    $user_join = intval($_GET['u']);
    //if i'm a moderator
    if ($usergroup->isGroupModerator($group_id)) {
        $usergroup->update_user_role($user_join, $group_id);
        Display::addFlash(Display::return_message(get_lang('UserAdded')));
    }
}

if (isset($_GET['action']) && $_GET['action'] == 'deny') {
    // we add a user only if is a open group
    $user_join = intval($_GET['u']);
    //if i'm a moderator
    if ($usergroup->isGroupModerator($group_id)) {
        $usergroup->delete_user_rel_group($user_join, $group_id);
        Display::addFlash(Display::return_message(get_lang('UserDeleted')));
    }
}

if (isset($_GET['action']) && $_GET['action'] == 'set_moderator') {
    // we add a user only if is a open group
    $user_moderator = intval($_GET['u']);
    //if i'm the admin
    if ($usergroup->is_group_admin($group_id)) {
        $usergroup->update_user_role($user_moderator, $group_id, GROUP_USER_PERMISSION_MODERATOR);
        Display::addFlash(Display::return_message(get_lang('UserChangeToModerator')));
    }
}

$users = $usergroup->get_users_by_group(
    $group_id,
    true,
    [GROUP_USER_PERMISSION_PENDING_INVITATION_SENT_BY_USER],
    0,
    1000
);

$new_member_list = [];
$social_left_content = SocialManager::show_social_menu('waiting_list', $group_id);

// Display form
foreach ($users as $user) {
    $userId = $user['user_info']['user_id'];
    switch ($user['relation_type']) {
        case GROUP_USER_PERMISSION_PENDING_INVITATION_SENT_BY_USER:
            $user['link'] = '<a href="group_waiting_list.php?id='.$group_id.'&u='.$userId.'&action=accept">'.
                Display::return_icon('invitation_friend.png', get_lang('AddNormalUser')).'</a>';
            $user['link'] .= '<a href="group_waiting_list.php?id='.$group_id.'&u='.$userId.'&action=set_moderator">'.
                Display::return_icon('social_moderator_add.png', get_lang('AddModerator')).'</a>';
            $user['link'] .= '<a href="group_waiting_list.php?id='.$group_id.'&u='.$userId.'&action=deny">'.
                Display::return_icon('user_delete.png', get_lang('DenyEntry')).'</a>';
            break;
    }
    $new_member_list[] = $user;
}

$social_right_content = '';
if (empty($new_member_list) > 0) {
    $social_right_content = Display::return_message(get_lang('ThereAreNotUsersInTheWaitingList'));
}

$tpl = new Template(null);

SocialManager::setSocialUserBlock($tpl, api_get_user_id(), 'groups', $group_id);
$social_menu_block = SocialManager::show_social_menu('member_list', $group_id);
$tpl->assign('social_menu_block', $social_menu_block);
$tpl->setHelp('Groups');
$tpl->assign('members', $new_member_list);
$tpl->assign('social_right_content', $social_right_content);

$social_layout = $tpl->get_template('social/group_waiting_list.tpl');
$tpl->display($social_layout);
