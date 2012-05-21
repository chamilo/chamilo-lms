<?php
/**
* When a user login, the function LoginRedirection::redirect is called.
* When this function is called all user info has already been registered in $_user session variable
**/
Class LoginRedirection {

  //checks user status and redirect him through custom page if setting is enabled
  public static function redirect(){

    global $param;
    $param = isset($param) ? $param : '';
    $redirect_url = '';
/*
    //If session request url is setted, we go there
    if (!empty($_SESSION['request_uri'])) {
      $req = $_SESSION['request_uri'];
      unset($_SESSION['request_uri']);
      header('location: '.$req);
      exit();
    }
 */

    if ( api_is_student() && !api_get_setting('student_page_after_login') == '' ){
      $redirect_url = html_entity_decode(api_get_setting('student_page_after_login'));
      if ($redirect_url[0] == "/") {
        $redirect_url = substr(api_get_path(WEB_PATH), 0, -1).$redirect_url;
      }
    }
    if ( api_is_teacher() && !api_get_setting('teacher_page_after_login') == '' ){
      $redirect_url = html_entity_decode(api_get_setting('teacher_page_after_login'));
      if ($redirect_url[0] == "/") {
        $redirect_url = substr(api_get_path(WEB_PATH), 0, -1).$redirect_url;
      }
    }
    if ( api_is_drh() && !api_get_setting('drh_page_after_login') == '' ){
      $redirect_url = html_entity_decode(api_get_setting('drh_page_after_login'));
      if ($redirect_url[0] == "/") {
        $redirect_url = substr(api_get_path(WEB_PATH), 0, -1).$redirect_url;
      }
    }
    if ( api_is_session_admin() && !api_get_setting('sessionadmin_page_after_login') == '' ){
      $redirect_url = html_entity_decode(api_get_setting('sessionadmin_page_after_login'));
      if ($redirect_url[0] == "/") {
        $redirect_url = substr(api_get_path(WEB_PATH), 0, -1).$redirect_url;
      }
    }

    if (!empty($redirect_url)){
      header('Location: '.$redirect_url.$param);
      exit();
    }

    // Custom pages
    if (CustomPages::enabled()) {
      CustomPages::display(CustomPages::INDEX_LOGGED);
    }
    header('location: '.api_get_path(WEB_PATH).api_get_setting('page_after_login').$param);
    exit();
  }
}