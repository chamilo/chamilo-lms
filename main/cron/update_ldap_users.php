<?php

/* For licensing terms, see /license.txt */

exit;

require_once __DIR__.'/../../app/config/auth.conf.php';
require_once __DIR__.'/../auth/external_login/ldap.inc.php';
require_once __DIR__.'/../auth/external_login/functions.inc.php';

global $extldap_config;

if (empty($extldap_config)) {
    echo "$extldap_config not found";
    exit;
}

$ds = extldap_connect();
if (!$ds) {
    echo 'ldap not connected';
    exit;
}

$table = Database::get_main_table(TABLE_MAIN_USER);
$sql = "SELECT * FROM $table WHERE auth_source = 'ldap' ";
$result = Database::query($sql);
while ($user = Database::fetch_array($result, 'ASSOC')) {
    echo "Loading user #".$user['id'].PHP_EOL;
    $username = $user['username'];
    $ldapbind = @ldap_bind($ds, $extldap_config['admin_dn'], $extldap_config['admin_password']);
    $user_search = extldap_get_user_search_string($username);
    $sr = ldap_search($ds, $extldap_config['base_dn'], $user_search);
    if (!$sr) {
        echo "Username not found in LDAP: ".$username.PHP_EOL;
        continue;
    }
    $users = ldap_get_entries($ds, $sr);
    for ($key = 0; $key < $users['count']; $key++) {
        $ldapUser = $users[$key];
        print_r($ldapUser).PHP_EOL;
    }
}
