<?php // External login module : LDAP
/**
 * Configuration file 
 * Please edit this file to match with your LDAP settings
 **/

require_once dirname(__FILE__).'/ldap.inc.php';

/** 
 * Array of connection parameters
 **/
$extldap_config = array(
  //base dommain string
  'base_dn' => 'DC=cblue,DC=be',
  //admin distinguished name
  'admin_dn' => 'CN=admin,dc=cblue,dc=be',
  //admin password
  'admin_password' => 'pass',
  //ldap host
  'host' => array('10.1.2.3', '10.1.2.4', '10.1.2.5'),
  // filter
//  'filter' => '', // no () arround the string
  //'port' => , default on 389
  //protocl version (2 or 3)
  'protocol_version' => 3,
  // set this to 0 to connect to AD server
  'referrals' => 0,
  //String used to search the user in ldap. %username will ber replaced by the username.
  //See extldap_get_user_search_string() function below
//  'user_search' => 'sAMAccountName=%username%',  // no () arround the string
  'user_search' => 'uid=%username%',  // no () arround the string
  //encoding used in ldap (most common are UTF-8 and ISO-8859-1
  'encoding' => 'UTF-8',
  //Set to true if user info have to be update at each login
  'update_userinfo' => true
);

/**
 * return the string used to search a user in ldap
 *
 * @param string username
 * @return string the serach string
 * @author ndiechburg <noel@cblue.be>
 **/
function extldap_get_user_search_string($username)
{
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

/**
 * Correspondance array between chamilo user info and ldap user info
 * This array is of this form : 
 *  '<chamilo_field> => <ldap_field>
 *
 * If <ldap_field> is "func", then the value of <chamilo_field> will be the return value of the function
 * extldap_get_<chamilo_field>($ldap_array)
 * In this cas you will have to declare the extldap_get_<chamilo_field> function
 *
 * If <ldap_field> is a string beginning with "!", then the value will be this string without "!"
 * 
 * If <ldap_field> is any other string then the value of <chamilo_field> will be 
 * $ldap_array[<ldap_field>][0]
 *
 * If <ldap_field> is an array then its value will be an array of values with the same rules as above
 * 
 **/
$extldap_user_correspondance = array(
  'firstname' => 'givenName',
  'lastname' => 'sn',
  'status' => 'func',
  'admin' => 'func',
  'email' => 'mail',
  'auth_source' => '!extldap',
  //'username' => ,
  'language' => '!english',
  'password' => '!PLACEHOLDER',
  'extra' => array(
    'title' => 'title',
    'globalid' => 'employeeID',
    'department' => 'department',
    'country' => 'co',
    'bu' => 'Company')
  );
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

?>
