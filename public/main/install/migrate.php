<?php
/* For licensing terms, see /license.txt */

use Symfony\Component\Dotenv\Dotenv;

ini_set('memory_limit', '4G');
ini_set('max_execution_time', 0);
require_once __DIR__.'/../../../vendor/autoload.php';
require_once api_get_path(SYS_CODE_PATH).'install/install.lib.php';

// Security: this endpoint carries no authentication of its own. Refuse it, exactly like
// index.php already does, once the instance is fully configured and its database already
// has settings rows -- otherwise an anonymous caller can keep re-triggering a production
// migration forever (see GHSA-mfgc-693v-xq5v). During a genuine legacy (1.11.x) upgrade,
// .env does not exist yet, so this check is skipped and the wizard behaves as before.
$envFile = api_get_path(SYMFONY_SYS_PATH).'.env';
if (file_exists($envFile)) {
    $dotenv = new Dotenv();

    try {
        $dotenv->loadEnv($envFile);
    } catch (Throwable $e) {
        // Ignore and let the checks below still gate the request.
    }

    $appInstalled = '1' === (string) (
        $_SERVER['APP_INSTALLED']
            ?? $_ENV['APP_INSTALLED']
            ?? getenv('APP_INSTALLED')
            ?? ''
    );

    $versionInfo = require __DIR__.'/version.php';
    $installerVersion = $versionInfo['new_version'] ?? null;

    if ($appInstalled && $installerVersion) {
        $dbLooksInitialized = false;

        try {
            $dbHost = (string) ($_SERVER['DATABASE_HOST'] ?? $_ENV['DATABASE_HOST'] ?? getenv('DATABASE_HOST') ?? 'localhost');
            $dbUser = (string) ($_SERVER['DATABASE_USER'] ?? $_ENV['DATABASE_USER'] ?? getenv('DATABASE_USER') ?? '');
            $dbPass = (string) ($_SERVER['DATABASE_PASSWORD'] ?? $_ENV['DATABASE_PASSWORD'] ?? getenv('DATABASE_PASSWORD') ?? '');
            $dbName = (string) ($_SERVER['DATABASE_NAME'] ?? $_ENV['DATABASE_NAME'] ?? getenv('DATABASE_NAME') ?? '');
            $dbPort = (int) ($_SERVER['DATABASE_PORT'] ?? $_ENV['DATABASE_PORT'] ?? getenv('DATABASE_PORT') ?? 3306);

            connectToDatabase($dbHost, $dbUser, $dbPass, $dbName, $dbPort);

            $conn = Database::getManager()->getConnection();

            try {
                $hasAnySetting = $conn->fetchOne('SELECT 1 FROM settings_current LIMIT 1');
                if (false !== $hasAnySetting && null !== $hasAnySetting) {
                    $dbLooksInitialized = true;
                }
            } catch (Throwable $e) {
                // Ignore and try the legacy table.
            }

            if (!$dbLooksInitialized) {
                try {
                    $hasAnySetting = $conn->fetchOne('SELECT 1 FROM settings LIMIT 1');
                    if (false !== $hasAnySetting && null !== $hasAnySetting) {
                        $dbLooksInitialized = true;
                    }
                } catch (Throwable $e) {
                    // No settings tables -> DB is not initialized.
                }
            }
        } catch (Throwable $e) {
            // If we cannot connect, do not block the request.
            $dbLooksInitialized = false;
        }

        if ($dbLooksInitialized) {
            http_response_code(409);
            header('Content-Type: application/json');
            echo json_encode([
                'status' => false,
                'message' => 'Chamilo is already installed.',
                'progress_percentage' => 0,
                'current_migration' => '',
                'redirect_to_step7' => false,
            ]);
            exit;
        }
    }
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
    'current_migration' => $response['current_migration'],
    'redirect_to_step7' => $response['status'] === true,
];

header('Content-Type: application/json');
echo json_encode($response);
