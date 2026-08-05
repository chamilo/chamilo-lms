<?php

/* For licensing terms, see /license.txt */

if (!function_exists('api_get_path')) {
    require_once __DIR__.'/../../main/inc/global.inc.php';
}

require_once __DIR__.'/DictionaryPlugin.php';

$plugin = DictionaryPlugin::create();

$isDirectRequest = isset($_SERVER['SCRIPT_FILENAME'])
    && realpath((string) $_SERVER['SCRIPT_FILENAME']) === realpath(__FILE__);

$isRenderingOwnFullPage = defined('DICTIONARY_FULL_PAGE_RENDERING')
    && DICTIONARY_FULL_PAGE_RENDERING;

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

define('DICTIONARY_FULL_PAGE_RENDERING', true);

$keyword = isset($_GET['q']) ? trim(Security::remove_XSS((string) $_GET['q'])) : '';

$content = $plugin->renderFullPage($keyword);

$template = new Template($plugin->get_title());
$template->assign('header', $plugin->get_title());
$template->assign('content', $content);
$template->display_one_col_template();
