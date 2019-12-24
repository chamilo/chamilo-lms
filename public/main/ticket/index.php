<?php

/* For licensing terms, see /license.txt */

/**
 * Redirects to "myticket.php".
 */
require_once __DIR__.'/../inc/global.inc.php';
header('Location:'.api_get_path(WEB_CODE_PATH).'ticket/tickets.php');
exit;
