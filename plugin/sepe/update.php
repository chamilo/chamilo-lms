<?php
/* For licensing terms, see /license.txt */
/**
 * Config the plugin.
 *
 * @package chamilo.plugin.sepe
 */
require_once __DIR__.'/config.php';

if (!api_is_platform_admin()) {
    die('You must have admin permissions to install plugins');
}

SepePlugin::create()->update();
