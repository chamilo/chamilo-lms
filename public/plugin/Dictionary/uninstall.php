<?php

/* For licensing terms, see /license.txt */

if (!function_exists('api_is_platform_admin')) {
    require_once __DIR__.'/../../main/inc/global.inc.php';
}

if (!api_is_platform_admin()) {
    exit('You must have admin permissions to uninstall plugins');
}

require_once __DIR__.'/DictionaryPlugin.php';

DictionaryPlugin::create()->uninstall();
