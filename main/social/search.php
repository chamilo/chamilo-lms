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
$language_file = array('registration','admin','userInfo');
$cidReset = true;
require_once '../inc/global.inc.php';

api_block_anonymous_users();

if (api_get_setting('allow_social_tool') !='true') {
    api_not_allowed();
}

$this_section = SECTION_SOCIAL;
$tool_name 	  = get_lang('Search');
$interbreadcrumb[]= array ('url' =>'profile.php','name' => get_lang('SocialNetwork'));

$query_vars = array();
$query  = isset($_GET['q']) ? $_GET['q'] : null;

$social_left_content = SocialManager::show_social_menu('search');

$social_right_content = '<div class="span9">'.UserManager::get_search_form($query).'</div>';

$usergroup = new UserGroup();
//I'm searching something
if ($query !='') {
    //get users from tags
    $users  = UserManager::get_all_user_tags($_GET['q'], 0, 0, 5);
    $groups = $usergroup->get_all_group_tags($_GET['q']);

    if (empty($users) && empty($groups)) {
        $social_right_content .= get_lang('SorryNoResults');
    }

    $results = '<div id="online_grid_container"><div class="col-md-12">';

    if (is_array($users) && count($users)> 0) {
        $results .=  Display::page_subheader(get_lang('Users'));
        $results .= '<ul class="thumbnails">';
        foreach ($users as $user) {
            $user_info = api_get_user_info($user['user_id'], true);
            $url = $user_info['profile_url'];

            if (empty($user['picture_uri'])) {
                $picture['file'] = api_get_path(WEB_IMG_PATH).'unknown_180_100.jpg';
                $img = '<img src="'.$picture['file'].'">';
            } else {
                $picture = UserManager::get_picture_user($user['user_id'], $user['picture_uri'], 80, USER_IMAGE_SIZE_ORIGINAL);
                $img = '<img src="'.$picture['file'].'">';
            }
            if ($user_info['user_is_online']) {
                $status_icon = Display::span('', array('class' => 'online_user_in_text'));
            } else {
                $status_icon = Display::span('', array('class' => 'offline_user_in_text'));
            }
            $user['tag'] = isset($user['tag']) ? $user['tag'] : null;
            $user_info['complete_name'] = Display::url($status_icon.$user_info['complete_name'], $url).'<br />'.$user['tag'];
            $results .= '<li class="col-md-3"><div class="thumbnail">'.$img.'<div class="caption">'.$user_info['complete_name'].$user['tag'].'</div</div></li>';
        }
        $results .='</ul></div></div>';
        $social_right_content .=  $results;
    }

    $grid_groups = array();
    if (is_array($groups) && count($groups)>0) {
        $social_right_content .= '<div class="col-md-12">';
        $social_right_content .=  Display::page_subheader(get_lang('Groups'));
        foreach ($groups as $group) {
            $group['name'] = Security::remove_XSS($group['name'], STUDENT, true);
            $$group['description'] = Security::remove_XSS($group['description'], STUDENT, true);
            $id = $group['id'];
            $url_open  = '<a href="groups.php?id='.$id.'" >';
            $url_close = '</a>';
            $name = Text::cut($group['name'], 25, true);
            $count_users_group = count($usergroup->get_all_users_by_group($id));
            if ($count_users_group == 1) {
                $count_users_group = $count_users_group.' '.get_lang('Member');
            } else {
                $count_users_group = $count_users_group.' '.get_lang('Members');
            }
            $picture = $usergroup->get_picture_group($group['id'], $group['picture'], 80);
            $tags = $usergroup->get_group_tags($group['id']);
            $group['picture'] = '<img class="social-groups-image" src="'.$picture['file'].'" hspace="4" height="50" border="2" align="left" width="50" />';

            $item_0 = Display::div($group['picture'], array('class'=>'box_description_group_image'));
            $members = Display::span($count_users_group, array('class'=>'box_description_group_member'));
            $item_1  = Display::div(Display::tag('h3', $url_open.$name.$url_close).$members, array('class'=>'box_description_group_title'));

            $item_2 = '';
            $item_3 = '';
            if ($group['description'] != '') {
                $item_3 = '<div class="box_description_group_content" >'.Text::cut($group['description'],100,true).'</div>';
            } else {
                $item_2 = '<div class="box_description_group_title" ><span class="social-groups-text2"></span></div>';
                $item_3 = '<div class="box_description_group_content" ></div>';
            }
            $item_4 = '<div class="box_description_group_tags" >'.$tags.'</div>';
            $item_5 = '<div class="box_description_group_actions" >'.$url_open.get_lang('SeeMore').$url_close.'</div>';
            $grid_item_2 = $item_0.$item_1.$item_2.$item_3.$item_4.$item_5;
            $grid_groups[]= array('',$grid_item_2);
        }
    }
    $visibility = array(true,true,true,true,true);
    $social_right_content .= Display::return_sortable_grid('mygroups', array(), $grid_groups, array('hide_navigation'=>true, 'per_page' => 5), $query_vars, false, $visibility);
}

$app['title'] = $tool_name;
$tpl = $app['template'];
$tpl->assign('content', $social_right_content);
