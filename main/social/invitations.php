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

if (api_get_setting('allow_social_tool') !== 'true') {
    api_not_allowed(true);
}

$this_section = SECTION_SOCIAL;

$interbreadcrumb[] = ['url' => 'profile.php', 'name' => get_lang('SocialNetwork')];
$interbreadcrumb[] = ['url' => '#', 'name' => get_lang('Invitations')];

$userGroupModel = new UserGroup();

if (is_array($_GET) && count($_GET) > 0) {
    foreach ($_GET as $key => $value) {
        switch ($key) {
            case 'accept':
                $useRole = $userGroupModel->get_user_group_role(api_get_user_id(), $value);

                if (in_array(
                    $useRole,
                    [
                        GROUP_USER_PERMISSION_PENDING_INVITATION_SENT_BY_USER,
                        GROUP_USER_PERMISSION_PENDING_INVITATION,
                    ]
                )) {
                    $userGroupModel->update_user_role(api_get_user_id(), $value, GROUP_USER_PERMISSION_READER);

                    Display::addFlash(
                        Display::return_message(get_lang('UserIsSubscribedToThisGroup'), 'success')
                    );

                    header('Location: '.api_get_path(WEB_CODE_PATH).'social/invitations.php');
                    exit;
                }

                if (in_array(
                    $useRole,
                    [
                        GROUP_USER_PERMISSION_READER,
                        GROUP_USER_PERMISSION_ADMIN,
                        GROUP_USER_PERMISSION_MODERATOR,
                    ]
                )) {
                    Display::addFlash(
                        Display::return_message(get_lang('UserIsAlreadySubscribedToThisGroup'), 'warning')
                    );

                    header('Location: '.api_get_path(WEB_CODE_PATH).'social/invitations.php');
                    exit;
                }

                Display::addFlash(
                    Display::return_message(get_lang('UserIsNotSubscribedToThisGroup'), 'warning')
                );

                header('Location: '.api_get_path(WEB_CODE_PATH).'social/invitations.php');
                exit;
                break;
            case 'deny':
                $userGroupModel->delete_user_rel_group(api_get_user_id(), $value);

                Display::addFlash(
                    Display::return_message(get_lang('GroupInvitationWasDeny'))
                );

                header('Location: '.api_get_path(WEB_CODE_PATH).'social/invitations.php');
                exit;
        }
    }
}

$content = null;

// Block Menu Social
$social_menu_block = SocialManager::show_social_menu('invitations');
// Block Invitations
$socialInvitationsBlock = '<div id="id_response" align="center"></div>';

$user_id = api_get_user_id();
$list_get_invitation = SocialManager::get_list_invitation_of_friends_by_user_id($user_id);
$list_get_invitation_sent = SocialManager::get_list_invitation_sent_by_user_id($user_id);
$pending_invitations = $userGroupModel->get_groups_by_user(
    $user_id,
    GROUP_USER_PERMISSION_PENDING_INVITATION
);
$numberLoop = count($list_get_invitation);

$total_invitations = $numberLoop + count($list_get_invitation_sent) + count($pending_invitations);

if (count($_GET) <= 0) {
    $socialInvitationsBlock .= '<div class="row">
        <div class="col-md-12">
            <a class="btn btn-success" href="search.php"><em class="fa fa-search"></em> '.
                get_lang('TryAndFindSomeFriends').'
            </a>
            </div>
        </div><br />';
}

