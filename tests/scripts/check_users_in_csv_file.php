<?php
/* For licensing terms, see /license.txt */
die('Remove the "die()" statement on line '.__LINE__.' to execute this script'.PHP_EOL);
require_once __DIR__.'/../../public/main/inc/global.inc.php';
api_protect_admin_script();

$file = '';
$users = Import :: csvToArray($file);
foreach ($users as $user) {
    $userInfo = api_get_user_info_from_email($user['Email']);
    if (empty($userInfo)) {
        echo 'User does not exists: '.$userInfo['Email'].'<br />';
    }
}
