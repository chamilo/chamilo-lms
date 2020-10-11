<?php

/* For licensing terms, see /license.txt */

exit;
if (PHP_SAPI != 'cli') {
    die('This script can only be launched from the command line');
}

require_once __DIR__.'/../../main/inc/global.inc.php';

require_once api_get_path(SYS_CODE_PATH).'install/install.lib.php';

$connection = Database::getManager()->getConnection();
fixPostGroupIds($connection);
