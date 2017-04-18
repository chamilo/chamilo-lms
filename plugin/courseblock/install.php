<?php
/* For license terms, see /license.txt */

require_once __DIR__.'/config.php';

if (!api_is_platform_admin()) {
    die ('You must have admin permissions to install plugins');
}
CourseBlockPlugin::create()->install();
