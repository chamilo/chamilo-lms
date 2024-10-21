<?php

require_once __DIR__.'/../../../vendor/autoload.php';
require_once __DIR__.'/../../../app/AppKernel.php';

$kernel = new AppKernel('', '');

// Check for 'action' parameter in the GET request
if (isset($_GET['action'])) {
    $action = $_GET['action'];

    if ($action == 'time') {
        // Load the Chamilo configuration
        $alreadyInstalled = false;
        if (file_exists($kernel->getConfigurationFile())) {
            require_once $kernel->getConfigurationFile();
            $alreadyInstalled = true;
        }

        // Load the API library BEFORE loading the Chamilo configuration
        require_once $_configuration['root_sys'].'main/inc/lib/api.lib.php';

        if (api_get_configuration_value('session_lifetime_controller')) {
            // Get the session
            session_name('ch_sid');
            session_start();

            $session = new ChamiloSession();

            $endTime = 0;
            $isExpired = false;
            $timeLeft = -1;

            $currentTime = time();

            // Existing code for time action
            if ($alreadyInstalled && api_get_user_id()) {
                $endTime = $session->end_time();
                $isExpired = $session->is_expired();
            } else {
                // Chamilo not installed or user not logged in
                $endTime = $currentTime + 315360000; // This sets a default end time far in the future
                $isExpired = false;
            }

            $timeLeft = $endTime - $currentTime;
        } else {
            $endTime = 999999;
            $isExpired = false;
            $timeLeft = 999999;
        }

        if ($endTime > 0) {
            echo json_encode(['sessionEndDate' => $endTime, 'sessionTimeLeft' => $timeLeft, 'sessionExpired' => $isExpired]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Error retrieving data from the current session']);
        }
    } elseif ($action == 'logout') {
        require_once __DIR__.'/../../../main/inc/global-min.inc.php';

        $userId = api_get_user_id();
        online_logout($userId, false);
        echo json_encode(['message' => 'Logged out successfully']);
    } else {
        // Handle unexpected action value
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action parameter']);
    }
} else {
    // No action provided
    http_response_code(400);
    echo json_encode(['error' => 'No action parameter provided']);
}
