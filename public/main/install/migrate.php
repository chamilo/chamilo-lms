<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Installer\InstallerGate;
use Chamilo\CoreBundle\Installer\InstallerState;

ini_set('memory_limit', '4G');
ini_set('max_execution_time', 0);
require_once __DIR__.'/../../../vendor/autoload.php';
require_once api_get_path(SYS_CODE_PATH).'install/install.lib.php';

// Security: this endpoint carries no authentication of its own. Refuse it, exactly like
// index.php already does, once the instance is installed and has no pending migration --
// otherwise an anonymous caller can keep re-triggering a production migration forever
// (see GHSA-mfgc-693v-xq5v). A genuine upgrade still has pending migrations, so the 1.11.x
// and 2.x paths reach the migration below.
if (isInstallerLocked()) {
    http_response_code(409);
    header('Content-Type: application/json');
    echo json_encode([
        'status' => false,
        'message' => InstallerState::UpgradeNotAuthorised === resolveInstallerState()
            ? 'No '.InstallerGate::UPGRADE_FLAG_FILE.' found in the project root.'
            : 'Chamilo is already installed.',
        'progress_percentage' => 0,
        'current_migration' => '',
        'redirect_to_step7' => false,
    ]);

    exit;
}

$logFile = api_get_path(SYS_PATH) . '/../var/log/migration-to-2.0.log';

if (isset($_GET['updatePath'])) {
    $updatePath = strip_tags($_GET['updatePath']);
    putenv('UPDATE_PATH=' . $updatePath);
}

$response = executeMigration();

$logContent = '';
if (file_exists($logFile)) {
    chmod($logFile, 0644);
    $logContent = file_get_contents($logFile);
}

if (!$response['status']) {
    http_response_code(500); // Return a 500 Internal Server Error if migration failed
} else {
    $kernel = new \Chamilo\Kernel('dev', true);
    $kernel->boot();

    executeLexikKeyPair($kernel);
}

$response = [
    'log_terminal' => '<pre class="terminal">' . $logContent . '</pre>',
    'progress_percentage' => $response['progress_percentage'],
    'message' => $response['message'],
    'upgrade_flag_warning' => $response['upgrade_flag_warning'] ?? '',
    'current_migration' => $response['current_migration'],
    'redirect_to_step7' => $response['status'] === true,
];

header('Content-Type: application/json');
echo json_encode($response);
