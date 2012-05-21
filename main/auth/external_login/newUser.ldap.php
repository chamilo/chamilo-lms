<?php // External login module : LDAP
/**
 * This file is included by main/inc/local.inc.php when extldap is activated, a user try to login 
 * and chamilo does not find his user
 * Variables that can be used : 
 *    - $login : string containing the username posted by the user
 *    - $password : string containing the password posted by the user
 *
 * Please configure the exldap module in main/auth/external_login/ldap.conf.php
 * 
 * If login succeeds, we have to add the user in the chamilo database and then 
 * we have 2 choices : 
 *    1.  - set $loginFailed to false, 
 *        - set $_SESSION['_user']['user_id'] with the dokeos user_id 
 *        - set $uidReset to true
 *        - let the script local.inc.php continue
 *
 *    2.  - set $_SESSION['_user']['user_id'] with the dokeos user_id 
 *        - set $_SESSION['_user']['uidReset'] to true
 *        - upgrade user info in dokeos database if needeed
 *        - redirect to any page and let local.inc.php do the magic
 * 
 * If login fails we have also 2 choices :
 *    1.  - unset $_user['user_id'] 
 *        - set $loginFailed=true  
 *        - set $uidReset =  false
 *        User wil then have the user password incorrect message
 *
 *    2. We redirect the user to index.php with appropriate message : 
 *        Possible messages are : 
 *          - index.php?loginFailed=1&error=access_url_inactive
 *          - index.php?loginFailed=1&error=account_expired
 *          - index.php?loginFailed=1&error=account_inactive
 *          - index.php?loginFailed=1&error=user_password_incorrect 
 *          - index.php?loginFailed=1&error=unrecognize_sso_origin');
 **/

require_once(dirname(__FILE__).'/ldap.conf.php');
require_once(dirname(__FILE__).'/functions.inc.php');

$ldap_user = extldap_authenticate($login,$password);
if ($ldap_user !== false) {
  $chamilo_user = extldap_get_chamilo_user($ldap_user);
  //username is not on the ldap, we have to use $login variable
  $chamilo_user['username'] = $login;
  $chamilo_uid = external_add_user($chamilo_user);
  if ($chamilo_uid !==false) {
    $loginFailed = false;
    $_user['user_id'] = $chamilo_uid;
    $_user['uidReset'] = true;  
    Session::write('_user',$_user);
    $uidReset=true;
    // Is user admin? 
    if ($chamilo_user['admin']=== true){
			$is_platformAdmin           = true;
      Database::query("INSERT INTO admin values ('$chamilo_uid')");
    }
  }
  event_login();
} else {
  $loginFailed = true;
  $uidReset = false;
  unset($_user['user_id']);
}

?>
