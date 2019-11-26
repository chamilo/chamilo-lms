<?php
/* For licensing terms, see /license.txt */

$_dont_save_user_course_access = true;

require_once __DIR__.'/../global.inc.php';

$action = isset($_GET['a']) ? $_GET['a'] : '';

switch ($action) {
    case 'get_users_online':
        echo returnNotificationMenu();
        break;
    case 'load_online_user':
        $access = accessToWhoIsOnline();

        if (!$access) {
            exit;
        }
        $images_to_show = MAX_ONLINE_USERS;
        $page = intval($_REQUEST['online_page_nr']);
        $max_page = ceil(who_is_online_count() / $images_to_show);
        $page_rows = ($page - 1) * MAX_ONLINE_USERS;
        if (!empty($max_page) && $page <= $max_page) {
            if (isset($_GET['cidReq']) && strlen($_GET['cidReq']) > 0) {
                $user_list = who_is_online_in_this_course(
                    $page_rows,
                    $images_to_show,
                    api_get_user_id(),
                    api_get_setting('time_limit_whosonline'),
                    $_GET['cidReq']
                );
            } else {
                $user_list = who_is_online($page_rows, $images_to_show);
            }
            if (!empty($user_list)) {
                echo SocialManager::display_user_list($user_list, false);
                exit;
            }
        }
        echo 'end';
        break;
    default:
        break;
}
