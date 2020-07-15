<?php
/* For license terms, see /license.txt */

if (!api_is_platform_admin()) {
    die('You must have admin permissions to install plugins');
}

ZoomPlugin::create()->install();
