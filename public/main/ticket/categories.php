<?php

declare(strict_types=1);

/* Deprecated compatibility entry point. */

require_once __DIR__.'/../inc/global.inc.php';

api_protect_admin_script(true);
$query = ['section' => 'categories'];
if (isset($_GET['project_id'])) {
    $query['project_id'] = (string) (int) $_GET['project_id'];
}
header('Location: '.api_get_path(WEB_PATH).'tickets/settings?'.http_build_query($query));

exit;
