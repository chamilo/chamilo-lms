<?php
/* For licensing terms, see /license.txt */
/**
 * @package chamilo.social
 * @author Julio Montoya <gugli100@gmail.com>
 */
/**
 * Initialization
 */
// name of the language file that needs to be included
$language_file = array('registration', 'admin', 'userInfo');
$cidReset      = true;
require_once '../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'group_portal_manager.lib.php';
require_once api_get_path(LIBRARY_PATH).'magpierss/rss_fetch.inc';
$ajax_url = api_get_path(WEB_AJAX_PATH).'message.ajax.php';
api_block_anonymous_users();

$htmlHeadXtra[] = '<script>

function checkLength( o, n, min, max ) {
    if ( o.val().length > max || o.val().length < min ) {
        o.addClass( "ui-state-error" );
        //updateTips( "Length of " + n + " must be between " + min + " and " + max + "." );
        return false;
    } else {
        return true;
    }
}

function send_message_to_user(user_id) {
    var subject = $( "#subject_id" );
    var content = $( "#content_id" );

    $("#send_message_form").show();
    $("#send_message_div").dialog({
        modal:true,
        height:350,
        buttons: {
            "'.addslashes(get_lang('Sent')).'": function() {
                var bValid = true;
                bValid = bValid && checkLength( subject, "subject", 1, 255 );
                bValid = bValid && checkLength( content, "content", 1, 255 );

                if (bValid) {
                    var url = "'.$ajax_url.'?a=send_message&user_id="+user_id;
                    var params = $("#send_message_form").serialize();
                    $.ajax({
                        url: url+"&"+params,
                        success:function(data) {
                            $("#message_ajax_reponse").attr("class", "");
                            $("#message_ajax_reponse").html(data);
                            $("#message_ajax_reponse").show();
                            $("#send_message_div").dialog({ buttons:{}});
                            $("#send_message_form").hide();
                            $("#send_message_div").dialog("close");

                            $("#subject_id").val("");
                            $("#content_id").val("");
                        }
                    });
                }
            },
        },
        close: function() {
        }
    });
    $("#send_message_div").dialog("open");
    //prevent the browser to follow the link
}

function send_invitation_to_user(user_id) {
    var content = $( "#content_invitation_id" );
    $("#send_invitation_form").show();
    $("#send_invitation_div").dialog({
        modal:true,
        buttons: {
            "'.addslashes(get_lang('SendInvitation')).'": function() {
                var bValid = true;
                bValid = bValid && checkLength( content, "content", 1, 255 );
                if (bValid) {
                    var url = "'.$ajax_url.'?a=send_invitation&user_id="+user_id;
                    var params = $("#send_invitation_form").serialize();
                    $.ajax({
                        url: url+"&"+params,
                        success:function(data) {
                            $("#message_ajax_reponse").attr("class", "");
                            $("#message_ajax_reponse").html(data);
                            $("#message_ajax_reponse").show();

                            $("#send_invitation_div").dialog({ buttons:{}});

                            $("#send_invitation_form").hide();
                            $("#send_invitation_div").dialog("close");
                            $("#content_invitation_id").val("");
                        }
                    });
                }
            },
        },
        close: function() {
        }
    });
    $("#send_invitation_div").dialog("open");
    //prevent the browser to follow the link
}

$(document).ready(function (){
    $("input#id_btn_send_invitation").bind("click", function(){
        if (confirm("'.get_lang('SendMessageInvitation', '').'")) {
            $("#form_register_friend").submit();
        }
    });

    $("#send_message_div").dialog({
        autoOpen: false,
        modal    : false,
        width    : 550,
        height    : 300
       });

    $("#send_invitation_div").dialog({
        autoOpen: false,
        modal    : false,
        width    : 550,
        height    : 300
       });
});
</script>';

if (api_get_setting('allow_social_tool') != 'true') {
    api_not_allowed();
}

$this_section      = SECTION_SOCIAL;
$tool_name         = get_lang('Search');
$interbreadcrumb[] = array('url' => 'profile.php', 'name' => get_lang('SocialNetwork'));

$query      = isset($_GET['q']) ? $_GET['q'] : null;
$query_vars = array('q' => $query);

$social_left_content = SocialManager::show_social_menu('search');

$social_right_content = '<div class="span9">'.UserManager::get_search_form($query).'</div>';

