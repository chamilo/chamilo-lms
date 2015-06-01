<?php
/* For license terms, see /license.txt */
/**
 * Get the plugin info
 * @package chamilo.plugin.sessions_slider_block
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 */
require_once __DIR__ . '/config.php';

$plugin_info = SessionsSliderBlockPlugin::create()->get_info();

if (api_is_anonymous()) {
    $plugin_info['templates'] = ['template/slider.tpl'];
}
