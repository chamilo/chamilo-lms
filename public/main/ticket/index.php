<?php

declare(strict_types=1);

/* Deprecated compatibility entry point. */

require_once __DIR__.'/../inc/global.inc.php';

api_block_anonymous_users();
header('Location: '.api_get_path(WEB_PATH).'tickets');

exit;
