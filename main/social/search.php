<?php
/* For licensing terms, see /license.txt */

/**
 * @package chamilo.social
 *
 * @author Julio Montoya <gugli100@gmail.com>
 */
$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';
$ajax_url = api_get_path(WEB_AJAX_PATH).'message.ajax.php';
api_block_anonymous_users();

if (api_get_setting('allow_social_tool') != 'true') {
    api_not_allowed();
}

$this_section = SECTION_SOCIAL;
$tool_name = get_lang('Search');
$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'social/profile.php',
    'name' => get_lang('SocialNetwork'),
];

$query = isset($_GET['q']) ? htmlentities($_GET['q']) : null;

$queryNoTags = isset($_GET['q']) ? strip_tags($_GET['q']) : null;
$query_search_type = isset($_GET['search_type']) && in_array($_GET['search_type'], ['0', '1', '2']) ? $_GET['search_type'] : null;
$extra_fields = UserManager::getExtraFilterableFields();
$query_vars = ['q' => $query, 'search_type' => $query_search_type];
if (!empty($extra_fields)) {
    foreach ($extra_fields as $extra_field) {
        $field_name = 'field_'.$extra_field['variable'];
        if (isset($_GET[$field_name]) && $_GET[$field_name] != '0') {
            $query_vars[$field_name] = $_GET[$field_name];
        }
    }
}

//Block Social Menu
$social_menu_block = SocialManager::show_social_menu('search');
$block_search = '';
$searchForm = UserManager::get_search_form($queryNoTags);

$groups = [];
$totalGroups = [];
$users = [];
$totalUsers = [];
$usergroup = new UserGroup();

