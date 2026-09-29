<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

require_once __DIR__.'/../inc/global.inc.php';

$current_course_tool = TOOL_COURSE_DESCRIPTION;
$this_section = SECTION_COURSES;

$rawAction = $_GET['action'] ?? 'listing';
$action = \is_string($rawAction) ? trim($rawAction) : 'listing';

if (!\in_array($action, ['listing', 'history', 'add', 'edit', 'delete'], true)) {
    $action = 'listing';
}

$rawStudentView = $_GET['isStudentView'] ?? null;
$studentView = \is_scalar($rawStudentView)
    && filter_var($rawStudentView, FILTER_VALIDATE_BOOLEAN);

if ($studentView) {
    $action = 'listing';
}

Event::registerLog([
    'tool' => TOOL_COURSE_DESCRIPTION,
    'action' => $action,
]);

api_protect_course_script(true);
Event::event_access_tool(TOOL_COURSE_DESCRIPTION);

$course = api_get_course_entity();
$courseResourceNode = $course?->getResourceNode();

if (null === $course || null === $courseResourceNode || null === $courseResourceNode->getId()) {
    api_not_allowed(true);
}

$rawDescriptionType = $_GET['description_type'] ?? null;
$descriptionType = \is_scalar($rawDescriptionType) ? (int) $rawDescriptionType : 0;
if ($descriptionType >= 8) {
    $descriptionType = 8;
}

$rawDescriptionId = $_GET['id'] ?? null;
$descriptionId = \is_scalar($rawDescriptionId) ? (int) $rawDescriptionId : 0;
$targetPath = 'resources/course-description/'.$courseResourceNode->getId().'/';

if ('add' === $action) {
    $targetPath .= 'add';
    if ($descriptionType <= 0) {
        $descriptionType = 8;
    }
} elseif ('edit' === $action) {
    $targetPath .= 'edit';
    if ($descriptionId > 0) {
        $targetPath .= '/'.$descriptionId;
    }
}

$query = [
    'cid' => (int) $course->getId(),
];

$sessionId = api_get_session_id();
if ($sessionId > 0) {
    $query['sid'] = $sessionId;
}

$groupId = api_get_group_id();
if ($groupId > 0) {
    $query['gid'] = $groupId;
}

if (null !== $rawStudentView) {
    $query['isStudentView'] = $studentView ? 'true' : 'false';
}

if ($descriptionType > 0 && \in_array($action, ['add', 'edit'], true)) {
    $query['descriptionType'] = $descriptionType;
}

api_location(api_get_path(WEB_PATH).$targetPath.'?'.http_build_query($query));
