<?php

declare(strict_types=1);
/* For license terms, see /license.txt */

/**
 * This script is included by main/admin/settings.lib.php when unselecting a plugin
 * and is meant to remove things installed by the install.php script in both
 * the global database and the courses tables.
 */
/**
 * Queries.
 */
require_once __DIR__.'/config.php';

$plugin = BuyCoursesPlugin::create();

if (!api_is_platform_admin()) {
    exit($plugin->get_lang('AdminPermissionsRequired'));
}

$plugin->uninstall();
