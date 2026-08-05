<?php

/* For licensing terms, see /license.txt */

// Make sure we void the course context if we are in the social network section.
if (empty($_GET['cid'])) {
    $cidReset = true;
}
require_once __DIR__.'/../inc/global.inc.php';

api_block_anonymous_users();

if (api_get_course_entity()) {
    api_protect_course_script(true);
}

if ('GET' !== ($_SERVER['REQUEST_METHOD'] ?? 'GET')) {
    http_response_code(405);
    header('Allow: GET');
    exit;
}

$action = (string) ($_GET['action'] ?? 'list');
$id = (int) ($_GET['id'] ?? 0);
$course = api_get_course_entity();
$query = $_GET;
unset($query['action'], $query['id'], $query['legacy']);

if ($course) {
    $nodeId = (int) ($course->getResourceNode()?->getId() ?? 0);
    $target = '/resources/portfolio/'.$nodeId.'/';
} else {
    $target = '/social/portfolio';
}

switch ($action) {
    case 'add_item':
        $target .= $course ? 'add' : '/add';
        break;
    case 'edit_item':
        if ($id > 0) {
            $target .= ($course ? 'edit/' : '/edit/').$id;
        }
        break;
    case 'view':
        if ($id > 0) {
            $target .= ($course ? 'item/' : '/item/').$id;
        }
        break;
    case 'details':
    case 'export_pdf':
    case 'export_zip':
        $target .= $course ? 'details' : '/details';
        break;
    case 'list_categories':
    case 'add_category':
    case 'edit_category':
    case 'translate_category':
        $target .= $course ? 'categories' : '/categories';
        break;
    case 'tags':
    case 'edit_tag':
        $target .= $course ? 'tags' : '/tags';
        break;
    case 'list':
        break;
    default:
        $query['legacyAction'] = 'blocked';
        break;
}

header('Location: '.$target.([] !== $query ? '?'.http_build_query($query) : ''), true, 302);
exit;
