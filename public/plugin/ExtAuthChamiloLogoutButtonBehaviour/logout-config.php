<?php

declare(strict_types=1);

require_once __DIR__.'/../../main/inc/global.inc.php';
require_once __DIR__.'/src/ExtAuthChamiloLogoutButtonBehaviourPlugin.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$plugin = ExtAuthChamiloLogoutButtonBehaviourPlugin::create();

echo json_encode(
    $plugin->getLogoutConfigurationForCurrentUser(),
    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
);
