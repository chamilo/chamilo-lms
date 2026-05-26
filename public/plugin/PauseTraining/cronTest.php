<?php

/* For licensing terms, see /license.txt */

require_once __DIR__.'/../../main/inc/global.inc.php';

if ('cli' !== PHP_SAPI) {
    api_block_anonymous_users();

    if (!api_is_platform_admin()) {
        api_not_allowed(true);
    }
}

PauseTraining::create()->runCronTest();
