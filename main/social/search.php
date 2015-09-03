<?php
/* For licensing terms, see /license.txt */

/**
 * @package chamilo.social
 * @author Julio Montoya <gugli100@gmail.com>
 */
$cidReset = true;

require_once '../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'magpierss/rss_fetch.inc';
$ajax_url = api_get_path(WEB_AJAX_PATH).'message.ajax.php';
api_block_anonymous_users();

if (api_get_setting('allow_social_tool') != 'true') {
    api_not_allowed();
}

$this_section = SECTION_SOCIAL;
$tool_name = get_lang('Search');
$interbreadcrumb[] = array('url' => 'profile.php', 'name' => get_lang('SocialNetwork'));

$query = isset($_GET['q']) ? Security::remove_XSS($_GET['q']): null;
$query_search_type = isset($_GET['search_type']) && in_array($_GET['search_type'], array('0','1','2')) ? $_GET['search_type'] : null;
$extra_fields = UserManager::get_extra_filtrable_fields();
$query_vars = array('q' => $query, 'search_type' => $query_search_type);
if (!empty($extra_fields)) {
    foreach ($extra_fields as $extra_field) {
        $field_name = 'field_' . $extra_field['variable'];
        if (isset($_GET[$field_name]) && $_GET[$field_name] != '0') {
            $query_vars[$field_name] = $_GET[$field_name];
        }
    }
}

//Block Social Menu
$social_menu_block = SocialManager::show_social_menu('search');
$social_right_content = '';
$searchForm = UserManager::get_search_form($query);

$groups = array();
$totalGroups = array();
$users = array();
$totalUsers = array();

$usergroup = new UserGroup();

