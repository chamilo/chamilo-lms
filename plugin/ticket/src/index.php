<?php
/* For licensing terms, see /license.txt */

/**
 * Redirects to "myticket.php"
 * @package chamilo.plugin.ticket
 */

require_once __DIR__.'/../config.php';
header('Location:' . api_get_path(WEB_PLUGIN_PATH) . PLUGIN_NAME . '/src/myticket.php');
exit;
