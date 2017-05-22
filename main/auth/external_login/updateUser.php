<?php

//Script loaded by local.inc.php providing update user information of type external_logininfo.
/*
This script must not exit.
*/

use ChamiloSession as Session;

require_once __DIR__.'/functions.inc.php';

//MAIN CODE
//$uData variable is set in local.inc.php
$user = api_get_user_info($uData['user_id']);
$new_user = external_get_user_info($login);
$user['firstname'] = $new_user['firstname'];
$user['lastname'] = $new_user['lastname'];
$user['status'] = $new_user['status'];
$user['admin'] = $new_user['admin'];
$user['email'] = $new_user['email'];
$user['username'] = $new_user['username'];
$user['profile_link'] = $new_user['profile_link'];
$user['worldwide_bu'] = $new_user['worldwide_bu'];
$user['manager'] = $new_user['manager'];
$user['country_bu'] = $new_user['country_bu'];
$user['extra'] = $new_user['extra'];

if ($new_user !== false) { //User can login
    external_update_user($user);
    $loginFailed = false;
    $_user['user_id'] = $user['user_id'];
    $_user['uidReset'] = true;
    $uidReset = true;
    Session::write('_user', $_user);
} else {
    //User cannot login
    $loginFailed = true;
    Session::erase('_uid');
    header('Location: '.api_get_path(WEB_PATH).'index.php?loginFailed=1&error=user_password_incorrect');
    exit;
}
