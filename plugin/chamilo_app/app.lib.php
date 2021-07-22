<?php
use ChamiloSession as Session;

function logoutPlatform($userId, $courseId = 0, $sessionId = 0) {
    online_logout($userId, false);
    
    $logoutInfo = [
        'uid' => $userId,
        'cid' => $courseId,
        'sid' => $sessionId,
    ];
    Event::courseLogout($logoutInfo);
}

function updateLogoutInLogin($userId) {
    $tbl_track_login = Database::get_main_table(TABLE_STATISTIC_TRACK_E_LOGIN);
    $sql = "SELECT login_id, login_date
            FROM $tbl_track_login
            WHERE
                login_user_id='".$userId."'
            ORDER BY login_date DESC
            LIMIT 0,1";
    
    $q_last_connection = Database::query($sql);
    if (Database::num_rows($q_last_connection) > 0) {
        $now = api_get_utc_datetime();
        $i_id_last_connection = Database::result($q_last_connection, 0, 'login_id');
        
        // is the latest logout_date still relevant?
        $sql = "SELECT logout_date FROM $tbl_track_login
                WHERE login_id = $i_id_last_connection";
        $q_logout_date = Database::query($sql);
        $res_logout_date = convert_sql_date(Database::result($q_logout_date, 0, 'logout_date'));
        $lifeTime = api_get_configuration_value('session_lifetime');
        
        if ($res_logout_date < time() - $lifeTime) {
            // it isn't, we should create a fresh entry
            Event::eventLogin($userId);
            // now that it's created, we can get its ID and carry on
        } else {
            $sql = "UPDATE $tbl_track_login SET logout_date = '$now'
                    WHERE login_id = '$i_id_last_connection'";
            Database::query($sql);
        }
        
        $tableUser = Database::get_main_table(TABLE_MAIN_USER);
        $sql = "UPDATE $tableUser SET last_login = '$now'
                WHERE user_id = ".$userId;
        Database::query($sql);
    }
}

function registerAccessCourseFromApp() {
    Event::accessCourse();
    Event::eventCourseLoginUpdate(
        api_get_course_int_id(),
        api_get_user_id(),
        api_get_session_id()
    );
}

function registerAccessFromApp($tool)
{
    Event::event_access_tool($tool);
}
