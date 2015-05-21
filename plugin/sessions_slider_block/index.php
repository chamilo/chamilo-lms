<?php
/* For license terms, see /license.txt */
/**
 * Install the Sessions Block Slider plugin
 * @package chamilo.plugin.sessions_slider_block
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 */
require_once __DIR__ . '/../../main/inc/global.inc.php';

$plugin = SessionsSliderBlockPlugin::create();

$showSlider = $plugin->get(SessionsSliderBlockPlugin::CONFIG_SHOW_SLIDER) === 'true';

if ($showSlider) {
    if (api_is_anonymous()) {
        $sessions = $plugin->getSessionList();

        foreach ($sessions as &$session) {
            $extraFieldValue = new ExtraFieldValue('session');
            $urlInfo = $extraFieldValue->get_values_by_handler_and_field_variable(
                $session['id'],
                SessionsSliderBlockPlugin::FIELD_VARIABLE_URL
            );
            $imageInfo = $extraFieldValue->get_values_by_handler_and_field_variable(
                $session['id'],
                SessionsSliderBlockPlugin::FIELD_VARIABLE_IMAGE
            );
            $session['url_in_slider'] = $urlInfo['value'];
            $session['image_in_slider'] = $imageInfo['value'];
        }

        $_template['sessions'] = $sessions;
    }
}
