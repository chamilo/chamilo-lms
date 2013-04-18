<?php
//Temporal hack to redirect calls to the new web/index.php
require_once 'main/inc/global.inc.php';
$path = api_get_path(WEB_PUBLIC_PATH);
$array_keys = isset($_GET) ? array_keys($_GET) : null;

if (!empty($array_keys)) {
    $username 	= Security::remove_XSS(substr($array_keys[0], 0, 100)); // max len of an username
    header('Location: '.$path.'user/'.$username);
}
exit;