<?php

/* For licensing terms, see /license.txt */
/**
 * Config the plugin.
 *
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 */
require_once __DIR__.'/config.php';

$pluginPath = api_get_path(SYS_PLUGIN_PATH).'Tour/';
$pluginWebPath = api_get_path(WEB_PLUGIN_PATH).'Tour/';

$userId = api_get_user_id();

$tourPlugin = Tour::create();
$showTour = $tourPlugin->isTourAvailable();

// Always expose the flag so the template can decide safely.
$_template['show_tour'] = $showTour;

// Stop here when the plugin is disabled or the feature flag is off.
if (!$showTour) {
    return;
}

$config = $tourPlugin->getTourConfig();
$pages = [];

foreach ($config as $pageContent) {
    if (!is_array($pageContent) || empty($pageContent['pageClass'])) {
        continue;
    }

    $pageClass = trim((string) $pageContent['pageClass']);

    if ('' === $pageClass) {
        continue;
    }

    $pages[] = [
        'pageClass' => $pageClass,
        'show' => $tourPlugin->checkTourForUser($pageClass, $userId),
    ];
}

$theme = $tourPlugin->get('theme');

$_template['pages'] = json_encode($pages);
$_template['tour_security_token'] = Security::get_existing_token();
$_template['web_path'] = [
    'intro_css' => "{$pluginWebPath}intro.js/introjs.min.css",
    'intro_theme_css' => null,
    'intro_js' => "{$pluginWebPath}intro.js/intro.min.js",
    'steps_ajax' => "{$pluginWebPath}ajax/steps.ajax.php",
    'save_ajax' => "{$pluginWebPath}ajax/save.ajax.php",
];

if (file_exists("{$pluginPath}intro.js/introjs-$theme.css")) {
    $_template['web_path']['intro_theme_css'] = "{$pluginWebPath}intro.js/introjs-$theme.css";
}
