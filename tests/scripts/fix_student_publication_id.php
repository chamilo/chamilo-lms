<?php
/* For license terms, see /license.txt */
die('Remove the "die()" statement on line '.__LINE__.' to execute this script'.PHP_EOL);
if (PHP_SAPI != 'cli') {
    die('This script can only be launched from the command line');
}

require_once __DIR__.'/../../public/main/inc/global.inc.php';

require_once api_get_path(SYS_CODE_PATH).'install/install.lib.php';

$connection = Database::getManager()->getConnection();
fixPostGroupIds($connection);
