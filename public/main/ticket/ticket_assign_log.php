<?php

declare(strict_types=1);

/* Deprecated compatibility entry point. */

require_once __DIR__.'/../inc/global.inc.php';

api_block_anonymous_users();
$ticketId = isset($_REQUEST['ticket_id']) ? (int) $_REQUEST['ticket_id'] : 0;
header('Location: '.api_get_path(WEB_PATH).($ticketId > 0 ? 'tickets/'.$ticketId : 'tickets'));

exit;