// I'm searching something
if ($query != '' || ($query_vars['search_type'] == '1' && count($query_vars) > 2)) {
    $itemPerPage = 6;

    if ($_GET['search_type'] == '0' || $_GET['search_type'] == '1') {
        $page = isset($_GET['users_page_nr']) ? intval($_GET['users_page_nr']) : 1;
        $totalUsers = UserManager::get_all_user_tags(
            $_GET['q'],
            0,
            0,
            $itemPerPage,
            true
        );

        $from = intval(($page - 1) * $itemPerPage);
        // Get users from tags
        $users = UserManager::get_all_user_tags($_GET['q'], 0, $from, $itemPerPage);
    }

    if ($_GET['search_type'] == '0' || $_GET['search_type'] == '2') {
        $pageGroup = isset($_GET['groups_page_nr']) ? intval($_GET['groups_page_nr']) : 1;
        // Groups
        $fromGroups = intval(($pageGroup - 1) * $itemPerPage);
        $totalGroups = count($usergroup->get_all_group_tags($_GET['q'], 0, $itemPerPage, true));

        $groups = $usergroup->get_all_group_tags($_GET['q'], $fromGroups);
    }

    if (empty($users) && empty($groups)) {
        Display::addFlash(Display::return_message(get_lang('SorryNoResults')));
    }

    $results = '<div id="whoisonline">';
    if (is_array($users) && count($users) > 0) {
        $results .= '<div class="row">';
        $buttonClass = 'btn btn-default btn-sm';
        foreach ($users as $user) {
            $user_info = api_get_user_info($user['id'], true);
            $sendInvitation = '<button class="'.$buttonClass.' disabled ">
                <em class="fa fa-user"></em> '.get_lang('SendInvitation').'</button>';
            $relation_type = SocialManager::get_relation_between_contacts(api_get_user_id(), $user_info['user_id']);
            $url = api_get_path(WEB_PATH).'main/social/profile.php?u='.$user_info['user_id'];

            // Show send invitation icon if they are not friends yet
            if ($relation_type != 3 && $relation_type != 4 && $user_info['user_id'] != api_get_user_id()) {
                $sendInvitation = '<a href="#" class="'.$buttonClass.' btn-to-send-invitation" data-send-to="'.$user_info['user_id'].'">
                             <em class="fa fa-user"></em> '.get_lang('SendInvitation').'</a>';
            }

            $sendMessageUrl = api_get_path(WEB_AJAX_PATH).'user_manager.ajax.php?'.http_build_query([
                'a' => 'get_user_popup',
                'user_id' => $user_info['user_id'],
            ]);

            $sendMessage = Display::toolbarButton(
                get_lang('SendMessage'),
                $sendMessageUrl,
                'envelope',
                'default',
                [
                    'class' => 'ajax btn-sm',
                    'data-title' => get_lang('SendMessage'),
                ]
            );

            if (!empty($user_info['user_is_online'])) {
                $status_icon = Display::return_icon('online.png', get_lang('OnLine'), null, ICON_SIZE_TINY);
            } else {
                $status_icon = Display::return_icon('offline.png', get_lang('Disconnected'), null, ICON_SIZE_TINY);
            }

            if ($user_info['status'] == 5) {
                $user_icon = Display::return_icon('user.png', get_lang('Student'), null, ICON_SIZE_TINY);
            } else {
                $user_icon = Display::return_icon('teacher.png', get_lang('Teacher'), null, ICON_SIZE_TINY);
            }

            $user_info['complete_name'] = Display::url($user_info['complete_name'], $url);
            $invitations = $sendInvitation.$sendMessage;

            $results .= Display::getUserCard(
                $user_info,
                $status_icon.$user_icon,
                $invitations
            );
        }
        $results .= '</div>';
    }
    $results .= '</div>';

    $visibility = [true, true, true, true, true];

    if (!empty($users)) {
        $results .= Display::return_sortable_grid(
            'users',
            null,
            null,
            ['hide_navigation' => false, 'per_page' => $itemPerPage],
            $query_vars,
            false,
            $visibility,
            true,
            [],
            $totalUsers
        );
        $block_search .= Display::panelCollapse(
            get_lang('Users'),
            $results,
            'search-friends',
            null,
            'friends-accordion',
            'friends-collapse'
        );
    }

    $grid_groups = [];
    $block_groups = '<div id="whoisonline">';
    if (is_array($groups) && count($groups) > 0) {
        $block_groups .= '<div class="row">';
        foreach ($groups as $group) {
            $group['name'] = Security::remove_XSS($group['name'], STUDENT, true);
            $group['description'] = Security::remove_XSS($group['description'], STUDENT, true);
            $id = $group['id'];
            $url_open = '<a href="group_view.php?id='.$id.'">';
            $url_close = '</a>';
            $name = cut($group['name'], 60, true);
            $count_users_group = count($usergroup->get_all_users_by_group($id));
            if ($count_users_group == 1) {
                $count_users_group = $count_users_group;
            } else {
                $count_users_group = $count_users_group;
            }
            $picture = $usergroup->get_picture_group(
                $group['id'],
                $group['picture'],
                GROUP_IMAGE_SIZE_ORIGINAL
            );

            $tags = null;
            $group['picture'] = '<img class="img-responsive img-circle" src="'.$picture['file'].'" />';

            $members = Display::returnFontAwesomeIcon('user').'( '.$count_users_group.' )';
            $item_1 = Display::tag('p', $url_open.$name.$url_close);

            $block_groups .= '
                <div class="col-md-4">
                    <div class="items-user">
                        <div class="items-user-avatar">
                            '.$group['picture'].'
                        </div>
                        <div class="user-info">
                            '.$item_1.'
                            <p>'.$members.'</p>
                            <p>'.$group['description'].'</p>
                            <p>'.$tags.'</p>
                            <p>'.$url_open.get_lang('SeeMore').$url_close.'</p>
                        </div>
                    </div>
                </div>';
        }
        $block_groups .= '</div>';
    }
    $block_groups .= '</div>';

    $visibility = [true, true, true, true, true];

    if (!empty($groups)) {
        $block_groups .= Display::return_sortable_grid(
            'groups',
            null,
            $grid_groups,
            ['hide_navigation' => false, 'per_page' => $itemPerPage],
            $query_vars,
            false,
            $visibility,
            true,
            [],
            $totalGroups
        );
        $block_search .= Display::panelCollapse(
            get_lang('Groups'),
            $block_groups,
            'search-groups',
            null,
            'groups-accordion',
            'groups-collapse'
        );
    }
}

$tpl = new Template($tool_name);
// Block Social Avatar
SocialManager::setSocialUserBlock($tpl, api_get_user_id(), 'search');
$tpl->assign('social_menu_block', $social_menu_block);
$tpl->assign('social_search', $block_search);
$tpl->assign('search_form', $searchForm);

$formModalTpl = new Template();
$formModalTpl->assign('invitation_form', MessageManager::generate_invitation_form());
$template = $formModalTpl->get_template('social/form_modals.tpl');
$formModals = $formModalTpl->fetch($template);

$tpl->assign('form_modals', $formModals);

$social_layout = $tpl->get_template('social/search.tpl');
$tpl->display($social_layout);
