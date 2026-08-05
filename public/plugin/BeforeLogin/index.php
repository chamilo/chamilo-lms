<?php

/* For licensing terms, see /license.txt */

if (!function_exists('api_get_path')) {
    require_once __DIR__.'/../../main/inc/global.inc.php';
}

require_once __DIR__.'/BeforeLoginPlugin.php';

$plugin = BeforeLoginPlugin::create();

$isDirectRequest = isset($_SERVER['SCRIPT_FILENAME'])
    && realpath((string) $_SERVER['SCRIPT_FILENAME']) === realpath(__FILE__);

if (!$plugin->isEnabled()) {
    if ($isDirectRequest) {
        api_not_allowed(true);
    }

    return;
}

if (!$isDirectRequest) {
    echo $plugin->renderGate('embedded');

    return;
}

$content = $plugin->renderStandalonePage();

$template = new Template($plugin->get_title());
$template->assign('header', $plugin->get_title());
$template->assign('content', $content);
$template->display_one_col_template();
