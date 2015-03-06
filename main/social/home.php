<?php
/* For licensing terms, see /chamilo_license.txt */
/**
 * @package chamilo.social
 * @author Julio Montoya <gugli100@gmail.com>
 * @autor Alex Aragon <alex.aragon@beeznest.com> CSS Design and Template
 */
/**
 * Initialization
 */
$language_file = array('userInfo');
$cidReset = true;

require_once '../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH) . 'skill.lib.php';

$user_id = api_get_user_id();
$show_full_profile = true;
//social tab
$this_section = SECTION_SOCIAL;
unset($_SESSION['this_section']); //for hmtl editor repository

api_block_anonymous_users();

if (api_get_setting('allow_social_tool') != 'true') {
    $url = api_get_path(WEB_CODE_PATH) . 'auth/profile.php';
    header('Location: ' . $url);
    exit;
    api_not_allowed();
}

//fast upload image
if (api_get_setting('profile', 'picture') == 'true') {
    $form = new FormValidator('profile', 'post', 'home.php', null, array());

    //	PICTURE
    $form->addElement('file', 'picture', get_lang('AddImage'));
    $form->add_progress_bar();
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
        get_lang('OnlyImagesAllowed') . ' (' . implode(
            ',',
            $allowed_picture_types
        ) . ')',
        'filetype',
        $allowed_picture_types
    );
    $form->addElement(
        'style_submit_button',
        'apply_change',
        get_lang('SaveSettings'),
        'class="save"'
    );

    if ($form->validate()) {
        $user_data = $form->getSubmitValues();
        // upload picture if a new one is provided
        if ($_FILES['picture']['size']) {
            if ($new_picture = UserManager::update_user_picture(
                api_get_user_id(),
                $_FILES['picture']['name'],
                $_FILES['picture']['tmp_name']
            )
            ) {
                $table_user = Database :: get_main_table(TABLE_MAIN_USER);

                $sql = "UPDATE $table_user
                    SET picture_uri = '$new_picture' WHERE user_id =  " . api_get_user_id();

                $result = Database::query($sql);
            }
        }
    }
}

$user_info = UserManager :: get_user_info_by_id(api_get_user_id());

$social_avatar_block = '<div class="panel panel-info social-avatar">';
$social_avatar_block .= SocialManager::show_social_avatar_block('home');
$social_avatar_block .= '<div class="lastname">'.$user_info['lastname'].'</div>';
$social_avatar_block .= '<div class="firstname">'.$user_info['firstname'].'</div>';
/* $social_avatar_block .= '<div class="username">'.Display::return_icon('user.png','','',ICON_SIZE_TINY).$user_info['username'].'</div>'; */
$social_avatar_block .= '<div class="email">'.Display::return_icon('instant_message.png').'&nbsp;' .$user_info['email'].'</div>';
$chat_status = $user_info['extra'];
 if(!empty($chat_status['user_chat_status'])){
     $social_avatar_block.= '<div class="status">'.Display::return_icon('online.png').get_lang('Chat')." (".get_lang('Online').')</div>';
 }else{
     $social_avatar_block.= '<div class="status">'.Display::return_icon('offline.png').get_lang('Chat')." (".get_lang('Offline').')</div>';
 }

    $editProfileUrl = api_get_path(WEB_CODE_PATH) . 'auth/profile.php';

    if (api_get_setting('sso_authentication') === 'true') {
        $subSSOClass = api_get_setting('sso_authentication_subclass');
        $objSSO = null;

        if (!empty($subSSOClass)) {
            require_once api_get_path(SYS_CODE_PATH) . 'auth/sso/sso.' . $subSSOClass . '.class.php';

            $subSSOClass = 'sso' . $subSSOClass;
            $objSSO = new $subSSOClass();
        } else {
            $objSSO = new sso();
        }

        $editProfileUrl = $objSSO->generateProfileEditingURL();
    }
$social_avatar_block .= '<div class="edit-profile">
                            <a class="btn" href="' . $editProfileUrl . '">' . get_lang('EditProfile') . '</a>
                         </div>';
$social_avatar_block .= '</div>';

