<?php
require_once dirname(__FILE__).'/config.php';

$plugin = SepePlugin::create();
$enable = $plugin->get('sepe_enable');
$pluginPath = api_get_path(WEB_PLUGIN_PATH).'sepe/src/menu_sepe_administracion.php';

if ($enable == "true" && api_is_platform_admin()) {
    header('Location:'.$pluginPath);
} else {
	header('Location: ../../index.php');	
}

