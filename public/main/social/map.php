<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

api_block_anonymous_users();

// Preserve old links without bypassing the modern map's portal and field visibility checks.
header('Location: '.api_get_path(WEB_PATH).'social/map');

exit;
