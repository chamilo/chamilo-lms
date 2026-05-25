<?php

/* For licensing terms, see /license.txt */

if (!function_exists('api_get_path')) {
    require_once __DIR__.'/../../main/inc/global.inc.php';
}

require_once __DIR__.'/lib/rss_plugin.class.php';

$plugin = RssPlugin::create();

$isDirectRequest = isset($_SERVER['SCRIPT_FILENAME'])
    && realpath((string) $_SERVER['SCRIPT_FILENAME']) === realpath(__FILE__);

$isRenderingOwnFullPage = defined('RSS_FULL_PAGE_RENDERING') && RSS_FULL_PAGE_RENDERING;

if (!$plugin->isEnabled()) {
    if ($isDirectRequest && !$isRenderingOwnFullPage) {
        api_not_allowed(true);
    }

    return;
}

if ($isRenderingOwnFullPage) {
    return;
}

if (!$isDirectRequest) {
    echo $plugin->renderBlock();

    return;
}

define('RSS_FULL_PAGE_RENDERING', true);

$template = new Template($plugin->get_title());
$template->assign('header', $plugin->get_title());
$template->assign('content', $plugin->renderFullPage());
$template->display_one_col_template();
