<?php
/* For licensing terms, see /license.txt */

require_once '../../inc/global.inc.php';

api_protect_course_script();

// Only the teachers of a course using the tool read the analysis state (and report links) of its documents.
if ('true' !== api_get_setting('work.allow_compilatio_tool') || !api_is_allowed_to_edit(null, true)) {
    api_not_allowed(true);
}

if (isset($_GET['workid'])) {
    $workIdList = $_GET['workid'];    // list of workid separate by the :
    $workList = explode('a', $workIdList);
    $compilatio = new Compilatio();
    $result = '';
    foreach ($workList as $workId) {
        // The id comes from the request: only documents of the current course.
        if (!empty($workId) && workIsInCourse((int) $workId, api_get_course_int_id())) {
            $result .= $compilatio->giveWorkIdState($workId);
        }
    }
    echo $result;
}
