<?php
define('USERINFO_TABLE', 'danone_userinfo');
define('DEFAULT_PASSWORD', 'danonelearning');

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
  //Can Send Message ?
  $can_send_message = ($user_info['can_send_message'] == 1) ? 'yes' : 'no';


  $u = array(
    'firstname' => $user_info['firstname'],
    'lastname' => $user_info['lastname'],
    'status' => $status,
    'admin' => $admin,
    'email' => $user_info['email'],
    'username' => $user_info['username'],
    'language' => $language,
    'password' => DEFAULT_PASSWORD,
    'courses' => $user_info['courses'],
    'profile_link' => $user_info['profile_link'],
    'worldwide_bu' => $user_info['worlwide_bu'],
    'manager' => $user_info['manager'],
    'extra' => array(
      'position_title' => $user_info['position_title'],
      'country' => $user_info['country'],
      'job_family' => $user_info['job_family'],
      'country_bu' => $user_info['country_bu'],
      'worldwide_bu' => $user_info['worldwide_bu'],
      'profile_link' => $user_info['profile_link'],
      'can_send_message' => $can_send_message,
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
  
  $chamilo_uid = UserManager::create_user($u['firstname'], $u['lastname'],$u['status'], $u['email'], $u['username'], $u['password'], $u['official_code'], $u['language'], $u['phone'],$u['picture_uri'], $u['auth_source'], $u['expiration_date'], $u['active'], $u['hr_dept_id'], $u['extra'], $u['encrypt_method']);
  return $chamilo_uid;
}
/**
 * update user info in database
 **/
function external_update_user($u){
  $updated = UserManager::update_user($u['user_id'], $u['firstname'], $u['lastname'], $u['username'], null, $u['auth_source'], $u['email'], $u['status'], $u['official_code'], $u['phone'], $u['picture_uri'], $u['expiration_date'], $u['active'], $u['creator_id'], $u['hr_dept_id'], $u['extra'], $u['language'],'');
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

?>
