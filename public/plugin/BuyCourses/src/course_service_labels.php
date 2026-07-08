<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

$cidReset = true;

require_once '../config.php';

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, private');

$plugin = BuyCoursesPlugin::create();
$currentUserId = api_get_user_id();

if ($currentUserId <= 0) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => get_lang('Authentication required.'),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (!$plugin->isEnabled(true) || 'true' !== $plugin->get('include_services')) {
    echo json_encode([
        'success' => true,
        'courses' => [],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$rawCourseIds = trim((string) ($_GET['course_ids'] ?? ''));
$courseIds = array_values(array_unique(array_filter(
    array_map('intval', explode(',', $rawCourseIds)),
    static fn (int $courseId): bool => $courseId > 0
)));

if (empty($courseIds)) {
    echo json_encode([
        'success' => true,
        'courses' => [],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

try {
    $labels = $plugin->getManagedCourseServiceLabelsForUser($currentUserId, $courseIds);

    echo json_encode([
        'success' => true,
        'courses' => $labels,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $exception) {
    error_log(
        '[BuyCourses][CourseServiceLabels] Failed to load labels for user '.$currentUserId.
        ' error='.$exception->getMessage()
    );

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Unable to load BuyCourses course service labels.',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
