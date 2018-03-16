<?php
/* For licensing terms, see /license.txt */
/**
 * Config the plugin.
 *
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 *
 * @package chamilo.plugin.tour
 */
require_once __DIR__.'/config.php';

$pluginPath = api_get_path(SYS_PLUGIN_PATH).'tour/';
$pluginWebPath = api_get_path(WEB_PLUGIN_PATH).'tour/';

$userId = api_get_user_id();

$tourPlugin = Tour::create();
$config = $tourPlugin->getTourConfig();
$showTour = $tourPlugin->get('show_tour') === 'true';

if ($showTour) {
    $pages = [];

    foreach ($config as $pageContent) {
        $pages[] = [
            'pageClass' => $pageContent['pageClass'],
            'show' => $tourPlugin->checkTourForUser($pageContent['pageClass'], $userId),
        ];
    }

    $theme = $tourPlugin->get('theme');

    $_template['show_tour'] = $showTour;

    $_template['pages'] = json_encode($pages);

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
}
