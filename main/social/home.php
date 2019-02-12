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

$user_id = api_get_user_id();
$show_full_profile = true;
// social tab
Session::erase('this_section');
$this_section = SECTION_SOCIAL;
api_block_anonymous_users();

if (api_get_setting('allow_social_tool') != 'true') {
    $url = api_get_path(WEB_CODE_PATH).'auth/profile.php';
    header('Location: '.$url);
    exit;
}

$userGroup = new UserGroup();

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
}

SocialManager::handlePosts(api_get_self());

// Social Post Wall
$posts = SocialManager::getMyWallMessages($user_id);
$countPost = $posts['count'];
$posts = $posts['posts'];
SocialManager::getScrollJs($countPost, $htmlHeadXtra);

// Block Menu
$social_menu_block = SocialManager::show_social_menu('home');

$social_search_block = Display::panel(
    UserManager::get_search_form(''),
    get_lang('SearchUsers')
);

$results = $userGroup->get_groups_by_user($user_id,
    [
        GROUP_USER_PERMISSION_ADMIN,
        GROUP_USER_PERMISSION_READER,
        GROUP_USER_PERMISSION_MODERATOR,
        GROUP_USER_PERMISSION_HRM,
    ]
);

$myGroups = [];
if (!empty($results)) {
    foreach ($results as $result) {
        $id = $result['id'];
        $result['description'] = Security::remove_XSS($result['description'], STUDENT, true);
        $result['name'] = Security::remove_XSS($result['name'], STUDENT, true);

        /*if ($result['count'] == 1) {
            $result['count'] = '1 '.get_lang('Member');
        } else {
            $result['count'] = $result['count'].' '.get_lang('Members');
        }*/
        $group_url = "group_view.php?id=$id";

        $link = Display::url(
            api_ucwords(cut($result['name'], 40, true)),
            $group_url
        );

        $result['name'] = $link;

        $picture = $userGroup->get_picture_group(
            $id,
            $result['picture'],
            null,
            GROUP_IMAGE_SIZE_BIG
        );

        $result['picture'] = '<img class="img-responsive" src="'.$picture['file'].'" />';
        $group_actions = '<div class="group-more"><a class="btn btn-default" href="groups.php?#tab_browse-2">'.
            get_lang('SeeMore').'</a></div>';
        $group_info = '<div class="description"><p>'.cut($result['description'], 120, true)."</p></div>";
        $myGroups[] = [
            'url' => Display::url(
                $result['picture'],
                $group_url
            ),
            'name' => $result['name'],
            'description' => $group_info.$group_actions,
        ];
    }
}


$social_group_block = '';
if (count($myGroups) > 0) {
    $social_group_block .= '<div class="list-group">';
    foreach ($myGroups as $group) {
        $social_group_block .= ' <li class="list-group-item">';
        $social_group_block .= $group['name'];
        $social_group_block .= '</li>';
    }
    $social_group_block .= '</div>';
}

$form = new FormValidator(
    'find_groups_form',
    'get',
    api_get_path(WEB_CODE_PATH).'social/search.php?search_type=2',
    null,
    null,
    'inline'
);
$form->addHidden('search_type', 2);

$form->addText(
    'q',
    get_lang('Search'),
    false,
    [
        'aria-label' => get_lang('SearchGroups'),
    ]
);
$form->addButtonSearch(get_lang('Search'));

$social_group_block .= $form->returnForm();

// My friends
$friend_html = SocialManager::listMyFriendsBlock(
    $user_id,
    ''
);

// Block Social Sessions
$social_session_block = null;
$user_info = api_get_user_info($user_id);
$sessionList = SessionManager::getSessionsFollowedByUser($user_id, $user_info['status']);

if (count($sessionList) > 0) {
    $social_session_block = $sessionList;
}

$social_group_block = Display::panelCollapse(
    get_lang('MyGroups'),
    $social_group_block,
    'sm-groups',
    null,
    'grups-acordion',
    'groups-collapse'
);

$wallSocialAddPost = SocialManager::getWallForm(api_get_self());
$socialAutoExtendLink = SocialManager::getAutoExtendLink($user_id, $countPost);

$form = new FormValidator(
    'find_friends_form',
    'get',
    api_get_path(WEB_CODE_PATH).'social/search.php?search_type=1',
    null,
    null,
    'inline'
);
$form->addHidden('search_type', 1);
$form->addText(
    'q',
    get_lang('Search'),
    false,
    [
        'aria-label' => get_lang('SearchUsers'),
    ]
);
$form->addButtonSearch(get_lang('Search'));

$tpl = new Template(get_lang('SocialNetwork'));
SocialManager::setSocialUserBlock($tpl, $user_id, 'home');
$tpl->assign('social_wall_block', $wallSocialAddPost);
$tpl->assign('social_post_wall_block', $posts);
$tpl->assign('social_menu_block', $social_menu_block);
$tpl->assign('social_auto_extend_link', $socialAutoExtendLink);
$tpl->assign('search_friends_form', $form->returnForm());
$tpl->assign('social_friend_block', $friend_html);
//$tpl->assign('session_list', $social_session_block);
$tpl->assign('social_search_block', $social_search_block);
$tpl->assign('social_skill_block', SocialManager::getSkillBlock($user_id));
$tpl->assign('social_group_block', $social_group_block);
$social_layout = $tpl->get_template('social/home.tpl');
$tpl->display($social_layout);
