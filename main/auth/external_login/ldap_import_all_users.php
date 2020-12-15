<?php
/* For licensing terms, see /license.txt */
/**
 * This script executes the importation of all users in the LDAP repository
 * into Chamilo.
 *
 * @package chamilo.auth.ldap
 */
/**
 * Init.
 */
if (PHP_SAPI != 'cli') {
    exit('For security reasons, this script can only be launched from cron or from the command line');
}

require __DIR__.'/../../inc/global.inc.php';
require __DIR__.'/ldap.inc.php';
require __DIR__.'/../../inc/conf/auth.conf.php';
/**
 * Code execution.
 */
extldap_import_all_users();
