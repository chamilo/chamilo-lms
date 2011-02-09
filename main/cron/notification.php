<?php
require_once '../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'notification.lib.php';
$notify = new Notification();
$notify->send();