// I'm searching something
if ($query != '' || ($query_vars['search_type']=='1' && count($query_vars)>2) ) {
    $itemPerPage = 9;

    if ($_GET['search_type']=='0' || $_GET['search_type']=='1') {
        $page = isset($_GET['users_page_nr']) ? intval($_GET['users_page_nr']) : 1;
        $totalUsers = UserManager::get_all_user_tags($_GET['q'], 0, 0, $itemPerPage, true);

        $from = intval(($page - 1) * $itemPerPage);
        // Get users from tags
        $users = UserManager::get_all_user_tags($_GET['q'], 0, $from, $itemPerPage);
    }

    if ($_GET['search_type']=='0' || $_GET['search_type']=='2') {
        $pageGroup = isset($_GET['groups_page_nr']) ? intval($_GET['groups_page_nr']) : 1;
        // Groups
        $fromGroups = intval(($pageGroup - 1) * $itemPerPage);
        $totalGroups = count($usergroup->get_all_group_tags($_GET['q'], 0, $itemPerPage, true));

        $groups = $usergroup->get_all_group_tags($_GET['q'], $fromGroups);
    }

    if (empty($users) && empty($groups)) {
        Display::addFlash(Display::return_message(get_lang('SorryNoResults')));
    }

    $results = '<div id="online_grid_container">';
    if (is_array($users) && count($users) > 0) {
        $results .= Display::page_subheader(get_lang('Users'));
        $results .= '<div class="row">';
        $buttonClass = 'btn btn-default btn-sm';
        foreach ($users as $user) {
            $send_inv = '<button class="'.$buttonClass.' disabled "><i class="fa fa-user"></i> '.get_lang('SendInvitation').'</button>';
            $relation_type = intval(SocialManager::get_relation_between_contacts(api_get_user_id(), $user['user_id']));
            $user_info = api_get_user_info($user['user_id'], true);
            $url = api_get_path(WEB_PATH).'main/social/profile.php?u='.$user['user_id'];

            // Show send invitation icon if they are not friends yet
            if ($relation_type != 3 && $relation_type != 4 && $user['user_id'] != api_get_user_id()) {
                $send_inv = '<a href="#" class="'.$buttonClass.' btn-to-send-invitation" data-send-to="' . $user['user_id'] . '">
                             <i class="fa fa-user"></i> '.get_lang('SendInvitation').'</a>';
            }
            $send_msg = '<a href="#" class="btn-to-send-message '.$buttonClass.'" data-send-to="' . $user['user_id'] . '">
                        <i class="fa fa-envelope"></i> '.get_lang('SendMessage').'</a>';

            $img = '<img src="'.$user_info['avatar'].'" width="100" height="100">';

            if ($user_info['user_is_online']) {
                $status_icon = Display::span('', array('class' => 'online_user_in_text'));
            } else {
                $status_icon = Display::span('', array('class' => 'offline_user_in_text'));
            }

            $tag = isset($user['tag']) ? ' <br /><br />'.$user['tag'] : null;
            $user_info['complete_name'] = Display::url($status_icon.$user_info['complete_name'], $url);
            $invitations = $user['tag'].$send_inv.$send_msg;

            $results .= '<div class="col-md-4">
                            <div class="card">
                                <canvas class="header-bg" width="250" height="70" id="header-blur"></canvas>
                                <div class="avatar">
                                '.$img.'
                                </div>
                                <div class="content">
                                    '.$user_info['complete_name'].'
                                    <div class="btn-group">
                                    '.$invitations.'
                                    </div>
                                </div>
                            </div>
                      </div>';


        }
        $results .= '</div></div>';
        $social_right_content .= $results;
    }

    $visibility = array(true, true, true, true, true);
    $social_right_content .= Display::return_sortable_grid(
        'users',
        null,
        null,
        array('hide_navigation' => false, 'per_page' => $itemPerPage),
        $query_vars,
        false,
        $visibility,
        true,
        array(),
        $totalUsers
    );

    $grid_groups = array();
    if (is_array($groups) && count($groups) > 0) {
        $social_right_content .= '<div class="row">';
        $social_right_content .= Display::page_subheader(get_lang('Groups'));

        foreach ($groups as $group) {
            $group['name'] = Security::remove_XSS($group['name'], STUDENT, true);
            $group['description'] = Security::remove_XSS($group['description'], STUDENT, true);
            $id = $group['id'];
            $url_open = '<a class="btn btn-default" href="group_view.php?id='.$id.'">';
            $url_close = '</a>';
            $name = cut($group['name'], 60, true);
            $count_users_group = count($usergroup->get_all_users_by_group($id));
            if ($count_users_group == 1) {
                $count_users_group = $count_users_group.' '.get_lang('Member');
            } else {
                $count_users_group = $count_users_group.' '.get_lang('Members');
            }
            $picture = $usergroup->get_picture_group($group['id'], $group['picture'], GROUP_IMAGE_SIZE_ORIGINAL);
            //$tags = $usergroup->get_group_tags($group['id']);
            $tags = null;
            $group['picture'] = '<img src="'.$picture['file'].'" />';

            $members = Display::span($count_users_group);
            $item_1  = Display::tag('h3', $url_open.$name.$url_close).$members;

            $social_right_content .= '
                <div class="col-md-4">
                    <div class="card">
                        <div class="avatar">
                            '.$group['picture'].'
                        </div>
                        <div class="content">
                            '.$item_1.'
                            <p>'.$group['description'].'</p>
                            <p>'.$tags.'</p>
                            <p>'.$url_open.get_lang('SeeMore').$url_close.'</p>
                        </div>
                    </div>
                </div>';

        }
        $social_right_content .= '</ul></div></div>';
    }

    $visibility = array(true, true, true, true, true);
    $social_right_content .= Display::return_sortable_grid(
        'groups',
        null,
        $grid_groups,
        array('hide_navigation' => false, 'per_page' => $itemPerPage),
        $query_vars,
        false,
        $visibility,
        true,
        array(),
        $totalGroups
    );
}

$tpl = new Template($tool_name);
// Block Social Avatar
SocialManager::setSocialUserBlock($tpl, $user_id, 'search');
$tpl->assign('social_menu_block', $social_menu_block);
$tpl->assign('social_right_content', $social_right_content);
$tpl->assign('search_form', $searchForm);

$formModalTpl =  new Template();
$formModalTpl->assign('messageForm', MessageManager::generate_message_form('send_message'));
$formModalTpl->assign('invitationForm', MessageManager::generate_invitation_form('send_invitation'));
$formModals = $formModalTpl->fetch('default/social/form_modals.tpl');

$tpl->assign('formModals', $formModals);

$social_layout = $tpl->get_template('social/search.tpl');
$tpl->display($social_layout);
