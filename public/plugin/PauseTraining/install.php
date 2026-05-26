<?php
/* For license terms, see /license.txt */

require_once __DIR__.'/config.php';

api_block_anonymous_users();

if (!api_is_platform_admin()) {
    api_not_allowed(true);
}

PauseTraining::create()->install();
