<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

$cidReset = true;

require_once '../config.php';

header('Content-Type: application/json; charset=UTF-8');

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
        'enabled' => false,
        'canCreate' => true,
        'hasServiceOptions' => false,
        'standard' => null,
        'services' => [],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

try {
    $options = $plugin->getCourseCreationOptionsForUser($currentUserId);

    echo json_encode([
        'success' => true,
        'enabled' => true,
    ] + $options, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $exception) {
    error_log(
        '[BuyCourses][CourseCreationOptions] Failed to build options for user '.$currentUserId.
        ' error='.$exception->getMessage()
    );

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Unable to load BuyCourses course creation options.',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
