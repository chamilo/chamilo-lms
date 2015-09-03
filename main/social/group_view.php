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
	document.getElementById("filepath_"+counter_image).innerHTML = "<input type=\"file\" name=\"attach_"+counter_image+"\"  size=\"20\" />&nbsp;<a href=\"javascript:remove_image_form("+id_elem1+")\"><img src=\"'.api_get_path(WEB_IMG_PATH).'delete.gif\"></a>";

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

</script>';

$allowed_views = array('mygroups','newest','pop');
$content = null;

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

// getting group information
$group_id	= isset($_GET['id']) ? intval($_GET['id']) : null;
$relation_group_title = '';
$role = 0;

$usergroup = new UserGroup();

if ($group_id != 0) {
    $group_info = $usergroup->get($group_id);

    $interbreadcrumb[]= array ('url' =>'#','name' => $group_info['name']);

    if (isset($_GET['action']) && $_GET['action']=='leave') {
        $user_leaved = intval($_GET['u']);
        //I can "leave me myself"
        if (api_get_user_id() == $user_leaved) {
            $usergroup->delete_user_rel_group($user_leaved, $group_id);
            Display::addFlash(
                Display::return_message(get_lang('UserIsNotSubscribedToThisGroup'), 'confirmation', false)
            );
        }
    }
    // add a user to a group if its open
    if (isset($_GET['action']) && $_GET['action']=='join') {
        // we add a user only if is a open group
        $user_join = intval($_GET['u']);
        if (api_get_user_id() == $user_join && !empty($group_id)) {
            if ($group_info['visibility'] == GROUP_PERMISSION_OPEN) {
                $usergroup->add_user_to_group($user_join, $group_id);
                Display::addFlash(
                    Display::return_message(get_lang('UserIsSubscribedToThisGroup'), 'confirmation', false)
                );
            } else {
                $usergroup->add_user_to_group(
                    $user_join,
                    $group_id,
                    GROUP_USER_PERMISSION_PENDING_INVITATION_SENT_BY_USER
                );
                Display::addFlash(
                    Display::return_message(get_lang('InvitationSent'), 'confirmation', false)
                );
            }
        }
    }
}
$create_thread_link = '';
$social_right_content = null;

$group_info = $usergroup->get($group_id);

//Loading group information
if (isset($_GET['status']) && $_GET['status']=='sent') {
    $social_right_content .= Display::return_message(get_lang('MessageHasBeenSent'), 'confirmation', false);
}

$is_group_member = $usergroup->is_group_member($group_id);
$role = $usergroup->get_user_group_role(api_get_user_id(), $group_id);

if (!$is_group_member && $group_info['visibility'] == GROUP_PERMISSION_CLOSED) {
    if ($role == GROUP_USER_PERMISSION_PENDING_INVITATION_SENT_BY_USER) {
        $social_right_content .=  Display::return_message(get_lang('YouAlreadySentAnInvitation'));
    }
}

