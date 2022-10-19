<?php
/* For license terms, see /license.txt */

require_once __DIR__.'/../../../main/inc/global.inc.php';
require_once __DIR__.'/../src/LtiProvider.php';
require_once __DIR__.'/../LtiProviderPlugin.php';

LtiProvider::create()->logout();
header('Location: '.api_get_path(WEB_PATH));
exit;
