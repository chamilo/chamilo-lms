<?php
/* For licensing terms, see /license.txt */

/**
 * @package chamilo.social
 * @author Julio Montoya <gugli100@gmail.com>
 */

$cidReset = true;
$language_file = array('userInfo');
require_once '../inc/global.inc.php';

api_block_anonymous_users();

if (api_get_setting('allow_social_tool') !='true') {
    api_not_allowed();
}

$this_section = SECTION_SOCIAL;
$allowed_views = array('mygroups','newest','pop');
$content = null;

if (isset($_GET['view']) && in_array($_GET['view'], $allowed_views)) {
    if ($_GET['view'] == 'mygroups') {
        $interbreadcrumb[]= array ('url' =>'groups.php','name' => get_lang('Groups'));
        $interbreadcrumb[]= array ('url' =>'#','name' => get_lang('MyGroups'));
    } else if ( $_GET['view'] == 'newest') {
        $interbreadcrumb[]= array ('url' =>'groups.php','name' => get_lang('Groups'));
        $interbreadcrumb[]= array ('url' =>'#','name' => get_lang('Newest'));
    } else  {
        $interbreadcrumb[]= array ('url' =>'groups.php','name' => get_lang('Groups'));
        $interbreadcrumb[]= array ('url' =>'#','name' => get_lang('Popular'));
    }
} else {
    $interbreadcrumb[]= array ('url' =>'groups.php','name' => get_lang('Groups'));
    if (!isset($_GET['id'])) {
        $interbreadcrumb[]= array ('url' =>'#','name' => get_lang('GroupList'));
    }
}

// getting group information
$relation_group_title = '';
$my_group_role = 0;

$usergroup = new UserGroup();

$create_thread_link = '';

$show_menu = 'browse_groups';
if (isset($_GET['view']) && $_GET['view'] == 'mygroups') {
    $show_menu = $_GET['view'];
}

$social_right_content = null;

// My groups
$results = $usergroup->get_groups_by_user(api_get_user_id(), 0);

$grid_my_groups = array();
$my_group_list = array();
if (is_array($results) && count($results) > 0) {
    foreach ($results as $result) {
        $id = $result['id'];
        $result['name'] = Security::remove_XSS($result['name'], STUDENT, true);
        $result['description'] = Security::remove_XSS($result['description'], STUDENT, true);
        $my_group_list[] = $id;
        $url_open  = '<a href="group_view.php?id='.$id.'">';
        $url_close = '</a>';

        $name = cut($result['name'], GROUP_TITLE_LENGTH, true);
        if ($result['relation_type'] == GROUP_USER_PERMISSION_ADMIN) {
            $name .= ' '.Display::return_icon('social_group_admin.png', get_lang('Admin'), array('style'=>'vertical-align:middle'));
        } elseif ($result['relation_type'] == GROUP_USER_PERMISSION_MODERATOR) {
            $name .= ' '.Display::return_icon('social_group_moderator.png', get_lang('Moderator'), array('style'=>'vertical-align:middle'));
        }
        $count_users_group = count($usergroup->get_users_by_group($id, false, array(GROUP_USER_PERMISSION_ADMIN, GROUP_USER_PERMISSION_READER, GROUP_USER_PERMISSION_MODERATOR), 0 , 1000));
        if ($count_users_group == 1 ) {
            $count_users_group = $count_users_group.' '.get_lang('Member');
        } else {
            $count_users_group = $count_users_group.' '.get_lang('Members');
        }

        $picture = $usergroup->get_picture_group($result['id'], $result['picture'],80);
        $result['picture'] = '<img class="social-groups-image" src="'.$picture['file'].'" />';
        $item_0  = Display::div($result['picture'], array('class'=>'box_description_group_image'));
        $members = Display::span($count_users_group, array('class'=>'box_description_group_member'));
        $item_1  = Display::div(Display::tag('h4', $url_open.$name.$url_close).$members, array('class'=>'box_description_group_title'));

        $item_2 = '';
        $item_3 = '';
        if ($result['description'] != '') {
            $item_3 = '<div class="box_description_group_content" >'.cut($result['description'],100,true).'</div>';
        } else {
            $item_2 = '<div class="box_description_group_title" ><span class="social-groups-text2"></span></div>';
            $item_3 = '<div class="box_description_group_content" ></div>';
        }
        $grid_item_2 = $item_0.$item_1.$item_2.$item_3;
        $grid_my_groups[]= array($grid_item_2);
    }
}