if ($is_group_member || $group_info['visibility'] == GROUP_PERMISSION_OPEN) {
    if (!$is_group_member) {
        if (!in_array($role,
            array(GROUP_USER_PERMISSION_PENDING_INVITATION_SENT_BY_USER, GROUP_USER_PERMISSION_PENDING_INVITATION))
        ) {
            $social_right_content .=  '<a class="btn" href="group_view.php?id='.$group_id.'&action=join&u='.api_get_user_id().'">'.
                get_lang('JoinGroup').'</a>';
        } elseif ($role == GROUP_USER_PERMISSION_PENDING_INVITATION) {
            $social_right_content .=  '<a class="btn" href="group_view.php?id='.$group_id.'&action=join&u='.api_get_user_id().'">'.
                get_lang('YouHaveBeenInvitedJoinNow').'</a>';
        }
        $social_right_content .=  '<br /><br />';
    }
    $content = MessageManager::display_messages_for_group($group_id);
    if ($is_group_member) {
        if (empty($content)) {
            $createThreadUrl = api_get_path(WEB_CODE_PATH)
                . 'social/message_for_group_form.inc.php?'
                . http_build_query([
                    'view_panel' => 1,
                    'user_friend' => api_get_user_id(),
                    'group_id' => $group_id,
                    'action' => 'add_message_group'
                ]);
            $create_thread_link = Display::url(
                get_lang('YouShouldCreateATopic'),
                $createThreadUrl,
                [
                    'class' => 'ajax btn btn-default',
                    'title' => get_lang('ComposeMessage'),
                    'data-title' => get_lang('ComposeMessage')
                ]
            );
        } else {
            $createThreadUrl = api_get_path(WEB_CODE_PATH)
                . 'social/message_for_group_form.inc.php?'
                . http_build_query([
                    'view_panel' => 1,
                    'user_friend' => api_get_user_id(),
                    'group_id' => $group_id,
                    'action' => 'add_message_group',
                ]);
            $create_thread_link = Display::url(
                get_lang('NewTopic'),
                $createThreadUrl,
                [
                    'class' => 'ajax btn btn-default',
                    'title' => get_lang('ComposeMessage'),
                    'data-title' => get_lang('ComposeMessage')
                ]
            );
        }
    }
    $members = $usergroup->get_users_by_group($group_id, true);
    $member_content = '';

    // Members

    if (count($members) > 0) {
        if ($role == GROUP_USER_PERMISSION_ADMIN) {
            $member_content .= Display::url(
                Display::return_icon('edit.gif', get_lang('EditMembersList')).' '.get_lang('EditMembersList'),
                'group_members.php?id='.$group_id
            );
        }
        foreach ($members as $member) {
            // if is a member
            if (in_array($member['relation_type'],
                array(GROUP_USER_PERMISSION_ADMIN, GROUP_USER_PERMISSION_READER,GROUP_USER_PERMISSION_MODERATOR))
            ) {
                //add icons
                if ($member['relation_type'] == GROUP_USER_PERMISSION_ADMIN) {
                    $icon= Display::return_icon('social_group_admin.png', get_lang('Admin'));
                } elseif ($member['relation_type'] == GROUP_USER_PERMISSION_MODERATOR) {
                    $icon= Display::return_icon('social_group_moderator.png', get_lang('Moderator'));
                } else {
                    $icon= '';
                }

                $userPicture = UserManager::getUserPicture($member['user_id']);

                $member_content .= '<div class="">';
                $member_name = Display::url(api_get_person_name(cut($member['firstname'],15),cut($member['lastname'],15)).'&nbsp;'.$icon, $member['user_info']['profile_url']);
                $member_content .= Display::div('<img class="social-groups-image" src="'.$userPicture.'"/>&nbsp'.$member_name);
                $member_content .= '</div>';
            }
        }
    }

    if (!empty($create_thread_link)) {
        $create_thread_link =  Display::div($create_thread_link, array('class'=>'pull-right'));
    }
    $headers = array(get_lang('Discussions'), get_lang('Members'));
    $social_right_content .= Display::tabs($headers, array($content, $member_content),'tabs');
} else {
    // if I already sent an invitation message
    if (!in_array(
        $role,
        array(
            GROUP_USER_PERMISSION_PENDING_INVITATION_SENT_BY_USER,
            GROUP_USER_PERMISSION_PENDING_INVITATION,
        )
    )
    ) {
        $social_right_content .=  '<a class="btn" href="group_view.php?id='.$group_id.'&action=join&u='.api_get_user_id().'">'.get_lang('JoinGroup').'</a>';
    } elseif ($role == GROUP_USER_PERMISSION_PENDING_INVITATION) {
        $social_right_content .=  '<a class="btn" href="group_view.php?id='.$group_id.'&action=join&u='.api_get_user_id().'">'.get_lang('YouHaveBeenInvitedJoinNow').'</a>';
    }
}

$tpl = new Template(null);

// Block Social Avatar
SocialManager::setSocialUserBlock($tpl, $user_id, 'groups', $group_id);
//Block Social Menu
$social_menu_block = SocialManager::show_social_menu('groups', $group_id);
$tpl->setHelp('Groups');
$tpl->assign('create_link', $create_thread_link);
$tpl->assign('is_group_member', $is_group_member);
$tpl->assign('group_info', $group_info);
$tpl->assign('social_menu_block', $social_menu_block);
$tpl->assign('social_right_content', $social_right_content);
$social_layout = $tpl->get_template('social/group_view.tpl');
$tpl->display($social_layout);
