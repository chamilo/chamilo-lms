<?php

/* For licensing terms, see /license.txt */

/**
 * Sets needed course variables and then jumps to the exercises result page.
 * This intermediate page is needed because the user is not inside a course
 * when visiting the gradebook, and several course scripts rely on these
 * variables.
 * Most code here is ripped from /main/course_home/course_home.php.
 *
 * @author Bert Steppé
 */
require_once __DIR__.'/../inc/global.inc.php';
api_block_anonymous_users();
$this_section = SECTION_COURSES;

$gradebook = Security::remove_XSS($_GET['gradebook']);
$session_id = api_get_session_id();
$courseId = Security::remove_XSS($_GET['cid']);
$type = Security::remove_XSS($_GET['type']);
$doExerciseUrl = '';

// no support for hot potatoes
/*if ($type == LINK_HOTPOTATOES) {
    $exerciseId = $_GET['exerciseId'];
    $path = Security::remove_XSS($_GET['path']);
    $doExerciseUrl = api_get_path(WEB_CODE_PATH).'exercise/showinframes.php?'.http_build_query(
        [
            'session_id' => $session_id,
            'cidReq' => Security::remove_XSS($cidReq),
            'file' => $path,
            'cid' => api_get_course_id(),
            'uid' => api_get_user_id(),
        ]
    );
    header('Location: '.$doExerciseUrl);
    exit;
}*/

if (!empty($doExerciseUrl)) {
    header('Location: '.$doExerciseUrl);
    exit;
} else {
    $url = api_get_path(WEB_CODE_PATH).'exercise/overview.php?'
        .http_build_query(['sid' => $session_id, 'cid' => $courseId]);
    if (isset($_GET['gradebook'])) {
        $exerciseId = (int) $_GET['exerciseId'];
        $url = api_get_path(WEB_CODE_PATH).'exercise/overview.php?'.http_build_query(
            [
                'sid' => $session_id,
                'cid' => $courseId,
                'gradebook' => $gradebook,
                'origin' => '',
                'learnpath_id' => '',
                'learnpath_item_id' => '',
                'exerciseId' => $exerciseId,
            ]
        );

        // Exercise IDs can be reused by migrated LP items from other courses.
        // Redirect only when exactly one LP in the current course contains it.
        $lpList = Exercise::getLpListFromExercise($exerciseId, (int) $courseId);

        if (1 === count($lpList)) {
            $firstLp = reset($lpList);
            $lpId = (int) ($firstLp['lp_id'] ?? 0);

            if ($lpId > 0) {
                $url = api_get_path(WEB_CODE_PATH).'lp/lp_controller.php?'.api_get_cidreq().'&'
                    .http_build_query(
                        [
                            'lp_id' => $lpId,
                            'action' => 'view',
                            'isStudentView' => 'true',
                        ]
                    );
            }
        }
    }

    header('Location: '.$url);
    exit;
}
