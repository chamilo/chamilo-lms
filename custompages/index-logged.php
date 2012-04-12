<?php 
require_once(dirname(__FILE__).'/../main/inc/global.inc.php');

$www = api_get_path('WEB_PATH');

header("Location: $www/user_portal.php");