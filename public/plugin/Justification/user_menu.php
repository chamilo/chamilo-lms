<?php
/* For license terms, see /license.txt */

require_once __DIR__.'/../../main/inc/global.inc.php';

header('Content-Type: application/json');

$plugin = Justification::create();
$userId = api_get_user_id();

if ($userId <= 0 || !$plugin->isEnabled()) {
    echo json_encode([
        'enabled' => false,
    ]);
    exit;
}

echo json_encode([
    'enabled' => true,
    'label' => $plugin->get_lang('MyJustifications'),
    'url' => api_get_path(WEB_PLUGIN_PATH).'Justification/upload.php',
]);
exit;
