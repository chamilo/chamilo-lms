<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */
/**
 * Update the plugin.
 */
require_once __DIR__.'/config.php';

$plugin = BuyCoursesPlugin::create();

if (!api_is_platform_admin()) {
    exit($plugin->get_lang('AdminPermissionsRequired'));
}

$plugin->update();

header('Location: '.api_get_path(WEB_PLUGIN_PATH).'BuyCourses');
exit;
