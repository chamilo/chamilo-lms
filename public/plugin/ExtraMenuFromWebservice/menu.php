<?php
/* For licensing terms, see /license.txt */

declare(strict_types=1);

require_once __DIR__.'/config.php';

header('Content-Type: application/json; charset=UTF-8');

$plugin = ExtraMenuFromWebservicePlugin::create();

echo json_encode(
    $plugin->getVueMenuResponse(),
    JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
);
