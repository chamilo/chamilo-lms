<?php
/* For licensing terms, see /license.txt */
die('Remove the "die()" statement on line '.__LINE__.' to execute this script'.PHP_EOL);
require_once __DIR__.'/../../public/main/inc/global.inc.php';
api_protect_admin_script();

// Define origin and destination courses' code
$debug = true;

$file = 'file.csv';
$users = Import :: csvToArray($file);
foreach ($users as $user) {
    //$userInfo = api_get_user_info_from_username($user['UserName']);
    $userInfo = api_get_user_info_from_email($user['Email']);
    if ($userInfo) {
        if ($debug == false) {
            UserManager::delete_user($userInfo['user_id']);
            echo 'User deleted: '.$userInfo['user_id'].'  '.$userInfo['username'].'<br />';
        } else {
            echo 'User will be deleted: '.$userInfo['user_id'].'  '.$userInfo['username'].'<br />';
        }
    } else {
        echo 'user not found: "'.$user['UserName'].'"<br />';
    }
}