//Block Menu
$social_menu_block = SocialManager::show_social_menu('home');

//Search box
$social_search_block = '<div class="panel panel-info social-search">';
$social_search_block .= '<div class="panel-heading">'.get_lang("SearchUsers").'</div>';
$social_search_block .= '<div class="panel-body">';
$social_search_block.= UserManager::get_search_form('');
$social_search_block.= '</div>';
$social_search_block.= '</div>';

//BLock Social Skill
$social_skill_block = '';

if (api_get_setting('allow_skills_tool') == 'true') {
    $skill = new Skill();

    $ranking = $skill->get_user_skill_ranking(api_get_user_id());
    $skills = $skill->get_user_skills(api_get_user_id(), true);

    $social_skill_block = '<div class="panel panel-info social-skill">';
    $social_skill_block .= '<div class="panel-heading">' . get_lang('Skills');
    $social_skill_block .= '<div class="btn-group pull-right"> <a class="btn dropdown-toggle" data-toggle="dropdown" href="#">
                            <span class="caret"></span></a>
                             <ul class="dropdown-menu">';
    if (api_is_student() || api_is_student_boss() || api_is_drh()) {
        $social_skill_block .= '<li>' . Display::url(
            get_lang('SkillsReport'),
            api_get_path(WEB_CODE_PATH) . 'social/my_skills_report.php'
            ) . '</li>';
    }

    $social_skill_block .= '<li>' . Display::url(
        get_lang('SkillsWheel'),
        api_get_path(WEB_CODE_PATH) . 'social/skills_wheel.php'
        ) . '</li>';

    $social_skill_block .= '<li>' . Display::url(
        sprintf(get_lang('YourSkillRankingX'), $ranking),
        api_get_path(WEB_CODE_PATH) . 'social/skills_ranking.php'
    ) . '</li>';

    $social_skill_block .= '</ul></div></div>';

    $lis = '';
    if (!empty($skills)) {
        foreach ($skills as $skill) {
            $badgeImage = null;

            if (!empty($skill['icon'])) {
                $badgeImage = Display::img(
                    api_get_path(WEB_DATA_PATH) . $skill['icon'],
                    $skill['name']
                );
            } else {
                $badgeImage = Display::return_icon(
                    'award_red.png',
                    $skill['name'],
                    array('title' => $skill['name'])
                );
            }

            $lis .= Display::tag(
                'li',
                $badgeImage .
                '<div class="badges-name">' . $skill['name'] . '</div>'
            );
        }
        $social_skill_block .= '<div class="panel-body">';
        $social_skill_block .= Display::tag('ul', $lis, array('class' => 'list-badges'));
        $social_skill_block .= '</div>';
    }else{

        $social_skill_block .= '<div class="panel-body">';
        $social_skill_block .= '<p>'. get_lang("SinCompetencias");
        $social_skill_block .=  Display::url(get_lang('SkillsWheel'),api_get_path(WEB_CODE_PATH) . 'social/skills_wheel.php').'</p>';
        $social_skill_block .= '</div>';
    }
    $social_skill_block.='</div>';
}



//Group box by age
$social_group_block = '<div class="panel panel-info social-group">';
$social_group_block .= '<div class="panel-heading">'.get_lang('Group').'</div>';
$social_group_block .= '<div class="panel-body">';

$results = GroupPortalManager::get_groups_by_age(1, false);

$groups_newest = array();

