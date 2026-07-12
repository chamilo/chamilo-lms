<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

require_once __DIR__.'/../inc/global.inc.php';

$this_section = SECTION_COURSES;
$current_course_tool = TOOL_COURSE_PROGRESS;

api_protect_course_script(true);

$courseId = api_get_course_int_id();
$course = api_get_course_entity();
$resourceNode = $course?->getResourceNode();

if (null === $course || null === $resourceNode) {
    api_not_allowed(true);
}

$sessionId = (int) api_get_session_id();
$groupId = (int) api_get_group_id();
$studentViewKey = 'student_view_course_'.$courseId;

if (isset($_GET['switch_student_view'])) {
    Session::write($studentViewKey, '1' === (string) $_GET['switch_student_view'] ? 1 : 0);
}

$query = [
    'cid' => $courseId,
    'sid' => $sessionId,
    'gid' => $groupId,
];

if (isset($_GET['isStudentView']) && in_array($_GET['isStudentView'], ['true', 'false'], true)) {
    $query['isStudentView'] = (string) $_GET['isStudentView'];
}

$courseProgressUrl = api_get_path(WEB_PATH).'resources/course-progress/'.$resourceNode->getId().'/';
$action = isset($_REQUEST['action']) ? (string) $_REQUEST['action'] : 'thematic_details';
$thematicId = isset($_REQUEST['thematic_id']) ? (int) $_REQUEST['thematic_id'] : 0;
$advanceId = isset($_REQUEST['thematic_advance_id']) ? (int) $_REQUEST['thematic_advance_id'] : 0;
$targetUrl = $courseProgressUrl;

switch ($action) {
    case 'thematic_add':
        $targetUrl .= 'add';

        break;

    case 'thematic_edit':
        if ($thematicId > 0) {
            $targetUrl .= 'edit/'.$thematicId;
        }

        break;

    case 'thematic_import_select':
    case 'thematic_import':
        $targetUrl .= 'import';

        break;

    case 'thematic_export':
        $targetUrl = api_get_path(WEB_PATH).'api/course-progress/export.csv';

        break;

    case 'thematic_export_pdf':
        $targetUrl = api_get_path(WEB_PATH).'api/course-progress/export.pdf';

        break;

    case 'thematic_plan_list':
    case 'thematic_plan_add':
    case 'thematic_plan_edit':
    case 'thematic_plan_delete':
        if ($thematicId > 0) {
            $targetUrl .= 'plan/'.$thematicId;
        }

        break;

    case 'thematic_advance_list':
        if ($thematicId > 0) {
            $targetUrl .= 'advance/'.$thematicId;
        }

        break;

    case 'thematic_advance_add':
        if ($thematicId > 0) {
            $targetUrl .= 'advance/'.$thematicId.'/add';
        }

        break;

    case 'thematic_advance_edit':
        if ($thematicId > 0 && $advanceId > 0) {
            $targetUrl .= 'advance/'.$thematicId.'/edit/'.$advanceId;
        }

        break;

    case 'export_single_thematic':
        if ($thematicId > 0) {
            $targetUrl = api_get_path(WEB_PATH).'api/course-progress/thematic/'.$thematicId.'/export.pdf';
        }

        break;

        // Legacy destructive GET actions intentionally return to the Vue list.
    case 'thematic_copy':
    case 'thematic_delete':
    case 'moveup':
    case 'movedown':
    case 'thematic_advance_delete':
    case 'export_documents':
    case 'export_single_documents':
    case 'thematic_details':
    case 'thematic_list':
    default:
        break;
}

$separator = str_contains($targetUrl, '?') ? '&' : '?';
header('Location: '.$targetUrl.$separator.http_build_query($query), true, 302);

exit;
