<?php
/* For license terms, see /license.txt */

/**
 * This script initiates a test2pdf plugin.
 *
 * @package chamilo.plugin.test2pdf
 */
require_once __DIR__.'/../../vendor/autoload.php';

$course_plugin = 'test2pdf'; //needed in order to load the plugin lang variables
require_once __DIR__.'/config.php';

$tool_name = get_lang('Test2pdf');

$plugin = Test2pdfPlugin::create();
$enable = $plugin->get('enable_plugin') == 'true';

if ($enable) {
    $url = 'src/view-pdf.php?'.api_get_cidreq();
    header('Location: '.$url);
    exit;
} else {
    Display::addFlash(Display::return_message($plugin->get_lang('PluginDisabledFromAdminPanel')));
    $url = api_get_path(WEB_PATH).'courses/'.api_get_course_id().'/index.php?id_session='.api_get_session_id();
    header('Location:'.$url);
    exit;
}
