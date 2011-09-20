<?php //Script loaded by local.inc.php providing update user information of type external_logininfo.
/*
This script must not exit.
 */
require_once(api_get_path(LIBRARY_PATH).'usermanager.lib.php');
require_once(api_get_path(LIBRARY_PATH).'course.lib.php');
define('USERINFO_TABLE', 'userinfo');
//TODO : Please implements this function for this module to work.
/**
 * Gets user info from external source 
 * @param string login
 * @param string password
 * @return user array with at least the following fields:
 *       firstname
 *       lastname
 *       status
 *       email
 *       login
 *       password
 *   or false if no data
 **/
function external_get_user_info($login, $password){
  //Those are the mandatory fields for user creation.
  //See external_add_user function for all the fields you can have.
  $table = USERINFO_TABLE;
  $sql = "SELECT * from $table where username='".Database::escape_string($login)."'";
  $result = Database::query($sql);

  if (Database::num_rows($result) == 0 ) { //false password
    return false;
  }
  $user_info = Database::fetch_assoc($result);
  // User status
  $admin = false;
  switch($user_info['status']){
  case 'admin': 
    $status = COURSEMANAGER;
    $admin = true;
    break;
  case 'teacher':
    $status = COURSEMANAGER;
    break;
  case 'user':
    $status = STUDENT;
    break;
  default:
    $status = STUDENT;
  }
  // Language
  switch($user_info['language']){
  case 'FR' :
    $language = 'french';
    break;
  case 'EN' :
    $language = 'english';
    break;
  default : 
    $language = 'english';
    break;
  }

  $u = array(
    'firstname' => $user_info['firstname'],
    'lastname' => $user_info['lastname'],
    'status' => $status,
    'admin' => $admin,
    'email' => $user_info['email'],
    'login' => $user_info['username'],
    'language' => $language,
    'password' => DEFAULT_PASSWORD,
    'courses' => $user_info['courses'],
    'profile_link' => $user_info['profile_link'],
    'worldwide_bu' => $user_info['worlwide_bu'],
    'manager' => $user_info['manager'],
    'country_bu' => $user_info['country_bu'],
    'extra' => array(
      'position_title' => $user_info['position_title'],
      'country' => $user_info['country'],
      'job_family' => $user_info['job_family'],
      'update_type' => 'external_logininfo')
    );

  return $u; //Please return false if user does not exist
}

/**
 * update user info in database
 **/
function external_update_user($u){
  $updated = UserManager::update_user($u['user_id'], $u['firstname'], $u['lastname'], $u['login'], null, $u['auth_source'], $u['email'], $u['status'], $u['official_code'], $u['phone'], $u['picture_uri'], $u['expiration_date'], $u['active'], $u['creator_id'], $u['hr_dept_id'], $u['extra'], $u['language'],'');
  if(!empty($user['courses'])){
    $autoSubscribe = explode('|', $u['courses']);
    foreach ($autoSubscribe as $code) {
      if (CourseManager::course_exists($code)) { 
        CourseManager::subscribe_user($_user['user_id'], $code);
      }
    }
  }
  // Is User Admin ?
  if ($user['admin']){
    $is_platformAdmin           = true;
    Database::query("INSERT INTO admin values ('$chamilo_uid')");
  }

}
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
