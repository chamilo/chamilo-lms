<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

// Deprecated file left here to redirect old links to the links tool in Vue. Should be removed before the next major version.
require_once __DIR__.'/../inc/global.inc.php';

api_protect_course_script(true);

$nodeId = (int) api_get_course_entity()?->getResourceNode()?->getId();
if (0 === $nodeId) {
    api_not_allowed(true);
}

header('Location: '.api_get_path(WEB_PATH).'resources/links/'.$nodeId.'/?'.http_build_query([
    'cid' => api_get_course_int_id(),
    'sid' => api_get_session_id(),
    'gid' => api_get_group_id(),
]));
exit;
