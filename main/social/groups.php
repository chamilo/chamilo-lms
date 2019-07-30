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
    api_not_allowed();
}
$join_url = '';

$this_section = SECTION_SOCIAL;
$allowed_views = ['mygroups', 'newest', 'pop'];
$content = null;

if (isset($_GET['view']) && in_array($_GET['view'], $allowed_views)) {
    if ($_GET['view'] === 'mygroups') {
        $interbreadcrumb[] = ['url' => 'groups.php', 'name' => get_lang('Groups')];
        $interbreadcrumb[] = ['url' => '#', 'name' => get_lang('MyGroups')];
    } elseif ($_GET['view'] === 'newest') {
        $interbreadcrumb[] = ['url' => 'groups.php', 'name' => get_lang('Groups')];
        $interbreadcrumb[] = ['url' => '#', 'name' => get_lang('Newest')];
    } else {
        $interbreadcrumb[] = ['url' => 'groups.php', 'name' => get_lang('Groups')];
        $interbreadcrumb[] = ['url' => '#', 'name' => get_lang('Popular')];
    }
} else {
    $interbreadcrumb[] = ['url' => 'groups.php', 'name' => get_lang('Groups')];
    if (!isset($_GET['id'])) {
        $interbreadcrumb[] = ['url' => '#', 'name' => get_lang('GroupList')];
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

$grid_my_groups = [];
$my_group_list = [];
if (is_array($results) && count($results) > 0) {
    foreach ($results as $result) {
        $id = $result['id'];
        $result['name'] = Security::remove_XSS($result['name'], STUDENT, true);
        $result['description'] = Security::remove_XSS($result['description'], STUDENT, true);
        $my_group_list[] = $id;
        $name = cut($result['name'], GROUP_TITLE_LENGTH, true);

        if ($result['relation_type'] == GROUP_USER_PERMISSION_ADMIN) {
            $name .= ' '.Display::return_icon(
                'social_group_admin.png',
                get_lang('Admin'),
                ['style' => 'vertical-align:middle']
            );
        } elseif ($result['relation_type'] == GROUP_USER_PERMISSION_MODERATOR) {
            $name .= ' '.Display::return_icon(
                'social_group_moderator.png',
                get_lang('Moderator'),
                ['style' => 'vertical-align:middle']
            );
        }
        $url = '<a href="group_view.php?id='.$id.'">'.$name.'</a>';

        $count_users_group = count(
            $usergroup->get_users_by_group(
                $id,
                false,
                [
                    GROUP_USER_PERMISSION_ADMIN,
                    GROUP_USER_PERMISSION_READER,
                    GROUP_USER_PERMISSION_MODERATOR,
                ],
                0,
                1000
            )
        );
        if ($count_users_group == 1) {
            $count_users_group = $count_users_group.' '.get_lang('Member');
        } else {
            $count_users_group = $count_users_group.' '.get_lang('Members');
        }

        $picture = $usergroup->get_picture_group(
            $result['id'],
            $result['picture'],
            80
        );
        $result['picture'] = '<img class="social-groups-image" src="'.$picture['file'].'" />';

        $members = Display::returnFontAwesomeIcon('user').$count_users_group;
        $html = '<div class="row">';
        $html .= '<div class="col-md-2">';
        $html .= $result['picture'];
        $html .= '</div>';
        $html .= '<div class="col-md-10">';
        $html .= '<div class="title-groups">';
        $html .= Display::tag('h5', $url);
        $html .= '</div>';
        $html .= '<div class="members-groups">'.$members.'</div>';
        if ($result['description'] != '') {
            $html .= '<div class="description-groups">'.cut($result['description'], 100, true).'</div>';
        } else {
            $html .= '';
        }
        $html .= '</div>';
        $html .= '</div>';

        $grid_item_2 = $html;
        $grid_my_groups[] = [$grid_item_2];
    }
}

// Newest groups
$results = $usergroup->get_groups_by_age(4, false);

$grid_newest_groups = [];
foreach ($results as $result) {
    $result['name'] = Security::remove_XSS($result['name'], STUDENT, true);
    $result['description'] = Security::remove_XSS($result['description'], STUDENT, true);
    $id = $result['id'];
    $name = cut($result['name'], GROUP_TITLE_LENGTH, true);

    $count_users_group = count(
        $usergroup->get_users_by_group(
            $id,
            false,
            [GROUP_USER_PERMISSION_ADMIN, GROUP_USER_PERMISSION_READER, GROUP_USER_PERMISSION_MODERATOR],
            0,
            1000
        )
    );
    if ($count_users_group == 1) {
        $count_users_group = $count_users_group.' '.get_lang('Member');
    } else {
        $count_users_group = $count_users_group.' '.get_lang('Members');
    }

    $url = '<a href="group_view.php?id='.$id.'">'.$name.'</a>';

    $picture = $usergroup->get_picture_group($result['id'], $result['picture'], 80);
    $result['picture'] = '<img class="social-groups-image" src="'.$picture['file'].'" />';
    $members = Display::returnFontAwesomeIcon('user').$count_users_group;

    $html = '<div class="row">';
    $html .= '<div class="col-md-2">';
    $html .= $result['picture'];
    $html .= '</div>';
    $html .= '<div class="col-md-10">';
    $html .= '<div class="title-groups">';
    $html .= Display::tag('h5', $url);
    $html .= '</div>';
    $html .= '<div class="members-groups">'.$members.'</div>';
    if ($result['description'] != '') {
        $html .= '<div class="description-groups">'.cut($result['description'], 100, true).'</div>';
    }
    // Avoiding my groups
    if (!in_array($id, $my_group_list)) {
        $html .= '<a class="btn btn-primary" href="group_view.php?id='.$id.'&action=join&u='.api_get_user_id().'">'.
            get_lang('JoinGroup').'</a> ';
    }

    $html .= '<div class="group-actions" >'.$join_url.'</div>';
    $html .= '</div>';
    $html .= '</div>';
    $grid_item_2 = $html;
    $grid_newest_groups[] = [$grid_item_2];
}

// Pop groups
$results = $usergroup->get_groups_by_popularity(4, false);
$grid_pop_groups = [];

if (is_array($results) && count($results) > 0) {
    foreach ($results as $result) {
        $result['name'] = Security::remove_XSS($result['name'], STUDENT, true);
        $result['description'] = Security::remove_XSS($result['description'], STUDENT, true);
        $id = $result['id'];
        $name = cut($result['name'], GROUP_TITLE_LENGTH, true);

        $count_users_group = count(
            $usergroup->get_users_by_group(
                $id,
                false,
                [
                    GROUP_USER_PERMISSION_ADMIN,
                    GROUP_USER_PERMISSION_READER,
                    GROUP_USER_PERMISSION_MODERATOR,
                ],
                0,
                1000
            )
        );
        if ($count_users_group == 1) {
            $count_users_group = $count_users_group.' '.get_lang('Member');
        } else {
            $count_users_group = $count_users_group.' '.get_lang('Members');
        }

        $url = '<a href="group_view.php?id='.$id.'">'.$name.'</a>';

        $picture = $usergroup->get_picture_group($result['id'], $result['picture'], 80);
        $result['picture'] = '<img class="social-groups-image" src="'.$picture['file'].'" />';

        $html = '<div class="row">';
        $html .= '<div class="col-md-2">';
        $html .= $result['picture'];
        $html .= '</div>';
        $html .= '<div class="col-md-10">';
        $html .= '<div class="title-groups">';
        $html .= Display::tag('h5', $url);
        $html .= '</div>';
        $html .= '<div class="members-groups">'.$members.'</div>';
        if ($result['description'] != '') {
            $html .= '<div class="description-groups">'.cut($result['description'], 100, true).'</div>';
        } else {
            $html .= '';
        }
        // Avoiding my groups
        if (!in_array($id, $my_group_list)) {
            $html .= '<a class="btn btn-primary" href="group_view.php?id='.$id.'&action=join&u='.api_get_user_id().'">'.
                get_lang('JoinGroup').'</a> ';
        }

        $html .= '<div class="group-actions" >'.$join_url.'</div>';
        $html .= '</div>';
        $html .= '</div>';

        $grid_item_2 = $html;
        $grid_pop_groups[] = [$grid_item_2];
    }
}

// Display groups (newest, mygroups, pop)
$query_vars = [];
$newest_content = $popular_content = $my_group_content = null;
if (isset($_GET['view']) && in_array($_GET['view'], $allowed_views)) {
    $view_group = $_GET['view'];
    switch ($view_group) {
        case 'mygroups':
            if (count($grid_my_groups) > 0) {
                $my_group_content = Display::return_sortable_grid(
                    'mygroups',
                    [],
                    $grid_my_groups,
                    ['hide_navigation' => true, 'per_page' => 2],
                    $query_vars,
                    false,
                    [true, true, true, false]
                );
            }
            if (api_get_setting('allow_students_to_create_groups_in_social') == 'true') {
                $create_group_item =
                    '<a class="btn btn-default" href="'.api_get_path(WEB_PATH).'main/social/group_add.php">'.
                    get_lang('CreateASocialGroup').'</a>';
            } else {
                if (api_is_allowed_to_edit(null, true)) {
                    $create_group_item =
                        '<a class="btn btn-default" href="'.api_get_path(WEB_PATH).'main/social/group_add.php">'.
                        get_lang('CreateASocialGroup').'</a>';
                }
            }
            break;
        case 'newest':
            if (count($grid_newest_groups) > 0) {
                $newest_content = Display::return_sortable_grid(
                    'newest',
                    [],
                    $grid_newest_groups,
                    ['hide_navigation' => true, 'per_page' => 100],
                    $query_vars,
                    false,
                    [true, true, true, false]
                );
            }
            break;
        default:
            if (count($grid_pop_groups) > 0) {
                $popular_content = Display::return_sortable_grid(
                    'popular',
                    [],
                    $grid_pop_groups,
                    ['hide_navigation' => true, 'per_page' => 100],
                    $query_vars,
                    false,
                    [true, true, true, true, true]
                );
            }
            break;
    }
} else {
    $my_group_content = null;
    if (count($grid_my_groups) > 0) {
        $my_group_content = Display::return_sortable_grid(
            'mygroups',
            [],
            $grid_my_groups,
            ['hide_navigation' => true, 'per_page' => 2],
            $query_vars,
            false,
            [true, true, true, false]
        );
    } else {
        $my_group_content = '<span class="muted">'.get_lang('GroupNone').'</span>';
    }
    if (api_get_setting('allow_students_to_create_groups_in_social') == 'true') {
        $create_group_item =
            '<a class="btn btn-default" href="'.api_get_path(WEB_PATH).'main/social/group_add.php">'.
            get_lang('CreateASocialGroup').'</a>';
    } else {
        if (api_is_allowed_to_edit(null, true)) {
            $create_group_item =
                '<a class="btn btn-default" href="'.api_get_path(WEB_PATH).'main/social/group_add.php">'.
                get_lang('CreateASocialGroup').'</a>';
        }
    }
    if (count($grid_newest_groups) > 0) {
        $newest_content = Display::return_sortable_grid(
            'mygroups',
            [],
            $grid_newest_groups,
            ['hide_navigation' => true, 'per_page' => 100],
            $query_vars,
            false,
            [true, true, true, false]
        );
    } else {
        $newest_content = '<div class="muted">'.get_lang('GroupNone').'</div>';
    }
    if (count($grid_pop_groups) > 0) {
        $popular_content = Display::return_sortable_grid(
            'mygroups',
            [],
            $grid_pop_groups,
            ['hide_navigation' => true, 'per_page' => 100],
            $query_vars,
            false,
            [true, true, true, true, true]
        );
    } else {
        $popular_content = '<div class="muted">'.get_lang('GroupNone').'</div>';
    }
}

if (!empty($create_group_item)) {
    $social_right_content .= Display::page_subheader($create_group_item);
}
$headers = [get_lang('Newest'), get_lang('Popular'), get_lang('MyGroups')];
$social_right_content .= Display::tabs(
    $headers,
    [$newest_content, $popular_content, $my_group_content],
    'tab_browse'
);

$tpl = new Template(null);

// Block Social Avatar
SocialManager::setSocialUserBlock($tpl, api_get_user_id(), $show_menu);
$show_menu = 'browse_groups';
if (isset($_GET['view']) && $_GET['view'] == 'mygroups') {
    $show_menu = $_GET['view'];
}

$social_menu_block = SocialManager::show_social_menu($show_menu);
$templateName = 'social/groups.tpl';

$tpl->setHelp('Groups');
$tpl->assign('social_menu_block', $social_menu_block);
$tpl->assign('social_right_content', $social_right_content);
$social_layout = $tpl->get_template($templateName);
$tpl->display($social_layout);
