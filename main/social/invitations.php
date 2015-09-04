<?php
/* For licensing terms, see /license.txt */

/**
 * @package chamilo.social
 * @author Julio Montoya <gugli100@gmail.com>
 */
$cidReset = true;
require_once '../inc/global.inc.php';

api_block_anonymous_users();

if (api_get_setting('allow_social_tool') !='true') {
    api_not_allowed();
}

$this_section = SECTION_SOCIAL;

$interbreadcrumb[] = array ('url' =>'profile.php','name' => get_lang('SocialNetwork'));
$interbreadcrumb[] = array ('url' =>'#','name' => get_lang('Invitations'));

$userGroup = new UserGroup();

$htmlHeadXtra[] = '
<script>
function denied_friend(element_input) {
    name_button=$(element_input).attr("id");
    name_div_id="id_"+name_button.substring(13);
    user_id=name_div_id.split("_");
    friend_user_id=user_id[1];
    $.ajax({
        contentType: "application/x-www-form-urlencoded",
        beforeSend: function(objeto) {
            $("#id_response").html("<img src=\'../inc/lib/javascript/indicator.gif\' />");
        },
        type: "POST",
        url: "'.api_get_path(WEB_AJAX_PATH).'social.ajax.php?a=deny_friend",
        data: "denied_friend_id="+friend_user_id,
        success: function(datos) {
            $("div#"+name_div_id).hide("slow");
            $("#id_response").html(datos);
        }
   });
}
function register_friend(element_input) {
    if(confirm("'.get_lang('AddToFriends').'")) {
		name_button=$(element_input).attr("id");
		name_div_id="id_"+name_button.substring(13);
		user_id=name_div_id.split("_");
		user_friend_id=user_id[1];
        $.ajax({
           contentType: "application/x-www-form-urlencoded",
           beforeSend: function(objeto) {
               $("div#dpending_"+user_friend_id).html("<img src=\'../inc/lib/javascript/indicator.gif\' />"); },
               type: "POST",
               url: "'.api_get_path(WEB_AJAX_PATH).'social.ajax.php?a=add_friend",
               data: "friend_id="+user_friend_id+"&is_my_friend="+"friend",
               success: function(data) {
                   $("div#"+name_div_id).hide("slow");
                   $("#id_response").html(data);
               }
		});
    }
}

</script>';
$show_message = null;
$content = null;

// Block Menu Social
$social_menu_block = SocialManager::show_social_menu('invitations');
// Block Invitations
$socialInvitationsBlock = '<div id="id_response" align="center"></div>';

$user_id = api_get_user_id();
$list_get_invitation = SocialManager::get_list_invitation_of_friends_by_user_id($user_id);
$list_get_invitation_sent = SocialManager::get_list_invitation_sent_by_user_id($user_id);
$pending_invitations = $userGroup->get_groups_by_user(
    $user_id,
    GROUP_USER_PERMISSION_PENDING_INVITATION
);
$number_loop = count($list_get_invitation);

$total_invitations = $number_loop + count($list_get_invitation_sent) + count($pending_invitations);

if ($total_invitations == 0 && count($_GET) <= 0) {
    $socialInvitationsBlock .= '<div class="row">
        <div class="col-md-12">
            <a class="btn btn-default" href="search.php">'.
                get_lang('TryAndFindSomeFriends').'
            </a>
            </div>
        </div>';
}

if ($number_loop != 0) {
    $invitationHtml = '';
    foreach ($list_get_invitation as $invitation) {
        $sender_user_id = $invitation['user_sender_id'];
        $user_info = api_get_user_info($sender_user_id);
        $userPicture = $user_info['avatar'];
        $invitationHtml .= '<div id="id_'.$sender_user_id.'" class="well">';

        $title = Security::remove_XSS($invitation['title'], STUDENT, true);
        $content = Security::remove_XSS($invitation['content'], STUDENT, true);
        $date = api_convert_and_format_date($invitation['send_date'], DATE_TIME_FORMAT_LONG);
        $invitationHtml .= '<div class="row">';
        $invitationHtml .= '<div class="col-md-3">';
        $invitationHtml .= '<a href="profile.php?u='.$sender_user_id.'"><img src="'.$userPicture.'"/></a>';
        $invitationHtml .= '</div>';
        $invitationHtml .= '<div class="col-md-9">';
        $invitationHtml .= '<h4 class="title-profile"><a href="profile.php?u='.$sender_user_id.'">
                                    '.$user_info['complete_name'].'</a>:
                                    </h4>';
        $invitationHtml .= '<div class="content-invitation">'.$content.'</div>';
        $invitationHtml .= '<div class="date-invitation">'.get_lang('DateSend').' : '.$date.'</div>';
        $invitationHtml .= '<div class="btn-group" role="group">
                            <button class="btn btn-success" type="submit" id="btn_accepted_'.$sender_user_id.'" onclick="javascript:register_friend(this)">
                            <i class="fa fa-check"></i> '.get_lang('AcceptInvitation').'</button>
                            <button class="btn btn-danger" type="submit" id="btn_deniedst_'.$sender_user_id.' " onclick="javascript:denied_friend(this)" >
                            <i class="fa fa-times"></i> '.get_lang('DenyInvitation').'</button>
                            ';
        $invitationHtml .= '</div>';
        $invitationHtml .= '</div>';
        $invitationHtml .= '</div></div>';
    }
    $socialInvitationsBlock .= Display::panel($invitationHtml, get_lang('InvitationReceived'));
}

