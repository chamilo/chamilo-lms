<?php

/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
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

//fast upload image
if (api_get_setting('profile', 'picture') == 'true') {
    $form = new FormValidator('profile', 'post', 'home.php', null, []);

    //	PICTURE
    $form->addElement('file', 'picture', get_lang('AddImage'));
    $form->addProgress();
    if (!empty($user_data['picture_uri'])) {
        $form->addElement(
            'checkbox',
            'remove_picture',
            null,
            get_lang('DelImage')
        );
    }
    $allowed_picture_types = api_get_supported_image_extensions();
    $form->addRule(
        'picture',
        get_lang('OnlyImagesAllowed').' ('.implode(
            ',',
            $allowed_picture_types
        ).')',
        'filetype',
        $allowed_picture_types
    );
    $form->addButtonSave(get_lang('SaveSettings'), 'apply_change');

    if ($form->validate()) {
        // upload picture if a new one is provided
        if ($_FILES['picture']['size']) {
            if ($new_picture = UserManager::update_user_picture(
                api_get_user_id(),
                $_FILES['picture']['name'],
                $_FILES['picture']['tmp_name']
            )) {
                $table_user = Database::get_main_table(TABLE_MAIN_USER);
                $sql = "UPDATE $table_user
                        SET
                            picture_uri = '$new_picture'
                        WHERE user_id =  ".$user_id;
                Database::query($sql);
            }
        }
    }
}

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
    get_lang('SearchUsers')
);

$social_group_block = SocialManager::getGroupBlock($user_id);

// My friends
$friend_html = SocialManager::listMyFriendsBlock($user_id);

// Block Social Sessions
$wallSocialAddPost = SocialManager::displayWallForm(api_get_self());
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
        'aria-label' => get_lang('SearchUsers'),
        'custom' => true,
        'placeholder' => get_lang('SearchUsersByName'),
    ]
);

// Added a Jquery Function to return the Preview of OpenGraph URL Content
$htmlHeadXtra[] = SocialManager::getScriptToGetOpenGraph();

$tpl = new Template(get_lang('SocialNetwork'));
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
$tpl->assign('session_list', null);
$social_layout = $tpl->get_template('social/home.tpl');
$tpl->display($social_layout);
