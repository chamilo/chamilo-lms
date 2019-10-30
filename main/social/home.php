<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * @package chamilo.social
 *
 * @author Julio Montoya <gugli100@gmail.com>
 * @autor Alex Aragon <alex.aragon@beeznest.com> CSS Design and Template
 */
$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

api_block_anonymous_users();

$user_id = api_get_user_id();
$show_full_profile = true;
// social tab
Session::erase('this_section');
$this_section = SECTION_SOCIAL;

if (api_get_setting('allow_social_tool') !== 'true') {
    $url = api_get_path(WEB_CODE_PATH).'auth/profile.php';
    header('Location: '.$url);
    exit;
}

$userGroup = new UserGroup();

SocialManager::handlePosts(api_get_self());

$threadList = SocialManager::getThreadList($user_id);
$threadIdList = [];
if (!empty($threadList)) {
    $threadIdList = array_column($threadList, 'id');
}

$posts = SocialManager::getMyWallMessages($user_id, 0, 10, $threadIdList);
$countPost = $posts['count'];
$posts = $posts['posts'];
SocialManager::getScrollJs($countPost, $htmlHeadXtra);

// Block Menu
$menu = SocialManager::show_social_menu('home');

$social_search_block = Display::panel(
    UserManager::get_search_form(''),
    get_lang('Search users')
);

$social_group_block = SocialManager::getGroupBlock($user_id);

// My friends
$friend_html = SocialManager::listMyFriendsBlock($user_id);

// Block Social Sessions
$social_session_block = null;
//$user_info = api_get_user_info($user_id);
//$sessionList = SessionManager::getSessionsFollowedByUser($user_id, $user_info['status']);
$sessionList = [];

if (count($sessionList) > 0) {
    $social_session_block = $sessionList;
}

$wallSocialAddPost = SocialManager::getWallForm(api_get_self());
$socialAutoExtendLink = SocialManager::getAutoExtendLink($user_id, $countPost);

$formSearch = new FormValidator(
    'find_friends_form',
    'get',
    api_get_path(WEB_CODE_PATH).'social/search.php?search_type=1',
    null,
    null,
    FormValidator::LAYOUT_BOX_NO_LABEL
);
$formSearch->addHidden('search_type', 1);
$formSearch->addText(
    'q',
    get_lang('Search'),
    false,
    [
        'aria-label' => get_lang('Search users'),
        'custom' => true,
        'placeholder' => get_lang('Search usersByName'),
    ]
);

// Added a Jquery Function to return the Preview of OpenGraph URL Content
$htmlHeadXtra[] = SocialManager::getScriptToGetOpenGraph();

$tpl = new Template(get_lang('Social network'));
SocialManager::setSocialUserBlock($tpl, $user_id, 'home');
$tpl->assign('add_post_form', $wallSocialAddPost);
$tpl->assign('posts', $posts);
$tpl->assign('social_menu_block', $menu);
$tpl->assign('social_auto_extend_link', $socialAutoExtendLink);
$tpl->assign('search_friends_form', $formSearch->returnForm());
$tpl->assign('social_friend_block', $friend_html);
$tpl->assign('social_search_block', $social_search_block);
$tpl->assign('social_skill_block', SocialManager::getSkillBlock($user_id, 'vertical'));
$tpl->assign('social_group_block', $social_group_block);
$tpl->assign('session_list', $social_session_block);
$social_layout = $tpl->get_template('social/home.tpl');
$tpl->display($social_layout);
