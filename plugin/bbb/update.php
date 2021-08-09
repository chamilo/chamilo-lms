<?php
/* For licensing terms, see /license.txt */
/**
 * Update the plugin.
 *
 * @package chamilo.plugin.bigbluebutton
 */
require_once __DIR__.'/config.php';

if (!api_is_platform_admin()) {
    exit('You must have admin permissions to install plugins');
}

BBBPlugin::create()->update();
