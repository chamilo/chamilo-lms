<?php
/* For license terms, see /license.txt */

require __DIR__.'/../../../../main/inc/global.inc.php';

if (PHP_SAPI !== 'cli') {
    exit('Run this script through the command line or comment this line in the code');
}

$command = new AzureSyncUsergroupsCommand();

try {
    foreach ($command() as $str) {
        printf("%d - %s".PHP_EOL, time(), $str);
    }
} catch (Exception $e) {
    printf('%s - Exception: %s'.PHP_EOL, time(), $e->getMessage());
}
