<?php

/* For licensing terms, see /license.txt */

if (!class_exists('Plugin', false)) {
    require_once __DIR__.'/../../main/inc/global.inc.php';
}

require_once __DIR__.'/lib/customfooter_plugin.class.php';

$region = $plugin_info['current_region'] ?? '';

if ('' === $region) {
    return;
}

echo CustomFooterPlugin::create()->renderRegion((string) $region);
