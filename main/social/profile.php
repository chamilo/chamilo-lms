<?php

/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * This is the profile social main page.
 *
 * @author Julio Montoya <gugli100@gmail.com>
 * @author Isaac Flores Paz <florespaz_isaac@hotmail.com>
 *
 * @todo use Display::panel()
 */
$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';

if (api_get_setting('allow_social_tool') !== 'true') {
    $url = api_get_path(WEB_PATH).'whoisonline.php?id='.intval($_GET['u']);
    header('Location: '.$url);
    exit;
}
$portfolioRequest = isset($_GET['p']) ? true : false;
$productionString = '';
$bossId = isset($_REQUEST['sup']) ? (int) $_REQUEST['sup'] : 0;
$user_id = api_get_user_id();

$redirectToBossId = 0;
if (!empty($bossId)) {
    $bossList = UserManager::getStudentBossList($user_id);
    if (!empty($bossList)) {
        foreach ($bossList as $boss) {
            $bossId = $boss['boss_id'];
            $bossInfo = api_get_user_info($bossId);
            if (!empty($bossInfo)) {
                $redirectToBossId = $bossId;
                break;
            }
        }
    }
}

if (!empty($redirectToBossId)) {
    header('Location: '.api_get_self().'?u='.$redirectToBossId);
    exit;
}

$friendId = isset($_GET['u']) ? (int) $_GET['u'] : api_get_user_id();
$show_full_profile = true;
//social tab
$this_section = SECTION_SOCIAL;

// Initialize blocks
$social_course_block = null;
$social_group_info_block = null;
$social_rss_block = null;
$social_session_block = null;

SocialManager::handlePosts(api_get_self().'?u='.$friendId);

if (isset($_GET['u'])) {
    //I'm your friend? I can see your profile?
    $user_id = (int) $_GET['u'];
    if (api_is_anonymous($user_id, true)) {
        api_not_allowed(true);
    }
    // It's me!
    if (api_get_user_id() != $user_id) {
        $user_info = api_get_user_info($user_id);
        $show_full_profile = false;
        if (!$user_info) {
            // user does no exist !!
            api_not_allowed(true);
        } else {
            //checking the relationship between me and my friend
            $my_status = SocialManager::get_relation_between_contacts(
                api_get_user_id(),
                $user_id
            );
            if (in_array($my_status, [
                    USER_RELATION_TYPE_PARENT,
                    USER_RELATION_TYPE_FRIEND,
                    USER_RELATION_TYPE_GOODFRIEND,
                ])
            ) {
                $show_full_profile = true;
            }
            //checking the relationship between my friend and me
            $my_friend_status = SocialManager::get_relation_between_contacts(
                $user_id,
                api_get_user_id()
            );
            if (in_array($my_friend_status, [
                    USER_RELATION_TYPE_PARENT,
                    USER_RELATION_TYPE_FRIEND,
                    USER_RELATION_TYPE_GOODFRIEND,
                ])
            ) {
                $show_full_profile = true;
            } else {
                // im probably not a good friend
                $show_full_profile = false;
            }
        }
    }
}

api_block_anonymous_users();
$countPost = SocialManager::getCountWallMessagesByUser($friendId);
SocialManager::getScrollJs($countPost, $htmlHeadXtra);
$link_shared = '';
if (isset($_GET['shared'])) {
    $link_shared = 'shared='.Security::remove_XSS($_GET['shared']);
}
$interbreadcrumb[] = [
    'url' => 'home.php',
    'name' => get_lang('SocialNetwork'),
];

if (isset($_GET['u']) && is_numeric($_GET['u']) && $_GET['u'] != api_get_user_id()) {
    $info_user = api_get_user_info($_GET['u']);
    $interbreadcrumb[] = [
        'url' => '#',
        'name' => $info_user['complete_name'],
    ];
}

Session::write('social_user_id', $user_id);

