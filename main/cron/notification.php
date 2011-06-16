<?php
/* For licensing terms, see /license.txt */
/**
 * @package chamilo.notification
 * @author Julio Montoya <gugli100@gmail.com>
 */

$language_file = array('userInfo');

require_once '../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'notification.lib.php';
$notify = new Notification();
$notify->send();