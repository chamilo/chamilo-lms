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
$courseId = api_get_course_int_id();

if (empty($sessionId)) {
    api_not_allowed(true);
}

$allow = api_is_platform_admin(true) ||
    api_is_coach($sessionId, $courseId, false) ||
    SessionManager::get_user_status_in_course_session(api_get_user_id(), $courseId, $sessionId) == 2;

if (!$allow) {
    api_not_allowed(true);
}

$sessionInfo = api_get_session_info($sessionId);

Display::display_header(get_lang('UsersOnLineList'));
echo Display::page_header($sessionInfo['name']);
?>
<br />
<table class="data_table">
    <tr>
        <th>
            <?php echo get_lang('Name'); ?>
        </th>
        <th>
            <?php echo get_lang('InCourse'); ?>
        </th>
        <th>
            <?php echo get_lang('Chat'); ?>
        </th>
    </tr>
<?php

if (empty($time_limit)) {
    $time_limit = api_get_setting('time_limit_whosonline');
} else {
    $time_limit = 60;
}

$urlCondition = '';
$urlJoin = '';
if (api_is_multiple_url_enabled()) {
    $accessUrlUser = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
    $urlId = api_get_current_access_url_id();
    $urlJoin = " INNER JOIN $accessUrlUser a ON (a.user_id = user.id) ";
    $urlCondition = " AND a.access_url_id = $urlId ";
}

$online_time = time() - $time_limit * 60;
$current_date = api_get_utc_datetime($online_time);
$studentsOnline = [];

$sql = "SELECT DISTINCT last_access.login_user_id,
            last_access.login_date,
            last_access.c_id,
            last_access.session_id
        FROM ".Database::get_main_table(TABLE_STATISTIC_TRACK_E_ONLINE)." AS last_access
        INNER JOIN ".Database::get_main_table(TABLE_MAIN_USER)." AS user
        ON user.id = last_access.login_user_id
        $urlJoin
        WHERE 
            session_id ='".$sessionId."' AND 
            login_date >= '$current_date'
            $urlCondition            
        GROUP BY login_user_id";

$result = Database::query($sql);
while ($user_list = Database::fetch_array($result)) {
    $studentsOnline[$user_list['login_user_id']] = $user_list;
}

if (count($studentsOnline) > 0) {
    foreach ($studentsOnline as $student_online) {
        $userInfo = api_get_user_info($student_online['login_user_id']);
        if (empty($userInfo)) {
            continue;
        }

        echo "<tr>
                <td>
            ";
        echo $userInfo['complete_name_with_message_link'];
        echo "	</td>
                <td>
             ";
        $courseInfo = api_get_course_info_by_id($student_online['c_id']);
        echo Display::url(
            $courseInfo['title'],
            $courseInfo['course_public_url'].'?id_session='.$student_online['session_id'],
            ['target' => '_blank']
        );
        echo "	</td>";
        echo "<td>";
        echo '<a 
                target="_blank" 
                href="main/chat/chat.php?cidReq='.$courseInfo['code'].'&id_session='.$student_online['session_id'].'"> -> </a>';
        echo "	</td>
            </tr>
             ";
    }
} else {
    echo '	<tr>
                <td colspan="4">
                    '.get_lang('NoOnlineStudents').'
                </td>
            </tr>
         ';
}

?>
</table>
<?php

Display::display_footer();