// I'm searching something
if ($query != '') {

    $itemPerPage = 9;
    $page = isset($_GET['users_page_nr']) ? intval($_GET['users_page_nr']) : 1;
    $totalUsers = UserManager::get_all_user_tags($_GET['q'], 0, 0, $itemPerPage, true);

    $from = intval(($page - 1) * $itemPerPage);
    // Get users from tags
    $users  = UserManager::get_all_user_tags($_GET['q'], 0, $from, $itemPerPage);

    $pageGroup = isset($_GET['groups_page_nr']) ? intval($_GET['groups_page_nr']) : 1;
    // Groups
    $fromGroups = intval(($pageGroup - 1) * $itemPerPage);

    $totalGroups = GroupPortalManager::get_all_group_tags($_GET['q'], 0, $itemPerPage, true);
    $groups = GroupPortalManager::get_all_group_tags($_GET['q'], $fromGroups, $itemPerPage);

    if (empty($users) && empty($groups)) {
        $social_right_content .= get_lang('SorryNoResults');
    }

    $results = '<div id="online_grid_container"><div class="span9">';
    if (is_array($users) && count($users) > 0) {
        $results .= Display::page_subheader(get_lang('Users'));
        $results .= '<ul class="thumbnails">';
        foreach ($users as $user) {

            $send_inv      = '<button class="btn btn-mini disabled "><i class="icon-user"></i> '.get_lang('SendInvitation').'</button><br /><br />';
            $relation_type = intval(SocialManager::get_relation_between_contacts(api_get_user_id(), $user['user_id']));
            $user_info     = api_get_user_info($user['user_id'], true);
            $url           = api_get_path(WEB_PATH).'main/social/profile.php?u='.$user['user_id'];

            // Show send invitation icon if they are not friends yet
            if ($relation_type != 3 && $relation_type != 4 && $user['user_id'] != api_get_user_id()) {
                $send_inv = '<a href="javascript:void(0);" onclick="javascript:send_invitation_to_user(\''.$user['user_id'].'\');"/>
                             <button class="btn btn-mini"><i class="icon-user"></i> '.get_lang('SendInvitation').'</button></a><br /><br />';
            }
            $send_msg = '<a href="javascript:void(0);" onclick="javascript:send_message_to_user(\''.$user['user_id'].'\');"/>
                        <button class="btn btn-mini"><i class="icon-envelope"></i> '.get_lang('SendMessage').'</button></a>';
            if (empty($user['picture_uri'])) {
                $picture['file'] = api_get_path(WEB_CODE_PATH).'img/unknown.jpg';
                $img             = '<img src="'.$picture['file'].'">';
            } else {
                $picture = UserManager::get_picture_user(
                    $user['user_id'],
                    $user['picture_uri'],
                    50,
                    USER_IMAGE_SIZE_ORIGINAL
                );
                $img = '<img src="'.$picture['file'].'" width="100" height="100">';
            }

            if ($user_info['user_is_online']) {
                $status_icon = Display::span('', array('class' => 'online_user_in_text'));
            } else {
                $status_icon = Display::span('', array('class' => 'offline_user_in_text'));
            }

            $tag = isset($user['tag']) ? ' <br /><br />'.$user['tag'] : null;

            $user_info['complete_name'] = Display::url($status_icon.$user_info['complete_name'], $url);

            $invitations = $user['tag'].$send_inv.$send_msg;

            $results .= '<li class="span3">
                            <div class="">
                            <div class="row-fluid">
                                <div class="span12">
                                    '.$user_info['complete_name'].'
                                </div>
                                <div class="span4">
                                    <div class="media">
                                    '.$img.'
                                    </div>
                                </div>
                                <div class="span5">
                                    <div class="media">
                                    '.$invitations.'
                                    </div>
                                </div>
                                </div>
                            </div>
                        </li>';
        }
        $results .= '</ul></div></div>';
        $social_right_content .= $results;
    }

    $visibility = array(true, true, true, true, true);
    $social_right_content .= Display::return_sortable_grid(
        'users',
        null,
        null,
        array('hide_navigation' => false, 'per_page' => $itemPerPage),
        $query_vars,
        false,
        $visibility,
        true,
        array(),
        $totalUsers
    );


    $grid_groups = array();
    if (is_array($groups) && count($groups) > 0) {
        $social_right_content .= '<div class="span9">';
        $social_right_content .= Display::page_subheader(get_lang('Groups'));

        $social_right_content .= '<ul class="thumbnails">';
        foreach ($groups as $group) {
            $group['name']         = Security::remove_XSS($group['name'], STUDENT, true);
            $group['description']  = Security::remove_XSS($group['description'], STUDENT, true);
            $id                    = $group['id'];
            $url_open              = '<a href="groups.php?id='.$id.'">';
            $url_close             = '</a>';
            $name                  = cut($group['name'], 60, true);
            $count_users_group     = count(GroupPortalManager::get_all_users_by_group($id));
            if ($count_users_group == 1) {
                $count_users_group = $count_users_group.' '.get_lang('Member');
            } else {
                $count_users_group = $count_users_group.' '.get_lang('Members');
            }
            $picture              = GroupPortalManager::get_picture_group($group['id'], $group['picture_uri'], 80);
            $tags                 = GroupPortalManager::get_group_tags($group['id']);
            $group['picture_uri'] = '<img src="'.$picture['file'].'" width="50" />';


            $item_0  = Display::div($group['picture_uri']);
            $members = Display::span($count_users_group);
            $item_1  = Display::tag('h3', $url_open.$name.$url_close).$members;

            $social_right_content .= '
                <li class="span8">
                    <div class="row-fluid">
                        <div class="span1">
                            <div class="media">
                                '.$item_0.'
                            </div>
                        </div>
                        <div class="span6">
                            '.$item_1.'
                            <p>'.$group['description'].'</p>
                            <p>'.$tags.'</p>
                            <p>'.$url_open.get_lang('SeeMore').$url_close.'</p>
                        </div>
                    </div>
                </li>';

        }
        $social_right_content .= '</ul></div></div>';
    }

    $visibility = array(true, true, true, true, true);
    $social_right_content .= Display::return_sortable_grid(
        'groups',
        null,
        $grid_groups,
        array('hide_navigation' => false, 'per_page' => $itemPerPage),
        $query_vars,
        false,
        $visibility,
        true,
        array(),
        $totalGroups
    );
}
$social_right_content .= MessageManager::generate_message_form('send_message');
$social_right_content .= MessageManager::generate_invitation_form('send_invitation');

$tpl = new Template($tool_name);
$tpl->assign('social_left_content', $social_left_content);
$tpl->assign('social_right_content', $social_right_content);

$social_layout = $tpl->get_template('layout/social_layout.tpl');
$tpl->display($social_layout);
