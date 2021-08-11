<?php
/* For license terms, see /license.txt */

/**
 * Install the Lti/Provider Plugin.
 *
 * @package chamilo.plugin.lti_provider
 */
require_once __DIR__.'/../../main/inc/global.inc.php';
require_once __DIR__.'/LtiProviderPlugin.php';

if (!api_is_platform_admin()) {
    exit('You must have admin permissions to install plugins');
}

LtiProviderPlugin::create()->install();
