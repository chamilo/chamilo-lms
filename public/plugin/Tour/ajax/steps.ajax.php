<?php

/* For licensing terms, see /license.txt */

/**
 * Return the configured Intro.js steps for the current page.
 */

require_once __DIR__.'/../config.php';

header('Content-Type: application/json; charset=utf-8');

api_block_anonymous_users();

try {
    $tourPlugin = Tour::create();

    $page = isset($_GET['page']) ? trim((string) $_GET['page']) : '';
    $pageName = isset($_REQUEST['page_name']) ? trim((string) $_REQUEST['page_name']) : '';
    $pageClass = isset($_REQUEST['page_class']) ? trim((string) $_REQUEST['page_class']) : '';

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
