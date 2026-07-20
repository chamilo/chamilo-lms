<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CLp;

require_once __DIR__.'/../inc/global.inc.php';

api_protect_course_script();

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

$isStudentView = isset($_REQUEST['isStudentView'])
    ? ('true' === (string) $_REQUEST['isStudentView'] ? 'true' : 'false')
    : (api_is_allowed_to_edit(null, true, false, false) ? 'false' : 'true');

$query = http_build_query([
    'cid' => (int) $course->getId(),
    'sid' => api_get_session_id(),
    'gid' => api_get_group_id(),
    'gradebook' => isset($_REQUEST['gradebook']) ? (int) $_REQUEST['gradebook'] : 0,
    'origin' => 'learnpath',
    'isStudentView' => $isStudentView,
]);

header(
    'Location: '.api_get_path(WEB_PATH).'resources/lp/'.$courseNodeId.'/'.$learningPathId.'/runtime?'.$query,
    true,
    302,
);
exit;
