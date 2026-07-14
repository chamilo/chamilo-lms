<?php

declare(strict_types=1);

/* Deprecated compatibility entry point. */

require_once __DIR__.'/../inc/global.inc.php';

api_block_anonymous_users();
$query = [];
foreach (['project_id', 'cid', 'sid', 'gid', 'course_id', 'session_id'] as $name) {
    if (isset($_GET[$name])) {
        $query[$name] = (string) $_GET[$name];
    }
}
header('Location: '.api_get_path(WEB_PATH).'tickets/create'.([] !== $query ? '?'.http_build_query($query) : ''));

exit;
