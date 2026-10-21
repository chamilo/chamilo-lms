<?php

/* For licensing terms, see /license.txt */

// Deprecated file left here because the announcements tool moved to Vue. It only redirects old links.
require_once __DIR__.'/../inc/global.inc.php';

$courseResourceNodeId = (int) (api_get_course_entity()?->getResourceNode()?->getId() ?? 0);
if ($courseResourceNodeId <= 0) {
    header('Location: '.api_get_path(WEB_PATH));
    exit;
}

$action = $_GET['action'] ?? '';
$announcementId = (int) ($_GET['ann_id'] ?? $_GET['id'] ?? 0);

$path = '';
if ('add' === $action) {
    $path = 'add';
} elseif ('modify' === $action && $announcementId > 0) {
    $path = 'edit/'.$announcementId;
} elseif (('view' === $action || isset($_GET['ann_id'])) && $announcementId > 0) {
    $path = 'view/'.$announcementId;
}

$query = [
    'cid' => api_get_course_int_id(),
    'sid' => api_get_session_id(),
    'gid' => api_get_group_id(),
];
foreach (['remind_inactive', 'remindallinactives', 'since'] as $key) {
    if (isset($_GET[$key]) && is_scalar($_GET[$key])) {
        $query[$key] = (string) $_GET[$key];
    }
}

header('Location: '.api_get_path(WEB_PATH).'resources/announcement/'.$courseResourceNodeId.'/'.$path.'?'.http_build_query($query));
exit;