if ($numberLoop != 0) {
    $invitationHtml = '';
    foreach ($list_get_invitation as $invitation) {
        $sender_user_id = $invitation['user_sender_id'];
        $user_info = api_get_user_info($sender_user_id);
        $userPicture = $user_info['avatar'];
        $invitationHtml .= '<div id="id_'.$sender_user_id.'" class="block-invitation">';

        $title = Security::remove_XSS($invitation['title'], STUDENT, true);
        $content = Security::remove_XSS($invitation['content'], STUDENT, true);
        $date = Display::dateToStringAgoAndLongDate($invitation['send_date']);
        $invitationHtml .= '<div class="row">';
        $invitationHtml .= '<div class="col-md-2">';
        $invitationHtml .= '<a href="profile.php?u='.$sender_user_id.'">';
        $invitationHtml .= '<img class="img-responsive img-rounded" src="'.$userPicture.'"/></a>';
        $invitationHtml .= '</div>';
        $invitationHtml .= '<div class="col-md-10">';

        $invitationHtml .= '<div class="pull-right">';
        $invitationHtml .= '<div class="btn-group btn-group-sm" role="group">';
        $invitationHtml .= Display::toolbarButton(
            null,
            api_get_path(WEB_AJAX_PATH).'social.ajax.php?'.http_build_query([
                'a' => 'add_friend',
                'friend_id' => $sender_user_id,
                'is_my_friend' => 'friend',
            ]),
            'check',
            'primary',
            ['id' => 'btn-accept-'.$sender_user_id]
        );
        $invitationHtml .= Display::toolbarButton(
            null,
            api_get_path(WEB_AJAX_PATH).'social.ajax.php?'.http_build_query([
                'a' => 'deny_friend',
                'denied_friend_id' => $sender_user_id,
            ]),
            'times',
            'danger',
            ['id' => 'btn-deny-'.$sender_user_id]
        );
        $invitationHtml .= '</div>';
        $invitationHtml .= '</div>';

        $invitationHtml .= '<h5 class="title-profile"><a href="profile.php?u='.$sender_user_id.'">
                            '.$user_info['complete_name'].'</a>:
                            </h5>';
        $invitationHtml .= '<div class="content-invitation">'.$content.'</div>';
        $invitationHtml .= '<div class="date-invitation">'.get_lang('Sent').' : '.$date.'</div>';

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

        $invitationSentHtml .= '<div class="row">';
        $invitationSentHtml .= '<div class="col-md-3">';
        $invitationSentHtml .= '<a href="profile.php?u='.$sender_user_id.'">';
        $invitationSentHtml .= '<img class="img-responsive img-rounded" src="'.$user_info['avatar'].'" /></a>';
        $invitationSentHtml .= '</div>';
        $invitationSentHtml .= '<div class="col-md-9">';
        $invitationSentHtml .= '<h4 class="title-profile">
            <a class="profile_link" href="profile.php?u='.$sender_user_id.'">'.$user_info['complete_name'].'</a></h4>';
        $invitationSentHtml .= '<div class="content-invitation">'.$title.' : '.$content.'</div>';
        $invitationSentHtml .= '<div class="date-invitation">'.
            get_lang('Sent').' : '.Display::dateToStringAgoAndLongDate($invitation['send_date']).'</div>';
        $invitationSentHtml .= '</div>';
        $invitationSentHtml .= '</div></div>';
    }
    $socialInvitationsBlock .= Display::panel($invitationSentHtml, get_lang('InvitationSent'));
}

if (count($pending_invitations) > 0) {
    $new_invitation = [];
    $waitingInvitation = '';
    foreach ($pending_invitations as $invitation) {
        $picture = $userGroupModel->get_picture_group(
            $invitation['id'],
            $invitation['picture'],
            null,
            GROUP_IMAGE_SIZE_BIG
        );
        $img = '<img class="img-responsive" src="'.$picture['file'].'" />';
        $invitation['picture_uri'] = '<a href="group_view.php?id='.$invitation['id'].'">'.$img.'</a>';
        $invitation['name'] = '<a href="group_view.php?id='.$invitation['id'].'">'.
            cut($invitation['name'], 120, true).'</a>';
        $invitation['description'] = cut($invitation['description'], 220, true);
        $new_invitation[] = $invitation;
        $waitingInvitation .= '<div class="panel-invitations"><div class="row">';
        $waitingInvitation .= '<div class="col-md-3">'.$invitation['picture_uri'].'</div>';
        $waitingInvitation .= '<div class="col-md-9">';
        $waitingInvitation .= '<h4 class="tittle-profile">'.$invitation['name'].'</h4>';
        $waitingInvitation .= '<div class="description-group">'.$invitation['description'].'</div>';
        $waitingInvitation .= '<div class="btn-group" role="group">';
        $waitingInvitation .= Display::toolbarButton(
            get_lang('AcceptInvitation'),
            api_get_path(WEB_CODE_PATH).'social/invitations.php?'.http_build_query(['accept' => $invitation['id']]),
            'check',
            'success',
            ['id' => 'accept-invitation-'.$invitation['id']]
        );
        $waitingInvitation .= Display::toolbarButton(
            get_lang('DenyInvitation'),
            api_get_path(WEB_CODE_PATH).'social/invitations.php?'.http_build_query(['deny' => $invitation['id']]),
            'times',
            'danger',
            ['id' => 'deny-invitation-'.$invitation['id']]
        );
        $waitingInvitation .= '</div>';
        $waitingInvitation .= '</div></div>';
    }
    $socialInvitationsBlock .= Display::panel($waitingInvitation, get_lang('GroupsWaitingApproval'));
}

$tpl = new Template(null);
SocialManager::setSocialUserBlock($tpl, api_get_user_id(), 'invitations');
$tpl->assign('social_menu_block', $social_menu_block);
$tpl->assign('social_invitations_block', $socialInvitationsBlock);
$tpl->assign('content', $content);
$social_layout = $tpl->get_template('social/invitations.tpl');
$tpl->display($social_layout);
