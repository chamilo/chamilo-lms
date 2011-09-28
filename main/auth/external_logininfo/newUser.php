<?php
/*
 Template to automatically create a new user with information from anywhere.
 This file is loaded by main/inc/local.inc.php
 To use it please add this line to main/inc/conf/configuration.php :
   $extAuthSource["external_logininfo"]["newUser"] = $_configuration['root_sys'].$_configuration['code_append']."auth/external_logininfo/newUser.php";
 
 You also have to implements the external_get_user_info function in functions.inc.php
 */
require_once(api_get_path(LIBRARY_PATH).'usermanager.lib.php');
require_once(api_get_path(LIBRARY_PATH).'course.lib.php');
require_once(dirname(__FILE__).'/functions.inc.php');


//MAIN CODE

//$login and $password variables are setted in main/inc/local.inc.php

if ($password != DEFAULT_PASSWORD) {
    $user =  false;
} else {
  $user = external_get_user_info($login, $password);
}

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
