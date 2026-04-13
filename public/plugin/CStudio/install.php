<?php

declare(strict_types=1);
/* For license terms, see /license.txt */

require_once __DIR__.'/teachdoc_hub.php';

if (!api_is_platform_admin()) {
    exit('You must have admin permissions to install plugins');
}

teachdoc_hub::create()->install();
