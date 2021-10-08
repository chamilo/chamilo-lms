<?php

/* For licensing terms, see /license.txt */

/**
 * @author Julio Montoya <gugli100@gmail.com>
 */
$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';

api_block_anonymous_users();

if ('true' !== api_get_setting('allow_social_tool')) {
    api_not_allowed();
}

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
    if (isset($match[1])) {
        $anchor = 'topic_'.$match[1];
    }
}
$htmlHeadXtra[] = '<script>

var counter_image = 1;
function remove_image_form(id_elem1) {
	var elem1 = document.getElementById(id_elem1);
	elem1.parentNode.removeChild(elem1);
	counter_image--;
	var filepaths = document.getElementById("filepaths");
	if (filepaths.childNodes.length < 3) {
		var link_attach = document.getElementById("link-more-attach");
		if (link_attach) {
			link_attach.innerHTML=\'<a href="javascript://" class="btn btn-default" onclick="return add_image_form()">'.get_lang('Add one more file').'</a>\';
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
	document.getElementById("filepath_"+counter_image).innerHTML = "\n\
        <input type=\"file\" name=\"attach_"+counter_image+"\"  size=\"20\" />\n\
        <a href=\"javascript:remove_image_form("+id_elem1+")\">\n\
            <img src=\"'.Display::returnIconPath('delete.gif').'\">\n\
        </a>\n\
    ";

	if (filepaths.childNodes.length == 3) {
		var link_attach = document.getElementById("link-more-attach");
		if (link_attach) {
			link_attach.innerHTML="";
		}
	}
}

</script>';

$allowed_views = ['mygroups', 'newest', 'pop'];
$content = null;

if (isset($_GET['view']) && in_array($_GET['view'], $allowed_views)) {
    if ('mygroups' === $_GET['view']) {
        $interbreadcrumb[] = ['url' => 'groups.php', 'name' => get_lang('Groups')];
        $interbreadcrumb[] = ['url' => '#', 'name' => get_lang('My groups')];
    } elseif ('newest' === $_GET['view']) {
        $interbreadcrumb[] = ['url' => 'groups.php', 'name' => get_lang('Groups')];
        $interbreadcrumb[] = ['url' => '#', 'name' => get_lang('Newest')];
    } else {
        $interbreadcrumb[] = ['url' => 'groups.php', 'name' => get_lang('Groups')];
        $interbreadcrumb[] = ['url' => '#', 'name' => get_lang('Popular')];
    }
} else {
    $interbreadcrumb[] = ['url' => 'groups.php', 'name' => get_lang('Groups')];
    if (!isset($_GET['id'])) {
        $interbreadcrumb[] = ['url' => '#', 'name' => get_lang('Groups list')];
    } else {
        //$interbreadcrumb[]= array ('url' =>'#','name' => get_lang('Group'));
    }
}

// getting group information
$group_id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$relation_group_title = '';
$role = 0;

$usergroup = new UserGroupModel();
if (0 != $group_id) {
    $groupInfo = $usergroup->get($group_id);
    $groupInfo['name'] = Security::remove_XSS($groupInfo['name']);
    $groupInfo['description'] = Security::remove_XSS($groupInfo['description']);
    $interbreadcrumb[] = ['url' => '#', 'name' => $groupInfo['name']];

    if (isset($_GET['action']) && 'leave' === $_GET['action']) {
        $user_leaved = intval($_GET['u']);
        // I can "leave me myself"
        if (api_get_user_id() == $user_leaved) {
            if (UserGroupModel::canLeave($groupInfo)) {
                $usergroup->delete_user_rel_group($user_leaved, $group_id);
                Display::addFlash(
                    Display::return_message(get_lang('User is not subscribed to this group'), 'confirmation', false)
                );
            }
        }
    }

    // add a user to a group if its open
    if (isset($_GET['action']) && 'join' === $_GET['action']) {
        // we add a user only if is a open group
        $user_join = intval($_GET['u']);
        if (api_get_user_id() == $user_join && !empty($group_id)) {
            if (GROUP_PERMISSION_OPEN == $groupInfo['visibility']) {
                $usergroup->add_user_to_group($user_join, $group_id);
                Display::addFlash(
                    Display::return_message(get_lang('User is subscribed to this group'), 'confirmation', false)
                );
            } else {
                $usergroup->add_user_to_group(
                    $user_join,
                    $group_id,
                    GROUP_USER_PERMISSION_PENDING_INVITATION_SENT_BY_USER
                );
                Display::addFlash(
                    Display::return_message(get_lang('Invitation sent'), 'confirmation', false)
                );
            }
        }
    }
}
$create_thread_link = '';
$social_right_content = null;
$socialForum = '';

$groupInfo = $usergroup->get($group_id);
$groupInfo['name'] = Security::remove_XSS($groupInfo['name']);
$groupInfo['description'] = Security::remove_XSS($groupInfo['description']);

//Loading group information
if (isset($_GET['status']) && 'sent' === $_GET['status']) {
    $social_right_content .= Display::return_message(get_lang('Your message has been sent.'), 'confirmation', false);
}

$is_group_member = $usergroup->is_group_member($group_id);
$role = $usergroup->get_user_group_role(api_get_user_id(), $group_id);

if (!$is_group_member && GROUP_PERMISSION_CLOSED == $groupInfo['visibility']) {
    if (GROUP_USER_PERMISSION_PENDING_INVITATION_SENT_BY_USER == $role) {
        $social_right_content .= Display::return_message(get_lang('You already sent an invitation'));
    }
}
$listTopic = null;
$listMembers = null;

if ($is_group_member || GROUP_PERMISSION_OPEN == $groupInfo['visibility']) {
    if (!$is_group_member) {
        if (!in_array(
            $role,
            [GROUP_USER_PERMISSION_PENDING_INVITATION_SENT_BY_USER, GROUP_USER_PERMISSION_PENDING_INVITATION]
        )) {
            $social_right_content .= '<div class="group-tool">';
            $social_right_content .= '<div class="pull-right">';
            $social_right_content .= '<a
                class="btn btn-default btn-sm"
                href="group_view.php?id='.$group_id.'&action=join&u='.api_get_user_id().'">'.
                get_lang('Join group').'</a>';
            $social_right_content .= '</div>';
            $social_right_content .= '</div>';
        } elseif (GROUP_USER_PERMISSION_PENDING_INVITATION == $role) {
            $social_right_content .= '<div class="group-tool">';
            $social_right_content .= '<div class="pull-right">';
            $social_right_content .= '<a
                class="btn btn-default btn-sm"
                href="group_view.php?id='.$group_id.'&action=join&u='.api_get_user_id().'">'.
                    Display::getMdiIcon('email').' '.
                get_lang('You have been invited to join now').'</a>';
        }
        $social_right_content .= '</div>';
        $social_right_content .= '</div>';
    }
    $content = MessageManager::display_messages_for_group($group_id);
    if ($is_group_member) {
        if (empty($content)) {
            $createThreadUrl = api_get_path(WEB_CODE_PATH)
                .'social/message_for_group_form.inc.php?'
                .http_build_query([
                    'view_panel' => 1,
                    'user_friend' => api_get_user_id(),
                    'group_id' => $group_id,
                    'action' => 'add_message_group',
                ]);
            $create_thread_link = Display::url(
                Display::getMdiIcon('comment-text').' '.
                get_lang('You should create a topic'),
                $createThreadUrl,
                [
                    'class' => 'ajax btn btn-primary',
                    'title' => get_lang('Compose message'),
                    'data-title' => get_lang('Compose message'),
                    'data-size' => 'lg',
                ]
            );
        } else {
            $createThreadUrl = api_get_path(WEB_CODE_PATH)
                .'social/message_for_group_form.inc.php?'
                .http_build_query([
                    'view_panel' => 1,
                    'user_friend' => api_get_user_id(),
                    'group_id' => $group_id,
                    'action' => 'add_message_group',
                ]);
            $create_thread_link = Display::url(
                Display::getMdiIcon('comment-text').' '.
                get_lang('Create thread'),
                $createThreadUrl,
                [
                    'class' => 'ajax btn btn-default',
                    'title' => get_lang('Compose message'),
                    'data-title' => get_lang('Compose message'),
                    'data-size' => 'lg',
                ]
            );
        }
    }
    $members = $usergroup->get_users_by_group($group_id, true);
    $member_content = '';

    // My friends
    $friend_html = SocialManager::listMyFriendsBlock(
        api_get_user_id(),
        '',
        ''
    );

    // Members
    if (count($members) > 0) {
        if (GROUP_USER_PERMISSION_ADMIN == $role) {
            $member_content .= '<div class="group-tool">';
            $member_content .= '<div class="pull-right">';
            $member_content .= Display::url(
                Display::getMdiIcon('pencil').' '.get_lang('Edit members list'),
                'group_members.php?id='.$group_id,
                ['class' => 'btn btn-default btn-sm', 'title' => get_lang('Edit members list')]
            );
            $member_content .= '</div>';
            $member_content .= '</div>';
        }
        $member_content .= '<div class="user-list">';
        $member_content .= '<div class="row">';
        foreach ($members as $member) {
            // if is a member
            if (in_array(
                $member['relation_type'],
                [GROUP_USER_PERMISSION_ADMIN, GROUP_USER_PERMISSION_READER, GROUP_USER_PERMISSION_MODERATOR]
            )) {
                //add icons
                if (GROUP_USER_PERMISSION_ADMIN == $member['relation_type']) {
                    $icon = Display::return_icon('social_group_admin.png', get_lang('Admin'));
                } elseif (GROUP_USER_PERMISSION_MODERATOR == $member['relation_type']) {
                    $icon = Display::return_icon('social_group_moderator.png', get_lang('Moderator'));
                } else {
                    $icon = '';
                }

                $userPicture = UserManager::getUserPicture($member['id']);
                $member_content .= '<div class="col-md-3">';
                $member_content .= '<div class="items-user">';
                $member_name = Display::url(
                    api_get_person_name(
                        cut($member['user_info']['firstname'], 15),
                        cut($member['user_info']['lastname'], 15)
                    ).'&nbsp;'.$icon,
                    $member['user_info']['profile_url']
                );
                $member_content .= Display::div(
                    '<img class="img-circle" src="'.$userPicture.'"/>',
                    ['class' => 'avatar']
                );
                $member_content .= Display::div($member_name, ['class' => 'name']);
                $member_content .= '</div>';
                $member_content .= '</div>';
            }
        }
        $member_content .= '</div>';
        $member_content .= '</div>';
    }

    if (!empty($create_thread_link)) {
        $create_thread_link = Display::div($create_thread_link, ['class' => 'pull-right']);
    }
    $headers = [get_lang('Discussions'), get_lang('Members')];
    $socialForum = Display::tabs($headers, [$content, $member_content], 'tabs');

    $listTopic = $content;
    $listMembers = $member_content;
} else {
    // if I already sent an invitation message
    if (!in_array(
        $role,
        [
            GROUP_USER_PERMISSION_PENDING_INVITATION_SENT_BY_USER,
            GROUP_USER_PERMISSION_PENDING_INVITATION,
        ]
    )) {
        $social_right_content .= '<a
            class="btn" href="group_view.php?id='.$group_id.'&action=join&u='.api_get_user_id().'">'.
            get_lang('Join group').'</a>';
    } elseif (GROUP_USER_PERMISSION_PENDING_INVITATION == $role) {
        $social_right_content .= '<a
            class="btn" href="group_view.php?id='.$group_id.'&action=join&u='.api_get_user_id().'">'.
            get_lang('You have been invited to join now').'</a>';
    }
}

$tpl = new Template(null);

// Block Social Avatar
SocialManager::setSocialUserBlock($tpl, api_get_user_id(), 'groups', $group_id);

//$social_menu_block = SocialManager::show_social_menu('groups', $group_id);
$tpl->setHelp('Groups');
$tpl->assign('create_link', $create_thread_link);
$tpl->assign('is_group_member', $is_group_member);
$tpl->assign('group_info', $groupInfo);
$tpl->assign('social_friend_block', $friend_html);
//$tpl->assign('social_menu_block', $social_menu_block);
$tpl->assign('social_forum', $socialForum);
$tpl->assign('social_right_content', $social_right_content);
$tpl->assign('list_members', $listMembers);
$tpl->assign('list_topic', $listTopic);
$tpl->display($tpl->get_template('social/group_view.html.twig'));
