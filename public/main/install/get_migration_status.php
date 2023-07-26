<?php
/* For licensing terms, see /license.txt */
ini_set('memory_limit', '1024M');

require_once __DIR__.'/../../../vendor/autoload.php';
require_once api_get_path(SYS_CODE_PATH).'install/install.lib.php';

$result = checkMigrationStatus();

$logFile = api_get_path(SYS_PATH) . '/../var/log/migration-to-2.0.log';

if (!file_exists($logFile)) {
    error_log('Initiating migration at '.date('d/m/Y H:i') . PHP_EOL, 3, $logFile);
    chmod($logFile, 0644);
}

if (!empty($result['current_migration'])) {
    $migrationPath = "Current Migration path: " . $result['current_migration'];
    error_log($migrationPath . PHP_EOL, 3, $logFile);
}

$logContent = '';
if (file_exists($logFile)) {
    // The migration log file was found
    chmod($logFile, 0644);
    $logContent = file_get_contents($logFile);
}

// Prepare the response array
$response = [
    'log_terminal' => '<pre class="terminal">' . $logContent . '</pre>',
    'progress_percentage' => $result['progress_percentage'],
];

// Send the response as JSON
header('Content-Type: application/json');
echo json_encode($response);
