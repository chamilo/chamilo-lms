<?php
/* For licensing terms, see /license.txt */
/**
 * This script is meant to update previous version the plugin.
 *
 * @package chamilo.plugin.customcertificate
 */
require_once __DIR__.'/config.php';

if (!api_is_platform_admin()) {
    exit('You must have admin permissions to install plugins');
}

CustomCertificatePlugin::create()->update();
