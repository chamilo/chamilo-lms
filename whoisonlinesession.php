<?php
/* For licensing terms, see /license.txt */
/**
 * Shows who is online in a specific session
 * @package chamilo.main
 */

include_once './main/inc/global.inc.php';
api_block_anonymous_users();

$tbl_session = Database::get_main_table(TABLE_MAIN_SESSION);
$tbl_session_course_user = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);

/**
 * Header
 * include the HTTP, HTML headers plus the top banner
 */

Display::display_header(get_lang('UserOnlineListSession'));
?>
<br /><br />
<table class="data_table" width="60%">
    <tr class="tableName">
        <td colspan="4">
            <strong><?php echo get_lang('UserOnlineListSession'); ?></strong>
        </td>
    </tr>
    <tr>
        <th>
            <?php echo get_lang('Name'); ?>
        </th>
        <th>
            <?php echo get_lang('InCourse'); ?>
        </th>
        <th>
            <?php echo get_lang('Email'); ?>
        </th>
        <th>
            <?php echo get_lang('Chat'); ?>
        </th>
    </tr>
<?php
$session_is_coach = array();

if (isset($_user['user_id']) && $_user['user_id'] != '') {
    $_user['user_id'] = intval($_user['user_id']);
    $sql = "SELECT DISTINCT session.id,
                name,
                access_start_date,
                access_end_date
            FROM $tbl_session as session
            INNER JOIN $tbl_session_course_user as srcru
            ON 
                srcru.user_id = ".$_user['user_id']." AND 
                srcru.status=2 AND 
                session.id = srcru.session_id
            ORDER BY access_start_date, access_end_date, name";
    $result = Database::query($sql);

    while ($session = Database:: fetch_array($result)) {
        $session_is_coach[$session['id']] = $session;
    }

    $sql = "SELECT DISTINCT session.id,
                name,
                access_start_date,
                access_end_date
            FROM $tbl_session as session
            WHERE session.id_coach = ".$_user['user_id']."
            ORDER BY access_start_date, access_end_date, name";
    $result = Database::query($sql);
    while ($session = Database:: fetch_array($result)) {
        $session_is_coach[$session['id']] = $session;
    }

    if (empty($time_limit)) {
        $time_limit = api_get_setting('time_limit_whosonline');
    } else {
        $time_limit = 60;
    }

    $online_time = time() - $time_limit * 60;
    $current_date = api_get_utc_datetime($online_time);
    $students_online = array();
    foreach ($session_is_coach as $session) {
        $sql = "SELECT DISTINCT last_access.access_user_id,
                    last_access.access_date,
                    last_access.c_id,
                    last_access.access_session_id,
                    ".(api_is_western_name_order() ? "CONCAT(user.firstname,' ',user.lastname)" : "CONCAT(user.lastname,' ',user.firstname)")." as name,
                    user.email
                FROM ".Database::get_main_table(TABLE_STATISTIC_TRACK_E_LASTACCESS)." AS last_access
                INNER JOIN ".Database::get_main_table(TABLE_MAIN_USER)." AS user
                    ON user.user_id = last_access.access_user_id
                WHERE access_session_id='".$session['id']."'
                AND access_date >= '$current_date'
                GROUP BY access_user_id";

        $result = Database::query($sql);
        while ($user_list = Database::fetch_array($result)) {
            $students_online[$user_list['access_user_id']] = $user_list;
        }
    }

    if (count($students_online) > 0) {
        foreach ($students_online as $student_online) {
            echo "<tr>
                    <td>
                ";
            echo $student_online['name'];
            echo "	</td>
                    <td align='center'>
                 ";
            $courseInfo = api_get_course_info_by_id($student_online['c_id']);
            echo $courseInfo['title'];
            echo "	</td>
                    <td align='center'>
                 ";
            if (!empty($student_online['email'])) {
                echo $student_online['email'];
            } else {
                echo get_lang('NoEmail');
            }
            echo "	</td>
                    <td align='center'>
                 ";
            echo '<a href="main/chat/chat.php?cidReq='.$courseInfo['code'].'&id_session='.$student_online['access_session_id'].'"> -> </a>';
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
}
?>
</table>
<?php

Display::display_footer();
