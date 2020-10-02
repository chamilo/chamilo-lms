<?php
/* For license terms, see /license.txt */

require_once 'boostTitle.php';

if (!api_is_platform_admin()) {
    die('You must have admin permissions to install plugins');
}
boostTitle::create()->install();
