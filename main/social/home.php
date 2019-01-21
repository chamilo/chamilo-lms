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

//fast upload image
/*if (api_get_setting('profile', 'picture') == 'true') {
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
        $user_data = $form->getSubmitValues();
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
                        WHERE user_id =  ".api_get_user_id();

                $result = Database::query($sql);
            }
        }
    }
}*/

$socialSearch = UserManager::getSearchForm('');

// Top Last
$results['newest'] = $userGroup->get_groups_by_age(2, true);
// Top popular
$results['popular'] = $userGroup->get_groups_by_popularity(2, true);

// My friends
$friend_html = SocialManager::listMyFriendsBlock(
    $user_id,
    '',
    $show_full_profile
);
// Block Social Sessions
$social_session_block = null;
$user_info = api_get_user_info($user_id);
$sessionList = SessionManager::getSessionsFollowedByUser($user_id, $user_info['status']);

if (count($sessionList) > 0) {
    $social_session_block = $sessionList;
}

$tpl = new Template(get_lang('SocialNetwork'));

SocialManager::setSocialUserBlock($tpl, api_get_user_id(), 'home');

$tpl->assign('social_friend_block', $friend_html);
$tpl->assign('session_list', $social_session_block);
$tpl->assign('social_search', $socialSearch);
$tpl->assign('social_skill_block', SocialManager::getSkillBlock($user_id));
$tpl->assign('groups', $results);
$social_layout = $tpl->get_template('social/home.tpl');
$content = $tpl->fetch($social_layout);
$tpl->assign('content', $content);
$tpl->display_one_col_template();
