<?php

declare(strict_types=1);

/* Deprecated compatibility entry point. */

require_once __DIR__.'/../inc/global.inc.php';

api_protect_admin_script(true);
header('Location: '.api_get_path(WEB_PATH).'tickets/settings?section=statuses');

exit;