// Social Block Menu
$menu = SocialManager::show_social_menu(
    'shared_profile',
    null,
    $user_id,
    $show_full_profile
);

//Setting some session info
$user_info = api_get_user_info($friendId);
$sessionList = [];

// My friends
$friend_html = SocialManager::listMyFriendsBlock($user_id, $link_shared);
$addPostForm = SocialManager::displayWallForm(api_get_self());
$addPostFormPortfolio = SocialManager::getWallFormPortfolio(api_get_self());

$posts = SocialManager::getWallMessagesByUser($friendId);
$socialAutoExtendLink = SocialManager::getAutoExtendLink($user_id, $countPost);

// Added a Jquery Function to return the Preview of OpenGraph URL Content
$htmlHeadXtra[] = SocialManager::getScriptToGetOpenGraph();

$socialRightInformation = '';
$listInvitations = '';
$more_info = '';

if ($show_full_profile) {
    $social_group_info_block = SocialManager::getGroupBlock($friendId);
    // Block Social Sessions
    if (count($sessionList) > 0) {
        $social_session_block = $sessionList;
    }

    // Block Social User Feeds
    $user_feeds = SocialManager::getUserRssFeed($user_id);

    if (!empty($user_feeds)) {
        $social_rss_block = Display::panel($user_feeds, get_lang('RSSFeeds'));
    }

    // Productions
    $production_list = UserManager::build_production_list($user_id);

    // Images uploaded by course
    $file_list = '';
    $count_pending_invitations = 0;
    if (!isset($_GET['u']) ||
        (isset($_GET['u']) && $_GET['u'] == api_get_user_id())
    ) {
        $pending_invitations = SocialManager::get_list_invitation_of_friends_by_user_id(api_get_user_id());
        $list_get_path_web = SocialManager::get_list_web_path_user_invitation_by_user_id(api_get_user_id());
        $count_pending_invitations = count($pending_invitations);
    }

    if (!empty($production_list) || !empty($file_list) || $count_pending_invitations > 0) {
        // Pending invitations
        if (!isset($_GET['u']) || (isset($_GET['u']) && $_GET['u'] == api_get_user_id())) {
            if ($count_pending_invitations > 0) {
                $invitations = '<ul class="list-group">';
                for ($i = 0; $i < $count_pending_invitations; $i++) {
                    $user_invitation_id = $pending_invitations[$i]['user_sender_id'];
                    $invitations .= '<li id="dpending_'.$user_invitation_id.'" class="list-group-item">';
                    $invitations .= '<img class="img-rounded" '
                                .' src="'.$list_get_path_web[$i]['dir'].'/'.$list_get_path_web[$i]['file'].'"'
                                .' width="40px">';
                    $userInfo = api_get_user_info($user_invitation_id);
                    $invitations .= '<a href="'.api_get_path(WEB_PATH).'main/social/profile.php?u='.$user_invitation_id.'">'
                                 .api_get_person_name($userInfo['firstname'], $userInfo['lastname']).'</a>';

                    $invitations .= '<div class="pull-right">';
                    $invitations .= Display::toolbarButton(
                        get_lang('SocialAddToFriends'),
                        api_get_path(WEB_AJAX_PATH).'social.ajax.php?'.http_build_query([
                            'a' => 'add_friend',
                            'friend_id' => $user_invitation_id,
                            'is_my_friend' => 'friend',
                        ]),
                        'plus',
                        'default',
                        ['class' => 'btn-sm'],
                        false
                    );
                    $invitations .= '</div>';
                    $invitations .= '<div id="id_response"></div>';
                    $invitations .= '</li>';
                }
                $invitations .= '</ul>';
                $listInvitations = Display::panelCollapse(
                    get_lang('PendingInvitations'),
                    $invitations,
                    'invitations',
                    null,
                    'invitations-acordion',
                    'invitations-collapse'
                );
            }
        }

        //    MY PRODUCTIONS
        // Productions
        $production_list = UserManager::build_production_list($user_id);
        $product_content = '';
        if (!empty($production_list)) {
            $product_content .= '<div><h3>'.get_lang('MyProductions').'</h3></div>';
            $product_content .= $production_list;
            // $socialRightInformation .= SocialManager::social_wrapper_div($product_content, 4);
            $more_info_1 = '<div class="social-actions-message"><strong>'.get_lang('MyProductions').'</strong></div>';
            $more_info_1 .= '<div class="social-profile-extended">'.$production_list.'</div>';
            $more_info_1 .= '<br />';
            $productionString .= $more_info_1;
        }

        $images_uploaded = null;
    }

    if (!empty($user_info['competences']) || !empty($user_info['diplomas'])
        || !empty($user_info['openarea']) || !empty($user_info['teach'])) {
        $more_info .= '<div><h3>'.get_lang('MoreInformation').'</h3></div>';
        //    MY PERSONAL OPEN AREA
        if (!empty($user_info['openarea'])) {
            $more_info .= '<div class="social-actions-message"><strong>'.get_lang('MyPersonalOpenArea').'</strong></div>';
            $more_info .= '<div class="social-profile-extended">'.$user_info['openarea'].'</div>';
            $more_info .= '<br />';
        }
        //    MY COMPETENCES
        if (!empty($user_info['competences'])) {
            $more_info .= '<div class="social-actions-message"><strong>'.get_lang('MyCompetences').'</strong></div>';
            $more_info .= '<div class="social-profile-extended">'.$user_info['competences'].'</div>';
            $more_info .= '<br />';
        }
        //    MY DIPLOMAS
        if (!empty($user_info['diplomas'])) {
            $more_info .= '<div class="social-actions-message"><strong>'.get_lang('MyDiplomas').'</strong></div>';
            $more_info .= '<div class="social-profile-extended">'.$user_info['diplomas'].'</div>';
            $more_info .= '<br />';
        }
        //    MY PRODUCTIONS
        $more_info .= $productionString;

        // WHAT I AM ABLE TO TEACH
        if (!empty($user_info['teach'])) {
            $more_info .= '<div class="social-actions-message"><strong>'.get_lang('MyTeach').'</strong></div>';
            $more_info .= '<div class="social-profile-extended">'.$user_info['teach'].'</div>';
            $more_info .= '<br />';
        }
        $socialRightInformation .= SocialManager::social_wrapper_div($more_info, 4);
    }
}

