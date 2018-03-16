<?php
/* For licensing terms, see /license.txt */

/**
 * Redirects to "myticket.php".
 *
 * @package chamilo.plugin.ticket
 */
require_once __DIR__.'/../inc/global.inc.php';
header('Location:'.api_get_path(WEB_CODE_PATH).'ticket/tickets.php');
exit;
