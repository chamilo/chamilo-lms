<?php

/* For license terms, see /license.txt */

require_once __DIR__.'/../../main/inc/global.inc.php';

header('Content-Type: application/json; charset=UTF-8');

function learning_calendar_empty_response(): void
{
    http_response_code(200);
    echo json_encode([
        'events' => [],
    ]);
    exit;
}

function learning_calendar_table_exists(string $tableName): bool
{
    $tableName = Database::escape_string($tableName);
    $result = Database::query("SHOW TABLES LIKE '$tableName'");

    return false !== $result && Database::num_rows($result) > 0;
}

try {
    $plugin = LearningCalendarPlugin::create();

    if (!$plugin->isEnabled()) {
        learning_calendar_empty_response();
    }

    $userId = api_get_user_id();

    if (empty($userId)) {
        learning_calendar_empty_response();
    }

    $requiredTables = [
        'learning_calendar',
        'learning_calendar_events',
        'learning_calendar_user',
    ];

    foreach ($requiredTables as $requiredTable) {
        if (!learning_calendar_table_exists($requiredTable)) {
            learning_calendar_empty_response();
        }
    }

    $startDate = isset($_GET['startDate']) ? (string) $_GET['startDate'] : '';
    $endDate = isset($_GET['endDate']) ? (string) $_GET['endDate'] : '';

    echo json_encode([
        'events' => $plugin->getPersonalEventsForApi((int) $userId, $startDate, $endDate),
    ]);
} catch (Throwable $exception) {
    learning_calendar_empty_response();
}
