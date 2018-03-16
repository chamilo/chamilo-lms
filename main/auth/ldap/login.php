<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 *    Users trying to login, who already exist in the Chamilo database
 *    and have ldap as authentication type get verified here.
 *
 * @author Roan Embrechts
 *
 * @package chamilo.auth.ldap
 */
/**
 * An external authentification module needs to set
 * - $loginFailed
 * - $uidReset
 * - $_user['user_id']
 * - register the $_user['user_id'] in the session.
 *
 * As the LDAP code shows, this is not as difficult as you might think.
 * LDAP authentification module
 * this calls the loginWithLdap function
 * from the LDAP library, and sets a few
 * variables based on the result.
 */

//require_once('../../inc/global.inc.php'); - this script should be loaded by the /index.php script anyway, so global is already loaded

require_once 'authldap.php';
$loginLdapSucces = ldap_login($login, $password);

if ($loginLdapSucces) {
    $loginFailed = false;
    $uidReset = true;
    $_user['user_id'] = $uData['user_id'];
    Session::write('_uid', $_uid);
    // Jand: copied from event_login in events.lib.php to enable login statistics:
    Event::eventLogin($uData['user_id']);
} else {
    $loginFailed = true;
    unset($_user['user_id']);
    $uidReset = false;
}
