<?php
/* For licensing terms, see /license.txt */

/**
 * Redirects to "myticket.php"
 * @package chamilo.plugin.ticket
 */
/**
 * Code
 */
require_once '../config.php';
header('location:' . api_get_path(WEB_PLUGIN_PATH) . PLUGIN_NAME . '/src/myticket.php?message=success');
exit;
