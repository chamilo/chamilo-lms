<?php
/* For license terms, see /license.txt */

/**
 * Uninstall the MSI/LTI Plugin.
 */
if (!api_is_platform_admin()) {
    exit('You must have admin permissions to uninstall plugins');
}

ImsLtiPlugin::create()->uninstall();
