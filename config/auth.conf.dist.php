<?php
/* For licensing terms, see /license.txt */

/**
 * Configuration file for all authentication methods. Uncomment and configure only the section(s) you need.
 * @package chamilo.conf.auth
 */

/**
 * Facebook
 */

/*
 * Decomment those lines and put your facebook app parameters here
 * Find them here : https://developers.facebook.com/apps/
 
$facebook_config = array(   'appId'         => 'APPID',
                            'secret'        => 'secret app',
                            'return_url'    => api_get_path(WEB_PATH).'?action=fbconnect'
);
*/
 

/**
 * Shibboleth
 */

// $shibb_login = ...;

/**
 * LDAP
 */



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
  'host' => array('1.2.3.4', '2.3.4.5', '3.4.5.6'),
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
 * OpenID
 */

$langMainInfo = 'You may login to this site using an OpenID. You may add your OpenId URLs below, and also see a list of any OpenIDs which have already been added.';
$langMainInfoDetail = '<p>OpenID is a secure way to use one user ID and password to log in to many web sites without special software, giving the same password to each site, or losing control over which information is shared with each site that you visit.</p>';
$langMainInfoDetail .= '<p>Users can create accounts using their OpenID, assign one or more OpenIDs to an existing account, and log in using an OpenID. This lowers the barrier to registration, which is good for the site, and offers convenience and security to the users. Logging in via OpenID is far more secure than cross-site logins using drupal.module.</p>';
$langMainInfoDetail .= '<p>More information on OpenID is available at <a href="http://openid.net">OpenID.net</a></p>';

/**
 * CAS
 */
//$cas = ...;
