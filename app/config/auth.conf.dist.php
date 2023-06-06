<?php
/* For licensing terms, see /license.txt */

/**
 * Configuration file for all authentication methods.
 * Uncomment and configure only the section(s) you need.
 * For MultiURL configuration you can override the configuration
 * of every variable by defining the same variable in app/config/configuration.php
 * The configuration in app/config/configuration.php will replace
 * the configuration in this file.
 * @package chamilo.conf.auth
 */

/**
 * Facebook
 */


/**
 * Uncomment those lines and put your facebook app parameters here
 *  Find them here : https://developers.facebook.com/apps/
 */
/*$facebook_config = array(
    'appId' => 'APPID',
    'secret' => 'secret app',
    'return_url' => api_get_path(WEB_PATH).'?action=fbconnect',
);*/

$facebookConfig = api_get_configuration_value('facebook_config');
if (!empty($facebookConfig)) {
    $facebook_config = $facebookConfig;
}

/**
 * Shibboleth
 */

// $shibb_login = ...;

$shibbLogin = api_get_configuration_value('shibb_login');
if (!empty($shibbLogin)) {
    $shibb_login = $shibbLogin;
}

/**
 * LDAP
 */



/**
 * Array of connection parameters
 **/
$extldap_config = array(
  //base domain string
  'base_dn' => 'DC=cblue,DC=be',
  //admin distinguished name - might be just a term like "elearning" rather than a whole string
  'admin_dn' => 'CN=admin,dc=cblue,dc=be',
  //admin password
  'admin_password' => 'pass',
  //ldap host
  'host' => array('1.2.3.4', '2.3.4.5', '3.4.5.6'),
  // filter
  'filter' => '', // no () arround the string
  //'port' => , default on 389
  'port' => 389,
  //protocl version (2 or 3)
  'protocol_version' => 3,
  // set this to 0 to connect to AD server
  'referrals' => 0,
  //String used to search the user in ldap. %username will ber replaced by the username.
  //See extldap_get_user_search_string() function below
  // For Active Directory: 'user_search' => 'sAMAccountName=%username%',  // no () arround the string
  // For OpenLDAP: 'user_search' => 'uid=%username%',  // no () arround the string
  'user_search' => 'uid=%username%',
  //encoding used in ldap (most common are UTF-8 and ISO-8859-1
  'encoding' => 'UTF-8',
  //Set to true if user info have to be update at each login
  'update_userinfo' => true,
  // Define user_search_import_all_users variable to control main/auth/external_login/ldap.inc.php
  // Active Directory: 'user_search_import_all_users' => 'sAMAccountName=$char1$char2*'
  // OpenLDAP: 'user_search_import_all_users' => 'uid=*'
  'user_search_import_all_users' => 'uid=*'
);

$ldapConfig = api_get_configuration_value('extldap_config');
if (!empty($ldapConfig)) {
    $extldap_config = $ldapConfig;
}

/**
 * Matching array between chamilo user info and ldap user info
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
 * Please Note that Chamilo expects some attributes that might not be present in your user ldap record
 *
 **/
$extldap_user_correspondance = array(
    'firstname' => 'givenName',
    'lastname' => 'sn',
    'email' => 'mail',
    'auth_source' => '!extldap',
    'username' => 'uid',
    'language' => '!english',
    'password' => 'userPassword',
    'status' => '!5', // Forcing status to 5; To change this set 'status' => 'func' and implement an extldap_get_status($ldap_array) function
    'active' => '!1', // Forcing active to 1; To change this set 'status' => 'func' and implement an extldap_get_active($ldap_array) function
    'admin' => 'func' // Using the extldap_get_admin() function (defined in main/auth/external_login/ldap.inc.php) to check if user is an administrator based on some ldap user record value
    /* Extras example
    'extra' => array(
        'title' => 'title',
        'globalid' => 'employeeID',
        'department' => 'department',
        'country' => 'co',
        'bu' => 'Company',
        'cas_user' => 'uid',
    ) */
);

$ldapUserCorrespondance = api_get_configuration_value('extldap_user_correspondance');
if (!empty($ldapUserCorrespondance)) {
    $extldap_user_correspondance = $ldapUserCorrespondance;
}

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
$cas = [
    'service_base_url' => '', //The base url of your service required by phpCAS since compliance with
    //https://github.com/advisories/GHSA-8q72-6qq8-xv64 in version 1.6
    //with this https://github.com/apereo/phpCAS/commit/b759361d904a2cb2a3bcee9411fc348cfde5d163
    //It should be the URL of you Chamilo or an array of all the URLs in case of a multiURL installation including https and / at the end
    'force_redirect' => false,
    'replace_login_form' => false,
    //'skip_force_redirect_in' => ['/main/webservices'],
    // 'verbose' => false,
    // 'debug' => '/var/log/cas_debug.log',
    'noCasServerValidation' => true, // set to false in production
    // 'fixedServiceURL' => false, // false by default, set to either true or to the service URL string if needed
    // sites might also need proxy_settings in configuration.php
];

$casConfig = api_get_configuration_value('cas');
if (!empty($casConfig)) {
    $cas = $casConfig;
}
