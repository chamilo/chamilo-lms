<?php

/* For licensing terms, see /license.txt */
/**
 * @author Bart Mollet, Julio Montoya lot of fixes
 */

use Chamilo\CoreBundle\Entity\Session;

$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';

// setting the section (for the tabs)
$this_section = SECTION_PLATFORM_ADMIN;

api_protect_admin_script(true);

$tool_name = get_lang('Session overview');
$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('Administration')];
$interbreadcrumb[] = ['url' => 'session_list.php', 'name' => get_lang('Session list')];

// Database Table Definitions
$tbl_user = Database::get_main_table(TABLE_MAIN_USER);
$tbl_session_rel_user = Database::get_main_table(TABLE_MAIN_SESSION_USER);
$table_access_url_user = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
$url_id = api_get_current_access_url_id();

$action = $_GET['action'];

switch ($action) {
    case 'add_user_to_url':
        $user_id = $_REQUEST['user_id'];
        $result = UrlManager::add_user_to_url($user_id, $url_id);
        $user_info = api_get_user_info($user_id);
        if ($result) {
            $message = Display::return_message(
                get_lang('The user has been added').' '.api_get_person_name(
                    $user_info['firstname'],
                    $user_info['lastname']
                ),
                'confirm'
            );
        }
        break;
}

Display::display_header($tool_name);

if (!empty($message)) {
    echo $message;
}

$multiple_url_is_on = api_get_multiple_access_url();
$order_clause = api_sort_by_first_name() ? ' ORDER BY firstname, lastname' : ' ORDER BY lastname, firstname';
$session_list = SessionManager::get_sessions_list();

$html = '';
$show_users_with_problems = isset($_REQUEST['show_users_with_problems']) && 1 == $_REQUEST['show_users_with_problems'] ? true : false;
if ($show_users_with_problems) {
    $html .= '<a href="'.api_get_self().'?show_users_with_problems=0">'.get_lang('Show all users').'</a>';
} else {
    $html .= '<a href="'.api_get_self().'?show_users_with_problems=1">'.get_lang('Show users not added to the URL').'</a>';
}

foreach ($session_list as $session_item) {
    $session_id = $session_item['id'];
    $html .= '<h3>'.$session_item['name'].'</h3>';
    $access_where = "(access_url_id = $url_id OR access_url_id is null )";
    if ($show_users_with_problems) {
        $access_where = '(access_url_id is null)';
    }

    $sql = "SELECT u.id as user_id, lastname, firstname, username, access_url_id
            FROM $tbl_user u
            INNER JOIN $tbl_session_rel_user su
            ON u.id = su.user_id AND su.relation_type <> ".Session::DRH."
            LEFT OUTER JOIN $table_access_url_user uu
            ON (uu.user_id = u.id)
            WHERE su.session_id = $session_id AND $access_where
            $order_clause";

    $result = Database::query($sql);
    $users = Database::store_result($result);

    if (!empty($users)) {
        $html .= '<table class="data_table"><tr><th>'.get_lang('User').'<th>'.get_lang('Detail').'</th></tr>';

        foreach ($users as $user) {
            $user_link = '';
            if (!empty($user['user_id'])) {
                $user_link = '<a href="'.api_get_path(WEB_CODE_PATH).'admin/user_information.php?user_id='.(int) ($user['user_id']).'">'.Security::remove_XSS(api_get_person_name($user['firstname'], $user['lastname'])).' ('.$user['username'].')</a>';
            }

            $link_to_add_user_in_url = '';
            if ($multiple_url_is_on) {
                if ($user['access_url_id'] != $url_id) {
                    $user_link .= ' '.Display::return_icon('warning.png', get_lang('Users not added to the URL'), [], ICON_SIZE_MEDIUM);
                    $add = Display::return_icon('add.png', get_lang('Add users to an URL'), [], ICON_SIZE_MEDIUM);
                    $link_to_add_user_in_url = '<a href="'.api_get_self().'?'.Security::remove_XSS($_SERVER['QUERY_STRING']).'&action=add_user_to_url&id_session='.$session_id.'&user_id='.$user['user_id'].'">'.$add.'</a>';
                }
            }
            $html .= '<tr>
                    <td>
                        <b>'.$user_link.'</b>
                    </td>
                    <td>
                        '.$link_to_add_user_in_url.'
                    </td>
                    </tr>';
        }
        $html .= '</table>';
    }
}
echo $html;
// footer
Display :: display_footer();
