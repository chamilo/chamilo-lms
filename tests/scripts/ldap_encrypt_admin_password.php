<?php
/* For licensing terms, see /license.txt */

/**
 * This script is to generate the encrypted password for LDAP admin to be used
 * when the parameter "ldap_encrypt_admin_password" is set to true
 * this encrypted password will be decrypted by the function api_decrypt_ldap_password
 */

//exit;

require_once __DIR__.'/../../main/inc/global.inc.php';

// Usage
echo "This generate the encryption of the password passed in parameter.".PHP_EOL;

$password = '';
if (!empty($argv[1])) {
    $password = $argv[1];
} else {
    echo "Password not defined in parameter. Please try again, passing it as argument to this script".PHP_EOL;
    echo "Usage: php ldap_encrypt_admin_password.php password".PHP_EOL;
    echo "  password    The original clear ldap admin's password".PHP_EOL;
    exit();
}

if (!empty(api_get_configuration_value('ldap_admin_password_salt'))) {
    echo "The encrypted password is : " . api_encrypt_hash($password, api_get_configuration_value('ldap_admin_password_salt')) .PHP_EOL;
} else {
    echo "There is no salt defined in app/config/configuration.php for variable 'ldap_admin_password_salt'".PHP_EOL.PHP_EOL;
}

