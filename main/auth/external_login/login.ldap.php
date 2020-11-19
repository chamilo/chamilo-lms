<?php

/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * This file is included in main/inc/local.inc.php at user login if the user have 'extldap' in
 * his auth_source field instead of platform.
 *
 * Variables that can be used :
 *    - $login : string containing the username posted by the user
 *    - $password : string containing the password posted by the user
 *    - $uData : associative array with those keys :
 *           -username
 *           -password
 *           -auth_source
 *           -active
 *           -expiration_date
 *
 * If login succeeds, we have 2 choices :
 *    1.  - set $loginFailed to false,
 *        - set $_SESSION['_user']['user_id'] with the Chamilo user_id
 *        - set $uidReset to true
 *        - upgrade user info in chamilo database if needed
 *        - let the script local.inc.php continue
 *
 *    2.  - set $_SESSION['_user']['user_id'] with the Chamilo user_id
 *        - set $_SESSION['_user']['uidReset'] to true
 *        - upgrade user info in chamilo database if needed
 *        - redirect to any page and let local.inc.php do the magic
 *
 * If login fails we have to redirect to index.php with the right message
 * Possible messages are :
 *  - index.php?loginFailed=1&error=access_url_inactive
 *  - index.php?loginFailed=1&error=account_expired
 *  - index.php?loginFailed=1&error=account_inactive
 *  - index.php?loginFailed=1&error=user_password_incorrect
 *  - index.php?loginFailed=1&error=unrecognize_sso_origin');
 */
require_once __DIR__.'/ldap.inc.php';
require_once __DIR__.'/functions.inc.php';

$debug = false;
if ($debug) {
    error_log('Entering login.ldap.php');
}
$ldap_user = extldap_authenticate($login, $password);
if ($ldap_user !== false) {
    if ($debug) {
        error_log('extldap_authenticate works');
    }
    $chamilo_user = extldap_get_chamilo_user($ldap_user);
    //userid is not on the ldap, we have to use $uData variable from local.inc.php
    $chamilo_user['user_id'] = $uData['user_id'];
    if ($debug) {
        error_log("chamilo_user found user_id: {$uData['user_id']}");
    }

    //Update user info
    if (isset($extldap_config['update_userinfo']) && $extldap_config['update_userinfo']) {
        external_update_user($chamilo_user);
        if ($debug) {
            error_log("Calling external_update_user");
        }
    }

    $loginFailed = false;
    $_user['user_id'] = $chamilo_user['user_id'];
    $_user['status'] = (isset($chamilo_user['status']) ? $chamilo_user['status'] : 5);
    $_user['uidReset'] = true;
    Session::write('_user', $_user);
    $uidReset = true;
    $logging_in = true;
    Event::eventLogin($_user['user_id']);
} else {
    if ($debug) {
        error_log('extldap_authenticate error');
    }
    $loginFailed = true;
    $uidReset = false;
    if (isset($_user) && isset($_user['user_id'])) {
        unset($_user['user_id']);
    }
}
