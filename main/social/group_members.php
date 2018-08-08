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
    api_not_allowed(true);
}

$this_section = SECTION_SOCIAL;
$group_id = intval($_GET['id']);
$userGroup = new UserGroup();
$user_role = '';

//todo @this validation could be in a function in group_portal_manager
if (empty($group_id)) {
    api_not_allowed(true);
} else {
    $group_info = $userGroup->get($group_id);
    if (empty($group_info)) {
        api_not_allowed(true);
    }
    $user_role = $userGroup->get_user_group_role(
        api_get_user_id(),
        $group_id
    );
    if (!in_array(
        $user_role,
        [
            GROUP_USER_PERMISSION_ADMIN,
            GROUP_USER_PERMISSION_MODERATOR,
            GROUP_USER_PERMISSION_READER,
        ]
    )
    ) {
        api_not_allowed(true);
    }
}

$interbreadcrumb[] = ['url' => 'home.php', 'name' => get_lang('Social')];
$interbreadcrumb[] = ['url' => 'groups.php', 'name' => get_lang('Groups')];
$interbreadcrumb[] = ['url' => 'group_view.php?id='.$group_id, 'name' => $group_info['name']];
$interbreadcrumb[] = ['url' => '#', 'name' => get_lang('MemberList')];

//if i'm a moderator
if (isset($_GET['action']) && $_GET['action'] == 'add') {
    // we add a user only if is a open group
    $user_join = intval($_GET['u']);
    //if i'm a moderator
    if ($userGroup->isGroupModerator($group_id)) {
        $userGroup->update_user_role($user_join, $group_id);
        Display::addFlash(Display::return_message(get_lang('UserAdded')));
    }
}

if (isset($_GET['action']) && $_GET['action'] == 'delete') {
    // we add a user only if is a open group
    $user_join = intval($_GET['u']);
    //if i'm a moderator
    if ($userGroup->isGroupModerator($group_id)) {
        $userGroup->delete_user_rel_group($user_join, $group_id);
        Display::addFlash(Display::return_message(get_lang('UserDeleted')));
    }
}

if (isset($_GET['action']) && $_GET['action'] == 'set_moderator') {
    // we add a user only if is a open group
    $user_moderator = intval($_GET['u']);
    //if i'm the admin
    if ($userGroup->is_group_admin($group_id)) {
        $userGroup->update_user_role(
            $user_moderator,
            $group_id,
            GROUP_USER_PERMISSION_MODERATOR
        );
        Display::addFlash(Display::return_message(get_lang('UserChangeToModerator')));
    }
}

if (isset($_GET['action']) && $_GET['action'] == 'delete_moderator') {
    // we add a user only if is a open group
    $user_moderator = intval($_GET['u']);
    //only group admins can do that
    if ($userGroup->is_group_admin($group_id)) {
        $userGroup->update_user_role(
            $user_moderator,
            $group_id,
            GROUP_USER_PERMISSION_READER
        );
        Display::addFlash(Display::return_message(get_lang('UserChangeToReader')));
    }
}

$users = $userGroup->get_users_by_group(
    $group_id,
    false,
    [
        GROUP_USER_PERMISSION_ADMIN,
        GROUP_USER_PERMISSION_READER,
        GROUP_USER_PERMISSION_MODERATOR,
    ],
    0,
    1000
);
$new_member_list = [];

$social_avatar_block = SocialManager::show_social_avatar_block(
    'member_list',
    $group_id
);
$social_menu_block = SocialManager::show_social_menu('member_list', $group_id);
$social_right_content = '<h2>'.$group_info['name'].'</h2>';

foreach ($users as $user) {
    switch ($user['relation_type']) {
        case GROUP_USER_PERMISSION_ADMIN:
            $user['link'] = Display::return_icon(
                'social_group_admin.png',
                get_lang('Admin')
            );
            break;
        case GROUP_USER_PERMISSION_READER:
            if (in_array(
                $user_role,
                [
                    GROUP_USER_PERMISSION_ADMIN,
                    GROUP_USER_PERMISSION_MODERATOR,
                ]
            )
            ) {
                $user['link'] = '<a href="group_members.php?id='.$group_id.'&u='.$user['id'].'&action=delete">'.
                    Display::return_icon(
                        'delete.png',
                        get_lang('DeleteFromGroup')
                    ).'</a>'.
                    '<a href="group_members.php?id='.$group_id.'&u='.$user['id'].'&action=set_moderator">'.
                    Display::return_icon(
                        'social_moderator_add.png',
                        get_lang('AddModerator')
                    ).'</a>';
            }
            break;
        case GROUP_USER_PERMISSION_PENDING_INVITATION:
            $user['link'] = '<a href="group_members.php?id='.$group_id.'&u='.$user['id'].'&action=add">'.
                Display::return_icon(
                    'pending_invitation.png',
                    get_lang('PendingInvitation')
                ).'</a>';
            break;
        case GROUP_USER_PERMISSION_MODERATOR:
            $user['link'] = Display::return_icon(
                'social_group_moderator.png',
                get_lang('Moderator')
            );
            //only group admin can manage moderators
            if ($user_role == GROUP_USER_PERMISSION_ADMIN) {
                $user['link'] .= '<a href="group_members.php?id='.$group_id.'&u='.$user['id'].'&action=delete_moderator">'.
                    Display::return_icon(
                        'social_moderator_delete.png',
                        get_lang('DeleteModerator')
                    ).'</a>';
            }
            break;
    }

    $userPicture = UserManager::getUserPicture($user['id']);
    $user['image'] = '<img src="'.$userPicture.'"  width="50px" height="50px" />';
    $new_member_list[] = $user;
}
if (count($new_member_list) > 0) {
    $social_right_content .= Display::return_sortable_grid(
        'list_members',
        [],
        $new_member_list,
        ['hide_navigation' => true, 'per_page' => 100],
        [],
        false,
        [true, false, true, false, false, true, true]
    );
}

$tpl = new Template(null);
$tpl->setHelp('Groups');
$tpl->assign('social_avatar_block', $social_avatar_block);
$tpl->assign('social_menu_block', $social_menu_block);
$tpl->assign('social_right_content', $social_right_content);

$social_layout = $tpl->get_template('social/home.tpl');
$tpl->display($social_layout);
