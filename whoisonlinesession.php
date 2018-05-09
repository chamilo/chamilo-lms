<?php
/* For licensing terms, see /license.txt */

/**
 * Shows who is online in a specific session.
 *
 * @package chamilo.main
 */
require_once './main/inc/global.inc.php';

api_block_anonymous_users();

$userId = api_get_user_id();
if (empty($userId)) {
    api_not_allowed(true);
}

$sessionId = api_get_session_id();
if (empty($sessionId)) {
    api_not_allowed(true);
}

$allow = api_is_platform_admin(true) ||
    api_is_coach($sessionId, null, false) ||
    SessionManager::isUserSubscribedAsStudent($sessionId, api_get_user_id());

if (!$allow) {
    api_not_allowed(true);
}

$maxNumberItems = 20;
$sessionInfo = api_get_session_info($sessionId);

Display::display_header(get_lang('UsersOnLineList'));
echo Display::page_header($sessionInfo['name']);

function getUsers(
    $from,
    $numberItems,
    $column,
    $direction,
    $getCount = false
) {
    $sessionId = api_get_session_id();
    $from = (int) $from;
    $numberItems = (int) $numberItems;

    $urlCondition = '';
    $urlJoin = '';
    if (api_is_multiple_url_enabled()) {
        $accessUrlUser = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
        $urlId = api_get_current_access_url_id();
        $urlJoin = " INNER JOIN $accessUrlUser a ON (a.user_id = user.id) ";
        $urlCondition = " AND a.access_url_id = $urlId ";
    }

    if (empty($time_limit)) {
        $time_limit = api_get_setting('time_limit_whosonline');
    } else {
        $time_limit = 60;
    }

    $online_time = time() - $time_limit * 60;
    $current_date = api_get_utc_datetime($online_time);

    if ($getCount) {
        $sql = "SELECT 
            count(DISTINCT last_access.login_user_id) count
            FROM ".Database::get_main_table(TABLE_STATISTIC_TRACK_E_ONLINE)." AS last_access
            INNER JOIN ".Database::get_main_table(TABLE_MAIN_USER)." AS user
            ON user.id = last_access.login_user_id
            $urlJoin
        WHERE 
            session_id ='".$sessionId."' AND 
            login_date >= '$current_date'
            $urlCondition";
        $result = Database::query($sql);
        $result = Database::fetch_array($result);

        return $result['count'];
    }

    $sql = "SELECT DISTINCT 
            last_access.login_user_id,
            last_access.c_id
        FROM ".Database::get_main_table(TABLE_STATISTIC_TRACK_E_ONLINE)." AS last_access
        INNER JOIN ".Database::get_main_table(TABLE_MAIN_USER)." AS user
        ON user.id = last_access.login_user_id
        $urlJoin
        WHERE 
            session_id ='".$sessionId."' AND 
            login_date >= '$current_date'
            $urlCondition            
        GROUP BY login_user_id
        LIMIT $from, $numberItems";

    $studentsOnline = [];
    $result = Database::query($sql);
    while ($user_list = Database::fetch_array($result)) {
        $studentsOnline[$user_list['login_user_id']] = $user_list;
    }

    return $studentsOnline;
}

function getCountUsers()
{
    return getUsers(0, 0, 0, 0, true);
}

$table = new SortableTable(
    'users',
    'getCountUsers',
    'getUsers',
    '1',
    $maxNumberItems
);
$table->set_header(0, get_lang('Name'), false);
$table->set_header(1, get_lang('InCourse'), false);

$table->set_column_filter(0, 'user_filter');
$table->set_column_filter(1, 'course_filter');
$table->display();

function user_filter($userId, $urlParams, $row)
{
    $userInfo = api_get_user_info($userId);

    return $userInfo['complete_name_with_message_link'];
}

function course_filter($courseId, $urlParams, $row)
{
    $sessionId = api_get_session_id();
    $courseInfo = api_get_course_info_by_id($courseId);

    return Display::url(
        $courseInfo['title'],
        $courseInfo['course_public_url'].'?id_session='.$sessionId,
        ['target' => '_blank']
    ).
    '&nbsp;'.
    Display::url(
        get_lang('Chat'),
        'main/chat/chat.php?cidReq='.$courseInfo['code'].'&id_session='.$sessionId,
        ['target' => '_blank', 'class' => 'btn btn-primary']
    );
}

Display::display_footer();
