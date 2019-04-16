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

$group_id = intval($_GET['id']);
$topic_id = isset($_GET['topic_id']) ? intval($_GET['topic_id']) : null;
$message_id = isset($_GET['msg_id']) ? intval($_GET['msg_id']) : null;

$usergroup = new UserGroup();
$is_member = false;

//todo @this validation could be in a function in group_portal_manager
if (empty($group_id)) {
    api_not_allowed(true);
} else {
    $group_info = $usergroup->get($group_id);

    if (empty($group_info)) {
        api_not_allowed(true);
    }
    $is_member = $usergroup->is_group_member($group_id);

    if ($group_info['visibility'] == GROUP_PERMISSION_CLOSED && !$is_member) {
        api_not_allowed(true);
    }
}

if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'delete') {
    $group_role = $usergroup->get_user_group_role(api_get_user_id(), $group_id);

    if (api_is_platform_admin() ||
        in_array($group_role, [GROUP_USER_PERMISSION_ADMIN, GROUP_USER_PERMISSION_MODERATOR])
    ) {
        $usergroup->delete_topic($group_id, $topic_id);
        Display::addFlash(Display::return_message(get_lang('Deleted')));
        header("Location: group_view.php?id=$group_id");
        exit;
    }
}

// My friends
$friend_html = SocialManager::listMyFriendsBlock(
    $user_id
);
$content = null;
$social_right_content = '';

if (isset($_POST['action'])) {
    $title = isset($_POST['title']) ? $_POST['title'] : null;
    $content = $_POST['content'];
    $group_id = intval($_POST['group_id']);
    $parent_id = intval($_POST['parent_id']);

    if ($_POST['action'] == 'reply_message_group') {
        $title = cut($content, 50);
    }

    if ($_POST['action'] == 'edit_message_group') {
        $edit_message_id = intval($_POST['message_id']);
        $res = MessageManager::send_message(
            0,
            $title,
            $content,
            $_FILES,
            [],
            $group_id,
            $parent_id,
            $edit_message_id,
            0,
            $topic_id
        );
    } else {
        if ($_POST['action'] == 'add_message_group' && !$is_member) {
            api_not_allowed(true);
        }
        $res = MessageManager::send_message(
            0,
            $title,
            $content,
            $_FILES,
            [],
            $group_id,
            $parent_id,
            0,
            $topic_id
        );
    }

    // display error messages
    if (!$res) {
        Display::addFlash(Display::return_message(get_lang('Error'), 'error'));
    }
    $topic_id = isset($_GET['topic_id']) ? intval($_GET['topic_id']) : null;
    if ($_POST['action'] == 'add_message_group') {
        $topic_id = $res;
    }
    $message_id = $res;
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
	document.getElementById("filepath_"+counter_image).innerHTML = "<input type=\"file\" name=\"attach_"+counter_image+"\"  size=\"20\" />&nbsp;<a href=\"javascript:remove_image_form("+id_elem1+")\"><img src=\"'.Display::returnIconPath('delete.png').'\"></a>";

	if (filepaths.childNodes.length == 3) {
		var link_attach = document.getElementById("link-more-attach");
		if (link_attach) {
			link_attach.innerHTML="";
		}
	}
}

$(function() {
	if ($("#msg_'.$message_id.'").length) {
		$("html,body").animate({
			scrollTop: $("#msg_'.$message_id.'").offset().top
		})
	}

	$(\'.group_message_popup\').on(\'click\', function() {
		var url     = this.href;
	    var dialog  = $("#dialog");
	    if ($("#dialog").length == 0) {
	    	dialog  = $(\'<div id="dialog" style="display:hidden"></div>\').appendTo(\'body\');
		}

	    // load remote content
	    dialog.load(
	    	url,
	        {},
	        	function(responseText, textStatus, XMLHttpRequest) {
                    dialog.dialog({
                        modal	: true,
                        width	: 520,
                        height	: 400,
                    });
				});
	            //prevent the browser to follow the link
	            return false;
	        });
        });
</script>';

$this_section = SECTION_SOCIAL;
$interbreadcrumb[] = ['url' => 'groups.php', 'name' => get_lang('Groups')];
$interbreadcrumb[] = ['url' => 'group_view.php?id='.$group_id, 'name' => Security::remove_XSS($group_info['name'])];
$interbreadcrumb[] = ['url' => '#', 'name' => get_lang('Discussions')];

$social_left_content = SocialManager::show_social_menu('member_list', $group_id);
$show_message = null;
if (!empty($show_message)) {
    $social_right_content .= Display::return_message($show_message, 'confirmation');
}
$group_message = MessageManager::display_message_for_group(
    $group_id,
    $topic_id,
    $is_member,
    $message_id
);

$social_menu_block = SocialManager::show_social_menu('member_list', $group_id);

$tpl = new Template(null);
$tpl->setHelp('Groups');
// Block Social Avatar
SocialManager::setSocialUserBlock($tpl, api_get_user_id(), 'groups', $group_id);
$tpl->assign('social_menu_block', $social_menu_block);
$tpl->assign('social_friend_block', $friend_html);
$tpl->assign('group_message', $group_message);
$tpl->assign('social_right_content', $social_right_content);
$social_layout = $tpl->get_template('social/groups_topics.tpl');
$tpl->display($social_layout);
