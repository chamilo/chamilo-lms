<?php
/*
 Template to automatically create a new user with information from anywhere.
 This file is loaded by main/inc/local.inc.php
 To use it please add this line to main/inc/conf/configuration.php :
   $extAuthSource["external_logininfo"]["newUser"] = $_configuration['root_sys'].$_configuration['code_append']."auth/external_logininfo/newUser.php";
 
 You also have to implements the external_get_user_info function in this file.
 */
	require_once(api_get_path(LIBRARY_PATH).'usermanager.lib.php');
	require_once(api_get_path(LIBRARY_PATH).'course.lib.php');
  define('USERINFO_TABLE', 'userinfo');
  define('DEFAULT_PASSWORD', 'boumbalah');

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
  if ($password != DEFAULT_PASSWORD) {
    return false;
  }
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
  //return false;
}

/**
 * Return an array with all user info
 * @param associative array with at least thes fields setted :
        firstname, lastname, status, email, login, password
 * @return mixed   new user id - if the new user creation succeeds, false otherwise
 **/
function external_add_user($u){
  //Setting default
  if (! isset($u['official_code']) )
    $u['official_code'] = '';
  if (! isset($u['language']) )
    $u['language'] = '';
  if (! isset($u['phone']) )
    $u['phone'] = '';
  if (! isset($u['picture_uri']) )
    $u['picture_uri'] = '';
  if (! isset($u['auth_source']) )
    $u['auth_source'] = PLATFORM_AUTH_SOURCE;
  if (! isset($u['expiration_date']) )
    $u['expiration_date'] = '0000-00-00 00:00:00';
  if (! isset($u['active']) )
    $u['active'] = 1;
  if (! isset($u['hr_dept_id']) )
    $u['hr_dept_id'] = 0; //id of responsible HR
  if (! isset($u['extra']) )
    $u['extra'] = null;
  if (! isset($u['encrypt_method']) )
    $u['encrypt_method'] = '';
  
  $chamilo_uid = UserManager::create_user($u['firstname'], $u['lastname'],$u['status'], $u['email'], $u['login'], $u['password'], $u['official_code'], $u['language'], $u['phone'],$u['picture_uri'], $u['auth_source'], $u['expiration_date'], $u['active'], $u['hr_dept_id'], $u['extra'], $u['encrypt_method']);
  return $chamilo_uid;
}


//MAIN CODE

//$login and $password variables are setted in main/inc/local.inc.php

$user = external_get_user_info($login, $password);

if ($user !== false && ($chamilo_uid = external_add_user($user)) !== false) {
    //log in the user
    $loginFailed = false;
    $_user['user_id'] = $chamilo_uid;
    api_session_register('_user');
    //Autosubscribe to courses
    if(!empty($user['courses'])){
      $autoSubscribe = explode('|', $user['courses']);
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
    // Can user create course
    $is_allowedCreateCourse     = (bool) (($user['status'] == COURSEMANAGER) or (api_get_setting('drhCourseManagerRights') and $user['status'] == SESSIONADMIN));

    event_login();
} else {
	$loginFailed = true;
	unset($_user['user_id']);
	$uidReset = false;
}
?>
