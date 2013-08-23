<?php
/** This script is only use when using the console.php chamilo:install command */
/**
 * Database settings
 */
// Your MySQL server
$_configuration['db_host']     = 'localhost';
// Your MySQL username
$_configuration['db_user']     = 'root';
// Your MySQL password
$_configuration['db_password'] = 'root';

// Prefix for course tables (IF NOT EMPTY, can be replaced by another prefix, else leave empty)
/*$_configuration['table_prefix']          = '';
// prefix all created bases (for courses) with this string
$_configuration['db_prefix']             = '';*/
// main Chamilo database
$_configuration['main_database']         = 'chamilo';

/**
 * Directory settings
 */
// URL to the root of your Chamilo installation, e.g.: http://www.mychamilo.com/
// Comment this line if you want to enable multiple URLs
$_configuration['root_web']       = 'http://localhost/chamilo';
$_configuration['root_sys']       = '/var/www/chamilo';

/**
 * Misc. settings
 */
// Security word for password recovery
$_configuration['security_key']      = md5(uniqid(rand().time()));
// Hash function method
$_configuration['password_encryption']      = 'sha1';
// You may have to restart your web server if you change this
$_configuration['session_stored_in_db']     = false;
// Session lifetime
$_configuration['session_lifetime']         = 3600;
// Activation for multi-url access
//$_configuration['multiple_access_urls']   = true;
//Deny the elimination of users
$_configuration['deny_delete_users']        = false;
//Prevent all admins from using the "login_as" feature
$_configuration['login_as_forbidden_globally'] = false;
// Version settings
$_configuration['system_version']           = '1.10.0';
