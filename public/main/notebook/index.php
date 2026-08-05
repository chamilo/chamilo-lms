<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

require_once __DIR__.'/../inc/global.inc.php';

$current_course_tool = TOOL_NOTEBOOK;
$this_section = SECTION_COURSES;

api_protect_course_script(true);

$course = api_get_course_entity();
$nodeId = $course?->getResourceNode()?->getId();
if (null === $nodeId) {
    api_not_allowed();
}

$params = [
    'cid' => api_get_course_int_id(),
];

$sessionId = api_get_session_id();
if ($sessionId > 0) {
    $params['sid'] = $sessionId;
}

$groupId = api_get_group_id();
if ($groupId > 0) {
    $params['gid'] = $groupId;
}

if (isset($_GET['isStudentView'])) {
    $params['isStudentView'] = 1;
}

$action = (string) ($_GET['action'] ?? '');
$path = '/resources/notebook/'.(int) $nodeId.'/';

if ('addnote' === $action) {
    $path .= 'add';
} elseif ('editnote' === $action) {
    $noteId = filter_input(INPUT_GET, 'notebook_id', FILTER_VALIDATE_INT);
    if (\is_int($noteId) && $noteId > 0) {
        $path .= 'edit/'.$noteId;
    }
} elseif ('changeview' === $action) {
    $view = (string) ($_GET['view'] ?? '');
    if (\in_array($view, ['creation_date', 'update_date', 'title'], true)) {
        $params['sort'] = $view;
    }

    $params['direction'] = 'DESC' === strtoupper((string) ($_GET['direction'] ?? '')) ? 'DESC' : 'ASC';
} elseif ('deletenote' === $action) {
    // Destructive legacy GET actions are intentionally never replayed after the Vue cutover.
    $params['legacyAction'] = 'deletenote';
}

header('Location: '.$path.'?'.http_build_query($params));
exit;
