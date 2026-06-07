<?php

/* For licensing terms, see /license.txt */

require_once __DIR__.'/../../main/inc/global.inc.php';

$queryString = $_SERVER['QUERY_STRING'] ?? '';
$url = api_get_path(WEB_PLUGIN_PATH).'Mobidico/start.php';

if ('' !== $queryString) {
    $url .= '?'.$queryString;
}

api_location($url);
