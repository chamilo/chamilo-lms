<?php

declare(strict_types=1);

/* Deprecated compatibility entry point. */

require_once __DIR__.'/../inc/global.inc.php';

api_block_anonymous_users();

$query = [];
$map = [
    'project_id' => 'project_id',
    'keyword' => 'keyword',
    'keyword_category' => 'category_id',
    'keyword_status' => 'status_id',
    'keyword_priority' => 'priority_id',
    'keyword_assigned_to' => 'assigned_user_id',
    'keyword_course' => 'course',
    'keyword_start_date_start' => 'start_date',
    'keyword_start_date_end' => 'end_date',
];
foreach ($map as $legacy => $modern) {
    if (isset($_GET[$legacy]) && '' !== (string) $_GET[$legacy]) {
        $query[$modern] = (string) $_GET[$legacy];
    }
}

if ('export' === ($_GET['action'] ?? '') && api_is_platform_admin()) {
    $exportQuery = [
        'projectId' => (string) (int) ($_GET['project_id'] ?? 1),
        'keyword' => (string) ($_GET['keyword'] ?? ''),
        'categoryId' => (string) ($_GET['keyword_category'] ?? ''),
        'statusId' => (string) ($_GET['keyword_status'] ?? ''),
        'priorityId' => (string) ($_GET['keyword_priority'] ?? ''),
        'assignedUserId' => (string) ($_GET['keyword_assigned_to'] ?? ''),
        'course' => (string) ($_GET['keyword_course'] ?? ''),
        'startDate' => (string) ($_GET['keyword_start_date_start'] ?? ''),
        'endDate' => (string) ($_GET['keyword_start_date_end'] ?? ''),
    ];
    $exportQuery = array_filter($exportQuery, static fn (string $value): bool => '' !== $value);
    header('Location: '.api_get_path(WEB_PATH).'api/ticket/admin/export?'.http_build_query($exportQuery));

    exit;
}

header('Location: '.api_get_path(WEB_PATH).'tickets'.([] !== $query ? '?'.http_build_query($query) : ''));

exit;
