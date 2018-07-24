<?php
/* For license terms, see /license.txt */

/**
 * Uninstall the MSI/LTI Plugin.
 *
 * @package chamilo.plugin.ims_lti
 */

if (!api_is_platform_admin()) {
    die('You must have admin permissions to uninstall plugins');
}

ImsLtiPlugin::create()->uninstall();
