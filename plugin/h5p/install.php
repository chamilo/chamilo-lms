<?php
/* For license terms, see /license.txt */

require_once __DIR__.'/h5p_plugin.class.php';

if (!api_is_platform_admin()) {
    exit('You must have admin permissions to install plugins');
}

H5PPlugin::create()->install();
