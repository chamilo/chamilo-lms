<?php

/* For licensing terms, see /license.txt */

require_once __DIR__.'/../../../main/inc/global.inc.php';

api_protect_course_script(true);
api_block_anonymous_users();
api_protect_teacher_script();

$course = api_get_course_entity(api_get_course_int_id());
$courseNodeId = (int) ($course?->getResourceNode()?->getId() ?? 0);

if ($courseNodeId <= 0) {
    api_not_allowed(true);
}

$categoryId = isset($_GET['selectcat']) ? max(0, (int) $_GET['selectcat']) : 0;
$query = api_get_cidreq();
$query .= '&'.http_build_query([
    'categoryId' => $categoryId,
    'source' => 'lti',
]);

header('Location: '.api_get_path(WEB_PATH).'resources/gradebook/'.$courseNodeId.'/?'.$query);
exit;
