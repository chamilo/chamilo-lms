<?php
/* For license terms, see /license.txt */
/**
 * Uninstall the Sessions Block Slider plugin
 * @package chamilo.plugin.sessions_slider_block
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 */
require_once __DIR__ . '/../../main/inc/global.inc.php';

if (!api_is_platform_admin()) {
    die('You must have admin permissions to uninstall plugins');
}

$plugin_info = SessionsSliderBlockPlugin::create()->uninstall();
