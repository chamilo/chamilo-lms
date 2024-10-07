<?php
/* For licensing terms, see /license.txt */
ini_set('memory_limit', '1024M');
require_once __DIR__.'/../../../vendor/autoload.php';
require_once api_get_path(SYS_CODE_PATH).'install/install.lib.php';

$result = checkMigrationStatus();

// Prepare the response array
$response = [
    'progress_percentage' => $result['progress_percentage'],
    'current_migration' => $result['current_migration'],
];

// Send the response as JSON
header('Content-Type: application/json');
echo json_encode($response);
