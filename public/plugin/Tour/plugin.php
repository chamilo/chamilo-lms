<?php

/* For licensing terms, see /license.txt */

/**
 * Show the JavaScript template in the web pages.
 *
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 */
require_once __DIR__.'/config.php';

$tourPlugin = Tour::create();

$plugin_info = array_merge($plugin_info ?? [], $tourPlugin->get_info());
$plugin_info['plugin_class'] = Tour::class;

// Only register the frontend template when the plugin is enabled
// for the current access URL and the feature flag is active.
if ($tourPlugin->isTourAvailable()) {
    $plugin_info['templates'] = ['views/script.tpl'];
}
