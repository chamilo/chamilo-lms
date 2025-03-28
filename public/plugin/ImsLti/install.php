<?php
/* For license terms, see /license.txt */

/**
 * Install the MSI/LTI Plugin.
 */
if (!api_is_platform_admin()) {
    exit('You must have admin permissions to install plugins');
}

ImsLtiPlugin::create()->install();
