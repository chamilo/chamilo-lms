<?php

/* For license terms, see /license.txt */

require_once 'CardGame.php';

if (!api_is_platform_admin()) {
    exit('You must have admin permissions to uninstall plugins');
}
CardGame::create()->uninstall();
