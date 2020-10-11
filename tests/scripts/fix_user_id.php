<?php

/* For licensing terms, see /license.txt */

/**
 * Temporary fix to set user.user_id to the same as user.id
 */
exit;
if (PHP_SAPI != 'cli') {
    die('This script can only be executed from the command line');
}
require __DIR__.'/../../main/inc/conf/configuration.php';

$dbh = mysql_connect(
    $_configuration['db_host'],
    $_configuration['db_user'],
    $_configuration['db_password']
);
$db = mysql_select_db($_configuration['main_database']);
$sql = "UPDATE user SET user_id = id";
mysql_query($sql);
