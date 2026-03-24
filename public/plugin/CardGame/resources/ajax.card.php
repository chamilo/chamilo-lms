<?php

/* For license terms, see /license.txt */

require_once __DIR__.'/../../../main/inc/global.inc.php';
require_once __DIR__.'/../CardGame.php';

header('Content-Type: application/json');

if (api_is_anonymous()) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Authentication required.',
    ]);
    exit;
}

$cardGame = CardGame::create();
$userId = (int) api_get_user_id();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ('' === $action && (isset($_POST['part']) || isset($_GET['part']))) {
    $action = 'reveal';
}

if ('' === $action && (isset($_POST['loose']) || isset($_GET['loose']))) {
    $action = 'lose';
}

switch ($action) {
    case 'reveal':
        $part = (int) ($_POST['part'] ?? $_GET['part'] ?? 0);
        $response = $cardGame->revealPart($userId, $part);
        break;
    case 'lose':
        $response = $cardGame->markLoss($userId);
        break;
    default:
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid action.',
        ]);
        exit;
}

$response['displayPan'] = $cardGame->getDisplayPan((int) ($response['pan'] ?? 1));
$response['parts'] = array_values(array_map('intval', $response['parts'] ?? []));

echo json_encode($response);