// Newest groups
$results = $usergroup->get_groups_by_age(4,false);

$grid_newest_groups = array();
foreach ($results as $result) {
    $result['name'] = Security::remove_XSS($result['name'], STUDENT, true);
    $result['description'] = Security::remove_XSS($result['description'], STUDENT, true);
    $id = $result['id'];
    $url_open  = '<a href="group_view.php?id='.$id.'">';
    $url_close = '</a>';
    $count_users_group = count($usergroup->get_users_by_group($id, false, array(GROUP_USER_PERMISSION_ADMIN, GROUP_USER_PERMISSION_READER, GROUP_USER_PERMISSION_MODERATOR), 0 , 1000));
    if ($count_users_group == 1 ) {
        $count_users_group = $count_users_group.' '.get_lang('Member');
    } else {
        $count_users_group = $count_users_group.' '.get_lang('Members');
    }

    $name = cut($result['name'],GROUP_TITLE_LENGTH,true);
    $picture = $usergroup->get_picture_group($result['id'], $result['picture'],80);
    $result['picture'] = '<img class="social-groups-image" src="'.$picture['file'].'" />';

    $item_0 = Display::div($result['picture'], array('class'=>'box_description_group_image'));
    $members = Display::span($count_users_group, array('class'=>'box_description_group_member'));
    $item_1  = Display::div(Display::tag('h4', $url_open.$name.$url_close).$members, array('class'=>'box_description_group_title'));

    if ($result['description'] != '') {
        $item_3 = '<div class="box_description_group_content" >'.cut($result['description'],100,true).'</div>';
    } else {
        $item_2 = '<div class="box_description_group_title" ><span class="social-groups-text2"></span></div>';
        $item_3 = '<div class="box_description_group_content" ></div>';
    }
    //Avoiding my groups
    $join_url = '';
    if (!in_array($id,$my_group_list)) {
        $join_url = '<a class="btn" href="group_view.php?id='.$id.'&action=join&u='.api_get_user_id().'">'.get_lang('JoinGroup').'</a> ';
    }

    $item_4 = '<div class="box_description_group_actions" >'.$join_url.'</div>';
    $grid_item_2 = $item_0.$item_1.$item_2.$item_3.$item_4;

    $grid_newest_groups[]= array($grid_item_2);
}

// Pop groups
$results = $usergroup->get_groups_by_popularity(4,false);
$grid_pop_groups = array();

if (is_array($results) && count($results) > 0) {
    foreach ($results as $result) {
        $result['name'] = Security::remove_XSS($result['name'], STUDENT, true);
        $result['description'] = Security::remove_XSS($result['description'], STUDENT, true);
        $id = $result['id'];
        $url_open  = '<a href="group_view.php?id='.$id.'">';
        $url_close = '</a>';

        $count_users_group = count($usergroup->get_users_by_group($id, false, array(GROUP_USER_PERMISSION_ADMIN, GROUP_USER_PERMISSION_READER, GROUP_USER_PERMISSION_MODERATOR), 0 , 1000));
        if ($count_users_group == 1 ) {
            $count_users_group = $count_users_group.' '.get_lang('Member');
        } else {
            $count_users_group = $count_users_group.' '.get_lang('Members');
        }

        $name = cut($result['name'],GROUP_TITLE_LENGTH,true);
        $picture = $usergroup->get_picture_group($result['id'], $result['picture'],80);
        $result['picture'] = '<img class="social-groups-image" src="'.$picture['file'].'" />';

        $item_0 = Display::div($result['picture'], array('class'=>'box_description_group_image'));
        $members = Display::span($count_users_group, array('class'=>'box_description_group_member'));
        $item_1  = Display::div(Display::tag('h4', $url_open.$name.$url_close).$members, array('class'=>'box_description_group_title'));

        if ($result['description'] != '') {
            $item_3 = '<div class="box_description_group_content" >'.cut($result['description'],100,true).'</div>';
        } else {
            $item_2 = '<div class="box_description_group_title" ><span class="social-groups-text2"></span></div>';
            $item_3 = '<div class="box_description_group_content" ></div>';
        }

        $join_url = '';
        if (!in_array($id,$my_group_list)) {
            $join_url = '<a class="btn" href="group_view.php?id='.$id.'&action=join&u='.api_get_user_id().'">'.get_lang('JoinGroup').'</a> ';
        }
        $item_4 = '<div class="box_description_group_actions" >'.$join_url.'</div>';

        $grid_item_2 = $item_0.$item_1.$item_2.$item_3.$item_4;
        $grid_pop_groups[]= array($grid_item_2);
    }
}

