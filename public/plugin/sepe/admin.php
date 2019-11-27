<?php
require_once __DIR__.'/config.php';

$plugin = SepePlugin::create();
$enable = $plugin->get('sepe_enable') == 'true';
$pluginPath = api_get_path(WEB_PLUGIN_PATH).'sepe/src/sepe-administration-menu.php';

if ($enable && api_is_platform_admin()) {
    header('Location:'.$pluginPath);
    exit;
} else {
    header('Location: ../../index.php');
    exit;
}
