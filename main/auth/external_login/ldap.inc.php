<?php // External login module : LDAP 
/**
 * This files is included by newUser.ldap.php and login.ldap.php
 * It implements the functions nedded by both files
 **/

//Includes the configuration file
require_once dirname(__FILE__).'/../../inc/global.inc.php';
require_once dirname(__FILE__).'/../../inc/conf/auth.conf.php';

/**
 * Returns a transcoded and trimmed string
 *
 * @param string 
 * @return string 
 * @author ndiechburg <noel@cblue.be>
 **/
function extldap_purify_string($string) {
  global $extldap_config;
  if(isset($extldap_config['encoding'])) {
    return trim(api_to_system_encoding($string, $extldap_config['encoding']));
  }
  else {
    return trim($string);
  }
}

/**
 * Establishes a connection to the LDAP server and sets the protocol version
 *
 * @return resource ldap link identifier or false
 * @author ndiechburg <noel@cblue.be>
 **/
function extldap_connect()
{
  global $extldap_config;

  if (!is_array($extldap_config['host']))
    $extldap_config['host'] = array($extldap_config['host']);

  foreach($extldap_config['host'] as $host) {
    //Trying to connect
    if (isset($extldap_config['port'])) {
      $ds = ldap_connect($host,$extldap_config['port']);
    } else {
      $ds = ldap_connect($host);
    }
    if (!$ds) {
      $port = isset($extldap_config['port']) ? $ldap_config['port'] : 389;
      error_log('EXTLDAP ERROR : cannot connect to '.$extldap_config['host'].':'. $port);
    } else
      break;
  }
  if (!$ds) { 
    error_log('EXTLDAP ERROR : no valid server found');
    return false;
  }
  //Setting protocol version
  if (isset($extldap_config['protocol_version'])) {
    if ( ! ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, $extldap_config['protocol_version'])) {
      ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 2);
    }
  }

  //Setting protocol version
  if (isset($extldap_config['referrals'])) {
    if ( ! ldap_set_option($ds, LDAP_OPT_REFERRALS, $extldap_config['referrals'])) {
      ldap_set_option($ds, LDAP_OPT_REFERRALS, $extldap_config['referrals']);
    }
  }
  
  return $ds;
}

/**
 * Authenticate user on external ldap server and return user ldap entry if that succeeds
 *
 * @return mixed false if user cannot authenticate on ldap, user ldap entry if tha succeeds
 * @author ndiechburg <noel@cblue.be>
 * Modified by hubert.borderiou@grenet.fr 
 * Add possibility to get user info from LDAP without check password (if CAS auth and LDAP profil update)
 * 
 **/
function extldap_authenticate($username, $password, $in_auth_with_no_password=false)
{
  global $extldap_config;

  if (empty($username) or empty($password)){
    return false;
  }

  $ds = extldap_connect();
  if (!$ds) {
    return false;
  }

  //Connection as admin to search dn of user
  $ldapbind = @ldap_bind($ds, $extldap_config['admin_dn'], $extldap_config['admin_password']);
  if ($ldapbind === false){
    error_log('EXTLDAP ERROR : cannot connect with admin login/password');
    return false;
  }
  $user_search = extldap_get_user_search_string($username);
  //Search distinguish name of user
  $sr = ldap_search($ds, $extldap_config['base_dn'], $user_search);
  if ( !$sr ){
    error_log('EXTLDAP ERROR : ldap_search('.$ds.', '.$extldap_config['base_dn'].", $user_search) failed");
    return false;
  }
  $entries_count = ldap_count_entries($ds,$sr);

  if ($entries_count > 1) {
    error_log('EXTLDAP ERROR : more than one entry for that user ( ldap_search(ds, '.$extldap_config['base_dn'].", $user_search) )");
    return false;
  }
  if ($entries_count < 1) {
    error_log('EXTLDAP ERROR :  No entry for that user ( ldap_search(ds, '.$extldap_config['base_dn'].", $user_search) )");
    return false;
  }
  $users = ldap_get_entries($ds,$sr);
  $user = $users[0];

  // If we just want to have user info from LDAP and not to check password
  if ($in_auth_with_no_password) {
    return $user;
  }
  //now we try to autenthicate the user in the ldap
  $ubind = @ldap_bind($ds, $user['dn'], $password);
  if($ubind !== false){
    return $user;
  }
  else {
    error_log('EXTLDAP : Wrong password for '.$user['dn']);
    return false;
  }
}

/**
 * Return an array with userinfo compatible with chamilo using $extldap_user_correspondance
 * configuration array declared in ldap.conf.php file
 *
 * @param array ldap user
 * @param array correspondance array (if not set use extldap_user_correspondance declared in auth.conf.php
 * @return array userinfo array
 * @author ndiechburg <noel@cblue.be>
 **/
function extldap_get_chamilo_user($ldap_user, $cor = null)
{
  global $extldap_user_correspondance;
  if ( is_null($cor) ) {
    $cor = $extldap_user_correspondance;
  }

  $chamilo_user =array();
  foreach ($cor as $chamilo_field => $ldap_field) {
    if (is_array($ldap_field)){
      $chamilo_user[$chamilo_field] = extldap_get_chamilo_user($ldap_user, $ldap_field);
      continue;
    }

    switch ($ldap_field) {
    case 'func':
      $func = "extldap_get_$chamilo_field";
      if (function_exists($func)) {
        $chamilo_user[$chamilo_field] = extldap_purify_string($func($ldap_user));
      } else {
        error_log("EXTLDAP WARNING : You forgot to declare $func");
      }
      break;
    default:
      //if string begins with "!", then this is a constant
      if($ldap_field[0] === '!' ){
        $chamilo_user[$chamilo_field] = trim($ldap_field, "!\t\n\r\0");
        break;
      }
      if ( isset($ldap_user[$ldap_field][0]) ) {
        $chamilo_user[$chamilo_field] = extldap_purify_string($ldap_user[$ldap_field][0]);
      } else {
        error_log('EXTLDAP WARNING : '.$ldap_field. '[0] field is not set in ldap array');

      }
      break;
    }
  }
  return $chamilo_user;
}



/**
 * Please declare here all the function you use in extldap_user_correspondance
 * All these functions must have an $ldap_user parameter. This parameter is the 
 * array returned by the ldap for the user
 **/
/**
 * example function for email
 **/
/*
function extldap_get_email($ldap_user){
  return $ldap_user['cn'].$ldap['sn'].'@gmail.com';
}
 */
function extldap_get_status($ldap_user){
    return STUDENT;
}
function extldap_get_admin($ldap_user){
    return false;
}


/**
 * return the string used to search a user in ldap
 *
 * @param string username
 * @return string the serach string
 * @author ndiechburg <noel@cblue.be>
 **/
function extldap_get_user_search_string($username) {
    global $extldap_config;
    // init
    $filter = '('.$extldap_config['user_search'].')';
    // replacing %username% by the actual username
    $filter = str_replace('%username%',$username,$filter);
    // append a global filter if needed
    if (isset($extldap_config['filter']) && $extldap_config['filter'] != "")
    $filter = '(&'.$filter.'('.$extldap_config['filter'].'))';

    return $filter;
}