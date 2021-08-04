<?php

/* For licensing terms, see /license.txt */

exit;

if (PHP_SAPI != 'cli') {
    die('This script can only be executed from the command line');
}

require_once __DIR__.'/../../main/inc/global.inc.php';

$userInfo = UserManager::logInAsFirstAdmin();

if (api_is_platform_admin()) {
    echo 'Logged as admin user: '.$userInfo['complete_name'];
} else {
    echo 'NOT logged as admin ';
}
