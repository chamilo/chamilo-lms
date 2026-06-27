<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CLp;

require_once __DIR__.'/../inc/global.inc.php';

api_protect_course_script(true);
if (!api_is_allowed_to_edit(null, true)) {
    api_not_allowed(true);
}

$learningPathId = isset($_REQUEST['lp_id']) ? (int) $_REQUEST['lp_id'] : 0;
$course = api_get_course_entity();
$learningPath = Container::getLpRepository()->find($learningPathId);

if (!$course instanceof Course || !$learningPath instanceof CLp) {
    api_not_allowed(true);
}

$courseNodeId = (int) ($course->getResourceNode()?->getId() ?? 0);
if ($courseNodeId <= 0) {
    api_not_allowed(true);
}

$query = [
    'cid' => (int) $course->getId(),
    'sid' => api_get_session_id(),
    'gid' => api_get_group_id(),
    'isStudentView' => 'false',
];

$groupFilter = trim((string) ($_REQUEST['group_filter'] ?? ''));
if ('' !== $groupFilter) {
    $query['groupFilter'] = $groupFilter;
}

if (isset($_REQUEST['show_teachers']) && 1 === (int) $_REQUEST['show_teachers']) {
    $query['showTeachers'] = 1;
}

$studentId = isset($_REQUEST['student_id']) ? (int) $_REQUEST['student_id'] : 0;
if ($studentId > 0) {
    $query['studentId'] = $studentId;
}

if (isset($_REQUEST['export']) && 'pdf' === (string) $_REQUEST['export']) {
    header(
        'Location: '.api_get_path(WEB_PATH).'api/learning_paths/'.$learningPathId.'/reporting.pdf?'.http_build_query($query),
        true,
        302,
    );
    exit;
}

header(
    'Location: '.api_get_path(WEB_PATH).'resources/lp/'.$courseNodeId.'/'.$learningPathId.'/reporting?'.http_build_query($query),
    true,
    302,
);
exit;
