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

if ('POST' !== $_SERVER['REQUEST_METHOD']) {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed.',
    ]);
    exit;
}

$sessionToken = $_SESSION['cardgame_csrf_token'] ?? '';
$requestToken = (string) ($_POST['csrf_token'] ?? '');

if ('' === $sessionToken || '' === $requestToken || !hash_equals($sessionToken, $requestToken)) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid security token.',
    ]);
    exit;
}

$cardGame = CardGame::create();
$userId = (int) api_get_user_id();
$action = (string) ($_POST['action'] ?? '');

if ('' === $action && isset($_POST['part'])) {
    $action = 'reveal';
}

if ('' === $action && isset($_POST['loose'])) {
    $action = 'lose';
}

switch ($action) {
    case 'reveal':
        $part = (int) ($_POST['part'] ?? 0);
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
