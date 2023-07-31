<?php
/* For license terms, see /license.txt */

/**
 * Uninstall the Ai Helper Plugin.
 *
 * @package chamilo.plugin.ai_helper
 */
require_once __DIR__.'/../../main/inc/global.inc.php';
require_once __DIR__.'/AiHelperPlugin.php';

if (!api_is_platform_admin()) {
    exit('You must have admin permissions to install plugins');
}

AiHelperPlugin::create()->uninstall();
