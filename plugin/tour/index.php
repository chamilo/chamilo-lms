<?php

/* For licensing terms, see /license.txt */

/**
 * Config the plugin
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 * @package chamilo.plugin.tour
 */
require_once dirname(__FILE__) . '/config.php';

$pluginPath = api_get_path(PLUGIN_PATH) . 'tour/';
$pluginWebPath = api_get_path(WEB_PLUGIN_PATH) . 'tour/';

$jsonContent = file_get_contents($pluginPath . 'config/tour.json');

$json = json_decode($jsonContent, true);

$pages = array();

foreach ($json as $pageContent) {
    $pages[] = $pageContent['pageClass'];
}

$_template['pagesClassName'] = json_encode($pages);

$_template['web_path'] = array(
    'intro_css' => "{$pluginWebPath}intro.js/introjs.min.css",
    'intro_theme_css' => "{$pluginWebPath}intro.js/introjs-nassim.css",
    'intro_js' => "{$pluginWebPath}intro.js/intro.min.js",
    'steps_ajax' => "{$pluginWebPath}ajax/steps.ajax.php"
);

$_template['text'] = array(
    'start_button' => get_lang('StartTheTour'),
    'start_tour' => get_lang('HelloChamiloLMS'),
);
