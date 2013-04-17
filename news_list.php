<?php
/* For licensing terms, see /license.txt */

//Temporal hack to redirect calls to the new web/index.php
require_once 'main/inc/global.inc.php';
$id = isset($_GET['id']) ? intval($_GET['id']) : null;
$path = api_get_path(WEB_PUBLIC_PATH);
header('Location: '.$path.'news/'.$id);
exit;