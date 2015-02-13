<?php
/* For licensing terms, see /license.txt */
/**
 * @author Juan Carlos Trabado herodoto@telefonica.net
 * @package chamilo.social
 */

$language_file = array('messages', 'userInfo');
$cidReset = true;
require_once '../inc/global.inc.php';

api_block_anonymous_users();

if (api_get_setting('allow_social_tool') != 'true') {
    api_not_allowed();
}

$this_section = SECTION_SOCIAL;
$_SESSION['this_section'] = $this_section;

$interbreadcrumb[] = array(
    'url' => 'profile.php',
    'name' => get_lang('SocialNetwork')
);
$interbreadcrumb[] = array('url' => '#', 'name' => get_lang('MyFiles'));

$htmlHeadXtra[] = '
<script>

function denied_friend (element_input) {
	name_button=$(element_input).attr("id");
	name_div_id="id_"+name_button.substring(13);
	user_id=name_div_id.split("_");
	friend_user_id=user_id[1];
	 $.ajax({
		contentType: "application/x-www-form-urlencoded",
		beforeSend: function(objeto) {
		$("#id_response").html("<img src=\'../inc/lib/javascript/indicator.gif\' />"); },
		type: "POST",
		url: "' . api_get_path(WEB_AJAX_PATH) . 'social.ajax.php?a=deny_friend",
		data: "denied_friend_id="+friend_user_id,
		success: function(datos) {
		 $("div#"+name_div_id).hide("slow");
		 $("#id_response").html(datos);
		}
	});
}
function register_friend(element_input) {
    if(confirm("' . get_lang('AddToFriends') . '")) {
    	name_button=$(element_input).attr("id");
    	name_div_id="id_"+name_button.substring(13);
    	user_id=name_div_id.split("_");
    	user_friend_id=user_id[1];
    	 $.ajax({
    		contentType: "application/x-www-form-urlencoded",
    		beforeSend: function(objeto) {
    		$("div#dpending_"+user_friend_id).html("<img src=\'../inc/lib/javascript/indicator.gif\' />"); },
    		type: "POST",
    		url: "' . api_get_path(WEB_AJAX_PATH) . 'social.ajax.php?a=add_friend",
    		data: "friend_id="+user_friend_id+"&is_my_friend="+"friend",
    		success: function(datos) {  $("div#"+name_div_id).hide("slow");
    			$("form").submit()
    		}
    	});
    }
}

$(document).on("ready", function () {
    $("#el-finder").elfinder({
        url: "' . api_get_path(WEB_LIBRARY_PATH) . 'elfinder/php/connector.php",
        lang: "' . api_get_language_isocode() . '",
        height: 600,
        resizable: false,
        rememberLastDir: false,
    }).elfinder("instance");
});

</script>';

$show_message = null;

// easy links
if (is_array($_GET) && count($_GET) > 0) {
    foreach ($_GET as $key => $value) {
        switch ($key) {
            case 'accept':
                $user_role = GroupPortalManager::get_user_group_role(
                    api_get_user_id(),
                    $value
                );
                if (in_array(
                    $user_role,
                    array(
                        GROUP_USER_PERMISSION_PENDING_INVITATION_SENT_BY_USER,
                        GROUP_USER_PERMISSION_PENDING_INVITATION
                    )
                )
                ) {
                    GroupPortalManager::update_user_role(
                        api_get_user_id(),
                        $value,
                        GROUP_USER_PERMISSION_READER
                    );
                    $show_message = get_lang('UserIsSubscribedToThisGroup');
                } elseif (in_array(
                    $user_role,
                    array(
                        GROUP_USER_PERMISSION_READER,
                        GROUP_USER_PERMISSION_ADMIN,
                        GROUP_USER_PERMISSION_MODERATOR
                    )
                )
                ) {
                    $show_message = get_lang(
                        'UserIsAlreadySubscribedToThisGroup'
                    );
                } else {
                    $show_message = get_lang('UserIsNotSubscribedToThisGroup');
                }
                break 2;
            case 'deny':
                // delete invitation
                GroupPortalManager::delete_user_rel_group(
                    api_get_user_id(),
                    $value
                );
                $show_message = get_lang('GroupInvitationWasDeny');
                break 2;
        }
    }
}

$social_avatar_block = SocialManager::show_social_avatar_block('myfiles');
$social_menu_block = SocialManager::show_social_menu('myfiles');
$actions = null;

if (isset($_GET['cidReq'])) {
    $actions = '<a href="' . api_get_path(
            WEB_CODE_PATH
        ) . 'document/document.php?cidReq=' . Security::remove_XSS(
            $_GET['cidReq']
        ) . '&amp;id_session=' . Security::remove_XSS(
            $_GET['id_session']
        ) . '&amp;gidReq=' . Security::remove_XSS(
            $_GET['gidReq']
        ) . '&amp;id=' . Security::remove_XSS(
            $_GET['parent_id']
        ) . '">' . Display::return_icon(
            'back.png',
            get_lang('BackTo') . ' ' . get_lang('Documents') . ' (' . get_lang(
                'Course'
            ) . ')'
        ) . '</a>';
}
$tpl = new Template();
$editor = new \Chamilo\CoreBundle\Component\Editor\Editor();

$editor = $tpl->fetch('default/'.$editor->getEditorStandAloneTemplate());
$social_right_content = '<div class="span9">';
$social_right_content .= $editor;
$social_right_content .= '</div>';

$tpl->assign('social_avatar_block', $social_avatar_block);
$tpl->assign('social_menu_block', $social_menu_block);
$tpl->assign('social_right_content', $social_right_content);

$tpl->assign('actions', $actions);
$tpl->assign('message', $show_message);

$social_layout = $tpl->get_template('layout/social_layout.tpl');
$tpl->display($social_layout);
