<?php
/* For licensing terms, see /license.txt */

require_once __DIR__.'/../inc/global.inc.php';
api_protect_admin_script();

$tableUser = Database::get_main_table(TABLE_MAIN_USER);
$userInfo = api_get_user_info(api_get_user_id());
if (isset($userInfo['last_login'])) {
    $sql = "SELECT login_user_id, MAX(login_date) login_date from track_e_login group by login_user_id";
    $result = Database::query($sql);
    while ($row = Database::fetch_array($result)) {
        $date = $row['login_date'];
        $userId = $row['login_user_id'];
        $sql = "UPDATE $tableUser SET last_login ='$date' WHERE user_id = $userId";
        Database::query($sql);
    }
}
