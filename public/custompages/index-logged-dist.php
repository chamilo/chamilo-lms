<?php
/* For licensing terms, see /license.txt */
/**
 * Redirect to normal Chamilo.
 *
 * @package chamilo.custompages
 */
require_once api_get_path(SYS_PATH).'main/inc/global.inc.php';

$www = api_get_path('WEB_PATH');
/**
 * Redirect.
 */
header("Location: $www/user_portal.php");
exit;
