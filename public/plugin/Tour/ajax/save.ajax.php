<?php

/* For licensing terms, see /license.txt */

/**
 * Save the completion state of a configured page tour.
 *
 * This endpoint is intentionally restricted to authenticated POST requests
 * protected by a CSRF token.
 *
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 */

require_once __DIR__.'/../config.php';

header('Content-Type: application/json; charset=utf-8');

api_block_anonymous_users();

if ('POST' !== $_SERVER['REQUEST_METHOD']) {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed.',
    ]);
    exit;
}

if (!Security::check_token('post')) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid security token.',
    ]);
    exit;
}

Security::clear_token();

try {
    $pageName = isset($_POST['page_name']) ? trim((string) $_POST['page_name']) : '';
    $pageClass = isset($_POST['page_class']) ? trim((string) $_POST['page_class']) : '';

    $pageName = mb_substr($pageName, 0, 255);
    $pageClass = mb_substr($pageClass, 0, 255);

    $tourPlugin = Tour::create();
    $resolvedPageClass = $tourPlugin->resolveConfiguredPageClass($pageName, $pageClass);

    if (null === $resolvedPageClass) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid page identifier.',
        ]);
        exit;
    }

    $saved = $tourPlugin->saveCompletedTour($resolvedPageClass, api_get_user_id());

    echo json_encode([
        'success' => true,
        'saved' => $saved,
        'page' => $resolvedPageClass,
    ]);
} catch (\Throwable $e) {
    error_log('[Tour][save.ajax] '.$e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Unable to save tour state.',
    ]);
}
