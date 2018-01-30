<?php

/**
 * This script initiates a test2pdf plugin
 * @package chamilo.plugin.test2pdf
 */

require __DIR__ . '/../../vendor/autoload.php';

$course_plugin = 'test2pdf'; //needed in order to load the plugin lang variables
require_once dirname(__FILE__).'/config.php';

//$_setting['student_view_enabled'] = 'false';

$tool_name = get_lang('Test2pdf');
$tpl = new Template($tool_name);

$plugin = Test2pdfPlugin::create();
$t2p_plugin = $plugin->get('enable_plugin');

if ($t2p_plugin == "true") {
    $url = 'src/view-pdf.php?'.api_get_cidreq();
    header('Location: ' . $url);
    exit;
} else {
    echo get_lang('PluginDisabledFromAdminPanel');
}