if (!empty($results)) {
    foreach ($results as $result) {
        $id = $result['id'];
        $result['description'] = Security::remove_XSS( $result['description'], STUDENT, true );
        $result['name'] = Security::remove_XSS($result['name'], STUDENT, true);

        if ($result['count'] == 1) {
            $result['count'] = '1 ' . get_lang('Member');
        } else {
            $result['count'] = $result['count'] . ' ' . get_lang('Members');
        }

        $group_url = "groups.php?id=$id";

        $result['name'] = '<div class="group-name">'.Display::url(
                          api_ucwords(cut($result['name'], 40, true)), $group_url)
                          .'</div><div class="count-username">'.Display::return_icon('user.png','','',ICON_SIZE_TINY).$result['count'].'</div>';

        $picture = GroupPortalManager::get_picture_group(
            $id,
            $result['picture_uri'],
            80
        );

        $result['picture_uri'] = '<img class="group-image" src="' . $picture['file'] . '" />';
        $group_actions = '<div class="group-more"><a href="groups.php?#tab_browse-2">' . get_lang('SeeMore') . '</a></div>';
        $group_info= '<div class="description"><p>' . cut($result['description'], 120, true) . "</p></div>";
        $groups_newest[] = array(
            Display::url(
                $result['picture_uri'],
                $group_url
            ),$result['name'],$group_info.$group_actions
        );
    }
}
//Top popular
$results = GroupPortalManager::get_groups_by_popularity(1, false);

$groups_pop = array();
foreach ($results as $result) {
    $result['description'] = Security::remove_XSS(
        $result['description'],
        STUDENT,
        true
    );
    $result['name'] = Security::remove_XSS($result['name'], STUDENT, true);
    $id = $result['id'];
    $group_url = "groups.php?id=$id";

    if ($result['count'] == 1) {
        $result['count'] = '1 ' . get_lang('Member');
    } else {
        $result['count'] = $result['count'] . ' ' . get_lang('Members');
    }
    $result['name'] = '<div class="group-name">'.Display::url(
            api_ucwords(cut($result['name'], 40, true)),$group_url)
        .'</div><div class="count-username">'.Display::return_icon('user.png','','',ICON_SIZE_TINY).$result['count'].'</div>';

    $picture = GroupPortalManager::get_picture_group(
        $id,
        $result['picture_uri'],
        80
    );
    $result['picture_uri'] = '<img class="group-image" src="' . $picture['file'] . '" />';
    $group_actions = '<div class="group-more" ><a href="groups.php?#tab_browse-3">' . get_lang('SeeMore') . '</a></div>';
    $group_info= '<div class="description"><p>' . cut($result['description'], 120, true) . "</p></div>";
    $groups_pop[] = array(
        Display::url($result['picture_uri'], $group_url),
        $result['name'],$group_info. $group_actions
    );
}

$list=count($groups_newest);

if ($list > 0) {
    $social_group_block .= '<div class="list-group-newest">';
    $social_group_block .= '<div class="group-title">' . get_lang('Newest') . '</div>';
    for($i = 0;$i < $list; $i++){
        $social_group_block.='<div class="items">';
        $social_group_block.='<div class="group-image">' . $groups_newest[$i][0] . '</div>';
        $social_group_block.='<div class="group-info">' . $groups_newest[$i][1];
        $social_group_block.= $groups_newest[$i][2] . '</div>';
        $social_group_block.="</div>";
    }
    $social_group_block.= "</div>";
}
$list=count($groups_pop);
if ($list > 0) {
    $social_group_block .= '<div class="list-group-newest">';
    $social_group_block .= '<div class="group-title">' . get_lang('Popular') . '</div>';

    for($i = 0;$i < $list; $i++){
        $social_group_block.='<div class="items">';
        $social_group_block.='<div class="group-image">' . $groups_pop[$i][0] . '</div>';
        $social_group_block.='<div class="group-info">' . $groups_pop[$i][1];
        $social_group_block.= $groups_pop[$i][2] . '</div>';
        $social_group_block.="</div>";
    }

    /*$social_group_block .= Display::return_sortable_grid(
        'home_group',
        array(),
        $groups_pop,
        array('hide_navigation' => true, 'per_page' => 100),
        array(),
        false,
        array(true, true, true, true, true)
    );*/
}
$social_group_block .= '</div>';

$tpl = new Template(get_lang('SocialNetwork'));
$tpl->assign('social_avatar_block', $social_avatar_block);
$tpl->assign('social_menu_block', $social_menu_block);
$tpl->assign('social_search_block', $social_search_block);
$tpl->assign('social_skill_block', $social_skill_block);
$tpl->assign('social_group_block', $social_group_block);
$social_layout = $tpl->get_template('social/home.tpl');
$tpl->display($social_layout);
