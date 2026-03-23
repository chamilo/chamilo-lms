<?php

/* For licensing terms, see /license.txt */

/**
 * Save the completion state of a page tour.
 *
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 */

require_once __DIR__.'/../config.php';

header('Content-Type: application/json; charset=utf-8');

api_block_anonymous_users();

try {
    $pageName = isset($_POST['page_name']) ? trim((string) $_POST['page_name']) : '';
    $pageClass = isset($_POST['page_class']) ? trim((string) $_POST['page_class']) : '';

    $resolvedPageName = '' !== $pageName ? $pageName : $pageClass;

    if ('' === $resolvedPageName) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Missing page identifier.',
        ]);
        exit;
    }

    $userId = api_get_user_id();
    $tourPlugin = Tour::create();

    $saved = $tourPlugin->saveCompletedTour($resolvedPageName, $userId);

    echo json_encode([
        'success' => true,
        'saved' => $saved,
        'page' => $resolvedPageName,
    ]);
} catch (\Throwable $e) {
    error_log('[Tour][save.ajax] '.$e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Unable to save tour state.',
    ]);
}
