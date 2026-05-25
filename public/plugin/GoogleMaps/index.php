<?php

/* For licensing terms, see /license.txt */

require_once __DIR__.'/../../main/inc/global.inc.php';
require_once __DIR__.'/config.php';

api_protect_admin_script();

$plugin = GoogleMapsPlugin::create();

if (!$plugin->isEnabled()) {
    api_not_allowed(true);
}

header('Location: '.$plugin->getMapUrl());
exit;
