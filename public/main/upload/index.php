<?php

/* For licensing terms, see /license.txt */

// Deprecated file left here because learning paths are imported from the Vue tool. It only redirects old links.
require_once __DIR__.'/../inc/global.inc.php';

api_protect_course_script(true);

$courseResourceNodeId = (int) (api_get_course_entity()?->getResourceNode()?->getId() ?? 0);
if ($courseResourceNodeId <= 0) {
    header('Location: '.api_get_path(WEB_PATH));
    exit;
}

$query = ['cid' => api_get_course_int_id()];
if (api_get_session_id() > 0) {
    $query['sid'] = api_get_session_id();
}
if (api_get_group_id() > 0) {
    $query['gid'] = api_get_group_id();
}

header('Location: '.api_get_path(WEB_PATH).'resources/lp/'.$courseResourceNodeId.'/import?'.http_build_query($query));
exit;