// Display groups (newest, mygroups, pop)
$query_vars = array();

$newest_content = $popular_content = $my_group_content = null;

if (isset($_GET['view']) && in_array($_GET['view'], $allowed_views)) {
    $view_group = $_GET['view'];
    switch ($view_group) {
        case 'mygroups':
            if (count($grid_my_groups) > 0) {
                $my_group_content = Display::return_sortable_grid('mygroups', array(), $grid_my_groups, array('hide_navigation'=>true, 'per_page' => 2), $query_vars, false, array(true, true, true,false));
            }
            if (api_get_setting('allow_students_to_create_groups_in_social') == 'true') {
                $create_group_item =  '<a class="btn btn-default" href="'.api_get_path(WEB_PATH).'main/social/group_add.php">'.
                    get_lang('CreateASocialGroup').'</a>';
            } else {
                if (api_is_allowed_to_edit(null,true)) {
                    $create_group_item =  '<a class="btn btn-default" href="'.api_get_path(WEB_PATH).'main/social/group_add.php">'.
                        get_lang('CreateASocialGroup').'</a>';
                }
            }
            break;
        case 'newest':
            if (count($grid_newest_groups) > 0) {
                $newest_content = Display::return_sortable_grid('newest', array(), $grid_newest_groups, array('hide_navigation'=>true, 'per_page' => 100), $query_vars, false, array(true, true, true,false));
            }
            break;
        default:
            if (count($grid_pop_groups) > 0) {
                $popular_content = Display::return_sortable_grid('popular', array(), $grid_pop_groups, array('hide_navigation'=>true, 'per_page' => 100), $query_vars, false, array(true, true, true,true,true));
            }
            break;
    }
} else {
    $my_group_content = null;
    if (count($grid_my_groups) > 0) {
        $my_group_content = Display::return_sortable_grid('mygroups', array(), $grid_my_groups, array('hide_navigation'=>true, 'per_page' => 2), $query_vars, false, array(true, true, true,false));
    }
    if (api_get_setting('allow_students_to_create_groups_in_social') == 'true') {
        $create_group_item =  '<a class="btn btn-default" href="'.api_get_path(WEB_PATH).'main/social/group_add.php">'.
            get_lang('CreateASocialGroup').'</a>';
    } else {
        if (api_is_allowed_to_edit(null,true)) {
            $create_group_item =  '<a class="btn btn-default" href="'.api_get_path(WEB_PATH).'main/social/group_add.php">'.get_lang('CreateASocialGroup').'</a>';
        }
    }
    if (count($grid_newest_groups) > 0) {
        $newest_content = Display::return_sortable_grid('mygroups', array(), $grid_newest_groups, array('hide_navigation'=>true, 'per_page' => 100), $query_vars, false, array(true, true, true,false));
    }
    if (count($grid_pop_groups) > 0) {
        $popular_content = Display::return_sortable_grid('mygroups', array(), $grid_pop_groups, array('hide_navigation'=>true, 'per_page' => 100), $query_vars, false, array(true, true, true,true,true));
    }
}

if (!empty($create_group_item)) {
    $social_right_content .=  Display::page_subheader($create_group_item);
}
$headers = array(get_lang('Newest'), get_lang('Popular'), get_lang('MyGroups'));
$social_right_content .= Display::tabs($headers, array($newest_content, $popular_content, $my_group_content),'tab_browse');

$show_message = null;
if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'show_message' && isset($_REQUEST['msg']) && $_REQUEST['msg'] == 'topic_deleted') {
    $show_message = Display::return_message(get_lang('Deleted'), 'success');
}

$tpl = new Template(null);

// Block Social Avatar
SocialManager::setSocialUserBlock($tpl, $user_id, $show_menu);
$show_menu = 'browse_groups';
if (isset($_GET['view']) && $_GET['view'] == 'mygroups') {
    $show_menu = $_GET['view'];
}

$social_menu_block = SocialManager::show_social_menu($show_menu);
$templateName = 'social/groups.tpl';

$tpl->setHelp('Groups');
$tpl->assign('message', $show_message);
$tpl->assign('social_menu_block', $social_menu_block);
$tpl->assign('social_right_content', $social_right_content);
$social_layout = $tpl->get_template($templateName);
$tpl->display($social_layout);
