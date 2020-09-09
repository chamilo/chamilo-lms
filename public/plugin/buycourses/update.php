<?php
/* For licensing terms, see /license.txt */
/**
 * Update the plugin.
 */
require_once __DIR__.'/config.php';

if (!api_is_platform_admin()) {
    die('You must have admin permissions to install plugins');
}

BuyCoursesPlugin::create()->update();