if (count($list_get_invitation_sent) > 0) {
    $invitationSentHtml = '';
    foreach ($list_get_invitation_sent as $invitation) {
        $sender_user_id = $invitation['user_receiver_id'];
        $user_info = api_get_user_info($sender_user_id);

        $invitationSentHtml .= '<div id="id_'.$sender_user_id.'" class="well">';

        $title = Security::remove_XSS($invitation['title'], STUDENT, true);
        $content = Security::remove_XSS($invitation['content'], STUDENT, true);
        $date = api_convert_and_format_date($invitation['send_date'], DATE_TIME_FORMAT_LONG);

        $invitationSentHtml .= '<div class="row">';
        $invitationSentHtml .= '<div class="col-md-3">';
        $invitationSentHtml .= '<a href="profile.php?u='.$sender_user_id.'"><img src="'.$user_info['avatar'].'"  /></a>';
        $invitationSentHtml .= '</div>';
        $invitationSentHtml .= '<div class="col-md-9">';
        $invitationSentHtml .= '<h4 class="title-profile"><a class="profile_link" href="profile.php?u='.$sender_user_id.'">'.$user_info['complete_name'].'</a></h4>';
        $invitationSentHtml .= '<div class="content-invitation">'.$title.' : '.$content.'</div>';
        $invitationSentHtml .= '<div class="date-invitation">'. get_lang('DateSend').' : '.$date.'</div>';
        $invitationSentHtml .= '</div>';
        $invitationSentHtml .= '</div></div>';
    }
    $socialInvitationsBlock .= Display::panel($invitationSentHtml, get_lang('InvitationSent'));
}

if (count($pending_invitations) > 0) {
    $new_invitation = array();
    $waitingInvitation = '';
    foreach ($pending_invitations as $invitation) {
        $picture = $userGroup->get_picture_group(
            $invitation['id'],
            $invitation['picture'],
            80
        );
        $img = '<img class="social-groups-image" src="'.$picture['file'].'" />';
        $invitation['picture_uri'] = '<a href="group_view.php?id='.$invitation['id'].'">'.$img.'</a>';
        $invitation['name'] = '<a href="group_view.php?id='.$invitation['id'].'">'.cut($invitation['name'],120,true).'</a>';
        $invitation['description'] = cut($invitation['description'],220,true);
        $new_invitation[]=$invitation;

        $waitingInvitation .= '<div class="well"><div class="row">';
        $waitingInvitation .= '<div class="col-md-3">'.$invitation['picture_uri'].'</div>';
        $waitingInvitation .= '<div class="col-md-9">';
        $waitingInvitation .= '<h4 class="tittle-profile">'.$invitation['name'].'</h4>';
        $waitingInvitation .= '<div class="description-group">'.$invitation['description'].'</div>';
        $waitingInvitation .= '<div class="btn-group" role="group">';
        $waitingInvitation .= '<a class="btn btn-success" href="invitations.php?accept='.$invitation['id'].'"><i class="fa fa-check"></i> '.get_lang('AcceptInvitation').'</a>';
        $waitingInvitation .= '<a class="btn btn-danger" href="invitations.php?deny='.$invitation['id'].'"><i class="fa fa-times"></i> '.get_lang('DenyInvitation').'</a>';
        $waitingInvitation .='</div>';
        $waitingInvitation .= '</div></div>';
    }
    $socialInvitationsBlock .= Display::panel($waitingInvitation, get_lang('GroupsWaitingApproval'));
}

$tpl = new Template(null);
SocialManager::setSocialUserBlock($tpl, $user_id, 'invitations');
$tpl->assign('social_menu_block', $social_menu_block);
$tpl->assign('social_invitations_block',$socialInvitationsBlock);
$tpl->assign('message', $show_message);
$tpl->assign('content', $content);
$social_layout = $tpl->get_template('social/invitations.tpl');
$tpl->display($social_layout);