$tpl = new Template(get_lang('Social'));
// Block Avatar Social
SocialManager::setSocialUserBlock(
    $tpl,
    $friendId,
    'shared_profile',
    0,
    $show_full_profile
);

$tpl->assign('social_friend_block', $friend_html);
$tpl->assign('social_menu_block', $menu);
if ($portfolioRequest == true && api_get_setting('extended_profile') == true) {
    $tpl->assign('add_post_form', $addPostFormPortfolio);
    $socialRightInformation = null;
} else {
    $tpl->assign('add_post_form', $addPostForm);
}
$tpl->assign('posts', $posts);
$tpl->assign('social_course_block', $social_course_block);
$tpl->assign('social_group_info_block', $social_group_info_block);
$tpl->assign('social_rss_block', $social_rss_block);
$tpl->assign('social_skill_block', SocialManager::getSkillBlock($friendId, 'vertical'));
$tpl->assign('session_list', $social_session_block);
$tpl->assign('invitations', $listInvitations);
$tpl->assign('social_right_information', $socialRightInformation);
$tpl->assign('social_auto_extend_link', $socialAutoExtendLink);

$formModalTpl = new Template();
$formModalTpl->assign('invitation_form', MessageManager::generate_invitation_form());
$template = $formModalTpl->get_template('social/form_modals.tpl');
$formModals = $formModalTpl->fetch($template);

$tpl->assign('form_modals', $formModals);
$tpl->display($tpl->get_template('social/profile.tpl'));
