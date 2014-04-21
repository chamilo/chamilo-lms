<?php
/* For licensing terms, see /license.txt */
/**
 * This script executes the importation of all users in the LDAP repository
 * into Chamilo 
 * @package chamilo.auth.ldap
 */
/**
 * Init
 */
if (PHP_SAPI != 'cli') {
    die ('For security reasons, this script can only be launched from cron or from the command line');
}
use \ChamiloSession as Session;

require dirname(__FILE__) . '/../../inc/global.inc.php';
require dirname(__FILE__) . '/ldap.inc.php';
require dirname(__FILE__) . '/../../inc/conf/auth.conf.php';
/**
 * Code execution
 */
extldap_import_all_users();
