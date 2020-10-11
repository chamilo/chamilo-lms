<?php

/* For licensing terms, see /license.txt */

// Check extra_field authors to lp and company to user
require_once 'CheckExtraFieldAuthorsCompanyPlugin.php';

if (!api_is_platform_admin()) {
    die('You must have admin permissions to install plugins');
}
CheckExtraFieldAuthorsCompanyPlugin::create()->install();
