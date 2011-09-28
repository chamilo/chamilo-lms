<?php //Script loaded by local.inc.php providing update user information of type external_logininfo.
/*
This script must not exit.
 */
require_once(api_get_path(LIBRARY_PATH).'usermanager.lib.php');
require_once(api_get_path(LIBRARY_PATH).'course.lib.php');
require_once(dirname(__FILE__).'/functions.inc.php');

//MAIN CODE

//$uData variable is set in local.inc.php
$user = UserManager::get_user_info_by_id($uData['user_id']);
$new_user = external_get_user_info($login);
$user['firstname'] = $new_user['firstname'];
$user['lastname'] = $new_user['lastname'];
$user['status'] = $new_user['status'];
$user['admin'] = $new_user['admin'];
$user['email'] = $new_user['email'];
$user['username'] = $new_user['login'];
$user['profile_link'] = $new_user['profile_link'];
$user['worldwide_bu'] = $new_user['worldwide_bu'];
$user['manager'] = $new_user['manager'];
$user['country_bu'] = $new_user['country_bu'];
$user['extra'] = $new_user['extra'];

if ($new_user !== false) { 
  $new_user['user_id'] = $uData['user_id'];
  external_update_user($new_user);
}
?>
