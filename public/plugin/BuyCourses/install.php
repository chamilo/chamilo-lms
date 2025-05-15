<?php
/* For license terms, see /license.txt */
/**
 * This script is included by main/admin/settings.lib.php and generally
 * includes things to execute in the main database (settings_current table).
 */
/**
 * Initialization.
 */
require_once __DIR__.'/config.php';
if (!api_is_platform_admin()) {
    exit('You must have admin permissions to install plugins');
}
BuyCoursesPlugin::create()->install();
