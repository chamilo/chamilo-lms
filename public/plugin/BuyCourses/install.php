<?php

declare(strict_types=1);

/* For license terms, see /license.txt */
/**
 * This script is included by main/admin/settings.lib.php and generally
 * includes things to execute in the main database.
 */
/**
 * Initialization.
 */
require_once __DIR__.'/config.php';
$plugin = BuyCoursesPlugin::create();

if (!api_is_platform_admin()) {
    exit($plugin->get_lang('AdminPermissionsRequired'));
}
$plugin->install();
