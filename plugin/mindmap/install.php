<?php
/* For license terms, see /license.txt */

require_once __DIR__.'/mindmap_plugin.class.php';

if (!api_is_platform_admin()) {
    exit('You must have admin permissions to install plugins');
}

MindmapPlugin::create()->install();
