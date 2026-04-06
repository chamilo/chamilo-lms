<?php

/* For licensing terms, see /license.txt */

/**
 * Return the configured Intro.js steps for the current page.
 */

require_once __DIR__.'/../config.php';

header('Content-Type: application/json; charset=utf-8');

api_block_anonymous_users();

if ('GET' !== $_SERVER['REQUEST_METHOD']) {
    http_response_code(405);
    echo json_encode([
        'error' => true,
        'message' => 'Method not allowed.',
    ]);
    exit;
}

try {
    $tourPlugin = Tour::create();

    // Do not expose steps when the plugin is disabled or the feature is off.
    if (!$tourPlugin->isTourAvailable()) {
        echo json_encode([]);
        exit;
    }

    $page = isset($_GET['page']) ? trim((string) $_GET['page']) : '';
    $pageName = isset($_GET['page_name']) ? trim((string) $_GET['page_name']) : '';
    $pageClass = isset($_GET['page_class']) ? trim((string) $_GET['page_class']) : '';

    $page = mb_substr($page, 0, 255);
    $pageName = mb_substr($pageName, 0, 255);
    $pageClass = mb_substr($pageClass, 0, 255);

    if ('' !== $page && '' === $pageClass) {
        $pageClass = $page;
    }

    $tourDefinition = null;

    if ('' !== $pageName) {
        $tourDefinition = $tourPlugin->getTourByName($pageName);
    }

    if (!$tourDefinition && '' !== $pageClass) {
        $tourDefinition = $tourPlugin->getTourByPageClass($pageClass);
    }

    if (!$tourDefinition) {
        echo json_encode([]);
        exit;
    }

    $configuredSteps = $tourDefinition['steps'] ?? [];
    $steps = [];

    foreach ($configuredSteps as $step) {
        if (!is_array($step)) {
            continue;
        }

        $elementSelector = isset($step['elementSelector'])
            ? trim((string) $step['elementSelector'])
            : '';

        $title = isset($step['title'])
            ? trim((string) $step['title'])
            : '';

        if ('' === $title && isset($step['titleMessage'])) {
            $title = trim((string) $tourPlugin->get_lang((string) $step['titleMessage']));
        }

        $content = isset($step['content'])
            ? trim((string) $step['content'])
            : '';

        if ('' === $content && isset($step['message'])) {
            $content = trim((string) $tourPlugin->get_lang((string) $step['message']));
        }

        $position = isset($step['position'])
            ? trim((string) $step['position'])
            : 'bottom';

        if ('' === $content) {
            continue;
        }

        $introStep = [
            'intro' => $content,
            'position' => '' !== $position ? $position : 'bottom',
        ];

        if ('' !== $title) {
            $introStep['title'] = $title;
        }

        if ('' !== $elementSelector) {
            $introStep['element'] = $elementSelector;
        }

        $steps[] = $introStep;
    }

    echo json_encode($steps);
} catch (\Throwable $e) {
    error_log('[Tour][steps.ajax] '.$e->getMessage());

    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => 'Unable to load tour steps.',
    ]);
}
